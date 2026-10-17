<?php
/** Single CMS-managed gallery album. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); $photo_ids = mags_school_gallery_image_ids(); ?>
	<header class="page-header">
		<div class="wrap">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
					<li><a href="<?php echo esc_url( get_post_type_archive_link( 'mags_gallery' ) ); ?>"><?php esc_html_e( 'Gallery', 'mags-school' ); ?></a></li>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>
	<article class="content-area"><div class="wrap entry-content entry-content--wide"><?php the_content(); ?><?php if ( $photo_ids ) : ?><section class="cms-photo-section" aria-label="Album photographs"><div class="cms-photo-grid"><?php foreach ( $photo_ids as $photo_index => $photo_id ) : $photo_alt = mags_school_attachment_alt( $photo_id, sprintf( '%s — photograph %d', get_the_title(), $photo_index + 1 ) ); ?><a href="<?php echo esc_url( wp_get_attachment_image_url( $photo_id, 'full' ) ); ?>"><?php echo wp_get_attachment_image( $photo_id, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => $photo_alt ) ); ?></a><?php endforeach; ?></div></section><?php else : ?><div class="empty-state">No photographs have been added to this album yet.</div><?php endif; ?></div></article>
<?php endwhile; get_footer(); ?>
