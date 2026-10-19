<?php
/**
 * Admission form. Parents fill the form at /portal/admission/ without a login.
 * The office reads each application and accepts it or not. Accepting makes the
 * student, with a portal login and a profile filled from the form.
 *
 * The form is the only portal screen that anyone can post to, so it is closed
 * until the office opens it, it limits how many forms one connection and the
 * whole site can send, and it takes at most one photo and one document.
 * Applications are never deleted; the office can remove (hide) unwanted ones.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Forms one connection may send in an hour, and all connections together in a
 * day. Tries count every form that reaches the photo and document step, also
 * the refused ones, because opening a photo is work for the server.
 */
define( 'MAGS_ERP_ADMISSIONS_PER_HOUR', 5 );
define( 'MAGS_ERP_ADMISSION_TRIES_PER_HOUR', 20 );
define( 'MAGS_ERP_ADMISSIONS_PER_DAY', 300 );

/** Largest document a parent may send with the form. */
define( 'MAGS_ERP_ADMISSION_DOCUMENT_BYTES', 5 * MB_IN_BYTES );

/** array( open, note ): whether the form takes applications and the line shown above it. */
function mags_erp_admission_settings() {
	$saved = (array) get_option( 'mags_erp_admissions', array() );
	return array( 'open' => ! empty( $saved['open'] ), 'note' => (string) ( $saved['note'] ?? '' ) );
}

function mags_erp_admissions_open() {
	return mags_erp_admission_settings()['open'];
}

/** The number a parent quotes at the office, e.g. APP-00012. */
function mags_erp_admission_no_text( $admission_id ) {
	return 'APP-' . str_pad( (string) (int) $admission_id, 5, '0', STR_PAD_LEFT );
}

/** Key that lets the "thank you" page show an application number only to the browser that sent the form. */
function mags_erp_admission_key( $admission_id ) {
	return substr( hash_hmac( 'sha256', 'mags_erp_admission|' . (int) $admission_id, wp_salt( 'nonce' ) ), 0, 20 );
}

/** Signed time the form was shown, to tell a person from a program that posts at once. */
function mags_erp_admission_stamp( $time = 0 ) {
	$time = $time ? (int) $time : time();
	return $time . '.' . substr( hash_hmac( 'sha256', 'mags_erp_admission_time|' . $time, wp_salt( 'nonce' ) ), 0, 20 );
}

/** Seconds since a stamp was made, or -1 when the stamp is not ours. */
function mags_erp_admission_stamp_age( $stamp ) {
	$time = (int) strtok( (string) $stamp, '.' );
	return $time && hash_equals( mags_erp_admission_stamp( $time ), (string) $stamp ) ? time() - $time : -1;
}

/** Class names a parent can choose from (sections are the office's choice when it accepts). */
function mags_erp_admission_class_names() {
	return array_values( array_unique( wp_list_pluck( mags_erp_get_classes(), 'name' ) ) );
}

/** status => array( label, chip class ). */
function mags_erp_admission_statuses() {
	return array(
		'new'      => array( 'New', 'erp-chip--orange' ),
		'approved' => array( 'Accepted', 'erp-chip--green' ),
		'rejected' => array( 'Not accepted', 'erp-chip--status-A' ),
	);
}

/** One application. A removed one is returned only when $with_removed is true. */
function mags_erp_get_admission( $admission_id, $with_removed = false ) {
	global $wpdb;
	$admission = $admission_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'admissions' ) . ' WHERE id = %d', $admission_id ) ) : null;
	return $admission && ( $with_removed || ! $admission->hidden_at ) ? $admission : null;
}

/** Applications with one status, newest first, with optional search in the child's and parents' names and the phone number. */
function mags_erp_query_admissions( $status, $search = '', $limit = 200, $removed = false ) {
	global $wpdb;
	$where  = $removed ? 'hidden_at IS NOT NULL' : 'hidden_at IS NULL AND status = %s';
	$values = $removed ? array() : array( $status );
	if ( '' !== $search ) {
		$like   = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= ' AND ( student_name LIKE %s OR father_name LIKE %s OR mother_name LIKE %s OR phone LIKE %s )';
		array_push( $values, $like, $like, $like, $like );
	}
	$values[] = $limit;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'admissions' ) . " WHERE {$where} ORDER BY id DESC LIMIT %d", $values ) );
}

/** status => number of applications that are not removed. */
function mags_erp_admission_counts() {
	global $wpdb;
	$counts = array_fill_keys( array_keys( mags_erp_admission_statuses() ), 0 );
	foreach ( (array) $wpdb->get_results( 'SELECT status, COUNT(*) AS total FROM ' . mags_erp_table( 'admissions' ) . ' WHERE hidden_at IS NULL GROUP BY status' ) as $row ) {
		if ( isset( $counts[ $row->status ] ) ) { $counts[ $row->status ] = (int) $row->total; }
	}
	return $counts;
}

/* ---------- The public form ---------- */

/** What the parent typed, cleaned. Every value is a string. */
function mags_erp_admission_posted() {
	$values = array();
	foreach ( array( 'student_name', 'gender', 'dob', 'applied_class', 'father_name', 'mother_name', 'phone', 'email', 'previous_school', 'blood_group' ) as $key ) { $values[ $key ] = mags_erp_post_text( $key ); }
	$values['address'] = trim( preg_replace( '/\s+/', ' ', mags_erp_post_textarea( 'address', 600 ) ) );
	$values['agree']   = mags_erp_post_text( 'agree' );
	return $values;
}

/** Check a filled form. Returns field => message for everything that is wrong (empty when all is right). */
function mags_erp_validate_admission( array $v ) {
	$errors = array();
	$today  = mags_erp_today();
	if ( '' === $v['student_name'] || ! preg_match( '/\p{L}/u', $v['student_name'] ) ) { $errors['student_name'] = 'Enter the child’s full name.'; }
	if ( ! mags_erp_valid_date( $v['dob'] ) ) {
		$errors['dob'] = 'Choose the child’s date of birth.';
	} elseif ( $v['dob'] > gmdate( 'Y-m-d', strtotime( $today . ' -2 years' ) ) || $v['dob'] < gmdate( 'Y-m-d', strtotime( $today . ' -25 years' ) ) ) {
		$errors['dob'] = 'Please check the date of birth. The child must be between 2 and 25 years old.';
	}
	$classes = mags_erp_admission_class_names();
	if ( '' === $v['applied_class'] || ( $classes && ! in_array( $v['applied_class'], $classes, true ) ) ) { $errors['applied_class'] = 'Choose the class you want admission in.'; }
	if ( '' === $v['father_name'] && '' === $v['mother_name'] ) { $errors['father_name'] = 'Enter the father’s or the mother’s name.'; }
	if ( '' === mags_erp_clean_phone( $v['phone'] ) ) { $errors['phone'] = 'Enter a phone number with 10 to 15 digits.'; }
	if ( '' !== $v['email'] && ! is_email( $v['email'] ) ) { $errors['email'] = 'This email address is not correct. You can also leave it empty.'; }
	if ( '' === $v['address'] ) { $errors['address'] = 'Enter your address.'; }
	if ( '' !== $v['gender'] && ! isset( mags_erp_genders()[ $v['gender'] ] ) ) { $errors['gender'] = 'Choose Boy, Girl or Other.'; }
	if ( '' !== $v['blood_group'] && ! in_array( $v['blood_group'], mags_erp_blood_groups(), true ) ) { $errors['blood_group'] = 'Choose a blood group from the list.'; }
	foreach ( array( 'student_name' => 120, 'father_name' => 120, 'mother_name' => 120, 'email' => 120, 'address' => 500, 'previous_school' => 160, 'applied_class' => 60 ) as $key => $limit ) {
		if ( mb_strlen( $v[ $key ] ) > $limit && ! isset( $errors[ $key ] ) ) { $errors[ $key ] = sprintf( 'This is too long (%d characters at most).', $limit ); }
	}
	if ( '' === $v['agree'] ) { $errors['agree'] = 'Please tick the box to say that you are the parent or guardian and the details are correct.'; }
	return $errors;
}

/**
 * What is wrong with the document a parent sent ('' when it is fine). It must
 * be a PDF, JPG or PNG by its name and by what is really inside the file.
 */
function mags_erp_admission_document_problem( $sent ) {
	$allowed = array( 'pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png' );
	$wrong   = 'The document must be a PDF, JPG or PNG file.';
	if ( UPLOAD_ERR_OK !== (int) ( $sent['error'] ?? UPLOAD_ERR_NO_FILE ) || empty( $sent['tmp_name'] ) || ! is_string( $sent['tmp_name'] ) || ! is_uploaded_file( $sent['tmp_name'] ) ) { return in_array( (int) ( $sent['error'] ?? 0 ), array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? sprintf( 'The document is too large. The limit is %s.', size_format( MAGS_ERP_ADMISSION_DOCUMENT_BYTES ) ) : 'The document could not be uploaded. Please try again.'; }
	if ( filesize( $sent['tmp_name'] ) > MAGS_ERP_ADMISSION_DOCUMENT_BYTES ) { return sprintf( 'The document is too large. The limit is %s.', size_format( MAGS_ERP_ADMISSION_DOCUMENT_BYTES ) ); }
	$check = wp_check_filetype_and_ext( $sent['tmp_name'], sanitize_file_name( wp_basename( (string) ( $sent['name'] ?? '' ) ) ), $allowed );
	if ( empty( $check['type'] ) ) { return $wrong; }
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$real  = $finfo ? (string) finfo_file( $finfo, $sent['tmp_name'] ) : '';
		if ( $real !== $check['type'] ) { return $wrong; }
	}
	return '';
}

/** Keys of the counters that limit how many forms are sent. */
function mags_erp_admission_limit_keys() {
	return array( 'ip' => 'mags_erp_adm_ip_' . md5( mags_erp_client_ip() ), 'try' => 'mags_erp_adm_try_' . md5( mags_erp_client_ip() ), 'day' => 'mags_erp_adm_day_' . mags_erp_today() );
}

/**
 * Take one posted admission form. Returns array( application id, errors ):
 * the id is 0 when something is wrong, and errors is field => message
 * ('form' for a message about the whole form).
 */
function mags_erp_receive_admission( array $v ) {
	global $wpdb;
	if ( ! wp_verify_nonce( mags_erp_post_text( '_mags_erp_nonce' ), 'mags_erp_apply_admission' ) ) { return array( 0, array( 'form' => 'This page was open for too long. Please check the form and send it again.' ) ); }
	// A hidden field that people leave empty, and a form sent within a few seconds, are signs of a program.
	$age = mags_erp_admission_stamp_age( mags_erp_post_text( 'erp_started' ) );
	if ( '' !== mags_erp_post_text( 'erp_website' ) || $age < 4 || $age > DAY_IN_SECONDS ) { return array( 0, array( 'form' => 'The form could not be sent. Please check it and press Send again.' ) ); }

	$errors = mags_erp_validate_admission( $v );
	if ( $errors ) { return array( 0, $errors ); }

	$keys  = mags_erp_admission_limit_keys();
	$tries = (int) get_transient( $keys['try'] ) + 1;
	set_transient( $keys['try'], $tries, HOUR_IN_SECONDS );
	if ( $tries > MAGS_ERP_ADMISSION_TRIES_PER_HOUR || (int) get_transient( $keys['ip'] ) >= MAGS_ERP_ADMISSIONS_PER_HOUR || (int) get_transient( $keys['day'] ) >= MAGS_ERP_ADMISSIONS_PER_DAY ) { return array( 0, array( 'form' => 'Too many forms have been sent from here. Please try again after one hour, or contact the school office.' ) ); }

	$table = mags_erp_table( 'admissions' );
	$phone = mags_erp_clean_phone( $v['phone'] );
	$twin  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE phone = %s AND dob = %s AND student_name = %s AND status = 'new' AND hidden_at IS NULL LIMIT 1", $phone, $v['dob'], $v['student_name'] ) );
	if ( $twin ) { return array( 0, array( 'form' => sprintf( 'The school already has this form: application number %s. You do not need to send it again.', mags_erp_admission_no_text( $twin ) ) ) ); }

	// The document is looked at before the photo is opened: a wrong document should cost the server nothing.
	$sent = isset( $_FILES['document_file'] ) && is_array( $_FILES['document_file'] ) && ! is_array( $_FILES['document_file']['error'] ?? array() ) && UPLOAD_ERR_NO_FILE !== (int) ( $_FILES['document_file']['error'] ?? UPLOAD_ERR_NO_FILE ) ? $_FILES['document_file'] : null; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $sent ) {
		$problem = mags_erp_admission_document_problem( $sent );
		if ( '' !== $problem ) { return array( 0, array( 'document_file' => $problem ) ); }
	}
	$photo = mags_erp_photo_supported() ? mags_erp_store_photo( 'photo' ) : 0;
	if ( is_wp_error( $photo ) ) { return array( 0, array( 'photo' => $photo->get_error_message() ) ); }
	$document = $sent ? mags_erp_store_file( $sent ) : 0;
	if ( is_wp_error( $document ) ) {
		if ( $photo ) { mags_erp_discard_upload( $photo ); }
		return array( 0, array( 'document_file' => $document->get_error_message() ) );
	}

	$saved = $wpdb->insert(
		$table,
		array( 'student_name' => $v['student_name'], 'gender' => $v['gender'], 'dob' => $v['dob'], 'applied_class' => $v['applied_class'], 'father_name' => $v['father_name'], 'mother_name' => $v['mother_name'], 'phone' => $phone, 'email' => $v['email'], 'address' => $v['address'], 'previous_school' => $v['previous_school'], 'blood_group' => $v['blood_group'], 'photo_file_id' => (int) $photo, 'document_file_id' => (int) $document, 'status' => 'new', 'created_at' => current_time( 'mysql' ) ),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
	);
	if ( ! $saved ) {
		if ( $photo ) { mags_erp_discard_upload( $photo ); }
		if ( $document ) { mags_erp_discard_upload( $document ); }
		return array( 0, array( 'form' => 'The form could not be saved. Please try again.' ) );
	}
	// Keep the number of the application before anything else is written to the database.
	$admission_id = (int) $wpdb->insert_id;
	set_transient( $keys['ip'], (int) get_transient( $keys['ip'] ) + 1, HOUR_IN_SECONDS );
	set_transient( $keys['day'], (int) get_transient( $keys['day'] ) + 1, DAY_IN_SECONDS );
	return array( $admission_id, array() );
}

/** /portal/admission/ : show the form, take it when it is posted, and show the application number afterwards. Open to everyone. */
function mags_erp_admission_screen() {
	$settings = mags_erp_admission_settings();
	$state    = array(
		'open'    => $settings['open'],
		'note'    => $settings['note'],
		'values'  => array_fill_keys( array( 'student_name', 'gender', 'dob', 'applied_class', 'father_name', 'mother_name', 'phone', 'email', 'address', 'previous_school', 'blood_group', 'agree' ), '' ),
		'errors'  => array(),
		'done'    => '',
		'classes' => mags_erp_admission_class_names(),
	);

	$done = mags_erp_get_int( 'done' );
	if ( $done && hash_equals( mags_erp_admission_key( $done ), mags_erp_get_text( 'k' ) ) ) { $state['done'] = mags_erp_admission_no_text( $done ); }

	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && $settings['open'] ) {
		if ( mags_erp_request_too_large() ) {
			$state['errors']['form'] = sprintf( 'The files are too large, so the form did not arrive. Please send it again with smaller files (photo and document together under %s).', size_format( wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ) ) );
		} else {
			$state['values'] = mags_erp_admission_posted();
			list( $admission_id, $errors ) = mags_erp_receive_admission( $state['values'] );
			if ( $admission_id ) {
				wp_safe_redirect( mags_erp_url( 'admission', array( 'done' => $admission_id, 'k' => mags_erp_admission_key( $admission_id ) ) ) );
				exit;
			}
			$state['errors'] = $errors;
		}
	}

	$ctx = array( 'route' => 'admission', 'role' => '', 'screens' => array(), 'badges' => array(), 'user' => wp_get_current_user(), 'title' => 'Admission form', 'flash' => null, 'admission' => $state );
	mags_erp_render( 'admission-form', $ctx );
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_admission_settings() {
	$note = mags_erp_post_text( 'note' );
	if ( mb_strlen( $note ) > 200 ) { mags_erp_flash( 'error', 'The line above the form is too long (200 characters at most).' ); return mags_erp_url( 'admissions' ); }
	$open = (bool) mags_erp_post_int( 'open' );
	update_option( 'mags_erp_admissions', array( 'open' => $open, 'note' => $note ), false );
	mags_erp_flash( 'success', $open ? 'The admission form is open. Parents can fill it on the website.' : 'The admission form is closed. Parents see a message that admissions are closed.' );
	return mags_erp_url( 'admissions' );
}

/**
 * Accept an application (this makes the student, the login and the profile)
 * or say that it is not accepted. An accepted application cannot be changed.
 */
function mags_erp_action_decide_admission() {
	global $wpdb;
	$admission = mags_erp_get_admission( mags_erp_post_int( 'admission_id' ) );
	if ( ! $admission ) { mags_erp_flash( 'error', 'That application no longer exists.' ); return mags_erp_url( 'admissions' ); }
	$back     = mags_erp_url( 'admissions', array( 'id' => (int) $admission->id ) );
	$table    = mags_erp_table( 'admissions' );
	$decision = mags_erp_post_text( 'decision' );
	$note     = mags_erp_post_text( 'decision_note' );
	if ( 'approved' === $admission->status ) { mags_erp_flash( 'error', 'This application is already accepted.' ); return $back; }
	if ( mb_strlen( $note ) > 500 ) { mags_erp_flash( 'error', 'The note is too long (500 characters at most).' ); return $back; }

	if ( 'rejected' === $decision ) {
		$done = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'rejected', decision_note = %s, decided_by = %d, decided_at = %s WHERE id = %d AND status <> 'approved'", $note, get_current_user_id(), current_time( 'mysql' ), $admission->id ) );
		if ( ! $done ) { mags_erp_flash( 'error', 'This application is already accepted.' ); return $back; }
		mags_erp_flash( 'success', sprintf( 'Application %s marked as not accepted. It is kept in the list and can still be accepted later.', mags_erp_admission_no_text( $admission->id ) ) );
		return mags_erp_url( 'admissions' );
	}
	if ( 'approved' !== $decision ) { mags_erp_flash( 'error', 'Choose Accept or Do not accept.' ); return $back; }

	$class_id     = mags_erp_post_int( 'class_id' );
	$admission_no = mags_erp_post_text( 'admission_no' );
	$roll_no      = mags_erp_post_text( 'roll_no' );
	// Take the application first, so two people accepting it at the same moment do not make two students.
	$taken = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'approved', decision_note = %s, decided_by = %d, decided_at = %s WHERE id = %d AND status <> 'approved' AND hidden_at IS NULL", $note, get_current_user_id(), current_time( 'mysql' ), $admission->id ) );
	if ( ! $taken ) { mags_erp_flash( 'error', 'This application is already accepted.' ); return $back; }

	$created = mags_erp_create_student( $admission_no, $admission->student_name, $class_id, $roll_no );
	if ( is_wp_error( $created ) ) {
		$wpdb->update( $table, array( 'status' => $admission->status, 'decision_note' => $admission->decision_note, 'decided_by' => (int) $admission->decided_by, 'decided_at' => $admission->decided_at ), array( 'id' => (int) $admission->id ), array( '%s', '%s', '%d', '%s' ), array( '%d' ) );
		mags_erp_flash( 'error', $created->get_error_message() );
		return $back;
	}
	$student_id = (int) $created[0];
	$wpdb->update( $table, array( 'student_id' => $student_id ), array( 'id' => (int) $admission->id ), array( '%d' ), array( '%d' ) );
	mags_erp_write_profile(
		$student_id,
		array( 'gender' => $admission->gender, 'dob' => $admission->dob ?: null, 'father_name' => $admission->father_name, 'mother_name' => $admission->mother_name, 'guardian_phone' => $admission->phone, 'emergency_phone' => $admission->phone, 'blood_group' => $admission->blood_group, 'address' => $admission->address, 'admitted_on' => mags_erp_today(), 'photo_file_id' => mags_erp_get_file( (int) $admission->photo_file_id ) ? (int) $admission->photo_file_id : 0 )
	);
	if ( mags_erp_get_file( (int) $admission->document_file_id ) ) { mags_erp_add_student_document( $student_id, 'Sent with the admission form', (int) $admission->document_file_id, 0 ); }

	mags_erp_flash( 'success', sprintf( 'Application %s accepted. The student is added with the profile from the form. Give these login details to the parent now. The password is not shown again.', mags_erp_admission_no_text( $admission->id ) ), array( 'name' => $admission->student_name, 'login_as' => 'Student / Parent', 'login' => $admission_no, 'password' => $created[1] ) );
	return mags_erp_url( 'profile', array( 'student' => $student_id ) );
}

/** Remove an application from the lists (hidden and kept, for forms sent by mistake or as a joke) or restore it. */
function mags_erp_action_delete_admission() {
	global $wpdb;
	$admission = mags_erp_get_admission( mags_erp_post_int( 'admission_id' ), true );
	if ( ! $admission ) { mags_erp_flash( 'error', 'That application no longer exists.' ); return mags_erp_url( 'admissions' ); }
	if ( mags_erp_post_int( 'restore' ) ) {
		$done = mags_erp_unhide( 'admissions', (int) $admission->id );
		mags_erp_flash( $done ? 'success' : 'error', $done ? 'Application ' . mags_erp_admission_no_text( $admission->id ) . ' restored.' : 'That application is not in the removed list.' );
		return mags_erp_url( 'admissions' );
	}
	if ( 'approved' === $admission->status ) { mags_erp_flash( 'error', 'An accepted application is kept with the student and cannot be removed.' ); return mags_erp_url( 'admissions', array( 'id' => (int) $admission->id ) ); }
	// Not through mags_erp_hide(): an application that was accepted a moment ago by someone else must stay.
	$done = $wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( 'admissions' ) . " SET hidden_at = %s, hidden_by = %d WHERE id = %d AND hidden_at IS NULL AND status <> 'approved'", current_time( 'mysql' ), get_current_user_id(), $admission->id ) );
	mags_erp_flash( $done ? 'success' : 'error', $done ? 'Application ' . mags_erp_admission_no_text( $admission->id ) . ' removed from the list. It is kept and can be restored.' : 'That application is already removed or accepted.' );
	return mags_erp_url( 'admissions' );
}
