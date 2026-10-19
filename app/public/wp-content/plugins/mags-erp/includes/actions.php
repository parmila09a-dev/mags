<?php
/** Form handlers. Every handler checks the role first, then saves, then returns where to go next. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** action => array( roles allowed, handler ). */
function mags_erp_actions() {
	$everyone = array( 'office', 'teacher', 'student' );
	return array(
		'logout'          => array( $everyone, 'mags_erp_action_logout' ),
		'change_password' => array( $everyone, 'mags_erp_action_change_password' ),
		'save_class'      => array( array( 'office' ), 'mags_erp_action_save_class' ),
		'delete_class'    => array( array( 'office' ), 'mags_erp_action_delete_class' ),
		'save_teacher'    => array( array( 'office' ), 'mags_erp_action_save_teacher' ),
		'delete_teacher'  => array( array( 'office' ), 'mags_erp_action_delete_teacher' ),
		'save_student'    => array( array( 'office' ), 'mags_erp_action_save_student' ),
		'delete_student'  => array( array( 'office' ), 'mags_erp_action_delete_student' ),
		'reset_password'  => array( array( 'office' ), 'mags_erp_action_reset_password' ),
		'save_attendance' => array( array( 'teacher' ), 'mags_erp_action_save_attendance' ),
		'save_homework'   => array( array( 'teacher' ), 'mags_erp_action_save_homework' ),
		'delete_homework' => array( array( 'teacher' ), 'mags_erp_action_delete_homework' ),
		'grade_homework'  => array( array( 'teacher' ), 'mags_erp_action_grade_homework' ),
		'submit_homework' => array( array( 'student' ), 'mags_erp_action_submit_homework' ),
		'save_note'       => array( array( 'teacher' ), 'mags_erp_action_save_note' ),
		'delete_note'     => array( array( 'teacher' ), 'mags_erp_action_delete_note' ),
	);
}

/** Form field names avoid WordPress query variables such as "name" and "error". */
function mags_erp_post_text( $key ) {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

function mags_erp_post_textarea( $key, $limit = 5000 ) {
	$text = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
}

function mags_erp_post_int( $key ) {
	return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
}

/** Run the submitted portal form, then redirect so a refresh never repeats it. */
function mags_erp_handle_post( $route ) {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
	$action = mags_erp_post_text( 'mags_erp_action' );
	if ( 'login' === $action ) { mags_erp_handle_login(); }

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( mags_erp_url( 'login', array( 'msg' => 'expired' ) ) );
		exit;
	}
	if ( mags_erp_request_too_large() ) {
		mags_erp_flash( 'error', sprintf( 'The file is too large. The limit is %s.', size_format( mags_erp_max_file_size() ) ) );
		wp_safe_redirect( mags_erp_url( $route ) );
		exit;
	}

	$actions = mags_erp_actions();
	$nonce   = mags_erp_post_text( '_mags_erp_nonce' );
	$back    = mags_erp_url( $route );
	if ( ! isset( $actions[ $action ] ) || ! wp_verify_nonce( $nonce, 'mags_erp_' . $action ) ) {
		mags_erp_flash( 'error', 'This page was open for too long. Please try again.' );
	} elseif ( ! in_array( mags_erp_user_role(), $actions[ $action ][0], true ) ) {
		mags_erp_flash( 'error', 'Your account is not allowed to do that.' );
		$back = mags_erp_url();
	} else {
		$back = call_user_func( $actions[ $action ][1] ) ?: $back;
	}

	wp_safe_redirect( $back );
	exit;
}

function mags_erp_action_logout() {
	wp_logout();
	return mags_erp_url( 'login' );
}

function mags_erp_action_change_password() {
	$user    = wp_get_current_user();
	// Like WordPress itself, passwords are used exactly as submitted.
	$current = isset( $_POST['current_password'] ) && is_string( $_POST['current_password'] ) ? $_POST['current_password'] : ''; // phpcs:ignore
	$new     = isset( $_POST['new_password'] ) && is_string( $_POST['new_password'] ) ? $_POST['new_password'] : ''; // phpcs:ignore
	$again   = isset( $_POST['new_password_again'] ) && is_string( $_POST['new_password_again'] ) ? $_POST['new_password_again'] : ''; // phpcs:ignore
	$back    = mags_erp_url( 'account' );

	if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
		mags_erp_flash( 'error', 'The current password is not correct.' );
	} elseif ( strlen( $new ) < 8 ) {
		mags_erp_flash( 'error', 'The new password must have at least 8 characters.' );
	} elseif ( $new !== $again ) {
		mags_erp_flash( 'error', 'The two new passwords do not match.' );
	} elseif ( $new === $current ) {
		mags_erp_flash( 'error', 'Choose a new password that is different from the current one.' );
	} else {
		wp_set_password( $new, $user->ID );
		// Changing the password signs every device out; keep this one signed in.
		wp_set_auth_cookie( $user->ID );
		mags_erp_flash( 'success', 'Your password has been changed.' );
	}
	return $back;
}

/* ---------- Office: classes ---------- */

function mags_erp_action_save_class() {
	$class_id = mags_erp_post_int( 'class_id' );
	$saved    = mags_erp_save_class( $class_id, mags_erp_post_text( 'erp_name' ), mags_erp_post_text( 'section' ) );
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() );
		return mags_erp_url( 'classes', array( 'edit' => $class_id ?: '' ) );
	}
	mags_erp_flash( 'success', $class_id ? 'Class updated.' : 'Class added.' );
	return mags_erp_url( 'classes' );
}

function mags_erp_action_delete_class() {
	$deleted = mags_erp_delete_class( mags_erp_post_int( 'class_id' ) );
	mags_erp_flash( is_wp_error( $deleted ) ? 'error' : 'success', is_wp_error( $deleted ) ? $deleted->get_error_message() : 'Class removed.' );
	return mags_erp_url( 'classes' );
}

/* ---------- Office: teachers ---------- */

function mags_erp_action_save_teacher() {
	$teacher_id = mags_erp_post_int( 'teacher_id' );
	$name       = mags_erp_post_text( 'erp_name' );
	$class_ids  = isset( $_POST['class_ids'] ) && is_array( $_POST['class_ids'] ) ? array_map( 'absint', $_POST['class_ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification

	if ( $teacher_id ) {
		if ( ! mags_erp_is_managed_user( $teacher_id, 'mags_teacher' ) ) {
			mags_erp_flash( 'error', 'That teacher no longer exists.' );
			return mags_erp_url( 'teachers' );
		}
		if ( '' === $name ) {
			mags_erp_flash( 'error', 'Enter the teacher’s name.' );
			return mags_erp_url( 'teachers', array( 'edit' => $teacher_id ) );
		}
		wp_update_user( array( 'ID' => $teacher_id, 'display_name' => $name, 'first_name' => $name ) );
		mags_erp_set_teacher_classes( $teacher_id, $class_ids );
		mags_erp_flash( 'success', 'Teacher updated.' );
		return mags_erp_url( 'teachers' );
	}

	$username = strtolower( mags_erp_post_text( 'username' ) );
	$created  = mags_erp_create_teacher( $name, $username );
	if ( is_wp_error( $created ) ) {
		mags_erp_flash( 'error', $created->get_error_message() );
		return mags_erp_url( 'teachers' );
	}
	mags_erp_set_teacher_classes( $created[0], $class_ids );
	mags_erp_flash( 'success', 'Teacher added. Give these login details to the teacher now. The password is not shown again.', array( 'name' => $name, 'login_as' => 'Teacher', 'login' => $username, 'password' => $created[1] ) );
	return mags_erp_url( 'teachers' );
}

function mags_erp_action_delete_teacher() {
	$teacher_id = mags_erp_post_int( 'teacher_id' );
	if ( mags_erp_is_managed_user( $teacher_id, 'mags_teacher' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $teacher_id );
		mags_erp_flash( 'success', 'Teacher removed. Attendance already marked is kept.' );
	} else {
		mags_erp_flash( 'error', 'That teacher no longer exists.' );
	}
	return mags_erp_url( 'teachers' );
}

/* ---------- Office: students ---------- */

function mags_erp_action_save_student() {
	$student_id = mags_erp_post_int( 'student_id' );
	$name       = mags_erp_post_text( 'erp_name' );
	$class_id   = mags_erp_post_int( 'class_id' );
	$roll_no    = mags_erp_post_text( 'roll_no' );
	$list       = mags_erp_url( 'students', array( 'class' => $class_id ?: '' ) );

	if ( $student_id ) {
		$saved = mags_erp_update_student( $student_id, $name, $class_id, $roll_no );
		if ( is_wp_error( $saved ) ) {
			mags_erp_flash( 'error', $saved->get_error_message() );
			return mags_erp_url( 'students', array( 'edit' => $student_id ) );
		}
		mags_erp_flash( 'success', 'Student updated.' );
		return $list;
	}

	$admission_no = mags_erp_post_text( 'admission_no' );
	$created      = mags_erp_create_student( $admission_no, $name, $class_id, $roll_no );
	if ( is_wp_error( $created ) ) {
		mags_erp_flash( 'error', $created->get_error_message() );
		return $list;
	}
	mags_erp_flash( 'success', 'Student added. Give these login details to the student or parent now. The password is not shown again.', array( 'name' => $name, 'login_as' => 'Student / Parent', 'login' => $admission_no, 'password' => $created[1] ) );
	return $list;
}

function mags_erp_action_delete_student() {
	$student = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
	if ( ! $student ) {
		mags_erp_flash( 'error', 'That student no longer exists.' );
		return mags_erp_url( 'students' );
	}
	mags_erp_delete_student( (int) $student->id );
	mags_erp_flash( 'success', 'Student removed.' );
	return mags_erp_url( 'students', array( 'class' => (int) $student->class_id ) );
}

/**
 * New password for a teacher or student. Only accounts created by the portal
 * can be reset here, never a website administrator or editor.
 */
function mags_erp_action_reset_password() {
	$kind = mags_erp_post_text( 'kind' );
	if ( 'student' === $kind ) {
		$student = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
		$user_id = $student ? (int) $student->user_id : 0;
		$back    = mags_erp_url( 'students', array( 'class' => $student ? (int) $student->class_id : '' ) );
		$details = array( 'name' => $student ? $student->full_name : '', 'login_as' => 'Student / Parent', 'login' => $student ? $student->admission_no : '' );
		$role    = 'mags_student';
	} else {
		$user_id = mags_erp_post_int( 'teacher_id' );
		$account = get_userdata( $user_id );
		$back    = mags_erp_url( 'teachers' );
		$details = array( 'name' => $account ? $account->display_name : '', 'login_as' => 'Teacher', 'login' => $account ? $account->user_login : '' );
		$role    = 'mags_teacher';
	}

	if ( ! $user_id || ! mags_erp_is_managed_user( $user_id, $role ) ) {
		mags_erp_flash( 'error', 'That account no longer exists.' );
		return $back;
	}

	$details['password'] = wp_generate_password( 10, false );
	wp_set_password( $details['password'], $user_id );
	mags_erp_flash( 'success', 'New password created. Give these login details now. The password is not shown again.', $details );
	return $back;
}

/* ---------- Teacher: attendance ---------- */

function mags_erp_action_save_attendance() {
	$teacher_id = get_current_user_id();
	$class_id   = mags_erp_post_int( 'class_id' );
	$date       = mags_erp_post_text( 'date' );
	$marks      = isset( $_POST['status'] ) && is_array( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : array(); // phpcs:ignore WordPress.Security

	if ( ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) {
		mags_erp_flash( 'error', 'This class is not assigned to you.' );
		return mags_erp_url();
	}
	if ( ! mags_erp_valid_date( $date ) || $date > mags_erp_today() ) {
		mags_erp_flash( 'error', 'Choose today or an earlier date.' );
		return mags_erp_url( 'attendance', array( 'class' => $class_id ) );
	}

	$counts = mags_erp_save_attendance( $class_id, $date, $marks, $teacher_id );
	if ( ! array_sum( $counts ) ) {
		mags_erp_flash( 'error', 'No attendance was marked.' );
	} else {
		mags_erp_flash( 'success', sprintf( 'Attendance saved for %s: %d present, %d absent, %d on leave.', mags_erp_format_date( $date ), $counts['P'], $counts['A'], $counts['L'] ) );
	}
	return mags_erp_url( 'attendance', array( 'class' => $class_id, 'date' => $date ) );
}

/* ---------- Teacher: homework ---------- */

function mags_erp_action_save_homework() {
	$teacher_id = get_current_user_id();
	$class_id   = mags_erp_post_int( 'class_id' );
	$title      = mags_erp_post_text( 'title' );
	$due_date   = mags_erp_post_text( 'due_date' );
	$max_text   = mags_erp_post_text( 'max_marks' );
	$back       = mags_erp_url( 'homework' );

	if ( ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) { mags_erp_flash( 'error', 'Choose one of your own classes.' ); return $back; }
	if ( '' === $title ) { mags_erp_flash( 'error', 'Enter a title for the homework.' ); return $back; }
	if ( mb_strlen( $title ) > 160 ) { mags_erp_flash( 'error', 'The title is too long (160 characters at most).' ); return $back; }
	if ( '' !== $due_date && ! mags_erp_valid_date( $due_date ) ) { mags_erp_flash( 'error', 'The due date is not a valid date.' ); return $back; }
	$max_marks = null;
	if ( '' !== $max_text ) {
		$max_marks = mags_erp_parse_marks( $max_text, null );
		if ( is_wp_error( $max_marks ) || $max_marks <= 0 ) { mags_erp_flash( 'error', 'Enter the maximum marks as a number above 0, or leave it empty.' ); return $back; }
	}
	$file_id = mags_erp_store_upload( 'homework_file' );
	if ( is_wp_error( $file_id ) ) { mags_erp_flash( 'error', $file_id->get_error_message() ); return $back; }

	$created = mags_erp_create_homework( $class_id, $teacher_id, $title, mags_erp_post_textarea( 'instructions' ), $due_date, $max_marks, $file_id );
	if ( is_wp_error( $created ) ) { mags_erp_delete_file( $file_id ); mags_erp_flash( 'error', $created->get_error_message() ); return $back; }
	mags_erp_flash( 'success', 'Homework given to ' . mags_erp_class_label( mags_erp_get_class( $class_id ) ) . '.' );
	return mags_erp_url( 'homework', array( 'id' => $created ) );
}

function mags_erp_action_delete_homework() {
	$homework = mags_erp_get_homework( mags_erp_post_int( 'homework_id' ) );
	if ( ! $homework || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $homework->class_id ) ) { mags_erp_flash( 'error', 'That homework is not in one of your classes.' ); return mags_erp_url( 'homework' ); }
	mags_erp_delete_homework( (int) $homework->id );
	mags_erp_flash( 'success', 'Homework removed, with all its answers.' );
	return mags_erp_url( 'homework' );
}

function mags_erp_action_grade_homework() {
	$submission = mags_erp_get_submission( mags_erp_post_int( 'submission_id' ) );
	$homework   = $submission ? mags_erp_get_homework( (int) $submission->homework_id ) : null;
	if ( ! $homework || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $homework->class_id ) ) { mags_erp_flash( 'error', 'That answer is not in one of your classes.' ); return mags_erp_url( 'homework' ); }
	$back = mags_erp_url( 'homework', array( 'id' => (int) $homework->id ) );

	$marks = mags_erp_parse_marks( mags_erp_post_text( 'marks' ), $homework->max_marks );
	if ( is_wp_error( $marks ) ) { mags_erp_flash( 'error', $marks->get_error_message() ); return $back; }
	$feedback = mags_erp_post_textarea( 'feedback', 2000 );
	if ( null === $marks && '' === $feedback ) { mags_erp_flash( 'error', 'Give marks, a comment, or both.' ); return $back; }

	$student = mags_erp_get_student( (int) $submission->student_id );
	mags_erp_grade_submission( (int) $submission->id, $marks, $feedback, get_current_user_id() );
	mags_erp_flash( 'success', 'Checked: ' . ( $student ? $student->full_name : 'student' ) . '.' );
	return $back;
}

/* ---------- Student: homework ---------- */

function mags_erp_action_submit_homework() {
	$student  = mags_erp_get_student_by_user( get_current_user_id() );
	$homework = mags_erp_get_homework( mags_erp_post_int( 'homework_id' ) );
	$back     = mags_erp_url( 'homework' );
	if ( ! $student || ! $homework || (int) $homework->class_id !== (int) $student->class_id ) { mags_erp_flash( 'error', 'That homework is not for your class.' ); return $back; }
	$existing = mags_erp_get_student_submission( (int) $homework->id, (int) $student->id );
	if ( $existing && $existing->checked_at ) { mags_erp_flash( 'error', 'This homework has already been checked and cannot be changed.' ); return $back; }

	$file_id = mags_erp_store_upload( 'answer_file' );
	if ( is_wp_error( $file_id ) ) { mags_erp_flash( 'error', $file_id->get_error_message() ); return $back; }
	if ( ! $file_id ) { mags_erp_flash( 'error', 'Choose the file with your answer (a photo or a document).' ); return $back; }

	$saved = mags_erp_save_submission( (int) $homework->id, (int) $student->id, $file_id, mags_erp_post_textarea( 'student_note', 1000 ) );
	if ( is_wp_error( $saved ) ) { mags_erp_delete_file( $file_id ); mags_erp_flash( 'error', $saved->get_error_message() ); return $back; }
	mags_erp_flash( 'success', $existing ? 'Your new answer has replaced the earlier one.' : 'Your answer has been sent to the teacher.' );
	return $back;
}

/* ---------- Teacher: notes ---------- */

function mags_erp_action_save_note() {
	$teacher_id = get_current_user_id();
	$class_id   = mags_erp_post_int( 'class_id' );
	$title      = mags_erp_post_text( 'title' );
	$back       = mags_erp_url( 'notes' );
	if ( ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) { mags_erp_flash( 'error', 'Choose one of your own classes.' ); return $back; }
	if ( '' === $title ) { mags_erp_flash( 'error', 'Enter a title for the notes.' ); return $back; }
	if ( mb_strlen( $title ) > 160 ) { mags_erp_flash( 'error', 'The title is too long (160 characters at most).' ); return $back; }

	$file_id = mags_erp_store_upload( 'note_file' );
	if ( is_wp_error( $file_id ) ) { mags_erp_flash( 'error', $file_id->get_error_message() ); return $back; }
	if ( ! $file_id ) { mags_erp_flash( 'error', 'Choose the notes file to upload.' ); return $back; }

	$created = mags_erp_create_note( $class_id, $teacher_id, $title, mags_erp_post_textarea( 'description', 2000 ), $file_id );
	if ( is_wp_error( $created ) ) { mags_erp_delete_file( $file_id ); mags_erp_flash( 'error', $created->get_error_message() ); return $back; }
	mags_erp_flash( 'success', 'Notes shared with ' . mags_erp_class_label( mags_erp_get_class( $class_id ) ) . '.' );
	return $back;
}

function mags_erp_action_delete_note() {
	$note = mags_erp_get_note( mags_erp_post_int( 'note_id' ) );
	if ( ! $note || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $note->class_id ) ) { mags_erp_flash( 'error', 'Those notes are not in one of your classes.' ); return mags_erp_url( 'notes' ); }
	mags_erp_delete_note( (int) $note->id );
	mags_erp_flash( 'success', 'Notes removed.' );
	return mags_erp_url( 'notes' );
}
