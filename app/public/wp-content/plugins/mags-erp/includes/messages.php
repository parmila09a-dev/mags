<?php
/**
 * Class notices and questions.
 *
 * Notices: a teacher writes to one of their own classes; the students of that
 * class read it. A removed notice is hidden, not deleted.
 *
 * Questions: a student asks one of the teachers of their class, that teacher
 * replies, and both can write again in the same thread. The office can read
 * every thread. There is no way to change or delete a question or a reply.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Limits that keep one student from flooding a teacher. */
define( 'MAGS_ERP_QUESTIONS_PER_DAY', 10 );
define( 'MAGS_ERP_REPLIES_PER_DAY', 30 );

/* ---------- Notices ---------- */

function mags_erp_get_notice( $notice_id, $with_removed = false ) {
	global $wpdb;
	$notice = $notice_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'notices' ) . ' WHERE id = %d', $notice_id ) ) : null;
	return $notice && ( $with_removed || ! $notice->hidden_at ) ? $notice : null;
}

function mags_erp_classes_notices( $class_ids, $limit = 200, $removed = false ) {
	global $wpdb;
	if ( ! $class_ids ) { return array(); }
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'notices' ) . ' WHERE class_id IN (' . mags_erp_id_list( $class_ids ) . ') AND hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) . ' ORDER BY created_at DESC, id DESC LIMIT %d', $limit ) );
}

function mags_erp_create_notice( $class_id, $teacher_id, $title, $body ) {
	global $wpdb;
	$saved = $wpdb->insert(
		mags_erp_table( 'notices' ),
		array( 'class_id' => $class_id, 'teacher_id' => $teacher_id, 'title' => $title, 'body' => $body, 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%d', '%s', '%s', '%s' )
	);
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_notice', 'The notice could not be saved. Please try again.' );
}

/* ---------- Questions ---------- */

function mags_erp_get_question( $question_id ) {
	global $wpdb;
	return $question_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'questions' ) . ' WHERE id = %d', $question_id ) ) : null;
}

/** Replies of one thread, oldest first. */
function mags_erp_question_replies( $question_id ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'question_replies' ) . ' WHERE question_id = %d ORDER BY id', $question_id ) );
}

/** A student's own threads, the latest activity first. */
function mags_erp_student_questions( $student_id, $limit = 200 ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'questions' ) . ' WHERE student_id = %d ORDER BY last_at DESC, id DESC LIMIT %d', $student_id, $limit ) );
}

/**
 * Threads a teacher may open: asked to this teacher, in a class the office has
 * given them. Threads waiting for a reply come first. Each row carries the
 * student's name and roll number.
 */
function mags_erp_teacher_questions( $teacher_id, $limit = 300 ) {
	global $wpdb;
	$class_ids = mags_erp_teacher_class_ids( $teacher_id );
	if ( ! $class_ids ) { return array(); }
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT q.*, s.full_name, s.roll_no, s.hidden_at AS student_removed FROM ' . mags_erp_table( 'questions' ) . ' q INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = q.student_id WHERE q.teacher_id = %d AND q.class_id IN (' . mags_erp_id_list( $class_ids ) . ") ORDER BY ( q.last_from = 'student' AND s.hidden_at IS NULL ) DESC, q.last_at DESC, q.id DESC LIMIT %d", $teacher_id, $limit ) );
}

/** May this teacher open and answer this thread? */
function mags_erp_teacher_can_open_question( $teacher_id, $question ) {
	return $question && (int) $question->teacher_id === (int) $teacher_id && mags_erp_teacher_has_class( $teacher_id, (int) $question->class_id );
}

/** Threads that wait for this teacher's reply. */
function mags_erp_waiting_questions_count( $teacher_id ) {
	$waiting = 0;
	foreach ( mags_erp_teacher_questions( $teacher_id ) as $question ) { if ( 'student' === $question->last_from && ! $question->student_removed ) { $waiting++; } }
	return $waiting;
}

/** Office: every thread, with optional class filter and search in the topic, the question and the student's name. */
function mags_erp_query_questions( $class_id, $search, $page, $per_page = 50 ) {
	global $wpdb;
	$q      = mags_erp_table( 'questions' );
	$s      = mags_erp_table( 'students' );
	$where  = '1=1';
	$values = array();
	if ( $class_id ) { $where .= ' AND q.class_id = %d'; $values[] = $class_id; }
	if ( '' !== $search ) {
		$like   = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= ' AND ( q.title LIKE %s OR q.body LIKE %s OR s.full_name LIKE %s OR s.admission_no LIKE %s )';
		array_push( $values, $like, $like, $like, $like );
	}
	$count_sql = "SELECT COUNT(*) FROM {$q} q INNER JOIN {$s} s ON s.id = q.student_id WHERE {$where}";
	$total     = (int) $wpdb->get_var( $values ? $wpdb->prepare( $count_sql, $values ) : $count_sql );
	$values[]  = $per_page;
	$values[]  = max( 0, ( $page - 1 ) * $per_page );
	$rows      = $wpdb->get_results( $wpdb->prepare( "SELECT q.*, s.full_name, s.roll_no, s.admission_no, s.hidden_at AS student_removed FROM {$q} q INNER JOIN {$s} s ON s.id = q.student_id WHERE {$where} ORDER BY q.last_at DESC, q.id DESC LIMIT %d OFFSET %d", $values ) );
	return array( (array) $rows, $total );
}

/** Threads whose teacher can no longer answer (removed, or the class was taken away) while the student waits. */
function mags_erp_stranded_questions_count() {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'questions' ) . ' q INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = q.student_id WHERE q.last_from = \'student\' AND s.hidden_at IS NULL AND NOT EXISTS (SELECT 1 FROM ' . mags_erp_table( 'class_teachers' ) . ' ct WHERE ct.teacher_id = q.teacher_id AND ct.class_id = q.class_id)' );
}

/** How many questions (or follow-up messages) a student has written today. */
function mags_erp_student_messages_today( $student, $table ) {
	global $wpdb;
	$since = mags_erp_today() . ' 00:00:00';
	if ( 'questions' === $table ) { return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'questions' ) . ' WHERE student_id = %d AND created_at >= %s', $student->id, $since ) ); }
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'question_replies' ) . " WHERE user_id = %d AND from_role = 'student' AND created_at >= %s", $student->user_id, $since ) );
}

function mags_erp_create_question( $student, $teacher_id, $title, $body ) {
	global $wpdb;
	$now   = current_time( 'mysql' );
	$saved = $wpdb->insert(
		mags_erp_table( 'questions' ),
		array( 'student_id' => (int) $student->id, 'class_id' => (int) $student->class_id, 'teacher_id' => (int) $teacher_id, 'title' => $title, 'body' => $body, 'created_at' => $now, 'last_at' => $now, 'last_from' => 'student' ),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
	);
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_question', 'The question could not be sent. Please try again.' );
}

/** Add a message to a thread. $from_role is "teacher" or "student". */
function mags_erp_add_reply( $question, $user_id, $from_role, $body ) {
	global $wpdb;
	$now   = current_time( 'mysql' );
	$saved = $wpdb->insert( mags_erp_table( 'question_replies' ), array( 'question_id' => (int) $question->id, 'user_id' => (int) $user_id, 'from_role' => $from_role, 'body' => $body, 'created_at' => $now ), array( '%d', '%d', '%s', '%s', '%s' ) );
	if ( ! $saved ) { return new WP_Error( 'mags_erp_question', 'The message could not be sent. Please try again.' ); }
	$wpdb->update( mags_erp_table( 'questions' ), array( 'last_at' => $now, 'last_from' => $from_role ), array( 'id' => (int) $question->id ), array( '%s', '%s' ), array( '%d' ) );
	return (int) $wpdb->insert_id;
}

/** "Waiting for reply" or "Answered", with the chip colour, for a thread row. */
function mags_erp_question_state( $question ) {
	return 'student' === $question->last_from ? array( 'Waiting for reply', 'erp-chip--orange' ) : array( 'Answered', 'erp-chip--green' );
}

/* ---------- Teacher actions: notices ---------- */

function mags_erp_action_save_notice() {
	$teacher_id = get_current_user_id();
	$class_id   = mags_erp_post_int( 'class_id' );
	$title      = mags_erp_post_text( 'title' );
	$body       = mags_erp_post_textarea( 'body', 3000 );
	$back       = mags_erp_url( 'notices' );
	if ( ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) { mags_erp_flash( 'error', 'Choose one of your own classes.' ); return $back; }
	if ( '' === $title ) { mags_erp_flash( 'error', 'Enter a title for the notice.' ); return $back; }
	if ( mb_strlen( $title ) > 160 ) { mags_erp_flash( 'error', 'The title is too long (160 characters at most).' ); return $back; }
	if ( '' === $body ) { mags_erp_flash( 'error', 'Write the notice.' ); return $back; }

	$created = mags_erp_create_notice( $class_id, $teacher_id, $title, $body );
	if ( is_wp_error( $created ) ) { mags_erp_flash( 'error', $created->get_error_message() ); return $back; }
	mags_erp_flash( 'success', 'Notice sent to ' . mags_erp_class_label( mags_erp_get_class( $class_id ) ) . '.' );
	return $back;
}

function mags_erp_action_delete_notice() {
	$notice = mags_erp_get_notice( mags_erp_post_int( 'notice_id' ), true );
	$back   = mags_erp_url( 'notices' );
	if ( ! $notice || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $notice->class_id ) ) { mags_erp_flash( 'error', 'That notice is not in one of your classes.' ); return $back; }
	if ( mags_erp_post_int( 'restore' ) ) {
		mags_erp_unhide( 'notices', (int) $notice->id );
		mags_erp_flash( 'success', 'Notice restored. The class can see it again.' );
	} else {
		mags_erp_hide( 'notices', (int) $notice->id );
		mags_erp_flash( 'success', 'Notice removed. The class no longer sees it; it is kept under Removed.' );
	}
	return $back;
}

/* ---------- Questions: student asks, student and teacher reply ---------- */

function mags_erp_action_ask_question() {
	$student    = mags_erp_get_student_by_user( get_current_user_id() );
	$teacher_id = mags_erp_post_int( 'teacher_id' );
	$title      = mags_erp_post_text( 'title' );
	$body       = mags_erp_post_textarea( 'body', 3000 );
	$back       = mags_erp_url( 'questions' );
	if ( ! $student ) { mags_erp_flash( 'error', 'Your student record was not found. Please contact the school office.' ); return $back; }
	if ( ! $teacher_id || ! mags_erp_teacher_has_class( $teacher_id, (int) $student->class_id ) ) { mags_erp_flash( 'error', 'Choose one of the teachers of your class.' ); return $back; }
	if ( '' === $title ) { mags_erp_flash( 'error', 'Enter the topic of your question.' ); return $back; }
	if ( mb_strlen( $title ) > 160 ) { mags_erp_flash( 'error', 'The topic is too long (160 characters at most).' ); return $back; }
	if ( '' === $body ) { mags_erp_flash( 'error', 'Write your question.' ); return $back; }
	if ( mags_erp_student_messages_today( $student, 'questions' ) >= MAGS_ERP_QUESTIONS_PER_DAY ) { mags_erp_flash( 'error', sprintf( 'You can ask %d questions in one day. Please ask again tomorrow.', MAGS_ERP_QUESTIONS_PER_DAY ) ); return $back; }

	$created = mags_erp_create_question( $student, $teacher_id, $title, $body );
	if ( is_wp_error( $created ) ) { mags_erp_flash( 'error', $created->get_error_message() ); return $back; }
	mags_erp_flash( 'success', 'Your question has been sent to ' . mags_erp_teacher_name( $teacher_id ) . '.' );
	return mags_erp_url( 'questions', array( 'id' => $created ) );
}

function mags_erp_action_reply_question() {
	$role     = mags_erp_user_role();
	$user_id  = get_current_user_id();
	$question = mags_erp_get_question( mags_erp_post_int( 'question_id' ) );
	$body     = mags_erp_post_textarea( 'body', 3000 );
	$list     = mags_erp_url( 'questions' );

	if ( 'teacher' === $role ) {
		if ( ! mags_erp_teacher_can_open_question( $user_id, $question ) ) { mags_erp_flash( 'error', 'That question was not asked to you.' ); return $list; }
		$asker = mags_erp_get_student( (int) $question->student_id );
		if ( ! $asker || $asker->hidden_at ) { mags_erp_flash( 'error', 'This student has left the school, so a reply cannot be sent.' ); return mags_erp_url( 'questions', array( 'id' => (int) $question->id ) ); }
	} else {
		$student = mags_erp_get_student_by_user( $user_id );
		if ( ! $student || ! $question || (int) $question->student_id !== (int) $student->id ) { mags_erp_flash( 'error', 'That question is not yours.' ); return $list; }
		if ( ! mags_erp_teacher_has_class( (int) $question->teacher_id, (int) $question->class_id ) ) { mags_erp_flash( 'error', 'This teacher no longer teaches that class. Please ask your question to another teacher.' ); return mags_erp_url( 'questions', array( 'id' => (int) $question->id ) ); }
		if ( mags_erp_student_messages_today( $student, 'question_replies' ) >= MAGS_ERP_REPLIES_PER_DAY ) { mags_erp_flash( 'error', 'You have written many messages today. Please write again tomorrow.' ); return mags_erp_url( 'questions', array( 'id' => (int) $question->id ) ); }
	}
	$back = mags_erp_url( 'questions', array( 'id' => (int) $question->id ) );
	if ( '' === $body ) { mags_erp_flash( 'error', 'Write your message.' ); return $back; }

	$added = mags_erp_add_reply( $question, $user_id, $role, $body );
	mags_erp_flash( is_wp_error( $added ) ? 'error' : 'success', is_wp_error( $added ) ? $added->get_error_message() : ( 'teacher' === $role ? 'Reply sent to the student.' : 'Your message has been sent.' ) );
	return $back;
}
