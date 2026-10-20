<?php
/**
 * Emails to parents: when a child is marked absent, when a fee is overdue and
 * when the results of an exam are published. The office turns each kind on or
 * off under More → Settings. Parents get them at the email address in the
 * student's profile.
 *
 * Emails are not sent while a teacher or the office waits: they go into a
 * queue (the mail_queue table) and WordPress's scheduled tasks (WP-Cron) send
 * them a few at a time. The queue also makes sure one parent gets one email
 * per child, kind and day / fee reminder / exam, even when a sheet is saved
 * again. An absent email waits 15 minutes and is dropped if the teacher
 * corrects the mark in the meantime. Settings → "Send waiting emails now"
 * empties the queue at once, for sites where WP-Cron does not run.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Minutes an absent email waits, so a mark saved by mistake can be corrected first. */
define( 'MAGS_ERP_ABSENT_MAIL_DELAY', 15 );

/** Emails sent in one go; the rest follow a minute later. */
define( 'MAGS_ERP_MAIL_BATCH', 25 );

/** kind => label. */
function mags_erp_mail_kinds() {
	return array( 'absent' => 'Child absent', 'fees' => 'Fees pending', 'results' => 'Results out' );
}

/** Is this kind of email turned on under Settings? */
function mags_erp_mail_enabled( $kind ) {
	$saved = (array) get_option( 'mags_erp_settings', array() );
	return ! empty( $saved[ 'mail_' . $kind ] );
}

function mags_erp_parent_email( $student_id ) {
	$email = (string) mags_erp_get_profile( (int) $student_id )->parent_email;
	return is_email( $email ) ? $email : '';
}

/** The end of every email: who sent it and where to see more. */
function mags_erp_mail_footer() {
	$school = mags_erp_school_details();
	$lines  = array( '', '— ' . $school['name'] );
	if ( '' !== $school['phone'] ) { $lines[] = 'Phone: ' . $school['phone']; }
	$lines[] = 'School portal: ' . mags_erp_url( 'login' );
	$lines[] = '';
	$lines[] = 'This message was sent by the school portal. Parents sign in with the child’s admission number.';
	return implode( "\n", $lines );
}

/**
 * Put one email in the queue. Nothing happens when the kind is turned off or
 * the student has no parent email. The same student, kind and $ref is queued
 * only once (a cancelled one is queued again). Returns true when it is waiting.
 */
function mags_erp_queue_mail( $student, $kind, $ref, $subject, $body, $delay_minutes = 0 ) {
	global $wpdb;
	if ( ! mags_erp_mail_enabled( $kind ) || ! $student || $student->hidden_at ) { return false; }
	$email = mags_erp_parent_email( (int) $student->id );
	if ( '' === $email ) { return false; }
	$table = mags_erp_table( 'mail_queue' );
	$after = gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) + 60 * (int) $delay_minutes );
	$found = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$table} WHERE student_id = %d AND kind = %s AND ref = %s", $student->id, $kind, $ref ) );
	if ( $found ) {
		if ( 'cancelled' !== $found->status ) { return false; }
		$wpdb->update( $table, array( 'status' => 'waiting', 'email' => $email, 'subject' => $subject, 'body' => $body, 'tries' => 0, 'error' => '', 'send_after' => $after ), array( 'id' => (int) $found->id ), array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ), array( '%d' ) );
	} else {
		$quiet = $wpdb->suppress_errors();
		$saved = $wpdb->insert( $table, array( 'student_id' => (int) $student->id, 'kind' => $kind, 'ref' => $ref, 'email' => $email, 'subject' => mb_substr( $subject, 0, 200 ), 'body' => $body, 'status' => 'waiting', 'send_after' => $after, 'created_at' => current_time( 'mysql' ) ), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		$wpdb->suppress_errors( $quiet );
		if ( ! $saved ) { return false; }
	}
	mags_erp_schedule_mail_run( 60 * (int) $delay_minutes );
	return true;
}

/** Ask WP-Cron to send the queue in $seconds (or sooner, when a run is already planned earlier). */
function mags_erp_schedule_mail_run( $seconds = 0 ) {
	$when = time() + max( 0, (int) $seconds ) + 5;
	$next = wp_next_scheduled( 'mags_erp_send_mail' );
	if ( $next && $next <= $when ) { return; }
	if ( $next ) { wp_unschedule_event( $next, 'mags_erp_send_mail' ); }
	wp_schedule_single_event( $when, 'mags_erp_send_mail' );
}

/** Is the reason for a waiting email still true? An absent child may have been marked present since. */
function mags_erp_mail_still_true( $row, $student ) {
	global $wpdb;
	if ( ! $student || $student->hidden_at || ! mags_erp_mail_enabled( $row->kind ) ) { return false; }
	if ( 'absent' === $row->kind ) {
		return 'A' === $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . mags_erp_table( 'attendance' ) . ' WHERE student_id = %d AND att_date = %s', $student->id, $row->ref ) );
	}
	if ( 'results' === $row->kind ) {
		$exam = mags_erp_get_exam( (int) $row->ref );
		return $exam && $exam->published_at;
	}
	// A fee reminder is not sent when everything overdue has been paid in the meantime.
	return ! empty( mags_erp_fee_dues( array( (int) $student->id ) )[ (int) $student->id ]['overdue'] );
}

/** Send up to $limit waiting emails. Returns array( sent, failed, dropped ). */
function mags_erp_process_mail_queue( $limit = MAGS_ERP_MAIL_BATCH ) {
	global $wpdb;
	$table  = mags_erp_table( 'mail_queue' );
	$done   = array( 0, 0, 0 );
	$school = mags_erp_school_details();
	$reply  = is_email( $school['email'] ) ? array( 'Reply-To: ' . $school['name'] . ' <' . $school['email'] . '>' ) : array();
	// An email left half-way by a run that stopped is tried again after half an hour.
	$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'waiting' WHERE status = 'sending' AND send_after < %s", gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - 1800 ) ) );
	$rows   = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'waiting' AND send_after <= %s ORDER BY send_after, id LIMIT %d", current_time( 'mysql' ), $limit ) );
	foreach ( $rows as $row ) {
		// Take the row first, so two runs at the same moment never send it twice.
		if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'sending' WHERE id = %d AND status = 'waiting'", $row->id ) ) ) { continue; }
		$student = mags_erp_get_student( (int) $row->student_id );
		if ( ! mags_erp_mail_still_true( $row, $student ) ) {
			$wpdb->update( $table, array( 'status' => 'dropped', 'error' => 'No longer true when it was time to send.' ), array( 'id' => (int) $row->id ), array( '%s', '%s' ), array( '%d' ) );
			$done[2]++;
			continue;
		}
		$error = '';
		$catch = static function ( $wp_error ) use ( &$error ) { $error = $wp_error instanceof WP_Error ? $wp_error->get_error_message() : 'The email could not be sent.'; };
		add_action( 'wp_mail_failed', $catch );
		$sent = wp_mail( $row->email, $row->subject, $row->body, $reply );
		remove_action( 'wp_mail_failed', $catch );
		if ( $sent ) {
			$wpdb->update( $table, array( 'status' => 'sent', 'sent_at' => current_time( 'mysql' ), 'error' => '', 'tries' => (int) $row->tries + 1 ), array( 'id' => (int) $row->id ), array( '%s', '%s', '%s', '%d' ), array( '%d' ) );
			$done[0]++;
		} else {
			$tries = (int) $row->tries + 1;
			// Try again in 10 minutes, three times in all.
			$wpdb->update( $table, array( 'status' => $tries >= 3 ? 'failed' : 'waiting', 'tries' => $tries, 'error' => mb_substr( '' !== $error ? $error : 'The email could not be sent.', 0, 200 ), 'send_after' => gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) + 600 ) ), array( 'id' => (int) $row->id ), array( '%s', '%d', '%s', '%s' ), array( '%d' ) );
			$done[1]++;
		}
	}
	$more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'waiting'" );
	if ( $more ) {
		$next = $wpdb->get_var( "SELECT MIN(send_after) FROM {$table} WHERE status = 'waiting'" );
		mags_erp_schedule_mail_run( max( 60, strtotime( (string) $next ) - strtotime( current_time( 'mysql' ) ) ) );
	}
	return $done;
}
add_action( 'mags_erp_send_mail', 'mags_erp_process_mail_queue' );

/* ---------- What starts an email ---------- */

/** After a class sheet is saved for today: queue an email for every child marked absent. Earlier days send nothing. */
function mags_erp_notify_absent( $class_id, $date, $marks ) {
	if ( ! mags_erp_mail_enabled( 'absent' ) || $date !== mags_erp_today() ) { return 0; }
	$class  = mags_erp_get_class( $class_id );
	$queued = 0;
	foreach ( mags_erp_class_students( $class_id ) as $student ) {
		if ( 'A' !== ( $marks[ (int) $student->id ] ?? '' ) ) { continue; }
		$body = sprintf( "Dear parent,\n\n%1\$s (%2\$s) was marked absent at school today, %3\$s.\n\nIf your child was at school, or if you have not told the school about this absence, please contact the class teacher or the school office.\n", $student->full_name, mags_erp_class_label( $class ), mags_erp_format_date( $date, 'l, j F Y' ) ) . mags_erp_mail_footer();
		$queued += (int) mags_erp_queue_mail( $student, 'absent', $date, sprintf( '%s was absent today (%s)', $student->full_name, mags_erp_format_date( $date, 'j M' ) ), $body, MAGS_ERP_ABSENT_MAIL_DELAY );
	}
	return $queued;
}

/** After the office publishes an exam: one email per student who has a paper in it. */
function mags_erp_notify_results( $exam ) {
	if ( ! mags_erp_mail_enabled( 'results' ) ) { return 0; }
	$queued = 0;
	foreach ( array_unique( array_map( 'intval', wp_list_pluck( mags_erp_exam_papers( (int) $exam->id ), 'class_id' ) ) ) as $class_id ) {
		foreach ( mags_erp_class_students( $class_id ) as $student ) {
			$body = sprintf( "Dear parent,\n\nThe results of %1\$s are out. You can see the marks of %2\$s and download the report card in the school portal under More → My marks.\n", $exam->name, $student->full_name ) . mags_erp_mail_footer();
			$queued += (int) mags_erp_queue_mail( $student, 'results', (string) (int) $exam->id, sprintf( 'Results of %s are out', $exam->name ), $body );
		}
	}
	return $queued;
}

/** When results are hidden again, the result emails that have not gone yet are held back (they go when the exam is published again). */
function mags_erp_cancel_result_mail( $exam_id ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( 'mail_queue' ) . " SET status = 'cancelled' WHERE kind = 'results' AND ref = %s AND status = 'waiting'", (string) (int) $exam_id ) );
}

/**
 * Fee reminders for students with overdue fees. Automatic (once a day) a
 * reminder goes only for fees that were not reminded before; with $all every
 * overdue fee is listed again (the office's button). One email per student per
 * day at most. $student_ids limits it to these students. Returns how many were queued.
 */
function mags_erp_notify_fees( $student_ids = null, $all = false ) {
	global $wpdb;
	if ( ! mags_erp_mail_enabled( 'fees' ) ) { return 0; }
	$queued = 0;
	$today  = mags_erp_today();
	foreach ( mags_erp_fee_dues( $student_ids ) as $student_id => $due ) {
		$fees = $all ? $due['fees'] : array_values( array_filter( $due['fees'], static function ( $fee ) { return ! $fee->reminded_at; } ) );
		if ( ! $fees ) { continue; }
		$student = mags_erp_get_student( $student_id );
		$lines   = array();
		foreach ( $fees as $fee ) { $lines[] = sprintf( '- %1$s: %2$s (last date %3$s)', $fee->title, mags_erp_money( $fee->unpaid ), mags_erp_format_date( $fee->due_date, 'j M Y' ) ); }
		$body = sprintf( "Dear parent,\n\nThis is a reminder that fees of %1\$s are overdue:\n\n%2\$s\n\nOverdue now: %3\$s. Total left to pay: %4\$s.\n\nFees are paid at the school office. If you have paid already, please bring the receipt to the office.\n", $student->full_name, implode( "\n", $lines ), mags_erp_money( $due['overdue'] ), mags_erp_money( $due['left'] ) ) . mags_erp_mail_footer();
		if ( mags_erp_queue_mail( $student, 'fees', 'fees-' . $today, sprintf( 'Fee reminder for %s', $student->full_name ), $body ) ) {
			$queued++;
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( 'fees' ) . ' SET reminded_at = %s WHERE id IN (' . mags_erp_id_list( wp_list_pluck( $fees, 'id' ) ) . ') AND reminded_at IS NULL', current_time( 'mysql' ) ) );
		}
	}
	return $queued;
}

/** Once a day: fee reminders for fees that became overdue. */
function mags_erp_daily_tasks() {
	mags_erp_notify_fees();
}
add_action( 'mags_erp_daily', 'mags_erp_daily_tasks' );

/** Plan the daily run at 9 in the morning, school time. */
function mags_erp_schedule_daily() {
	if ( wp_next_scheduled( 'mags_erp_daily' ) ) { return; }
	$next = new DateTime( 'tomorrow 09:00', wp_timezone() );
	wp_schedule_event( $next->getTimestamp(), 'daily', 'mags_erp_daily' );
}
add_action( 'init', 'mags_erp_schedule_daily', 30 );

/* ---------- For the settings screen ---------- */

/** status => number of emails, for the queue in the last 30 days. */
function mags_erp_mail_counts() {
	global $wpdb;
	$counts = array_fill_keys( array( 'waiting', 'sent', 'failed', 'dropped' ), 0 );
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM ' . mags_erp_table( 'mail_queue' ) . ' WHERE created_at >= %s GROUP BY status', gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - 30 * DAY_IN_SECONDS ) ) ) as $row ) {
		$key            = 'sending' === $row->status ? 'waiting' : $row->status;
		$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + (int) $row->total;
	}
	return $counts;
}

/** The newest emails, with the student's name. */
function mags_erp_recent_mail( $limit = 30 ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT q.id, q.kind, q.email, q.subject, q.status, q.error, q.created_at, q.sent_at, s.full_name FROM ' . mags_erp_table( 'mail_queue' ) . ' q LEFT JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = q.student_id ORDER BY q.id DESC LIMIT %d', $limit ) );
}

/** Students in the school now without a parent email. */
function mags_erp_students_without_email() {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'students' ) . ' s LEFT JOIN ' . mags_erp_table( 'student_profiles' ) . " p ON p.student_id = s.id WHERE s.hidden_at IS NULL AND ( p.parent_email IS NULL OR p.parent_email = '' )" );
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_mail_settings() {
	$saved = (array) get_option( 'mags_erp_settings', array() );
	foreach ( array_keys( mags_erp_mail_kinds() ) as $kind ) { $saved[ 'mail_' . $kind ] = mags_erp_post_int( 'mail_' . $kind ) ? 1 : 0; }
	update_option( 'mags_erp_settings', $saved, true );
	$on = array();
	foreach ( mags_erp_mail_kinds() as $kind => $label ) { if ( ! empty( $saved[ 'mail_' . $kind ] ) ) { $on[] = strtolower( $label ); } }
	mags_erp_flash( 'success', $on ? 'Emails to parents saved. On: ' . implode( ', ', $on ) . '.' : 'Emails to parents are all turned off.' );
	return mags_erp_url( 'settings' ) . '#erp-mail';
}

function mags_erp_action_send_mail_now() {
	list( $sent, $failed, $dropped ) = mags_erp_process_mail_queue( 200 );
	mags_erp_flash( $failed ? 'error' : 'success', sprintf( 'Emails sent: %1$d. Not sent: %2$d%3$s. No longer needed: %4$d.', $sent, $failed, $failed ? ' (they are tried again; see the list below for the reason)' : '', $dropped ) );
	return mags_erp_url( 'settings' ) . '#erp-mail';
}

/** The office's fee reminder: to one student (student_id) or to every student with an overdue fee. */
function mags_erp_action_send_fee_reminders() {
	$student_id = mags_erp_post_int( 'student_id' );
	$back       = $student_id ? mags_erp_url( 'fees', array( 'student' => $student_id ) ) : mags_erp_url( 'fees' );
	if ( ! mags_erp_mail_enabled( 'fees' ) ) { mags_erp_flash( 'error', 'Fee reminder emails are turned off. Turn them on under More → Settings.' ); return $back; }
	if ( $student_id && '' === mags_erp_parent_email( $student_id ) ) { mags_erp_flash( 'error', 'This student has no parent email. Add it in the profile first.' ); return $back; }
	$queued = mags_erp_notify_fees( $student_id ? array( $student_id ) : null, true );
	mags_erp_flash( $queued ? 'success' : 'error', $queued ? sprintf( _n( 'A fee reminder is on its way to %d parent.', 'Fee reminders are on their way to %d parents.', $queued, 'mags-erp' ), $queued ) : 'No reminder was sent: nobody with an overdue fee and a parent email is left who was not reminded today.' );
	return $back;
}
