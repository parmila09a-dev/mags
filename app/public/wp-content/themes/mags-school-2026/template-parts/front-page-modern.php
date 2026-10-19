<?php
/**
 * Clean, production-focused and CMS-managed homepage.
 *
 * School-approved copy is used as a fallback only. WordPress pages, featured
 * images, galleries, notices, news and events remain the primary data source.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mags_image_base   = trailingslashit( get_theme_file_uri( '/assets/images' ) );
$mags_mission_icon = $mags_image_base . 'mags-site-icon.png';
$mags_school_name  = mags_school_option( 'school_name', 'Maharaja Agrasen Global School' );

$mags_journey_images = array(
	array( 'src' => $mags_image_base . 'mags-school-building.jpg', 'width' => 1800, 'height' => 1322 ),
	array( 'src' => $mags_image_base . 'journey/mait-campus-front.webp', 'width' => 1360, 'height' => 938 ),
	array( 'src' => $mags_image_base . 'journey/mait-campus-gate.webp', 'width' => 1360, 'height' => 1020 ),
	array( 'src' => $mags_image_base . 'journey/mau-campus-gateway.webp', 'width' => 1360, 'height' => 895 ),
	array( 'src' => $mags_image_base . 'journey/mau-campus-green.webp', 'width' => 1080, 'height' => 791 ),
);

$mags_notice_url   = get_post_type_archive_link( 'mags_notice' ) ?: home_url( '/notices/' );
$mags_download_url = get_post_type_archive_link( 'mags_download' ) ?: home_url( '/downloads/' );
$mags_gallery_url  = get_post_type_archive_link( 'mags_gallery' ) ?: home_url( '/gallery/' );
$mags_news_page_id = (int) get_option( 'page_for_posts' );
$mags_news_url     = $mags_news_page_id ? get_permalink( $mags_news_page_id ) : mags_school_page_url( 'news-and-events' );

$mags_about_url             = mags_school_page_url( 'about-the-school' );
$mags_history_url           = mags_school_page_url( 'history', '/about-the-school/history/' );
$mags_mission_url           = mags_school_page_url( 'vision-and-mission', '/about-the-school/vision-and-mission/' );
$mags_infrastructure_url    = mags_school_page_url( 'infrastructure' );
$mags_library_url           = mags_school_page_url( 'library', '/infrastructure/library/' );
$mags_laboratories_url      = mags_school_page_url( 'laboratories', '/infrastructure/laboratories/' );
$mags_sports_url            = mags_school_page_url( 'sports-facilities', '/infrastructure/sports-facilities/' );
$mags_activities_url        = mags_school_page_url( 'student-activities', '/infrastructure/student-activities/' );
$mags_counselling_url       = mags_school_page_url( 'counselling', '/student-support/counselling/' );
$mags_transport_url         = mags_school_page_url( 'transport-facilities', '/student-support/transport-facilities/' );
$mags_admission_url         = mags_school_page_url( 'admissions' );
$mags_fees_url              = mags_school_page_url( 'fees', '/admissions/fees/' );
$mags_scholarships_url      = mags_school_page_url( 'scholarships', '/admissions/scholarships/' );
$mags_contact_url           = mags_school_page_url( 'contact-us' );
$mags_patron_message_url    = mags_school_page_url( 'patrons-message', '/about-the-school/patrons-message/' );
$mags_chairman_message_url  = mags_school_page_url( 'chairmans-message', '/about-the-school/chairmans-message/' );
$mags_principal_message_url = mags_school_page_url( 'principals-message', '/about-the-school/principals-message/' );

$mags_hero_album         = get_page_by_path( 'homepage-hero', OBJECT, 'mags_gallery' );
$mags_hero_image_ids     = $mags_hero_album ? get_post_meta( $mags_hero_album->ID, '_mags_gallery_image_ids', true ) : array();
$mags_hero_image_ids     = is_string( $mags_hero_image_ids ) ? explode( ',', $mags_hero_image_ids ) : (array) $mags_hero_image_ids;
$mags_hero_image_ids     = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $mags_hero_image_ids ), 'wp_attachment_is_image' ) ) ), 0, 5 );
$mags_hero_slide_content = $mags_hero_album ? get_post_meta( $mags_hero_album->ID, '_mags_hero_slide_content', true ) : array();
$mags_hero_slide_content = is_array( $mags_hero_slide_content ) ? $mags_hero_slide_content : array();
$mags_hero_legacy        = $mags_hero_image_ids && isset( $mags_hero_slide_content[ $mags_hero_image_ids[0] ] ) && is_array( $mags_hero_slide_content[ $mags_hero_image_ids[0] ] ) ? $mags_hero_slide_content[ $mags_hero_image_ids[0] ] : array();
$mags_hero_fixed         = $mags_hero_album ? get_post_meta( $mags_hero_album->ID, '_mags_hero_fixed_content', true ) : array();
$mags_hero_fixed         = array_merge( $mags_hero_legacy, is_array( $mags_hero_fixed ) ? $mags_hero_fixed : array() );
$mags_hero_value         = static function ( $key, $fallback ) use ( $mags_hero_fixed ) {
	$value = isset( $mags_hero_fixed[ $key ] ) && is_scalar( $mags_hero_fixed[ $key ] ) ? trim( (string) $mags_hero_fixed[ $key ] ) : '';
	return '' !== $value ? $value : $fallback;
};
$mags_hero_content       = array(
	'eyebrow'         => $mags_hero_value( 'eyebrow', 'Delhi · Established 2010' ),
	'heading'         => $mags_hero_value( 'heading', $mags_school_name ),
	'motto_hi'        => $mags_hero_value( 'motto_hi', 'आत्म दीपो भवः' ),
	'motto_en'        => $mags_hero_value( 'motto_en', 'Be your own light' ),
	'description'     => $mags_hero_value( 'description', 'Where discipline shapes character, innovation drives learning, sports build spirit, and academics form the core.' ),
	'primary_label'   => $mags_hero_value( 'primary_label', 'Explore MAGS' ),
	'primary_url'     => $mags_hero_value( 'primary_url', $mags_about_url ),
	'secondary_label' => $mags_hero_value( 'secondary_label', 'Admissions' ),
	'secondary_url'   => $mags_hero_value( 'secondary_url', $mags_admission_url ),
);
$mags_hero_slides        = array();

if ( $mags_hero_image_ids ) {
	foreach ( $mags_hero_image_ids as $mags_hero_index => $mags_hero_image_id ) {
		$mags_hero_slide = isset( $mags_hero_slide_content[ $mags_hero_image_id ] ) && is_array( $mags_hero_slide_content[ $mags_hero_image_id ] ) ? $mags_hero_slide_content[ $mags_hero_image_id ] : array();
		$mags_badge_on   = array_key_exists( 'badge_enabled', $mags_hero_slide ) ? '1' === (string) $mags_hero_slide['badge_enabled'] : 0 === $mags_hero_index;
		$mags_hero_slides[] = array(
			'id'           => $mags_hero_image_id,
			'image'        => '',
			'badge_on'     => $mags_badge_on,
			'badge_title'  => ! empty( $mags_hero_slide['badge_title'] ) ? $mags_hero_slide['badge_title'] : 'School of Excellence',
			'badge_detail' => ! empty( $mags_hero_slide['badge_detail'] ) ? $mags_hero_slide['badge_detail'] : 'Indian Talent Olympiad · 2024',
		);
	}
} else {
	$mags_hero_slides[] = array(
		'id'           => 0,
		'image'        => $mags_image_base . 'mags-school-building.jpg',
		'badge_on'     => true,
		'badge_title'  => 'School of Excellence',
		'badge_detail' => 'Indian Talent Olympiad · 2024',
	);
}
$mags_hero_slide_total = count( $mags_hero_slides );

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
	$text = preg_replace( '/<br\s*\/?>|<\/(?:p|div|li|h[1-6]|blockquote|section|article)>/i', ' ', $text );
	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
};

$mags_page_excerpt = static function ( $slug, $fallback = '', $words = 55 ) use ( $mags_find_page, $mags_clean_text ) {
	$page = $mags_find_page( $slug );
	$text = '';

	if ( $page ) {
		$text = trim( (string) $page->post_excerpt );
		if ( ! $text ) {
			$text = preg_replace( '/^\s*(?:<!--.*?-->\s*)*<h[1-6][^>]*>.*?<\/h[1-6]>\s*(?:<!--.*?-->\s*)*/is', '', (string) $page->post_content, 1 );
		}
	}

	$text = $mags_clean_text( $text ?: $fallback );
	return $text ? wp_trim_words( $text, $words, '…' ) : '';
};

$mags_legacy_summary = $mags_page_excerpt(
	'about-the-school',
	'Maharaja Agrasen established an egalitarian society rooted in inclusive governance, social equality and economic empowerment through entrepreneurship, trade and commerce.',
	64
);
$mags_mission_summary = $mags_page_excerpt(
	'vision-and-mission',
	'The school fosters every learner towards their fullest potential through intellectual growth, skill development and a spirit of service to the community, country and humanity.',
	52
);
$mags_history_summary = $mags_page_excerpt(
	'history',
	'Sponsored by the Maharaja Agrasen Technical Education Society, Delhi, the school is part of a continuing commitment to quality education, Indian values and service to humanity.',
	55
);

$mags_history_milestones = array();
$mags_history_page       = $mags_find_page( 'history' );
if ( $mags_history_page && preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', (string) $mags_history_page->post_content, $mags_history_matches ) ) {
	foreach ( $mags_history_matches[1] as $mags_history_match ) {
		$mags_history_item = $mags_clean_text( $mags_history_match );
		if ( $mags_history_item ) {
			$mags_history_milestones[] = $mags_history_item;
		}
	}
}

$mags_principles = array(
	array( '01', 'Discipline', 'Building self-discipline, respect, responsibility and integrity.' ),
	array( '02', 'Innovation', 'Learning beyond textbooks through technology, creativity and critical thinking.' ),
	array( '03', 'Sports', 'Developing resilience, teamwork, fitness, leadership and fair play.' ),
	array( '04', 'Academics', 'Strengthening conceptual clarity through a focused curriculum and dedicated faculty.' ),
);

$mags_patron_page    = $mags_find_page( 'patrons-message' );
$mags_chairman_page  = $mags_find_page( 'chairmans-message' );
$mags_principal_page = $mags_find_page( 'principals-message' );
$mags_has_message    = static function ( $page ) use ( $mags_clean_text ) {
	if ( ! $page ) {
		return false;
	}

	$text = $mags_clean_text( $page->post_content );
	return count( preg_split( '/\s+/', $text ) ) > 12;
};
$mags_leaders        = array();
foreach (
	array(
		array( 'patrons-message', $mags_patron_page, $mags_patron_message_url, 'Our continuing dream is to ensure value-based, quality education for every student.' ),
		array( 'chairmans-message', $mags_chairman_page, $mags_chairman_message_url, '' ),
		array( 'principals-message', $mags_principal_page, $mags_principal_message_url, 'Discipline is the foundation, innovation is the spark, sports build the spirit, and academics form the core.' ),
	) as $mags_leader_source
) {
	list( $mags_leader_slug, $mags_leader_page, $mags_leader_url, $mags_leader_fallback ) = $mags_leader_source;
	$mags_leader_profile = mags_school_leader_profile( $mags_leader_slug, $mags_leader_page );
	$mags_leader_message = $mags_has_message( $mags_leader_page );
	$mags_leaders[]      = array_merge(
		$mags_leader_profile,
		array(
			'url'         => $mags_leader_url,
			'excerpt'     => $mags_leader_message || $mags_leader_fallback ? $mags_page_excerpt( $mags_leader_slug, $mags_leader_fallback, 27 ) : '',
			'has_message' => $mags_leader_message,
		)
	);
}

$mags_computer_lab_album = get_page_by_path( 'computer-lab', OBJECT, 'mags_gallery' );
$mags_computer_lab_url   = $mags_computer_lab_album ? get_permalink( $mags_computer_lab_album ) : $mags_gallery_url;
$mags_facilities         = array(
	array( 'Building', $mags_infrastructure_url ),
	array( 'Library', $mags_library_url ),
	array( 'Computer Laboratory', $mags_computer_lab_url ),
	array( 'Mathematics Laboratory', $mags_laboratories_url ),
	array( 'Biotechnology Laboratory', $mags_laboratories_url ),
	array( 'Chemistry Laboratory', $mags_laboratories_url ),
	array( 'Sports Facilities', $mags_sports_url ),
	array( 'Music, Dance & Cultural', $mags_activities_url ),
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
	$mags_counselling_topics = array( 'Personal', 'Educational', 'Developmental', 'Social', 'Spiritual / Cultural' );
}

$mags_notices = new WP_Query(
	array(
		'post_type'      => 'mags_notice',
		'post_status'    => 'publish',
		'posts_per_page' => 4,
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
$mags_has_notices = $mags_notices->have_posts();

$mags_updates         = mags_school_homepage_updates( 3 );
$mags_upcoming_events = mags_school_upcoming_events( 4 );

$mags_gallery_cards  = array();
$mags_gallery_albums = mags_school_homepage_albums( 3 );
$mags_gallery_chosen = $mags_gallery_albums && '1' === get_post_meta( $mags_gallery_albums[0]->ID, '_mags_gallery_homepage', true );
foreach ( $mags_gallery_albums as $mags_gallery_post ) {
	$mags_gallery_image_id = (int) get_post_thumbnail_id( $mags_gallery_post );
	if ( ! $mags_gallery_image_id ) {
		$mags_gallery_image_ids = mags_school_gallery_image_ids( $mags_gallery_post->ID );
		$mags_gallery_image_id  = $mags_gallery_image_ids ? (int) $mags_gallery_image_ids[0] : 0;
	}
	if ( $mags_gallery_image_id ) {
		$mags_gallery_cards[] = array(
			'id'    => $mags_gallery_image_id,
			'title' => get_the_title( $mags_gallery_post ),
			'url'   => get_permalink( $mags_gallery_post ),
		);
	}
}

if ( ! $mags_gallery_chosen && count( $mags_gallery_cards ) < 3 ) {
	$mags_official_gallery = mags_school_official_gallery_items();
	foreach ( array( 1, 4, 3 ) as $mags_gallery_index ) {
		if ( count( $mags_gallery_cards ) >= 3 || empty( $mags_official_gallery[ $mags_gallery_index ] ) ) {
			break;
		}
		$mags_gallery_cards[] = array(
			'id'    => 0,
			'image' => $mags_official_gallery[ $mags_gallery_index ]['image'],
			'title' => $mags_official_gallery[ $mags_gallery_index ]['title'],
			'url'   => $mags_gallery_url,
		);
	}
}

$mags_address = mags_school_option( 'address', '1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086' );
?>

<div class="mags-home">
	<section class="mags-home-hero<?php echo $mags_hero_slide_total > 1 ? ' has-multiple-slides' : ''; ?>" aria-labelledby="mags-home-title" aria-roledescription="carousel" aria-label="School highlights" data-mags-hero-carousel data-interval="5500">
		<div class="mags-home-hero__media" id="mags-home-hero-slides" aria-live="off">
			<?php foreach ( $mags_hero_slides as $mags_hero_index => $mags_hero_slide ) : ?>
				<figure class="mags-home-hero__slide<?php echo 0 === $mags_hero_index ? ' is-active' : ''; ?>" data-mags-hero-slide aria-label="Slide <?php echo esc_attr( (string) ( $mags_hero_index + 1 ) ); ?> of <?php echo esc_attr( (string) $mags_hero_slide_total ); ?>" aria-hidden="<?php echo 0 === $mags_hero_index ? 'false' : 'true'; ?>"<?php echo 0 === $mags_hero_index ? '' : ' inert'; ?>>
					<?php if ( $mags_hero_slide['id'] ) : ?>
						<?php echo wp_get_attachment_image( $mags_hero_slide['id'], 'full', false, array( 'alt' => '', 'class' => 'mags-home-hero__image', 'loading' => 0 === $mags_hero_index ? 'eager' : 'lazy', 'decoding' => 'async', 'fetchpriority' => 0 === $mags_hero_index ? 'high' : 'low', 'sizes' => '100vw' ) ); ?>
					<?php else : ?>
						<img class="mags-home-hero__image" src="<?php echo esc_url( $mags_hero_slide['image'] ); ?>" alt="" width="1800" height="1322" loading="eager" decoding="async" fetchpriority="high">
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<div class="mags-home-hero__shade" aria-hidden="true"></div>

		<div class="wrap mags-home-hero__layout">
			<div class="mags-home-hero__copy">
				<p class="mags-home-hero__eyebrow"><?php echo esc_html( $mags_hero_content['eyebrow'] ); ?></p>
				<h1 id="mags-home-title"><?php echo esc_html( $mags_hero_content['heading'] ); ?></h1>
				<p class="mags-home-hero__motto"><span lang="hi"><?php echo esc_html( $mags_hero_content['motto_hi'] ); ?></span><small><?php echo esc_html( $mags_hero_content['motto_en'] ); ?></small></p>
				<p class="mags-home-hero__intro"><?php echo esc_html( $mags_hero_content['description'] ); ?></p>
				<p class="mags-home-hero__pillars" aria-label="Our educational priorities"><span>Discipline</span><span>Innovation</span><span>Sports</span><span>Academics</span></p>
				<div class="mags-home-hero__actions">
					<a class="mags-home-hero__button mags-home-hero__button--primary" href="<?php echo esc_url( $mags_hero_content['primary_url'] ); ?>"><?php echo esc_html( $mags_hero_content['primary_label'] ); ?> <span aria-hidden="true">→</span></a>
					<a class="mags-home-hero__button mags-home-hero__button--secondary" href="<?php echo esc_url( $mags_hero_content['secondary_url'] ); ?>"><?php echo esc_html( $mags_hero_content['secondary_label'] ); ?></a>
				</div>
			</div>

			<div class="mags-home-hero__footer">
				<?php if ( $mags_hero_slide_total > 1 ) : ?>
					<div class="mags-home-hero__controls">
						<p class="mags-home-hero__counter"><span class="mags-home-hero__current">01</span><span aria-hidden="true">/</span><span><?php echo esc_html( str_pad( (string) $mags_hero_slide_total, 2, '0', STR_PAD_LEFT ) ); ?></span><span class="screen-reader-text" aria-live="polite" data-mags-hero-status>Slide 1 of <?php echo esc_html( (string) $mags_hero_slide_total ); ?></span></p>
						<div class="mags-home-hero__progress" aria-hidden="true"><span></span></div>
						<div class="mags-home-hero__control-buttons">
							<button type="button" data-mags-hero-previous aria-label="Previous photograph"><span aria-hidden="true">‹</span></button>
							<button type="button" data-mags-hero-toggle aria-label="Pause photograph rotation" aria-pressed="false"><span data-mags-hero-toggle-icon aria-hidden="true">Ⅱ</span></button>
							<button type="button" data-mags-hero-next aria-label="Next photograph"><span aria-hidden="true">›</span></button>
						</div>
					</div>
				<?php endif; ?>

				<div class="mags-home-hero__badges" aria-live="polite">
					<?php foreach ( $mags_hero_slides as $mags_hero_index => $mags_hero_slide ) : ?>
						<div class="mags-home-hero__badge<?php echo $mags_hero_slide['badge_on'] ? ' has-content' : ''; ?><?php echo 0 === $mags_hero_index ? ' is-active' : ''; ?>" data-mags-hero-badge aria-hidden="<?php echo 0 === $mags_hero_index ? 'false' : 'true'; ?>"<?php echo 0 === $mags_hero_index ? '' : ' inert'; ?>>
							<?php if ( $mags_hero_slide['badge_on'] ) : ?><small>Recognition</small><strong><?php echo esc_html( $mags_hero_slide['badge_title'] ); ?></strong><span><?php echo esc_html( $mags_hero_slide['badge_detail'] ); ?></span><?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<nav class="mags-home-shortcuts wrap" aria-label="Popular school links">
		<a href="<?php echo esc_url( $mags_admission_url ); ?>"><small>Join MAGS</small><strong>Admissions</strong><span aria-hidden="true">→</span></a>
		<a href="<?php echo esc_url( $mags_notice_url ); ?>"><small>Stay informed</small><strong>Notice Board</strong><span aria-hidden="true">→</span></a>
		<a href="<?php echo esc_url( $mags_download_url ); ?>"><small>Official documents</small><strong>Downloads</strong><span aria-hidden="true">→</span></a>
		<a href="<?php echo esc_url( $mags_contact_url ); ?>"><small>Rohini, Delhi</small><strong>Contact Us</strong><span aria-hidden="true">→</span></a>
	</nav>

	<section class="mags-home-section mags-home-purpose" aria-labelledby="mags-purpose-title">
		<div class="wrap mags-home-purpose__grid">
			<div class="mags-home-purpose__story">
				<p class="mags-home-eyebrow">Maharaja Agrasen — The legendary king</p>
				<h2 id="mags-purpose-title">A legacy of equality, enterprise and service.</h2>
				<p><?php echo esc_html( $mags_legacy_summary ); ?></p>
				<a class="mags-home-text-link" href="<?php echo esc_url( $mags_about_url ); ?>">Read about Maharaja Agrasen <span aria-hidden="true">→</span></a>
			</div>

			<article class="mags-home-mission">
				<img src="<?php echo esc_url( $mags_mission_icon ); ?>" alt="" width="512" height="512" loading="lazy" decoding="async">
				<p class="mags-home-eyebrow">School’s mission</p>
				<h3><span lang="hi">आत्म दीपो भवः</span><small>Be your own light</small></h3>
				<p><?php echo esc_html( $mags_mission_summary ); ?></p>
				<a class="mags-home-text-link" href="<?php echo esc_url( $mags_mission_url ); ?>">Explore our mission <span aria-hidden="true">→</span></a>
			</article>
		</div>
	</section>
	<div class="mags-home-passage" aria-hidden="true" data-mags-passage><span class="mags-home-passage__lamp"></span></div>

	<section class="mags-home-section mags-home-principles" aria-labelledby="mags-principles-title">
		<div class="wrap">
			<header class="mags-home-heading">
				<div><p class="mags-home-eyebrow">How students grow at MAGS</p><h2 id="mags-principles-title">Strong foundations for a changing world.</h2></div>
				<p>Learning combines character, curiosity, physical well-being and academic clarity.</p>
			</header>
			<ol class="mags-home-principles__grid">
				<?php foreach ( $mags_principles as $mags_principle_index => $mags_principle ) : ?>
					<li><span><?php echo esc_html( $mags_principle[0] ); ?></span><h3><?php echo esc_html( $mags_principle[1] ); ?></h3><p><?php echo esc_html( $mags_principle[2] ); ?></p></li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="mags-home-history" aria-labelledby="mags-history-title">
		<div class="mags-home-history__backdrop" aria-hidden="true">
			<?php foreach ( $mags_journey_images as $mags_journey_image ) : ?>
				<img class="mags-home-history__slide" src="<?php echo esc_url( $mags_journey_image['src'] ); ?>" alt="" width="<?php echo esc_attr( $mags_journey_image['width'] ); ?>" height="<?php echo esc_attr( $mags_journey_image['height'] ); ?>" loading="lazy" decoding="async" fetchpriority="low">
			<?php endforeach; ?>
		</div>
		<div class="wrap mags-home-history__grid">
			<div class="mags-home-history__intro">
				<p class="mags-home-eyebrow">Our educational journey</p>
				<h2 id="mags-history-title">Building institutions of excellence since 1998.</h2>
				<p><?php echo esc_html( $mags_history_summary ); ?></p>
				<a class="mags-home-text-link mags-home-text-link--light" href="<?php echo esc_url( $mags_history_url ); ?>">Read our complete history <span aria-hidden="true">→</span></a>
			</div>
			<?php if ( $mags_history_milestones ) : ?>
				<ul class="mags-home-history__facts">
					<?php foreach ( array_slice( $mags_history_milestones, 0, 4 ) as $mags_history_milestone ) : ?>
						<?php
						$mags_history_year  = '';
						$mags_history_label = $mags_history_milestone;
						if ( preg_match( '/\b(?:19|20)\d{2}\b/u', $mags_history_milestone, $mags_history_year_match ) ) {
							$mags_history_year  = $mags_history_year_match[0];
							$mags_history_label = trim( preg_replace( '/\s*(?:—|–|-)\s*' . preg_quote( $mags_history_year, '/' ) . '\s*$/u', '', $mags_history_milestone ) );
						}
						?>
						<li>
							<?php if ( $mags_history_year ) : ?><time datetime="<?php echo esc_attr( $mags_history_year ); ?>"><?php echo esc_html( $mags_history_year ); ?></time><?php endif; ?>
							<span><?php echo esc_html( $mags_history_label ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<section class="mags-home-section mags-home-leadership" aria-labelledby="mags-leadership-title">
		<div class="wrap">
			<header class="mags-home-heading">
				<div><p class="mags-home-eyebrow">Leadership</p><h2 id="mags-leadership-title">Messages that guide our community.</h2></div>
				<p>Meet the leaders shaping the school’s values, direction and everyday learning culture.</p>
			</header>
			<div class="mags-home-leadership__grid">
				<?php foreach ( $mags_leaders as $mags_leader ) : ?>
					<article class="mags-home-leader<?php echo $mags_leader['has_message'] ? '' : ' mags-home-leader--profile'; ?>">
						<?php if ( $mags_leader['has_message'] ) : ?><a class="mags-home-leader__photo" href="<?php echo esc_url( $mags_leader['url'] ); ?>" aria-label="Read the <?php echo esc_attr( $mags_leader['role'] ); ?>’s message"><?php else : ?><div class="mags-home-leader__photo"><?php endif; ?>
							<?php if ( $mags_leader['image_id'] ) : ?>
								<?php echo wp_get_attachment_image( $mags_leader['image_id'], 'medium', false, array( 'alt' => $mags_leader['name'] . ', ' . $mags_leader['role'], 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
							<?php else : ?>
								<img src="<?php echo esc_url( $mags_leader['image'] ); ?>" alt="<?php echo esc_attr( $mags_leader['name'] . ', ' . $mags_leader['role'] ); ?>" width="<?php echo esc_attr( $mags_leader['width'] ); ?>" height="<?php echo esc_attr( $mags_leader['height'] ); ?>" loading="lazy" decoding="async">
							<?php endif; ?>
						<?php if ( $mags_leader['has_message'] ) : ?></a><?php else : ?></div><?php endif; ?>
						<div class="mags-home-leader__copy"><p><?php echo esc_html( $mags_leader['role'] ); ?></p><h3><?php echo esc_html( $mags_leader['name'] ); ?></h3><?php if ( $mags_leader['excerpt'] ) : ?><blockquote><?php echo esc_html( $mags_leader['excerpt'] ); ?></blockquote><?php endif; ?><?php if ( $mags_leader['has_message'] ) : ?><a class="mags-home-text-link" href="<?php echo esc_url( $mags_leader['url'] ); ?>">Read message <span aria-hidden="true">→</span></a><?php endif; ?></div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<div class="mags-home-passage" aria-hidden="true" data-mags-passage><span class="mags-home-passage__lamp"></span></div>

	<section class="mags-home-section mags-home-campus" aria-labelledby="mags-campus-title">
		<div class="wrap">
			<header class="mags-home-heading">
				<div><p class="mags-home-eyebrow">Campus & infrastructure</p><h2 id="mags-campus-title">Spaces designed for focused, active learning.</h2></div>
				<a class="mags-home-text-link" href="<?php echo esc_url( $mags_infrastructure_url ); ?>">Explore infrastructure <span aria-hidden="true">→</span></a>
			</header>
			<div class="mags-home-campus__grid">
				<figure><img src="<?php echo esc_url( $mags_image_base . 'gallery-computer-lab.jpg' ); ?>" alt="Students learning with teacher guidance in the MAGS computer laboratory" width="1800" height="1350" loading="lazy" decoding="async"><figcaption>Computer Laboratory</figcaption></figure>
				<nav class="mags-home-facilities" aria-label="School facilities">
					<?php foreach ( $mags_facilities as $mags_facility_index => $mags_facility ) : ?>
						<a href="<?php echo esc_url( $mags_facility[1] ); ?>"><span><?php echo esc_html( str_pad( (string) ( $mags_facility_index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><strong><?php echo esc_html( $mags_facility[0] ); ?></strong><b aria-hidden="true">→</b></a>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>
	</section>

	<section class="mags-home-section mags-home-support" aria-labelledby="mags-support-title">
		<div class="wrap">
			<header class="mags-home-heading"><div><p class="mags-home-eyebrow">Student support</p><h2 id="mags-support-title">Support for every step of the journey.</h2></div></header>
			<div class="mags-home-support__grid">
				<article><p class="mags-home-support__number">01</p><h3>Counselling</h3><ul><?php foreach ( $mags_counselling_topics as $mags_topic ) : ?><li><?php echo esc_html( $mags_topic ); ?></li><?php endforeach; ?></ul><a class="mags-home-text-link" href="<?php echo esc_url( $mags_counselling_url ); ?>">Counselling support <span aria-hidden="true">→</span></a></article>
				<article><p class="mags-home-support__number">02</p><h3>Transport</h3><p>School transport information and related guidance for families.</p><a class="mags-home-text-link" href="<?php echo esc_url( $mags_transport_url ); ?>">Transport facilities <span aria-hidden="true">→</span></a></article>
				<article><p class="mags-home-support__number">03</p><h3>Admissions</h3><p>Find admission information, fee details and available scholarship categories.</p><div class="mags-home-support__links"><a href="<?php echo esc_url( $mags_admission_url ); ?>">Admissions</a><a href="<?php echo esc_url( $mags_fees_url ); ?>">Fees</a><a href="<?php echo esc_url( $mags_scholarships_url ); ?>">Scholarships</a></div></article>
			</div>
		</div>
	</section>
	<div class="mags-home-passage" aria-hidden="true" data-mags-passage><span class="mags-home-passage__lamp"></span></div>

	<?php if ( $mags_upcoming_events ) : ?>
		<section class="mags-home-section mags-home-events" aria-labelledby="mags-events-title">
			<div class="wrap"><header class="mags-home-heading"><div><p class="mags-home-eyebrow">Mark your calendar</p><h2 id="mags-events-title">Upcoming events.</h2></div><a class="mags-home-text-link" href="<?php echo esc_url( $mags_news_url ); ?>">View all events <span aria-hidden="true">→</span></a></header><ol class="mags-home-events__list">
			<?php foreach ( $mags_upcoming_events as $mags_event ) : $mags_event_iso = get_post_meta( $mags_event->ID, '_mags_event_date', true ); ?>
				<li><a href="<?php echo esc_url( get_permalink( $mags_event ) ); ?>"><time datetime="<?php echo esc_attr( $mags_event_iso ); ?>"><b><?php echo esc_html( mags_school_event_date( $mags_event->ID, 'j' ) ); ?></b><span><?php echo esc_html( mags_school_event_date( $mags_event->ID, 'M Y' ) ); ?></span></time><div><h3><?php echo esc_html( get_the_title( $mags_event ) ); ?></h3><p><?php echo esc_html( mags_school_event_date( $mags_event->ID, 'l, j F Y' ) ); ?></p></div><span aria-hidden="true">→</span></a></li>
			<?php endforeach; ?>
			</ol></div>
		</section>
	<?php endif; ?>

	<?php if ( $mags_updates ) : ?>
		<section class="mags-home-section mags-home-news" aria-labelledby="mags-news-title">
			<div class="wrap"><header class="mags-home-heading"><div><p class="mags-home-eyebrow">News & events</p><h2 id="mags-news-title">Latest from MAGS.</h2></div><a class="mags-home-text-link" href="<?php echo esc_url( $mags_news_url ); ?>">View all updates <span aria-hidden="true">→</span></a></header><div class="mags-home-news__grid">
			<?php foreach ( $mags_updates as $mags_update ) : $mags_update_event_iso = 'mags_event' === $mags_update->post_type ? get_post_meta( $mags_update->ID, '_mags_event_date', true ) : ''; ?>
				<article><a href="<?php echo esc_url( get_permalink( $mags_update ) ); ?>"><?php $mags_update_image = (int) get_post_thumbnail_id( $mags_update ); if ( $mags_update_image ) { echo wp_get_attachment_image( $mags_update_image, 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); } ?><div><time datetime="<?php echo esc_attr( $mags_update_event_iso ?: get_the_date( 'c', $mags_update ) ); ?>"><?php echo esc_html( mags_school_update_date( $mags_update->ID ) ); ?></time><h3><?php echo esc_html( get_the_title( $mags_update ) ); ?></h3></div></a></article>
			<?php endforeach; ?>
			</div></div>
		</section>
	<?php endif; ?>

	<div class="mags-home-section mags-home-updates">
		<div class="wrap mags-home-updates__grid<?php echo $mags_has_notices ? '' : ' mags-home-updates__grid--gallery-only'; ?>">
			<?php if ( $mags_has_notices ) : ?>
			<section class="mags-home-notices" aria-labelledby="mags-updates-title">
				<header><div><p class="mags-home-eyebrow">Stay informed</p><h2 id="mags-updates-title">Notice Board</h2></div><a href="<?php echo esc_url( $mags_notice_url ); ?>">View all <span aria-hidden="true">→</span></a></header>
				<ul>
					<?php while ( $mags_notices->have_posts() ) : $mags_notices->the_post(); ?><li><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></time><a href="<?php echo esc_url( mags_school_document_link() ); ?>" aria-label="Open notice: <?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></a></li><?php endwhile; wp_reset_postdata(); ?>
				</ul>
			</section>
			<?php endif; ?>

			<section class="mags-home-gallery" aria-labelledby="mags-gallery-title">
				<header><div><p class="mags-home-eyebrow">School life</p><h2 id="mags-gallery-title">From our galleries</h2></div><a href="<?php echo esc_url( $mags_gallery_url ); ?>">View gallery <span aria-hidden="true">→</span></a></header>
				<div class="mags-home-gallery__grid mags-home-gallery__grid--<?php echo esc_attr( (string) min( 3, count( $mags_gallery_cards ) ) ); ?>">
					<?php foreach ( $mags_gallery_cards as $mags_gallery_card ) : ?>
						<a href="<?php echo esc_url( $mags_gallery_card['url'] ); ?>">
							<?php if ( $mags_gallery_card['id'] ) : ?><?php echo wp_get_attachment_image( $mags_gallery_card['id'], 'medium_large', false, array( 'alt' => mags_school_attachment_alt( $mags_gallery_card['id'], $mags_gallery_card['title'] ), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?><?php else : ?><img src="<?php echo esc_url( $mags_gallery_card['image'] ); ?>" alt="<?php echo esc_attr( $mags_gallery_card['title'] ); ?>" loading="lazy" decoding="async"><?php endif; ?>
							<span><?php echo esc_html( $mags_gallery_card['title'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
	</div>

	<section class="mags-home-contact" aria-labelledby="mags-contact-title">
		<div class="wrap mags-home-contact__grid">
			<div><p class="mags-home-eyebrow">Visit Maharaja Agrasen Global School</p><h2 id="mags-contact-title">A welcoming learning community in Rohini, Delhi.</h2><?php if ( $mags_address ) : ?><address><?php echo esc_html( $mags_address ); ?></address><?php endif; ?></div>
			<div class="mags-home-actions"><a class="mags-home-button mags-home-button--light" href="<?php echo esc_url( $mags_contact_url ); ?>">Contact the school <span aria-hidden="true">→</span></a><a class="mags-home-button mags-home-button--outline-light" href="<?php echo esc_url( $mags_admission_url ); ?>">Admission enquiry</a></div>
		</div>
	</section>
</div>
