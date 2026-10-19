<?php
/** Single event with CMS-managed event photographs. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); $event_date = mags_school_event_date(); $photo_ids = mags_school_gallery_image_ids(); ?>
	<header class="page-header">
		<div class="wrap">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
					<li><a href="<?php echo esc_url( mags_school_page_url( 'news-and-events' ) ); ?>"><?php esc_html_e( 'News & Events', 'mags-school' ); ?></a></li>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>
			<?php if ( $event_date ) : ?><p class="page-header-label"><?php echo esc_html( $event_date ); ?></p><?php endif; ?>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>
	<article class="content-area"><div class="wrap entry-content entry-content--wide"><?php if ( ! $photo_ids && has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'class' => 'single-featured-image' ) ); } the_content(); ?><?php if ( $photo_ids ) : ?><section class="cms-photo-section" aria-labelledby="event-photographs"><h2 id="event-photographs">Event photographs</h2><div class="cms-photo-grid"><?php foreach ( $photo_ids as $photo_index => $photo_id ) : $photo_alt = mags_school_attachment_alt( $photo_id, sprintf( '%s — photograph %d', get_the_title(), $photo_index + 1 ) ); ?><a href="<?php echo esc_url( wp_get_attachment_image_url( $photo_id, 'full' ) ); ?>"><?php echo wp_get_attachment_image( $photo_id, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => $photo_alt ) ); ?></a><?php endforeach; ?></div></section><?php endif; ?></div></article>
<?php endwhile; get_footer(); ?>
