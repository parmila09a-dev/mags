<?php
/**
 * Student profile: personal details the office keeps for each student (parents,
 * date of birth, blood group, emergency number, address), a photo and scanned
 * documents.
 *
 * The office fills and changes the profile. A student sees their own details
 * and photo but cannot change them, and does not see the documents. Teachers
 * do not see profiles. Photos and documents are private files like homework:
 * they are handed out only through /portal/file/ after the access check below.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Photos are cut to this size (4:5, like a passport photo) and saved as JPEG. */
define( 'MAGS_ERP_PHOTO_WIDTH', 480 );
define( 'MAGS_ERP_PHOTO_HEIGHT', 600 );

/** Largest picture taken as a photo. A bigger one could use up the server's memory while it is opened. */
define( 'MAGS_ERP_PHOTO_MAX_PIXELS', 25000000 );

/** Most documents one student can have. */
define( 'MAGS_ERP_STUDENT_DOCUMENTS', 20 );

/** The profile of a student. A student without a saved profile gets empty values. */
function mags_erp_get_profile( $student_id ) {
	global $wpdb;
	$profile = $student_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'student_profiles' ) . ' WHERE student_id = %d', $student_id ) ) : null;
	if ( $profile ) { return $profile; }
	return (object) array( 'student_id' => (int) $student_id, 'gender' => '', 'dob' => null, 'father_name' => '', 'mother_name' => '', 'guardian_phone' => '', 'parent_email' => '', 'emergency_name' => '', 'emergency_phone' => '', 'blood_group' => '', 'address' => '', 'admitted_on' => null, 'photo_file_id' => 0, 'updated_by' => 0, 'updated_at' => null );
}

/** Write profile values ($data is column => value) for a student, creating the row when needed. */
function mags_erp_write_profile( $student_id, array $data ) {
	global $wpdb;
	$table   = mags_erp_table( 'student_profiles' );
	$formats = array( 'photo_file_id' => '%d', 'updated_by' => '%d' );
	$data    = array_merge( $data, array( 'updated_by' => get_current_user_id(), 'updated_at' => current_time( 'mysql' ) ) );
	$format  = array();
	foreach ( array_keys( $data ) as $column ) { $format[] = $formats[ $column ] ?? '%s'; }

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT student_id FROM {$table} WHERE student_id = %d", $student_id ) );
	if ( $exists ) { return false !== $wpdb->update( $table, $data, array( 'student_id' => (int) $student_id ), $format, array( '%d' ) ); }
	return (bool) $wpdb->insert( $table, array_merge( array( 'student_id' => (int) $student_id ), $data ), array_merge( array( '%d' ), $format ) );
}

/**
 * Check profile values typed in a form. $values has gender, dob, father_name,
 * mother_name, guardian_phone, parent_email, emergency_name, emergency_phone,
 * blood_group, address and admitted_on as text. Returns the clean column => value list or a WP_Error.
 */
function mags_erp_clean_profile( array $values ) {
	$today = mags_erp_today();
	$clean = array();
	$clean['gender'] = isset( mags_erp_genders()[ $values['gender'] ?? '' ] ) ? $values['gender'] : '';
	$clean['blood_group'] = in_array( $values['blood_group'] ?? '', mags_erp_blood_groups(), true ) ? $values['blood_group'] : '';

	foreach ( array( 'dob' => 'The date of birth', 'admitted_on' => 'The date of admission' ) as $key => $label ) {
		$date = (string) ( $values[ $key ] ?? '' );
		if ( '' !== $date && ( ! mags_erp_valid_date( $date ) || $date < '1900-01-01' || $date > $today ) ) { return new WP_Error( 'mags_erp_profile', $label . ' is not a valid date (it cannot be in the future).' ); }
		$clean[ $key ] = '' === $date ? null : $date;
	}
	if ( $clean['dob'] && $clean['admitted_on'] && $clean['admitted_on'] < $clean['dob'] ) { return new WP_Error( 'mags_erp_profile', 'The date of admission cannot be before the date of birth.' ); }

	foreach ( array( 'father_name' => 'The father’s name', 'mother_name' => 'The mother’s name', 'emergency_name' => 'The emergency contact’s name' ) as $key => $label ) {
		$clean[ $key ] = (string) ( $values[ $key ] ?? '' );
		if ( mb_strlen( $clean[ $key ] ) > 120 ) { return new WP_Error( 'mags_erp_profile', $label . ' is too long.' ); }
	}
	foreach ( array( 'guardian_phone' => 'The parent’s phone number', 'emergency_phone' => 'The emergency number' ) as $key => $label ) {
		$typed         = (string) ( $values[ $key ] ?? '' );
		$clean[ $key ] = mags_erp_clean_phone( $typed );
		if ( '' !== $typed && '' === $clean[ $key ] ) { return new WP_Error( 'mags_erp_profile', $label . ' must have 10 to 15 digits.' ); }
	}
	$clean['parent_email'] = trim( (string) ( $values['parent_email'] ?? '' ) );
	if ( '' !== $clean['parent_email'] && ( ! is_email( $clean['parent_email'] ) || mb_strlen( $clean['parent_email'] ) > 120 ) ) { return new WP_Error( 'mags_erp_profile', 'The parent’s email address is not correct. You can also leave it empty.' ); }
	$clean['address'] = trim( preg_replace( '/\s+/', ' ', (string) ( $values['address'] ?? '' ) ) );
	if ( mb_strlen( $clean['address'] ) > 500 ) { return new WP_Error( 'mags_erp_profile', 'The address is too long (500 characters at most).' ); }
	return $clean;
}

/** "Son", "Daughter" or "Son / Daughter", and the matching "he", "she" or "he / she", for certificates. */
function mags_erp_gender_words( $gender ) {
	if ( 'boy' === $gender ) { return array( 'child' => 'son', 'he' => 'he', 'his' => 'his', 'him' => 'him' ); }
	if ( 'girl' === $gender ) { return array( 'child' => 'daughter', 'he' => 'she', 'his' => 'her', 'him' => 'her' ); }
	return array( 'child' => 'son / daughter', 'he' => 'he / she', 'his' => 'his / her', 'him' => 'him / her' );
}

/* ---------- Photo ---------- */

/** Can this server cut and save photos? (WordPress needs the GD or Imagick extension for it.) */
function mags_erp_photo_supported() {
	return (bool) wp_image_editor_supports( array( 'mime_type' => 'image/jpeg', 'methods' => array( 'crop', 'resize', 'save' ) ) );
}

/**
 * Take the photo posted under $field, turn it upright, cut it to 4:5 and save
 * it as a JPEG in the private folder. Saving it again as a new image also
 * drops anything hidden inside the uploaded file (place, camera data).
 * Returns the new file id, 0 when no photo was chosen, or a WP_Error.
 */
function mags_erp_store_photo( $field ) {
	global $wpdb;
	$upload = isset( $_FILES[ $field ] ) && is_array( $_FILES[ $field ] ) ? $_FILES[ $field ] : null; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $upload || ! isset( $upload['error'] ) || is_array( $upload['error'] ) || UPLOAD_ERR_NO_FILE === (int) $upload['error'] ) { return 0; }

	$max = mags_erp_max_file_size();
	if ( in_array( (int) $upload['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) { return new WP_Error( 'mags_erp_photo', sprintf( 'The photo is too large. The limit is %s.', size_format( $max ) ) ); }
	if ( UPLOAD_ERR_OK !== (int) $upload['error'] || empty( $upload['tmp_name'] ) || ! is_uploaded_file( $upload['tmp_name'] ) ) { return new WP_Error( 'mags_erp_photo', 'The photo could not be uploaded. Please try again.' ); }
	if ( filesize( $upload['tmp_name'] ) > $max ) { return new WP_Error( 'mags_erp_photo', sprintf( 'The photo is too large. The limit is %s.', size_format( $max ) ) ); }

	$check = wp_check_filetype_and_ext( $upload['tmp_name'], sanitize_file_name( wp_basename( (string) $upload['name'] ) ), array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
	$info  = @getimagesize( $upload['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( empty( $check['type'] ) || ! $info ) { return new WP_Error( 'mags_erp_photo', 'The photo must be a JPG or PNG picture.' ); }
	if ( $info[0] < 120 || $info[1] < 150 ) { return new WP_Error( 'mags_erp_photo', 'The photo is too small. It must be at least 120 by 150 pixels.' ); }
	if ( $info[0] * $info[1] > MAGS_ERP_PHOTO_MAX_PIXELS ) { return new WP_Error( 'mags_erp_photo', sprintf( 'The photo has too many pixels (more than %d megapixels). Please choose a smaller picture, or take it with the normal camera setting.', MAGS_ERP_PHOTO_MAX_PIXELS / 1000000 ) ); }
	if ( ! mags_erp_photo_supported() ) { return new WP_Error( 'mags_erp_photo', 'This server cannot process photos. Please tell the site administrator (the GD or Imagick extension is missing).' ); }

	$editor = wp_get_image_editor( $upload['tmp_name'], array( 'mime_type' => $check['type'] ) );
	if ( is_wp_error( $editor ) ) { return new WP_Error( 'mags_erp_photo', 'The photo could not be read. Please try another picture.' ); }
	// Make a large picture smaller first, so turning and cutting it needs little memory.
	$size = $editor->get_size();
	$done = max( $size['width'], $size['height'] ) > 1600 ? $editor->resize( 1600, 1600, false ) : true;
	if ( is_wp_error( $done ) ) { return new WP_Error( 'mags_erp_photo', 'The photo could not be processed. Please try another picture.' ); }
	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) { $editor->maybe_exif_rotate(); }

	// Cut the middle of the picture to 4:5 (a little above the middle when it is tall, where the face is), then make it smaller.
	$size   = $editor->get_size();
	$ratio  = MAGS_ERP_PHOTO_WIDTH / MAGS_ERP_PHOTO_HEIGHT;
	$crop_w = (int) min( $size['width'], round( $size['height'] * $ratio ) );
	$crop_h = (int) min( $size['height'], round( $size['width'] / $ratio ) );
	$done   = $editor->crop( (int) ( ( $size['width'] - $crop_w ) / 2 ), (int) ( ( $size['height'] - $crop_h ) * 0.35 ), $crop_w, $crop_h );
	if ( ! is_wp_error( $done ) && $crop_w > MAGS_ERP_PHOTO_WIDTH ) { $done = $editor->resize( MAGS_ERP_PHOTO_WIDTH, MAGS_ERP_PHOTO_HEIGHT, false ); }
	if ( is_wp_error( $done ) ) { return new WP_Error( 'mags_erp_photo', 'The photo could not be processed. Please try another picture.' ); }
	$editor->set_quality( 85 );

	$dir = mags_erp_private_dir();
	if ( ! is_dir( $dir ) || ! wp_is_writable( $dir ) ) { return new WP_Error( 'mags_erp_photo', 'The upload folder is not writable. Please tell the site administrator.' ); }
	$name  = bin2hex( random_bytes( 20 ) );
	$saved = $editor->save( $dir . '/' . $name . '.jpg', 'image/jpeg' );
	if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_file( $saved['path'] ) ) { return new WP_Error( 'mags_erp_photo', 'The photo could not be saved. Please try again.' ); }
	// Like every private file, the photo has no real extension on the server.
	$stored = $name . '.bin';
	if ( ! @rename( $saved['path'], $dir . '/' . $stored ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
		wp_delete_file( $saved['path'] );
		return new WP_Error( 'mags_erp_photo', 'The photo could not be saved. Please try again.' );
	}
	@chmod( $dir . '/' . $stored, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

	$inserted = $wpdb->insert(
		mags_erp_table( 'files' ),
		array( 'stored_name' => $stored, 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => (int) filesize( $dir . '/' . $stored ), 'uploaded_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ),
		array( '%s', '%s', '%s', '%d', '%d', '%s' )
	);
	if ( ! $inserted ) {
		wp_delete_file( $dir . '/' . $stored );
		return new WP_Error( 'mags_erp_photo', 'The photo could not be saved. Please try again.' );
	}
	return (int) $wpdb->insert_id;
}

/**
 * Give a student a new photo, or none with 0. A photo replaced by a new one is
 * deleted from the server; a photo taken off without a new one is kept, hidden.
 */
function mags_erp_set_student_photo( $student_id, $file_id ) {
	$old = (int) mags_erp_get_profile( $student_id )->photo_file_id;
	if ( ! mags_erp_write_profile( $student_id, array( 'photo_file_id' => (int) $file_id ) ) ) { return false; }
	if ( $old && $old !== (int) $file_id ) {
		if ( $file_id ) { mags_erp_delete_replaced_file( $old ); } else { mags_erp_hide_file( $old ); }
	}
	return true;
}

/** Path of a student's photo on the server for a PDF, or '' when there is none. */
function mags_erp_photo_path( $profile ) {
	$file = mags_erp_get_file( (int) $profile->photo_file_id );
	$path = mags_erp_file_path( $file );
	return $file && is_file( $path ) ? $path : '';
}

/** The photo as an image, or a box that says there is none. */
function mags_erp_photo_img( $file_id, $who, $class = 'erp-photo' ) {
	if ( mags_erp_get_file( (int) $file_id ) ) {
		echo '<img class="' . esc_attr( $class ) . '" src="' . esc_url( mags_erp_file_url( (int) $file_id ) ) . '" alt="' . esc_attr( 'Photo of ' . $who ) . '" width="96" height="120">';
	} else {
		echo '<span class="' . esc_attr( $class . ' erp-photo--none' ) . '" role="img" aria-label="' . esc_attr( 'No photo of ' . $who ) . '">No photo</span>';
	}
}

/* ---------- Documents ---------- */

/** Documents of one student, newest first; the removed ones when $removed is true. */
function mags_erp_student_documents( $student_id, $removed = false ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'student_documents' ) . ' WHERE student_id = %d AND hidden_at IS ' . ( $removed ? 'NOT NULL' : 'NULL' ) . ' ORDER BY id DESC', $student_id ) );
}

function mags_erp_get_student_document( $document_id ) {
	global $wpdb;
	return $document_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'student_documents' ) . ' WHERE id = %d', $document_id ) ) : null;
}

/** Add a document to a student. $added_by is 0 for a file the parent sent with the admission form. */
function mags_erp_add_student_document( $student_id, $title, $file_id, $added_by = null ) {
	global $wpdb;
	return (bool) $wpdb->insert( mags_erp_table( 'student_documents' ), array( 'student_id' => (int) $student_id, 'title' => $title, 'file_id' => (int) $file_id, 'added_by' => null === $added_by ? get_current_user_id() : (int) $added_by, 'created_at' => current_time( 'mysql' ) ), array( '%d', '%s', '%d', '%d', '%s' ) );
}

/* ---------- Who may open these files ---------- */

/**
 * Photos, student documents and the files of admission forms. The office may
 * open all of them; a student only their own photo; nobody else anything.
 * Once an application is accepted its files belong to the student, so they
 * follow the student's photo and documents (a removed document is not handed out).
 */
function mags_erp_user_can_open_record_file( $file_id, $user_id, $role ) {
	global $wpdb;
	$file_id = (int) $file_id;
	if ( 'student' === $role ) {
		$student = mags_erp_get_student_by_user( $user_id );
		return $student && $file_id === (int) mags_erp_get_profile( (int) $student->id )->photo_file_id;
	}
	if ( 'office' !== $role ) { return false; }
	if ( $wpdb->get_var( $wpdb->prepare( 'SELECT student_id FROM ' . mags_erp_table( 'student_profiles' ) . ' WHERE photo_file_id = %d LIMIT 1', $file_id ) ) ) { return true; }
	if ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'student_documents' ) . ' WHERE file_id = %d AND hidden_at IS NULL LIMIT 1', $file_id ) ) ) { return true; }
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . mags_erp_table( 'admissions' ) . " WHERE ( photo_file_id = %d OR document_file_id = %d ) AND hidden_at IS NULL AND status <> 'approved' LIMIT 1", $file_id, $file_id ) );
}

/* ---------- Office actions ---------- */

/** The student a profile form is about (removed students too), or null with an error message set. */
function mags_erp_posted_profile_student() {
	$student = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
	if ( ! $student ) { mags_erp_flash( 'error', 'That student no longer exists.' ); }
	return $student;
}

function mags_erp_action_save_profile() {
	$student = mags_erp_posted_profile_student();
	if ( ! $student ) { return mags_erp_url( 'profile' ); }
	$back = mags_erp_url( 'profile', array( 'student' => (int) $student->id ) );

	$values = array();
	foreach ( array( 'gender', 'dob', 'father_name', 'mother_name', 'guardian_phone', 'parent_email', 'emergency_name', 'emergency_phone', 'blood_group', 'admitted_on' ) as $key ) { $values[ $key ] = mags_erp_post_text( $key ); }
	$values['address'] = mags_erp_post_textarea( 'address', 600 );
	$clean = mags_erp_clean_profile( $values );
	if ( is_wp_error( $clean ) ) { mags_erp_flash( 'error', $clean->get_error_message() ); return $back; }

	$photo = mags_erp_store_photo( 'photo' );
	if ( is_wp_error( $photo ) ) { mags_erp_flash( 'error', $photo->get_error_message() . ' Nothing was saved.' ); return $back; }
	if ( ! mags_erp_write_profile( (int) $student->id, $clean ) ) {
		if ( $photo ) { mags_erp_discard_upload( $photo ); }
		mags_erp_flash( 'error', 'The profile could not be saved. Please try again.' );
		return $back;
	}
	if ( $photo ) { mags_erp_set_student_photo( (int) $student->id, $photo ); }
	mags_erp_flash( 'success', 'Profile saved.' . ( $photo ? ' The photo was changed.' : '' ) );
	return $back;
}

/** Take the photo off a profile. The picture is kept on the server, hidden. */
function mags_erp_action_remove_photo() {
	$student = mags_erp_posted_profile_student();
	if ( ! $student ) { return mags_erp_url( 'profile' ); }
	$had = (int) mags_erp_get_profile( (int) $student->id )->photo_file_id;
	if ( $had ) { mags_erp_set_student_photo( (int) $student->id, 0 ); }
	mags_erp_flash( $had ? 'success' : 'error', $had ? 'Photo removed.' : 'This student has no photo.' );
	return mags_erp_url( 'profile', array( 'student' => (int) $student->id ) );
}

function mags_erp_action_add_document() {
	$student = mags_erp_posted_profile_student();
	if ( ! $student ) { return mags_erp_url( 'profile' ); }
	$back  = mags_erp_url( 'profile', array( 'student' => (int) $student->id ) );
	$title = mags_erp_post_text( 'title' );
	if ( '' === $title ) { mags_erp_flash( 'error', 'Enter what the document is, such as Birth certificate.' ); return $back; }
	if ( mb_strlen( $title ) > 120 ) { mags_erp_flash( 'error', 'The document name is too long.' ); return $back; }
	if ( count( mags_erp_student_documents( (int) $student->id ) ) >= MAGS_ERP_STUDENT_DOCUMENTS ) { mags_erp_flash( 'error', sprintf( 'A student can have %d documents. Remove one to add another.', MAGS_ERP_STUDENT_DOCUMENTS ) ); return $back; }

	$file_id = mags_erp_store_upload( 'document_file' );
	if ( is_wp_error( $file_id ) ) { mags_erp_flash( 'error', $file_id->get_error_message() ); return $back; }
	if ( ! $file_id ) { mags_erp_flash( 'error', 'Choose the file of the document.' ); return $back; }
	if ( ! mags_erp_add_student_document( (int) $student->id, $title, $file_id ) ) {
		mags_erp_discard_upload( $file_id );
		mags_erp_flash( 'error', 'The document could not be saved. Please try again.' );
		return $back;
	}
	mags_erp_flash( 'success', 'Document added: ' . $title . '.' );
	return $back;
}

/** Remove a document (hidden and kept) or restore a removed one. */
function mags_erp_action_delete_document() {
	$document = mags_erp_get_student_document( mags_erp_post_int( 'document_id' ) );
	if ( ! $document ) { mags_erp_flash( 'error', 'That document no longer exists.' ); return mags_erp_url( 'profile' ); }
	if ( mags_erp_post_int( 'restore' ) ) {
		$done = mags_erp_unhide( 'student_documents', (int) $document->id );
		mags_erp_flash( $done ? 'success' : 'error', $done ? 'Document restored: ' . $document->title . '.' : 'That document is not in the removed list.' );
	} else {
		$done = mags_erp_hide( 'student_documents', (int) $document->id );
		mags_erp_flash( $done ? 'success' : 'error', $done ? 'Document removed: ' . $document->title . '. It is kept and can be restored.' : 'That document is already removed.' );
	}
	return mags_erp_url( 'profile', array( 'student' => (int) $document->student_id ) );
}
