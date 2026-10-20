<?php
/** Form handlers. Every handler checks the role first, then saves, then returns where to go next. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** action => array( roles allowed, handler ). The handlers of the newer modules live in their own files in includes/. */
function mags_erp_actions() {
	$everyone = array( 'office', 'teacher', 'student' );
	$office   = array( 'office' );
	$teacher  = array( 'teacher' );
	$student  = array( 'student' );
	return array(
		'logout'               => array( $everyone, 'mags_erp_action_logout' ),
		'change_password'      => array( $everyone, 'mags_erp_action_change_password' ),
		'save_class'           => array( $office, 'mags_erp_action_save_class' ),
		'delete_class'         => array( $office, 'mags_erp_action_delete_class' ),
		'save_teacher'         => array( $office, 'mags_erp_action_save_teacher' ),
		'delete_teacher'       => array( $office, 'mags_erp_action_delete_teacher' ),
		'save_student'         => array( $office, 'mags_erp_action_save_student' ),
		'delete_student'       => array( $office, 'mags_erp_action_delete_student' ),
		'reset_password'       => array( $office, 'mags_erp_action_reset_password' ),
		'import_students'      => array( $office, 'mags_erp_action_import_students' ),
		'save_subject'         => array( $office, 'mags_erp_action_save_subject' ),
		'delete_subject'       => array( $office, 'mags_erp_action_delete_subject' ),
		'save_period'          => array( $office, 'mags_erp_action_save_period' ),
		'delete_period'        => array( $office, 'mags_erp_action_delete_period' ),
		'save_timetable'       => array( $office, 'mags_erp_action_save_timetable' ),
		'save_exam'            => array( $office, 'mags_erp_action_save_exam' ),
		'delete_exam'          => array( $office, 'mags_erp_action_delete_exam' ),
		'save_papers'          => array( $office, 'mags_erp_action_save_papers' ),
		'delete_paper'         => array( $office, 'mags_erp_action_delete_paper' ),
		'publish_exam'         => array( $office, 'mags_erp_action_publish_exam' ),
		'save_fee'             => array( $office, 'mags_erp_action_save_fee' ),
		'save_payment'         => array( $office, 'mags_erp_action_save_payment' ),
		'cancel_fee'           => array( $office, 'mags_erp_action_cancel_fee' ),
		'cancel_payment'       => array( $office, 'mags_erp_action_cancel_payment' ),
		'save_discount'        => array( $office, 'mags_erp_action_save_discount' ),
		'save_fine'            => array( $office, 'mags_erp_action_save_fine' ),
		'save_profile'         => array( $office, 'mags_erp_action_save_profile' ),
		'remove_photo'         => array( $office, 'mags_erp_action_remove_photo' ),
		'add_document'         => array( $office, 'mags_erp_action_add_document' ),
		'delete_document'      => array( $office, 'mags_erp_action_delete_document' ),
		'issue_certificate'    => array( $office, 'mags_erp_action_issue_certificate' ),
		'cancel_certificate'   => array( $office, 'mags_erp_action_cancel_certificate' ),
		'save_admission_settings' => array( $office, 'mags_erp_action_save_admission_settings' ),
		'decide_admission'     => array( $office, 'mags_erp_action_decide_admission' ),
		'delete_admission'     => array( $office, 'mags_erp_action_delete_admission' ),
		'save_staff_attendance' => array( $office, 'mags_erp_action_save_staff_attendance' ),
		'decide_staff_leave'   => array( $office, 'mags_erp_action_decide_staff_leave' ),
		'save_calendar'        => array( $office, 'mags_erp_action_save_calendar' ),
		'delete_calendar'      => array( $office, 'mags_erp_action_delete_calendar' ),
		'save_settings'        => array( $office, 'mags_erp_action_save_settings' ),
		'check_private_files'  => array( $office, 'mags_erp_action_check_private_files' ),
		'save_mail_settings'   => array( $office, 'mags_erp_action_save_mail_settings' ),
		'send_mail_now'        => array( $office, 'mags_erp_action_send_mail_now' ),
		'send_fee_reminders'   => array( $office, 'mags_erp_action_send_fee_reminders' ),
		'download_backup'      => array( $office, 'mags_erp_action_download_backup' ),
		'promote_classes'      => array( $office, 'mags_erp_action_promote_classes' ),
		'undo_promotion'       => array( $office, 'mags_erp_action_undo_promotion' ),
		'save_admit_settings'  => array( $office, 'mags_erp_action_save_admit_settings' ),
		'set_admit'            => array( $office, 'mags_erp_action_set_admit' ),
		'save_substitution'    => array( $office, 'mags_erp_action_save_substitution' ),
		'remove_substitution'  => array( $office, 'mags_erp_action_remove_substitution' ),
		'save_attendance'      => array( $teacher, 'mags_erp_action_save_attendance' ),
		'save_homework'        => array( $teacher, 'mags_erp_action_save_homework' ),
		'update_homework'      => array( $teacher, 'mags_erp_action_update_homework' ),
		'remove_homework_file' => array( $teacher, 'mags_erp_action_remove_homework_file' ),
		'delete_homework'      => array( $teacher, 'mags_erp_action_delete_homework' ),
		'grade_homework'       => array( $teacher, 'mags_erp_action_grade_homework' ),
		'save_note'            => array( $teacher, 'mags_erp_action_save_note' ),
		'delete_note'          => array( $teacher, 'mags_erp_action_delete_note' ),
		'save_marks'           => array( $teacher, 'mags_erp_action_save_marks' ),
		'save_notice'          => array( $teacher, 'mags_erp_action_save_notice' ),
		'delete_notice'        => array( $teacher, 'mags_erp_action_delete_notice' ),
		'decide_leave'         => array( $teacher, 'mags_erp_action_decide_leave' ),
		'ask_staff_leave'      => array( $teacher, 'mags_erp_action_ask_staff_leave' ),
		'withdraw_staff_leave' => array( $teacher, 'mags_erp_action_withdraw_staff_leave' ),
		'make_ptm_slots'       => array( $teacher, 'mags_erp_action_make_ptm_slots' ),
		'remove_ptm_slot'      => array( $teacher, 'mags_erp_action_remove_ptm_slot' ),
		'submit_homework'      => array( $student, 'mags_erp_action_submit_homework' ),
		'ask_question'         => array( $student, 'mags_erp_action_ask_question' ),
		'ask_leave'            => array( $student, 'mags_erp_action_ask_leave' ),
		'withdraw_leave'       => array( $student, 'mags_erp_action_withdraw_leave' ),
		'book_ptm'             => array( $student, 'mags_erp_action_book_ptm' ),
		'cancel_ptm'           => array( $student, 'mags_erp_action_cancel_ptm' ),
		'reply_question'       => array( array( 'teacher', 'student' ), 'mags_erp_action_reply_question' ),
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
	// Signing out needs no portal role: a website account that wandered in must be able to leave.
	if ( 'logout' === $action && wp_verify_nonce( mags_erp_post_text( '_mags_erp_nonce' ), 'mags_erp_logout' ) ) {
		wp_safe_redirect( mags_erp_action_logout() );
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
	if ( ! is_wp_error( $saved ) && $class_id ) { $saved = mags_erp_set_class_teacher( $class_id, mags_erp_post_int( 'class_teacher_id' ) ); }
	if ( is_wp_error( $saved ) ) {
		mags_erp_flash( 'error', $saved->get_error_message() );
		return mags_erp_url( 'classes', array( 'edit' => $class_id ?: '' ) );
	}
	mags_erp_flash( 'success', $class_id ? 'Class updated.' : 'Class added.' );
	return mags_erp_url( 'classes' );
}

/** Remove a class (it is hidden and kept) or restore a removed one. */
function mags_erp_action_delete_class() {
	$class_id = mags_erp_post_int( 'class_id' );
	if ( mags_erp_post_int( 'restore' ) ) {
		$restored = mags_erp_restore_class( $class_id );
		mags_erp_flash( is_wp_error( $restored ) ? 'error' : 'success', is_wp_error( $restored ) ? $restored->get_error_message() : 'Class restored, with its homework, notes, timetable and marks.' . ( $restored ? ' ' . sprintf( _n( '%d timetable period has no teacher now, because that teacher teaches another class at that time.', '%d timetable periods have no teacher now, because that teacher teaches another class at that time.', $restored, 'mags-erp' ), $restored ) : '' ) );
		return mags_erp_url( 'classes' );
	}
	$removed = mags_erp_remove_class( $class_id );
	mags_erp_flash( is_wp_error( $removed ) ? 'error' : 'success', is_wp_error( $removed ) ? $removed->get_error_message() : 'Class removed. Its records are kept and it can be restored from the removed classes.' );
	return mags_erp_url( 'classes' );
}

/* ---------- Office: teachers ---------- */

function mags_erp_action_save_teacher() {
	$teacher_id = mags_erp_post_int( 'teacher_id' );
	$name       = mags_erp_post_text( 'erp_name' );
	$class_ids  = isset( $_POST['class_ids'] ) && is_array( $_POST['class_ids'] ) ? array_map( 'absint', $_POST['class_ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification

	if ( $teacher_id ) {
		if ( ! mags_erp_is_managed_user( $teacher_id, 'mags_teacher' ) || mags_erp_is_removed_user( $teacher_id ) ) {
			mags_erp_flash( 'error', 'That teacher no longer exists.' );
			return mags_erp_url( 'teachers' );
		}
		if ( '' === $name ) {
			mags_erp_flash( 'error', 'Enter the teacher’s name.' );
			return mags_erp_url( 'teachers', array( 'edit' => $teacher_id ) );
		}
		wp_update_user( array( 'ID' => $teacher_id, 'display_name' => $name, 'first_name' => $name ) );
		$dropped = mags_erp_set_teacher_classes( $teacher_id, $class_ids );
		$labels  = mags_erp_all_class_labels();
		mags_erp_flash( 'success', 'Teacher updated.' . ( $dropped ? ' Taken away: ' . implode( ', ', array_map( static function ( $class_id ) use ( $labels ) { return $labels[ $class_id ] ?? 'class'; }, $dropped ) ) . ' (also as class teacher and in the timetable of that class).' : '' ) );
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

/**
 * Remove a teacher or restore a removed one. Removing switches the login off
 * and frees the classes; the homework, notes and marks they gave stay with the
 * class, where the other teachers of the class carry on with them.
 */
function mags_erp_action_delete_teacher() {
	$teacher_id = mags_erp_post_int( 'teacher_id' );
	$back       = mags_erp_url( 'teachers' );
	if ( ! mags_erp_is_managed_user( $teacher_id, 'mags_teacher' ) ) { mags_erp_flash( 'error', 'That teacher no longer exists.' ); return $back; }

	if ( mags_erp_post_int( 'restore' ) ) {
		if ( ! mags_erp_is_removed_user( $teacher_id ) ) { mags_erp_flash( 'error', 'That teacher is not in the removed list.' ); return $back; }
		mags_erp_restore_teacher( $teacher_id );
		mags_erp_flash( 'success', 'Teacher restored. They can sign in again with the same password and have their earlier classes back. Check the class teacher and the timetable of those classes.' );
		return $back;
	}
	if ( mags_erp_is_removed_user( $teacher_id ) ) { mags_erp_flash( 'error', 'That teacher is already removed.' ); return $back; }

	$alone  = mags_erp_remove_teacher( $teacher_id );
	$labels = mags_erp_all_class_labels();
	$lines  = array();
	foreach ( $alone as $class_id ) { $lines[] = sprintf( '%s now has no teacher. Give it to another teacher so its homework can be checked and its marks entered.', $labels[ $class_id ] ?? 'A class' ); }
	mags_erp_flash( 'success', 'Teacher removed. They can no longer sign in. Their attendance, homework, notes, marks and replies are kept.', array( 'lines' => $lines ) );
	return $back;
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

/** Remove a student (hidden and kept, login switched off) or restore a removed one. */
function mags_erp_action_delete_student() {
	$student = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
	if ( ! $student ) {
		mags_erp_flash( 'error', 'That student no longer exists.' );
		return mags_erp_url( 'students' );
	}

	if ( mags_erp_post_int( 'restore' ) ) {
		$password = mags_erp_restore_student( (int) $student->id );
		if ( is_wp_error( $password ) ) {
			mags_erp_flash( 'error', $password->get_error_message() );
			return mags_erp_url( 'students', array( 'removed' => 1 ) );
		}
		if ( '' !== $password ) {
			mags_erp_flash( 'success', 'Student restored with a new login. Give these login details to the student or parent now. The password is not shown again.', array( 'name' => $student->full_name, 'login_as' => 'Student / Parent', 'login' => $student->admission_no, 'password' => $password ) );
		} else {
			mags_erp_flash( 'success', $student->full_name . ' is back in the class and can sign in again with the same password.' );
		}
		return mags_erp_url( 'students', array( 'class' => (int) $student->class_id ) );
	}

	$removed = mags_erp_remove_student( (int) $student->id );
	mags_erp_flash( $removed ? 'success' : 'error', $removed ? 'Student removed. The login is switched off; attendance, homework, marks and fees are kept. You can restore the student from “Removed students”.' : 'That student is already removed.' );
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
		$student = $student && ! $student->hidden_at ? $student : null;
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

	if ( ! $user_id || ! mags_erp_is_managed_user( $user_id, $role ) || mags_erp_is_removed_user( $user_id ) ) {
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

	// Own classes, or a class the office gave this teacher as a substitute for that very day.
	if ( ! mags_erp_teacher_can_mark( $teacher_id, $class_id, $date ) ) {
		mags_erp_flash( 'error', 'This class is not assigned to you.' );
		return mags_erp_url();
	}
	if ( ! mags_erp_valid_date( $date ) || $date > mags_erp_today() ) {
		mags_erp_flash( 'error', 'Choose today or an earlier date.' );
		return mags_erp_url( 'attendance', array( 'class' => $class_id ) );
	}
	$holiday = mags_erp_holiday_on( $date );
	if ( $holiday ) {
		mags_erp_flash( 'error', sprintf( '%1$s is a holiday (%2$s). Attendance is not marked on a holiday.', mags_erp_format_date( $date ), $holiday->title ) );
		return mags_erp_url( 'attendance', array( 'class' => $class_id, 'date' => $date ) );
	}

	$counts = mags_erp_save_attendance( $class_id, $date, $marks, $teacher_id );
	if ( ! array_sum( $counts ) ) {
		mags_erp_flash( 'error', 'No attendance was marked.' );
	} else {
		$mailed = mags_erp_notify_absent( $class_id, $date, $marks );
		mags_erp_flash( 'success', sprintf( 'Attendance saved for %s: %d present, %d absent, %d on leave.', mags_erp_format_date( $date ), $counts['P'], $counts['A'], $counts['L'] ) . ( $mailed ? ' ' . sprintf( _n( 'The parent of %d absent child gets an email in %d minutes, unless you correct the mark before then.', 'The parents of %d absent children get an email in %d minutes, unless you correct the mark before then.', $mailed, 'mags-erp' ), $mailed, MAGS_ERP_ABSENT_MAIL_DELAY ) : '' ) );
	}
	return mags_erp_url( 'attendance', array( 'class' => $class_id, 'date' => $date ) );
}

/* ---------- Teacher: homework ---------- */

/** Title, due date and maximum marks typed in the homework form. Returns array( title, due date, maximum marks ) or a WP_Error. */
function mags_erp_posted_homework_fields() {
	$title    = mags_erp_post_text( 'title' );
	$due_date = mags_erp_post_text( 'due_date' );
	$max_text = mags_erp_post_text( 'max_marks' );
	if ( '' === $title ) { return new WP_Error( 'mags_erp_homework', 'Enter a title for the homework.' ); }
	if ( mb_strlen( $title ) > 160 ) { return new WP_Error( 'mags_erp_homework', 'The title is too long (160 characters at most).' ); }
	if ( '' !== $due_date && ! mags_erp_valid_date( $due_date ) ) { return new WP_Error( 'mags_erp_homework', 'The due date is not a valid date.' ); }
	$max_marks = null;
	if ( '' !== $max_text ) {
		$max_marks = mags_erp_parse_marks( $max_text, null );
		if ( is_wp_error( $max_marks ) || $max_marks <= 0 ) { return new WP_Error( 'mags_erp_homework', 'Enter the maximum marks as a number above 0, or leave it empty.' ); }
	}
	return array( $title, $due_date, $max_marks );
}

function mags_erp_action_save_homework() {
	$teacher_id = get_current_user_id();
	$class_id   = mags_erp_post_int( 'class_id' );
	$back       = mags_erp_url( 'homework' );

	if ( ! mags_erp_teacher_has_class( $teacher_id, $class_id ) ) { mags_erp_flash( 'error', 'Choose one of your own classes.' ); return $back; }
	$fields = mags_erp_posted_homework_fields();
	if ( is_wp_error( $fields ) ) { mags_erp_flash( 'error', $fields->get_error_message() ); return $back; }
	$file_ids = mags_erp_store_uploads( 'homework_files', MAGS_ERP_HOMEWORK_FILES );
	if ( is_wp_error( $file_ids ) ) { mags_erp_flash( 'error', $file_ids->get_error_message() ); return $back; }

	$created = mags_erp_create_homework( $class_id, $teacher_id, $fields[0], mags_erp_post_textarea( 'instructions' ), $fields[1], $fields[2] );
	if ( is_wp_error( $created ) ) { array_map( 'mags_erp_discard_upload', $file_ids ); mags_erp_flash( 'error', $created->get_error_message() ); return $back; }
	mags_erp_add_homework_files( $created, $file_ids, $teacher_id );
	mags_erp_flash( 'success', 'Homework given to ' . mags_erp_class_label( mags_erp_get_class( $class_id ) ) . '.' );
	return mags_erp_url( 'homework', array( 'id' => $created ) );
}

/** Change a homework and add more files to it. Any teacher of the class may do this, like checking answers. */
function mags_erp_action_update_homework() {
	$teacher_id = get_current_user_id();
	$homework   = mags_erp_get_homework( mags_erp_post_int( 'homework_id' ) );
	if ( ! $homework || ! mags_erp_teacher_has_class( $teacher_id, (int) $homework->class_id ) ) { mags_erp_flash( 'error', 'That homework is not in one of your classes.' ); return mags_erp_url( 'homework' ); }
	$back = mags_erp_url( 'homework', array( 'id' => (int) $homework->id ) );

	$fields = mags_erp_posted_homework_fields();
	if ( is_wp_error( $fields ) ) { mags_erp_flash( 'error', $fields->get_error_message() ); return $back; }
	$room     = MAGS_ERP_HOMEWORK_FILES - count( mags_erp_homework_files( (int) $homework->id ) );
	$file_ids = mags_erp_store_uploads( 'homework_files', max( 0, $room ) );
	if ( is_wp_error( $file_ids ) ) { mags_erp_flash( 'error', $file_ids->get_error_message() ); return $back; }

	$updated = mags_erp_update_homework( (int) $homework->id, $fields[0], mags_erp_post_textarea( 'instructions' ), $fields[1], $fields[2], $teacher_id );
	if ( is_wp_error( $updated ) ) { array_map( 'mags_erp_discard_upload', $file_ids ); mags_erp_flash( 'error', $updated->get_error_message() ); return $back; }
	mags_erp_add_homework_files( (int) $homework->id, $file_ids, $teacher_id );
	mags_erp_flash( 'success', 'Homework changed.' . ( $file_ids ? ' ' . sprintf( _n( '%d file added.', '%d files added.', count( $file_ids ), 'mags-erp' ), count( $file_ids ) ) : '' ) );
	return $back;
}

/** Take one file off a homework. The file is kept on the server, hidden. */
function mags_erp_action_remove_homework_file() {
	$link     = mags_erp_get_homework_file_link( mags_erp_post_int( 'link_id' ) );
	$homework = $link ? mags_erp_get_homework( (int) $link->homework_id ) : null;
	if ( ! $homework || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $homework->class_id ) ) { mags_erp_flash( 'error', 'That file is not in one of your classes.' ); return mags_erp_url( 'homework' ); }
	mags_erp_hide( 'homework_files', (int) $link->id );
	mags_erp_flash( 'success', 'File taken off the homework.' );
	return mags_erp_url( 'homework', array( 'id' => (int) $homework->id ) );
}

/** Remove a homework (hidden and kept with its answers) or restore a removed one. */
function mags_erp_action_delete_homework() {
	$homework = mags_erp_get_homework( mags_erp_post_int( 'homework_id' ), true );
	if ( ! $homework || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $homework->class_id ) ) { mags_erp_flash( 'error', 'That homework is not in one of your classes.' ); return mags_erp_url( 'homework' ); }
	if ( mags_erp_post_int( 'restore' ) ) {
		mags_erp_unhide( 'homework', (int) $homework->id );
		mags_erp_flash( 'success', 'Homework restored, with its answers and marks.' );
		return mags_erp_url( 'homework', array( 'id' => (int) $homework->id ) );
	}
	mags_erp_hide( 'homework', (int) $homework->id );
	mags_erp_flash( 'success', 'Homework removed. Students no longer see it. Its answers and marks are kept and it can be restored from “Removed homework”.' );
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
	if ( ! empty( $student->class_since ) && $homework->created_at < $student->class_since ) { mags_erp_flash( 'error', 'That homework was given before you came to this class.' ); return $back; }
	$existing = mags_erp_get_student_submission( (int) $homework->id, (int) $student->id );
	if ( $existing && $existing->checked_at ) { mags_erp_flash( 'error', 'This homework has already been checked and cannot be changed.' ); return $back; }

	// Every answer sent is a new upload, so the number of files one student can send in a day is limited.
	if ( mags_erp_uploads_today( get_current_user_id() ) >= MAGS_ERP_STUDENT_FILES_PER_DAY ) { mags_erp_flash( 'error', sprintf( 'You have sent %d files today. Please send the rest tomorrow.', MAGS_ERP_STUDENT_FILES_PER_DAY ) ); return $back; }

	$file_id = mags_erp_store_upload( 'answer_file' );
	if ( is_wp_error( $file_id ) ) { mags_erp_flash( 'error', $file_id->get_error_message() ); return $back; }
	if ( ! $file_id ) { mags_erp_flash( 'error', 'Choose the file with your answer (a photo or a document).' ); return $back; }

	$saved = mags_erp_save_submission( (int) $homework->id, (int) $student->id, $file_id, mags_erp_post_textarea( 'student_note', 1000 ) );
	if ( is_wp_error( $saved ) ) { mags_erp_discard_upload( $file_id ); mags_erp_flash( 'error', $saved->get_error_message() ); return $back; }
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
	if ( is_wp_error( $created ) ) { mags_erp_discard_upload( $file_id ); mags_erp_flash( 'error', $created->get_error_message() ); return $back; }
	mags_erp_flash( 'success', 'Notes shared with ' . mags_erp_class_label( mags_erp_get_class( $class_id ) ) . '.' );
	return $back;
}

/** Remove notes (hidden and kept) or restore removed ones. */
function mags_erp_action_delete_note() {
	$note = mags_erp_get_note( mags_erp_post_int( 'note_id' ), true );
	if ( ! $note || ! mags_erp_teacher_has_class( get_current_user_id(), (int) $note->class_id ) ) { mags_erp_flash( 'error', 'Those notes are not in one of your classes.' ); return mags_erp_url( 'notes' ); }
	if ( mags_erp_post_int( 'restore' ) ) {
		mags_erp_unhide( 'notes', (int) $note->id );
		mags_erp_flash( 'success', 'Notes restored. The class can download them again.' );
	} else {
		mags_erp_hide( 'notes', (int) $note->id );
		mags_erp_flash( 'success', 'Notes removed. The class no longer sees them; the file is kept under “Removed notes”.' );
	}
	return mags_erp_url( 'notes' );
}
