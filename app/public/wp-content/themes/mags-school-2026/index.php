<?php
/** Generic archive and index fallback. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$archive_title = is_post_type_archive() ? post_type_archive_title( '', false ) : 'News & Events';
?>
<header class="page-header">
	<div class="wrap">
		<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
				<li aria-current="page"><?php echo esc_html( $archive_title ); ?></li>
			</ol>
		</nav>
		<h1><?php echo esc_html( $archive_title ); ?></h1>
	</div>
</header>
<section class="content-area">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="archive-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="card">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="card-media">
								<?php the_post_thumbnail( 'medium_large', array( 'class' => 'card-thumbnail', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
							</div>
						<?php endif; ?>
						<p class="eyebrow"><?php echo esc_html( get_the_date() ); ?></p>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php if ( in_array( get_post_type(), array( 'mags_notice', 'mags_download' ), true ) ) : ?>
							<a class="button button--outline" href="<?php echo esc_url( mags_school_document_link() ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Open document: %s', get_the_title() ) ); ?>">Open document</a>
						<?php else : ?>
							<a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( 'Read more: %s', get_the_title() ) ); ?>">Read more <span aria-hidden="true">&rarr;</span></a>
						<?php endif; ?>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="empty-state">No content has been published yet. Add content in the WordPress Dashboard.</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
