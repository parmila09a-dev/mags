<?php
/** Shared bottom of every portal screen. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
</main>

<footer class="erp-foot">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>">← School website</a>
</footer>

<script src="<?php echo esc_url( MAGS_ERP_URL . 'assets/portal.js?ver=' . MAGS_ERP_VERSION ); ?>" defer></script>
</body>
</html>
