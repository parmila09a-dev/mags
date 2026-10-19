<?php
/**
 * Report card as a print page. Used instead of the PDF file when a name or
 * subject is written in a script the PDF fonts do not have: the browser prints
 * it or saves it as a PDF. Expects $card from mags_erp_report_card_data().
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_result = $card['result'];
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( 'Report card · ' . $card['student']->full_name . ' · ' . $card['exam'] ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( MAGS_ERP_URL . 'assets/portal.css?ver=' . MAGS_ERP_VERSION ); ?>">
</head>
<body class="erp erp--guest erp--paper">
<main class="erp-paper">
	<p class="erp-paper__tools"><button class="erp-btn erp-btn--navy" type="button" data-erp-print hidden>Print or save as PDF</button> <a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( 'office' === mags_erp_user_role() ? mags_erp_url( 'reports', array( 'report' => 'marks' ) ) : mags_erp_url( 'marks' ) ); ?>">Back</a></p>
	<p class="erp-paper__tools erp-hint">To keep a copy, print this page and choose “Save as PDF”.</p>

	<header class="erp-paper__head">
		<h1><?php echo esc_html( $card['school'] ); ?></h1>
		<p>Report card</p>
	</header>
	<h2><?php echo esc_html( $card['exam'] ); ?></h2>
	<p class="erp-paper__session">Session <?php echo esc_html( $card['session'] ); ?></p>

	<dl class="erp-paper__facts">
		<div><dt>Student</dt><dd><?php echo esc_html( $card['student']->full_name ); ?></dd></div>
		<div><dt>Class</dt><dd><?php echo esc_html( $card['class'] ); ?></dd></div>
		<div><dt>Admission no.</dt><dd><?php echo esc_html( $card['student']->admission_no ); ?></dd></div>
		<div><dt>Roll no.</dt><dd><?php echo esc_html( '' !== $card['student']->roll_no ? $card['student']->roll_no : '–' ); ?></dd></div>
	</dl>

	<table class="erp-table erp-table--marks">
		<thead><tr><th scope="col">Subject</th><th scope="col" class="erp-num">Maximum marks</th><th scope="col" class="erp-num">Marks obtained</th></tr></thead>
		<tbody>
			<?php foreach ( $erp_result['rows'] as $erp_row ) : ?>
				<tr><th scope="row"><?php echo esc_html( $erp_row->subject_name ); ?></th><td class="erp-num"><?php echo esc_html( mags_erp_format_marks( $erp_row->max_marks ) ); ?></td><td class="erp-num"><strong><?php echo esc_html( mags_erp_marks_text( $erp_row ) ); ?></strong></td></tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr><th scope="row">Total</th><td class="erp-num"><?php echo esc_html( mags_erp_format_marks( $erp_result['max'] ) ); ?></td><td class="erp-num"><strong><?php echo esc_html( mags_erp_format_marks( $erp_result['total'] ) ); ?></strong></td></tr>
			<tr><th scope="row">Percentage</th><td></td><td class="erp-num"><strong><?php echo esc_html( null === $erp_result['percent'] ? '–' : $erp_result['percent'] . '%' ); ?></strong></td></tr>
		</tfoot>
	</table>
	<p class="erp-hint">AB = absent, counted as 0. A dash = marks not entered, not counted in the total.</p>
	<?php if ( $card['attendance']['marked'] ) : ?>
		<p><?php echo esc_html( sprintf( 'Attendance in session %s: present on %d of %d days marked (%s%%).', $card['session'], $card['attendance']['present'], $card['attendance']['marked'], $card['attendance']['percent'] ) ); ?></p>
	<?php endif; ?>

	<div class="erp-paper__sign"><span>Class teacher</span><span>Principal</span><span>Parent / Guardian</span></div>
	<p class="erp-hint erp-paper__made">Made from the school portal on <?php echo esc_html( $card['made_on'] ); ?>.</p>
</main>
<script src="<?php echo esc_url( MAGS_ERP_URL . 'assets/portal.js?ver=' . MAGS_ERP_VERSION ); ?>" defer></script>
</body>
</html>
