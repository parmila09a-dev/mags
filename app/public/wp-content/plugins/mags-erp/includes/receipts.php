<?php
/**
 * Fee receipt. After a payment is recorded its receipt can be downloaded as a
 * PDF: one A4 page with a copy for the parent and a copy for the office. The
 * office downloads any receipt; a student (and the parents, who use the same
 * login) the receipts of their own payments. A cancelled payment has no receipt.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Everything printed on the receipt of one payment. */
function mags_erp_receipt_data( $payment, $student ) {
	$profile = mags_erp_get_profile( (int) $student->id );
	$sums    = mags_erp_fee_breakdown( (int) $student->id );
	$summary = mags_erp_fee_summary( (int) $student->id );
	$modes   = mags_erp_payment_modes();
	$account = array( array( 'Total fees', mags_erp_money_plain( $sums['fee'] ) ) );
	if ( $sums['discount'] > 0 ) { $account[] = array( 'Discount', mags_erp_money_plain( -1 * $sums['discount'] ) ); }
	if ( $sums['fine'] > 0 ) { $account[] = array( 'Late fine', mags_erp_money_plain( $sums['fine'] ) ); }
	$account[] = array( 'Paid so far, with this receipt', mags_erp_money_plain( $summary['paid'] ) );
	$account[] = $summary['left'] < 0 ? array( 'Paid in advance', mags_erp_money_plain( -1 * $summary['left'] ) ) : array( 'Left to pay', mags_erp_money_plain( $summary['left'] ) );

	return array(
		'school'    => mags_erp_school_details(),
		'number'    => mags_erp_receipt_no( $payment ),
		'date'      => mags_erp_format_date( $payment->paid_on ),
		'student'   => $student->full_name,
		'class'     => mags_erp_all_class_labels()[ (int) $student->class_id ] ?? '',
		'admission' => $student->admission_no,
		'parent'    => '' !== $profile->father_name ? $profile->father_name : $profile->mother_name,
		'amount'    => mags_erp_money_plain( $payment->amount ),
		'words'     => mags_erp_amount_words( $payment->amount ),
		'mode'      => $modes[ $payment->mode ] ?? $payment->mode,
		'reference' => (string) $payment->reference,
		'note'      => (string) $payment->note,
		'account'   => $account,
		'made_on'   => mags_erp_format_date( mags_erp_today() ),
		'made_by'   => mags_erp_teacher_name( (int) $payment->recorded_by ),
	);
}

/** Draw one copy of the receipt with its top edge at $top. */
function mags_erp_receipt_draw( $pdf, $receipt, $top, $copy ) {
	$c     = mags_erp_pdf_colours();
	$left  = 50;
	$right = MAGS_ERP_PDF::WIDTH - 50;
	$mid   = MAGS_ERP_PDF::WIDTH / 2;
	$s     = $receipt['school'];

	$pdf->box( 0, $top, MAGS_ERP_PDF::WIDTH, 52, $c['navy'] );
	$pdf->box( 0, $top + 52, MAGS_ERP_PDF::WIDTH, 2, $c['orange'] );
	$pdf->text( $mid, $top + 22, $pdf->fit( $s['name'], 14, true, $right - $left ), 14, true, 'C', $c['white'] );
	if ( '' !== $s['address'] ) { $pdf->text( $mid, $top + 35, $pdf->fit( $s['address'], 7.5, false, $right - $left ), 7.5, false, 'C', array( 0.86, 0.87, 0.95 ) ); }
	if ( '' !== $s['phone'] ) { $pdf->text( $mid, $top + 45, $pdf->fit( 'Phone: ' . $s['phone'], 7.5, false, $right - $left ), 7.5, false, 'C', array( 0.86, 0.87, 0.95 ) ); }

	$pdf->text( $mid, $top + 74, 'FEE RECEIPT', 12, true, 'C', $c['navy'] );
	$pdf->text( $right, $top + 74, $copy, 8.5, true, 'R', $c['muted'] );
	$pdf->text( $left, $top + 94, 'Receipt no. ' . $receipt['number'], 10.5, true, 'L', $c['navy'] );
	$pdf->text( $right, $top + 94, 'Date: ' . $receipt['date'], 10.5, true, 'R', $c['navy'] );

	$pdf->box( $left, $top + 102, $right - $left, 56, $c['soft'] );
	$facts = array(
		array( $left + 12, 115, 'RECEIVED FOR STUDENT', $receipt['student'], 240 ),
		array( 330, 115, 'CLASS', $receipt['class'], 200 ),
		array( $left + 12, 139, 'ADMISSION NO.', $receipt['admission'], 240 ),
		array( 330, 139, 'PARENT', '' !== $receipt['parent'] ? $receipt['parent'] : '-', 200 ),
	);
	foreach ( $facts as $fact ) {
		$pdf->text( $fact[0], $top + $fact[1], $fact[2], 6.5, true, 'L', $c['muted'] );
		$pdf->text( $fact[0], $top + $fact[1] + 12, $pdf->fit( $fact[3], 10.5, true, $fact[4] ), 10.5, true, 'L', $c['navy'] );
	}

	$pdf->box( $left, $top + 166, $right - $left, 28, $c['navy'] );
	$pdf->text( $left + 12, $top + 184, 'Amount received', 10.5, true, 'L', $c['white'] );
	$pdf->text( $right - 12, $top + 185, $receipt['amount'], 14, true, 'R', $c['white'] );
	$y = $top + 208;
	foreach ( array_slice( $pdf->wrap( $receipt['words'], 9.5, false, $right - $left ), 0, 2 ) as $line ) {
		$pdf->text( $left, $y, $line, 9.5, false, 'L', $c['navy'] );
		$y += 12;
	}
	$how = 'Paid by: ' . $receipt['mode'] . ( '' !== $receipt['reference'] ? ' · Reference: ' . $receipt['reference'] : '' ) . ( '' !== $receipt['note'] ? ' · Note: ' . $receipt['note'] : '' );
	$pdf->text( $left, $y + 4, $pdf->fit( $how, 9.5, false, $right - $left ), 9.5, false, 'L', $c['navy'] );

	$y += 26;
	$pdf->text( $left, $y, 'FEE ACCOUNT ON ' . strtoupper( $receipt['made_on'] ), 7, true, 'L', $c['muted'] );
	foreach ( $receipt['account'] as $index => $row ) {
		$y += 14;
		$last = count( $receipt['account'] ) - 1 === $index;
		$pdf->text( $left, $y, $row[0], 9.5, $last, 'L', $c['navy'] );
		$pdf->text( 300, $y, $row[1], 9.5, $last, 'R', $c['navy'] );
	}
	$pdf->line( 380, $y - 6, $right, $y - 6, 0.8, $c['muted'] );
	$pdf->text( ( 380 + $right ) / 2, $y + 6, 'Received by (school office)', 8.5, false, 'C', $c['muted'] );
	$pdf->text( $mid, $y + 28, $pdf->fit( 'Recorded by ' . $receipt['made_by'] . '. Made from the school portal on ' . $receipt['made_on'] . '.', 7.5, false, $right - $left ), 7.5, false, 'C', $c['muted'] );
}

function mags_erp_receipt_pdf( $receipt ) {
	$pdf = new MAGS_ERP_PDF();
	$pdf->add_page();
	mags_erp_receipt_draw( $pdf, $receipt, 0, 'PARENT COPY' );
	$pdf->line( 20, 421, MAGS_ERP_PDF::WIDTH - 20, 421, 0.5, array( 0.6, 0.62, 0.72 ) );
	$pdf->text( MAGS_ERP_PDF::WIDTH / 2, 418, 'cut here', 6.5, false, 'C', array( 0.6, 0.62, 0.72 ) );
	mags_erp_receipt_draw( $pdf, $receipt, 430, 'OFFICE COPY' );
	return $pdf->output( 'Fee receipt ' . $receipt['number'] . ' - ' . $receipt['student'] );
}

/**
 * /portal/receipt/?id=… : the receipt of a payment that is not cancelled. The
 * office gets any receipt, a student only one of their own payments.
 */
function mags_erp_serve_receipt() {
	$role    = mags_erp_user_role();
	$payment = in_array( $role, array( 'office', 'student' ), true ) ? mags_erp_get_payment( mags_erp_get_int( 'id' ) ) : null;
	if ( $payment && 'student' === $role ) {
		$own     = mags_erp_get_student_by_user( get_current_user_id() );
		$payment = $own && (int) $own->id === (int) $payment->student_id ? $payment : null;
	}
	$student = $payment && ! $payment->hidden_at ? mags_erp_get_student( (int) $payment->student_id ) : null;
	if ( ! $student ) { mags_erp_download_refused( 'This receipt is not available. A cancelled payment has no receipt.' ); }
	$receipt = mags_erp_receipt_data( $payment, $student );

	if ( ! mags_erp_pdf_can_write_all( $receipt ) ) {
		mags_erp_print_documents(
			array(
				array(
					'title'      => 'FEE RECEIPT',
					'serial'     => 'Receipt no. ' . $receipt['number'] . ' · Date: ' . $receipt['date'],
					'facts'      => array( 'Student' => $receipt['student'], 'Class' => $receipt['class'], 'Admission no.' => $receipt['admission'], 'Parent' => $receipt['parent'] ),
					'rows'       => array_merge( array( array( 'Amount received', $receipt['amount'] ), array( 'In words', $receipt['words'] ), array( 'Paid by', $receipt['mode'] . ( '' !== $receipt['reference'] ? ' · ' . $receipt['reference'] : '' ) ) ), $receipt['account'] ),
					'note'       => 'Recorded by ' . $receipt['made_by'] . '.',
					'signatures' => array( 'Received by (school office)' ),
				),
			),
			'Fee receipt ' . $receipt['number'],
			'student' === $role ? mags_erp_url( 'fees' ) : mags_erp_url( 'fees', array( 'student' => (int) $student->id ) )
		);
	}
	mags_erp_send_pdf( mags_erp_receipt_pdf( $receipt ), 'receipt-' . $receipt['number'] . '-' . sanitize_title( $student->admission_no ) . '.pdf' );
}
