<?php
/** Notice Board archive with current and archived views. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$show_archived = isset( $_GET['notice_archive'] ) && sanitize_key( wp_unslash( $_GET['notice_archive'] ) ) === 'archived';
$notice_url    = get_post_type_archive_link( 'mags_notice' );
get_header();
?>
<header class="page-header">
	<div class="wrap">
		<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
				<li aria-current="page"><?php esc_html_e( 'Notice Board', 'mags-school' ); ?></li>
			</ol>
		</nav>
		<h1><?php echo esc_html( $show_archived ? 'Archived Notices' : 'Notice Board' ); ?></h1>
	</div>
</header>
<section class="content-area">
	<div class="wrap">
		<nav class="archive-tabs" aria-label="Notice Board views">
			<a href="<?php echo esc_url( $notice_url ); ?>"<?php if ( ! $show_archived ) { echo ' aria-current="page"'; } ?>>Current notices</a>
			<a href="<?php echo esc_url( add_query_arg( 'notice_archive', 'archived', $notice_url ) ); ?>"<?php if ( $show_archived ) { echo ' aria-current="page"'; } ?>>Archived notices</a>
		</nav>
		<div class="section-heading"><div><p class="eyebrow"><?php echo $show_archived ? 'Previous circulars and updates' : 'Latest school updates'; ?></p><h2><?php echo $show_archived ? 'Past notices remain available for reference.' : 'Published in reverse chronological order.'; ?></h2></div></div>
		<div class="archive-grid">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				<article class="card document-card">
					<p class="eyebrow"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></p>
					<h3><?php the_title(); ?></h3>
					<?php if ( get_the_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
					<a class="button button--outline" href="<?php echo esc_url( mags_school_document_link() ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Open notice: %s', get_the_title() ) ); ?>">Open notice</a>
				</article>
			<?php endwhile; else : ?>
				<div class="empty-state"><?php echo $show_archived ? 'No notices have been archived yet.' : 'No current notices have been published yet.'; ?></div>
			<?php endif; ?>
		</div>
		<?php the_posts_pagination(); ?>
	</div>
</section>
<?php get_footer(); ?>
