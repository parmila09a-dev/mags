<?php
/**
 * Add many students at once from a CSV file (a spreadsheet saved as CSV).
 *
 * The whole file is checked first. If one line is wrong nothing is added and
 * the office is told which lines to correct. Students whose admission number
 * is already in the portal are skipped, so the same file can be uploaded again.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MAGS_ERP_IMPORT_MAX_ROWS', 500 );
define( 'MAGS_ERP_IMPORT_MAX_BYTES', MB_IN_BYTES );

/** field => header spellings it is recognised by (compared in small letters, letters only). */
function mags_erp_import_columns() {
	return array(
		'admission_no' => array( 'admissionno', 'admissionnumber', 'admno', 'admnno', 'admission', 'admissionid' ),
		'name'         => array( 'name', 'studentname', 'studentsname', 'fullname', 'student', 'nameofstudent' ),
		'class'        => array( 'class', 'classname', 'std', 'standard', 'grade' ),
		'section'      => array( 'section', 'sec' ),
		'roll_no'      => array( 'rollno', 'rollnumber', 'roll' ),
		'email'        => array( 'parentemail', 'parentsemail', 'email', 'emailid', 'emailaddress', 'guardianemail', 'fatheremail', 'motheremail' ),
	);
}

function mags_erp_import_sample_rows() {
	return array(
		array( 'Admission No', 'Name', 'Class', 'Section', 'Roll No', 'Parent Email' ),
		array( 'A1001', 'Aarav Sharma', 'Class 5', 'A', '1', 'parent.sharma@example.com' ),
		array( 'A1002', 'Diya Gupta', 'Class 5', 'A', '2', '' ),
		array( 'A1003', 'Kabir Singh', 'Class 6', '', '1', '' ),
	);
}

/** Compare class names the way people write them: "Class 5", "class-5", "5" and "Std 5" are the same. */
function mags_erp_import_class_key( $text ) {
	$key = preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( (string) $text ) ) );
	return preg_replace( '/^(class|standard|std|grade)(?=.)/', '', $key );
}

/** Find the class a line names. Returns the class id, 0 when not found, or -1 when two classes fit. */
function mags_erp_import_match_class( $class_text, $section_text, $classes ) {
	$wanted  = mags_erp_import_class_key( $class_text ) . '|' . mags_erp_import_class_key( $section_text );
	$joined  = mags_erp_import_class_key( $class_text . $section_text );
	$matches = array();
	foreach ( $classes as $class ) {
		if ( mags_erp_import_class_key( $class->name ) . '|' . mags_erp_import_class_key( $class->section ) === $wanted ) { $matches[ (int) $class->id ] = true; }
	}
	if ( ! $matches ) {
		// "Class 5 - A" written in the class column alone.
		foreach ( $classes as $class ) {
			if ( mags_erp_import_class_key( $class->name . $class->section ) === $joined ) { $matches[ (int) $class->id ] = true; }
		}
	}
	if ( ! $matches ) { return 0; }
	return 1 === count( $matches ) ? (int) array_key_first( $matches ) : -1;
}

/**
 * Read the uploaded CSV. Returns array( rows, '' ) or array( array(), message ).
 * Each row is array( line, admission_no, name, class, section, roll_no, parent email ).
 */
function mags_erp_import_read( $path ) {
	$text = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( "\xEF\xBB\xBF" === substr( $text, 0, 3 ) ) {
		$text = substr( $text, 3 );
	} elseif ( in_array( substr( $text, 0, 2 ), array( "\xFF\xFE", "\xFE\xFF" ), true ) ) {
		$text = mb_convert_encoding( $text, 'UTF-8', 'UTF-16' );
		$text = preg_replace( '/^\x{FEFF}/u', '', $text );
	} elseif ( ! mb_check_encoding( $text, 'UTF-8' ) ) {
		$text = mb_convert_encoding( $text, 'UTF-8', 'Windows-1252' );
	}
	if ( '' === trim( $text ) ) { return array( array(), 'The file is empty.' ); }
	if ( false !== strpos( $text, "\0" ) ) { return array( array(), 'This is not a CSV file. In Excel choose File → Save As → CSV (Comma delimited).' ); }

	$first     = strtok( $text, "\r\n" );
	$delimiter = ',';
	foreach ( array( ';', "\t" ) as $candidate ) { if ( substr_count( $first, $candidate ) > substr_count( $first, $delimiter ) ) { $delimiter = $candidate; } }

	$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $handle, $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	rewind( $handle );
	$header = fgetcsv( $handle, 0, $delimiter, '"', '' );
	$map    = array();
	foreach ( (array) $header as $index => $cell ) {
		$key = preg_replace( '/[^a-z]+/', '', strtolower( (string) $cell ) );
		foreach ( mags_erp_import_columns() as $field => $spellings ) { if ( ! isset( $map[ $field ] ) && in_array( $key, $spellings, true ) ) { $map[ $field ] = $index; } }
	}
	foreach ( array( 'admission_no' => 'Admission No', 'name' => 'Name', 'class' => 'Class' ) as $field => $label ) {
		if ( ! isset( $map[ $field ] ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return array( array(), sprintf( 'The first line of the file must name the columns. The column “%s” is missing. Download the sample file to see the layout.', $label ) );
		}
	}

	$rows = array();
	$line = 1;
	while ( false !== ( $cells = fgetcsv( $handle, 0, $delimiter, '"', '' ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
		$line++;
		$cells = array_map( static function ( $cell ) { return trim( sanitize_text_field( (string) $cell ) ); }, (array) $cells );
		if ( '' === implode( '', $cells ) ) { continue; }
		$value  = static function ( $field ) use ( $map, $cells ) { return isset( $map[ $field ] ) ? ( $cells[ $map[ $field ] ] ?? '' ) : ''; };
		$rows[] = array( $line, $value( 'admission_no' ), $value( 'name' ), $value( 'class' ), $value( 'section' ), $value( 'roll_no' ), $value( 'email' ) );
		if ( count( $rows ) > MAGS_ERP_IMPORT_MAX_ROWS ) { break; }
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $rows ) { return array( array(), 'The file has the column names but no students under them.' ); }
	if ( count( $rows ) > MAGS_ERP_IMPORT_MAX_ROWS ) { return array( array(), sprintf( 'The file has more than %d students. Split it into smaller files, for example one per class.', MAGS_ERP_IMPORT_MAX_ROWS ) ); }
	return array( $rows, '' );
}

/**
 * Check every line. Returns array( students to add, lines to correct, number already in the portal ).
 * A student to add is array( admission_no, name, class_id, roll_no, parent email ).
 */
function mags_erp_import_check( $rows ) {
	$classes = mags_erp_get_classes();
	$seen    = array();
	$ready   = array();
	$errors  = array();
	$exists  = 0;
	foreach ( $rows as $row ) {
		list( $line, $admission_no, $name, $class_text, $section_text, $roll_no, $email ) = $row;
		$problem = '';
		$class_id = mags_erp_import_match_class( $class_text, $section_text, $classes );
		if ( ! mags_erp_valid_admission_no( $admission_no ) ) {
			$problem = '' === $admission_no ? 'the admission number is empty' : sprintf( 'the admission number “%s” can use letters, numbers and / . - _ only, up to 30 characters', $admission_no );
		} elseif ( isset( $seen[ strtolower( $admission_no ) ] ) ) {
			$problem = sprintf( 'the admission number “%s” is also on line %d', $admission_no, $seen[ strtolower( $admission_no ) ] );
		} elseif ( '' === $name ) {
			$problem = 'the name is empty';
		} elseif ( mb_strlen( $name ) > 120 ) {
			$problem = 'the name is too long';
		} elseif ( '' === $class_text ) {
			$problem = 'the class is empty';
		} elseif ( 0 === $class_id ) {
			$problem = sprintf( 'the class “%s” is not in the portal. Add it under Classes or correct the spelling', trim( $class_text . ' ' . $section_text ) );
		} elseif ( -1 === $class_id ) {
			$problem = sprintf( 'the class “%s” fits more than one class. Fill the Section column', $class_text );
		} elseif ( mb_strlen( $roll_no ) > 10 ) {
			$problem = 'the roll number is too long';
		} elseif ( '' !== $email && ( ! is_email( $email ) || mb_strlen( $email ) > 120 ) ) {
			$problem = sprintf( 'the parent email “%s” is not a correct email address', $email );
		}
		if ( mags_erp_valid_admission_no( $admission_no ) && ! isset( $seen[ strtolower( $admission_no ) ] ) ) { $seen[ strtolower( $admission_no ) ] = $line; }
		if ( '' !== $problem ) { $errors[] = sprintf( 'Line %d: %s.', $line, $problem ); continue; }
		if ( mags_erp_get_student_by_admission( $admission_no ) ) { $exists++; continue; }
		$ready[] = array( $admission_no, $name, $class_id, $roll_no, $email );
	}
	return array( $ready, $errors, $exists );
}

function mags_erp_action_import_students() {
	$back   = mags_erp_url( 'import' );
	$upload = isset( $_FILES['students_csv'] ) && is_array( $_FILES['students_csv'] ) ? $_FILES['students_csv'] : null; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $upload || ! isset( $upload['error'] ) || is_array( $upload['error'] ) || UPLOAD_ERR_NO_FILE === (int) $upload['error'] ) { mags_erp_flash( 'error', 'Choose the CSV file with the students.' ); return $back; }
	if ( UPLOAD_ERR_OK !== (int) $upload['error'] || empty( $upload['tmp_name'] ) || ! is_uploaded_file( $upload['tmp_name'] ) ) { mags_erp_flash( 'error', 'The file could not be uploaded. Please try again.' ); return $back; }
	if ( filesize( $upload['tmp_name'] ) > MAGS_ERP_IMPORT_MAX_BYTES ) { mags_erp_flash( 'error', sprintf( 'The file is too large. The limit is %s.', size_format( MAGS_ERP_IMPORT_MAX_BYTES ) ) ); return $back; }
	if ( ! preg_match( '/\.(csv|txt)$/i', (string) $upload['name'] ) ) { mags_erp_flash( 'error', 'Upload a CSV file. In Excel choose File → Save As → CSV (Comma delimited).' ); return $back; }

	list( $rows, $message ) = mags_erp_import_read( $upload['tmp_name'] );
	if ( '' !== $message ) { mags_erp_flash( 'error', $message ); return $back; }
	list( $ready, $errors, $exists ) = mags_erp_import_check( $rows );
	if ( $errors ) {
		$shown = array_slice( $errors, 0, 25 );
		if ( count( $errors ) > 25 ) { $shown[] = sprintf( '… and %d more lines.', count( $errors ) - 25 ); }
		mags_erp_flash( 'error', 'No student was added. Correct these lines in the file and upload it again.', array( 'lines' => $shown ) );
		return $back;
	}
	if ( ! $ready ) { mags_erp_flash( 'success', sprintf( 'Nothing to add: all %d students of this file are already in the portal.', $exists ) ); return $back; }

	// Creating a login takes a moment. Stop before the server's time limit; the rest is added by uploading the file again.
	$limit    = (int) ini_get( 'max_execution_time' );
	$deadline = microtime( true ) + ( $limit > 0 ? max( 5, $limit * 0.6 ) : 120 );
	$labels   = mags_erp_all_class_labels();
	$logins   = array();
	$failed   = 0;
	$waiting  = 0;
	foreach ( $ready as $index => $student ) {
		if ( microtime( true ) > $deadline ) { $waiting = count( $ready ) - $index; break; }
		$created = mags_erp_create_student( $student[0], $student[1], $student[2], $student[3] );
		if ( is_wp_error( $created ) ) { $failed++; continue; }
		if ( '' !== $student[4] ) { mags_erp_write_profile( (int) $created[0], array( 'parent_email' => $student[4] ) ); }
		$logins[] = array( $student[1], $labels[ $student[2] ] ?? '', $student[0], $created[1] );
	}

	$notes = array();
	if ( $exists ) { $notes[] = sprintf( '%d were already in the portal and were left as they are.', $exists ); }
	if ( $failed ) { $notes[] = sprintf( '%d could not be saved. Upload the file again to retry them.', $failed ); }
	if ( $waiting ) { $notes[] = sprintf( '%d are still waiting because the server stopped for time. Upload the same file again to add them; students already added are skipped.', $waiting ); }
	mags_erp_flash( $logins ? 'success' : 'error', sprintf( _n( '%d student added.', '%d students added.', count( $logins ), 'mags-erp' ), count( $logins ) ) . ( $logins ? ' Save the login details below now: the passwords are not shown again.' : '' ), array( 'lines' => $notes, 'logins' => $logins ) );
	return $back;
}
