<?php
/** Public archive of photo albums managed through the Gallery CMS. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<header class="page-header">
	<div class="wrap">
		<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
				<li aria-current="page"><?php esc_html_e( 'Gallery', 'mags-school' ); ?></li>
			</ol>
		</nav>
		<h1>Life at MAGS</h1>
	</div>
</header>

<section class="content-area gallery-archive" aria-labelledby="gallery-albums-heading">
	<div class="wrap">
		<div class="section-heading">
			<div>
				<p class="eyebrow">School photo gallery</p>
				<h2>Learning, community and school traditions.</h2>
			</div>
			<p>Explore school-approved photographs organized into albums by the MAGS team.</p>
		</div>
		<h2 id="gallery-albums-heading" class="display-heading">Gallery albums</h2>

		<?php if ( have_posts() ) : ?>
			<div class="gallery-cms-section">
				<div class="archive-grid">
					<?php while ( have_posts() ) : the_post(); ?>
						<article class="card gallery-album-card">
							<?php $album_image = mags_school_primary_image_url( get_the_ID(), 'medium_large' ); if ( $album_image ) : ?><img src="<?php echo esc_url( $album_image ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
							<p class="eyebrow"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></p>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						</article>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination(); ?>
			</div>
		<?php else : ?>
			<div class="empty-state" role="status">No gallery albums have been published yet. Authorized staff can create an album and add multiple photographs from the WordPress Dashboard.</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
