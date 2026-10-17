<?php
/** Site header. */
$mags_logo_url        = get_theme_file_uri( '/assets/images/mags-logo-header.png' );
$mags_custom_logo_id  = (int) get_theme_mod( 'custom_logo' );
$mags_admission_url   = mags_school_page_url( 'admissions' );
$mags_header_name     = mags_school_option( 'school_name', 'Maharaja Agrasen Global School' );
$mags_header_tagline  = trim( (string) get_bloginfo( 'description' ) );
$mags_header_tagline  = $mags_header_tagline && 'Just another WordPress site' !== $mags_header_tagline ? $mags_header_tagline : 'Delhi · Established 2010';
$mags_header_address = mags_school_option( 'address', '1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086' );
$mags_header_email   = mags_school_option( 'email' );
$mags_header_phones  = mags_school_phone_numbers();
$mags_header_phone   = $mags_header_phones ? $mags_header_phones[0] : '';
$mags_header_socials = array_filter(
	array(
		'Facebook'  => array( 'label' => 'f', 'url' => mags_school_option( 'facebook' ) ),
		'Instagram' => array( 'label' => 'ig', 'url' => mags_school_option( 'instagram' ) ),
		'YouTube'   => array( 'label' => 'yt', 'url' => mags_school_option( 'youtube' ) ),
	),
	static function ( $mags_social ) {
		return ! empty( $mags_social['url'] );
	}
);
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'mags-school' ); ?></a>

<header class="site-header">
	<div class="utility">
		<div class="wrap">
			<div class="utility-contact">
				<?php if ( $mags_header_address ) : ?><span class="utility-address"><?php echo esc_html( $mags_header_address ); ?></span><?php endif; ?>
				<?php if ( $mags_header_email ) : ?><a href="mailto:<?php echo esc_attr( $mags_header_email ); ?>"><?php echo esc_html( $mags_header_email ); ?></a><?php endif; ?>
				<?php if ( $mags_header_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mags_header_phone ) ); ?>"><?php echo esc_html( $mags_header_phone ); ?></a><?php endif; ?>
			</div>
			<?php if ( $mags_header_socials ) : ?>
				<nav class="utility-socials" aria-label="School social media">
					<?php foreach ( $mags_header_socials as $mags_network => $mags_social ) : ?>
						<a href="<?php echo esc_url( $mags_social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $mags_network . ' (opens in a new tab)' ); ?>"><?php echo esc_html( $mags_social['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>

	<div class="wrap identity-row">
		<a class="brand brand--official" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $mags_header_name . ' home' ); ?>">
			<?php if ( $mags_custom_logo_id ) : ?>
				<?php echo wp_get_attachment_image( $mags_custom_logo_id, 'medium', false, array( 'class' => 'brand-logo', 'alt' => '', 'decoding' => 'async' ) ); ?>
			<?php else : ?>
				<img class="brand-logo" src="<?php echo esc_url( $mags_logo_url ); ?>" alt="" width="320" height="189" decoding="async">
			<?php endif; ?>
			<span class="brand-copy">
				<strong class="brand-title"><?php echo esc_html( $mags_header_name ); ?></strong>
				<small class="brand-subtitle"><?php echo esc_html( $mags_header_tagline ); ?></small>
			</span>
		</a>

		<nav class="main-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'mags-school' ); ?>">
			<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu" aria-label="<?php esc_attr_e( 'Open navigation menu', 'mags-school' ); ?>"><?php esc_html_e( 'Menu', 'mags-school' ); ?></button>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'menu_id'        => 'primary-menu',
					'fallback_cb'    => 'mags_school_fallback_menu',
				)
			);
			?>
			<div class="header-actions header-actions--nav">
				<a class="button button--gold" href="<?php echo esc_url( $mags_admission_url ); ?>">Admission Enquiry</a>
			</div>
		</nav>
	</div>
</header>

<main id="content" tabindex="-1">
