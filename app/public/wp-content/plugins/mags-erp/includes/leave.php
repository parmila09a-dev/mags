<?php
/**
 * Leave requests. A student (or parent) asks for leave for one or more days;
 * the class teacher of the student's class approves it or not. An approved
 * leave is offered as "Leave" on the attendance sheet of those days; the
 * teacher still saves the attendance.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** How far back a leave may start (for a child who was already sick) and how long one request may be. */
define( 'MAGS_ERP_LEAVE_BACK_DAYS', 7 );
define( 'MAGS_ERP_LEAVE_MAX_DAYS', 30 );

/** status => array( label, chip class ). */
function mags_erp_leave_statuses() {
	return array(
		'pending'   => array( 'Waiting for class teacher', 'erp-chip--orange' ),
		'approved'  => array( 'Approved', 'erp-chip--green' ),
		'rejected'  => array( 'Not approved', 'erp-chip--status-A' ),
		'withdrawn' => array( 'Withdrawn', '' ),
	);
}

function mags_erp_get_leave( $leave_id ) {
	global $wpdb;
	return $leave_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'leave_requests' ) . ' WHERE id = %d', $leave_id ) ) : null;
}

function mags_erp_student_leaves( $student_id, $limit = 100 ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'leave_requests' ) . ' WHERE student_id = %d ORDER BY id DESC LIMIT %d', $student_id, $limit ) );
}

/**
 * Leave requests of the students who are now in the given classes, each row
 * with the student's name, roll number and class. $pending picks the waiting
 * requests (oldest first) or the answered ones (newest first).
 */
function mags_erp_classes_leaves( $class_ids, $pending, $limit = 100 ) {
	global $wpdb;
	if ( ! $class_ids ) { return array(); }
	$where = $pending ? "l.status = 'pending'" : "l.status IN ('approved','rejected')";
	$order = $pending ? 'l.from_date, l.id' : 'l.decided_at DESC, l.id DESC';
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT l.*, s.full_name, s.roll_no, s.class_id AS current_class_id FROM ' . mags_erp_table( 'leave_requests' ) . ' l INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = l.student_id WHERE s.hidden_at IS NULL AND s.class_id IN (' . mags_erp_id_list( $class_ids ) . ") AND {$where} ORDER BY {$order} LIMIT %d", $limit ) );
}

/** Requests that wait for this class teacher. */
function mags_erp_pending_leave_count( $teacher_id ) {
	return count( mags_erp_classes_leaves( mags_erp_class_teacher_class_ids( $teacher_id ), true, 500 ) );
}

/** class_id => number of waiting requests, for classes that have no class teacher (shown to the office). */
function mags_erp_unanswerable_leave_by_class() {
	global $wpdb;
	$counts = array();
	$rows   = $wpdb->get_results( 'SELECT c.id, COUNT(*) AS total FROM ' . mags_erp_table( 'leave_requests' ) . ' l INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = l.student_id INNER JOIN ' . mags_erp_table( 'classes' ) . " c ON c.id = s.class_id WHERE l.status = 'pending' AND s.hidden_at IS NULL AND c.hidden_at IS NULL AND c.class_teacher_id = 0 GROUP BY c.id" );
	foreach ( (array) $rows as $row ) { $counts[ (int) $row->id ] = (int) $row->total; }
	return $counts;
}

/** Number of days of a request, both ends counted. */
function mags_erp_leave_days( $leave ) {
	return (int) round( ( strtotime( $leave->to_date . ' 00:00:00 UTC' ) - strtotime( $leave->from_date . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS ) + 1;
}

/** "3 March 2026" or "3 March 2026 to 5 March 2026 (3 days)". */
function mags_erp_leave_dates( $leave ) {
	if ( $leave->from_date === $leave->to_date ) { return mags_erp_format_date( $leave->from_date ); }
	return mags_erp_format_date( $leave->from_date ) . ' to ' . mags_erp_format_date( $leave->to_date ) . ' (' . mags_erp_leave_days( $leave ) . ' days)';
}

/** Ask for leave. Returns the request id or a WP_Error. */
function mags_erp_create_leave( $student, $from, $to, $reason ) {
	global $wpdb;
	$table = mags_erp_table( 'leave_requests' );
	$today = mags_erp_today();
	if ( ! mags_erp_valid_date( $from ) ) { return new WP_Error( 'mags_erp_leave', 'Choose the first day of the leave.' ); }
	$to = '' === $to ? $from : $to;
	if ( ! mags_erp_valid_date( $to ) || $to < $from ) { return new WP_Error( 'mags_erp_leave', 'The last day cannot be before the first day.' ); }
	if ( $from < gmdate( 'Y-m-d', strtotime( $today . ' -' . MAGS_ERP_LEAVE_BACK_DAYS . ' days' ) ) ) { return new WP_Error( 'mags_erp_leave', sprintf( 'Leave can start at most %d days in the past.', MAGS_ERP_LEAVE_BACK_DAYS ) ); }
	if ( $to > gmdate( 'Y-m-d', strtotime( $from . ' +' . ( MAGS_ERP_LEAVE_MAX_DAYS - 1 ) . ' days' ) ) ) { return new WP_Error( 'mags_erp_leave', sprintf( 'One request can be for %d days at most. For a longer leave please meet the class teacher.', MAGS_ERP_LEAVE_MAX_DAYS ) ); }
	if ( '' === $reason ) { return new WP_Error( 'mags_erp_leave', 'Write the reason for the leave.' ); }

	$overlap = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE student_id = %d AND status IN ('pending','approved') AND from_date <= %s AND to_date >= %s", $student->id, $to, $from ) );
	if ( $overlap ) { return new WP_Error( 'mags_erp_leave', 'You already have a leave request for some of these days.' ); }

	$saved = $wpdb->insert(
		$table,
		array( 'student_id' => (int) $student->id, 'class_id' => (int) $student->class_id, 'from_date' => $from, 'to_date' => $to, 'reason' => $reason, 'status' => 'pending', 'decision_note' => '', 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_leave', 'The request could not be sent. Please try again.' );
}

/** Set a waiting request to approved, rejected or withdrawn. False when it was already answered. */
function mags_erp_set_leave_status( $leave_id, $status, $note, $user_id ) {
	global $wpdb;
	return (bool) $wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( 'leave_requests' ) . " SET status = %s, decision_note = %s, decided_by = %d, decided_at = %s WHERE id = %d AND status = 'pending'", $status, $note, $user_id, current_time( 'mysql' ), $leave_id ) );
}

/** Of the given students, those with approved leave on this date: student_id => true. */
function mags_erp_approved_leave_on( $student_ids, $date ) {
	global $wpdb;
	$on_leave = array();
	if ( ! $student_ids ) { return $on_leave; }
	$rows = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT student_id FROM ' . mags_erp_table( 'leave_requests' ) . " WHERE status = 'approved' AND from_date <= %s AND to_date >= %s AND student_id IN (" . mags_erp_id_list( $student_ids ) . ')', $date, $date ) );
	foreach ( (array) $rows as $student_id ) { $on_leave[ (int) $student_id ] = true; }
	return $on_leave;
}

/* ---------- Student actions ---------- */

function mags_erp_action_ask_leave() {
	$student = mags_erp_get_student_by_user( get_current_user_id() );
	$back    = mags_erp_url( 'leave' );
	if ( ! $student ) { mags_erp_flash( 'error', 'Your student record was not found. Please contact the school office.' ); return $back; }
	$created = mags_erp_create_leave( $student, mags_erp_post_text( 'from_date' ), mags_erp_post_text( 'to_date' ), mags_erp_post_textarea( 'reason', 1000 ) );
	mags_erp_flash( is_wp_error( $created ) ? 'error' : 'success', is_wp_error( $created ) ? $created->get_error_message() : 'Your leave request has been sent to the class teacher.' );
	return $back;
}

function mags_erp_action_withdraw_leave() {
	$student = mags_erp_get_student_by_user( get_current_user_id() );
	$leave   = mags_erp_get_leave( mags_erp_post_int( 'leave_id' ) );
	$back    = mags_erp_url( 'leave' );
	if ( ! $student || ! $leave || (int) $leave->student_id !== (int) $student->id ) { mags_erp_flash( 'error', 'That leave request is not yours.' ); return $back; }
	$done = mags_erp_set_leave_status( (int) $leave->id, 'withdrawn', '', get_current_user_id() );
	mags_erp_flash( $done ? 'success' : 'error', $done ? 'Leave request withdrawn.' : 'The class teacher has already answered this request.' );
	return $back;
}

/* ---------- Class teacher action ---------- */

function mags_erp_action_decide_leave() {
	$teacher_id = get_current_user_id();
	$leave      = mags_erp_get_leave( mags_erp_post_int( 'leave_id' ) );
	$student    = $leave ? mags_erp_get_student( (int) $leave->student_id ) : null;
	$decision   = mags_erp_post_text( 'decision' );
	$back       = mags_erp_url( 'leave' );
	if ( ! $student || $student->hidden_at || ! in_array( (int) $student->class_id, mags_erp_class_teacher_class_ids( $teacher_id ), true ) ) { mags_erp_flash( 'error', 'Only the class teacher of the student can answer this request.' ); return $back; }
	if ( ! in_array( $decision, array( 'approved', 'rejected' ), true ) ) { mags_erp_flash( 'error', 'Choose Approve or Do not approve.' ); return $back; }

	$done = mags_erp_set_leave_status( (int) $leave->id, $decision, mags_erp_post_textarea( 'decision_note', 500 ), $teacher_id );
	if ( ! $done ) { mags_erp_flash( 'error', 'This request was already answered or withdrawn.' ); return $back; }
	mags_erp_flash( 'success', sprintf( 'approved' === $decision ? 'Leave approved for %s.' : 'Leave not approved for %s.', $student->full_name ) );
	return $back;
}
