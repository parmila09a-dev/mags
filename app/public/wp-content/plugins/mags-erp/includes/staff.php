<?php
/**
 * Teacher attendance and teacher leave. The office marks each teacher Present,
 * Absent or on Leave for a day; a teacher sees their own attendance. A teacher
 * asks for leave and the office approves it or not.
 *
 * The rules are the same as for students: one mark per teacher per day, no
 * marking on a holiday of the school calendar, on Sunday or in the future,
 * holidays are not counted in the percentage, and an answer to a leave request
 * is final.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** How far back a teacher's leave may start and how long one request may be. */
define( 'MAGS_ERP_STAFF_LEAVE_BACK_DAYS', 7 );
define( 'MAGS_ERP_STAFF_LEAVE_MAX_DAYS', 92 );

/* ---------- Attendance ---------- */

/** teacher_id => status for one date. */
function mags_erp_staff_attendance_on( $date ) {
	global $wpdb;
	$marks = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT teacher_id, status FROM ' . mags_erp_table( 'staff_attendance' ) . ' WHERE att_date = %s', $date ) ) as $row ) { $marks[ (int) $row->teacher_id ] = $row->status; }
	return $marks;
}

/** Save one day's attendance of the teachers who have a login now. Returns status => count. */
function mags_erp_save_staff_attendance( $date, $marks, $marked_by ) {
	global $wpdb;
	$table    = mags_erp_table( 'staff_attendance' );
	$existing = mags_erp_staff_attendance_on( $date );
	$counts   = array_fill_keys( array_keys( mags_erp_statuses() ), 0 );
	$now      = current_time( 'mysql' );
	foreach ( mags_erp_get_teachers() as $teacher ) {
		$teacher_id = (int) $teacher->ID;
		$status     = isset( $marks[ $teacher_id ] ) && is_string( $marks[ $teacher_id ] ) ? $marks[ $teacher_id ] : '';
		if ( ! isset( $counts[ $status ] ) ) { continue; }
		$counts[ $status ]++;
		if ( ! isset( $existing[ $teacher_id ] ) ) {
			$wpdb->insert( $table, array( 'teacher_id' => $teacher_id, 'att_date' => $date, 'status' => $status, 'marked_by' => $marked_by, 'marked_at' => $now ), array( '%d', '%s', '%s', '%d', '%s' ) );
		} elseif ( $existing[ $teacher_id ] !== $status ) {
			$wpdb->update( $table, array( 'status' => $status, 'marked_by' => $marked_by, 'marked_at' => $now ), array( 'teacher_id' => $teacher_id, 'att_date' => $date ), array( '%s', '%d', '%s' ), array( '%d', '%s' ) );
		}
	}
	return $counts;
}

/** teacher_id => status => number of days between two dates (inclusive). Holidays are not counted. */
function mags_erp_staff_report( $from, $to ) {
	global $wpdb;
	$report = array();
	$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT teacher_id, status, COUNT(*) AS total FROM ' . mags_erp_table( 'staff_attendance' ) . ' WHERE att_date BETWEEN %s AND %s AND ' . mags_erp_not_holiday_sql( 'att_date' ) . ' GROUP BY teacher_id, status', $from, $to ) );
	foreach ( (array) $rows as $row ) { $report[ (int) $row->teacher_id ][ $row->status ] = (int) $row->total; }
	return $report;
}

/** status => number of days for one teacher between two dates (inclusive). Holidays are not counted. */
function mags_erp_staff_summary( $teacher_id, $from, $to ) {
	return array_merge( array_fill_keys( array_keys( mags_erp_statuses() ), 0 ), mags_erp_staff_report( $from, $to )[ (int) $teacher_id ] ?? array() );
}

/** date => status for one teacher between two dates (inclusive). Holidays are left out. */
function mags_erp_staff_days( $teacher_id, $from, $to ) {
	global $wpdb;
	$days = array();
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT att_date, status FROM ' . mags_erp_table( 'staff_attendance' ) . ' WHERE teacher_id = %d AND att_date BETWEEN %s AND %s AND ' . mags_erp_not_holiday_sql( 'att_date' ), $teacher_id, $from, $to ) );
	foreach ( (array) $rows as $row ) { $days[ substr( $row->att_date, 0, 10 ) ] = $row->status; }
	return $days;
}

/* ---------- Leave ---------- */

function mags_erp_get_staff_leave( $leave_id ) {
	global $wpdb;
	return $leave_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'staff_leave' ) . ' WHERE id = %d', $leave_id ) ) : null;
}

/** A teacher's own leave requests, newest first. */
function mags_erp_teacher_own_leaves( $teacher_id, $limit = 100 ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'staff_leave' ) . ' WHERE teacher_id = %d ORDER BY id DESC LIMIT %d', $teacher_id, $limit ) );
}

/** Leave requests of all teachers: the waiting ones (oldest first) or the answered ones (newest first). */
function mags_erp_staff_leaves( $pending, $limit = 100 ) {
	global $wpdb;
	$where = $pending ? "status = 'pending'" : "status IN ('approved','rejected')";
	$order = $pending ? 'from_date, id' : 'decided_at DESC, id DESC';
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'staff_leave' ) . " WHERE {$where} ORDER BY {$order} LIMIT %d", $limit ) );
}

function mags_erp_pending_staff_leave_count() {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'staff_leave' ) . " WHERE status = 'pending'" );
}

/** Ask for leave. Returns the request id or a WP_Error. */
function mags_erp_create_staff_leave( $teacher_id, $from, $to, $reason ) {
	global $wpdb;
	$table = mags_erp_table( 'staff_leave' );
	$today = mags_erp_today();
	if ( ! mags_erp_valid_date( $from ) ) { return new WP_Error( 'mags_erp_leave', 'Choose the first day of the leave.' ); }
	$to = '' === $to ? $from : $to;
	if ( ! mags_erp_valid_date( $to ) || $to < $from ) { return new WP_Error( 'mags_erp_leave', 'The last day cannot be before the first day.' ); }
	if ( $from < gmdate( 'Y-m-d', strtotime( $today . ' -' . MAGS_ERP_STAFF_LEAVE_BACK_DAYS . ' days' ) ) ) { return new WP_Error( 'mags_erp_leave', sprintf( 'Leave can start at most %d days in the past.', MAGS_ERP_STAFF_LEAVE_BACK_DAYS ) ); }
	if ( $to > gmdate( 'Y-m-d', strtotime( $from . ' +' . ( MAGS_ERP_STAFF_LEAVE_MAX_DAYS - 1 ) . ' days' ) ) ) { return new WP_Error( 'mags_erp_leave', sprintf( 'One request can be for %d days at most. For a longer leave please meet the school office.', MAGS_ERP_STAFF_LEAVE_MAX_DAYS ) ); }
	if ( '' === $reason ) { return new WP_Error( 'mags_erp_leave', 'Write the reason for the leave.' ); }

	$overlap = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE teacher_id = %d AND status IN ('pending','approved') AND from_date <= %s AND to_date >= %s", $teacher_id, $to, $from ) );
	if ( $overlap ) { return new WP_Error( 'mags_erp_leave', 'You already have a leave request for some of these days.' ); }

	$saved = $wpdb->insert( $table, array( 'teacher_id' => (int) $teacher_id, 'from_date' => $from, 'to_date' => $to, 'reason' => $reason, 'status' => 'pending', 'decision_note' => '', 'created_at' => current_time( 'mysql' ) ), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_leave', 'The request could not be sent. Please try again.' );
}

/** Set a waiting request to approved, rejected or withdrawn. False when it was already answered. */
function mags_erp_set_staff_leave_status( $leave_id, $status, $note, $user_id ) {
	global $wpdb;
	return (bool) $wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( 'staff_leave' ) . " SET status = %s, decision_note = %s, decided_by = %d, decided_at = %s WHERE id = %d AND status = 'pending'", $status, $note, $user_id, current_time( 'mysql' ), $leave_id ) );
}

/** Teachers with approved leave on this date: teacher_id => true. */
function mags_erp_staff_on_leave( $date ) {
	global $wpdb;
	$on_leave = array();
	foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT teacher_id FROM ' . mags_erp_table( 'staff_leave' ) . " WHERE status = 'approved' AND from_date <= %s AND to_date >= %s", $date, $date ) ) as $teacher_id ) { $on_leave[ (int) $teacher_id ] = true; }
	return $on_leave;
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_staff_attendance() {
	$date  = mags_erp_post_text( 'date' );
	$marks = isset( $_POST['status'] ) && is_array( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : array(); // phpcs:ignore WordPress.Security
	if ( ! mags_erp_valid_date( $date ) || $date > mags_erp_today() ) {
		mags_erp_flash( 'error', 'Choose today or an earlier date.' );
		return mags_erp_url( 'staff' );
	}
	$back    = mags_erp_url( 'staff', array( 'date' => $date ) );
	$holiday = mags_erp_holiday_on( $date );
	if ( $holiday ) {
		mags_erp_flash( 'error', sprintf( '%1$s is a holiday (%2$s). Attendance is not marked on a holiday.', mags_erp_format_date( $date ), $holiday->title ) );
		return $back;
	}
	$counts = mags_erp_save_staff_attendance( $date, $marks, get_current_user_id() );
	if ( ! array_sum( $counts ) ) {
		mags_erp_flash( 'error', 'No attendance was marked.' );
	} else {
		mags_erp_flash( 'success', sprintf( 'Teacher attendance saved for %s: %d present, %d absent, %d on leave.', mags_erp_format_date( $date ), $counts['P'], $counts['A'], $counts['L'] ) );
	}
	return $back;
}

function mags_erp_action_decide_staff_leave() {
	$leave    = mags_erp_get_staff_leave( mags_erp_post_int( 'leave_id' ) );
	$decision = mags_erp_post_text( 'decision' );
	$back     = mags_erp_url( 'staff-leave' );
	if ( ! $leave ) { mags_erp_flash( 'error', 'That leave request no longer exists.' ); return $back; }
	if ( ! in_array( $decision, array( 'approved', 'rejected' ), true ) ) { mags_erp_flash( 'error', 'Choose Approve or Do not approve.' ); return $back; }
	$done = mags_erp_set_staff_leave_status( (int) $leave->id, $decision, mags_erp_post_textarea( 'decision_note', 500 ), get_current_user_id() );
	if ( ! $done ) { mags_erp_flash( 'error', 'This request was already answered or withdrawn.' ); return $back; }
	mags_erp_flash( 'success', sprintf( 'approved' === $decision ? 'Leave approved for %s.' : 'Leave not approved for %s.', mags_erp_teacher_name( (int) $leave->teacher_id ) ) );
	return $back;
}

/* ---------- Teacher actions ---------- */

function mags_erp_action_ask_staff_leave() {
	$created = mags_erp_create_staff_leave( get_current_user_id(), mags_erp_post_text( 'from_date' ), mags_erp_post_text( 'to_date' ), mags_erp_post_textarea( 'reason', 1000 ) );
	mags_erp_flash( is_wp_error( $created ) ? 'error' : 'success', is_wp_error( $created ) ? $created->get_error_message() : 'Your leave request has been sent to the school office.' );
	return mags_erp_url( 'my-attendance' ) . ( is_wp_error( $created ) ? '' : '#erp-my-leave' );
}

function mags_erp_action_withdraw_staff_leave() {
	$leave = mags_erp_get_staff_leave( mags_erp_post_int( 'leave_id' ) );
	$back  = mags_erp_url( 'my-attendance' );
	if ( ! $leave || (int) $leave->teacher_id !== get_current_user_id() ) { mags_erp_flash( 'error', 'That leave request is not yours.' ); return $back; }
	$done = mags_erp_set_staff_leave_status( (int) $leave->id, 'withdrawn', '', get_current_user_id() );
	mags_erp_flash( $done ? 'success' : 'error', $done ? 'Leave request withdrawn.' : 'The school office has already answered this request.' );
	return $back;
}
