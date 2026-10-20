<?php
/**
 * Office reports: attendance, exam marks and fees, on screen and as a CSV file
 * that opens in Excel. The fees report lists the students who are in a class
 * now. The attendance and marks reports of a class follow the class each mark
 * was made in, so after the classes move up for a new session the reports of
 * the year before still show the students who were in the class then.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Longest period one attendance report may cover. */
define( 'MAGS_ERP_REPORT_MAX_DAYS', 366 );

/** Clean a from / to pair: both valid, in order, not in the future and not longer than a year. */
function mags_erp_report_range( $from, $to ) {
	$today = mags_erp_today();
	$to    = mags_erp_valid_date( $to ) && $to <= $today ? $to : $today;
	$from  = mags_erp_valid_date( $from ) && $from <= $to ? $from : substr( $to, 0, 8 ) . '01';
	$first = gmdate( 'Y-m-d', strtotime( $to . ' -' . ( MAGS_ERP_REPORT_MAX_DAYS - 1 ) . ' days' ) );
	return array( max( $from, $first ), $to );
}

/**
 * The students of a class report: those in the class now, then the students of
 * $others (ids) who have moved to another class since, each with ->now_in and
 * the roll number they had in this class.
 */
function mags_erp_report_students( $class_id, $others ) {
	global $wpdb;
	$students = mags_erp_class_students( $class_id );
	$others   = array_diff( array_map( 'intval', (array) $others ), array_map( 'intval', wp_list_pluck( $students, 'id' ) ) );
	if ( ! $others ) { return $students; }
	$rolls = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT student_id, roll_no FROM ' . mags_erp_table( 'class_history' ) . ' WHERE from_class_id = %d AND undone_at IS NULL ORDER BY moved_at, id', $class_id ) ) as $row ) { $rolls[ (int) $row->student_id ] = $row->roll_no; }
	$labels = mags_erp_all_class_labels();
	$moved  = (array) $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE hidden_at IS NULL AND id IN (' . mags_erp_id_list( $others ) . ')' );
	foreach ( $moved as $student ) {
		$student->now_in  = $labels[ (int) $student->class_id ] ?? '';
		$student->roll_no = $rolls[ (int) $student->id ] ?? '';
	}
	return array_merge( $students, mags_erp_natural_sort( $moved, array( 'roll_no', 'full_name' ) ) );
}

/**
 * Attendance of one class between two dates: one row per student with the
 * number of days present, absent and on leave and the percentage, counting the
 * marks made in this class. Holidays are not counted.
 */
function mags_erp_report_attendance( $class_id, $from, $to ) {
	global $wpdb;
	$counts = array();
	$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT student_id, status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' WHERE class_id = %d AND att_date BETWEEN %s AND %s AND ' . mags_erp_not_holiday_sql( 'att_date' ) . ' GROUP BY student_id, status', $class_id, $from, $to ) );
	foreach ( (array) $rows as $row ) { $counts[ (int) $row->student_id ][ $row->status ] = (int) $row->total; }
	$students = mags_erp_report_students( $class_id, array_keys( $counts ) );
	$report = array();
	foreach ( $students as $student ) {
		$own      = array_merge( array( 'P' => 0, 'A' => 0, 'L' => 0 ), $counts[ (int) $student->id ] ?? array() );
		$report[] = array( 'student' => $student, 'P' => $own['P'], 'A' => $own['A'], 'L' => $own['L'], 'marked' => array_sum( $own ), 'percent' => mags_erp_percentage( $own ) );
	}
	return $report;
}

/** Attendance of every class between two dates: one row per class, counting the marks made in that class. */
function mags_erp_report_attendance_classes( $from, $to ) {
	global $wpdb;
	$counts = array();
	$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT a.class_id, a.status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' a INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = a.student_id WHERE a.att_date BETWEEN %s AND %s AND s.hidden_at IS NULL AND ' . mags_erp_not_holiday_sql( 'a.att_date' ) . ' GROUP BY a.class_id, a.status', $from, $to ) );
	foreach ( (array) $rows as $row ) { $counts[ (int) $row->class_id ][ $row->status ] = (int) $row->total; }
	$report = array();
	foreach ( mags_erp_get_classes() as $class ) {
		$own      = array_merge( array( 'P' => 0, 'A' => 0, 'L' => 0 ), $counts[ (int) $class->id ] ?? array() );
		$report[] = array( 'class' => $class, 'P' => $own['P'], 'A' => $own['A'], 'L' => $own['L'], 'marked' => array_sum( $own ), 'percent' => mags_erp_percentage( $own ) );
	}
	return $report;
}

/**
 * Marks of one class in one exam. Returns array( papers, rows ); each row has
 * the student, marks text per paper id, total, maximum and percentage.
 */
function mags_erp_report_marks( $exam_id, $class_id ) {
	global $wpdb;
	$papers = mags_erp_exam_papers( $exam_id, array( $class_id ) );
	$marks  = array();
	if ( $papers ) {
		$rows = $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'exam_marks' ) . ' WHERE paper_id IN (' . mags_erp_id_list( wp_list_pluck( $papers, 'id' ) ) . ')' );
		foreach ( (array) $rows as $row ) { $marks[ (int) $row->student_id ][ (int) $row->paper_id ] = $row; }
	}
	// Students who sat the exam in this class and have moved up since are listed too.
	$students = mags_erp_report_students( $class_id, array_keys( $marks ) );
	$report = array();
	foreach ( $students as $student ) {
		$line = array( 'student' => $student, 'cells' => array(), 'total' => 0.0, 'max' => 0.0, 'percent' => null );
		foreach ( $papers as $paper ) {
			$mark = $marks[ (int) $student->id ][ (int) $paper->id ] ?? null;
			$line['cells'][ (int) $paper->id ] = $mark ? mags_erp_marks_text( $mark ) : '–';
			if ( $mark && ( null !== $mark->marks || (int) $mark->absent ) ) {
				$line['total'] += (float) $mark->marks;
				$line['max']   += (float) $paper->max_marks;
			}
		}
		if ( $line['max'] > 0 ) { $line['percent'] = round( $line['total'] * 100 / $line['max'], 1 ); }
		$report[] = $line;
	}
	return array( $papers, $report );
}

/**
 * Fees of one class (or of all classes when $class_id is 0): one row per
 * student with charged, paid and left. $only_left keeps students who still owe.
 * Returns array( rows, totals ).
 */
function mags_erp_report_fees( $class_id, $only_left ) {
	$rows   = array();
	$totals = array( 'charged' => 0.0, 'paid' => 0.0, 'left' => 0.0 );
	foreach ( mags_erp_get_classes() as $class ) {
		if ( $class_id && (int) $class->id !== (int) $class_id ) { continue; }
		$students = mags_erp_class_students( (int) $class->id );
		$fees     = mags_erp_fee_totals( wp_list_pluck( $students, 'id' ) );
		foreach ( $students as $student ) {
			$own  = $fees[ (int) $student->id ] ?? array( 'charged' => 0.0, 'paid' => 0.0 );
			$left = round( $own['charged'] - $own['paid'], 2 );
			if ( $only_left && $left <= 0 ) { continue; }
			$rows[] = array( 'student' => $student, 'class' => mags_erp_class_label( $class ), 'charged' => $own['charged'], 'paid' => $own['paid'], 'left' => $left );
			$totals['charged'] += $own['charged'];
			$totals['paid']    += $own['paid'];
			$totals['left']    += $left;
		}
	}
	return array( $rows, array_map( static function ( $sum ) { return round( $sum, 2 ); }, $totals ) );
}

/* ---------- CSV files ---------- */

/** A spreadsheet treats a cell that starts with = + - or @ as a formula; a leading apostrophe keeps it as text. */
function mags_erp_csv_cell( $value ) {
	$value = (string) $value;
	return '' !== $value && ! is_numeric( $value ) && false !== strpos( "=+-@\t\r", $value[0] ) ? "'" . $value : $value;
}

/** Send rows as a CSV download (UTF-8 with a mark so Excel shows names correctly) and stop. */
function mags_erp_send_csv( $filename, $rows ) {
	while ( ob_get_level() ) { ob_end_clean(); }
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="' . preg_replace( '/[^A-Za-z0-9._-]+/', '-', $filename ) . '"' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	foreach ( $rows as $row ) { fputcsv( $out, array_map( 'mags_erp_csv_cell', $row ), ',', '"', '' ); }
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

/** /portal/export/?report=… : the CSV file of a report, for the office only. */
function mags_erp_export() {
	$report   = mags_erp_get_text( 'report' );
	$class    = mags_erp_get_class( mags_erp_get_int( 'class' ) );
	$class_id = $class ? (int) $class->id : 0;
	$slug     = $class ? sanitize_title( mags_erp_class_label( $class ) ) : 'all-classes';

	if ( 'sample' === $report ) { mags_erp_send_csv( 'students-sample.csv', mags_erp_import_sample_rows() ); }

	if ( 'attendance' === $report ) {
		list( $from, $to ) = mags_erp_report_range( mags_erp_get_text( 'from' ), mags_erp_get_text( 'to' ) );
		$rows = array();
		if ( $class ) {
			$rows[] = array( 'Roll No', 'Admission No', 'Name', 'Class', 'Present', 'Absent', 'Leave', 'Days marked', 'Present %' );
			foreach ( mags_erp_report_attendance( $class_id, $from, $to ) as $line ) { $rows[] = array( $line['student']->roll_no, $line['student']->admission_no, $line['student']->full_name, mags_erp_class_label( $class ), $line['P'], $line['A'], $line['L'], $line['marked'], null === $line['percent'] ? '' : $line['percent'] ); }
		} else {
			$rows[] = array( 'Class', 'Students', 'Present', 'Absent', 'Leave', 'Marks made', 'Present %' );
			foreach ( mags_erp_report_attendance_classes( $from, $to ) as $line ) { $rows[] = array( mags_erp_class_label( $line['class'] ), $line['class']->student_count, $line['P'], $line['A'], $line['L'], $line['marked'], null === $line['percent'] ? '' : $line['percent'] ); }
		}
		mags_erp_send_csv( "attendance-{$slug}-{$from}-to-{$to}.csv", $rows );
	}

	if ( 'marks' === $report ) {
		$exam = mags_erp_get_exam( mags_erp_get_int( 'exam' ) );
		if ( $exam && $class ) {
			list( $papers, $lines ) = mags_erp_report_marks( (int) $exam->id, $class_id );
			$head = array( 'Roll No', 'Admission No', 'Name', 'Class' );
			foreach ( $papers as $paper ) { $head[] = $paper->subject_name . ' (' . mags_erp_format_marks( $paper->max_marks ) . ')'; }
			$rows = array( array_merge( $head, array( 'Total', 'Out of', '%' ) ) );
			foreach ( $lines as $line ) { $rows[] = array_merge( array( $line['student']->roll_no, $line['student']->admission_no, $line['student']->full_name, mags_erp_class_label( $class ) ), array_values( $line['cells'] ), array( mags_erp_format_marks( $line['total'] ), mags_erp_format_marks( $line['max'] ), null === $line['percent'] ? '' : $line['percent'] ) ); }
			mags_erp_send_csv( 'marks-' . sanitize_title( $exam->name ) . "-{$slug}.csv", $rows );
		}
	}

	if ( 'fees' === $report ) {
		list( $lines, $totals ) = mags_erp_report_fees( $class_id, (bool) mags_erp_get_int( 'left' ) );
		$rows = array( array( 'Roll No', 'Admission No', 'Name', 'Class', 'Fees', 'Paid', 'Left to pay' ) );
		foreach ( $lines as $line ) { $rows[] = array( $line['student']->roll_no, $line['student']->admission_no, $line['student']->full_name, $line['class'], number_format( $line['charged'], 2, '.', '' ), number_format( $line['paid'], 2, '.', '' ), number_format( $line['left'], 2, '.', '' ) ); }
		$rows[] = array( '', '', 'Total', '', number_format( $totals['charged'], 2, '.', '' ), number_format( $totals['paid'], 2, '.', '' ), number_format( $totals['left'], 2, '.', '' ) );
		mags_erp_send_csv( "fees-{$slug}-" . mags_erp_today() . '.csv', $rows );
	}

	wp_safe_redirect( mags_erp_url( 'reports' ) );
	exit;
}
