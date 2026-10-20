<?php
/** Every section the signed-in person can open, for all roles. The navigation bar holds only the first four. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_labels = mags_erp_role_labels();
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( $erp_labels[ $ctx['role'] ] ); ?></p>
	<h1>All sections</h1>
</header>

<nav aria-label="All sections">
	<ul class="erp-menu">
		<?php foreach ( $ctx['screens'] as $erp_menu_route => $erp_menu_screen ) : if ( 'more' === $erp_menu_route || ! empty( $erp_menu_screen[5] ) ) { continue; } ?>
			<li>
				<a class="erp-menu__item" href="<?php echo esc_url( mags_erp_url( $erp_menu_route ) ); ?>">
					<span class="erp-menu__icon"><?php echo mags_erp_icon( $erp_menu_screen[3] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<strong><?php echo esc_html( $erp_menu_screen[1] ); ?></strong>
					<?php if ( ! empty( $ctx['badges'][ $erp_menu_route ] ) ) : ?><span class="erp-chip erp-chip--orange"><?php echo esc_html( $ctx['badges'][ $erp_menu_route ] ); ?> waiting</span><?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
