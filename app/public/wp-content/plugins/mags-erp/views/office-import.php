<?php
/** Office: add many students at once from a CSV file. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes = mags_erp_get_classes();
$erp_sample  = mags_erp_import_sample_rows();
?>
<header class="erp-head">
	<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'students' ) ); ?>">← Students</a></p>
	<h1>Add students from a file</h1>
</header>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">Add the classes first. The file names the class of each student. <a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Add classes</a></p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-import-title">
		<h2 id="erp-import-title">Upload the list</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'import' ) ); ?>" enctype="multipart/form-data">
			<?php mags_erp_form_fields( 'import_students' ); ?>
			<p class="erp-field erp-field--file">
				<label for="erp_students_csv">CSV file</label>
				<input id="erp_students_csv" name="students_csv" type="file" accept=".csv,.txt,text/csv" required>
				<small>Up to <?php echo esc_html( number_format_i18n( MAGS_ERP_IMPORT_MAX_ROWS ) ); ?> students and <?php echo esc_html( size_format( MAGS_ERP_IMPORT_MAX_BYTES ) ); ?> in one file.</small>
			</p>
			<div class="erp-form__actions">
				<button class="erp-btn erp-btn--orange" type="submit">Check and add students</button>
				<a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'export', array( 'report' => 'sample' ) ) ); ?>">Download sample file</a>
			</div>
		</form>
		<p class="erp-hint">The whole file is checked first. If any line is wrong, no student is added and you are told which lines to correct. Students who are already in the portal are skipped, so the same file can be uploaded again.</p>
	</section>

	<section class="erp-card" aria-labelledby="erp-import-how-title">
		<h2 id="erp-import-how-title">How to make the file</h2>
		<ol class="erp-steps erp-steps--plain">
			<li><div><strong>Open Excel or Google Sheets</strong><p>Use the first line for the column names, exactly as in the sample below. Section and Roll No may be left empty.</p></div></li>
			<li><div><strong>One student on each line</strong><p>Write the class and section as they are named in the portal. The admission number becomes the login ID.</p></div></li>
			<li><div><strong>Save as CSV</strong><p>In Excel choose File → Save As → “CSV UTF-8 (Comma delimited)”. In Google Sheets choose File → Download → “Comma-separated values”.</p></div></li>
		</ol>
		<div class="erp-table-wrap">
			<table class="erp-table">
				<caption class="erp-sr">Sample of the file</caption>
				<thead><tr><?php foreach ( $erp_sample[0] as $erp_cell ) : ?><th scope="col"><?php echo esc_html( $erp_cell ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
					<?php foreach ( array_slice( $erp_sample, 1 ) as $erp_row ) : ?>
						<tr><?php foreach ( $erp_row as $erp_cell ) : ?><td><?php echo esc_html( $erp_cell ); ?></td><?php endforeach; ?></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="erp-hint">Classes in the portal now: <?php echo esc_html( implode( ', ', array_map( 'mags_erp_class_label', $erp_classes ) ) ); ?>.</p>
		<p class="erp-hint">After the upload the portal shows the login ID and password of every new student once. Download or print that list before leaving the page.</p>
	</section>
<?php endif; ?>
