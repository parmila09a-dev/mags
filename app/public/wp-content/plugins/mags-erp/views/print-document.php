<?php
/**
 * A certificate, receipt or ID card as a print page. Used instead of the PDF
 * file when a name is written in a script the PDF fonts do not have: the
 * browser prints it or saves it as a PDF. Expects $documents, $page_title,
 * $back and $school from mags_erp_print_documents().
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $page_title ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( MAGS_ERP_URL . 'assets/portal.css?ver=' . MAGS_ERP_VERSION ); ?>">
</head>
<body class="erp erp--guest erp--paper">
<main class="erp-paper">
	<p class="erp-paper__tools"><button class="erp-btn erp-btn--navy" type="button" data-erp-print hidden>Print or save as PDF</button> <a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( $back ); ?>">Back</a></p>
	<p class="erp-paper__tools erp-hint">To keep a copy, print this page and choose “Save as PDF”.</p>

	<?php foreach ( $documents as $erp_doc ) : ?>
		<?php $erp_school = (array) ( $erp_doc['school'] ?? array() ) + $school; // A certificate keeps the school's name as it was printed. ?>
		<article class="erp-paper__doc">
			<header class="erp-paper__head">
				<h1><?php echo esc_html( $erp_school['name'] ); ?></h1>
				<?php if ( '' !== $erp_school['address'] ) : ?><p class="erp-paper__where"><?php echo esc_html( $erp_school['address'] ); ?></p><?php endif; ?>
				<p><?php echo esc_html( $erp_doc['title'] ); ?></p>
			</header>
			<?php if ( ! empty( $erp_doc['serial'] ) ) : ?><p class="erp-paper__session"><?php echo esc_html( $erp_doc['serial'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $erp_doc['photo'] ) ) { mags_erp_photo_img( (int) $erp_doc['photo'], 'the student', 'erp-photo erp-photo--paper' ); } ?>

			<?php if ( ! empty( $erp_doc['facts'] ) ) : ?>
				<dl class="erp-paper__facts">
					<?php foreach ( $erp_doc['facts'] as $erp_label => $erp_value ) : ?><div><dt><?php echo esc_html( $erp_label ); ?></dt><dd><?php echo esc_html( '' !== (string) $erp_value ? $erp_value : '–' ); ?></dd></div><?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php foreach ( (array) ( $erp_doc['paragraphs'] ?? array() ) as $erp_text ) : ?><p class="erp-paper__text"><?php echo esc_html( $erp_text ); ?></p><?php endforeach; ?>
			<?php if ( ! empty( $erp_doc['rows'] ) ) : ?>
				<table class="erp-table erp-table--marks">
					<tbody>
						<?php foreach ( $erp_doc['rows'] as $erp_index => $erp_row ) : ?><tr><th scope="row"><?php echo esc_html( $erp_row[0] ); ?></th><td><strong><?php echo esc_html( $erp_row[1] ); ?></strong></td></tr><?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<?php if ( ! empty( $erp_doc['note'] ) ) : ?><p class="erp-hint"><?php echo esc_html( $erp_doc['note'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $erp_doc['signatures'] ) ) : ?><div class="erp-paper__sign"><?php foreach ( $erp_doc['signatures'] as $erp_name ) : ?><span><?php echo esc_html( $erp_name ); ?></span><?php endforeach; ?></div><?php endif; ?>
		</article>
	<?php endforeach; ?>
	<p class="erp-hint erp-paper__made">Made from the school portal on <?php echo esc_html( mags_erp_format_date( mags_erp_today() ) ); ?>.</p>
</main>
<script src="<?php echo esc_url( MAGS_ERP_URL . 'assets/portal.js?ver=' . MAGS_ERP_VERSION ); ?>" defer></script>
</body>
</html>
