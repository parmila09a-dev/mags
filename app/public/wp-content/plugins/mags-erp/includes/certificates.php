<?php
/**
 * Certificates the office makes for a student: bonafide certificate, character
 * certificate and transfer certificate (TC), each downloaded as a PDF.
 *
 * A certificate gets a serial number and keeps a copy of everything printed on
 * it, so downloading it again later gives the same paper even when the
 * student's profile has changed since. A wrong certificate is cancelled, not
 * deleted; its number is not used again.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** kind => array( name, short code used in the serial number ). */
function mags_erp_certificate_kinds() {
	return array(
		'bonafide'  => array( 'Bonafide Certificate', 'BC' ),
		'character' => array( 'Character Certificate', 'CC' ),
		'tc'        => array( 'Transfer Certificate', 'TC' ),
	);
}

function mags_erp_conduct_words() {
	return array( 'Excellent', 'Very good', 'Good', 'Satisfactory' );
}

/** One certificate; a cancelled one only when $with_cancelled is true. ->data is the printed values as an array. */
function mags_erp_get_certificate( $certificate_id, $with_cancelled = false ) {
	global $wpdb;
	$certificate = $certificate_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'certificates' ) . ' WHERE id = %d', $certificate_id ) ) : null;
	if ( ! $certificate || ( $certificate->hidden_at && ! $with_cancelled ) ) { return null; }
	$certificate->data = (array) json_decode( (string) $certificate->data, true );
	return $certificate;
}

/** Certificates of one student, newest first, cancelled ones included. */
function mags_erp_student_certificates( $student_id ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT id, student_id, kind, serial_no, issued_on, created_by, hidden_at FROM ' . mags_erp_table( 'certificates' ) . ' WHERE student_id = %d ORDER BY id DESC', $student_id ) );
}

/** The office register: newest certificates of every student, with optional kind and search in name, admission number and serial number. */
function mags_erp_query_certificates( $kind, $search, $limit = 200 ) {
	global $wpdb;
	$where  = '1=1';
	$values = array();
	if ( isset( mags_erp_certificate_kinds()[ $kind ] ) ) { $where .= ' AND c.kind = %s'; $values[] = $kind; }
	if ( '' !== $search ) {
		$like   = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= ' AND ( s.full_name LIKE %s OR s.admission_no LIKE %s OR c.serial_no LIKE %s )';
		array_push( $values, $like, $like, $like );
	}
	$values[] = $limit;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT c.id, c.student_id, c.kind, c.serial_no, c.issued_on, c.created_by, c.hidden_at, s.full_name, s.admission_no FROM ' . mags_erp_table( 'certificates' ) . ' c INNER JOIN ' . mags_erp_table( 'students' ) . " s ON s.id = c.student_id WHERE {$where} ORDER BY c.id DESC LIMIT %d", $values ) );
}

/**
 * Make a certificate. $fields are the values typed by the office for this kind.
 * Returns the certificate id or a WP_Error that says what is missing.
 */
function mags_erp_issue_certificate( $student, $kind, array $fields ) {
	global $wpdb;
	$kinds = mags_erp_certificate_kinds();
	if ( ! isset( $kinds[ $kind ] ) ) { return new WP_Error( 'mags_erp_certificate', 'Choose the kind of certificate.' ); }
	$profile = mags_erp_get_profile( (int) $student->id );
	if ( '' === $profile->father_name && '' === $profile->mother_name ) { return new WP_Error( 'mags_erp_certificate', 'Fill the father’s or mother’s name in the profile first. It is printed on the certificate.' ); }
	if ( ! $profile->dob && 'character' !== $kind ) { return new WP_Error( 'mags_erp_certificate', 'Fill the date of birth in the profile first. It is printed on the certificate.' ); }
	if ( 'bonafide' === $kind && $student->hidden_at ) { return new WP_Error( 'mags_erp_certificate', 'A bonafide certificate is for a student who is in the school now. This student was removed.' ); }

	$today   = mags_erp_today();
	$session = mags_erp_session_range( $today );
	$data    = array(
		'school'    => mags_erp_school_details(),
		'session'   => $session['label'],
		'issued_on' => mags_erp_format_date( $today ),
		'student'   => array(
			'name'         => $student->full_name,
			'admission_no' => $student->admission_no,
			'class'        => mags_erp_all_class_labels()[ (int) $student->class_id ] ?? '',
			'gender'       => $profile->gender,
			'father'       => $profile->father_name,
			'mother'       => $profile->mother_name,
			'dob'          => $profile->dob ? mags_erp_format_date( $profile->dob ) : '',
			'dob_words'    => $profile->dob ? mags_erp_date_words( $profile->dob ) : '',
			'admitted_on'  => $profile->admitted_on ? mags_erp_format_date( $profile->admitted_on ) : '',
			'left'         => (bool) $student->hidden_at,
		),
	);

	if ( 'bonafide' === $kind ) {
		$data['purpose'] = (string) ( $fields['purpose'] ?? '' );
		if ( mb_strlen( $data['purpose'] ) > 160 ) { return new WP_Error( 'mags_erp_certificate', 'The purpose is too long (160 characters at most).' ); }
	} else {
		$data['conduct'] = in_array( $fields['conduct'] ?? '', mags_erp_conduct_words(), true ) ? $fields['conduct'] : 'Good';
		$data['remarks'] = (string) ( $fields['remarks'] ?? '' );
		if ( mb_strlen( $data['remarks'] ) > 300 ) { return new WP_Error( 'mags_erp_certificate', 'The remarks are too long (300 characters at most).' ); }
	}
	if ( 'tc' === $kind ) {
		$leaving = (string) ( $fields['leaving_date'] ?? '' );
		if ( ! mags_erp_valid_date( $leaving ) || $leaving > $today ) { return new WP_Error( 'mags_erp_certificate', 'Choose the date of leaving the school: today or an earlier date.' ); }
		if ( $profile->admitted_on && $leaving < $profile->admitted_on ) { return new WP_Error( 'mags_erp_certificate', 'The date of leaving cannot be before the date of admission.' ); }
		$promoted = array( 'yes' => 'Yes', 'no' => 'No', 'na' => 'Not applicable' );
		$counts   = mags_erp_student_summary( (int) $student->id, mags_erp_session_range( $leaving )['start'], $leaving );
		$fees     = mags_erp_fee_summary( (int) $student->id );
		$data['leaving_date'] = mags_erp_format_date( $leaving );
		$data['reason']       = '' !== (string) ( $fields['reason'] ?? '' ) ? (string) $fields['reason'] : 'Parent’s request';
		$data['nationality']  = '' !== (string) ( $fields['nationality'] ?? '' ) ? (string) $fields['nationality'] : 'Indian';
		$data['promoted']     = $promoted[ $fields['promoted'] ?? '' ] ?? 'Not applicable';
		$data['dues_paid']    = $fees['left'] > 0 ? 'No. ' . mags_erp_money_plain( $fees['left'] ) . ' left to pay' : 'Yes';
		$data['working_days'] = array_sum( $counts );
		$data['present_days'] = $counts['P'];
		$data['session']      = mags_erp_session_range( $leaving )['label'];
		if ( mb_strlen( $data['reason'] ) > 160 || mb_strlen( $data['nationality'] ) > 40 ) { return new WP_Error( 'mags_erp_certificate', 'The reason or the nationality is too long.' ); }
	}

	// The serial number counts up inside one kind and one session. Two people saving at the same moment get different numbers.
	$table = mags_erp_table( 'certificates' );
	$code  = str_replace( '–', '-', $session['label'] );
	for ( $try = 0; $try < 5; $try++ ) {
		$seq   = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(seq) FROM {$table} WHERE kind = %s AND session = %s", $kind, $code ) );
		$quiet = $wpdb->suppress_errors();
		$saved = $wpdb->insert(
			$table,
			array( 'student_id' => (int) $student->id, 'kind' => $kind, 'session' => $code, 'seq' => $seq, 'serial_no' => sprintf( '%s/%s/%03d', $kinds[ $kind ][1], $code, $seq ), 'issued_on' => $today, 'data' => wp_json_encode( $data ), 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
		$wpdb->suppress_errors( $quiet );
		if ( $saved ) { return (int) $wpdb->insert_id; }
	}
	return new WP_Error( 'mags_erp_certificate', 'The certificate could not be saved. Please try again.' );
}

/* ---------- What is printed ---------- */

/** "son of A and B" as parts for a paragraph, the names in bold. */
function mags_erp_certificate_parents( $student ) {
	$words = mags_erp_gender_words( $student['gender'] ?? '' );
	$parts = array( array( ', ' . $words['child'] . ' of ', false ) );
	if ( '' !== $student['father'] ) { $parts[] = array( $student['father'], true ); }
	if ( '' !== $student['father'] && '' !== $student['mother'] ) { $parts[] = array( ' and ', false ); }
	if ( '' !== $student['mother'] ) { $parts[] = array( $student['mother'], true ); }
	return $parts;
}

/**
 * The content of a certificate, the same for the PDF and the print page:
 * title, paragraphs (each a list of array( text, bold )), rows (list of
 * array( label, value )) and the signature names.
 */
function mags_erp_certificate_content( $certificate ) {
	$data    = $certificate->data;
	$student = (array) ( $data['student'] ?? array() ) + array( 'name' => '', 'admission_no' => '', 'class' => '', 'gender' => '', 'father' => '', 'mother' => '', 'dob' => '', 'dob_words' => '', 'admitted_on' => '', 'left' => false );
	$words   = mags_erp_gender_words( $student['gender'] );
	$session = (string) ( $data['session'] ?? '' );
	$content = array( 'title' => strtoupper( mags_erp_certificate_kinds()[ $certificate->kind ][0] ?? 'Certificate' ), 'paragraphs' => array(), 'rows' => array(), 'signatures' => array( 'Principal' ) );

	if ( 'bonafide' === $certificate->kind ) {
		$content['paragraphs'][] = array_merge(
			array( array( 'This is to certify that ', false ), array( $student['name'], true ) ),
			mags_erp_certificate_parents( $student ),
			array( array( ', is a bona fide student of this school. ' . ucfirst( $words['he'] ) . ' is studying in ', false ), array( $student['class'], true ), array( ' in the academic session ', false ), array( $session, true ), array( '. ' . ucfirst( $words['his'] ) . ' admission number is ', false ), array( $student['admission_no'], true ), array( ' and, according to the school records, ' . $words['his'] . ' date of birth is ', false ), array( $student['dob'], true ), array( '.', false ) )
		);
		$content['paragraphs'][] = '' !== (string) ( $data['purpose'] ?? '' )
			? array( array( 'This certificate is issued on request for the purpose of ', false ), array( $data['purpose'], true ), array( '.', false ) )
			: array( array( 'This certificate is issued on request.', false ) );
	} elseif ( 'character' === $certificate->kind ) {
		$content['paragraphs'][] = array_merge(
			array( array( 'This is to certify that ', false ), array( $student['name'], true ) ),
			mags_erp_certificate_parents( $student ),
			array( array( $student['left'] ? ', was a student of this school in ' : ', is a student of this school in ', false ), array( $student['class'], true ), array( ' during the academic session ', false ), array( $session, true ), array( '. ' . ucfirst( $words['his'] ) . ' admission number is ', false ), array( $student['admission_no'], true ), array( '.', false ) )
		);
		$content['paragraphs'][] = array( array( 'According to the school records and to the best of my knowledge, ' . $words['he'] . ' bears a good moral character. ' . ucfirst( $words['his'] ) . ' conduct in the school has been ', false ), array( strtolower( (string) ( $data['conduct'] ?? 'Good' ) ), true ), array( '.', false ) );
		if ( '' !== (string) ( $data['remarks'] ?? '' ) ) { $content['paragraphs'][] = array( array( $data['remarks'], false ) ); }
		$content['paragraphs'][] = array( array( 'I wish ' . $words['him'] . ' every success in life.', false ) );
	} else {
		$content['rows'] = array(
			array( 'Name of the pupil', $student['name'] ),
			array( 'Father’s name', '' !== $student['father'] ? $student['father'] : '-' ),
			array( 'Mother’s name', '' !== $student['mother'] ? $student['mother'] : '-' ),
			array( 'Nationality', (string) ( $data['nationality'] ?? '' ) ),
			array( 'Date of birth according to the admission register', $student['dob'] . ( '' !== $student['dob_words'] ? ' (' . $student['dob_words'] . ')' : '' ) ),
			array( 'Admission number', $student['admission_no'] ),
			array( 'Date of admission to the school', '' !== $student['admitted_on'] ? $student['admitted_on'] : '-' ),
			array( 'Class in which the pupil last studied', $student['class'] . ( '' !== $session ? ', session ' . $session : '' ) ),
			array( 'Whether qualified for promotion to the next class', (string) ( $data['promoted'] ?? '' ) ),
			array( 'Whether all school dues are paid', (string) ( $data['dues_paid'] ?? '' ) ),
			array( 'School days marked in the session and days present', (int) ( $data['working_days'] ?? 0 ) ? sprintf( '%d days marked, present on %d', (int) $data['working_days'], (int) ( $data['present_days'] ?? 0 ) ) : '-' ),
			array( 'Date of leaving the school', (string) ( $data['leaving_date'] ?? '' ) ),
			array( 'Reason for leaving the school', (string) ( $data['reason'] ?? '' ) ),
			array( 'General conduct', (string) ( $data['conduct'] ?? '' ) ),
			array( 'Any other remarks', '' !== (string) ( $data['remarks'] ?? '' ) ? $data['remarks'] : '-' ),
		);
		$content['paragraphs'][] = array( array( 'Certified that the above information is in accordance with the school records.', false ) );
		$content['signatures']   = array( 'Prepared by', 'Checked by', 'Principal' );
	}
	return $content;
}

/** Every text of a certificate, to see whether the PDF fonts can write it. */
function mags_erp_certificate_texts( $certificate, $content ) {
	return array( $certificate->data['school'] ?? array(), $certificate->serial_no, (string) ( $certificate->data['issued_on'] ?? '' ), $content['paragraphs'], $content['rows'] );
}

/** Draw a certificate. Returns the PDF file as a string. */
function mags_erp_certificate_pdf( $certificate, $content ) {
	$c      = mags_erp_pdf_colours();
	$left   = 60;
	$right  = MAGS_ERP_PDF::WIDTH - 60;
	$school = (array) ( $certificate->data['school'] ?? array() ) + array( 'name' => '', 'address' => '', 'phone' => '', 'email' => '' );

	$pdf = new MAGS_ERP_PDF();
	$pdf->add_page();
	$y = mags_erp_pdf_letterhead( $pdf, $school, $content['title'] ) + 14;
	$pdf->text( $left, $y, 'No. ' . $certificate->serial_no, 10.5, true, 'L', $c['navy'] );
	$pdf->text( $right, $y, 'Date: ' . (string) ( $certificate->data['issued_on'] ?? '' ), 10.5, true, 'R', $c['navy'] );
	$y += 12;
	$pdf->line( $left, $y, $right, $y, 0.6, $c['line'] );

	if ( $content['rows'] ) {
		$y += 8;
		foreach ( $content['rows'] as $index => $row ) {
			$labels = $pdf->wrap( $row[0], 10.5, false, 215 );
			$values = $pdf->wrap( $row[1], 10.5, true, $right - 310 );
			$height = max( count( $labels ), count( $values ), 1 ) * 14 + 10;
			if ( $y + $height > 770 ) {
				$pdf->add_page();
				$pdf->text( $left, 50, $content['title'] . ' ' . $certificate->serial_no . ' (continued)', 9, true, 'L', $c['muted'] );
				$y = 64;
			}
			if ( 0 === $index % 2 ) { $pdf->box( $left, $y, $right - $left, $height, array( 0.97, 0.975, 0.995 ) ); }
			$pdf->text( $left + 8, $y + 17, ( $index + 1 ) . '.', 10.5, false, 'L', $c['muted'] );
			foreach ( $labels as $n => $line ) { $pdf->text( $left + 30, $y + 17 + 14 * $n, $line, 10.5, false, 'L', $c['navy'] ); }
			foreach ( $values as $n => $line ) { $pdf->text( 310, $y + 17 + 14 * $n, $line, 10.5, true, 'L', $c['navy'] ); }
			$y += $height;
		}
		$y += 24;
		foreach ( $content['paragraphs'] as $parts ) { $y = $pdf->paragraph( $left, $y, $parts, 10.5, $right - $left, 16, $c['navy'] ) + 6; }
	} else {
		$y += 46;
		foreach ( $content['paragraphs'] as $parts ) { $y = $pdf->paragraph( $left, $y, $parts, 12.5, $right - $left, 25, $c['navy'] ) + 12; }
	}

	// The signatures stand near the foot of the page; when the text has used the page, they go to the top of the next one.
	$sign = max( $y + 64, 690 );
	if ( $sign > 786 ) {
		$pdf->add_page();
		$pdf->text( $left, 50, $content['title'] . ' ' . $certificate->serial_no . ' (continued)', 9, true, 'L', $c['muted'] );
		$sign = 150;
	}
	mags_erp_pdf_signatures( $pdf, $sign, $content['signatures'], $left, $right );
	if ( 1 === count( $content['signatures'] ) ) { $pdf->text( $left, $sign + 13, 'School seal', 9, false, 'L', $c['muted'] ); }
	$pdf->text( MAGS_ERP_PDF::WIDTH / 2, 806, 'Certificate ' . $certificate->serial_no . ', made from the school portal.', 8, false, 'C', $c['muted'] );
	return $pdf->output( $content['title'] . ' ' . $certificate->serial_no );
}

/** /portal/certificate/?id=… : the office downloads a certificate that is not cancelled. */
function mags_erp_serve_certificate() {
	$certificate = 'office' === mags_erp_user_role() ? mags_erp_get_certificate( mags_erp_get_int( 'id' ) ) : null;
	if ( ! $certificate ) { mags_erp_download_refused( 'This certificate is not available.' ); }
	$content = mags_erp_certificate_content( $certificate );

	if ( ! mags_erp_pdf_can_write_all( mags_erp_certificate_texts( $certificate, $content ) ) ) {
		$plain = array();
		foreach ( $content['paragraphs'] as $parts ) { $plain[] = preg_replace( '/\s+([,.])/', '$1', implode( '', wp_list_pluck( $parts, 0 ) ) ); }
		mags_erp_print_documents(
			array( array( 'school' => (array) ( $certificate->data['school'] ?? array() ), 'title' => $content['title'], 'serial' => 'No. ' . $certificate->serial_no . ' · Date: ' . ( $certificate->data['issued_on'] ?? '' ), 'paragraphs' => $content['rows'] ? array() : $plain, 'rows' => $content['rows'], 'note' => $content['rows'] ? implode( ' ', $plain ) : '', 'signatures' => $content['signatures'] ) ),
			$content['title'] . ' ' . $certificate->serial_no,
			mags_erp_url( 'profile', array( 'student' => (int) $certificate->student_id ) )
		);
	}
	mags_erp_send_pdf( mags_erp_certificate_pdf( $certificate, $content ), strtolower( str_replace( '/', '-', $certificate->serial_no ) ) . '.pdf' );
}

/* ---------- Office actions ---------- */

function mags_erp_action_issue_certificate() {
	$student = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
	if ( ! $student ) { mags_erp_flash( 'error', 'That student no longer exists.' ); return mags_erp_url( 'certificates' ); }
	$back   = mags_erp_url( 'profile', array( 'student' => (int) $student->id ) );
	$kind   = mags_erp_post_text( 'kind' );
	$fields = array();
	foreach ( array( 'purpose', 'conduct', 'remarks', 'leaving_date', 'reason', 'nationality', 'promoted' ) as $key ) { $fields[ $key ] = mags_erp_post_text( $key ); }

	$made = mags_erp_issue_certificate( $student, $kind, $fields );
	if ( is_wp_error( $made ) ) { mags_erp_flash( 'error', $made->get_error_message() ); return $back; }
	$certificate = mags_erp_get_certificate( $made );
	mags_erp_flash( 'success', sprintf( '%1$s %2$s made for %3$s. Download it from the list of certificates below.', mags_erp_certificate_kinds()[ $kind ][0], $certificate->serial_no, $student->full_name ) . ( 'tc' === $kind && ! $student->hidden_at ? ' The student is still in the class lists; remove the student under Students when they have left.' : '' ) );
	return $back . '#erp-certificates';
}

/** Cancel a certificate (kept, struck out, no longer downloadable) or make it valid again. */
function mags_erp_action_cancel_certificate() {
	$certificate = mags_erp_get_certificate( mags_erp_post_int( 'certificate_id' ), true );
	if ( ! $certificate ) { mags_erp_flash( 'error', 'That certificate no longer exists.' ); return mags_erp_url( 'certificates' ); }
	$restore = (bool) mags_erp_post_int( 'restore' );
	$done    = $restore ? mags_erp_unhide( 'certificates', (int) $certificate->id ) : mags_erp_hide( 'certificates', (int) $certificate->id );
	mags_erp_flash( $done ? 'success' : 'error', $done ? sprintf( $restore ? 'Certificate %s is valid again.' : 'Certificate %s cancelled. It is kept in the list and its number is not used again.', $certificate->serial_no ) : 'Nothing was changed.' );
	return 'certificates' === mags_erp_post_text( 'from' ) ? mags_erp_url( 'certificates' ) : mags_erp_url( 'profile', array( 'student' => (int) $certificate->student_id ) ) . '#erp-certificates';
}
