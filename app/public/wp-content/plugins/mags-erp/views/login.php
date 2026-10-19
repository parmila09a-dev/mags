<?php
/** Sign-in screen for students / parents, teachers and the office. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_as    = isset( $_GET['as'] ) ? sanitize_key( wp_unslash( $_GET['as'] ) ) : 'student'; // phpcs:ignore WordPress.Security.NonceVerification
$erp_as    = isset( mags_erp_role_labels()[ $erp_as ] ) ? $erp_as : 'student';
$erp_error = mags_erp_login_error_message( isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
$erp_tabs  = array(
	'student' => array( 'Student / Parent', 'Admission number', 'Parents sign in with their child’s admission number and password.' ),
	'teacher' => array( 'Teacher', 'Username', 'Use the username and password given by the school office.' ),
	'office'  => array( 'Office', 'Username', 'For school office staff.' ),
);
?>
<section class="erp-login">
	<div class="erp-login__intro">
		<p class="erp-eyebrow">Welcome</p>
		<h1>Sign in to the school portal</h1>
		<p>Attendance and school records for students, parents, teachers and the office.</p>
	</div>

	<form class="erp-card erp-login__card" method="post" action="<?php echo esc_url( mags_erp_url( 'login' ) ); ?>" data-erp-login>
		<input type="hidden" name="mags_erp_action" value="login">
		<?php if ( $erp_error ) : ?><p class="erp-flash erp-flash--error" role="alert"><?php echo esc_html( $erp_error ); ?></p><?php endif; ?>

		<fieldset class="erp-seg erp-seg--roles">
			<legend>I am signing in as</legend>
			<?php foreach ( $erp_tabs as $erp_tab => $erp_tab_text ) : ?>
				<label class="erp-seg__item erp-seg__item--<?php echo esc_attr( $erp_tab ); ?>"><input type="radio" name="mags_erp_as" value="<?php echo esc_attr( $erp_tab ); ?>" data-erp-login-label="<?php echo esc_attr( $erp_tab_text[1] ); ?>" data-erp-login-hint="<?php echo esc_attr( $erp_tab_text[2] ); ?>" <?php checked( $erp_as, $erp_tab ); ?>><span><?php echo esc_html( $erp_tab_text[0] ); ?></span></label>
			<?php endforeach; ?>
		</fieldset>

		<p class="erp-field">
			<label for="mags_erp_login" data-erp-login-label-target><?php echo esc_html( $erp_tabs[ $erp_as ][1] ); ?></label>
			<input id="mags_erp_login" name="mags_erp_login" type="text" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" maxlength="100" required>
		</p>
		<p class="erp-field">
			<label for="mags_erp_password">Password</label>
			<span class="erp-password"><input id="mags_erp_password" name="mags_erp_password" type="password" autocomplete="current-password" maxlength="200" required><button class="erp-password__toggle" type="button" data-erp-toggle-password="mags_erp_password" aria-pressed="false" hidden>Show</button></span>
		</p>
		<p class="erp-check"><label><input type="checkbox" name="mags_erp_remember" value="1"> <span>Keep me signed in on this phone</span></label></p>

		<button class="erp-btn erp-btn--orange erp-btn--block" type="submit">Sign in</button>
		<p class="erp-hint" data-erp-login-hint-target><?php echo esc_html( $erp_tabs[ $erp_as ][2] ); ?></p>
		<p class="erp-hint">Forgot the password? Ask the school office for a new one.</p>
		<?php if ( mags_erp_admissions_open() ) : ?><p class="erp-hint">New admission? <a href="<?php echo esc_url( mags_erp_url( 'admission' ) ); ?>">Fill the admission form</a>. No login is needed.</p><?php endif; ?>
	</form>
</section>
