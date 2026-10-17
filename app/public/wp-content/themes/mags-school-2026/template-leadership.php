<?php
/* Template Name: Leadership Message */
get_header();

while ( have_posts() ) {
	the_post();
	$slug = get_post_field( 'post_name', get_the_ID() );
	$profiles = array(
		'patrons-message' => array(
			'name'  => 'Dr. Nand Kishore Garg',
			'role'  => 'Patron',
			'image' => get_theme_file_uri( '/assets/images/patron-dr-nand-kishore-garg.jpg' ),
			'width' => 267,
			'height' => 244,
		),
		'chairmans-message' => array(
			'name'  => 'Er. T. R. Garg',
			'role'  => 'Chairman',
			'image' => get_theme_file_uri( '/assets/images/chairman-tr-garg.jpg' ),
			'width' => 1000,
			'height' => 1024,
		),
		'principals-message' => array(
			'name'  => 'Meetu Aggarwal',
			'role'  => 'Principal',
			'image' => get_theme_file_uri( '/assets/images/principal-meetu-aggarwal.png' ),
			'width' => 195,
			'height' => 258,
		),
	);
	$profile = isset( $profiles[ $slug ] ) ? $profiles[ $slug ] : array( 'name' => get_the_title(), 'role' => 'School Leadership', 'image' => '', 'width' => 0, 'height' => 0 );
	$message_text = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
	$message_length = function_exists( 'mb_strlen' ) ? mb_strlen( $message_text ) : strlen( $message_text );
	$has_message = $message_length >= 80;
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
	<section class="content-area leadership-page">
		<div class="wrap leadership-page-grid">
			<aside class="leadership-profile">
				<?php if ( $profile['image'] ) : ?>
					<img src="<?php echo esc_url( $profile['image'] ); ?>" alt="<?php echo esc_attr( $profile['name'] . ', ' . $profile['role'] ); ?>" width="<?php echo esc_attr( $profile['width'] ); ?>" height="<?php echo esc_attr( $profile['height'] ); ?>">
				<?php else : ?>
					<div class="leadership-page-symbol" aria-hidden="true"><span>MA</span></div>
				<?php endif; ?>
				<div><p><?php echo esc_html( $profile['role'] ); ?></p><h2><?php echo esc_html( $profile['name'] ); ?></h2><small>Maharaja Agrasen Global School</small></div>
			</aside>
			<article class="leadership-message entry-content">
				<p class="eyebrow">Message</p>
				<?php if ( $has_message ) : ?>
					<?php the_content(); ?>
				<?php else : ?>
					<div class="leadership-message-pending">
						<h2><?php echo esc_html( sprintf( 'From the %s', $profile['role'] ) ); ?></h2>
						<p><?php echo esc_html( sprintf( 'The school will publish the %s’s approved message here when it is supplied.', $profile['role'] ) ); ?></p>
					</div>
				<?php endif; ?>
			</article>
		</div>
	</section>
	<?php
}

get_footer();
