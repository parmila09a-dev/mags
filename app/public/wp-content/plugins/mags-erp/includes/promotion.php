<?php
/**
 * New session: move every class up in one go (Class 5 → Class 6, …). The last
 * class leaves the school. Children who repeat the year can be kept back.
 *
 * Nothing of the year that ends is lost: attendance, homework answers, exam
 * marks, report cards, fees and certificates stay attached to the class the
 * child was in when they were made, and every move is written in the class
 * history (class_history) with the session, the old class and the roll number.
 * The newest promotion can be undone as long as the children have not been
 * moved again since.
 *
 * The classes themselves stay as they are, with their teachers, timetable and
 * class teacher: "Class 6 – A" next year is the same class with new children.
 * After the move, a child sees only the homework, notes and notices their new
 * class gets from then on (students.class_since), not those of the year before.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Value of "move to" for the children of a class who leave the school. */
define( 'MAGS_ERP_PROMOTE_LEAVE', 'leave' );

/** Order of a class name: Nursery, LKG, UKG, then Class 1 … 12 (also I … XII), or null when unknown. */
function mags_erp_class_rank( $name ) {
	$text  = strtolower( trim( (string) $name ) );
	$words = array( 'playgroup' => -4, 'play group' => -4, 'pre-nursery' => -4, 'pre nursery' => -4, 'nursery' => -3, 'lkg' => -2, 'kg1' => -2, 'kg 1' => -2, 'kg-1' => -2, 'ukg' => -1, 'kg2' => -1, 'kg 2' => -1, 'kg-2' => -1, 'prep' => -1 );
	foreach ( $words as $word => $rank ) { if ( false !== strpos( $text, $word ) ) { return $rank; } }
	if ( preg_match( '/\b(\d{1,2})(st|nd|rd|th)?\b/', $text, $match ) ) { return (int) $match[1]; }
	$roman = array( 'xii' => 12, 'xi' => 11, 'x' => 10, 'ix' => 9, 'viii' => 8, 'vii' => 7, 'vi' => 6, 'v' => 5, 'iv' => 4, 'iii' => 3, 'ii' => 2, 'i' => 1 );
	foreach ( $roman as $numeral => $rank ) { if ( preg_match( '/\b' . $numeral . '\b/', $text ) ) { return $rank; } }
	return null;
}

/**
 * The move the portal suggests for each class: class_id => target class id,
 * MAGS_ERP_PROMOTE_LEAVE for the highest class, or 0 (stays) when the name
 * does not tell. The next class keeps the section when it has one.
 */
function mags_erp_promotion_guess( $classes ) {
	$ranks = array();
	foreach ( $classes as $class ) { $ranks[ (int) $class->id ] = mags_erp_class_rank( $class->name ); }
	$known = array_filter( $ranks, static function ( $rank ) { return null !== $rank; } );
	$top   = $known ? max( $known ) : null;
	$plan  = array();
	foreach ( $classes as $class ) {
		$rank = $ranks[ (int) $class->id ];
		if ( null === $rank ) { $plan[ (int) $class->id ] = 0; continue; }
		$next = array_values( array_filter( $classes, static function ( $other ) use ( $ranks, $rank ) { return $ranks[ (int) $other->id ] === $rank + 1; } ) );
		if ( ! $next ) {
			$plan[ (int) $class->id ] = $rank === $top ? MAGS_ERP_PROMOTE_LEAVE : 0;
			continue;
		}
		$same = array_values( array_filter( $next, static function ( $other ) use ( $class ) { return 0 === strcasecmp( $other->section, $class->section ); } ) );
		$plan[ (int) $class->id ] = (int) ( $same ? $same[0]->id : $next[0]->id );
	}
	return $plan;
}

/** The session that ends with this promotion: the current one, or (from April to June) the one before. */
function mags_erp_promotion_sessions() {
	$now  = mags_erp_session_range();
	$prev = mags_erp_session_range( gmdate( 'Y-m-d', strtotime( $now['start'] . ' -1 day' ) ) );
	$default = (int) substr( mags_erp_today(), 5, 2 ) - (int) substr( $now['start'], 5, 2 );
	return array( 'choices' => array( $prev['label'], $now['label'] ), 'default' => ( $default >= 0 && $default < 3 ) ? $prev['label'] : $now['label'] );
}

function mags_erp_get_promotions( $limit = 20 ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'promotions' ) . ' ORDER BY id DESC LIMIT %d', $limit ) );
}

/** The newest promotion that has not been undone, or null. */
function mags_erp_last_promotion() {
	global $wpdb;
	$last = $wpdb->get_row( 'SELECT * FROM ' . mags_erp_table( 'promotions' ) . ' ORDER BY id DESC LIMIT 1' );
	return $last && ! $last->undone_at ? $last : null;
}

/** The classes a student was in before, oldest first: rows of class_history with ->class_label. */
function mags_erp_student_class_history( $student_id ) {
	global $wpdb;
	$rows   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'class_history' ) . ' WHERE student_id = %d AND undone_at IS NULL ORDER BY moved_at, id', $student_id ) );
	$labels = mags_erp_all_class_labels();
	foreach ( $rows as $row ) {
		$row->class_label = $labels[ (int) $row->from_class_id ] ?? 'Class';
		$row->to_label    = (int) $row->to_class_id ? ( $labels[ (int) $row->to_class_id ] ?? 'Class' ) : 'left the school';
	}
	return $rows;
}

/**
 * Move the classes up. $plan is class_id => target class id, MAGS_ERP_PROMOTE_LEAVE
 * or 0 (stays); $keep lists student ids who stay in their class. Every class
 * is read before anything moves, so Class 5 → 6 and Class 6 → 7 in the same
 * plan never carry a child two classes up. Returns array( moved, left ) or a WP_Error.
 */
function mags_erp_run_promotion( array $plan, array $keep, $session ) {
	global $wpdb;
	$active = array();
	foreach ( mags_erp_get_classes() as $class ) { $active[ (int) $class->id ] = $class; }
	$clean = array();
	foreach ( $plan as $class_id => $target ) {
		$class_id = (int) $class_id;
		if ( ! isset( $active[ $class_id ] ) ) { continue; }
		if ( MAGS_ERP_PROMOTE_LEAVE === $target ) { $clean[ $class_id ] = MAGS_ERP_PROMOTE_LEAVE; continue; }
		$target = (int) $target;
		if ( ! $target || $target === $class_id ) { continue; }
		if ( ! isset( $active[ $target ] ) ) { return new WP_Error( 'mags_erp_promotion', sprintf( 'The class chosen for %s no longer exists. Please check the plan again.', mags_erp_class_label( $active[ $class_id ] ) ) ); }
		$clean[ $class_id ] = $target;
	}
	if ( ! $clean ) { return new WP_Error( 'mags_erp_promotion', 'The plan moves no class. Choose where the students of each class go.' ); }
	if ( ! mags_erp_lock( 'promotion', 5 ) ) { return new WP_Error( 'mags_erp_promotion', 'Someone else is moving the classes right now. Please wait a moment and look at the classes again.' ); }

	$keep  = array_flip( array_map( 'intval', $keep ) );
	$moves = array();
	foreach ( $clean as $class_id => $target ) {
		foreach ( mags_erp_class_students( $class_id ) as $student ) {
			if ( ! isset( $keep[ (int) $student->id ] ) ) { $moves[] = array( $student, $target ); }
		}
	}
	if ( ! $moves ) { mags_erp_unlock( 'promotion' ); return new WP_Error( 'mags_erp_promotion', 'There is no student to move: the classes in the plan are empty or every student is kept back.' ); }

	$now = current_time( 'mysql' );
	$wpdb->insert( mags_erp_table( 'promotions' ), array( 'session' => $session, 'plan' => (string) wp_json_encode( array( 'plan' => $clean, 'keep' => array_keys( $keep ) ) ), 'created_by' => get_current_user_id(), 'created_at' => $now ), array( '%s', '%s', '%d', '%s' ) );
	$promotion_id = (int) $wpdb->insert_id;
	if ( ! $promotion_id ) { mags_erp_unlock( 'promotion' ); return new WP_Error( 'mags_erp_promotion', 'The promotion could not be saved. Nothing was moved. Please try again.' ); }

	$moved = 0;
	$left  = 0;
	foreach ( $moves as $move ) {
		list( $student, $target ) = $move;
		$wpdb->insert(
			mags_erp_table( 'class_history' ),
			array( 'promotion_id' => $promotion_id, 'student_id' => (int) $student->id, 'from_class_id' => (int) $student->class_id, 'to_class_id' => MAGS_ERP_PROMOTE_LEAVE === $target ? 0 : (int) $target, 'roll_no' => (string) $student->roll_no, 'prev_class_since' => $student->class_since, 'session' => $session, 'moved_at' => $now ),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		if ( MAGS_ERP_PROMOTE_LEAVE === $target ) {
			mags_erp_mark_student_left( (int) $student->id, substr( $now, 0, 10 ), 0 );
			$left++;
		} else {
			$wpdb->update( mags_erp_table( 'students' ), array( 'class_id' => (int) $target, 'class_since' => $now ), array( 'id' => (int) $student->id ), array( '%d', '%s' ), array( '%d' ) );
			$moved++;
		}
	}
	$wpdb->update( mags_erp_table( 'promotions' ), array( 'moved_count' => $moved, 'left_count' => $left ), array( 'id' => $promotion_id ), array( '%d', '%d' ), array( '%d' ) );
	mags_erp_unlock( 'promotion' );
	return array( $moved, $left );
}

/**
 * Undo the newest promotion: every child goes back to the class (and roll
 * number) they had, and those who left come back, unless the office has
 * changed them since. Returns array( back, skipped ) or a WP_Error.
 */
function mags_erp_undo_promotion( $promotion_id ) {
	global $wpdb;
	$last = mags_erp_last_promotion();
	if ( ! $last || (int) $last->id !== (int) $promotion_id ) { return new WP_Error( 'mags_erp_promotion', 'Only the newest promotion can be undone, and only once.' ); }
	if ( ! mags_erp_lock( 'promotion', 5 ) ) { return new WP_Error( 'mags_erp_promotion', 'Someone else is moving the classes right now. Please try again in a moment.' ); }
	$back    = 0;
	$skipped = 0;
	$history = mags_erp_table( 'class_history' );
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$history} WHERE promotion_id = %d AND undone_at IS NULL", $last->id ) ) as $row ) {
		$student = mags_erp_get_student( (int) $row->student_id );
		$done    = false;
		if ( $student && (int) $row->to_class_id ) {
			if ( ! $student->hidden_at && (int) $student->class_id === (int) $row->to_class_id && (string) $student->class_since === (string) $row->moved_at && mags_erp_get_class( (int) $row->from_class_id ) ) {
				$done = false !== $wpdb->update( mags_erp_table( 'students' ), array( 'class_id' => (int) $row->from_class_id, 'roll_no' => $row->roll_no, 'class_since' => $row->prev_class_since ), array( 'id' => (int) $student->id ), array( '%d', '%s', '%s' ), array( '%d' ) );
			}
		} elseif ( $student && $student->hidden_at && (string) $student->left_on === substr( (string) $row->moved_at, 0, 10 ) && ! (int) $student->left_certificate_id ) {
			$done = ! is_wp_error( mags_erp_restore_student( (int) $student->id ) );
		}
		if ( $done ) {
			$wpdb->update( $history, array( 'undone_at' => current_time( 'mysql' ) ), array( 'id' => (int) $row->id ), array( '%s' ), array( '%d' ) );
			$back++;
		} else {
			$skipped++;
		}
	}
	$wpdb->update( mags_erp_table( 'promotions' ), array( 'undone_at' => current_time( 'mysql' ), 'undone_by' => get_current_user_id() ), array( 'id' => (int) $last->id ), array( '%s', '%d' ), array( '%d' ) );
	mags_erp_unlock( 'promotion' );
	return array( $back, $skipped );
}

/* ---------- Office actions ---------- */

function mags_erp_action_promote_classes() {
	$back = mags_erp_url( 'promotion' );
	if ( ! mags_erp_post_int( 'checked' ) ) { mags_erp_flash( 'error', 'Tick the box to say that you have checked the plan.' ); return $back; }
	$session = mags_erp_post_text( 'session' );
	if ( ! in_array( $session, mags_erp_promotion_sessions()['choices'], true ) ) { mags_erp_flash( 'error', 'Choose the session that ends.' ); return $back; }
	$posted = isset( $_POST['move'] ) && is_array( $_POST['move'] ) ? wp_unslash( $_POST['move'] ) : array(); // phpcs:ignore WordPress.Security
	$plan   = array();
	foreach ( $posted as $class_id => $target ) {
		if ( is_scalar( $target ) ) { $plan[ absint( $class_id ) ] = MAGS_ERP_PROMOTE_LEAVE === $target ? MAGS_ERP_PROMOTE_LEAVE : absint( $target ); }
	}
	$keep = isset( $_POST['keep'] ) && is_array( $_POST['keep'] ) ? array_map( 'absint', $_POST['keep'] ) : array(); // phpcs:ignore WordPress.Security

	$done = mags_erp_run_promotion( $plan, $keep, $session );
	if ( is_wp_error( $done ) ) { mags_erp_flash( 'error', $done->get_error_message() ); return $back; }
	$kept = count( array_unique( $keep ) );
	mags_erp_flash( 'success', 'Classes moved up for the new session. ' . sprintf( _n( '%d student is in their new class', '%d students are in their new class', $done[0], 'mags-erp' ), $done[0] ) . ( $done[1] ? '; ' . sprintf( _n( '%d student of the last class has left the school', '%d students of the last class have left the school', $done[1], 'mags-erp' ), $done[1] ) : '' ) . ( $kept ? '; ' . sprintf( _n( '%d was kept back in their class', '%d were kept back in their class', $kept, 'mags-erp' ), $kept ) : '' ) . '. ' . sprintf( 'Attendance, marks, fees and certificates of session %s are kept with the old classes, and each move is in the student’s class history. If something is wrong, use “Undo” below before making other changes.', $session ) );
	return $back;
}

function mags_erp_action_undo_promotion() {
	$done = mags_erp_undo_promotion( mags_erp_post_int( 'promotion_id' ) );
	if ( is_wp_error( $done ) ) { mags_erp_flash( 'error', $done->get_error_message() ); return mags_erp_url( 'promotion' ); }
	mags_erp_flash( $done[1] ? 'error' : 'success', 'Promotion undone: ' . sprintf( _n( '%d student is back in their earlier class.', '%d students are back in their earlier class.', $done[0], 'mags-erp' ), $done[0] ) . ( $done[1] ? ' ' . sprintf( _n( '%d student was not moved back because the office changed them after the promotion; check them under Students.', '%d students were not moved back because the office changed them after the promotion; check them under Students.', $done[1], 'mags-erp' ), $done[1] ) : '' ) );
	return mags_erp_url( 'promotion' );
}
