<?php
/**
 * The office's "Today" page: one look at the school for one day. How many
 * children are absent (and who), which classes are not marked yet, which
 * teachers are away, the fees received that day, and the fees still pending.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Attendance of every class on one date. Returns array( classes, totals, absent ):
 * classes is a list of array( class, students, P, A, L, marked ), totals the
 * same counts for the school, absent the children marked absent (each with
 * ->class_label and ->phone).
 */
function mags_erp_today_attendance( $date ) {
	global $wpdb;
	$counts = array();
	$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT s.class_id, a.status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' a INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = a.student_id WHERE a.att_date = %s AND s.hidden_at IS NULL GROUP BY s.class_id, a.status', $date ) );
	foreach ( (array) $rows as $row ) { $counts[ (int) $row->class_id ][ $row->status ] = (int) $row->total; }

	$classes = array();
	$totals  = array( 'students' => 0, 'P' => 0, 'A' => 0, 'L' => 0, 'marked' => 0 );
	foreach ( mags_erp_get_classes() as $class ) {
		$own  = array_merge( array( 'P' => 0, 'A' => 0, 'L' => 0 ), $counts[ (int) $class->id ] ?? array() );
		$line = array( 'class' => $class, 'students' => (int) $class->student_count, 'P' => $own['P'], 'A' => $own['A'], 'L' => $own['L'], 'marked' => array_sum( $own ) );
		foreach ( array( 'students', 'P', 'A', 'L', 'marked' ) as $key ) { $totals[ $key ] += $line[ $key ]; }
		$classes[] = $line;
	}

	$absent = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT s.*, p.guardian_phone, p.emergency_phone FROM ' . mags_erp_table( 'attendance' ) . ' a INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = a.student_id LEFT JOIN ' . mags_erp_table( 'student_profiles' ) . " p ON p.student_id = s.id WHERE a.att_date = %s AND a.status = 'A' AND s.hidden_at IS NULL", $date ) );
	$labels = mags_erp_all_class_labels();
	foreach ( $absent as $student ) {
		$student->class_label = $labels[ (int) $student->class_id ] ?? '';
		$student->phone       = '' !== (string) $student->guardian_phone ? (string) $student->guardian_phone : (string) $student->emergency_phone;
	}
	return array( $classes, $totals, mags_erp_natural_sort( $absent, array( 'class_label', 'roll_no', 'full_name' ) ) );
}

/** Teachers who are away on a date: teacher_id => why ("On leave", "Absent"), from approved leave and the teacher attendance sheet. */
function mags_erp_teachers_away( $date ) {
	// Asked once per teacher and period by the substitute screen: worked out once per date in a request.
	static $known = array();
	if ( isset( $known[ $date ] ) ) { return $known[ $date ]; }
	$away   = array();
	$labels = mags_erp_statuses();
	foreach ( array_keys( mags_erp_staff_on_leave( $date ) ) as $teacher_id ) { $away[ (int) $teacher_id ] = 'On leave'; }
	foreach ( mags_erp_staff_attendance_on( $date ) as $teacher_id => $status ) {
		if ( 'P' === $status ) { unset( $away[ (int) $teacher_id ] ); continue; }
		$away[ (int) $teacher_id ] = $labels[ $status ] ?? $status;
	}
	// Removed teachers are not counted.
	$active = array_map( 'intval', wp_list_pluck( mags_erp_get_teachers(), 'ID' ) );
	return $known[ $date ] = array_intersect_key( $away, array_flip( $active ) );
}

/** Payments received on one date (not cancelled), newest first, each with ->student_name and ->class_label. */
function mags_erp_payments_on( $date ) {
	global $wpdb;
	$rows   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT p.*, s.full_name AS student_name, s.admission_no, s.class_id FROM ' . mags_erp_table( 'fee_payments' ) . ' p INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = p.student_id WHERE p.paid_on = %s AND p.hidden_at IS NULL ORDER BY p.id DESC', $date ) );
	$labels = mags_erp_all_class_labels();
	foreach ( $rows as $row ) { $row->class_label = $labels[ (int) $row->class_id ] ?? ''; }
	return $rows;
}

/** Fees still to collect from the students in the school now: array( left, overdue, owing, overdue_students ). */
function mags_erp_pending_fees_overview() {
	$sum = array( 'left' => 0.0, 'overdue' => 0.0, 'owing' => 0, 'overdue_students' => 0 );
	foreach ( mags_erp_fee_dues() as $due ) {
		$sum['left'] += $due['left'];
		$sum['owing']++;
		if ( $due['overdue'] > 0 ) {
			$sum['overdue'] += $due['overdue'];
			$sum['overdue_students']++;
		}
	}
	$sum['left']    = round( $sum['left'], 2 );
	$sum['overdue'] = round( $sum['overdue'], 2 );
	return $sum;
}
