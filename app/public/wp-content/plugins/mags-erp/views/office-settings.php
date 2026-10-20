<?php
/** Office: the lock after wrong passwords, the largest file that can be uploaded, and whether the private files are closed to direct downloads. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_limits  = mags_erp_setting_limits();
$erp_server  = wp_max_upload_size();
$erp_exposed = mags_erp_private_files_exposed();
$erp_check   = get_transient( 'mags_erp_private_check' );
$erp_states  = array(
	'open'    => array( 'Open to direct downloads', 'erp-chip--status-A', 'Homework, photos and documents in the private folder can be opened by anyone who has the address of a file. Add the rule below to the web server and press Check again.' ),
	'closed'  => array( 'Closed', 'erp-chip--green', 'The web server refuses to hand out the private folder. Files reach people only through the portal, after it checks who is asking.' ),
	'unknown' => array( 'Not known', 'erp-chip--orange', 'The test could not reach the website from the server (this happens on some local and hosted setups). Check by hand: the rule below must be in the site’s nginx settings.' ),
);
$erp_state   = $erp_states[ true === $erp_exposed ? 'open' : ( false === $erp_exposed ? 'closed' : 'unknown' ) ];
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Settings</h1>
</header>

<section class="erp-card" aria-labelledby="erp-settings-title">
	<h2 id="erp-settings-title">Sign-in lock and uploads</h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'settings' ) ); ?>">
		<?php mags_erp_form_fields( 'save_settings' ); ?>
		<div class="erp-form__grid">
			<p class="erp-field"><label for="erp_set_tries">Wrong passwords before the lock</label><input id="erp_set_tries" name="login_tries" type="number" inputmode="numeric" min="<?php echo esc_attr( $erp_limits['login_tries'][1] ); ?>" max="<?php echo esc_attr( $erp_limits['login_tries'][2] ); ?>" step="1" value="<?php echo esc_attr( mags_erp_setting( 'login_tries' ) ); ?>" required><small>For one login ID from one network address. <?php echo esc_html( $erp_limits['login_tries'][1] . ' to ' . $erp_limits['login_tries'][2] ); ?>.</small></p>
			<p class="erp-field"><label for="erp_set_lock">Lock time in minutes</label><input id="erp_set_lock" name="lock_minutes" type="number" inputmode="numeric" min="<?php echo esc_attr( $erp_limits['lock_minutes'][1] ); ?>" max="<?php echo esc_attr( $erp_limits['lock_minutes'][2] ); ?>" step="1" value="<?php echo esc_attr( mags_erp_setting( 'lock_minutes' ) ); ?>" required><small>How long the login stays locked. Up to 1440 (one day).</small></p>
			<p class="erp-field"><label for="erp_set_file">Largest file in MB</label><input id="erp_set_file" name="max_file_mb" type="number" inputmode="numeric" min="<?php echo esc_attr( $erp_limits['max_file_mb'][1] ); ?>" max="<?php echo esc_attr( $erp_limits['max_file_mb'][2] ); ?>" step="1" value="<?php echo esc_attr( mags_erp_setting( 'max_file_mb' ) ); ?>" required><small>Homework, answers, notes, photos and documents. Now: up to <?php echo esc_html( size_format( mags_erp_max_file_size() ) ); ?>.</small></p>
		</div>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Save settings</button></div>
	</form>
	<p class="erp-hint">The lock works the same on the portal and on the WordPress login page (wp-login.php), and wrong passwords on both count together. A locked teacher or student can wait, or get a new password from the office; the lock itself ends after the lock time.</p>
	<p class="erp-hint">This server takes files up to <?php echo esc_html( $erp_server ? size_format( $erp_server ) : 'an unknown size' ); ?>; a bigger setting is cut to that. The admission form takes a document of <?php echo esc_html( size_format( mags_erp_admission_document_bytes() ) ); ?> at most.</p>
</section>

<?php
$erp_mail_counts = mags_erp_mail_counts();
$erp_mail_recent = mags_erp_recent_mail( 20 );
$erp_no_email    = mags_erp_students_without_email();
$erp_mail_states = array( 'waiting' => array( 'Waiting', 'erp-chip--orange' ), 'sending' => array( 'Sending', 'erp-chip--orange' ), 'sent' => array( 'Sent', 'erp-chip--green' ), 'failed' => array( 'Not sent', 'erp-chip--status-A' ), 'dropped' => array( 'No longer needed', '' ), 'cancelled' => array( 'Held back', '' ) );
?>
<section id="erp-mail" class="erp-card" aria-labelledby="erp-mail-title">
	<h2 id="erp-mail-title">Emails to parents</h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'settings' ) ); ?>">
		<?php mags_erp_form_fields( 'save_mail_settings' ); ?>
		<fieldset class="erp-checks">
			<legend>Send an email to the parent</legend>
			<p class="erp-check"><label><input type="checkbox" name="mail_absent" value="1" <?php checked( mags_erp_mail_enabled( 'absent' ) ); ?>> <span><strong>When the child is marked absent</strong> — for today’s attendance only, <?php echo esc_html( MAGS_ERP_ABSENT_MAIL_DELAY ); ?> minutes after it is saved, and not when the teacher has corrected the mark by then.</span></label></p>
			<p class="erp-check"><label><input type="checkbox" name="mail_fees" value="1" <?php checked( mags_erp_mail_enabled( 'fees' ) ); ?>> <span><strong>When a fee is overdue</strong> — once, in the morning after the last date of the fee has passed, and when the office presses “Send fee reminder”.</span></label></p>
			<p class="erp-check"><label><input type="checkbox" name="mail_results" value="1" <?php checked( mags_erp_mail_enabled( 'results' ) ); ?>> <span><strong>When the results of an exam are published</strong> — the email says the results are out; the marks are seen in the portal.</span></label></p>
		</fieldset>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Save emails</button></div>
	</form>
	<p class="erp-hint">Emails go to the parent’s email in the student’s profile. <?php echo esc_html( $erp_no_email ? sprintf( _n( '%d student has no parent email yet and gets no emails.', '%d students have no parent email yet and get no emails.', $erp_no_email, 'mags-erp' ), $erp_no_email ) : 'Every student has a parent email.' ); ?> The website must be able to send email: on the live site set up the school’s SMTP or email service (see the deployment guide).</p>
	<p class="erp-row__chips"><span class="erp-chip erp-chip--orange">Waiting: <?php echo esc_html( $erp_mail_counts['waiting'] ); ?></span><span class="erp-chip erp-chip--green">Sent: <?php echo esc_html( $erp_mail_counts['sent'] ); ?></span><span class="erp-chip erp-chip--status-A">Not sent: <?php echo esc_html( $erp_mail_counts['failed'] ); ?></span><span class="erp-chip">Last 30 days</span></p>
	<?php if ( $erp_mail_counts['waiting'] ) : ?>
		<form method="post" action="<?php echo esc_url( mags_erp_url( 'settings' ) ); ?>">
			<?php mags_erp_form_fields( 'send_mail_now' ); ?>
			<button class="erp-btn erp-btn--navy erp-btn--sm" type="submit">Send waiting emails now</button>
		</form>
	<?php endif; ?>
	<?php if ( $erp_mail_recent ) : ?>
		<details class="erp-removed">
			<summary class="erp-btn erp-btn--ghost erp-btn--sm">Newest emails <span class="erp-count"><?php echo esc_html( count( $erp_mail_recent ) ); ?></span></summary>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_mail_recent as $erp_mail ) : $erp_mail_state = $erp_mail_states[ $erp_mail->status ] ?? array( $erp_mail->status, '' ); ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php echo esc_html( $erp_mail->subject ); ?></strong>
							<span><?php echo esc_html( (string) $erp_mail->full_name ); ?> · <?php echo esc_html( $erp_mail->email ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_mail->sent_at ?: $erp_mail->created_at ) ); ?></span>
							<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_mail_state[1] ); ?>"><?php echo esc_html( $erp_mail_state[0] ); ?></span><span class="erp-chip"><?php echo esc_html( mags_erp_mail_kinds()[ $erp_mail->kind ] ?? $erp_mail->kind ); ?></span></span>
							<?php if ( '' !== $erp_mail->error && 'sent' !== $erp_mail->status ) : ?><span><?php echo esc_html( $erp_mail->error ); ?></span><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
</section>

<section id="erp-private-files" class="erp-card" aria-labelledby="erp-private-title">
	<h2 id="erp-private-title">Private files <span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span></h2>
	<p><?php echo esc_html( $erp_state[2] ); ?></p>
	<?php if ( is_array( $erp_check ) && ! empty( $erp_check['at'] ) ) : ?><p class="erp-hint">Last checked <?php echo esc_html( mags_erp_format_datetime( $erp_check['at'] ) ); ?>.</p><?php endif; ?>
	<form method="post" action="<?php echo esc_url( mags_erp_url( 'settings' ) ); ?>">
		<?php mags_erp_form_fields( 'check_private_files' ); ?>
		<button class="erp-btn erp-btn--navy erp-btn--sm" type="submit">Check again</button>
	</form>
	<h3>The rule for nginx</h3>
	<p class="erp-hint">Apache follows the folder’s own .htaccess file. nginx does not read it, so the site’s nginx settings need this rule inside the <code>server { … }</code> block:</p>
	<pre class="erp-code"><code><?php echo esc_html( mags_erp_nginx_rule() ); ?></code></pre>
	<ul class="erp-hint erp-code-steps">
		<li><strong>Local (this computer):</strong> open the site’s folder, then <code>conf/nginx/site.conf.hbs</code>. Paste the rule inside <code>server { … }</code>, above the first <code>location</code> line, save, and restart the site in Local.</li>
		<li><strong>Hosting with nginx:</strong> give the rule to the hosting company or the server administrator to add to the site’s server block, then reload nginx.</li>
	</ul>
</section>
