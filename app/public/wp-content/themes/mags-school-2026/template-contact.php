<?php
/* Template Name: Contact Us */
get_header();

while ( have_posts() ) {
	the_post();
	$mags_address  = mags_school_option( 'address' );
	$mags_phones   = mags_school_phone_numbers();
	$mags_email    = mags_school_option( 'email' );
	$mags_map      = mags_school_option( 'map_url' );
	$mags_building = get_theme_file_uri( '/assets/images/mags-school-building.jpg' );
	?>
	<header class="page-header">
		<div class="wrap">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>
	<section class="content-area">
		<div class="wrap contact-layout">
			<aside class="contact-details">
				<img class="contact-building" src="<?php echo esc_url( $mags_building ); ?>" alt="Maharaja Agrasen Global School building in Rohini, Delhi" width="5448" height="4000">
				<div class="contact-details-copy">
					<p class="eyebrow">Visit MAGS</p>
					<h2>Let’s start a conversation.</h2>
					<p>We would be pleased to answer questions about the school, facilities or admission process.</p>
					<?php if ( $mags_address ) : ?><p><strong>Address</strong><br><?php echo nl2br( esc_html( $mags_address ) ); ?></p><?php endif; ?>
					<?php if ( $mags_phones ) : ?><p><strong>Telephone</strong><br><?php foreach ( $mags_phones as $index => $mags_phone ) : ?><?php if ( $index ) : ?><br><?php endif; ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mags_phone ) ); ?>"><?php echo esc_html( $mags_phone ); ?></a><?php endforeach; ?></p><?php endif; ?>
					<?php if ( $mags_email ) : ?><p><strong>Email</strong><br><a href="mailto:<?php echo esc_attr( $mags_email ); ?>"><?php echo esc_html( $mags_email ); ?></a></p><?php endif; ?>
				</div>
			</aside>
			<div>
				<?php echo do_shortcode( '[mags_contact_form]' ); ?>
				<?php if ( $mags_map ) : ?>
					<iframe class="school-map" title="Maharaja Agrasen Global School location" width="100%" height="320" loading="lazy" src="<?php echo esc_url( $mags_map ); ?>"></iframe>
				<?php else : ?>
					<div class="map-placeholder"><div><strong>School location</strong><?php if ( $mags_address ) : ?><br><small><?php echo nl2br( esc_html( $mags_address ) ); ?></small><?php endif; ?></div></div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

get_footer();
