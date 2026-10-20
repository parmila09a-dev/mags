<?php
/** Office: download all portal data as one ZIP file, after typing the password again. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

list( $erp_file_count, $erp_file_bytes ) = mags_erp_backup_files_size();
$erp_log = mags_erp_backup_log();
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Backup</h1>
</header>

<section class="erp-card" aria-labelledby="erp-backup-title">
	<h2 id="erp-backup-title">Download all portal data</h2>
	<p class="erp-hint erp-hint--lead">One ZIP file with every class, student, teacher, attendance mark, homework, mark, fee, payment, certificate, admission form, leave request, question and setting of the portal. Each table is a CSV file that opens in Excel, and the same data is there as an SQL file the site administrator can load back.</p>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'backup' ) ); ?>">
		<?php mags_erp_form_fields( 'download_backup' ); ?>
		<p class="erp-check"><label><input type="checkbox" name="with_files" value="1"> <span>Also the uploaded files: homework, answers, notes, photos and documents (<?php echo esc_html( sprintf( _n( '%1$d file, %2$s', '%1$d files, %2$s', $erp_file_count, 'mags-erp' ), $erp_file_count, size_format( $erp_file_bytes, 1 ) ?: '0 B' ) ); ?>)</span></label></p>
		<p class="erp-field"><label for="erp_backup_password">Your password</label><span class="erp-password"><input id="erp_backup_password" name="confirm_password" type="password" autocomplete="current-password" maxlength="200" required><button class="erp-password__toggle" type="button" data-erp-toggle-password="erp_backup_password" aria-pressed="false" hidden>Show</button></span><small>The file holds the details of every child, so the portal asks for your password once more.</small></p>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Download backup</button></div>
	</form>
	<p class="erp-hint">Keep the file on a safe computer or drive, not on a shared or public place. Passwords of the portal logins are not in it. The download can take a minute for a big school; keep the page open until the browser shows the file.</p>
	<p class="erp-hint">This backs up the portal. The website pages, news and photos are backed up by the hosting company or the site administrator.</p>
</section>

<section aria-labelledby="erp-backup-log-title">
	<h2 id="erp-backup-log-title" class="erp-section-title">Last backups <span class="erp-count"><?php echo esc_html( count( $erp_log ) ); ?></span></h2>
	<?php if ( ! $erp_log ) : ?>
		<p class="erp-card erp-empty">No backup has been downloaded yet.</p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_log as $erp_entry ) : ?>
				<li class="erp-row"><div class="erp-row__main"><strong><?php echo esc_html( mags_erp_format_datetime( $erp_entry['at'] ) ); ?></strong><span><?php echo esc_html( mags_erp_teacher_name( (int) $erp_entry['by'] ) ); ?> · <?php echo $erp_entry['files'] ? 'with the uploaded files' : 'without the uploaded files'; ?></span></div></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
