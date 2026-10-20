<?php
/**
 * Parent-teacher meetings (PTM). A teacher makes time slots on a day (for
 * example 10:00 to 12:00 in 10-minute slots) for one of their classes or for
 * all of them. Parents, signed in with the child's login, pick one free slot of
 * a teacher of the child's class.
 *
 * One slot is one meeting: it is taken with a single database update that
 * works only while the slot is still free, so when two parents press at the
 * same moment exactly one gets it and the other is told to pick another. A
 * child can have one slot per teacher per day. Parents can give their slot back
 * until it starts; a teacher who removes a booked slot leaves the parent a note
 * that it was cancelled.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Limits for one round of slots. */
define( 'MAGS_ERP_PTM_MAX_SLOTS', 60 );
define( 'MAGS_ERP_PTM_DAYS_AHEAD', 90 );

/** One slot, also a removed one. */
function mags_erp_get_ptm_slot( $slot_id ) {
	global $wpdb;
	return $slot_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'ptm_slots' ) . ' WHERE id = %d', $slot_id ) ) : null;
}

/** Has the slot started (or passed)? */
function mags_erp_ptm_started( $slot ) {
	return $slot->slot_date < mags_erp_today() || ( $slot->slot_date === mags_erp_today() && $slot->start_time <= current_time( 'H:i' ) );
}

/** Add ->student_name, ->student_class and ->phone to slots that are booked. */
function mags_erp_ptm_add_students( $slots ) {
	global $wpdb;
	$ids = array_filter( array_map( 'intval', wp_list_pluck( $slots, 'student_id' ) ) );
	$who = array();
	if ( $ids ) {
		foreach ( (array) $wpdb->get_results( 'SELECT s.id, s.full_name, s.class_id, s.roll_no, p.guardian_phone FROM ' . mags_erp_table( 'students' ) . ' s LEFT JOIN ' . mags_erp_table( 'student_profiles' ) . ' p ON p.student_id = s.id WHERE s.id IN (' . mags_erp_id_list( $ids ) . ')' ) as $row ) { $who[ (int) $row->id ] = $row; }
	}
	$labels = mags_erp_all_class_labels();
	foreach ( $slots as $slot ) {
		$row                 = $who[ (int) $slot->student_id ] ?? null;
		$slot->student_name  = $row ? $row->full_name : '';
		$slot->student_class = $row ? ( $labels[ (int) $row->class_id ] ?? '' ) : '';
		$slot->phone         = $row ? (string) $row->guardian_phone : '';
	}
	return $slots;
}

/** A teacher's slots from a date on (not removed), in time order, with the booked students. */
function mags_erp_teacher_ptm_slots( $teacher_id, $from ) {
	global $wpdb;
	return mags_erp_ptm_add_students( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'ptm_slots' ) . ' WHERE teacher_id = %d AND slot_date >= %s AND hidden_at IS NULL ORDER BY slot_date, start_time', $teacher_id, $from ) ) );
}

/** A teacher's booked meetings on one date, for the home screen. */
function mags_erp_teacher_ptm_on( $teacher_id, $date ) {
	global $wpdb;
	return mags_erp_ptm_add_students( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'ptm_slots' ) . ' WHERE teacher_id = %d AND slot_date = %s AND student_id > 0 AND hidden_at IS NULL ORDER BY start_time', $teacher_id, $date ) ) );
}

/**
 * Make slots of $minutes from $from to $to on $date. Slots that would overlap a
 * slot the teacher already has are left out. Returns array( made, left out ) or a WP_Error.
 */
function mags_erp_make_ptm_slots( $teacher_id, $date, $from, $to, $minutes, $class_id, $place ) {
	global $wpdb;
	$today = mags_erp_today();
	if ( ! mags_erp_valid_date( $date ) || $date < $today ) { return new WP_Error( 'mags_erp_ptm', 'Choose the day of the meeting: today or later.' ); }
	if ( $date > gmdate( 'Y-m-d', strtotime( $today . ' +' . MAGS_ERP_PTM_DAYS_AHEAD . ' days' ) ) ) { return new WP_Error( 'mags_erp_ptm', sprintf( 'Slots can be made up to %d days ahead.', MAGS_ERP_PTM_DAYS_AHEAD ) ); }
	if ( ! mags_erp_valid_time( $from ) || ! mags_erp_valid_time( $to ) || $to <= $from ) { return new WP_Error( 'mags_erp_ptm', 'Enter the time from and to, for example 10:00 and 12:00.' ); }
	if ( $minutes < 5 || $minutes > 60 ) { return new WP_Error( 'mags_erp_ptm', 'One meeting can be 5 to 60 minutes long.' ); }
	if ( $class_id && ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) { return new WP_Error( 'mags_erp_ptm', 'Choose one of your own classes, or all of them.' ); }
	if ( mb_strlen( $place ) > 80 ) { return new WP_Error( 'mags_erp_ptm', 'The place is too long (80 characters at most).' ); }
	$holiday = mags_erp_holiday_on( $date );
	// A PTM may be on a Sunday; not on a holiday of the school calendar.
	if ( $holiday && (int) $holiday->id ) { return new WP_Error( 'mags_erp_ptm', sprintf( '%1$s is a holiday (%2$s).', mags_erp_format_date( $date ), $holiday->title ) ); }
	if ( ! mags_erp_lock( 'ptm', 5 ) ) { return new WP_Error( 'mags_erp_ptm', 'Please try again in a moment.' ); }

	$table    = mags_erp_table( 'ptm_slots' );
	$existing = (array) $wpdb->get_results( $wpdb->prepare( "SELECT start_time, end_time FROM {$table} WHERE teacher_id = %d AND slot_date = %s AND hidden_at IS NULL", $teacher_id, $date ) );
	$made     = 0;
	$skipped  = 0;
	$start    = strtotime( '1970-01-01 ' . $from . ':00 UTC' );
	$last     = strtotime( '1970-01-01 ' . $to . ':00 UTC' );
	for ( $at = $start; $at + 60 * $minutes <= $last; $at += 60 * $minutes ) {
		$begin = gmdate( 'H:i', $at );
		$end   = gmdate( 'H:i', $at + 60 * $minutes );
		$clash = false;
		foreach ( $existing as $other ) { if ( $begin < $other->end_time && $end > $other->start_time ) { $clash = true; break; } }
		if ( $clash || ( $date === $today && $begin <= current_time( 'H:i' ) ) ) { $skipped++; continue; }
		if ( $made >= MAGS_ERP_PTM_MAX_SLOTS ) { $skipped++; continue; }
		$made += (int) $wpdb->insert( $table, array( 'teacher_id' => (int) $teacher_id, 'class_id' => (int) $class_id, 'slot_date' => $date, 'start_time' => $begin, 'end_time' => $end, 'place' => $place, 'created_at' => current_time( 'mysql' ) ), array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );
	}
	mags_erp_unlock( 'ptm' );
	if ( ! $made ) { return new WP_Error( 'mags_erp_ptm', $skipped ? 'No slot was made: you already have slots at these times, or the time has passed.' : 'No slot fits between these times. Choose a longer time or shorter meetings.' ); }
	return array( $made, $skipped );
}

/**
 * Coming slots a parent can see: teacher_id => list of slots of the teachers of
 * the child's class that are for this class or all the teacher's classes. Also
 * the slots this child booked that a teacher removed, as ->cancelled.
 */
function mags_erp_student_ptm_slots( $student ) {
	global $wpdb;
	$teachers = mags_erp_class_teachers( (int) $student->class_id );
	if ( ! $teachers ) { return array(); }
	$rows   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'ptm_slots' ) . ' WHERE teacher_id IN (' . mags_erp_id_list( array_keys( $teachers ) ) . ') AND slot_date >= %s AND ( class_id = 0 OR class_id = %d ) AND ( hidden_at IS NULL OR student_id = %d ) ORDER BY slot_date, start_time', mags_erp_today(), $student->class_id, $student->id ) );
	$by     = array();
	foreach ( $rows as $slot ) {
		$slot->cancelled = (bool) $slot->hidden_at;
		$slot->mine      = (int) $slot->student_id === (int) $student->id;
		if ( mags_erp_ptm_started( $slot ) && ! $slot->mine ) { continue; }
		$by[ (int) $slot->teacher_id ][] = $slot;
	}
	return $by;
}

/** Book a slot for a child. Returns true or a WP_Error that says why not. */
function mags_erp_book_ptm( $student, $slot_id ) {
	global $wpdb;
	$slot = mags_erp_get_ptm_slot( $slot_id );
	if ( ! $slot || $slot->hidden_at ) { return new WP_Error( 'mags_erp_ptm', 'That slot is no longer offered. Please pick another one.' ); }
	if ( ! isset( mags_erp_class_teachers( (int) $student->class_id )[ (int) $slot->teacher_id ] ) || ( (int) $slot->class_id && (int) $slot->class_id !== (int) $student->class_id ) ) { return new WP_Error( 'mags_erp_ptm', 'That slot is not for your class.' ); }
	if ( mags_erp_ptm_started( $slot ) ) { return new WP_Error( 'mags_erp_ptm', 'That slot has already started. Please pick a later one.' ); }
	if ( (int) $slot->student_id === (int) $student->id ) { return new WP_Error( 'mags_erp_ptm', 'This slot is already yours.' ); }
	if ( ! mags_erp_lock( 'ptm', 5 ) ) { return new WP_Error( 'mags_erp_ptm', 'Please try again in a moment.' ); }
	$table = mags_erp_table( 'ptm_slots' );
	$own   = $wpdb->get_var( $wpdb->prepare( "SELECT start_time FROM {$table} WHERE teacher_id = %d AND slot_date = %s AND student_id = %d AND hidden_at IS NULL LIMIT 1", $slot->teacher_id, $slot->slot_date, $student->id ) );
	if ( $own ) {
		mags_erp_unlock( 'ptm' );
		return new WP_Error( 'mags_erp_ptm', sprintf( 'You already have a slot with %1$s on that day, at %2$s. Give that one back first to take another.', mags_erp_teacher_name( (int) $slot->teacher_id ), mags_erp_format_time( $own ) ) );
	}
	// Only a free slot is taken: when another parent was a moment faster, no row changes.
	$taken = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET student_id = %d, booked_at = %s WHERE id = %d AND student_id = 0 AND hidden_at IS NULL", $student->id, current_time( 'mysql' ), $slot->id ) );
	mags_erp_unlock( 'ptm' );
	return 1 === (int) $taken ? true : new WP_Error( 'mags_erp_ptm', 'Sorry, another parent has just taken this slot. Please pick another one.' );
}

/* ---------- Teacher actions ---------- */

function mags_erp_action_make_ptm_slots() {
	$made = mags_erp_make_ptm_slots( get_current_user_id(), mags_erp_post_text( 'slot_date' ), mags_erp_post_text( 'from_time' ), mags_erp_post_text( 'to_time' ), mags_erp_post_int( 'minutes' ), mags_erp_post_int( 'class_id' ), mags_erp_post_text( 'place' ) );
	if ( is_wp_error( $made ) ) { mags_erp_flash( 'error', $made->get_error_message() ); return mags_erp_url( 'ptm' ); }
	mags_erp_flash( 'success', sprintf( _n( '%d slot made. Parents can book it now.', '%d slots made. Parents can book them now.', $made[0], 'mags-erp' ), $made[0] ) . ( $made[1] ? ' ' . sprintf( '%d times were left out because you already have a slot then, the time has passed, or the limit of %d slots was reached.', $made[1], MAGS_ERP_PTM_MAX_SLOTS ) : '' ) );
	return mags_erp_url( 'ptm' );
}

/** Remove a slot. A booked slot shows the parent that it was cancelled. */
function mags_erp_action_remove_ptm_slot() {
	$slot = mags_erp_get_ptm_slot( mags_erp_post_int( 'slot_id' ) );
	if ( ! $slot || (int) $slot->teacher_id !== get_current_user_id() ) { mags_erp_flash( 'error', 'That slot is not yours.' ); return mags_erp_url( 'ptm' ); }
	$done = mags_erp_hide( 'ptm_slots', (int) $slot->id );
	mags_erp_flash( $done ? 'success' : 'error', $done ? ( (int) $slot->student_id ? 'Slot removed. The parent sees that the meeting was cancelled.' : 'Slot removed.' ) : 'That slot is already removed.' );
	return mags_erp_url( 'ptm' );
}

/* ---------- Parent (student login) actions ---------- */

function mags_erp_action_book_ptm() {
	$student = mags_erp_get_student_by_user( get_current_user_id() );
	if ( ! $student ) { mags_erp_flash( 'error', 'Student record not found.' ); return mags_erp_url( 'ptm' ); }
	$done = mags_erp_book_ptm( $student, mags_erp_post_int( 'slot_id' ) );
	if ( is_wp_error( $done ) ) { mags_erp_flash( 'error', $done->get_error_message() ); return mags_erp_url( 'ptm' ); }
	$slot = mags_erp_get_ptm_slot( mags_erp_post_int( 'slot_id' ) );
	mags_erp_flash( 'success', sprintf( 'Booked: meeting with %1$s on %2$s at %3$s%4$s.', mags_erp_teacher_name( (int) $slot->teacher_id ), mags_erp_format_date( $slot->slot_date, 'l, j F' ), mags_erp_format_time( $slot->start_time ), '' !== $slot->place ? ' (' . $slot->place . ')' : '' ) );
	return mags_erp_url( 'ptm' );
}

function mags_erp_action_cancel_ptm() {
	global $wpdb;
	$student = mags_erp_get_student_by_user( get_current_user_id() );
	$slot    = mags_erp_get_ptm_slot( mags_erp_post_int( 'slot_id' ) );
	if ( ! $student || ! $slot || (int) $slot->student_id !== (int) $student->id || $slot->hidden_at ) { mags_erp_flash( 'error', 'That slot is not yours.' ); return mags_erp_url( 'ptm' ); }
	if ( mags_erp_ptm_started( $slot ) ) { mags_erp_flash( 'error', 'This meeting has already started.' ); return mags_erp_url( 'ptm' ); }
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( 'ptm_slots' ) . ' SET student_id = 0, booked_at = NULL WHERE id = %d AND student_id = %d', $slot->id, $student->id ) );
	mags_erp_flash( 'success', 'Your slot is given back. Another parent can take it now.' );
	return mags_erp_url( 'ptm' );
}
