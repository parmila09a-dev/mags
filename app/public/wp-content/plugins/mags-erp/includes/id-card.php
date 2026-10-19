<?php
/**
 * Student ID card as a PDF, with the photo from the profile. One A4 page holds
 * the front and back of up to three cards at their real size (54 × 85.6 mm),
 * with cutting lines, so it can be printed at 100%, cut and laminated. The
 * office downloads the card of one student or of a whole class.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Size of the card in PDF points (1 mm = 2.8346 points). */
define( 'MAGS_ERP_CARD_WIDTH', 153.07 );
define( 'MAGS_ERP_CARD_HEIGHT', 242.65 );

/** Everything printed on one student's card. */
function mags_erp_id_card_data( $student, $school, $session, $labels ) {
	$profile = mags_erp_get_profile( (int) $student->id );
	return array(
		'school'    => $school,
		'session'   => $session,
		'student_id' => (int) $student->id,
		'name'      => $student->full_name,
		'class'     => $labels[ (int) $student->class_id ] ?? '',
		'admission' => $student->admission_no,
		'roll'      => $student->roll_no,
		'dob'       => $profile->dob ? mags_erp_format_date( $profile->dob, 'j M Y' ) : '',
		'blood'     => $profile->blood_group,
		'parent'    => '' !== $profile->father_name ? $profile->father_name : $profile->mother_name,
		'emergency' => trim( ( '' !== $profile->emergency_phone ? $profile->emergency_phone : $profile->guardian_phone ) . ( '' !== $profile->emergency_phone && '' !== $profile->emergency_name ? ' (' . $profile->emergency_name . ')' : '' ) ),
		'address'   => $profile->address,
		'photo'     => mags_erp_photo_path( $profile ),
		'photo_id'  => (int) $profile->photo_file_id,
	);
}

/** Draw the front of a card with its top left corner at $x, $y. */
function mags_erp_id_card_front( $pdf, $x, $y, $card ) {
	$c = mags_erp_pdf_colours();
	$w = MAGS_ERP_CARD_WIDTH;
	$h = MAGS_ERP_CARD_HEIGHT;
	$m = $x + $w / 2;

	$pdf->box( $x, $y, $w, 40, $c['navy'] );
	$pdf->box( $x, $y + 40, $w, 2.5, $c['orange'] );
	$name = array_slice( $pdf->wrap( $card['school']['name'], 9, true, $w - 14 ), 0, 2 );
	foreach ( $name as $index => $line ) { $pdf->text( $m, $y + ( 1 === count( $name ) ? 24 : 17 + 11 * $index ), $line, 9, true, 'C', $c['white'] ); }
	$pdf->text( $m, $y + 53, 'STUDENT IDENTITY CARD', 6.5, true, 'C', $c['muted'] );

	$pw = 70;
	$ph = 87.5;
	$px = $m - $pw / 2;
	$py = $y + 59;
	if ( '' === $card['photo'] || ! $pdf->jpeg( $card['photo'], $px, $py, $pw, $ph ) ) {
		$pdf->box( $px, $py, $pw, $ph, $c['soft'] );
		$pdf->text( $m, $py + $ph / 2 + 3, 'Photo', 8, false, 'C', $c['muted'] );
	}
	$pdf->rect( $px, $py, $pw, $ph, 0.8, $c['navy'] );

	$pdf->text( $m, $y + 163, $pdf->fit( $card['name'], 11, true, $w - 12 ), 11, true, 'C', $c['navy'] );
	$pdf->text( $m, $y + 177, $pdf->fit( $card['class'], 9.5, false, $w - 12 ), 9.5, false, 'C', $c['navy'] );
	$pdf->text( $x + 12, $y + 195, 'Adm. no.', 7.5, false, 'L', $c['muted'] );
	$pdf->text( $x + $w - 12, $y + 195, $pdf->fit( $card['admission'], 9, true, 90 ), 9, true, 'R', $c['navy'] );
	$pdf->text( $x + 12, $y + 208, 'Roll no.', 7.5, false, 'L', $c['muted'] );
	$pdf->text( $x + $w - 12, $y + 208, '' !== $card['roll'] ? $card['roll'] : '-', 9, true, 'R', $c['navy'] );

	$pdf->box( $x, $y + $h - 22, $w, 22, $c['navy'] );
	$pdf->text( $m, $y + $h - 8, 'Session ' . $card['session'], 8.5, true, 'C', $c['white'] );
	$pdf->rect( $x, $y, $w, $h, 0.5, array( 0.6, 0.62, 0.72 ), true );
}

/** Draw the back of a card with its top left corner at $x, $y. */
function mags_erp_id_card_back( $pdf, $x, $y, $card ) {
	$c = mags_erp_pdf_colours();
	$w = MAGS_ERP_CARD_WIDTH;
	$h = MAGS_ERP_CARD_HEIGHT;
	$at = $y + 16;
	$facts = array( 'DATE OF BIRTH' => $card['dob'], 'BLOOD GROUP' => $card['blood'], 'PARENT' => $card['parent'], 'EMERGENCY NUMBER' => $card['emergency'] );
	foreach ( $facts as $label => $value ) {
		$pdf->text( $x + 10, $at, $label, 5.5, true, 'L', $c['muted'] );
		$pdf->text( $x + 10, $at + 11, $pdf->fit( '' !== $value ? $value : '-', 9, true, $w - 20 ), 9, true, 'L', $c['navy'] );
		$at += 25;
	}
	$pdf->text( $x + 10, $at, 'ADDRESS', 5.5, true, 'L', $c['muted'] );
	$lines = '' !== $card['address'] ? array_slice( $pdf->wrap( $card['address'], 7.5, false, $w - 20 ), 0, 3 ) : array( '-' );
	foreach ( $lines as $index => $line ) { $pdf->text( $x + 10, $at + 10 + 9.5 * $index, $line, 7.5, false, 'L', $c['navy'] ); }

	$pdf->line( $x + $w - 70, $y + $h - 66, $x + $w - 10, $y + $h - 66, 0.6, $c['muted'] );
	$pdf->text( $x + $w - 40, $y + $h - 58, 'Principal', 6.5, false, 'C', $c['muted'] );

	$pdf->box( $x, $y + $h - 50, $w, 50, $c['soft'] );
	$pdf->text( $x + 10, $y + $h - 40, 'IF FOUND, PLEASE RETURN TO', 5.5, true, 'L', $c['muted'] );
	$pdf->text( $x + 10, $y + $h - 30, $pdf->fit( $card['school']['name'], 7.5, true, $w - 20 ), 7.5, true, 'L', $c['navy'] );
	$where = array_slice( $pdf->wrap( trim( $card['school']['address'] . ( '' !== $card['school']['phone'] ? ' Phone: ' . $card['school']['phone'] : '' ) ), 6.5, false, $w - 20 ), 0, 2 );
	foreach ( $where as $index => $line ) { $pdf->text( $x + 10, $y + $h - 20 + 8 * $index, $line, 6.5, false, 'L', $c['navy'] ); }
	$pdf->rect( $x, $y, $w, $h, 0.5, array( 0.6, 0.62, 0.72 ), true );
}

/** The cards of the given students, three to a page. Returns the PDF file as a string. */
function mags_erp_id_cards_pdf( $cards, $title ) {
	$c    = mags_erp_pdf_colours();
	$pdf  = new MAGS_ERP_PDF();
	$gap  = 24;
	$left = ( MAGS_ERP_PDF::WIDTH - 2 * MAGS_ERP_CARD_WIDTH - $gap ) / 2;
	foreach ( array_values( $cards ) as $index => $card ) {
		$slot = $index % 3;
		if ( 0 === $slot ) {
			$pdf->add_page();
			$pdf->text( MAGS_ERP_PDF::WIDTH / 2, 26, 'Print at 100% (actual size), cut along the dotted lines, fold or paste back to back. Front on the left, back on the right.', 7.5, false, 'C', $c['muted'] );
		}
		$top = 40 + $slot * ( MAGS_ERP_CARD_HEIGHT + 20 );
		mags_erp_id_card_front( $pdf, $left, $top, $card );
		mags_erp_id_card_back( $pdf, $left + MAGS_ERP_CARD_WIDTH + $gap, $top, $card );
	}
	return $pdf->output( $title );
}

/** /portal/id-card/?student=… or ?class=… : the office downloads ID cards of students who are in a class now. */
function mags_erp_serve_id_card() {
	if ( 'office' !== mags_erp_user_role() ) { mags_erp_download_refused(); }
	$class    = mags_erp_get_class( mags_erp_get_int( 'class' ) );
	$student  = mags_erp_get_int( 'student' ) ? mags_erp_get_student( mags_erp_get_int( 'student' ) ) : null;
	$students = $class ? mags_erp_class_students( (int) $class->id ) : ( $student && ! $student->hidden_at ? array( $student ) : array() );
	if ( ! $students ) { mags_erp_download_refused( 'There is no student to make an ID card for. A removed student has no ID card.' ); }

	$school  = mags_erp_school_details();
	$session = mags_erp_session_range()['label'];
	$labels  = mags_erp_all_class_labels();
	$cards   = array();
	foreach ( $students as $one ) { $cards[] = mags_erp_id_card_data( $one, $school, $session, $labels ); }
	$title = $class ? 'ID cards ' . mags_erp_class_label( $class ) : 'ID card ' . $students[0]->full_name;

	$texts = $cards;
	foreach ( $texts as $index => $card ) { unset( $texts[ $index ]['photo'] ); }
	if ( ! mags_erp_pdf_can_write_all( $texts ) ) {
		$documents = array();
		foreach ( $cards as $card ) {
			$documents[] = array(
				'title'  => 'STUDENT IDENTITY CARD',
				'serial' => 'Session ' . $card['session'],
				'photo'  => $card['photo_id'],
				'facts'  => array( 'Name' => $card['name'], 'Class' => $card['class'], 'Admission no.' => $card['admission'], 'Roll no.' => $card['roll'], 'Date of birth' => $card['dob'], 'Blood group' => $card['blood'], 'Parent' => $card['parent'], 'Emergency number' => $card['emergency'], 'Address' => $card['address'] ),
				'signatures' => array( 'Principal' ),
			);
		}
		mags_erp_print_documents( $documents, $title, $class ? mags_erp_url( 'students', array( 'class' => (int) $class->id ) ) : mags_erp_url( 'profile', array( 'student' => (int) $students[0]->id ) ) );
	}
	mags_erp_send_pdf( mags_erp_id_cards_pdf( $cards, $title ), sanitize_title( $class ? 'id-cards-' . mags_erp_class_label( $class ) : 'id-card-' . $students[0]->admission_no ) . '.pdf' );
}
