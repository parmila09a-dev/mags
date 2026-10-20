<?php
/**
 * Database access for homework, homework submissions and notes.
 * Removed homework and notes are hidden with mags_erp_hide(), never deleted.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- Homework ---------- */

/** Most files one homework can carry. */
define( 'MAGS_ERP_HOMEWORK_FILES', 10 );

/** One homework. A removed homework is returned only when $with_removed is true. */
function mags_erp_get_homework( $homework_id, $with_removed = false ) {
	global $wpdb;
	$homework = $homework_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'homework' ) . ' WHERE id = %d', $homework_id ) ) : null;
	return $homework && ( $with_removed || ! $homework->hidden_at ) ? $homework : null;
}

/** Homework for the given classes, newest first, with how many students have submitted and how many are checked. */
function mags_erp_classes_homework( $class_ids, $limit = 200, $removed = false ) {
	global $wpdb;
	if ( ! $class_ids ) { return array(); }
	$h = mags_erp_table( 'homework' );
	$s = mags_erp_table( 'submissions' );
	$t = mags_erp_table( 'students' );
	// Answers of students who are still in the school are counted, also of those who moved up to another class since (mags_erp_homework_students()).
	$count = "SELECT COUNT(*) FROM {$s} INNER JOIN {$t} ON {$t}.id = {$s}.student_id WHERE {$s}.homework_id = h.id AND {$t}.hidden_at IS NULL";
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT h.*, ({$count}) AS submitted_count, ({$count} AND {$s}.checked_at IS NOT NULL) AS checked_count FROM {$h} h WHERE h.class_id IN (" . mags_erp_id_list( $class_ids ) . ') AND h.hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) . ' ORDER BY h.created_at DESC, h.id DESC LIMIT %d', $limit ) );
}

/** Homework of one student's class, each row carrying that student's own submission (if any). */
function mags_erp_student_homework( $student, $limit = 200 ) {
	global $wpdb;
	$h = mags_erp_table( 'homework' );
	$s = mags_erp_table( 'submissions' );
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT h.*, s.id AS submission_id, s.file_id AS submission_file_id, s.student_note, s.submitted_at, s.marks, s.feedback, s.checked_at FROM {$h} h LEFT JOIN {$s} s ON s.homework_id = h.id AND s.student_id = %d WHERE h.class_id = %d AND h.hidden_at IS NULL" . mags_erp_student_since_sql( $student, 'h.created_at' ) . ' ORDER BY h.created_at DESC, h.id DESC LIMIT %d', $student->id, $student->class_id, $limit ) );
}

function mags_erp_create_homework( $class_id, $teacher_id, $title, $instructions, $due_date, $max_marks ) {
	global $wpdb;
	$saved = $wpdb->insert(
		mags_erp_table( 'homework' ),
		array( 'class_id' => $class_id, 'teacher_id' => $teacher_id, 'title' => $title, 'instructions' => $instructions, 'due_date' => $due_date ?: null, 'max_marks' => null === $max_marks ? null : $max_marks, 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%d', '%s', '%s', '%s', '%f', '%s' )
	);
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_homework', 'The homework could not be saved. Please try again.' );
}

/**
 * Change the text, due date or maximum marks of a homework. The class stays the
 * same because the answers belong to its students. The maximum cannot go below
 * marks that were already given.
 */
function mags_erp_update_homework( $homework_id, $title, $instructions, $due_date, $max_marks, $teacher_id ) {
	global $wpdb;
	if ( null !== $max_marks ) {
		$highest = $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(marks) FROM ' . mags_erp_table( 'submissions' ) . ' WHERE homework_id = %d', $homework_id ) );
		if ( null !== $highest && (float) $highest > (float) $max_marks ) { return new WP_Error( 'mags_erp_homework', sprintf( 'An answer already has %s marks. The maximum cannot be lower than that.', mags_erp_format_marks( $highest ) ) ); }
	}
	$updated = $wpdb->update(
		mags_erp_table( 'homework' ),
		array( 'title' => $title, 'instructions' => $instructions, 'due_date' => $due_date ?: null, 'max_marks' => null === $max_marks ? null : $max_marks, 'updated_at' => current_time( 'mysql' ), 'updated_by' => (int) $teacher_id ),
		array( 'id' => (int) $homework_id ),
		array( '%s', '%s', '%s', '%f', '%s', '%d' ),
		array( '%d' )
	);
	return false === $updated ? new WP_Error( 'mags_erp_homework', 'The homework could not be saved. Please try again.' ) : true;
}

/** Files of one homework, oldest first: each row is the file with ->link_id for removing it. */
function mags_erp_homework_files( $homework_id ) {
	$files = mags_erp_homework_files_map( array( $homework_id ) );
	return $files[ (int) $homework_id ] ?? array();
}

/** homework_id => list of file rows, for a whole list of homework in one query. */
function mags_erp_homework_files_map( $homework_ids ) {
	global $wpdb;
	$map = array();
	if ( ! $homework_ids ) { return $map; }
	$rows = $wpdb->get_results( 'SELECT f.*, l.id AS link_id, l.homework_id FROM ' . mags_erp_table( 'homework_files' ) . ' l INNER JOIN ' . mags_erp_table( 'files' ) . ' f ON f.id = l.file_id WHERE l.homework_id IN (' . mags_erp_id_list( $homework_ids ) . ') AND l.hidden_at IS NULL AND f.hidden_at IS NULL ORDER BY l.id' );
	foreach ( (array) $rows as $row ) { $map[ (int) $row->homework_id ][] = $row; }
	return $map;
}

function mags_erp_add_homework_files( $homework_id, $file_ids, $teacher_id ) {
	global $wpdb;
	foreach ( (array) $file_ids as $file_id ) {
		$wpdb->insert( mags_erp_table( 'homework_files' ), array( 'homework_id' => (int) $homework_id, 'file_id' => (int) $file_id, 'added_by' => (int) $teacher_id, 'created_at' => current_time( 'mysql' ) ), array( '%d', '%d', '%d', '%s' ) );
	}
}

/** One attachment link of a homework (not removed), or null. */
function mags_erp_get_homework_file_link( $link_id ) {
	global $wpdb;
	return $link_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'homework_files' ) . ' WHERE id = %d AND hidden_at IS NULL', $link_id ) ) : null;
}

/**
 * The students a homework was for: those in its class who were there when it
 * was given, and after them the students who answered it and have moved to
 * another class since (each with ->now_in, the label of their class now).
 * Students who came to the class later by a promotion are not listed.
 */
function mags_erp_homework_students( $homework ) {
	global $wpdb;
	$list = array();
	foreach ( mags_erp_class_students( (int) $homework->class_id ) as $student ) {
		if ( empty( $student->class_since ) || $student->class_since <= $homework->created_at ) { $list[ (int) $student->id ] = $student; }
	}
	$labels = mags_erp_all_class_labels();
	$moved  = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT t.* FROM ' . mags_erp_table( 'submissions' ) . ' s INNER JOIN ' . mags_erp_table( 'students' ) . ' t ON t.id = s.student_id WHERE s.homework_id = %d AND t.hidden_at IS NULL', $homework->id ) ) as $student ) {
		if ( isset( $list[ (int) $student->id ] ) ) { continue; }
		$student->now_in = $labels[ (int) $student->class_id ] ?? '';
		$moved[]         = $student;
	}
	return array_merge( array_values( $list ), mags_erp_natural_sort( $moved, array( 'full_name' ) ) );
}

/* ---------- Submissions ---------- */

/** student_id => submission for one homework. */
function mags_erp_homework_submissions( $homework_id ) {
	global $wpdb;
	$rows = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'submissions' ) . ' WHERE homework_id = %d', $homework_id ) ) as $row ) { $rows[ (int) $row->student_id ] = $row; }
	return $rows;
}

function mags_erp_get_submission( $submission_id ) {
	global $wpdb;
	return $submission_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'submissions' ) . ' WHERE id = %d', $submission_id ) ) : null;
}

function mags_erp_get_student_submission( $homework_id, $student_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'submissions' ) . ' WHERE homework_id = %d AND student_id = %d', $homework_id, $student_id ) );
}

/** Save or replace a student's answer. A checked answer cannot be replaced. The file of the replaced answer is deleted. */
function mags_erp_save_submission( $homework_id, $student_id, $file_id, $student_note ) {
	global $wpdb;
	$table    = mags_erp_table( 'submissions' );
	$existing = mags_erp_get_student_submission( $homework_id, $student_id );
	$data     = array( 'file_id' => (int) $file_id, 'student_note' => $student_note, 'submitted_at' => current_time( 'mysql' ) );
	if ( $existing ) {
		if ( $existing->checked_at ) { return new WP_Error( 'mags_erp_submission', 'This homework has already been checked and cannot be changed.' ); }
		$updated = $wpdb->update( $table, $data, array( 'id' => (int) $existing->id ), array( '%d', '%s', '%s' ), array( '%d' ) );
		if ( false === $updated ) { return new WP_Error( 'mags_erp_submission', 'The answer could not be saved. Please try again.' ); }
		if ( (int) $existing->file_id !== (int) $file_id ) { mags_erp_delete_replaced_file( (int) $existing->file_id ); }
		return (int) $existing->id;
	}
	$saved = $wpdb->insert( $table, array_merge( $data, array( 'homework_id' => (int) $homework_id, 'student_id' => (int) $student_id, 'feedback' => '' ) ), array( '%d', '%s', '%s', '%d', '%d', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_submission', 'The answer could not be saved. Please try again.' );
}

function mags_erp_grade_submission( $submission_id, $marks, $feedback, $teacher_id ) {
	global $wpdb;
	return false !== $wpdb->update(
		mags_erp_table( 'submissions' ),
		array( 'marks' => $marks, 'feedback' => $feedback, 'checked_by' => (int) $teacher_id, 'checked_at' => current_time( 'mysql' ) ),
		array( 'id' => (int) $submission_id ),
		array( '%f', '%s', '%d', '%s' ),
		array( '%d' )
	);
}

/** Answers that are sent but not checked, per class: class_id => count. Used to show the office where work is waiting. */
function mags_erp_unchecked_answers_by_class() {
	global $wpdb;
	$counts = array();
	$rows   = $wpdb->get_results( 'SELECT h.class_id, COUNT(*) AS total FROM ' . mags_erp_table( 'submissions' ) . ' s INNER JOIN ' . mags_erp_table( 'homework' ) . ' h ON h.id = s.homework_id INNER JOIN ' . mags_erp_table( 'students' ) . ' t ON t.id = s.student_id WHERE s.checked_at IS NULL AND h.hidden_at IS NULL AND t.hidden_at IS NULL AND t.class_id = h.class_id GROUP BY h.class_id' );
	foreach ( (array) $rows as $row ) { $counts[ (int) $row->class_id ] = (int) $row->total; }
	return $counts;
}

/* ---------- Notes ---------- */

function mags_erp_get_note( $note_id, $with_removed = false ) {
	global $wpdb;
	$note = $note_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'notes' ) . ' WHERE id = %d', $note_id ) ) : null;
	return $note && ( $with_removed || ! $note->hidden_at ) ? $note : null;
}

/** Notes of the given classes, newest first. With $student, only those made since the student came to the class. */
function mags_erp_classes_notes( $class_ids, $limit = 300, $removed = false, $student = null ) {
	global $wpdb;
	if ( ! $class_ids ) { return array(); }
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'notes' ) . ' WHERE class_id IN (' . mags_erp_id_list( $class_ids ) . ') AND hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) . mags_erp_student_since_sql( $student, 'created_at' ) . ' ORDER BY created_at DESC, id DESC LIMIT %d', $limit ) );
}

function mags_erp_create_note( $class_id, $teacher_id, $title, $description, $file_id ) {
	global $wpdb;
	$saved = $wpdb->insert(
		mags_erp_table( 'notes' ),
		array( 'class_id' => $class_id, 'teacher_id' => $teacher_id, 'title' => $title, 'description' => $description, 'file_id' => (int) $file_id, 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%d', '%s', '%s', '%d', '%s' )
	);
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_note', 'The notes could not be saved. Please try again.' );
}

/* ---------- Small display helpers ---------- */

/** Name of a staff account; also works after the teacher was removed or the login was deleted. */
function mags_erp_teacher_name( $teacher_id ) {
	$user = $teacher_id ? get_userdata( (int) $teacher_id ) : false;
	if ( $user ) { return $user->display_name; }
	$former = (array) get_option( 'mags_erp_former_staff', array() );
	return isset( $former[ (int) $teacher_id ] ) ? (string) $former[ (int) $teacher_id ] : 'Teacher';
}

function mags_erp_format_datetime( $datetime, $format = 'j M Y, g:i a' ) {
	return $datetime ? mysql2date( $format, $datetime ) : '';
}

/** "8" or "7.5" without trailing zeros. */
function mags_erp_format_marks( $marks ) {
	if ( null === $marks || '' === $marks ) { return ''; }
	$text = rtrim( rtrim( number_format( (float) $marks, 2, '.', '' ), '0' ), '.' );
	return '' === $text ? '0' : $text;
}

/** Marks typed by a teacher: a number with up to two decimals, within the maximum when one is set. */
function mags_erp_parse_marks( $text, $max_marks ) {
	$text = trim( (string) $text );
	if ( '' === $text ) { return null; }
	if ( ! preg_match( '/^\d{1,4}(\.\d{1,2})?$/', $text ) ) { return new WP_Error( 'mags_erp_marks', 'Enter marks as a number, for example 8 or 7.5.' ); }
	$marks = (float) $text;
	if ( null !== $max_marks && '' !== $max_marks && $marks > (float) $max_marks ) { return new WP_Error( 'mags_erp_marks', sprintf( 'Marks cannot be more than the maximum of %s.', mags_erp_format_marks( $max_marks ) ) ); }
	return $marks;
}

/** Status of a homework row for one student: pending, late, submitted or checked. */
function mags_erp_homework_status( $row ) {
	if ( ! empty( $row->checked_at ) ) { return 'checked'; }
	if ( ! empty( $row->submission_id ) || ! empty( $row->submitted_at ) ) { return 'submitted'; }
	if ( ! empty( $row->due_date ) && $row->due_date < mags_erp_today() ) { return 'late'; }
	return 'pending';
}
