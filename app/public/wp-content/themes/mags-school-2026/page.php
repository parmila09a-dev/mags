<?php
/** Default editorial page layout. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$slug = get_post_field( 'post_name', get_the_ID() );
	$page_contexts = array(
		'about-the-school'     => 'About MAGS',
		'history'              => 'Our journey',
		'vision-and-mission'   => 'Our purpose',
		'school-motto'         => 'Our guiding light',
		'academics'            => 'Learning at MAGS',
		'laboratories'         => 'Learning spaces',
		'library'              => 'Reading & discovery',
		'sports-facilities'    => 'Sport & wellbeing',
		'student-activities'   => 'Beyond the classroom',
		'student-support'      => 'Guidance & wellbeing',
		'admissions'           => 'Joining MAGS',
		'scholarships'         => 'Student support',
		'counselling'          => 'Guidance & wellbeing',
		'transport-facilities' => 'School transport',
		'fees'                 => 'Fee information',
	);
	$context = $page_contexts[ $slug ] ?? 'School information';

	$plain_content = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
	$content_length = function_exists( 'mb_strlen' ) ? mb_strlen( $plain_content ) : strlen( $plain_content );
	$is_short_page = $content_length < 420;

	$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
	?>
	<header class="page-header">
		<div class="wrap">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
					<?php foreach ( $ancestors as $ancestor_id ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $ancestor_id ) ); ?>"><?php echo esc_html( get_the_title( $ancestor_id ) ); ?></a></li>
					<?php endforeach; ?>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>
			<p class="page-header-label"><?php echo esc_html( $context ); ?></p>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>

	<section class="content-area editorial-page<?php echo $is_short_page ? ' page-shell--compact' : ''; ?>">
		<div class="wrap editorial-layout">
			<div class="entry-content editorial-card">
				<?php if ( $plain_content ) : ?>
					<?php the_content(); ?>
				<?php else : ?>
					<div class="leadership-message-pending">
						<h2><?php esc_html_e( 'Information coming soon', 'mags-school' ); ?></h2>
						<p><?php esc_html_e( 'This page is ready for the school team to publish its latest information through the CMS.', 'mags-school' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
