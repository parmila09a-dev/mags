<?php
/** Database access for homework, homework submissions and notes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- Homework ---------- */

function mags_erp_get_homework( $homework_id ) {
	global $wpdb;
	return $homework_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'homework' ) . ' WHERE id = %d', $homework_id ) ) : null;
}

/** Homework for the given classes, newest first, with how many students have submitted and how many are checked. */
function mags_erp_classes_homework( $class_ids, $limit = 200 ) {
	global $wpdb;
	if ( ! $class_ids ) { return array(); }
	$h = mags_erp_table( 'homework' );
	$s = mags_erp_table( 'submissions' );
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT h.*, (SELECT COUNT(*) FROM {$s} WHERE {$s}.homework_id = h.id) AS submitted_count, (SELECT COUNT(*) FROM {$s} WHERE {$s}.homework_id = h.id AND {$s}.checked_at IS NOT NULL) AS checked_count FROM {$h} h WHERE h.class_id IN (" . mags_erp_id_list( $class_ids ) . ') ORDER BY h.created_at DESC, h.id DESC LIMIT %d', $limit ) );
}

/** Homework of one student's class, each row carrying that student's own submission (if any). */
function mags_erp_student_homework( $student, $limit = 200 ) {
	global $wpdb;
	$h = mags_erp_table( 'homework' );
	$s = mags_erp_table( 'submissions' );
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT h.*, s.id AS submission_id, s.file_id AS submission_file_id, s.student_note, s.submitted_at, s.marks, s.feedback, s.checked_at FROM {$h} h LEFT JOIN {$s} s ON s.homework_id = h.id AND s.student_id = %d WHERE h.class_id = %d ORDER BY h.created_at DESC, h.id DESC LIMIT %d", $student->id, $student->class_id, $limit ) );
}

function mags_erp_create_homework( $class_id, $teacher_id, $title, $instructions, $due_date, $max_marks, $file_id ) {
	global $wpdb;
	$saved = $wpdb->insert(
		mags_erp_table( 'homework' ),
		array( 'class_id' => $class_id, 'teacher_id' => $teacher_id, 'title' => $title, 'instructions' => $instructions, 'file_id' => (int) $file_id, 'due_date' => $due_date ?: null, 'max_marks' => null === $max_marks ? null : $max_marks, 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%s' )
	);
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_homework', 'The homework could not be saved. Please try again.' );
}

/** Remove a homework with every submission and file that belongs to it. */
function mags_erp_delete_homework( $homework_id ) {
	global $wpdb;
	$homework = mags_erp_get_homework( $homework_id );
	if ( ! $homework ) { return; }
	foreach ( mags_erp_homework_submissions( $homework_id ) as $submission ) { mags_erp_delete_file( (int) $submission->file_id ); }
	$wpdb->delete( mags_erp_table( 'submissions' ), array( 'homework_id' => (int) $homework_id ), array( '%d' ) );
	mags_erp_delete_file( (int) $homework->file_id );
	$wpdb->delete( mags_erp_table( 'homework' ), array( 'id' => (int) $homework_id ), array( '%d' ) );
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

/** Save or replace a student's answer. A checked answer cannot be replaced. */
function mags_erp_save_submission( $homework_id, $student_id, $file_id, $student_note ) {
	global $wpdb;
	$table    = mags_erp_table( 'submissions' );
	$existing = mags_erp_get_student_submission( $homework_id, $student_id );
	$data     = array( 'file_id' => (int) $file_id, 'student_note' => $student_note, 'submitted_at' => current_time( 'mysql' ) );
	if ( $existing ) {
		if ( $existing->checked_at ) { return new WP_Error( 'mags_erp_submission', 'This homework has already been checked and cannot be changed.' ); }
		$updated = $wpdb->update( $table, $data, array( 'id' => (int) $existing->id ), array( '%d', '%s', '%s' ), array( '%d' ) );
		if ( false === $updated ) { return new WP_Error( 'mags_erp_submission', 'The answer could not be saved. Please try again.' ); }
		if ( (int) $existing->file_id !== (int) $file_id ) { mags_erp_delete_file( (int) $existing->file_id ); }
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

/** Remove every submission (and file) of one student. */
function mags_erp_delete_student_submissions( $student_id ) {
	global $wpdb;
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT id, file_id FROM ' . mags_erp_table( 'submissions' ) . ' WHERE student_id = %d', $student_id ) ) as $row ) { mags_erp_delete_file( (int) $row->file_id ); }
	$wpdb->delete( mags_erp_table( 'submissions' ), array( 'student_id' => (int) $student_id ), array( '%d' ) );
}

/* ---------- Notes ---------- */

function mags_erp_get_note( $note_id ) {
	global $wpdb;
	return $note_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'notes' ) . ' WHERE id = %d', $note_id ) ) : null;
}

function mags_erp_classes_notes( $class_ids, $limit = 300 ) {
	global $wpdb;
	if ( ! $class_ids ) { return array(); }
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'notes' ) . ' WHERE class_id IN (' . mags_erp_id_list( $class_ids ) . ') ORDER BY created_at DESC, id DESC LIMIT %d', $limit ) );
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

function mags_erp_delete_note( $note_id ) {
	global $wpdb;
	$note = mags_erp_get_note( $note_id );
	if ( ! $note ) { return; }
	mags_erp_delete_file( (int) $note->file_id );
	$wpdb->delete( mags_erp_table( 'notes' ), array( 'id' => (int) $note_id ), array( '%d' ) );
}

/** Everything a class owns: used when the office removes an (empty) class. */
function mags_erp_delete_class_work( $class_id ) {
	global $wpdb;
	foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'homework' ) . ' WHERE class_id = %d', $class_id ) ) as $homework_id ) { mags_erp_delete_homework( (int) $homework_id ); }
	foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'notes' ) . ' WHERE class_id = %d', $class_id ) ) as $note_id ) { mags_erp_delete_note( (int) $note_id ); }
}

/* ---------- Small display helpers ---------- */

function mags_erp_teacher_name( $teacher_id ) {
	$user = $teacher_id ? get_userdata( (int) $teacher_id ) : false;
	return $user ? $user->display_name : 'Teacher';
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
