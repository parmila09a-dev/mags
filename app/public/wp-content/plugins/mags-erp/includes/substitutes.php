<?php
/**
 * Substitute teachers. When a teacher is on leave or absent, the office gives
 * their periods (or their class for the whole day) to another teacher for that
 * one day. The substitute sees it on their home screen and may mark the
 * attendance of that class on that day; on other days nothing changes.
 *
 * A substitute cannot be given a period they already teach (in their own
 * timetable or as a substitute elsewhere), and a teacher who is away cannot be
 * a substitute. A wrong substitution is removed (hidden), not deleted.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** How many days ahead the office can plan substitutes. */
define( 'MAGS_ERP_SUBSTITUTE_DAYS_AHEAD', 60 );

/**
 * Substitutions on a date (for one substitute teacher when $teacher_id is set),
 * each with ->class_label, ->period (row or null for the whole day), ->subject_name
 * (from the timetable of that day) and ->absent_name.
 */
function mags_erp_substitutions_on( $date, $teacher_id = 0 ) {
	global $wpdb;
	$sql  = 'SELECT * FROM ' . mags_erp_table( 'substitutions' ) . ' WHERE sub_date = %s AND hidden_at IS NULL';
	$args = array( $date );
	if ( $teacher_id ) { $sql .= ' AND teacher_id = %d'; $args[] = (int) $teacher_id; }
	$rows     = (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	$labels   = mags_erp_all_class_labels();
	$periods  = array();
	foreach ( mags_erp_get_periods() as $period ) { $periods[ (int) $period->id ] = $period; }
	$subjects = mags_erp_subject_names();
	$dow      = (int) gmdate( 'N', strtotime( $date . ' 00:00:00 UTC' ) );
	foreach ( $rows as $row ) {
		$row->class_label  = $labels[ (int) $row->class_id ] ?? 'Class';
		$row->period       = $periods[ (int) $row->period_id ] ?? null;
		$slot              = $row->period ? ( mags_erp_class_timetable( (int) $row->class_id )[ $dow ][ (int) $row->period_id ] ?? null ) : null;
		$row->subject_name = $slot && (int) $slot->subject_id ? ( $subjects[ (int) $slot->subject_id ] ?? '' ) : '';
		$row->absent_name  = (int) $row->absent_teacher_id ? mags_erp_teacher_name( (int) $row->absent_teacher_id ) : '';
		$row->sort         = $row->period ? $row->period->start_time : '00:00';
	}
	usort( $rows, static function ( $a, $b ) { return strcmp( $a->sort, $b->sort ) ?: strnatcasecmp( $a->class_label, $b->class_label ); } );
	return $rows;
}

/** Does this teacher stand in for this class on this date (a period or the whole day)? */
function mags_erp_teacher_substitutes_class( $teacher_id, $class_id, $date ) {
	global $wpdb;
	return mags_erp_valid_date( $date ) && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'substitutions' ) . ' WHERE sub_date = %s AND teacher_id = %d AND class_id = %d AND hidden_at IS NULL LIMIT 1', $date, $teacher_id, $class_id ) );
}

/** May this teacher mark the attendance of this class on this date: their own class, or one they stand in for that day. */
function mags_erp_teacher_can_mark( $teacher_id, $class_id, $date ) {
	return mags_erp_teacher_has_class( $teacher_id, $class_id ) || mags_erp_teacher_substitutes_class( $teacher_id, $class_id, $date );
}

/** The classes (rows like mags_erp_get_classes()) a teacher stands in for on a date and does not teach anyway. */
function mags_erp_substitute_classes( $teacher_id, $date ) {
	$ids = array_diff( array_map( 'intval', wp_list_pluck( mags_erp_substitutions_on( $date, $teacher_id ), 'class_id' ) ), mags_erp_teacher_class_ids( $teacher_id ) );
	return array_values( array_filter( mags_erp_get_classes(), static function ( $class ) use ( $ids ) { return in_array( (int) $class->id, $ids, true ); } ) );
}

/** Why a teacher cannot take this period on this date ('' when they can). $period_id 0 is the whole day. */
function mags_erp_substitute_problem( $teacher_id, $date, $period_id, $ignore_class = 0 ) {
	global $wpdb;
	if ( isset( mags_erp_teachers_away( $date )[ (int) $teacher_id ] ) ) { return 'is away that day'; }
	if ( ! $period_id ) { return ''; }
	$dow  = (int) gmdate( 'N', strtotime( $date . ' 00:00:00 UTC' ) );
	$own  = $wpdb->get_var( $wpdb->prepare( 'SELECT t.class_id FROM ' . mags_erp_table( 'timetable' ) . ' t INNER JOIN ' . mags_erp_table( 'classes' ) . ' c ON c.id = t.class_id WHERE t.teacher_id = %d AND t.dow = %d AND t.period_id = %d AND t.subject_id > 0 AND c.hidden_at IS NULL AND t.class_id <> %d LIMIT 1', $teacher_id, $dow, $period_id, $ignore_class ) );
	if ( $own ) { return sprintf( 'teaches %s in that period', mags_erp_all_class_labels()[ (int) $own ] ?? 'another class' ); }
	$other = $wpdb->get_var( $wpdb->prepare( 'SELECT class_id FROM ' . mags_erp_table( 'substitutions' ) . ' WHERE sub_date = %s AND teacher_id = %d AND period_id = %d AND hidden_at IS NULL LIMIT 1', $date, $teacher_id, $period_id ) );
	return $other ? sprintf( 'already stands in for %s in that period', mags_erp_all_class_labels()[ (int) $other ] ?? 'another class' ) : '';
}

/** Teachers (id => name) who can take this period on this date, for the pick list. */
function mags_erp_free_teachers( $date, $period_id, $class_id = 0 ) {
	$free = array();
	foreach ( mags_erp_get_teachers() as $teacher ) {
		if ( '' === mags_erp_substitute_problem( (int) $teacher->ID, $date, $period_id, $class_id ) ) { $free[ (int) $teacher->ID ] = $teacher->display_name; }
	}
	return $free;
}

/** Give a period (or the whole day, $period_id 0) of a class to a teacher on one date. Returns the id or a WP_Error. */
function mags_erp_save_substitution( $date, $class_id, $period_id, $absent_teacher_id, $teacher_id, $note ) {
	global $wpdb;
	$today = mags_erp_today();
	if ( ! mags_erp_valid_date( $date ) || $date < $today ) { return new WP_Error( 'mags_erp_sub', 'Choose today or a later date.' ); }
	if ( $date > gmdate( 'Y-m-d', strtotime( $today . ' +' . MAGS_ERP_SUBSTITUTE_DAYS_AHEAD . ' days' ) ) ) { return new WP_Error( 'mags_erp_sub', sprintf( 'Substitutes can be planned up to %d days ahead.', MAGS_ERP_SUBSTITUTE_DAYS_AHEAD ) ); }
	$holiday = mags_erp_holiday_on( $date );
	if ( $holiday ) { return new WP_Error( 'mags_erp_sub', sprintf( '%1$s is a holiday (%2$s).', mags_erp_format_date( $date ), $holiday->title ) ); }
	$class = mags_erp_get_class( $class_id );
	if ( ! $class ) { return new WP_Error( 'mags_erp_sub', 'Choose the class.' ); }
	if ( $period_id && ( ! mags_erp_get_period( $period_id ) || mags_erp_get_period( $period_id )->is_break ) ) { return new WP_Error( 'mags_erp_sub', 'Choose a period of the school day, or the whole day.' ); }
	if ( ! mags_erp_is_managed_user( $teacher_id, 'mags_teacher' ) || mags_erp_is_removed_user( $teacher_id ) ) { return new WP_Error( 'mags_erp_sub', 'Choose the teacher who stands in.' ); }
	if ( (int) $teacher_id === (int) $absent_teacher_id ) { return new WP_Error( 'mags_erp_sub', 'A teacher cannot stand in for themselves.' ); }
	if ( strlen( $note ) > 0 && mb_strlen( $note ) > 200 ) { return new WP_Error( 'mags_erp_sub', 'The note is too long (200 characters at most).' ); }
	if ( ! mags_erp_lock( 'substitutes', 5 ) ) { return new WP_Error( 'mags_erp_sub', 'Someone else is saving a substitute right now. Please try again.' ); }

	$table = mags_erp_table( 'substitutions' );
	$taken = $wpdb->get_var( $wpdb->prepare( "SELECT teacher_id FROM {$table} WHERE sub_date = %s AND class_id = %d AND period_id = %d AND hidden_at IS NULL LIMIT 1", $date, $class_id, $period_id ) );
	$error = '';
	if ( $taken ) {
		$error = sprintf( '%1$s already stands in for %2$s %3$s. Remove that first.', mags_erp_teacher_name( (int) $taken ), mags_erp_class_label( $class ), $period_id ? 'in that period' : 'for the whole day' );
	} else {
		$problem = mags_erp_substitute_problem( $teacher_id, $date, $period_id, $class_id );
		if ( '' !== $problem ) { $error = mags_erp_teacher_name( (int) $teacher_id ) . ' ' . $problem . '.'; }
	}
	if ( '' !== $error ) { mags_erp_unlock( 'substitutes' ); return new WP_Error( 'mags_erp_sub', $error ); }
	$saved = $wpdb->insert( $table, array( 'sub_date' => $date, 'class_id' => (int) $class_id, 'period_id' => (int) $period_id, 'absent_teacher_id' => (int) $absent_teacher_id, 'teacher_id' => (int) $teacher_id, 'note' => $note, 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ), array( '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s' ) );
	mags_erp_unlock( 'substitutes' );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_sub', 'The substitute could not be saved. Please try again.' );
}

/** The periods an away teacher takes on a date: list of timetable slots with ->period and ->class_label, in time order. */
function mags_erp_away_teacher_periods( $teacher_id, $date ) {
	$dow    = (int) gmdate( 'N', strtotime( $date . ' 00:00:00 UTC' ) );
	$week   = mags_erp_teacher_timetable( $teacher_id );
	$labels = mags_erp_all_class_labels();
	$slots  = array();
	foreach ( mags_erp_get_periods() as $period ) {
		$slot = $week[ $dow ][ (int) $period->id ] ?? null;
		if ( ! $slot || $period->is_break || ! (int) $slot->subject_id ) { continue; }
		$slot->period      = $period;
		$slot->class_label = $labels[ (int) $slot->class_id ] ?? 'Class';
		$slots[]           = $slot;
	}
	return $slots;
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_substitution() {
	$date  = mags_erp_post_text( 'date' );
	$saved = mags_erp_save_substitution( $date, mags_erp_post_int( 'class_id' ), mags_erp_post_int( 'period_id' ), mags_erp_post_int( 'absent_teacher_id' ), mags_erp_post_int( 'teacher_id' ), mags_erp_post_text( 'note' ) );
	$back  = mags_erp_url( 'substitutes', array( 'date' => mags_erp_valid_date( $date ) ? $date : '' ) );
	if ( is_wp_error( $saved ) ) { mags_erp_flash( 'error', $saved->get_error_message() ); return $back; }
	mags_erp_flash( 'success', sprintf( '%1$s stands in for %2$s on %3$s. It shows on their home screen, and they can mark the attendance of that class that day.', mags_erp_teacher_name( mags_erp_post_int( 'teacher_id' ) ), mags_erp_class_label( mags_erp_get_class( mags_erp_post_int( 'class_id' ) ) ), mags_erp_format_date( $date, 'l, j F' ) ) );
	return $back;
}

function mags_erp_action_remove_substitution() {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'substitutions' ) . ' WHERE id = %d', mags_erp_post_int( 'substitution_id' ) ) );
	if ( ! $row ) { mags_erp_flash( 'error', 'That substitute no longer exists.' ); return mags_erp_url( 'substitutes' ); }
	$done = mags_erp_hide( 'substitutions', (int) $row->id );
	mags_erp_flash( $done ? 'success' : 'error', $done ? sprintf( '%s no longer stands in for that class.', mags_erp_teacher_name( (int) $row->teacher_id ) ) : 'That substitute is already removed.' );
	return mags_erp_url( 'substitutes', array( 'date' => $row->sub_date ) );
}
