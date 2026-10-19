<?php
/**
 * Database access for classes, teachers, students and attendance.
 * Removing a class, teacher or student hides it; nothing here deletes school records.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Comma-separated list of whole numbers that is safe to place inside IN (...). */
function mags_erp_id_list( $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	return $ids ? implode( ',', $ids ) : '0';
}

/* ---------- Classes ---------- */

/** Classes in use, each with its number of students. Removed classes and removed students are left out. */
function mags_erp_get_classes() {
	global $wpdb;
	$classes = $wpdb->get_results( 'SELECT c.*, COUNT(s.id) AS student_count FROM ' . mags_erp_table( 'classes' ) . ' c LEFT JOIN ' . mags_erp_table( 'students' ) . ' s ON s.class_id = c.id AND s.hidden_at IS NULL WHERE c.hidden_at IS NULL GROUP BY c.id' );
	return mags_erp_natural_sort( (array) $classes, array( 'name', 'section' ) );
}

function mags_erp_get_removed_classes() {
	global $wpdb;
	return mags_erp_natural_sort( (array) $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'classes' ) . ' WHERE hidden_at IS NOT NULL' ), array( 'name', 'section' ) );
}

/** One class. A removed class is returned only when $with_removed is true (for labels on old records). */
function mags_erp_get_class( $class_id, $with_removed = false ) {
	global $wpdb;
	$class = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'classes' ) . ' WHERE id = %d', $class_id ) );
	return $class && ( $with_removed || ! $class->hidden_at ) ? $class : null;
}

/** id => label of every class, removed ones included, for lists that show old records. */
function mags_erp_all_class_labels() {
	global $wpdb;
	$labels = array();
	foreach ( (array) $wpdb->get_results( 'SELECT * FROM ' . mags_erp_table( 'classes' ) ) as $class ) { $labels[ (int) $class->id ] = mags_erp_class_label( $class ); }
	return $labels;
}

/** Add a class ($class_id 0) or rename one. Returns the class id or a WP_Error. */
function mags_erp_save_class( $class_id, $name, $section ) {
	global $wpdb;
	$table = mags_erp_table( 'classes' );
	if ( '' === $name ) { return new WP_Error( 'mags_erp_class', 'Enter the class name.' ); }
	if ( mb_strlen( $name ) > 60 || mb_strlen( $section ) > 20 ) { return new WP_Error( 'mags_erp_class', 'The class name or section is too long.' ); }

	$duplicate = $wpdb->get_row( $wpdb->prepare( "SELECT id, hidden_at FROM {$table} WHERE name = %s AND section = %s AND id <> %d", $name, $section, $class_id ) );
	if ( $duplicate ) { return new WP_Error( 'mags_erp_class', $duplicate->hidden_at ? 'A removed class has this name and section. Restore it from the removed classes below.' : 'This class and section already exist.' ); }

	if ( $class_id ) {
		if ( ! mags_erp_get_class( $class_id ) ) { return new WP_Error( 'mags_erp_class', 'That class no longer exists.' ); }
		$wpdb->update( $table, array( 'name' => $name, 'section' => $section ), array( 'id' => $class_id ), array( '%s', '%s' ), array( '%d' ) );
		return (int) $class_id;
	}
	$saved = $wpdb->insert( $table, array( 'name' => $name, 'section' => $section, 'created_at' => current_time( 'mysql' ) ), array( '%s', '%s', '%s' ) );
	return $saved ? (int) $wpdb->insert_id : new WP_Error( 'mags_erp_class', 'The class could not be saved. Please try again.' );
}

/**
 * A class can be removed only when no student is in it, so no child loses their class by mistake.
 * The class and its homework, notes, timetable and marks are kept and come back when it is restored.
 */
function mags_erp_remove_class( $class_id ) {
	global $wpdb;
	if ( ! mags_erp_get_class( $class_id ) ) { return new WP_Error( 'mags_erp_class', 'That class no longer exists.' ); }
	$students = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'students' ) . ' WHERE class_id = %d AND hidden_at IS NULL', $class_id ) );
	if ( $students ) { return new WP_Error( 'mags_erp_class', 'Move or remove the students of this class first.' ); }
	mags_erp_hide( 'classes', $class_id );
	return true;
}

/**
 * Restore a removed class. A teacher of its timetable may have been given the
 * same period in another class meanwhile; those periods keep their subject and
 * lose the teacher. Returns how many periods that was, or a WP_Error.
 */
function mags_erp_restore_class( $class_id ) {
	global $wpdb;
	if ( ! mags_erp_unhide( 'classes', $class_id ) ) { return new WP_Error( 'mags_erp_class', 'That class is not in the removed list.' ); }
	$t     = mags_erp_table( 'timetable' );
	$busy  = (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT a.id FROM {$t} a INNER JOIN {$t} b ON b.dow = a.dow AND b.period_id = a.period_id AND b.teacher_id = a.teacher_id AND b.class_id <> a.class_id INNER JOIN " . mags_erp_table( 'classes' ) . ' c ON c.id = b.class_id WHERE a.class_id = %d AND a.teacher_id > 0 AND c.hidden_at IS NULL', $class_id ) );
	foreach ( $busy as $slot_id ) { $wpdb->update( $t, array( 'teacher_id' => 0 ), array( 'id' => (int) $slot_id ), array( '%d' ), array( '%d' ) ); }
	return count( $busy );
}

/**
 * The class teacher answers the leave requests of the class. It must be one of
 * the teachers the office has given this class to; 0 means "no class teacher".
 */
function mags_erp_set_class_teacher( $class_id, $teacher_id ) {
	global $wpdb;
	if ( $teacher_id && ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) { return new WP_Error( 'mags_erp_class', 'Give this class to the teacher first (Teachers → Edit), then choose them as class teacher.' ); }
	$wpdb->update( mags_erp_table( 'classes' ), array( 'class_teacher_id' => (int) $teacher_id ), array( 'id' => (int) $class_id ), array( '%d' ), array( '%d' ) );
	return true;
}

/* ---------- Teachers ---------- */

/** Teacher logins in use, or the removed ones when $removed is true. */
function mags_erp_get_teachers( $removed = false ) {
	return get_users( array( 'role' => 'mags_teacher', 'orderby' => 'display_name', 'order' => 'ASC', 'meta_query' => array( array( 'key' => 'mags_erp_removed', 'compare' => $removed ? 'EXISTS' : 'NOT EXISTS' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
}

/** class_id => ( teacher_id => display name ), for the office screens and the pick lists. */
function mags_erp_class_teacher_names() {
	global $wpdb;
	$names = array();
	$rows  = $wpdb->get_results( 'SELECT ct.class_id, ct.teacher_id, u.display_name FROM ' . mags_erp_table( 'class_teachers' ) . " ct INNER JOIN {$wpdb->users} u ON u.ID = ct.teacher_id ORDER BY u.display_name" );
	foreach ( (array) $rows as $row ) { $names[ (int) $row->class_id ][ (int) $row->teacher_id ] = $row->display_name; }
	return $names;
}

/** teacher_id => display name of the teachers of one class. */
function mags_erp_class_teachers( $class_id ) {
	$names = mags_erp_class_teacher_names();
	return $names[ (int) $class_id ] ?? array();
}

function mags_erp_teacher_class_ids( $teacher_id ) {
	global $wpdb;
	return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT ct.class_id FROM ' . mags_erp_table( 'class_teachers' ) . ' ct INNER JOIN ' . mags_erp_table( 'classes' ) . ' c ON c.id = ct.class_id WHERE ct.teacher_id = %d AND c.hidden_at IS NULL', $teacher_id ) ) );
}

/** Classes a teacher may mark, each with its student count. */
function mags_erp_teacher_classes( $teacher_id ) {
	$own = mags_erp_teacher_class_ids( $teacher_id );
	return array_values( array_filter( mags_erp_get_classes(), static function ( $class ) use ( $own ) { return in_array( (int) $class->id, $own, true ); } ) );
}

function mags_erp_teacher_has_class( $teacher_id, $class_id ) {
	return in_array( (int) $class_id, mags_erp_teacher_class_ids( $teacher_id ), true );
}

/** Classes where this teacher is the class teacher. */
function mags_erp_class_teacher_class_ids( $teacher_id ) {
	global $wpdb;
	$own = (array) $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'classes' ) . ' WHERE class_teacher_id = %d AND hidden_at IS NULL', $teacher_id ) );
	return array_values( array_intersect( array_map( 'intval', $own ), mags_erp_teacher_class_ids( $teacher_id ) ) );
}

/**
 * Give a teacher exactly these classes. A class that is taken away also stops
 * naming them as class teacher and in its timetable. Returns the class ids taken away.
 */
function mags_erp_set_teacher_classes( $teacher_id, $class_ids ) {
	global $wpdb;
	$table   = mags_erp_table( 'class_teachers' );
	$valid   = array_map( 'intval', wp_list_pluck( mags_erp_get_classes(), 'id' ) );
	$wanted  = array_values( array_intersect( array_unique( array_map( 'absint', (array) $class_ids ) ), $valid ) );
	$current = mags_erp_teacher_class_ids( $teacher_id );
	$dropped = array_values( array_diff( $current, $wanted ) );

	foreach ( $dropped as $class_id ) {
		$wpdb->delete( $table, array( 'class_id' => $class_id, 'teacher_id' => $teacher_id ), array( '%d', '%d' ) );
		$wpdb->update( mags_erp_table( 'classes' ), array( 'class_teacher_id' => 0 ), array( 'id' => $class_id, 'class_teacher_id' => $teacher_id ), array( '%d' ), array( '%d', '%d' ) );
		$wpdb->update( mags_erp_table( 'timetable' ), array( 'teacher_id' => 0 ), array( 'class_id' => $class_id, 'teacher_id' => $teacher_id ), array( '%d' ), array( '%d', '%d' ) );
	}
	foreach ( array_diff( $wanted, $current ) as $class_id ) {
		$wpdb->insert( $table, array( 'class_id' => $class_id, 'teacher_id' => $teacher_id ), array( '%d', '%d' ) );
	}
	return $dropped;
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

/**
 * Take a teacher out of every class, removed classes included: the class links,
 * the class-teacher choice and the timetable periods. Returns the class ids they had.
 */
function mags_erp_free_teacher( $teacher_id ) {
	global $wpdb;
	$class_ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT class_id FROM ' . mags_erp_table( 'class_teachers' ) . ' WHERE teacher_id = %d', $teacher_id ) ) );
	$wpdb->delete( mags_erp_table( 'class_teachers' ), array( 'teacher_id' => $teacher_id ), array( '%d' ) );
	$wpdb->update( mags_erp_table( 'classes' ), array( 'class_teacher_id' => 0 ), array( 'class_teacher_id' => $teacher_id ), array( '%d' ), array( '%d' ) );
	$wpdb->update( mags_erp_table( 'timetable' ), array( 'teacher_id' => 0 ), array( 'teacher_id' => $teacher_id ), array( '%d' ), array( '%d' ) );
	return $class_ids;
}

/**
 * Remove a teacher: the login is switched off and their classes are freed, but
 * the account, name, homework, notes, marks and replies are all kept. Homework
 * belongs to the class, so the other teachers of the class carry on with it.
 * Returns the classes that are left without any teacher.
 */
function mags_erp_remove_teacher( $teacher_id ) {
	$active = mags_erp_teacher_class_ids( $teacher_id );
	update_user_meta( $teacher_id, 'mags_erp_removed_classes', mags_erp_free_teacher( $teacher_id ) );
	mags_erp_set_user_removed( $teacher_id, true );

	$names = mags_erp_class_teacher_names();
	return array_values( array_filter( $active, static function ( $class_id ) use ( $names ) { return empty( $names[ $class_id ] ); } ) );
}

/** Switch a removed teacher's login on again and give back the classes they had (those that still exist). */
function mags_erp_restore_teacher( $teacher_id ) {
	global $wpdb;
	mags_erp_set_user_removed( $teacher_id, false );
	foreach ( array_unique( array_map( 'absint', (array) get_user_meta( $teacher_id, 'mags_erp_removed_classes', true ) ) ) as $class_id ) {
		if ( $class_id && mags_erp_get_class( $class_id, true ) && ! $wpdb->get_var( $wpdb->prepare( 'SELECT class_id FROM ' . mags_erp_table( 'class_teachers' ) . ' WHERE class_id = %d AND teacher_id = %d', $class_id, $teacher_id ) ) ) {
			$wpdb->insert( mags_erp_table( 'class_teachers' ), array( 'class_id' => $class_id, 'teacher_id' => $teacher_id ), array( '%d', '%d' ) );
		}
	}
	delete_user_meta( $teacher_id, 'mags_erp_removed_classes' );
}

/* ---------- Students ---------- */

/** One student record, also when the student was removed (check ->hidden_at). */
function mags_erp_get_student( $student_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE id = %d', $student_id ) );
}

/** The student record of a signed-in student. A removed student has none. */
function mags_erp_get_student_by_user( $user_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE user_id = %d AND hidden_at IS NULL', $user_id ) );
}

/** Admission numbers stay taken after a student is removed, so this also returns removed students. */
function mags_erp_get_student_by_admission( $admission_no ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE admission_no = %s', $admission_no ) );
}

function mags_erp_count_students() {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'students' ) . ' WHERE hidden_at IS NULL' );
}

/** Students of one class in roll-number order. */
function mags_erp_class_students( $class_id ) {
	global $wpdb;
	$students = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'students' ) . ' WHERE class_id = %d AND hidden_at IS NULL', $class_id ) );
	return mags_erp_natural_sort( (array) $students, array( 'roll_no', 'full_name' ) );
}

/** Office student list: optional class filter and name / admission number search, one page at a time. */
function mags_erp_query_students( $class_id, $search, $page, $per_page = 50, $removed = false ) {
	global $wpdb;
	$table  = mags_erp_table( 'students' );
	$where  = $removed ? 'hidden_at IS NOT NULL' : 'hidden_at IS NULL';
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

function mags_erp_valid_admission_no( $admission_no ) {
	return is_string( $admission_no ) && (bool) preg_match( '~^[A-Za-z0-9][A-Za-z0-9/._-]{0,29}$~', $admission_no );
}

/**
 * Create the WordPress login behind a student. The username is internal
 * ("stu-" keeps it apart from staff usernames); the student types the admission
 * number. Returns array( user_id, password ) or a WP_Error.
 */
function mags_erp_create_student_login( $admission_no, $name ) {
	$base  = 'stu-' . strtolower( preg_replace( '~[^A-Za-z0-9._-]+~', '-', $admission_no ) );
	$login = $base;
	for ( $suffix = 2; username_exists( $login ); $suffix++ ) { $login = $base . '-' . $suffix; }

	$password = wp_generate_password( 10, false );
	$user_id  = wp_insert_user( array( 'user_login' => $login, 'user_pass' => $password, 'display_name' => $name, 'first_name' => $name, 'role' => 'mags_student', 'show_admin_bar_front' => 'false' ) );
	return is_wp_error( $user_id ) ? new WP_Error( 'mags_erp_student', 'The student could not be saved. Please try again.' ) : array( (int) $user_id, $password );
}

/**
 * Create a student with a portal login. The admission number is the login ID
 * that the student and parents type. Returns array( student_id, password ) or a WP_Error.
 */
function mags_erp_create_student( $admission_no, $name, $class_id, $roll_no ) {
	global $wpdb;
	if ( ! mags_erp_valid_admission_no( $admission_no ) ) { return new WP_Error( 'mags_erp_student', 'The admission number can use letters, numbers and / . - _ only (no spaces), up to 30 characters.' ); }
	$valid = mags_erp_validate_student_fields( $name, $class_id, $roll_no );
	if ( is_wp_error( $valid ) ) { return $valid; }
	$existing = mags_erp_get_student_by_admission( $admission_no );
	if ( $existing ) { return new WP_Error( 'mags_erp_student', $existing->hidden_at ? 'A removed student has this admission number. Restore that student from the removed students list.' : 'A student with this admission number already exists.' ); }

	$login = mags_erp_create_student_login( $admission_no, $name );
	if ( is_wp_error( $login ) ) { return $login; }
	list( $user_id, $password ) = $login;

	$saved = $wpdb->insert(
		mags_erp_table( 'students' ),
		array( 'user_id' => $user_id, 'admission_no' => $admission_no, 'full_name' => $name, 'class_id' => $class_id, 'roll_no' => $roll_no, 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%s', '%s', '%d', '%s', '%s' )
	);
	if ( ! $saved ) {
		// The login was created a moment ago for this record only; without the record it has no use.
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
		return new WP_Error( 'mags_erp_student', 'The student could not be saved. Please try again.' );
	}
	return array( (int) $wpdb->insert_id, $password );
}

function mags_erp_update_student( $student_id, $name, $class_id, $roll_no ) {
	global $wpdb;
	$student = mags_erp_get_student( $student_id );
	if ( ! $student || $student->hidden_at ) { return new WP_Error( 'mags_erp_student', 'That student no longer exists.' ); }
	$valid = mags_erp_validate_student_fields( $name, $class_id, $roll_no );
	if ( is_wp_error( $valid ) ) { return $valid; }

	$wpdb->update( mags_erp_table( 'students' ), array( 'full_name' => $name, 'class_id' => $class_id, 'roll_no' => $roll_no ), array( 'id' => $student_id ), array( '%s', '%d', '%s' ), array( '%d' ) );
	if ( mags_erp_is_managed_user( $student->user_id, 'mags_student' ) ) { wp_update_user( array( 'ID' => (int) $student->user_id, 'display_name' => $name, 'first_name' => $name ) ); }
	return true;
}

/**
 * Remove a student: the login is switched off and the student leaves the class
 * lists, but the record, attendance, answers, marks, fees and questions are kept.
 */
function mags_erp_remove_student( $student_id ) {
	$student = mags_erp_get_student( $student_id );
	if ( ! $student || $student->hidden_at ) { return false; }
	mags_erp_hide( 'students', (int) $student->id );
	if ( mags_erp_is_managed_user( $student->user_id, 'mags_student' ) ) { mags_erp_set_user_removed( (int) $student->user_id, true ); }
	return true;
}

/**
 * Put a removed student back into their class. When the login itself was
 * deleted from the WordPress Users screen a new one is made. Returns the new
 * password in that case, '' otherwise, or a WP_Error.
 */
function mags_erp_restore_student( $student_id ) {
	global $wpdb;
	$student = mags_erp_get_student( $student_id );
	if ( ! $student || ! $student->hidden_at ) { return new WP_Error( 'mags_erp_student', 'That student is not in the removed list.' ); }
	if ( ! mags_erp_get_class( (int) $student->class_id ) ) { return new WP_Error( 'mags_erp_student', 'The class of this student was removed. Restore the class first.' ); }

	$password = '';
	if ( ! mags_erp_is_managed_user( $student->user_id, 'mags_student' ) ) {
		$login = mags_erp_create_student_login( $student->admission_no, $student->full_name );
		if ( is_wp_error( $login ) ) { return $login; }
		$wpdb->update( mags_erp_table( 'students' ), array( 'user_id' => $login[0] ), array( 'id' => (int) $student->id ), array( '%d' ), array( '%d' ) );
		$password = $login[1];
	} else {
		mags_erp_set_user_removed( (int) $student->user_id, false );
	}
	mags_erp_unhide( 'students', (int) $student->id );
	return $password;
}

/** Names of deleted staff logins, so old homework and replies still say who wrote them. */
function mags_erp_remember_deleted_user( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user || ! array_intersect( array( 'mags_teacher', 'mags_office' ), (array) $user->roles ) ) { return; }
	$names             = (array) get_option( 'mags_erp_former_staff', array() );
	$names[ $user_id ] = $user->display_name;
	update_option( 'mags_erp_former_staff', $names, false );
}
add_action( 'delete_user', 'mags_erp_remember_deleted_user' );

/**
 * When an account is deleted from the WordPress Users screen the portal keeps
 * its records: a teacher's classes are freed and a student is moved to the
 * removed list (and gets a new login if the office restores them).
 */
function mags_erp_cleanup_deleted_user( $user_id ) {
	global $wpdb;
	mags_erp_free_teacher( $user_id );
	$student = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'students' ) . ' WHERE user_id = %d', $user_id ) );
	if ( $student ) { mags_erp_hide( 'students', (int) $student->id ); }
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

/** class_id => status => count for one date, for the teacher's class cards. Empty on a holiday. */
function mags_erp_day_summary( $class_ids, $date ) {
	global $wpdb;
	$summary = array();
	if ( ! $class_ids ) { return $summary; }
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT s.class_id, a.status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' a INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = a.student_id WHERE a.att_date = %s AND s.hidden_at IS NULL AND ' . mags_erp_not_holiday_sql( 'a.att_date' ) . ' AND s.class_id IN (' . mags_erp_id_list( $class_ids ) . ') GROUP BY s.class_id, a.status', $date ) );
	foreach ( (array) $rows as $row ) { $summary[ (int) $row->class_id ][ $row->status ] = (int) $row->total; }
	return $summary;
}

/** status => number of days for one student between two dates (inclusive). Holidays are not counted. */
function mags_erp_student_summary( $student_id, $from, $to ) {
	global $wpdb;
	$counts = array_fill_keys( array_keys( mags_erp_statuses() ), 0 );
	$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM ' . mags_erp_table( 'attendance' ) . ' WHERE student_id = %d AND att_date BETWEEN %s AND %s AND ' . mags_erp_not_holiday_sql( 'att_date' ) . ' GROUP BY status', $student_id, $from, $to ) );
	foreach ( (array) $rows as $row ) { if ( isset( $counts[ $row->status ] ) ) { $counts[ $row->status ] = (int) $row->total; } }
	return $counts;
}

/** date => status for one student between two dates (inclusive). Holidays are left out. */
function mags_erp_student_days( $student_id, $from, $to ) {
	global $wpdb;
	$days = array();
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT att_date, status FROM ' . mags_erp_table( 'attendance' ) . ' WHERE student_id = %d AND att_date BETWEEN %s AND %s AND ' . mags_erp_not_holiday_sql( 'att_date' ), $student_id, $from, $to ) );
	foreach ( (array) $rows as $row ) { $days[ substr( $row->att_date, 0, 10 ) ] = $row->status; }
	return $days;
}
