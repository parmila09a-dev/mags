<?php
/** Database access for classes, teachers, students and attendance. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Comma-separated list of whole numbers that is safe to place inside IN (...). */
function mags_erp_id_list( $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	return $ids ? implode( ',', $ids ) : '0';
}

/* ---------- Classes ---------- */

function mags_erp_get_classes() {
	global $wpdb;
	$classes = $wpdb->get_results( 'SELECT c.*, COUNT(s.id) AS student_count FROM ' . mags_erp_table( 'classes' ) . ' c LEFT JOIN ' . mags_erp_table( 'students' ) . ' s ON s.class_id = c.id GROUP BY c.id' );
	return mags_erp_natural_sort( (array) $classes, array( 'name', 'section' ) );
}

function mags_erp_get_class( $class_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'classes' ) . ' WHERE id = %d', $class_id ) );
}

/** Add a class ($class_id 0) or rename one. Returns the class id or a WP_Error. */
function mags_erp_save_class( $class_id, $name, $section ) {
	global $wpdb;
	$table = mags_erp_table( 'classes' );
	if ( '' === $name ) { return new WP_Error( 'mags_erp_class', 'Enter the class name.' ); }
	if ( mb_strlen( $name ) > 60 || mb_strlen( $section ) > 20 ) { return new WP_Error( 'mags_erp_class', 'The class name or section is too long.' ); }

	$duplicate = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s AND section = %s AND id <> %d", $name, $section, $class_id ) );
	if ( $duplicate ) { return new WP_Error( 'mags_erp_class', 'This class and section already exist.' ); }

	if ( $class_id ) {
		if ( ! mags_erp_get_class( $class_id ) ) { return new WP_Error( 'mags_erp_class', 'That class no longer exists.' ); }
		$wpdb->update( $table, array( 'name' => $name, 'section' => $section ), array( 'id' => $class_id ), array( '%s', '%s' ), array( '%d' ) );
		return (int) $class_id;
	}
	$saved = $wpdb->insert( $table, array( 'name' => $name, 'section' => $section, 'created_at' => current_time( 'mysql' ) ), array( '%s', '%s', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_class', 'The class could not be saved. Please try again.' );
}

/** A class can be removed only when no student is in it, so no child loses their class by mistake. */
function mags_erp_delete_class( $class_id ) {
	global $wpdb;
	$students = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'students' ) . ' WHERE class_id = %d', $class_id ) );
	if ( $students ) { return new WP_Error( 'mags_erp_class', 'Move or remove the students of this class first.' ); }
	mags_erp_delete_class_work( $class_id );
	$wpdb->delete( mags_erp_table( 'class_teachers' ), array( 'class_id' => $class_id ), array( '%d' ) );
	$wpdb->delete( mags_erp_table( 'classes' ), array( 'id' => $class_id ), array( '%d' ) );
	return true;
}

/* ---------- Teachers ---------- */

function mags_erp_get_teachers() {
	return get_users( array( 'role' => 'mags_teacher', 'orderby' => 'display_name', 'order' => 'ASC' ) );
}

/** class_id => list of teacher display names, for the office screens. */
function mags_erp_class_teacher_names() {
	global $wpdb;
	$names = array();
	$rows  = $wpdb->get_results( 'SELECT ct.class_id, u.display_name FROM ' . mags_erp_table( 'class_teachers' ) . " ct INNER JOIN {$wpdb->users} u ON u.ID = ct.teacher_id ORDER BY u.display_name" );
	foreach ( (array) $rows as $row ) { $names[ (int) $row->class_id ][] = $row->display_name; }
	return $names;
}

function mags_erp_teacher_class_ids( $teacher_id ) {
	global $wpdb;
	return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT class_id FROM ' . mags_erp_table( 'class_teachers' ) . ' WHERE teacher_id = %d', $teacher_id ) ) );
}

/** Classes a teacher may mark, each with its student count. */
function mags_erp_teacher_classes( $teacher_id ) {
	$own = mags_erp_teacher_class_ids( $teacher_id );
	return array_values( array_filter( mags_erp_get_classes(), static function ( $class ) use ( $own ) { return in_array( (int) $class->id, $own, true ); } ) );
}

function mags_erp_teacher_has_class( $teacher_id, $class_id ) {
	return in_array( (int) $class_id, mags_erp_teacher_class_ids( $teacher_id ), true );
}

function mags_erp_set_teacher_classes( $teacher_id, $class_ids ) {
	global $wpdb;
	$table = mags_erp_table( 'class_teachers' );
	$valid = array_map( 'intval', wp_list_pluck( mags_erp_get_classes(), 'id' ) );
	$wpdb->delete( $table, array( 'teacher_id' => $teacher_id ), array( '%d' ) );
	foreach ( array_intersect( array_unique( array_map( 'absint', (array) $class_ids ) ), $valid ) as $class_id ) {
		$wpdb->insert( $table, array( 'class_id' => $class_id, 'teacher_id' => $teacher_id ), array( '%d', '%d' ) );
	}
}

/** Create a teacher login. Returns array( user_id, password ) or a WP_Error. */
function mags_erp_create_teacher( $name, $username ) {
	if ( '' === $name ) { return new WP_Error( 'mags_erp_teacher', 'Enter the teacher’s name.' ); }
	if ( ! preg_match( '/^[a-z][a-z0-9._-]{2,39}$/', $username ) ) { return new WP_Error( 'mags_erp_teacher', 'The username must start with a letter and use 3 to 40 small letters, numbers, dots, dashes or underscores.' ); }
	if ( username_exists( $username ) || mags_erp_get_student_by_admission( $username ) ) { return new WP_Error( 'mags_erp_teacher', 'This username is already in use. Choose another one.' ); }

	$password = wp_generate_password( 10, false );
	$user_id  = wp_insert_user( array( 'user_login' => $username, 'user_pass' => $password, 'display_name' => $name, 'first_name' => $name, 'role' => 'mags_teacher', 'show_admin_bar_front' => 'false' ) );
	return is_wp_error( $user_id ) ? new WP_Error( 'mags_erp_teacher', 'The teacher could not be saved. Please try again.' ) : array( (int) $user_id, $password );
}

/* ---------- Students ---------- */

function mags_erp_get_student( $student_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE id = %d', $student_id ) );
}

function mags_erp_get_student_by_user( $user_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE user_id = %d', $user_id ) );
}

function mags_erp_get_student_by_admission( $admission_no ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE admission_no = %s', $admission_no ) );
}

function mags_erp_count_students() {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'students' ) );
}

/** Students of one class in roll-number order. */
function mags_erp_class_students( $class_id ) {
	global $wpdb;
	$students = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE class_id = %d', $class_id ) );
	return mags_erp_natural_sort( (array) $students, array( 'roll_no', 'full_name' ) );
}

/** Office student list: optional class filter and name / admission number search, one page at a time. */
function mags_erp_query_students( $class_id, $search, $page, $per_page = 50 ) {
	global $wpdb;
	$table  = mags_erp_table( 'students' );
	$where  = '1=1';
	$values = array();
	if ( $class_id ) { $where .= ' AND class_id = %d'; $values[] = $class_id; }
	if ( '' !== $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$where   .= ' AND ( full_name LIKE %s OR admission_no LIKE %s )';
		$values[] = $like;
		$values[] = $like;
	}

	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
	$total     = (int) $wpdb->get_var( $values ? $wpdb->prepare( $count_sql, $values ) : $count_sql );
	$values[]  = $per_page;
	$values[]  = max( 0, ( $page - 1 ) * $per_page );
	$rows      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY class_id, full_name LIMIT %d OFFSET %d", $values ) );
	return array( (array) $rows, $total );
}

function mags_erp_validate_student_fields( $name, $class_id, $roll_no ) {
	if ( '' === $name ) { return new WP_Error( 'mags_erp_student', 'Enter the student’s name.' ); }
	if ( mb_strlen( $name ) > 120 ) { return new WP_Error( 'mags_erp_student', 'The student’s name is too long.' ); }
	if ( ! $class_id || ! mags_erp_get_class( $class_id ) ) { return new WP_Error( 'mags_erp_student', 'Choose the student’s class.' ); }
	if ( mb_strlen( $roll_no ) > 10 ) { return new WP_Error( 'mags_erp_student', 'The roll number is too long.' ); }
	return true;
}

/**
 * Create a student with a portal login. The admission number is the login ID
 * that the student and parents type. Returns array( student_id, password ) or a WP_Error.
 */
function mags_erp_create_student( $admission_no, $name, $class_id, $roll_no ) {
	global $wpdb;
	if ( ! preg_match( '~^[A-Za-z0-9][A-Za-z0-9/._-]{0,29}$~', $admission_no ) ) { return new WP_Error( 'mags_erp_student', 'The admission number can use letters, numbers and / . - _ only (no spaces), up to 30 characters.' ); }
	$valid = mags_erp_validate_student_fields( $name, $class_id, $roll_no );
	if ( is_wp_error( $valid ) ) { return $valid; }
	if ( mags_erp_get_student_by_admission( $admission_no ) ) { return new WP_Error( 'mags_erp_student', 'A student with this admission number already exists.' ); }

	// The WordPress username is internal; "stu-" keeps it apart from staff usernames.
	$base  = 'stu-' . strtolower( preg_replace( '~[^A-Za-z0-9._-]+~', '-', $admission_no ) );
	$login = $base;
	for ( $suffix = 2; username_exists( $login ); $suffix++ ) { $login = $base . '-' . $suffix; }

	$password = wp_generate_password( 10, false );
	$user_id  = wp_insert_user( array( 'user_login' => $login, 'user_pass' => $password, 'display_name' => $name, 'first_name' => $name, 'role' => 'mags_student', 'show_admin_bar_front' => 'false' ) );
	if ( is_wp_error( $user_id ) ) { return new WP_Error( 'mags_erp_student', 'The student could not be saved. Please try again.' ); }

	$saved = $wpdb->insert(
		mags_erp_table( 'students' ),
		array( 'user_id' => $user_id, 'admission_no' => $admission_no, 'full_name' => $name, 'class_id' => $class_id, 'roll_no' => $roll_no, 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%s', '%s', '%d', '%s', '%s' )
	);
	if ( ! $saved ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
		return new WP_Error( 'mags_erp_student', 'The student could not be saved. Please try again.' );
	}
	return array( (int) $wpdb->insert_id, $password );
}

function mags_erp_update_student( $student_id, $name, $class_id, $roll_no ) {
	global $wpdb;
	$student = mags_erp_get_student( $student_id );
	if ( ! $student ) { return new WP_Error( 'mags_erp_student', 'That student no longer exists.' ); }
	$valid = mags_erp_validate_student_fields( $name, $class_id, $roll_no );
	if ( is_wp_error( $valid ) ) { return $valid; }

	$wpdb->update( mags_erp_table( 'students' ), array( 'full_name' => $name, 'class_id' => $class_id, 'roll_no' => $roll_no ), array( 'id' => $student_id ), array( '%s', '%d', '%s' ), array( '%d' ) );
	if ( mags_erp_is_managed_user( $student->user_id, 'mags_student' ) ) { wp_update_user( array( 'ID' => (int) $student->user_id, 'display_name' => $name, 'first_name' => $name ) ); }
	return true;
}

/** Remove a student, their attendance, their homework answers and their login. */
function mags_erp_delete_student( $student_id ) {
	global $wpdb;
	$student = mags_erp_get_student( $student_id );
	if ( ! $student ) { return; }
	mags_erp_delete_student_submissions( (int) $student_id );
	$wpdb->delete( mags_erp_table( 'attendance' ), array( 'student_id' => $student_id ), array( '%d' ) );
	$wpdb->delete( mags_erp_table( 'students' ), array( 'id' => $student_id ), array( '%d' ) );
	if ( mags_erp_is_managed_user( $student->user_id, 'mags_student' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( (int) $student->user_id );
	}
}

/** Keep portal records tidy when an account is deleted from the WordPress Users screen. */
function mags_erp_cleanup_deleted_user( $user_id ) {
	global $wpdb;
	$wpdb->delete( mags_erp_table( 'class_teachers' ), array( 'teacher_id' => $user_id ), array( '%d' ) );
	$student = mags_erp_get_student_by_user( $user_id );
	if ( $student ) {
		mags_erp_delete_student_submissions( (int) $student->id );
		$wpdb->delete( mags_erp_table( 'attendance' ), array( 'student_id' => $student->id ), array( '%d' ) );
		$wpdb->delete( mags_erp_table( 'students' ), array( 'id' => $student->id ), array( '%d' ) );
	}
}
add_action( 'deleted_user', 'mags_erp_cleanup_deleted_user' );

/* ---------- Attendance ---------- */

/** student_id => status for the given students on one date. */
function mags_erp_attendance_for_students( $student_ids, $date ) {
	global $wpdb;
	$marks = array();
	if ( ! $student_ids ) { return $marks; }
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT student_id, status FROM ' . mags_erp_table( 'attendance' ) . ' WHERE att_date = %s AND student_id IN (' . mags_erp_id_list( $student_ids ) . ')', $date ) );
	foreach ( (array) $rows as $row ) { $marks[ (int) $row->student_id ] = $row->status; }
	return $marks;
}

/**
 * Save one day's attendance for a class. Only students who are in the class
 * now are written; anything else in $marks is ignored. Returns status => count.
 */
function mags_erp_save_attendance( $class_id, $date, $marks, $marked_by ) {
	global $wpdb;
	$table    = mags_erp_table( 'attendance' );
	$students = mags_erp_class_students( $class_id );
	$existing = mags_erp_attendance_for_students( wp_list_pluck( $students, 'id' ), $date );
	$counts   = array_fill_keys( array_keys( mags_erp_statuses() ), 0 );
	$now      = current_time( 'mysql' );

	foreach ( $students as $student ) {
		$student_id = (int) $student->id;
		$status     = isset( $marks[ $student_id ] ) && is_string( $marks[ $student_id ] ) ? $marks[ $student_id ] : '';
		if ( ! isset( $counts[ $status ] ) ) { continue; }
		$counts[ $status ]++;

		if ( ! isset( $existing[ $student_id ] ) ) {
			$wpdb->insert( $table, array( 'student_id' => $student_id, 'class_id' => $class_id, 'att_date' => $date, 'status' => $status, 'marked_by' => $marked_by, 'marked_at' => $now ), array( '%d', '%d', '%s', '%s', '%d', '%s' ) );
		} elseif ( $existing[ $student_id ] !== $status ) {
			$wpdb->update( $table, array( 'class_id' => $class_id, 'status' => $status, 'marked_by' => $marked_by, 'marked_at' => $now ), array( 'student_id' => $student_id, 'att_date' => $date ), array( '%d', '%s', '%d', '%s' ), array( '%d', '%s' ) );
		}
	}
	return $counts;
}

/** class_id => status => count for one date, for the teacher's class cards. */
function mags_erp_day_summary( $class_ids, $date ) {
	global $wpdb;
	$summary = array();
	if ( ! $class_ids ) { return $summary; }
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT s.class_id, a.status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' a INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = a.student_id WHERE a.att_date = %s AND s.class_id IN (' . mags_erp_id_list( $class_ids ) . ') GROUP BY s.class_id, a.status', $date ) );
	foreach ( (array) $rows as $row ) { $summary[ (int) $row->class_id ][ $row->status ] = (int) $row->total; }
	return $summary;
}

/** status => number of days for one student between two dates (inclusive). */
function mags_erp_student_summary( $student_id, $from, $to ) {
	global $wpdb;
	$counts = array_fill_keys( array_keys( mags_erp_statuses() ), 0 );
	$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' WHERE student_id = %d AND att_date BETWEEN %s AND %s GROUP BY status', $student_id, $from, $to ) );
	foreach ( (array) $rows as $row ) { if ( isset( $counts[ $row->status ] ) ) { $counts[ $row->status ] = (int) $row->total; } }
	return $counts;
}

/** date => status for one student between two dates (inclusive). */
function mags_erp_student_days( $student_id, $from, $to ) {
	global $wpdb;
	$days = array();
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT att_date, status FROM ' . mags_erp_table( 'attendance' ) . ' WHERE student_id = %d AND att_date BETWEEN %s AND %s', $student_id, $from, $to ) );
	foreach ( (array) $rows as $row ) { $days[ substr( $row->att_date, 0, 10 ) ] = $row->status; }
	return $days;
}
