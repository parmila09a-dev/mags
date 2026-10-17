<?php
/**
 * Reference-led, fully CMS-managed homepage.
 *
 * Layout and styling follow the approved blue-and-gold school reference while
 * page copy, media, notices, news, links and contact data remain dynamic.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mags_front_id       = get_queried_object_id();
$mags_front_post     = get_post( $mags_front_id );
$mags_hero_image_id  = get_post_thumbnail_id( $mags_front_id );
$mags_hero_fallback  = get_theme_file_uri( '/assets/images/mags-school-building.jpg' );
$mags_logo_url       = get_theme_file_uri( '/assets/images/mags-logo.png' );
$mags_theme_image    = trailingslashit( get_theme_file_uri( '/assets/images' ) );
$mags_school_name = mags_school_option( 'school_name', 'Maharaja Agrasen Global School' );

$mags_notice_url   = get_post_type_archive_link( 'mags_notice' ) ?: home_url( '/notices/' );
$mags_download_url = get_post_type_archive_link( 'mags_download' ) ?: home_url( '/downloads/' );
$mags_gallery_url  = get_post_type_archive_link( 'mags_gallery' ) ?: home_url( '/gallery/' );
$mags_news_page_id = (int) get_option( 'page_for_posts' );
$mags_news_url     = $mags_news_page_id ? get_permalink( $mags_news_page_id ) : home_url( '/news-and-events/' );

$mags_about_url             = mags_school_page_url( 'about-the-school' );
$mags_history_url           = mags_school_page_url( 'history', '/about-the-school/history/' );
$mags_mission_url           = mags_school_page_url( 'vision-and-mission', '/about-the-school/vision-and-mission/' );
$mags_academics_url         = mags_school_page_url( 'academics' );
$mags_infrastructure_url    = mags_school_page_url( 'infrastructure' );
$mags_library_url           = mags_school_page_url( 'library', '/infrastructure/library/' );
$mags_laboratories_url      = mags_school_page_url( 'laboratories', '/infrastructure/laboratories/' );
$mags_sports_url            = mags_school_page_url( 'sports-facilities', '/infrastructure/sports-facilities/' );
$mags_activities_url        = mags_school_page_url( 'student-activities', '/infrastructure/student-activities/' );
$mags_student_support_url   = mags_school_page_url( 'student-support' );
$mags_counselling_url       = mags_school_page_url( 'counselling', '/student-support/counselling/' );
$mags_transport_url         = mags_school_page_url( 'transport-facilities', '/student-support/transport-facilities/' );
$mags_admission_url         = mags_school_page_url( 'admissions' );
$mags_fees_url              = mags_school_page_url( 'fees', '/admissions/fees/' );
$mags_scholarships_url      = mags_school_page_url( 'scholarships', '/admissions/scholarships/' );
$mags_contact_url           = mags_school_page_url( 'contact-us' );
$mags_patron_message_url    = mags_school_page_url( 'patrons-message', '/about-the-school/patrons-message/' );
$mags_chairman_message_url  = mags_school_page_url( 'chairmans-message', '/about-the-school/chairmans-message/' );
$mags_principal_message_url = mags_school_page_url( 'principals-message', '/about-the-school/principals-message/' );

/* Leaf-slug lookup keeps hierarchical WordPress pages editable in the CMS. */
$mags_find_page = static function ( $slug ) {
	static $cache = array();

	$slug = sanitize_title( $slug );
	if ( array_key_exists( $slug, $cache ) ) {
		return $cache[ $slug ];
	}

	$pages = get_posts(
		array(
			'name'                   => $slug,
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$cache[ $slug ] = $pages ? $pages[0] : null;
	return $cache[ $slug ];
};

$mags_clean_text = static function ( $text ) {
	$text = strip_shortcodes( (string) $text );
	/* Preserve readable spacing when Gutenberg/block markup is converted to plain text. */
	$text = preg_replace( '/<br\s*\/?>|<\/(?:p|div|li|h[1-6]|blockquote|section|article)>/i', ' ', $text );

	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
};

$mags_page_excerpt = static function ( $slug, $fallback = '', $words = 50 ) use ( $mags_find_page, $mags_clean_text ) {
	$page = $mags_find_page( $slug );
	$has_excerpt = $page && trim( (string) $page->post_excerpt );
	$text        = $page ? ( $has_excerpt ? (string) $page->post_excerpt : (string) $page->post_content ) : (string) $fallback;
	if ( $page && ! $has_excerpt ) {
		/* Page titles are rendered elsewhere; omit a duplicated opening heading from card summaries. */
		$text = preg_replace( '/^\s*(?:<!--.*?-->\s*)*<h[1-6][^>]*>.*?<\/h[1-6]>\s*(?:<!--.*?-->\s*)*/is', '', $text, 1 );
	}
	$text = $mags_clean_text( $text );

	return $text ? wp_trim_words( $text, $words, '…' ) : '';
};

$mags_page_image_id = static function ( $slug ) use ( $mags_find_page ) {
	$page = $mags_find_page( $slug );
	return $page ? (int) get_post_thumbnail_id( $page ) : 0;
};

$mags_about_page     = $mags_find_page( 'about-the-school' );
$mags_about_excerpt  = $mags_about_page ? $mags_clean_text( $mags_about_page->post_excerpt ) : '';
$mags_hero_intro     = $mags_front_post ? $mags_clean_text( $mags_front_post->post_excerpt ) : '';
if ( ! $mags_hero_intro ) {
	$mags_hero_intro = $mags_about_excerpt ? wp_trim_words( $mags_about_excerpt, 22, '…' ) : 'Where discipline shapes character, innovation drives learning, sports build spirit, and academics form the core.';
}

$mags_welcome_content = $mags_front_post ? trim( (string) $mags_front_post->post_content ) : '';
$mags_setup_placeholder = 'The MAGS homepage is supplied by the active theme.';
if ( $mags_welcome_content && $mags_setup_placeholder === trim( wp_strip_all_tags( $mags_welcome_content ) ) ) {
	$mags_welcome_content = '';
}
if ( $mags_welcome_content ) {
	/* A second H1 entered in the page editor becomes an H2 on the homepage. */
	$mags_welcome_content = preg_replace( '/<h1([^>]*)>(.*?)<\/h1>/is', '<h2$1>$2</h2>', $mags_welcome_content );
	$mags_welcome_content = apply_filters( 'the_content', $mags_welcome_content );
} else {
	$mags_welcome_text    = $mags_about_excerpt ? wp_trim_words( $mags_about_excerpt, 68, '…' ) : 'Established in 2010, Maharaja Agrasen Global School provides quality, value-based education from Pre-primary onwards. Guided by “आत्म दीपो भवः”—Be your own light—we help every learner grow with confidence, curiosity and responsibility.';
	$mags_welcome_content = '<h2>Inspiring excellence, nurturing values.</h2><p>' . esc_html( $mags_welcome_text ) . '</p>';
}

$mags_patron_page    = $mags_find_page( 'patrons-message' );
$mags_chairman_page  = $mags_find_page( 'chairmans-message' );
$mags_principal_page = $mags_find_page( 'principals-message' );

$mags_leaders = array(
	array(
		'name'     => $mags_patron_page ? ( get_post_meta( $mags_patron_page->ID, '_mags_leader_name', true ) ?: 'Dr. Nand Kishore Garg' ) : 'Dr. Nand Kishore Garg',
		'role'     => $mags_patron_page ? ( get_post_meta( $mags_patron_page->ID, '_mags_leader_role', true ) ?: 'Patron' ) : 'Patron',
		'image_id' => $mags_patron_page ? (int) get_post_thumbnail_id( $mags_patron_page ) : 0,
		'image'    => get_theme_file_uri( '/assets/images/patron-dr-nand-kishore-garg.jpg' ),
		'url'      => $mags_patron_message_url,
		'excerpt'  => $mags_page_excerpt( 'patrons-message', 'Our continuing dream is to ensure value-based, quality education for every student with dedication and commitment.', 24 ),
		'width'    => 267,
		'height'   => 244,
	),
	array(
		'name'     => $mags_chairman_page ? ( get_post_meta( $mags_chairman_page->ID, '_mags_leader_name', true ) ?: 'Er. T. R. Garg' ) : 'Er. T. R. Garg',
		'role'     => $mags_chairman_page ? ( get_post_meta( $mags_chairman_page->ID, '_mags_leader_role', true ) ?: 'Chairman' ) : 'Chairman',
		'image_id' => $mags_chairman_page ? (int) get_post_thumbnail_id( $mags_chairman_page ) : 0,
		'image'    => get_theme_file_uri( '/assets/images/chairman-tr-garg.jpg' ),
		'url'      => $mags_chairman_message_url,
		'excerpt'  => $mags_page_excerpt( 'chairmans-message', '', 24 ),
		'width'    => 1000,
		'height'   => 1024,
	),
	array(
		'name'     => $mags_principal_page ? ( get_post_meta( $mags_principal_page->ID, '_mags_leader_name', true ) ?: 'Meetu Aggarwal' ) : 'Meetu Aggarwal',
		'role'     => $mags_principal_page ? ( get_post_meta( $mags_principal_page->ID, '_mags_leader_role', true ) ?: 'Principal' ) : 'Principal',
		'image_id' => $mags_principal_page ? (int) get_post_thumbnail_id( $mags_principal_page ) : 0,
		'image'    => get_theme_file_uri( '/assets/images/principal-meetu-aggarwal.png' ),
		'url'      => $mags_principal_message_url,
		'excerpt'  => $mags_page_excerpt( 'principals-message', 'Discipline is the foundation, innovation is the spark, sports build the spirit, and academics form the core.', 24 ),
		'width'    => 195,
		'height'   => 258,
	),
);

/* Welcome collage: latest CMS albums first, approved school photos as fallback. */
$mags_welcome_images = array();
$mags_gallery_posts  = get_posts(
	array(
		'post_type'      => 'mags_gallery',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	)
);

foreach ( $mags_gallery_posts as $mags_gallery_post ) {
	$mags_gallery_image_id = (int) get_post_thumbnail_id( $mags_gallery_post );
	if ( ! $mags_gallery_image_id ) {
		$mags_gallery_image_ids = mags_school_gallery_image_ids( $mags_gallery_post->ID );
		$mags_gallery_image_id  = $mags_gallery_image_ids ? (int) $mags_gallery_image_ids[0] : 0;
	}
	if ( $mags_gallery_image_id ) {
		$mags_welcome_images[] = array(
			'id'    => $mags_gallery_image_id,
			'image' => '',
			'alt'   => get_the_title( $mags_gallery_post ),
			'url'   => get_permalink( $mags_gallery_post ),
		);
	}
	if ( 3 === count( $mags_welcome_images ) ) {
		break;
	}
}

$mags_official_gallery = mags_school_official_gallery_items();
$mags_fallback_gallery = array( 1, 4, 3 );
foreach ( $mags_fallback_gallery as $mags_fallback_index ) {
	if ( 3 <= count( $mags_welcome_images ) || empty( $mags_official_gallery[ $mags_fallback_index ] ) ) {
		break;
	}
	$mags_fallback_item   = $mags_official_gallery[ $mags_fallback_index ];
	$mags_welcome_images[] = array(
		'id'    => 0,
		'image' => $mags_fallback_item['image'],
		'alt'   => $mags_fallback_item['title'],
		'url'   => $mags_gallery_url,
	);
}

/* Computer-lab album remains CMS-managed and keeps the requested page-turn UI. */
$mags_learning_gallery = get_page_by_path( 'computer-lab', OBJECT, 'mags_gallery' );
$mags_learning_ids     = array();
$mags_learning_url     = $mags_gallery_url;
if ( $mags_learning_gallery && 'publish' === $mags_learning_gallery->post_status ) {
	$mags_learning_url = get_permalink( $mags_learning_gallery );
	$mags_learning_ids = mags_school_gallery_image_ids( $mags_learning_gallery->ID );
	$mags_cover_id      = get_post_thumbnail_id( $mags_learning_gallery );
	if ( $mags_cover_id ) {
		array_unshift( $mags_learning_ids, $mags_cover_id );
	}
	$mags_learning_ids = array_values( array_unique( array_filter( array_map( 'absint', $mags_learning_ids ) ) ) );
}
$mags_learning_ids     = array_slice( $mags_learning_ids, 0, 4 );
$mags_photo_book_count = count( $mags_learning_ids );

$mags_facilities = array(
	array( 'Building', $mags_infrastructure_url, $mags_page_image_id( 'infrastructure' ) ?: $mags_hero_image_id, $mags_hero_fallback, 'BU' ),
	array( 'Library', $mags_library_url, $mags_page_image_id( 'library' ), '', 'LI' ),
	array( 'Computer Laboratory', $mags_learning_url, $mags_learning_ids ? $mags_learning_ids[0] : 0, $mags_theme_image . 'gallery-computer-lab.jpg', 'CL' ),
	array( 'Mathematics Laboratory', $mags_laboratories_url, $mags_page_image_id( 'laboratories' ), '', 'MA' ),
	array( 'Biotechnology Laboratory', $mags_laboratories_url, $mags_page_image_id( 'laboratories' ), '', 'BI' ),
	array( 'Chemistry Laboratory', $mags_laboratories_url, $mags_page_image_id( 'laboratories' ), '', 'CH' ),
	array( 'Sports Facilities', $mags_sports_url, $mags_page_image_id( 'sports-facilities' ), '', 'SP' ),
	array( 'Music, Dance, Drama & Cultural', $mags_activities_url, $mags_page_image_id( 'student-activities' ), $mags_theme_image . 'gallery-cultural-programme.jpg', 'CU' ),
);

$mags_counselling_topics = array();
$mags_counselling_page   = $mags_find_page( 'counselling' );
if ( $mags_counselling_page && preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', (string) $mags_counselling_page->post_content, $mags_topic_matches ) ) {
	foreach ( $mags_topic_matches[1] as $mags_topic_match ) {
		$mags_topic = trim( wp_strip_all_tags( $mags_topic_match ) );
		if ( $mags_topic ) {
			$mags_counselling_topics[] = $mags_topic;
		}
	}
}
if ( ! $mags_counselling_topics ) {
	$mags_counselling_topics = array( 'Personal Counselling', 'Educational Counselling', 'Developmental Counselling', 'Social Counselling', 'Spiritual / Cultural Counselling' );
}
$mags_counselling_summary = 'Supporting students in every step of their journey.';
if ( $mags_counselling_page && trim( (string) $mags_counselling_page->post_excerpt ) ) {
	$mags_counselling_summary = wp_trim_words( $mags_clean_text( $mags_counselling_page->post_excerpt ), 28, '…' );
}

$mags_notices = new WP_Query(
	array(
		'post_type'      => 'mags_notice',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'     => '_mags_notice_archived',
				'compare' => 'NOT EXISTS',
			),
		),
	)
);

$mags_collect_update = static function () {
	$mags_image_id = (int) get_post_thumbnail_id();
	if ( ! $mags_image_id ) {
		$mags_gallery_ids = mags_school_gallery_image_ids();
		$mags_image_id    = $mags_gallery_ids ? (int) $mags_gallery_ids[0] : 0;
	}
	$mags_is_event   = 'mags_event' === get_post_type();
	$mags_event_date = $mags_is_event ? get_post_meta( get_the_ID(), '_mags_event_date', true ) : '';
	$mags_event_time = $mags_event_date ? strtotime( $mags_event_date ) : false;

	return array(
		'id'       => get_the_ID(),
		'type'     => $mags_is_event ? 'Event' : 'News',
		'date'     => $mags_event_time ? date_i18n( get_option( 'date_format' ), $mags_event_time ) : get_the_date(),
		'day'      => $mags_event_time ? date_i18n( 'd', $mags_event_time ) : get_the_date( 'd' ),
		'month'    => $mags_event_time ? date_i18n( 'M', $mags_event_time ) : get_the_date( 'M' ),
		'datetime' => $mags_event_time ? gmdate( 'Y-m-d', $mags_event_time ) : get_the_date( 'c' ),
		'title'    => get_the_title(),
		'url'      => get_permalink(),
		'image_id' => $mags_image_id,
	);
};

$mags_updates  = array();
$mags_seen_ids = array();
$mags_featured_query = new WP_Query(
	array(
		'post_type'           => array( 'post', 'mags_event' ),
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => '_mags_featured_homepage',
		'meta_value'          => '1',
	)
);
if ( $mags_featured_query->have_posts() ) {
	$mags_featured_query->the_post();
	$mags_updates[]  = $mags_collect_update();
	$mags_seen_ids[] = get_the_ID();
}
wp_reset_postdata();

$mags_latest_query = new WP_Query(
	array(
		'post_type'           => array( 'post', 'mags_event' ),
		'post_status'         => 'publish',
		'posts_per_page'      => 3 - count( $mags_updates ),
		'post__not_in'        => $mags_seen_ids,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
while ( $mags_latest_query->have_posts() ) {
	$mags_latest_query->the_post();
	$mags_updates[] = $mags_collect_update();
}
wp_reset_postdata();
?>

<div class="mags-reference-home">
	<section class="mags-reference-hero" aria-labelledby="mags-reference-title">
		<div class="mags-reference-hero__media" aria-hidden="true">
			<?php if ( $mags_hero_image_id ) : ?>
				<?php echo wp_get_attachment_image( $mags_hero_image_id, 'full', false, array( 'alt' => '', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '100vw' ) ); ?>
			<?php else : ?>
				<img src="<?php echo esc_url( $mags_hero_fallback ); ?>" alt="" width="1800" height="1322" fetchpriority="high" decoding="async">
			<?php endif; ?>
		</div>
		<div class="wrap mags-reference-hero__inner">
			<div class="mags-reference-hero__copy">
				<p class="mags-reference-kicker">Delhi <span aria-hidden="true">·</span> Established 2010</p>
				<h1 id="mags-reference-title"><?php echo esc_html( $mags_school_name ); ?></h1>
				<p class="mags-reference-hero__motto"><span lang="hi">आत्म दीपो भवः</span><strong>Be Your Own Light</strong></p>
				<p class="mags-reference-hero__intro"><?php echo esc_html( $mags_hero_intro ); ?></p>
				<div class="mags-reference-actions">
					<a class="mags-reference-button mags-reference-button--gold" href="<?php echo esc_url( $mags_about_url ); ?>">Explore Our School <span aria-hidden="true">→</span></a>
					<a class="mags-reference-button mags-reference-button--outline" href="<?php echo esc_url( $mags_admission_url ); ?>">Admission Information</a>
				</div>
				<nav class="mags-reference-values" aria-label="MAGS learning values">
					<a href="<?php echo esc_url( $mags_mission_url ); ?>"><b>01</b><span>Discipline</span></a>
					<a href="<?php echo esc_url( $mags_academics_url ); ?>"><b>02</b><span>Innovation</span></a>
					<a href="<?php echo esc_url( $mags_sports_url ); ?>"><b>03</b><span>Sports</span></a>
					<a href="<?php echo esc_url( $mags_academics_url ); ?>"><b>04</b><span>Academics</span></a>
				</nav>
			</div>
			<aside class="mags-reference-award" aria-label="School recognition">
				<span aria-hidden="true">★</span>
				<div><strong>School of Excellence</strong><small>Rated by Indian Talent Olympiad</small><b>2024</b></div>
			</aside>
		</div>
	</section>

	<section class="mags-reference-section mags-reference-welcome" aria-label="Welcome to Maharaja Agrasen Global School">
		<div class="wrap mags-reference-welcome__grid">
			<div class="mags-reference-welcome__copy">
				<p class="mags-reference-kicker">Welcome to MAGS</p>
				<div class="mags-reference-cms-copy"><?php echo $mags_welcome_content; ?></div>
				<div class="mags-reference-actions"><a class="mags-reference-button mags-reference-button--navy" href="<?php echo esc_url( $mags_about_url ); ?>">Know More About Us <span aria-hidden="true">→</span></a><a class="mags-reference-text-link" href="<?php echo esc_url( $mags_history_url ); ?>">Our history</a></div>
			</div>
			<div class="mags-reference-collage">
				<?php foreach ( $mags_welcome_images as $mags_collage_index => $mags_collage_image ) : ?>
					<a class="mags-reference-collage__item mags-reference-collage__item--<?php echo esc_attr( $mags_collage_index + 1 ); ?>" href="<?php echo esc_url( $mags_collage_image['url'] ); ?>">
						<?php if ( $mags_collage_image['id'] ) : ?>
							<?php echo wp_get_attachment_image( $mags_collage_image['id'], 'large', false, array( 'alt' => mags_school_attachment_alt( $mags_collage_image['id'], $mags_collage_image['alt'] ), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) 100vw, 44vw' ) ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( $mags_collage_image['image'] ); ?>" alt="<?php echo esc_attr( $mags_collage_image['alt'] ); ?>" loading="lazy" decoding="async">
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="mags-reference-section mags-reference-pillars" aria-label="School legacy and mission">
		<div class="wrap mags-reference-pillars__grid">
			<article class="mags-reference-pillar mags-reference-pillar--legacy">
				<div class="mags-reference-pillar__emblem" aria-hidden="true"><span>अग्रसेन</span><small>One Brick · One Coin</small></div>
				<div class="mags-reference-pillar__copy">
					<p class="mags-reference-kicker">Our namesake</p>
					<h2>The Legacy of Maharaja Agrasen</h2>
					<p><?php echo esc_html( $mags_page_excerpt( 'about-the-school', 'A legendary king whose values of equality, enterprise and service continue to guide our vision.', 52 ) ); ?></p>
					<a class="mags-reference-button mags-reference-button--navy" href="<?php echo esc_url( $mags_about_url ); ?>">Discover the Legacy <span aria-hidden="true">→</span></a>
				</div>
			</article>
			<article class="mags-reference-pillar mags-reference-pillar--mission">
				<div class="mags-reference-pillar__copy">
					<p class="mags-reference-kicker">Our Mission</p>
					<h2>Lighting the Path of Excellence</h2>
					<p><?php echo esc_html( $mags_page_excerpt( 'vision-and-mission', 'To foster every student to their fullest potential in intellectual growth, skill development and character.', 52 ) ); ?></p>
					<a class="mags-reference-button mags-reference-button--navy" href="<?php echo esc_url( $mags_mission_url ); ?>">Our Mission <span aria-hidden="true">→</span></a>
				</div>
				<img src="<?php echo esc_url( $mags_logo_url ); ?>" alt="" width="1600" height="943" loading="lazy" decoding="async">
			</article>
		</div>
	</section>

	<section class="mags-reference-section mags-reference-leadership" aria-labelledby="mags-reference-leadership-title">
		<div class="wrap">
			<header class="mags-reference-heading"><span></span><h2 id="mags-reference-leadership-title">Messages from Our Leadership</h2><span></span></header>
			<div class="mags-reference-leaders">
				<?php foreach ( $mags_leaders as $leader ) : ?>
					<article class="mags-reference-leader">
						<a class="mags-reference-leader__image" href="<?php echo esc_url( $leader['url'] ); ?>" aria-label="Read the <?php echo esc_attr( $leader['role'] ); ?>’s message"><?php if ( $leader['image_id'] ) : ?><?php echo wp_get_attachment_image( $leader['image_id'], 'medium', false, array( 'alt' => $leader['name'] . ', ' . $leader['role'], 'loading' => 'lazy', 'decoding' => 'async' ) ); ?><?php else : ?><img src="<?php echo esc_url( $leader['image'] ); ?>" alt="<?php echo esc_attr( $leader['name'] . ', ' . $leader['role'] ); ?>" width="<?php echo esc_attr( $leader['width'] ); ?>" height="<?php echo esc_attr( $leader['height'] ); ?>" loading="lazy" decoding="async"><?php endif; ?></a>
						<div class="mags-reference-leader__copy"><h3>From the <?php echo esc_html( $leader['role'] ); ?></h3><strong><?php echo esc_html( $leader['name'] ); ?></strong><small><?php echo esc_html( $leader['role'] ); ?></small><?php if ( $leader['excerpt'] ) : ?><p><?php echo esc_html( $leader['excerpt'] ); ?></p><?php endif; ?><a href="<?php echo esc_url( $leader['url'] ); ?>">Read Message <span aria-hidden="true">→</span></a></div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="mags-reference-section mags-reference-infrastructure" aria-labelledby="mags-reference-infrastructure-title">
		<div class="wrap">
			<header class="mags-reference-heading mags-reference-heading--light"><span></span><h2 id="mags-reference-infrastructure-title">School Infrastructure</h2><span></span></header>
			<div class="mags-reference-facilities">
				<?php foreach ( $mags_facilities as $mags_facility ) : ?>
					<a class="mags-reference-facility" href="<?php echo esc_url( $mags_facility[1] ); ?>">
						<div class="mags-reference-facility__media">
							<?php if ( $mags_facility[2] ) : ?><?php echo wp_get_attachment_image( $mags_facility[2], 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?><?php elseif ( $mags_facility[3] ) : ?><img src="<?php echo esc_url( $mags_facility[3] ); ?>" alt="" loading="lazy" decoding="async"><?php else : ?><span aria-hidden="true"><?php echo esc_html( $mags_facility[4] ); ?></span><?php endif; ?>
						</div>
						<strong><?php echo esc_html( $mags_facility[0] ); ?></strong>
					</a>
				<?php endforeach; ?>
			</div>
			<div class="mags-reference-infrastructure__action"><a class="mags-reference-button mags-reference-button--gold" href="<?php echo esc_url( $mags_infrastructure_url ); ?>">Explore All Facilities <span aria-hidden="true">→</span></a></div>

			<?php if ( 1 < $mags_photo_book_count ) : ?>
				<div class="mags-reference-labbook">
					<header><p class="mags-reference-kicker">Computer Laboratory</p><h3>A closer look at learning in action</h3><p>Scroll to turn through photographs managed in the Computer Lab gallery.</p></header>
					<div class="mags-photo-book" data-photo-book role="group" aria-label="Computer laboratory photo book with <?php echo esc_attr( $mags_photo_book_count ); ?> photographs">
						<div class="mags-photo-book__sticky"><div class="mags-photo-book__stage"><div class="mags-photo-book__pages">
							<?php foreach ( $mags_learning_ids as $mags_page_index => $mags_learning_id ) : ?>
								<?php $mags_page_style = sprintf( '--page-index:%d; --page-z:%d; --page-turn:0; --page-angle:0deg; --page-shadow:0;', $mags_page_index, $mags_photo_book_count - $mags_page_index ); ?>
								<figure class="mags-photo-book__page" data-photo-book-page style="<?php echo esc_attr( $mags_page_style ); ?>"><div class="mags-photo-book__face mags-photo-book__front"><?php echo wp_get_attachment_image( $mags_learning_id, 'large', false, array( 'alt' => mags_school_attachment_alt( $mags_learning_id, sprintf( 'Students in the MAGS computer laboratory, photograph %d', $mags_page_index + 1 ) ), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></div><div class="mags-photo-book__face mags-photo-book__back" aria-hidden="true"><span>MAGS</span><p lang="hi">आत्म दीपो भवः</p><small>Be your own light</small></div></figure>
							<?php endforeach; ?>
						</div></div><div class="mags-photo-book__meta"><p>Computer Laboratory</p><span aria-hidden="true"><b data-photo-book-current>01</b> / <?php echo esc_html( str_pad( (string) $mags_photo_book_count, 2, '0', STR_PAD_LEFT ) ); ?></span><a href="<?php echo esc_url( $mags_learning_url ); ?>">Open album</a></div></div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="mags-reference-section mags-reference-support" aria-labelledby="mags-reference-support-title">
		<div class="wrap">
			<div class="mags-reference-support__title"><div><p class="mags-reference-kicker">Student Support</p><h2 id="mags-reference-support-title">Counselling & Student Support</h2></div><a class="mags-reference-text-link" href="<?php echo esc_url( $mags_transport_url ); ?>">Transport Facilities <span aria-hidden="true">→</span></a></div>
			<div class="mags-reference-support__grid">
				<ul class="mags-reference-support__topics"><?php foreach ( $mags_counselling_topics as $mags_topic_index => $mags_counselling_topic ) : ?><li><span><?php echo esc_html( str_pad( (string) ( $mags_topic_index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $mags_counselling_topic ); ?></li><?php endforeach; ?></ul>
				<aside><div aria-hidden="true">◎</div><p><?php echo esc_html( $mags_counselling_summary ); ?></p><a class="mags-reference-button mags-reference-button--light" href="<?php echo esc_url( $mags_counselling_url ); ?>">Know More <span aria-hidden="true">→</span></a></aside>
			</div>
		</div>
	</section>

	<section class="mags-reference-section mags-reference-updates" aria-labelledby="mags-reference-updates-title">
		<div class="wrap mags-reference-updates__grid">
			<section class="mags-reference-news" aria-labelledby="mags-reference-updates-title">
				<header><h2 id="mags-reference-updates-title">Latest News & Events</h2><a href="<?php echo esc_url( $mags_news_url ); ?>">View all <span aria-hidden="true">→</span></a></header>
				<div class="mags-reference-news__grid">
					<?php if ( $mags_updates ) : ?>
						<?php foreach ( $mags_updates as $mags_update_index => $mags_update ) : ?>
							<article><a href="<?php echo esc_url( $mags_update['url'] ); ?>"><div class="mags-reference-news__image"><?php if ( $mags_update['image_id'] ) : ?><?php echo wp_get_attachment_image( $mags_update['image_id'], 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?><?php else : ?><img src="<?php echo esc_url( $mags_hero_fallback ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></div><div class="mags-reference-news__copy"><time datetime="<?php echo esc_attr( $mags_update['datetime'] ); ?>"><b><?php echo esc_html( $mags_update['day'] ); ?></b><small><?php echo esc_html( $mags_update['month'] ); ?></small></time><h3><?php echo esc_html( $mags_update['title'] ); ?></h3></div></a></article>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="mags-reference-empty">News and events published in the CMS will appear here automatically.</p>
					<?php endif; ?>
				</div>
			</section>

			<section class="mags-reference-notices" aria-labelledby="mags-reference-notices-title">
				<header><h2 id="mags-reference-notices-title">Notice Board</h2><a href="<?php echo esc_url( $mags_notice_url ); ?>">View all <span aria-hidden="true">→</span></a></header>
				<ul>
					<?php if ( $mags_notices->have_posts() ) : ?>
						<?php while ( $mags_notices->have_posts() ) : $mags_notices->the_post(); ?><li><span aria-hidden="true">◇</span><a href="<?php echo esc_url( mags_school_document_link() ); ?>"><?php the_title(); ?></a><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></li><?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					<?php else : ?>
						<li class="mags-reference-notices__empty">Notices published in the CMS will appear here automatically.</li>
					<?php endif; ?>
				</ul>
			</section>
		</div>
		<nav class="wrap mags-reference-resources" aria-label="School resources"><a href="<?php echo esc_url( $mags_admission_url ); ?>">Admissions</a><a href="<?php echo esc_url( $mags_fees_url ); ?>">Fee Structure</a><a href="<?php echo esc_url( $mags_scholarships_url ); ?>">Scholarships</a><a href="<?php echo esc_url( $mags_download_url ); ?>">Downloads</a><a href="<?php echo esc_url( $mags_gallery_url ); ?>">Gallery</a><a href="<?php echo esc_url( $mags_contact_url ); ?>">Contact Us</a></nav>
	</section>
</div>
