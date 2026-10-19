<?php
/**
 * Pieces shared by the documents the office downloads as PDF: certificates,
 * fee receipts and ID cards.
 *
 * Each document is first put together as plain data. When every text in it can
 * be written with the PDF's built-in fonts it is sent as a PDF file; otherwise
 * (a name in Devanagari, for example) the same data opens as a print page that
 * the browser can print or save as PDF.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Colours of the PDF documents, the same as the portal. */
function mags_erp_pdf_colours() {
	return array(
		'navy'   => array( 0.157, 0.157, 0.314 ),
		'orange' => array( 0.973, 0.471, 0 ),
		'muted'  => array( 0.345, 0.353, 0.471 ),
		'soft'   => array( 0.933, 0.941, 0.984 ),
		'line'   => array( 0.78, 0.8, 0.88 ),
		'white'  => array( 1, 1, 1 ),
	);
}

/** True when every one of the texts can be written with the PDF fonts. Nested lists are checked too. */
function mags_erp_pdf_can_write_all( $texts ) {
	foreach ( (array) $texts as $text ) {
		if ( is_array( $text ) || is_object( $text ) ) {
			if ( ! mags_erp_pdf_can_write_all( (array) $text ) ) { return false; }
		} elseif ( ! MAGS_ERP_PDF::can_write( (string) $text ) ) {
			return false;
		}
	}
	return true;
}

/**
 * The school's letterhead across the top of a page: name, address line and the
 * title of the document. $top is where it starts; returns where the body can start.
 */
function mags_erp_pdf_letterhead( $pdf, $school, $title, $top = 0 ) {
	$c     = mags_erp_pdf_colours();
	$mid   = MAGS_ERP_PDF::WIDTH / 2;
	$width = MAGS_ERP_PDF::WIDTH - 100;
	$lines = array_filter( array( $school['address'], implode( ' · ', array_filter( array( $school['phone'] ? 'Phone: ' . $school['phone'] : '', $school['email'] ? 'Email: ' . $school['email'] : '' ) ) ) ) );
	$band  = 62 + 12 * count( $lines );

	$pdf->box( 0, $top, MAGS_ERP_PDF::WIDTH, $band, $c['navy'] );
	$pdf->box( 0, $top + $band, MAGS_ERP_PDF::WIDTH, 3, $c['orange'] );
	$pdf->text( $mid, $top + 34, $pdf->fit( $school['name'], 17, true, $width ), 17, true, 'C', $c['white'] );
	$y = $top + 50;
	foreach ( $lines as $line ) {
		$pdf->text( $mid, $y, $pdf->fit( $line, 8.5, false, $width ), 8.5, false, 'C', array( 0.86, 0.87, 0.95 ) );
		$y += 12;
	}
	$pdf->text( $mid, $top + $band + 30, $title, 14, true, 'C', $c['navy'] );
	return $top + $band + 44;
}

/** Signature lines along the bottom: $names left to right. */
function mags_erp_pdf_signatures( $pdf, $y, $names, $left = 50, $right = null ) {
	$c     = mags_erp_pdf_colours();
	$right = null === $right ? MAGS_ERP_PDF::WIDTH - 50 : $right;
	$count = count( $names );
	$width = min( 150, ( $right - $left ) / max( 1, $count ) - 16 );
	foreach ( array_values( $names ) as $index => $name ) {
		$x = 1 === $count ? $right - $width : $left + ( $right - $left - $width ) * $index / ( $count - 1 );
		$pdf->line( $x, $y, $x + $width, $y, 0.8, $c['muted'] );
		$pdf->text( $x + $width / 2, $y + 13, $name, 9, false, 'C', $c['muted'] );
	}
}

/** Send a finished PDF as a download and stop. */
function mags_erp_send_pdf( $pdf, $filename ) {
	while ( ob_get_level() ) { ob_end_clean(); }
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Length: ' . strlen( $pdf ) );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="' . ( preg_replace( '/[^A-Za-z0-9._-]+/', '-', $filename ) ?: 'document.pdf' ) . '"' );
	echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF.
	exit;
}

/** Answer a download the account may not have with a short "not found" page and stop. */
function mags_erp_download_refused( $message = 'This document is not available for your account.' ) {
	status_header( 404 );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo esc_html( $message );
	exit;
}

/**
 * Show documents as a print page instead of a PDF. Each document is an array:
 * title, serial (small line under the title), facts (label => value), photo
 * (file id), paragraphs (list of texts), rows (list of array( label, value )),
 * signatures (list of names) and note. $back is where the Back button goes.
 */
function mags_erp_print_documents( $documents, $page_title, $back ) {
	$school = mags_erp_school_details();
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
	require MAGS_ERP_PATH . 'views/print-document.php';
	exit;
}
