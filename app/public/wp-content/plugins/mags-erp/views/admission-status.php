<?php
/** Public page: a parent types the application number and the phone number of an admission form and sees its status. Expects $ctx['status'] from mags_erp_admission_status_screen(). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_check = $ctx['status'];
$erp_found = $erp_check['found'];
?>
<section class="erp-login erp-admission">
	<div class="erp-login__intro">
		<p class="erp-eyebrow">Admission</p>
		<h1>Status of an admission form</h1>
		<p><?php echo esc_html( mags_erp_school_name() ); ?></p>
	</div>

	<?php if ( $erp_found ) : $erp_state = mags_erp_admission_status_for_parent( $erp_found ); ?>
		<div class="erp-card erp-admission__done" role="status">
			<p>Application number</p>
			<p class="erp-admission__number"><?php echo esc_html( mags_erp_admission_no_text( $erp_found->id ) ); ?></p>
			<p><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span></p>
			<dl class="erp-facts">
				<div><dt>Child</dt><dd><?php echo esc_html( $erp_found->student_name ); ?></dd></div>
				<div><dt>Class asked for</dt><dd><?php echo esc_html( $erp_found->applied_class ); ?></dd></div>
				<div><dt>Form sent on</dt><dd><?php echo esc_html( mags_erp_format_date( substr( (string) $erp_found->created_at, 0, 10 ) ) ); ?></dd></div>
				<?php if ( 'new' !== $erp_found->status && $erp_found->decided_at ) : ?><div><dt>Decided on</dt><dd><?php echo esc_html( mags_erp_format_date( substr( (string) $erp_found->decided_at, 0, 10 ) ) ); ?></dd></div><?php endif; ?>
			</dl>
			<p><?php echo esc_html( $erp_state[2] ); ?></p>
			<p><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'admission-status' ) ); ?>">Check another form</a> <a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the school website</a></p>
		</div>
	<?php else : ?>
		<form class="erp-card erp-form erp-login__card" method="post" action="<?php echo esc_url( mags_erp_url( 'admission-status' ) ); ?>">
			<?php mags_erp_form_fields( 'admission_status' ); ?>
			<?php if ( '' !== $erp_check['error'] ) : ?><p class="erp-flash erp-flash--error" role="alert"><?php echo esc_html( $erp_check['error'] ); ?></p><?php endif; ?>
			<p class="erp-field"><label for="erp_status_no">Application number</label><input id="erp_status_no" name="application_no" type="text" maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="APP-00012" value="<?php echo esc_attr( $erp_check['number'] ); ?>" required><small>Shown on the screen after the form was sent.</small></p>
			<p class="erp-field"><label for="erp_status_phone">Phone number written on the form</label><input id="erp_status_phone" name="phone" type="tel" inputmode="tel" maxlength="20" autocomplete="tel" value="<?php echo esc_attr( $erp_check['phone'] ); ?>" required></p>
			<button class="erp-btn erp-btn--orange erp-btn--block" type="submit">Check status</button>
			<p class="erp-hint">Lost the application number? Please call the school office.</p>
			<?php if ( mags_erp_admissions_open() ) : ?><p class="erp-hint">Not sent a form yet? <a href="<?php echo esc_url( mags_erp_url( 'admission' ) ); ?>">Fill the admission form</a>.</p><?php endif; ?>
		</form>
	<?php endif; ?>
</section>
