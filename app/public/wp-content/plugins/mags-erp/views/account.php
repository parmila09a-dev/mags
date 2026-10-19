<?php
/** Own account details and password change, for every role. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = 'student' === $ctx['role'] ? mags_erp_get_student_by_user( $ctx['user']->ID ) : null;
$erp_labels  = mags_erp_role_labels();
?>
<header class="erp-head">
	<p class="erp-eyebrow">My account</p>
	<h1><?php echo esc_html( $ctx['user']->display_name ); ?></h1>
</header>

<section class="erp-card">
	<dl class="erp-facts">
		<div><dt>Account type</dt><dd><?php echo esc_html( $erp_labels[ $ctx['role'] ] ); ?></dd></div>
		<div><dt>Login ID</dt><dd><?php echo esc_html( $erp_student ? $erp_student->admission_no : $ctx['user']->user_login ); ?></dd></div>
	</dl>
</section>

<section class="erp-card" aria-labelledby="erp-password-title">
	<h2 id="erp-password-title">Change password</h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'account' ) ); ?>">
		<?php mags_erp_form_fields( 'change_password' ); ?>
		<p class="erp-field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" maxlength="200" required></p>
		<p class="erp-field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" maxlength="200" required><small>At least 8 characters. Do not use your name, admission number or date of birth.</small></p>
		<p class="erp-field"><label for="new_password_again">New password again</label><input id="new_password_again" name="new_password_again" type="password" autocomplete="new-password" minlength="8" maxlength="200" required></p>
		<button class="erp-btn erp-btn--navy" type="submit">Change password</button>
	</form>
</section>
