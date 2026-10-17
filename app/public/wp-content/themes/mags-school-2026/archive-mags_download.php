<?php
/** Download centre organized by document type. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
$download_types = get_terms( array( 'taxonomy' => 'mags_download_type', 'hide_empty' => false ) );
?>
<header class="page-header">
	<div class="wrap">
		<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
				<li aria-current="page"><?php esc_html_e( 'Downloads', 'mags-school' ); ?></li>
			</ol>
		</nav>
		<h1><?php esc_html_e( 'Downloads', 'mags-school' ); ?></h1>
	</div>
</header>
<section class="content-area"><div class="wrap">
	<?php if ( $download_types && ! is_wp_error( $download_types ) ) : ?><nav class="archive-tabs" aria-label="Download categories"><a href="<?php echo esc_url( get_post_type_archive_link( 'mags_download' ) ); ?>" aria-current="page">All documents</a><?php foreach ( $download_types as $download_type ) : ?><a href="<?php echo esc_url( get_term_link( $download_type ) ); ?>"><?php echo esc_html( $download_type->name ); ?></a><?php endforeach; ?></nav><?php endif; ?>
	<div class="section-heading"><div><p class="eyebrow">Document centre</p><h2>Circulars, brochures, lists and official PDFs.</h2></div><p>Administrators can upload, categorize, replace or remove every document through WordPress.</p></div>
	<div class="archive-grid">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $types = get_the_terms( get_the_ID(), 'mags_download_type' ); ?>
			<article class="card document-card"><p class="eyebrow"><?php echo $types && ! is_wp_error( $types ) ? esc_html( implode( ', ', wp_list_pluck( $types, 'name' ) ) ) : 'Document'; ?></p><h3><?php the_title(); ?></h3><?php if ( get_the_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?><a class="button button--outline" href="<?php echo esc_url( mags_school_document_link() ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Open PDF: %s', get_the_title() ) ); ?>">Open PDF</a></article>
		<?php endwhile; else : ?><div class="empty-state">No documents have been published yet.</div><?php endif; ?>
	</div>
	<?php the_posts_pagination(); ?>
</div></section>
<?php get_footer(); ?>
