<?php
/** Shown to signed-in website accounts that have no portal role. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="erp-card erp-empty">
	<h1>No portal access</h1>
	<p>This account (<?php echo esc_html( $ctx['user']->display_name ); ?>) is not a student, teacher or office account. Sign out and use your portal login, or ask the school office for help.</p>
</section>
