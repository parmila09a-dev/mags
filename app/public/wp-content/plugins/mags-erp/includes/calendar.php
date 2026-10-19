<?php
/**
 * School calendar. The office adds holidays and events; teachers and students
 * see them month by month.
 *
 * A holiday is a day without school: attendance cannot be marked on it, and
 * marks that were saved on that day before it became a holiday are kept but
 * not counted in any percentage. An event is only shown in the calendar.
 * A removed entry is hidden, not deleted, and can be restored.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Longest holiday or event (summer vacation fits). Guards against a wrong year closing the school for years. */
define( 'MAGS_ERP_CALENDAR_MAX_DAYS', 92 );

/** How many months back and ahead the calendar can be turned. */
define( 'MAGS_ERP_CALENDAR_MONTHS', 24 );

/** kind => array( label, chip class ). */
function mags_erp_calendar_kinds() {
	return array(
		'holiday' => array( 'Holiday', 'erp-chip--blue' ),
		'event'   => array( 'Event', 'erp-chip--orange' ),
	);
}

/** One calendar entry. A removed entry is returned only when $with_removed is true. */
function mags_erp_get_calendar_entry( $entry_id, $with_removed = false ) {
	global $wpdb;
	$entry = $entry_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'calendar' ) . ' WHERE id = %d', $entry_id ) ) : null;
	return $entry && ( $with_removed || ! $entry->hidden_at ) ? $entry : null;
}

/** Holidays and events that touch the days from $from to $to, in date order. */
function mags_erp_calendar_entries( $from, $to ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'calendar' ) . ' WHERE hidden_at IS NULL AND from_date <= %s AND to_date >= %s ORDER BY from_date, kind DESC, id', $to, $from ) );
}

function mags_erp_removed_calendar_entries( $limit = 100 ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'calendar' ) . ' WHERE hidden_at IS NOT NULL ORDER BY from_date DESC, id DESC LIMIT %d', $limit ) );
}

/** The holiday that covers this date, or null when it is a school day. */
function mags_erp_holiday_on( $date ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'calendar' ) . " WHERE kind = 'holiday' AND hidden_at IS NULL AND from_date <= %s AND to_date >= %s ORDER BY from_date, id LIMIT 1", $date, $date ) );
}

/** date => holiday title for every holiday day between two dates (inclusive). */
function mags_erp_holiday_dates( $from, $to ) {
	$dates = array();
	foreach ( mags_erp_calendar_entries( $from, $to ) as $entry ) {
		if ( 'holiday' !== $entry->kind ) { continue; }
		$last = min( $entry->to_date, $to );
		for ( $date = max( $entry->from_date, $from ); $date <= $last; $date = gmdate( 'Y-m-d', strtotime( $date . ' +1 day' ) ) ) {
			if ( ! isset( $dates[ $date ] ) ) { $dates[ $date ] = $entry->title; }
		}
	}
	return $dates;
}

/**
 * SQL condition that leaves out attendance saved on a holiday. $column is the
 * attendance date column of the query, for example "a.att_date"; it is written
 * in the code, never taken from a request.
 */
function mags_erp_not_holiday_sql( $column ) {
	return 'NOT EXISTS (SELECT 1 FROM ' . mags_erp_table( 'calendar' ) . " hol WHERE hol.kind = 'holiday' AND hol.hidden_at IS NULL AND hol.from_date <= {$column} AND hol.to_date >= {$column})";
}

/** Number of school days between two dates on which attendance has been saved. */
function mags_erp_marked_days_between( $from, $to ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT att_date) FROM ' . mags_erp_table( 'attendance' ) . ' WHERE att_date BETWEEN %s AND %s', $from, $to ) );
}

/** Number of days of an entry, both ends counted. */
function mags_erp_calendar_days( $entry ) {
	return (int) round( ( strtotime( $entry->to_date . ' 00:00:00 UTC' ) - strtotime( $entry->from_date . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS ) + 1;
}

/** "26 January 2027" or "20 May 2027 to 30 June 2027 (42 days)". */
function mags_erp_calendar_dates( $entry ) {
	if ( $entry->from_date === $entry->to_date ) { return mags_erp_format_date( $entry->from_date, 'l, j F Y' ); }
	return mags_erp_format_date( $entry->from_date ) . ' to ' . mags_erp_format_date( $entry->to_date ) . ' (' . mags_erp_calendar_days( $entry ) . ' days)';
}

/** Add a holiday or event ($entry_id 0) or change one. Returns the entry id or a WP_Error. */
function mags_erp_save_calendar_entry( $entry_id, $kind, $title, $from, $to, $details ) {
	global $wpdb;
	$table = mags_erp_table( 'calendar' );
	if ( ! isset( mags_erp_calendar_kinds()[ $kind ] ) ) { return new WP_Error( 'mags_erp_calendar', 'Choose Holiday or Event.' ); }
	if ( '' === $title ) { return new WP_Error( 'mags_erp_calendar', 'Enter the name, such as Diwali or Annual Day.' ); }
	if ( mb_strlen( $title ) > 120 ) { return new WP_Error( 'mags_erp_calendar', 'The name is too long (120 characters at most).' ); }
	if ( ! mags_erp_valid_date( $from ) ) { return new WP_Error( 'mags_erp_calendar', 'Choose the date.' ); }
	$to = '' === $to ? $from : $to;
	if ( ! mags_erp_valid_date( $to ) || $to < $from ) { return new WP_Error( 'mags_erp_calendar', 'The last day cannot be before the first day.' ); }
	if ( $to > gmdate( 'Y-m-d', strtotime( $from . ' +' . ( MAGS_ERP_CALENDAR_MAX_DAYS - 1 ) . ' days' ) ) ) { return new WP_Error( 'mags_erp_calendar', sprintf( 'One entry can be %d days at most. Please check the year of both dates, or add a long break in two parts.', MAGS_ERP_CALENDAR_MAX_DAYS ) ); }

	$data = array( 'kind' => $kind, 'title' => $title, 'from_date' => $from, 'to_date' => $to, 'details' => $details );
	if ( $entry_id ) {
		if ( ! mags_erp_get_calendar_entry( $entry_id ) ) { return new WP_Error( 'mags_erp_calendar', 'That calendar entry no longer exists.' ); }
		$updated = $wpdb->update( $table, $data, array( 'id' => $entry_id ), array( '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
		return false === $updated ? new WP_Error( 'mags_erp_calendar', 'The entry could not be saved. Please try again.' ) : (int) $entry_id;
	}
	$saved = $wpdb->insert( $table, array_merge( $data, array( 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ) ), array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_calendar', 'The entry could not be saved. Please try again.' );
}

/* ---------- Showing a month ---------- */

/**
 * The month a calendar screen shows: the one asked for ("2027-01") when it is
 * within reach, otherwise the current month. Returns its first and last day,
 * the months before and after ('' at the end of the reach) and the empty cells
 * before day 1.
 */
function mags_erp_calendar_month( $asked ) {
	$today = mags_erp_today();
	$this_month = substr( $today, 0, 7 );
	$first = gmdate( 'Y-m', strtotime( $this_month . '-01 -' . MAGS_ERP_CALENDAR_MONTHS . ' months' ) );
	$last  = gmdate( 'Y-m', strtotime( $this_month . '-01 +' . MAGS_ERP_CALENDAR_MONTHS . ' months' ) );
	$month = is_string( $asked ) && preg_match( '/^\d{4}-\d{2}$/', $asked ) && mags_erp_valid_date( $asked . '-01' ) && $asked >= $first && $asked <= $last ? $asked : $this_month;
	$start = $month . '-01';
	$days  = (int) gmdate( 't', strtotime( $start ) );
	$prev  = gmdate( 'Y-m', strtotime( $start . ' -1 month' ) );
	$next  = gmdate( 'Y-m', strtotime( $start . ' +1 month' ) );
	$week_start = (int) get_option( 'start_of_week', 1 );
	return array(
		'month'      => $month,
		'start'      => $start,
		'end'        => $month . '-' . sprintf( '%02d', $days ),
		'days'       => $days,
		'prev'       => $prev >= $first ? $prev : '',
		'next'       => $next <= $last ? $next : '',
		'is_current' => $month === $this_month,
		'week_start' => $week_start,
		'blank'      => ( (int) gmdate( 'w', strtotime( $start ) ) - $week_start + 7 ) % 7,
	);
}

/** The month heading with the previous and next arrows. */
function mags_erp_print_calendar_head( $month, $title_id ) {
	echo '<div class="erp-month">';
	echo $month['prev'] ? '<a class="erp-iconbtn" href="' . esc_url( mags_erp_url( 'calendar', array( 'month' => $month['prev'] ) ) ) . '" aria-label="Previous month">‹</a>' : '<span class="erp-iconbtn is-disabled" aria-hidden="true">‹</span>';
	echo '<h2 id="' . esc_attr( $title_id ) . '">' . esc_html( mags_erp_format_date( $month['start'], 'F Y' ) ) . '</h2>';
	echo $month['next'] ? '<a class="erp-iconbtn" href="' . esc_url( mags_erp_url( 'calendar', array( 'month' => $month['next'] ) ) ) . '" aria-label="Next month">›</a>' : '<span class="erp-iconbtn is-disabled" aria-hidden="true">›</span>';
	echo '</div>';
}

/** The month as a grid of days: holidays are filled, days with an event carry a dot. $entries come from mags_erp_calendar_entries(). */
function mags_erp_print_calendar_grid( $month, $entries ) {
	$today = mags_erp_today();
	$marks = array();
	foreach ( $entries as $entry ) {
		$last = min( $entry->to_date, $month['end'] );
		for ( $date = max( $entry->from_date, $month['start'] ); $date <= $last; $date = gmdate( 'Y-m-d', strtotime( $date . ' +1 day' ) ) ) { $marks[ $date ][ $entry->kind ][] = $entry->title; }
	}

	echo '<ol class="erp-cal erp-cal--school" aria-label="' . esc_attr( 'School calendar for ' . mags_erp_format_date( $month['start'], 'F Y' ) ) . '">';
	for ( $i = 0; $i < 7; $i++ ) { echo '<li class="erp-cal__dow" aria-hidden="true">' . esc_html( mysql2date( 'D', gmdate( 'Y-m-d', strtotime( 'Sunday +' . ( $month['week_start'] + $i ) . ' days' ) ) . ' 00:00:00' ) ) . '</li>'; }
	for ( $i = 0; $i < $month['blank']; $i++ ) { echo '<li class="erp-cal__blank" aria-hidden="true"></li>'; }
	for ( $day = 1; $day <= $month['days']; $day++ ) {
		$date     = $month['month'] . '-' . sprintf( '%02d', $day );
		$holidays = $marks[ $date ]['holiday'] ?? array();
		$events   = $marks[ $date ]['event'] ?? array();
		$spoken   = array();
		if ( $holidays ) { $spoken[] = 'Holiday: ' . implode( ', ', $holidays ); }
		if ( $events ) { $spoken[] = 'Event: ' . implode( ', ', $events ); }
		echo '<li class="erp-cal__day' . ( $holidays ? ' erp-cal__day--H' : '' ) . ( $events ? ' has-event' : '' ) . ( $date === $today ? ' is-today' : '' ) . '">';
		echo '<span aria-hidden="true">' . esc_html( $day ) . '</span>';
		if ( $holidays ) { echo '<b aria-hidden="true">H</b>'; }
		if ( $events ) { echo '<i class="erp-cal__event" aria-hidden="true"></i>'; }
		echo '<span class="erp-sr">' . esc_html( mags_erp_format_date( $date, 'j F' ) . ': ' . ( $spoken ? implode( '; ', $spoken ) : 'nothing' ) ) . '</span></li>';
	}
	echo '</ol>';
	echo '<p class="erp-legend" aria-hidden="true"><span class="erp-dot erp-dot--H">H</span> Holiday <span class="erp-dot erp-dot--E"></span> Event</p>';
}

/** The holidays and events of the shown month as a list. $can_edit adds the office's Edit and Remove buttons. */
function mags_erp_print_calendar_list( $month, $entries, $can_edit = false ) {
	$kinds = mags_erp_calendar_kinds();
	$today = mags_erp_today();
	if ( ! $entries ) {
		echo '<p class="erp-card erp-empty">No holiday or event in ' . esc_html( mags_erp_format_date( $month['start'], 'F Y' ) ) . '.</p>';
		return;
	}
	echo '<ul class="erp-list">';
	foreach ( $entries as $entry ) {
		$kind = $kinds[ $entry->kind ] ?? array( 'Entry', '' );
		$when = $entry->to_date < $today ? '' : ( $entry->from_date <= $today ? 'Today' : '' );
		echo '<li class="erp-row"><div class="erp-row__main"><strong>' . esc_html( $entry->title ) . '</strong><span>' . esc_html( mags_erp_calendar_dates( $entry ) ) . '</span>';
		if ( '' !== trim( $entry->details ) ) { echo '<span>' . nl2br( esc_html( $entry->details ) ) . '</span>'; }
		echo '<span class="erp-row__chips"><span class="erp-chip ' . esc_attr( $kind[1] ) . '">' . esc_html( $kind[0] ) . '</span>' . ( $when ? '<span class="erp-chip erp-chip--green">' . esc_html( $when ) . '</span>' : '' ) . '</span></div>';
		if ( $can_edit ) {
			echo '<div class="erp-row__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="' . esc_url( mags_erp_url( 'calendar', array( 'edit' => (int) $entry->id, 'month' => $month['month'] ) ) ) . '">Edit<span class="erp-sr"> ' . esc_html( $entry->title ) . '</span></a>';
			echo '<form method="post" action="' . esc_url( mags_erp_url( 'calendar' ) ) . '" data-erp-confirm="' . esc_attr( 'Remove “' . $entry->title . '” from the calendar? It is kept and can be restored.' . ( 'holiday' === $entry->kind ? ' Attendance can then be marked on those days.' : '' ) ) . '">';
			mags_erp_form_fields( 'delete_calendar' );
			echo '<input type="hidden" name="entry_id" value="' . esc_attr( $entry->id ) . '"><button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove<span class="erp-sr"> ' . esc_html( $entry->title ) . '</span></button></form></div>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_calendar() {
	$entry_id = mags_erp_post_int( 'entry_id' );
	$kind     = mags_erp_post_text( 'kind' );
	$from     = mags_erp_post_text( 'from_date' );
	$to       = mags_erp_post_text( 'to_date' );
	$saved    = mags_erp_save_calendar_entry( $entry_id, $kind, mags_erp_post_text( 'title' ), $from, $to, mags_erp_post_textarea( 'details', 1000 ) );
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() );
		return mags_erp_url( 'calendar', array( 'edit' => $entry_id ?: '', 'month' => mags_erp_valid_date( $from ) ? substr( $from, 0, 7 ) : '' ) );
	}

	$entry  = mags_erp_get_calendar_entry( $saved );
	$marked = 'holiday' === $entry->kind ? mags_erp_marked_days_between( $entry->from_date, $entry->to_date ) : 0;
	$note   = $marked ? ' ' . sprintf( _n( 'Attendance was already saved on %d day of this holiday. It is kept, but it is not counted while the holiday stands.', 'Attendance was already saved on %d days of this holiday. It is kept, but it is not counted while the holiday stands.', $marked, 'mags-erp' ), $marked ) : '';
	mags_erp_flash( 'success', sprintf( '%1$s %2$s: %3$s, %4$s.', mags_erp_calendar_kinds()[ $entry->kind ][0], $entry_id ? 'changed' : 'added', $entry->title, mags_erp_calendar_dates( $entry ) ) . $note );
	return mags_erp_url( 'calendar', array( 'month' => substr( $entry->from_date, 0, 7 ) ) );
}

/** Remove a holiday or event (hidden and kept) or restore a removed one. */
function mags_erp_action_delete_calendar() {
	$entry = mags_erp_get_calendar_entry( mags_erp_post_int( 'entry_id' ), true );
	if ( ! $entry ) { mags_erp_flash( 'error', 'That calendar entry no longer exists.' ); return mags_erp_url( 'calendar' ); }
	$back   = mags_erp_url( 'calendar', array( 'month' => substr( $entry->from_date, 0, 7 ) ) );
	$label  = mags_erp_calendar_kinds()[ $entry->kind ][0] ?? 'Entry';
	$marked = 'holiday' === $entry->kind ? mags_erp_marked_days_between( $entry->from_date, $entry->to_date ) : 0;

	if ( mags_erp_post_int( 'restore' ) ) {
		$restored = mags_erp_unhide( 'calendar', (int) $entry->id );
		mags_erp_flash( $restored ? 'success' : 'error', $restored ? $label . ' restored: ' . $entry->title . '.' . ( $marked ? ' Attendance saved on those days is not counted again.' : '' ) : 'That entry is not in the removed list.' );
		return $back;
	}
	$removed = mags_erp_hide( 'calendar', (int) $entry->id );
	mags_erp_flash( $removed ? 'success' : 'error', $removed ? $label . ' removed: ' . $entry->title . '.' . ( 'holiday' === $entry->kind ? ' Attendance can be marked on those days again' . ( $marked ? ', and the attendance already saved there is counted again.' : '.' ) : '' ) : 'That entry is already removed.' );
	return $back;
}
