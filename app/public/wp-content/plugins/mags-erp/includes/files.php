<?php
/**
 * Private file storage for homework, notes, profile photos and documents.
 *
 * Files live outside the Media Library in a folder that the web server is told
 * not to serve, under random names with no real extension. They reach the
 * browser only through the portal's /portal/file/ route after an access check.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mags_erp_private_dir() {
	$upload = wp_upload_dir( null, false );
	$dir    = trailingslashit( $upload['basedir'] ) . 'mags-erp-private';
	if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
	if ( is_dir( $dir ) ) {
		if ( ! file_exists( $dir . '/.htaccess' ) ) { file_put_contents( $dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n" ); }
		if ( ! file_exists( $dir . '/index.html' ) ) { file_put_contents( $dir . '/index.html', '' ); }
	}
	return $dir;
}

/** Extensions and MIME types a teacher or student may upload. */
function mags_erp_allowed_mimes() {
	return array(
		'pdf'      => 'application/pdf',
		'doc'      => 'application/msword',
		'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'ppt'      => 'application/vnd.ms-powerpoint',
		'pptx'     => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'xls'      => 'application/vnd.ms-excel',
		'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
		'txt'      => 'text/plain',
		'zip'      => 'application/zip',
	);
}

function mags_erp_allowed_extensions_label() {
	return 'PDF, Word, PowerPoint, Excel, JPG, PNG, WebP, TXT or ZIP';
}

/** Most files one student can send in a day (answers, and answers that replace an earlier one). */
define( 'MAGS_ERP_STUDENT_FILES_PER_DAY', 30 );

/** Files this account has uploaded today, counting the ones that were replaced later. */
function mags_erp_uploads_today( $user_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . mags_erp_table( 'files' ) . ' WHERE uploaded_by = %d AND created_at >= %s', $user_id, mags_erp_today() . ' 00:00:00' ) );
}

function mags_erp_max_file_size() {
	return (int) min( 10 * MB_IN_BYTES, wp_max_upload_size() ?: 10 * MB_IN_BYTES );
}

/** A stored file. A file that was replaced or removed is kept on disk but is no longer handed out. */
function mags_erp_get_file( $file_id ) {
	global $wpdb;
	return $file_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'files' ) . ' WHERE id = %d AND hidden_at IS NULL', $file_id ) ) : null;
}

function mags_erp_file_path( $file ) {
	return $file ? mags_erp_private_dir() . '/' . basename( $file->stored_name ) : '';
}

function mags_erp_file_url( $file_id ) {
	return mags_erp_url( 'file', array( 'id' => (int) $file_id ) );
}

function mags_erp_file_label( $file ) {
	return $file ? $file->original_name . ' (' . size_format( (int) $file->size, 1 ) . ')' : '';
}

/**
 * Validate and store the file posted under $field.
 * Returns the new file id, 0 when no file was chosen, or a WP_Error.
 */
function mags_erp_store_upload( $field ) {
	$upload = isset( $_FILES[ $field ] ) && is_array( $_FILES[ $field ] ) ? $_FILES[ $field ] : null; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $upload || ! isset( $upload['error'] ) || is_array( $upload['error'] ) ) { return 0; }
	return mags_erp_store_file( $upload );
}

/**
 * Validate and store every file posted under a field that accepts several files.
 * Returns the list of new file ids (empty when none was chosen) or a WP_Error;
 * after an error none of the files of this upload is kept.
 */
function mags_erp_store_uploads( $field, $limit ) {
	$posted  = isset( $_FILES[ $field ] ) && is_array( $_FILES[ $field ] ) ? $_FILES[ $field ] : null; // phpcs:ignore WordPress.Security.NonceVerification
	$uploads = array();
	if ( $posted && isset( $posted['error'] ) && is_array( $posted['error'] ) ) {
		foreach ( $posted['error'] as $index => $error ) {
			if ( is_array( $error ) || UPLOAD_ERR_NO_FILE === (int) $error ) { continue; }
			$uploads[] = array( 'name' => $posted['name'][ $index ] ?? '', 'tmp_name' => $posted['tmp_name'][ $index ] ?? '', 'size' => $posted['size'][ $index ] ?? 0, 'error' => $error );
		}
	} elseif ( $posted && isset( $posted['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $posted['error'] ) {
		$uploads[] = $posted;
	}
	if ( count( $uploads ) > $limit ) { return new WP_Error( 'mags_erp_file', sprintf( 'Too many files. You can add %d more.', $limit ) ); }

	$file_ids = array();
	foreach ( $uploads as $upload ) {
		$file_id = mags_erp_store_file( $upload );
		if ( is_wp_error( $file_id ) ) {
			array_map( 'mags_erp_discard_upload', $file_ids );
			return new WP_Error( 'mags_erp_file', sanitize_file_name( wp_basename( (string) $upload['name'] ) ) . ': ' . $file_id->get_error_message() );
		}
		if ( $file_id ) { $file_ids[] = $file_id; }
	}
	return $file_ids;
}

/** Store one entry of $_FILES. Returns the new file id, 0 when it is empty, or a WP_Error. */
function mags_erp_store_file( $upload ) {
	global $wpdb;
	if ( ! is_array( $upload ) || ! isset( $upload['error'] ) || is_array( $upload['error'] ) || UPLOAD_ERR_NO_FILE === (int) $upload['error'] ) { return 0; }

	$max = mags_erp_max_file_size();
	if ( in_array( (int) $upload['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) { return new WP_Error( 'mags_erp_file', sprintf( 'The file is too large. The limit is %s.', size_format( $max ) ) ); }
	if ( UPLOAD_ERR_OK !== (int) $upload['error'] || empty( $upload['tmp_name'] ) || ! is_uploaded_file( $upload['tmp_name'] ) ) { return new WP_Error( 'mags_erp_file', 'The file could not be uploaded. Please try again.' ); }
	if ( (int) $upload['size'] > $max || filesize( $upload['tmp_name'] ) > $max ) { return new WP_Error( 'mags_erp_file', sprintf( 'The file is too large. The limit is %s.', size_format( $max ) ) ); }

	$original = sanitize_file_name( wp_basename( (string) $upload['name'] ) );
	if ( '' === $original ) { $original = 'file'; }
	$check = wp_check_filetype_and_ext( $upload['tmp_name'], $original, mags_erp_allowed_mimes() );
	if ( empty( $check['ext'] ) || empty( $check['type'] ) ) { return new WP_Error( 'mags_erp_file', 'This file type is not allowed. Upload ' . mags_erp_allowed_extensions_label() . '.' ); }
	if ( ! empty( $check['proper_filename'] ) ) { $original = $check['proper_filename']; }
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $original ) > 200 ) { $original = mb_substr( $original, -200 ); }

	$dir = mags_erp_private_dir();
	if ( ! is_dir( $dir ) || ! wp_is_writable( $dir ) ) { return new WP_Error( 'mags_erp_file', 'The upload folder is not writable. Please tell the site administrator.' ); }

	$stored = bin2hex( random_bytes( 20 ) ) . '.bin';
	if ( ! @move_uploaded_file( $upload['tmp_name'], $dir . '/' . $stored ) ) { return new WP_Error( 'mags_erp_file', 'The file could not be saved. Please try again.' ); } // phpcs:ignore WordPress.PHP.NoSilencedErrors
	@chmod( $dir . '/' . $stored, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

	$saved = $wpdb->insert(
		mags_erp_table( 'files' ),
		array( 'stored_name' => $stored, 'original_name' => $original, 'mime' => $check['type'], 'size' => (int) filesize( $dir . '/' . $stored ), 'uploaded_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ),
		array( '%s', '%s', '%s', '%d', '%d', '%s' )
	);
	if ( ! $saved ) {
		wp_delete_file( $dir . '/' . $stored );
		return new WP_Error( 'mags_erp_file', 'The file could not be saved. Please try again.' );
	}
	return (int) $wpdb->insert_id;
}

/**
 * Take back a file that was uploaded a moment ago because the record it was
 * meant for could not be saved. Files that belong to a saved record are never
 * deleted; they are hidden with mags_erp_hide_file().
 */
function mags_erp_discard_upload( $file_id ) {
	global $wpdb;
	$file = mags_erp_get_file( $file_id );
	if ( ! $file ) { return; }
	$path = mags_erp_file_path( $file );
	if ( $path && is_file( $path ) ) { wp_delete_file( $path ); }
	$wpdb->delete( mags_erp_table( 'files' ), array( 'id' => (int) $file->id ), array( '%d' ) );
}

/** Stop handing out a file (for example an answer the student replaced). The file itself stays on disk. */
function mags_erp_hide_file( $file_id ) {
	return $file_id ? mags_erp_hide( 'files', (int) $file_id ) : false;
}

/** Upload-size error that arrives with an empty $_POST because PHP dropped the whole request body. */
function mags_erp_request_too_large() {
	$length = isset( $_SERVER['CONTENT_LENGTH'] ) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
	return $length > 0 && empty( $_POST ) && empty( $_FILES ) && $length > wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * May this user open this file? A file belongs to a homework, a note or a
 * submission, and each of those belongs to a class; or it is a profile photo,
 * a student document or a file of an admission form (see includes/profiles.php). Anything that was removed
 * (the homework, the attachment, the note) is not handed out until it is restored.
 */
function mags_erp_user_can_open_file( $file_id, $user_id ) {
	global $wpdb;
	$file_id = (int) $file_id;
	$role    = mags_erp_user_role( get_userdata( $user_id ) );
	if ( ! $file_id || '' === $role ) { return false; }
	// Profile photos, student documents and admission form files have their own, narrower rule.
	if ( mags_erp_user_can_open_record_file( $file_id, $user_id, $role ) ) { return true; }
	if ( ! in_array( $role, array( 'teacher', 'student' ), true ) ) { return false; }

	$student   = 'student' === $role ? mags_erp_get_student_by_user( $user_id ) : null;
	$class_ids = 'teacher' === $role ? mags_erp_teacher_class_ids( $user_id ) : ( $student ? array( (int) $student->class_id ) : array() );
	$in_class  = static function ( $class_id ) use ( $class_ids ) { return in_array( (int) $class_id, $class_ids, true ); };
	$homework  = mags_erp_table( 'homework' );

	$homework_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT h.class_id FROM ' . mags_erp_table( 'homework_files' ) . " f INNER JOIN {$homework} h ON h.id = f.homework_id WHERE f.file_id = %d AND f.hidden_at IS NULL AND h.hidden_at IS NULL", $file_id ) );
	foreach ( (array) $homework_rows as $row ) { if ( $in_class( $row->class_id ) ) { return true; } }
	$note_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT class_id FROM ' . mags_erp_table( 'notes' ) . ' WHERE file_id = %d AND hidden_at IS NULL', $file_id ) );
	foreach ( (array) $note_rows as $row ) { if ( $in_class( $row->class_id ) ) { return true; } }
	$submission_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT s.student_id, h.class_id FROM ' . mags_erp_table( 'submissions' ) . " s INNER JOIN {$homework} h ON h.id = s.homework_id WHERE s.file_id = %d AND h.hidden_at IS NULL", $file_id ) );
	foreach ( (array) $submission_rows as $row ) {
		if ( $student && (int) $row->student_id === (int) $student->id ) { return true; }
		if ( 'teacher' === $role && $in_class( $row->class_id ) ) { return true; }
	}
	return false;
}

/** Send a stored file to the signed-in user, or a short "not found" page. */
function mags_erp_serve_file( $file_id ) {
	$file = mags_erp_get_file( $file_id );
	$path = mags_erp_file_path( $file );
	if ( ! $file || ! is_file( $path ) || ! mags_erp_user_can_open_file( $file->id, get_current_user_id() ) ) {
		status_header( 404 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'This file is not available for your account.';
		exit;
	}

	$inline = in_array( $file->mime, array( 'application/pdf', 'image/jpeg', 'image/png', 'image/webp' ), true );
	$ascii  = preg_replace( '/[^A-Za-z0-9._-]+/', '_', remove_accents( $file->original_name ) ) ?: 'file';
	while ( ob_get_level() ) { ob_end_clean(); }
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: ' . $file->mime );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Security-Policy: sandbox' );
	header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode( $file->original_name ) );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
