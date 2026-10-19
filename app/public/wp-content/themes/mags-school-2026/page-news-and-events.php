<?php
/** Combined CMS-managed News and Events page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
	$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$items = new WP_Query( array( 'post_type' => array( 'post', 'mags_event' ), 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC' ) );
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
	<section class="content-area"><div class="wrap">
		<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) ) : ?><div class="entry-content news-page-intro"><?php the_content(); ?></div><?php endif; ?>
		<?php if ( 1 === $paged ) { get_template_part( 'template-parts/upcoming-events' ); } ?>
		<div class="section-heading"><div><p class="eyebrow">School updates</p><h2>News, achievements and events.</h2></div></div>
		<div class="archive-grid">
			<?php if ( $items->have_posts() ) : while ( $items->have_posts() ) : $items->the_post(); $event_date = mags_school_event_date(); ?>
				<article class="card cms-story-card"><?php $story_image = mags_school_primary_image_url( get_the_ID(), 'medium_large' ); if ( $story_image ) : ?><img src="<?php echo esc_url( $story_image ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?><p class="eyebrow"><?php echo esc_html( get_post_type() === 'mags_event' && $event_date ? 'Event · ' . $event_date : get_the_date( 'j F Y' ) ); ?></p><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html( get_the_excerpt() ); ?></p><a href="<?php the_permalink(); ?>">Read more →</a></article>
			<?php endwhile; else : ?><div class="empty-state">No news or events have been published yet.</div><?php endif; wp_reset_postdata(); ?>
		</div>
		<?php if ( $items->max_num_pages > 1 ) : ?><nav class="pagination" aria-label="News pagination"><?php echo wp_kses_post( paginate_links( array( 'total' => $items->max_num_pages, 'current' => $paged ) ) ); ?></nav><?php endif; ?>
	</div></section>
	<?php
endwhile;
get_footer();
