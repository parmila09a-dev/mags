<?php
/**
 * Subjects, the school's periods and the weekly timetable of each class.
 * The office makes the timetable; a student reads the timetable of their class
 * and a teacher reads their own periods.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- Subjects ---------- */

function mags_erp_get_subjects( $removed = false ) {
	global $wpdb;
	return mags_erp_natural_sort( (array) $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'subjects' ) . ' WHERE hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) ), array( 'name' ) );
}

function mags_erp_get_subject( $subject_id ) {
	global $wpdb;
	return $subject_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'subjects' ) . ' WHERE id = %d AND hidden_at IS NULL', $subject_id ) ) : null;
}

/** id => name of every subject, removed ones included, so old marks keep their subject name. */
function mags_erp_subject_names() {
	global $wpdb;
	$names = array();
	foreach ( (array) $wpdb->get_results( 'SELECT id, name FROM ' . mags_erp_table( 'subjects' ) ) as $row ) { $names[ (int) $row->id ] = $row->name; }
	return $names;
}

/** Add a subject ($subject_id 0) or rename one. Returns the subject id or a WP_Error. */
function mags_erp_save_subject( $subject_id, $name ) {
	global $wpdb;
	$table = mags_erp_table( 'subjects' );
	if ( '' === $name ) { return new WP_Error( 'mags_erp_subject', 'Enter the subject name.' ); }
	if ( mb_strlen( $name ) > 80 ) { return new WP_Error( 'mags_erp_subject', 'The subject name is too long.' ); }
	$duplicate = $wpdb->get_row( $wpdb->prepare( "SELECT id, hidden_at FROM {$table} WHERE name = %s AND id <> %d", $name, $subject_id ) );
	if ( $duplicate ) { return new WP_Error( 'mags_erp_subject', $duplicate->hidden_at ? 'A removed subject has this name. Restore it from the removed subjects below.' : 'This subject already exists.' ); }

	if ( $subject_id ) {
		if ( ! mags_erp_get_subject( $subject_id ) ) { return new WP_Error( 'mags_erp_subject', 'That subject no longer exists.' ); }
		$wpdb->update( $table, array( 'name' => $name ), array( 'id' => $subject_id ), array( '%s' ), array( '%d' ) );
		return (int) $subject_id;
	}
	$saved = $wpdb->insert( $table, array( 'name' => $name, 'created_at' => current_time( 'mysql' ) ), array( '%s', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_subject', 'The subject could not be saved. Please try again.' );
}

/** Labels of the classes whose timetable uses this subject; a subject in use is not removed. */
function mags_erp_subject_timetable_classes( $subject_id ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT c.* FROM ' . mags_erp_table( 'timetable' ) . ' t INNER JOIN ' . mags_erp_table( 'classes' ) . ' c ON c.id = t.class_id INNER JOIN ' . mags_erp_table( 'periods' ) . ' p ON p.id = t.period_id WHERE t.subject_id = %d AND c.hidden_at IS NULL AND p.hidden_at IS NULL', $subject_id ) );
	return array_map( 'mags_erp_class_label', mags_erp_natural_sort( (array) $rows, array( 'name', 'section' ) ) );
}

/* ---------- Periods ---------- */

/** The school's periods and breaks in time order. They are the rows of every class timetable. */
function mags_erp_get_periods( $removed = false ) {
	global $wpdb;
	return (array) $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'periods' ) . ' WHERE hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) . ' ORDER BY start_time, id' );
}

function mags_erp_get_period( $period_id ) {
	global $wpdb;
	return $period_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'periods' ) . ' WHERE id = %d AND hidden_at IS NULL', $period_id ) ) : null;
}

/** "8:00 am – 8:40 am". */
function mags_erp_period_time( $period ) {
	return mags_erp_format_time( $period->start_time ) . ' – ' . mags_erp_format_time( $period->end_time );
}

/** Add a period ($period_id 0) or change one. Periods may not overlap. Returns the period id or a WP_Error. */
function mags_erp_save_period( $period_id, $label, $start, $end, $is_break ) {
	global $wpdb;
	$table = mags_erp_table( 'periods' );
	if ( '' === $label ) { return new WP_Error( 'mags_erp_period', 'Enter a name for the period, such as 1 or Lunch.' ); }
	if ( mb_strlen( $label ) > 40 ) { return new WP_Error( 'mags_erp_period', 'The period name is too long.' ); }
	if ( ! mags_erp_valid_time( $start ) || ! mags_erp_valid_time( $end ) ) { return new WP_Error( 'mags_erp_period', 'Enter the start and end time, for example 08:00 and 08:40.' ); }
	if ( $end <= $start ) { return new WP_Error( 'mags_erp_period', 'The end time must be after the start time.' ); }
	foreach ( mags_erp_get_periods() as $other ) {
		if ( (int) $other->id !== (int) $period_id && $start < $other->end_time && $end > $other->start_time ) { return new WP_Error( 'mags_erp_period', sprintf( 'This time overlaps with “%s” (%s).', $other->label, mags_erp_period_time( $other ) ) ); }
	}

	$data = array( 'label' => $label, 'start_time' => $start, 'end_time' => $end, 'is_break' => $is_break ? 1 : 0 );
	if ( $period_id ) {
		if ( ! mags_erp_get_period( $period_id ) ) { return new WP_Error( 'mags_erp_period', 'That period no longer exists.' ); }
		$wpdb->update( $table, $data, array( 'id' => $period_id ), array( '%s', '%s', '%s', '%d' ), array( '%d' ) );
		return (int) $period_id;
	}
	$saved = $wpdb->insert( $table, array_merge( $data, array( 'created_at' => current_time( 'mysql' ) ) ), array( '%s', '%s', '%s', '%d', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_period', 'The period could not be saved. Please try again.' );
}

/** A removed period may be restored only when its time is still free. */
function mags_erp_restore_period( $period_id ) {
	global $wpdb;
	$period = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'periods' ) . ' WHERE id = %d AND hidden_at IS NOT NULL', $period_id ) );
	if ( ! $period ) { return new WP_Error( 'mags_erp_period', 'That period is not in the removed list.' ); }
	foreach ( mags_erp_get_periods() as $other ) {
		if ( $period->start_time < $other->end_time && $period->end_time > $other->start_time ) { return new WP_Error( 'mags_erp_period', sprintf( 'Its time overlaps with “%s” (%s). Change that period first.', $other->label, mags_erp_period_time( $other ) ) ); }
	}
	mags_erp_unhide( 'periods', $period_id );
	return true;
}

/* ---------- Timetable ---------- */

/** dow => period_id => slot row (subject_id, teacher_id) for one class. */
function mags_erp_class_timetable( $class_id ) {
	global $wpdb;
	$slots = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'timetable' ) . ' WHERE class_id = %d', $class_id ) ) as $row ) { $slots[ (int) $row->dow ][ (int) $row->period_id ] = $row; }
	return $slots;
}

/** dow => period_id => slot row (class_id, subject_id) of the periods one teacher takes, in their own classes only. */
function mags_erp_teacher_timetable( $teacher_id ) {
	global $wpdb;
	$slots     = array();
	$class_ids = mags_erp_teacher_class_ids( $teacher_id );
	if ( ! $class_ids ) { return $slots; }
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'timetable' ) . ' WHERE teacher_id = %d AND class_id IN (' . mags_erp_id_list( $class_ids ) . ')', $teacher_id ) ) as $row ) { $slots[ (int) $row->dow ][ (int) $row->period_id ] = $row; }
	return $slots;
}

/**
 * Save one day of a class timetable. $slots is period_id => array( subject_id, teacher_id ).
 * A teacher must be a teacher of the class and cannot be in two classes in the
 * same period. Nothing is saved when one period is wrong.
 */
function mags_erp_save_timetable_day( $class_id, $dow, $slots ) {
	global $wpdb;
	$table = mags_erp_table( 'timetable' );
	$days  = mags_erp_days();
	$class = mags_erp_get_class( $class_id );
	if ( ! $class ) { return new WP_Error( 'mags_erp_timetable', 'Choose a class.' ); }
	if ( ! isset( $days[ $dow ] ) ) { return new WP_Error( 'mags_erp_timetable', 'Choose a day.' ); }

	$teachers = mags_erp_class_teachers( $class_id );
	$labels   = mags_erp_all_class_labels();
	$clean    = array();
	foreach ( mags_erp_get_periods() as $period ) {
		if ( $period->is_break ) { continue; }
		$slot       = isset( $slots[ (int) $period->id ] ) && is_array( $slots[ (int) $period->id ] ) ? $slots[ (int) $period->id ] : array();
		$subject_id = absint( $slot[0] ?? 0 );
		$teacher_id = absint( $slot[1] ?? 0 );
		if ( $subject_id && ! mags_erp_get_subject( $subject_id ) ) { return new WP_Error( 'mags_erp_timetable', sprintf( 'Period %s: that subject no longer exists.', $period->label ) ); }
		if ( $teacher_id && ! $subject_id ) { return new WP_Error( 'mags_erp_timetable', sprintf( 'Period %s: choose a subject for the teacher.', $period->label ) ); }
		if ( $teacher_id && ! isset( $teachers[ $teacher_id ] ) ) { return new WP_Error( 'mags_erp_timetable', sprintf( 'Period %s: that teacher is not a teacher of %s. Give them the class first (Teachers → Edit).', $period->label, mags_erp_class_label( $class ) ) ); }
		if ( $teacher_id ) {
			$busy = (int) $wpdb->get_var( $wpdb->prepare( "SELECT t.class_id FROM {$table} t INNER JOIN " . mags_erp_table( 'classes' ) . ' c ON c.id = t.class_id WHERE t.dow = %d AND t.period_id = %d AND t.teacher_id = %d AND t.class_id <> %d AND c.hidden_at IS NULL LIMIT 1', $dow, $period->id, $teacher_id, $class_id ) );
			if ( $busy ) { return new WP_Error( 'mags_erp_timetable', sprintf( 'Period %s: %s already teaches %s at that time on %s.', $period->label, $teachers[ $teacher_id ], $labels[ $busy ] ?? 'another class', $days[ $dow ] ) ); }
		}
		$clean[ (int) $period->id ] = array( $subject_id, $teacher_id );
	}

	$existing = mags_erp_class_timetable( $class_id )[ $dow ] ?? array();
	$now      = current_time( 'mysql' );
	foreach ( $clean as $period_id => $slot ) {
		$data = array( 'subject_id' => $slot[0], 'teacher_id' => $slot[1], 'updated_at' => $now );
		if ( isset( $existing[ $period_id ] ) ) {
			if ( (int) $existing[ $period_id ]->subject_id !== $slot[0] || (int) $existing[ $period_id ]->teacher_id !== $slot[1] ) { $wpdb->update( $table, $data, array( 'id' => (int) $existing[ $period_id ]->id ), array( '%d', '%d', '%s' ), array( '%d' ) ); }
		} elseif ( $slot[0] ) {
			$wpdb->insert( $table, array_merge( $data, array( 'class_id' => $class_id, 'dow' => $dow, 'period_id' => $period_id ) ), array( '%d', '%d', '%s', '%d', '%d', '%d' ) );
		}
	}
	return true;
}

/* ---------- Showing a timetable ---------- */

/**
 * A function that describes one timetable cell for the screens: it takes the
 * slot row and returns array( main text, small text ), or null for a free period.
 * $for is "class" (subject and teacher) or "teacher" (subject and class).
 */
function mags_erp_timetable_cell( $for ) {
	$subjects = mags_erp_subject_names();
	$classes  = 'teacher' === $for ? mags_erp_all_class_labels() : array();
	return static function ( $slot ) use ( $for, $subjects, $classes ) {
		if ( ! $slot || ! (int) $slot->subject_id ) { return null; }
		$small = 'teacher' === $for ? ( $classes[ (int) $slot->class_id ] ?? '' ) : ( (int) $slot->teacher_id ? mags_erp_teacher_name( (int) $slot->teacher_id ) : '' );
		return array( $subjects[ (int) $slot->subject_id ] ?? 'Subject', $small );
	};
}

/** The periods of one day as a list (easy to read on a phone). $slots is period_id => slot row. */
function mags_erp_print_timetable_day( $periods, $slots, $cell, $is_today ) {
	$now = current_time( 'H:i' );
	echo '<ol class="erp-tt">';
	foreach ( $periods as $period ) {
		$text  = $period->is_break ? null : $cell( $slots[ (int) $period->id ] ?? null );
		$class = 'erp-tt__row' . ( $period->is_break ? ' is-break' : ( $text ? '' : ' is-free' ) ) . ( $is_today && $now >= $period->start_time && $now < $period->end_time ? ' is-now' : '' );
		echo '<li class="' . esc_attr( $class ) . '"><div class="erp-tt__time"><strong>' . esc_html( $period->label ) . '</strong><span>' . esc_html( mags_erp_period_time( $period ) ) . '</span></div><div class="erp-tt__what">';
		if ( $period->is_break ) {
			echo '<strong>Break</strong>';
		} elseif ( $text ) {
			echo '<strong>' . esc_html( $text[0] ) . '</strong>' . ( '' !== $text[1] ? '<span>' . esc_html( $text[1] ) . '</span>' : '' );
		} else {
			echo '<span>Free</span>';
		}
		echo '</div></li>';
	}
	echo '</ol>';
}

/** The whole week as a table: one row per period, one column per day. $week is dow => period_id => slot row. */
function mags_erp_print_timetable_week( $periods, $week, $cell, $caption ) {
	$days = mags_erp_days();
	echo '<div class="erp-table-wrap"><table class="erp-table erp-table--week"><caption class="erp-sr">' . esc_html( $caption ) . '</caption><thead><tr><th scope="col">Period</th>';
	foreach ( $days as $day ) { echo '<th scope="col">' . esc_html( substr( $day, 0, 3 ) ) . '<span class="erp-sr">' . esc_html( substr( $day, 3 ) ) . '</span></th>'; }
	echo '</tr></thead><tbody>';
	foreach ( $periods as $period ) {
		echo '<tr' . ( $period->is_break ? ' class="is-break"' : '' ) . '><th scope="row">' . esc_html( $period->label ) . '<small>' . esc_html( mags_erp_period_time( $period ) ) . '</small></th>';
		if ( $period->is_break ) {
			echo '<td colspan="' . esc_attr( count( $days ) ) . '">Break</td></tr>';
			continue;
		}
		foreach ( array_keys( $days ) as $dow ) {
			$text = $cell( $week[ $dow ][ (int) $period->id ] ?? null );
			echo '<td>' . ( $text ? '<strong>' . esc_html( $text[0] ) . '</strong>' . ( '' !== $text[1] ? '<small>' . esc_html( $text[1] ) . '</small>' : '' ) : '<span class="erp-sr">Free</span>' ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table></div>';
}

/** The day a timetable screen opens on: the one asked for, otherwise today, otherwise the first school day. */
function mags_erp_timetable_dow( $asked ) {
	$days = mags_erp_days();
	if ( isset( $days[ $asked ] ) ) { return (int) $asked; }
	return isset( $days[ mags_erp_today_dow() ] ) ? mags_erp_today_dow() : (int) array_key_first( $days );
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_subject() {
	$subject_id = mags_erp_post_int( 'subject_id' );
	$saved      = mags_erp_save_subject( $subject_id, mags_erp_post_text( 'erp_name' ) );
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() );
		return mags_erp_url( 'subjects', array( 'edit' => $subject_id ?: '' ) );
	}
	mags_erp_flash( 'success', $subject_id ? 'Subject updated.' : 'Subject added.' );
	return mags_erp_url( 'subjects' );
}

function mags_erp_action_delete_subject() {
	$subject_id = mags_erp_post_int( 'subject_id' );
	$back       = mags_erp_url( 'subjects' );
	if ( mags_erp_post_int( 'restore' ) ) {
		$restored = mags_erp_unhide( 'subjects', $subject_id );
		mags_erp_flash( $restored ? 'success' : 'error', $restored ? 'Subject restored.' : 'That subject is not in the removed list.' );
		return $back;
	}
	$subject = mags_erp_get_subject( $subject_id );
	if ( ! $subject ) { mags_erp_flash( 'error', 'That subject no longer exists.' ); return $back; }
	$used = mags_erp_subject_timetable_classes( $subject_id );
	if ( $used ) { mags_erp_flash( 'error', sprintf( '%s is in the timetable of %s. Change those periods first.', $subject->name, implode( ', ', $used ) ) ); return $back; }
	mags_erp_hide( 'subjects', $subject_id );
	mags_erp_flash( 'success', 'Subject removed. Marks already entered for it are kept.' );
	return $back;
}

function mags_erp_action_save_period() {
	$period_id = mags_erp_post_int( 'period_id' );
	$saved     = mags_erp_save_period( $period_id, mags_erp_post_text( 'label' ), mags_erp_post_text( 'start_time' ), mags_erp_post_text( 'end_time' ), mags_erp_post_int( 'is_break' ) );
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() );
		return mags_erp_url( 'subjects', array( 'period' => $period_id ?: '' ) );
	}
	mags_erp_flash( 'success', $period_id ? 'Period updated.' : 'Period added.' );
	return mags_erp_url( 'subjects' );
}

function mags_erp_action_delete_period() {
	$period_id = mags_erp_post_int( 'period_id' );
	$back      = mags_erp_url( 'subjects' );
	if ( mags_erp_post_int( 'restore' ) ) {
		$restored = mags_erp_restore_period( $period_id );
		mags_erp_flash( is_wp_error( $restored ) ? 'error' : 'success', is_wp_error( $restored ) ? $restored->get_error_message() : 'Period restored, with its timetable entries.' );
		return $back;
	}
	if ( ! mags_erp_get_period( $period_id ) ) { mags_erp_flash( 'error', 'That period no longer exists.' ); return $back; }
	mags_erp_hide( 'periods', $period_id );
	mags_erp_flash( 'success', 'Period removed from every timetable. Its entries are kept and return if you restore it.' );
	return $back;
}

function mags_erp_action_save_timetable() {
	$class_id = mags_erp_post_int( 'class_id' );
	$dow      = mags_erp_post_int( 'dow' );
	$subjects = isset( $_POST['subject'] ) && is_array( $_POST['subject'] ) ? $_POST['subject'] : array(); // phpcs:ignore WordPress.Security
	$teachers = isset( $_POST['teacher'] ) && is_array( $_POST['teacher'] ) ? $_POST['teacher'] : array(); // phpcs:ignore WordPress.Security
	$slots    = array();
	foreach ( $subjects as $period_id => $subject_id ) {
		if ( is_scalar( $subject_id ) ) { $slots[ absint( $period_id ) ] = array( absint( $subject_id ), isset( $teachers[ $period_id ] ) && is_scalar( $teachers[ $period_id ] ) ? absint( $teachers[ $period_id ] ) : 0 ); }
	}

	$saved = mags_erp_save_timetable_day( $class_id, $dow, $slots );
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() . ' Nothing was saved for this day.' );
	} else {
		mags_erp_flash( 'success', sprintf( 'Timetable saved: %s, %s.', mags_erp_class_label( mags_erp_get_class( $class_id ) ), mags_erp_days()[ $dow ] ) );
	}
	return mags_erp_url( 'timetable', array( 'class' => $class_id ?: '', 'dow' => $dow ?: '' ) );
}
