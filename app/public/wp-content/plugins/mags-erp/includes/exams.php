<?php
/**
 * Exams and marks. The office makes an exam and its papers (one subject of one
 * class, with maximum marks). The teachers of the class enter the marks. When
 * the office publishes the results, the marks are locked and each student sees
 * their own marks and can download a report card.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- Exams ---------- */

function mags_erp_get_exams( $removed = false ) {
	global $wpdb;
	return (array) $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'exams' ) . ' WHERE hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) . ' ORDER BY COALESCE(start_date, created_at) DESC, id DESC' );
}

/** One exam. A removed exam is returned only when $with_removed is true. */
function mags_erp_get_exam( $exam_id, $with_removed = false ) {
	global $wpdb;
	$exam = $exam_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'exams' ) . ' WHERE id = %d', $exam_id ) ) : null;
	return $exam && ( $with_removed || ! $exam->hidden_at ) ? $exam : null;
}

/** Add an exam ($exam_id 0) or change its name and date. Returns the exam id or a WP_Error. */
function mags_erp_save_exam( $exam_id, $name, $start_date ) {
	global $wpdb;
	$table = mags_erp_table( 'exams' );
	if ( '' === $name ) { return new WP_Error( 'mags_erp_exam', 'Enter the exam name, such as Half Yearly Exam 2026.' ); }
	if ( mb_strlen( $name ) > 120 ) { return new WP_Error( 'mags_erp_exam', 'The exam name is too long.' ); }
	if ( '' !== $start_date && ! mags_erp_valid_date( $start_date ) ) { return new WP_Error( 'mags_erp_exam', 'The exam date is not a valid date.' ); }

	$data = array( 'name' => $name, 'start_date' => $start_date ?: null );
	if ( $exam_id ) {
		if ( ! mags_erp_get_exam( $exam_id ) ) { return new WP_Error( 'mags_erp_exam', 'That exam no longer exists.' ); }
		$wpdb->update( $table, $data, array( 'id' => $exam_id ), array( '%s', '%s' ), array( '%d' ) );
		return (int) $exam_id;
	}
	$saved = $wpdb->insert( $table, array_merge( $data, array( 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ) ), array( '%s', '%s', '%d', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_exam', 'The exam could not be saved. Please try again.' );
}

/** Publish the results (students can see them, marks are locked) or open the exam for marks again. */
function mags_erp_set_exam_published( $exam_id, $published ) {
	global $wpdb;
	$wpdb->update( mags_erp_table( 'exams' ), array( 'published_at' => $published ? current_time( 'mysql' ) : null ), array( 'id' => (int) $exam_id ), array( '%s' ), array( '%d' ) );
}

/* ---------- Papers ---------- */

/**
 * Papers of one exam (optionally only of some classes), each with ->entered_count:
 * the students of the class who have marks or are marked absent.
 */
function mags_erp_exam_papers( $exam_id, $class_ids = null ) {
	global $wpdb;
	$p = mags_erp_table( 'exam_papers' );
	$m = mags_erp_table( 'exam_marks' );
	$t = mags_erp_table( 'students' );
	if ( is_array( $class_ids ) && ! $class_ids ) { return array(); }
	$only   = is_array( $class_ids ) ? ' AND p.class_id IN (' . mags_erp_id_list( $class_ids ) . ')' : '';
	$papers = (array) $wpdb->get_results( $wpdb->prepare( "SELECT p.*, (SELECT COUNT(*) FROM {$m} INNER JOIN {$t} ON {$t}.id = {$m}.student_id WHERE {$m}.paper_id = p.id AND {$t}.hidden_at IS NULL AND {$t}.class_id = p.class_id AND ({$m}.marks IS NOT NULL OR {$m}.absent = 1)) AS entered_count FROM {$p} p INNER JOIN " . mags_erp_table( 'classes' ) . " c ON c.id = p.class_id WHERE p.exam_id = %d AND p.hidden_at IS NULL AND c.hidden_at IS NULL{$only}", $exam_id ) );

	$subjects = mags_erp_subject_names();
	$classes  = mags_erp_all_class_labels();
	foreach ( $papers as $paper ) {
		$paper->subject_name = $subjects[ (int) $paper->subject_id ] ?? 'Subject';
		$paper->class_label  = $classes[ (int) $paper->class_id ] ?? 'Class';
	}
	return mags_erp_natural_sort( $papers, array( 'class_label', 'subject_name' ) );
}

function mags_erp_removed_papers( $exam_id ) {
	global $wpdb;
	$papers   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'exam_papers' ) . ' WHERE exam_id = %d AND hidden_at IS NOT NULL', $exam_id ) );
	$subjects = mags_erp_subject_names();
	$classes  = mags_erp_all_class_labels();
	foreach ( $papers as $paper ) {
		$paper->subject_name = $subjects[ (int) $paper->subject_id ] ?? 'Subject';
		$paper->class_label  = $classes[ (int) $paper->class_id ] ?? 'Class';
	}
	return $papers;
}

/** One paper that is in use, with its subject name. */
function mags_erp_get_paper( $paper_id ) {
	global $wpdb;
	$paper = $paper_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'exam_papers' ) . ' WHERE id = %d AND hidden_at IS NULL', $paper_id ) ) : null;
	if ( $paper ) { $paper->subject_name = mags_erp_subject_names()[ (int) $paper->subject_id ] ?? 'Subject'; }
	return $paper;
}

/**
 * Add one subject as a paper for each of the chosen classes. A paper that
 * already exists gets the new maximum marks, date and time; a removed one comes back.
 * Returns array( added, updated, list of problems ).
 */
function mags_erp_save_papers( $exam_id, $class_ids, $subject_id, $max_marks, $exam_date, $start_time = '', $end_time = '' ) {
	global $wpdb;
	$table    = mags_erp_table( 'exam_papers' );
	$added    = 0;
	$updated  = 0;
	$problems = array();
	foreach ( array_unique( array_map( 'absint', (array) $class_ids ) ) as $class_id ) {
		$class = mags_erp_get_class( $class_id );
		if ( ! $class ) { continue; }
		$data     = array( 'max_marks' => $max_marks, 'exam_date' => $exam_date ?: null, 'start_time' => (string) $start_time, 'end_time' => (string) $end_time );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE exam_id = %d AND class_id = %d AND subject_id = %d", $exam_id, $class_id, $subject_id ) );
		if ( ! $existing ) {
			if ( $wpdb->insert( $table, array_merge( $data, array( 'exam_id' => $exam_id, 'class_id' => $class_id, 'subject_id' => $subject_id, 'created_at' => current_time( 'mysql' ) ) ), array( '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s' ) ) ) {
				$added++;
			} else {
				$problems[] = sprintf( '%s: the paper could not be saved. Please try again.', mags_erp_class_label( $class ) );
			}
			continue;
		}
		$highest = $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(marks) FROM ' . mags_erp_table( 'exam_marks' ) . ' WHERE paper_id = %d', $existing->id ) );
		if ( null !== $highest && (float) $highest > (float) $max_marks ) {
			$problems[] = sprintf( '%s: a student already has %s marks, so the maximum cannot be %s.', mags_erp_class_label( $class ), mags_erp_format_marks( $highest ), mags_erp_format_marks( $max_marks ) );
			continue;
		}
		$wpdb->update( $table, $data, array( 'id' => (int) $existing->id ), array( '%f', '%s', '%s', '%s' ), array( '%d' ) );
		if ( $existing->hidden_at ) { mags_erp_unhide( 'exam_papers', (int) $existing->id ); }
		$updated++;
	}
	return array( $added, $updated, $problems );
}

/* ---------- Marks ---------- */

/** student_id => marks row for one paper. */
function mags_erp_paper_marks( $paper_id ) {
	global $wpdb;
	$rows = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'exam_marks' ) . ' WHERE paper_id = %d', $paper_id ) ) as $row ) { $rows[ (int) $row->student_id ] = $row; }
	return $rows;
}

/**
 * Save the marks sheet of one paper. $marks is student_id => text, $absent is
 * student_id => anything for absent students. Only students who are in the
 * class now are written. Good rows are saved even when another row is wrong.
 * Returns array( number saved or changed, names of students with wrong marks ).
 */
function mags_erp_save_paper_marks( $paper, $marks, $absent, $teacher_id ) {
	global $wpdb;
	$table    = mags_erp_table( 'exam_marks' );
	$existing = mags_erp_paper_marks( (int) $paper->id );
	$now      = current_time( 'mysql' );
	$saved    = 0;
	$wrong    = array();

	foreach ( mags_erp_class_students( (int) $paper->class_id ) as $student ) {
		$student_id = (int) $student->id;
		$is_absent  = ! empty( $absent[ $student_id ] );
		$text       = isset( $marks[ $student_id ] ) && is_string( $marks[ $student_id ] ) ? trim( $marks[ $student_id ] ) : '';
		$value      = $is_absent ? null : mags_erp_parse_marks( $text, $paper->max_marks );
		if ( is_wp_error( $value ) ) { $wrong[] = $student->full_name; continue; }

		$old = $existing[ $student_id ] ?? null;
		if ( ! $old && null === $value && ! $is_absent ) { continue; }
		$same = $old && (int) $old->absent === (int) $is_absent && ( null === $value ? null === $old->marks : ( null !== $old->marks && abs( (float) $old->marks - $value ) < 0.001 ) );
		if ( $same ) { continue; }

		$data = array( 'marks' => $value, 'absent' => $is_absent ? 1 : 0, 'entered_by' => (int) $teacher_id, 'entered_at' => $now );
		if ( $old ) {
			$wpdb->update( $table, $data, array( 'id' => (int) $old->id ), array( '%f', '%d', '%d', '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert( $table, array_merge( $data, array( 'paper_id' => (int) $paper->id, 'student_id' => $student_id ) ), array( '%f', '%d', '%d', '%s', '%d', '%d' ) );
		}
		$saved++;
	}
	return array( $saved, $wrong );
}

/**
 * One student's result in one exam: the papers of the class they sat the exam
 * in, with their marks. Total and percentage count only the papers that have
 * marks; an absent paper counts as 0.
 */
function mags_erp_student_result( $exam, $student ) {
	global $wpdb;
	$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT p.*, m.id AS mark_id, m.marks, m.absent FROM ' . mags_erp_table( 'exam_papers' ) . ' p LEFT JOIN ' . mags_erp_table( 'exam_marks' ) . ' m ON m.paper_id = p.id AND m.student_id = %d WHERE p.exam_id = %d AND p.hidden_at IS NULL', $student->id, $exam->id ) );

	// The class the student sat this exam in: where their marks are, otherwise their class today.
	$sat = array();
	foreach ( $rows as $row ) { if ( $row->mark_id ) { $sat[ (int) $row->class_id ] = ( $sat[ (int) $row->class_id ] ?? 0 ) + 1; } }
	arsort( $sat );
	$class_id = $sat ? (int) array_key_first( $sat ) : (int) $student->class_id;

	$subjects = mags_erp_subject_names();
	$result   = array( 'rows' => array(), 'total' => 0.0, 'max' => 0.0, 'percent' => null, 'class_id' => $class_id );
	foreach ( $rows as $row ) {
		if ( (int) $row->class_id !== $class_id ) { continue; }
		$row->subject_name = $subjects[ (int) $row->subject_id ] ?? 'Subject';
		$row->has_result   = null !== $row->marks || (int) $row->absent;
		if ( $row->has_result ) {
			$result['total'] += (float) $row->marks;
			$result['max']   += (float) $row->max_marks;
		}
		$result['rows'][] = $row;
	}
	$result['rows'] = mags_erp_natural_sort( $result['rows'], array( 'subject_name' ) );
	if ( $result['max'] > 0 ) { $result['percent'] = round( $result['total'] * 100 / $result['max'], 1 ); }
	return $result;
}

/** Published exams in which this student has a paper, newest first, each with ->result. */
function mags_erp_student_exams( $student ) {
	$exams = array();
	foreach ( mags_erp_get_exams() as $exam ) {
		if ( ! $exam->published_at ) { continue; }
		$exam->result = mags_erp_student_result( $exam, $student );
		if ( $exam->result['rows'] ) { $exams[] = $exam; }
	}
	return $exams;
}

/** "9:00 am – 12:00 pm", "9:00 am" or '' for the time of a paper. */
function mags_erp_paper_time( $paper ) {
	$start = mags_erp_format_time( (string) ( $paper->start_time ?? '' ) );
	$end   = mags_erp_format_time( (string) ( $paper->end_time ?? '' ) );
	return '' === $start ? '' : $start . ( '' !== $end ? ' – ' . $end : '' );
}

/** The papers of one class in an exam in the order they are written: by date, then time, then subject. */
function mags_erp_datesheet( $exam_id, $class_id ) {
	$papers = mags_erp_exam_papers( (int) $exam_id, array( (int) $class_id ) );
	usort(
		$papers,
		static function ( $a, $b ) {
			$da = (string) $a->exam_date;
			$db = (string) $b->exam_date;
			if ( $da !== $db ) { return '' === $da ? 1 : ( '' === $db ? -1 : strcmp( $da, $db ) ); }
			return strcmp( (string) $a->start_time, (string) $b->start_time ) ?: strnatcasecmp( $a->subject_name, $b->subject_name );
		}
	);
	return $papers;
}

/** "AB" for absent, the marks, or "–" when nothing is entered. */
function mags_erp_marks_text( $row ) {
	if ( ! empty( $row->absent ) ) { return 'AB'; }
	return null === $row->marks ? '–' : mags_erp_format_marks( $row->marks );
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_exam() {
	$exam_id = mags_erp_post_int( 'exam_id' );
	$saved   = mags_erp_save_exam( $exam_id, mags_erp_post_text( 'erp_name' ), mags_erp_post_text( 'start_date' ) );
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() );
		return mags_erp_url( 'exams', array( 'id' => $exam_id ?: '' ) );
	}
	mags_erp_flash( 'success', $exam_id ? 'Exam updated.' : 'Exam added. Now add its papers: a subject and maximum marks for each class.' );
	return mags_erp_url( 'exams', array( 'id' => $saved ) );
}

function mags_erp_action_delete_exam() {
	$exam_id = mags_erp_post_int( 'exam_id' );
	if ( mags_erp_post_int( 'restore' ) ) {
		$restored = mags_erp_unhide( 'exams', $exam_id );
		mags_erp_flash( $restored ? 'success' : 'error', $restored ? 'Exam restored, with its papers and marks.' : 'That exam is not in the removed list.' );
		return mags_erp_url( 'exams' );
	}
	if ( ! mags_erp_get_exam( $exam_id ) ) { mags_erp_flash( 'error', 'That exam no longer exists.' ); return mags_erp_url( 'exams' ); }
	mags_erp_hide( 'exams', $exam_id );
	mags_erp_flash( 'success', 'Exam removed. Its papers and marks are kept and return if you restore it.' );
	return mags_erp_url( 'exams' );
}

function mags_erp_action_save_papers() {
	$exam    = mags_erp_get_exam( mags_erp_post_int( 'exam_id' ) );
	$subject = mags_erp_get_subject( mags_erp_post_int( 'subject_id' ) );
	$date    = mags_erp_post_text( 'exam_date' );
	$classes = isset( $_POST['class_ids'] ) && is_array( $_POST['class_ids'] ) ? array_map( 'absint', $_POST['class_ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $exam ) { mags_erp_flash( 'error', 'That exam no longer exists.' ); return mags_erp_url( 'exams' ); }
	$back = mags_erp_url( 'exams', array( 'id' => (int) $exam->id ) );

	if ( $exam->published_at ) { mags_erp_flash( 'error', 'The results are published. Open the exam for marks again before changing its papers.' ); return $back; }
	if ( ! $subject ) { mags_erp_flash( 'error', 'Choose a subject.' ); return $back; }
	if ( ! $classes ) { mags_erp_flash( 'error', 'Tick at least one class.' ); return $back; }
	$max_marks = mags_erp_parse_marks( mags_erp_post_text( 'max_marks' ), null );
	if ( is_wp_error( $max_marks ) || null === $max_marks || $max_marks <= 0 ) { mags_erp_flash( 'error', 'Enter the maximum marks as a number above 0.' ); return $back; }
	if ( '' !== $date && ! mags_erp_valid_date( $date ) ) { mags_erp_flash( 'error', 'The paper date is not a valid date.' ); return $back; }
	$start = mags_erp_post_text( 'start_time' );
	$end   = mags_erp_post_text( 'end_time' );
	if ( ( '' !== $start && ! mags_erp_valid_time( $start ) ) || ( '' !== $end && ! mags_erp_valid_time( $end ) ) ) { mags_erp_flash( 'error', 'Enter the time of the paper like 09:00, or leave it empty.' ); return $back; }
	if ( '' !== $start && '' !== $end && $end <= $start ) { mags_erp_flash( 'error', 'The paper must end after it starts.' ); return $back; }
	if ( '' === $start && '' !== $end ) { mags_erp_flash( 'error', 'Enter the start time too, or leave both times empty.' ); return $back; }

	list( $added, $updated, $problems ) = mags_erp_save_papers( (int) $exam->id, $classes, (int) $subject->id, $max_marks, $date, $start, $end );
	$message = sprintf( '%s: %d paper(s) added, %d updated.', $subject->name, $added, $updated );
	mags_erp_flash( $problems ? 'error' : 'success', $problems ? $message . ' ' . implode( ' ', $problems ) : $message );
	return $back;
}

function mags_erp_action_delete_paper() {
	global $wpdb;
	$paper_id = mags_erp_post_int( 'paper_id' );
	$paper    = $paper_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'exam_papers' ) . ' WHERE id = %d', $paper_id ) ) : null;
	$exam     = $paper ? mags_erp_get_exam( (int) $paper->exam_id ) : null;
	if ( ! $exam ) { mags_erp_flash( 'error', 'That paper no longer exists.' ); return mags_erp_url( 'exams' ); }
	$back = mags_erp_url( 'exams', array( 'id' => (int) $exam->id ) );
	if ( $exam->published_at ) { mags_erp_flash( 'error', 'The results are published. Open the exam for marks again before changing its papers.' ); return $back; }

	if ( mags_erp_post_int( 'restore' ) ) {
		$restored = mags_erp_unhide( 'exam_papers', $paper_id );
		mags_erp_flash( $restored ? 'success' : 'error', $restored ? 'Paper restored, with its marks.' : 'That paper is not in the removed list.' );
	} else {
		$removed = mags_erp_hide( 'exam_papers', $paper_id );
		mags_erp_flash( $removed ? 'success' : 'error', $removed ? 'Paper removed. Its marks are kept and return if you restore it.' : 'That paper no longer exists.' );
	}
	return $back;
}

function mags_erp_action_publish_exam() {
	$exam = mags_erp_get_exam( mags_erp_post_int( 'exam_id' ) );
	if ( ! $exam ) { mags_erp_flash( 'error', 'That exam no longer exists.' ); return mags_erp_url( 'exams' ); }
	$back = mags_erp_url( 'exams', array( 'id' => (int) $exam->id ) );

	if ( ! mags_erp_post_int( 'publish' ) ) {
		mags_erp_set_exam_published( (int) $exam->id, false );
		mags_erp_cancel_result_mail( (int) $exam->id );
		mags_erp_flash( 'success', 'The exam is open for marks again. Students cannot see the results until you publish them.' );
		return $back;
	}
	$papers = mags_erp_exam_papers( (int) $exam->id );
	if ( ! $papers ) { mags_erp_flash( 'error', 'Add the papers of this exam first.' ); return $back; }
	$counts  = wp_list_pluck( mags_erp_get_classes(), 'student_count', 'id' );
	$missing = 0;
	foreach ( $papers as $paper ) { if ( (int) $paper->entered_count < (int) ( $counts[ (int) $paper->class_id ] ?? 0 ) ) { $missing++; } }
	mags_erp_set_exam_published( (int) $exam->id, true );
	$mailed = mags_erp_notify_results( mags_erp_get_exam( (int) $exam->id ) );
	mags_erp_flash( 'success', 'Results published. Students can now see their marks and download the report card.' . ( $mailed ? ' ' . sprintf( _n( 'An email goes to %d parent.', 'Emails go to %d parents.', $mailed, 'mags-erp' ), $mailed ) : '' ) . ( $missing ? sprintf( ' Note: %d paper(s) still have marks missing; those show as “–”.', $missing ) : '' ) );
	return $back;
}

/* ---------- Teacher action ---------- */

function mags_erp_action_save_marks() {
	$paper = mags_erp_get_paper( mags_erp_post_int( 'paper_id' ) );
	$exam  = $paper ? mags_erp_get_exam( (int) $paper->exam_id ) : null;
	if ( ! $exam || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $paper->class_id ) ) { mags_erp_flash( 'error', 'That paper is not in one of your classes.' ); return mags_erp_url( 'marks' ); }
	$back = mags_erp_url( 'marks', array( 'paper' => (int) $paper->id ) );
	if ( $exam->published_at ) { mags_erp_flash( 'error', 'The results of this exam are published, so the marks are locked. Ask the school office to open the exam again.' ); return $back; }

	$marks  = isset( $_POST['marks'] ) && is_array( $_POST['marks'] ) ? wp_unslash( $_POST['marks'] ) : array(); // phpcs:ignore WordPress.Security
	$absent = isset( $_POST['absent'] ) && is_array( $_POST['absent'] ) ? $_POST['absent'] : array(); // phpcs:ignore WordPress.Security
	list( $saved, $wrong ) = mags_erp_save_paper_marks( $paper, $marks, $absent, get_current_user_id() );

	if ( $wrong ) {
		mags_erp_flash( 'error', sprintf( 'Marks not saved for %s: enter a number from 0 to %s. %d other change(s) were saved.', implode( ', ', array_slice( $wrong, 0, 8 ) ) . ( count( $wrong ) > 8 ? ' and others' : '' ), mags_erp_format_marks( $paper->max_marks ), $saved ) );
	} else {
		mags_erp_flash( 'success', $saved ? sprintf( 'Marks saved: %d change(s).', $saved ) : 'Nothing was changed.' );
	}
	return $back;
}
