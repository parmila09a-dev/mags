<?php
/** Generic single content template. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_type = get_post_type();

	if ( 'mags_notice' === $post_type ) {
		$parent_label = 'Notice Board';
		$parent_url   = get_post_type_archive_link( 'mags_notice' );
	} elseif ( 'mags_download' === $post_type ) {
		$parent_label = 'Downloads';
		$parent_url   = get_post_type_archive_link( 'mags_download' );
	} else {
		$parent_label = 'News & Events';
		$parent_url   = mags_school_page_url( 'news-and-events' );
	}
	?>
	<header class="page-header">
		<div class="wrap">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
					<li><a href="<?php echo esc_url( $parent_url ); ?>"><?php echo esc_html( $parent_label ); ?></a></li>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>
	<article class="content-area">
		<div class="wrap entry-content">
			<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
