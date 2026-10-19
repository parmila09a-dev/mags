<?php
/** Theme setup and CMS modules for MAGS School. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mags_school_setup() {
	load_theme_textdomain( 'mags-school', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 100, 'width' => 360, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'primary' => __( 'Primary navigation', 'mags-school' ), 'footer' => __( 'Footer navigation', 'mags-school' ) ) );
}
add_action( 'after_setup_theme', 'mags_school_setup' );

function mags_school_assets() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'mags-school-style', get_stylesheet_uri(), array(), $version );
	if ( is_front_page() ) {
		wp_enqueue_style( 'mags-school-home', get_template_directory_uri() . '/assets/css/home-modern.css', array( 'mags-school-style' ), $version );
		wp_enqueue_script( 'mags-school-home', get_template_directory_uri() . '/assets/js/home-modern.js', array(), $version, true );
	}
	wp_enqueue_script( 'mags-school-main', get_template_directory_uri() . '/assets/js/main.js', array(), $version, true );
}
add_action( 'wp_enqueue_scripts', 'mags_school_assets' );

function mags_school_register_content_types() {
	if ( defined( 'MAGS_CORE_VERSION' ) ) { return; }
	$common = array( 'public' => true, 'show_in_rest' => true, 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ), 'has_archive' => true, 'menu_icon' => 'dashicons-megaphone' );
	register_post_type( 'mags_notice', array_merge( $common, array( 'labels' => array( 'name' => __( 'Notices', 'mags-school' ), 'singular_name' => __( 'Notice', 'mags-school' ), 'add_new_item' => __( 'Add Notice', 'mags-school' ), 'edit_item' => __( 'Edit Notice', 'mags-school' ) ), 'rewrite' => array( 'slug' => 'notices' ) ) ) );
	register_post_type( 'mags_download', array_merge( $common, array( 'labels' => array( 'name' => __( 'Downloads', 'mags-school' ), 'singular_name' => __( 'Download', 'mags-school' ), 'add_new_item' => __( 'Add Download', 'mags-school' ) ), 'rewrite' => array( 'slug' => 'downloads' ), 'menu_icon' => 'dashicons-media-document' ) ) );
	register_post_type( 'mags_event', array_merge( $common, array( 'labels' => array( 'name' => __( 'Events', 'mags-school' ), 'singular_name' => __( 'Event', 'mags-school' ), 'add_new_item' => __( 'Add Event', 'mags-school' ) ), 'rewrite' => array( 'slug' => 'events' ), 'menu_icon' => 'dashicons-calendar-alt' ) ) );
	register_post_type( 'mags_gallery', array_merge( $common, array( 'labels' => array( 'name' => __( 'Galleries', 'mags-school' ), 'singular_name' => __( 'Gallery Album', 'mags-school' ), 'add_new_item' => __( 'Add Gallery Album', 'mags-school' ) ), 'rewrite' => array( 'slug' => 'gallery' ), 'menu_icon' => 'dashicons-format-gallery' ) ) );
}
add_action( 'init', 'mags_school_register_content_types' );

function mags_school_add_document_box() { if ( defined( 'MAGS_CORE_VERSION' ) ) { return; } foreach ( array( 'mags_notice', 'mags_download' ) as $type ) { add_meta_box( 'mags_document', __( 'Attached PDF / document', 'mags-school' ), 'mags_school_document_box', $type, 'side' ); } }
add_action( 'add_meta_boxes', 'mags_school_add_document_box' );
function mags_school_document_box( $post ) { wp_nonce_field( 'mags_document_save', 'mags_document_nonce' ); $url = get_post_meta( $post->ID, '_mags_document_url', true ); ?><p><label for="mags_document_url"><?php esc_html_e( 'Document URL', 'mags-school' ); ?></label><input class="widefat" id="mags_document_url" name="mags_document_url" value="<?php echo esc_url( $url ); ?>" /></p><p><button type="button" class="button mags-upload-document"><?php esc_html_e( 'Choose from Media Library', 'mags-school' ); ?></button></p><?php }
function mags_school_save_document( $post_id ) { if ( defined( 'MAGS_CORE_VERSION' ) || ! isset( $_POST['mags_document_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mags_document_nonce'] ) ), 'mags_document_save' ) || defined( 'DOING_AUTOSAVE' ) || ! current_user_can( 'edit_post', $post_id ) ) { return; } if ( isset( $_POST['mags_document_url'] ) ) { update_post_meta( $post_id, '_mags_document_url', esc_url_raw( wp_unslash( $_POST['mags_document_url'] ) ) ); } }
add_action( 'save_post', 'mags_school_save_document' );
function mags_school_admin_assets( $hook ) { if ( defined( 'MAGS_CORE_VERSION' ) ) { return; } if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { wp_enqueue_media(); wp_enqueue_script( 'mags-school-admin', get_template_directory_uri() . '/assets/js/admin.js', array( 'jquery' ), '1.0.0', true ); } }
add_action( 'admin_enqueue_scripts', 'mags_school_admin_assets' );

function mags_school_customize( $wp_customize ) {
	$wp_customize->add_section( 'mags_contact', array( 'title' => __( 'School contact details', 'mags-school' ), 'priority' => 30 ) );
	$fields = array( 'phone' => array( 'Phone number', '' ), 'email' => array( 'Official email', '' ), 'address' => array( 'Address', '1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086' ), 'map_url' => array( 'Google Maps embed URL', '' ), 'facebook' => array( 'Facebook URL', '' ), 'instagram' => array( 'Instagram URL', '' ), 'youtube' => array( 'YouTube URL', '' ) );
	foreach ( $fields as $id => $field ) { $setting = 'mags_' . $id; $wp_customize->add_setting( $setting, array( 'default' => $field[1], 'sanitize_callback' => $id === 'email' ? 'sanitize_email' : 'esc_url_raw' ) ); if ( in_array( $id, array( 'phone', 'address' ), true ) ) { $wp_customize->get_setting( $setting )->sanitize_callback = 'sanitize_text_field'; } $wp_customize->add_control( $setting, array( 'label' => __( $field[0], 'mags-school' ), 'section' => 'mags_contact', 'type' => $id === 'address' ? 'textarea' : 'text' ) ); }
}
add_action( 'customize_register', 'mags_school_customize' );
function mags_school_option( $key, $default = '' ) { return function_exists( 'mags_core_option' ) ? mags_core_option( $key, $default ) : get_theme_mod( 'mags_' . $key, $default ); }

function mags_school_phone_numbers( $value = '' ) {
	$value = $value ?: mags_school_option( 'phone' );
	return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,;|]+/', (string) $value ) ) ) );
}

function mags_school_gallery_image_ids( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();
	if ( function_exists( 'mags_core_gallery_image_ids' ) ) { return mags_core_gallery_image_ids( $post_id ); }
	$ids = get_post_meta( $post_id, '_mags_gallery_image_ids', true );
	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

function mags_school_primary_image_url( $post_id = 0, $size = 'large' ) {
	$post_id = $post_id ?: get_the_ID();
	$url = get_the_post_thumbnail_url( $post_id, $size );
	if ( $url ) { return $url; }
	$ids = mags_school_gallery_image_ids( $post_id );
	return $ids ? wp_get_attachment_image_url( $ids[0], $size ) : '';
}

function mags_school_attachment_alt( $attachment_id, $fallback = '' ) {
	$alt = trim( wp_strip_all_tags( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
	if ( $alt ) {
		return $alt;
	}

	$caption = trim( wp_strip_all_tags( (string) wp_get_attachment_caption( $attachment_id ) ) );
	return $caption ?: $fallback;
}

/** Event date as readers expect it (29 June 2026) instead of the stored 2026-06-29. */
function mags_school_event_date( $post_id = 0, $format = 'j F Y' ) {
	$date = trim( (string) get_post_meta( $post_id ?: get_the_ID(), '_mags_event_date', true ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return $date; }
	return mysql2date( $format, $date . ' 00:00:00' );
}

/** Date line for a News/Event card: the event date for events, otherwise the publish date. */
function mags_school_update_date( $post_id = 0 ) {
	$post_id    = $post_id ?: get_the_ID();
	$event_date = 'mags_event' === get_post_type( $post_id ) ? mags_school_event_date( $post_id ) : '';
	return $event_date ?: get_the_date( 'j F Y', $post_id );
}

/**
 * Homepage News & Events: items ticked under “Homepage promotion” come first
 * (newest first), then the remaining places are filled with the latest items.
 */
function mags_school_homepage_updates( $limit = 3 ) {
	$base     = array( 'post_type' => array( 'post', 'mags_event' ), 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true );
	$featured = get_posts( array_merge( $base, array( 'posts_per_page' => $limit, 'meta_key' => '_mags_featured_homepage', 'meta_value' => '1' ) ) );
	if ( count( $featured ) >= $limit ) { return $featured; }

	$latest = new WP_Query( array_merge( $base, array( 'posts_per_page' => $limit, 'post__not_in' => wp_list_pluck( $featured, 'ID' ), 'ignore_sticky_posts' => false ) ) );
	return array_slice( array_merge( $featured, $latest->posts ), 0, $limit );
}

/** Events whose event date is today or later, soonest first. */
function mags_school_upcoming_events( $limit = 4 ) {
	return get_posts(
		array(
			'post_type'      => 'mags_event',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
			'meta_query'     => array( 'event_date' => array( 'key' => '_mags_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=' ) ),
			'orderby'        => array( 'event_date' => 'ASC' ),
		)
	);
}

/**
 * Albums for the homepage gallery section. Albums ticked “Show this album in
 * the homepage” are used; when none is ticked the newest albums are shown.
 */
function mags_school_homepage_albums( $limit = 3 ) {
	$base   = array( 'post_type' => 'mags_gallery', 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true );
	$chosen = get_posts( array_merge( $base, array( 'posts_per_page' => $limit, 'meta_key' => '_mags_gallery_homepage', 'meta_value' => '1' ) ) );
	if ( $chosen ) { return $chosen; }

	$albums = array();
	foreach ( get_posts( array_merge( $base, array( 'posts_per_page' => $limit + 2 ) ) ) as $album ) {
		if ( in_array( $album->post_name, array( 'new-academic-session-2026-27-vedic-havan', 'homepage-hero' ), true ) ) { continue; }
		$albums[] = $album;
	}
	return array_slice( $albums, 0, $limit );
}

/** Name, role and photograph saved on a leadership message page, with the approved defaults. */
function mags_school_leader_profile( $slug, $page = null ) {
	$image_base = trailingslashit( get_theme_file_uri( '/assets/images' ) );
	$defaults   = array(
		'patrons-message'    => array( 'name' => 'Dr. Nand Kishore Garg', 'role' => 'Patron', 'image' => $image_base . 'patron-dr-nand-kishore-garg.jpg', 'width' => 267, 'height' => 244 ),
		'chairmans-message'  => array( 'name' => 'Er. T. R. Garg', 'role' => 'Chairman', 'image' => $image_base . 'chairman-tr-garg.jpg', 'width' => 1000, 'height' => 1024 ),
		'principals-message' => array( 'name' => 'Meetu Aggarwal', 'role' => 'Principal', 'image' => $image_base . 'principal-meetu-aggarwal.png', 'width' => 195, 'height' => 258 ),
	);
	$profile    = $defaults[ $slug ] ?? array( 'name' => $page ? get_the_title( $page ) : '', 'role' => 'School Leadership', 'image' => '', 'width' => 0, 'height' => 0 );
	$profile['image_id'] = 0;

	if ( $page ) {
		$name = trim( (string) get_post_meta( $page->ID, '_mags_leader_name', true ) );
		$role = trim( (string) get_post_meta( $page->ID, '_mags_leader_role', true ) );
		if ( '' !== $name ) { $profile['name'] = $name; }
		if ( '' !== $role ) { $profile['role'] = $role; }
		$profile['image_id'] = (int) get_post_thumbnail_id( $page );
	}
	return $profile;
}

/**
 * Curated school-supplied photographs used when no CMS gallery albums exist.
 * Keep this list general: it deliberately avoids identifying individual students.
 */
function mags_school_official_gallery_items() {
	$image_base = trailingslashit( get_theme_file_uri( '/assets/images' ) );

	return array(
		array(
			'title'       => 'New Academic Session 2026–27',
			'date'        => '29 June 2026',
			'description' => 'The new academic session began with a Vedic havan at the Lakhi Ram Park campus.',
			'image'       => $image_base . 'gallery-academic-session-2026.jpg',
		),
		array(
			'title'       => 'Computer Laboratory',
			'date'        => 'Learning at MAGS',
			'description' => 'Students learn with teacher guidance in the school computer laboratory.',
			'image'       => $image_base . 'gallery-computer-lab.jpg',
		),
		array(
			'title'       => 'Teachers’ Day',
			'date'        => '5 September 2019',
			'description' => 'A school community gathering marking Teachers’ Day at Lakhi Ram Park.',
			'image'       => $image_base . 'gallery-teachers-day-2019.jpg',
		),
		array(
			'title'       => 'Culture & Creativity',
			'date'        => 'Life at MAGS',
			'description' => 'Students take part in cultural performances and creative school activities.',
			'image'       => $image_base . 'gallery-cultural-programme.jpg',
		),
		array(
			'title'       => 'School Assembly',
			'date'        => 'Life at MAGS',
			'description' => 'Students gather in the school courtyard for a morning assembly.',
			'image'       => $image_base . 'gallery-school-assembly.jpg',
		),
		array(
			'title'       => 'Maharaja Agrasen Jayanti',
			'date'        => '10 October 2018',
			'description' => 'The school community marked Maharaja Agrasen Jayanti with a traditional havan.',
			'image'       => $image_base . 'gallery-inauguration-2018.jpg',
		),
		array(
			'title'       => 'Ram Navami Havan',
			'date'        => '27 March 2026',
			'description' => 'The school community came together for a Ram Navami havan at the Lakhi Ram Park campus.',
			'image'       => $image_base . 'gallery-ram-navami-2026.jpg',
		),
		array(
			'title'       => 'Bhumi Pujan',
			'date'        => '30 May 2025',
			'description' => 'Members of the school community participated in a traditional bhumi pujan ceremony.',
			'image'       => $image_base . 'gallery-bhumi-pujan-2025.jpg',
		),
		array(
			'title'       => 'New Academic Session 2019–20',
			'date'        => '10 July 2019',
			'description' => 'The 2019–20 academic session opened with a Vedic havan programme.',
			'image'       => $image_base . 'gallery-academic-session-2019.jpg',
		),
	);
}

function mags_school_page_url( $slug, $fallback_path = '' ) {
	static $cache = array();
	if ( ! isset( $cache[ $slug ] ) ) {
		$matches = get_posts( array( 'name' => $slug, 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1, 'suppress_filters' => false ) );
		$cache[ $slug ] = $matches ? get_permalink( $matches[0] ) : home_url( $fallback_path ?: '/' . trim( $slug, '/' ) . '/' );
	}
	return $cache[ $slug ];
}

function mags_school_document_link( $post_id = 0 ) { $url = get_post_meta( $post_id ?: get_the_ID(), '_mags_document_url', true ); return $url ? $url : get_permalink( $post_id ); }
function mags_school_fallback_menu( $args = array() ) {
	$notice_url   = get_post_type_archive_link( 'mags_notice' ) ?: home_url( '/notices/' );
	$download_url = get_post_type_archive_link( 'mags_download' ) ?: home_url( '/downloads/' );
	$gallery_url  = get_post_type_archive_link( 'mags_gallery' ) ?: home_url( '/gallery/' );
	$menu_id      = ! empty( $args['menu_id'] ) ? sanitize_html_class( $args['menu_id'] ) : 'primary-menu';
	?>
	<ul id="<?php echo esc_attr( $menu_id ); ?>" class="menu">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'about-the-school' ) ); ?>">About</a><ul class="sub-menu">
			<li><a href="<?php echo esc_url( mags_school_page_url( 'history', '/about-the-school/history/' ) ); ?>">History</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'vision-and-mission', '/about-the-school/vision-and-mission/' ) ); ?>">School’s Mission</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'patrons-message', '/about-the-school/patrons-message/' ) ); ?>">Patron’s Message</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'chairmans-message', '/about-the-school/chairmans-message/' ) ); ?>">Chairman’s Message</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'principals-message', '/about-the-school/principals-message/' ) ); ?>">Principal’s Message</a></li>
		</ul></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'academics' ) ); ?>">Academics</a></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'infrastructure' ) ); ?>">Infrastructure</a><ul class="sub-menu">
			<li><a href="<?php echo esc_url( mags_school_page_url( 'library', '/infrastructure/library/' ) ); ?>">Library</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'laboratories', '/infrastructure/laboratories/' ) ); ?>">Laboratories</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'sports-facilities', '/infrastructure/sports-facilities/' ) ); ?>">Sports Facilities</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'student-activities', '/infrastructure/student-activities/' ) ); ?>">Cultural Activities</a></li>
		</ul></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'student-support' ) ); ?>">Student Support</a><ul class="sub-menu">
			<li><a href="<?php echo esc_url( mags_school_page_url( 'counselling', '/student-support/counselling/' ) ); ?>">Counselling</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'transport-facilities', '/student-support/transport-facilities/' ) ); ?>">Transport Facilities</a></li>
		</ul></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'admissions' ) ); ?>">Admissions</a><ul class="sub-menu">
			<li><a href="<?php echo esc_url( mags_school_page_url( 'fees', '/admissions/fees/' ) ); ?>">Fees</a></li>
			<li><a href="<?php echo esc_url( mags_school_page_url( 'scholarships', '/admissions/scholarships/' ) ); ?>">Scholarships</a></li>
		</ul></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'news-and-events' ) ); ?>">Updates</a><ul class="sub-menu">
			<li><a href="<?php echo esc_url( $notice_url ); ?>">Notice Board</a></li>
			<li><a href="<?php echo esc_url( $download_url ); ?>">Downloads</a></li>
			<li><a href="<?php echo esc_url( $gallery_url ); ?>">Gallery</a></li>
		</ul></li>
		<li><a href="<?php echo esc_url( mags_school_page_url( 'contact-us' ) ); ?>">Contact</a></li>
	</ul>
	<?php
}

/**
 * Keep the theme's native metadata out of the way when a dedicated SEO plugin
 * is active. This lets the school adopt one later without duplicate tags.
 */
function mags_school_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| class_exists( 'RankMath' )
		|| function_exists( 'wpseo_init' );
}

function mags_school_meta_description() {
	$default = 'Maharaja Agrasen Global School, Delhi provides value-based quality education from Pre-primary onwards.';
	$text    = '';

	if ( is_front_page() ) {
		$text = 'Maharaja Agrasen Global School in Rohini, Delhi offers value-based education from Pre-primary onwards, guided by the motto “Be your own light”.';
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$text = $post->post_excerpt ?: $post->post_content;
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$text = term_description();
	} elseif ( is_post_type_archive() ) {
		$descriptions = array(
			'mags_notice'   => 'Read the latest notices and archived updates from Maharaja Agrasen Global School, Delhi.',
			'mags_download' => 'Download official circulars, brochures, holiday lists, fee information and newsletters from Maharaja Agrasen Global School.',
			'mags_event'    => 'Explore school events, activities and photographs from Maharaja Agrasen Global School, Delhi.',
			'mags_gallery'  => 'View photo albums featuring learning, activities and school life at Maharaja Agrasen Global School, Delhi.',
		);
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$text = $descriptions[ $post_type ] ?? '';
		if ( 'mags_notice' === $post_type && isset( $_GET['notice_archive'] ) && 'archived' === sanitize_key( wp_unslash( $_GET['notice_archive'] ) ) ) {
			$text = 'Browse archived notices and previous school updates from Maharaja Agrasen Global School, Delhi.';
		}
	} elseif ( is_home() ) {
		$text = 'News, achievements and upcoming events from Maharaja Agrasen Global School, Delhi.';
	}

	$text = html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $text ), true ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	if ( ! $text ) {
		$text = $default;
	}

	if ( function_exists( 'mb_strimwidth' ) ) {
		return mb_strimwidth( $text, 0, 158, '…', get_bloginfo( 'charset' ) );
	}

	return wp_html_excerpt( $text, 155, '…' );
}

function mags_school_canonical_url() {
	if ( is_404() || is_search() ) {
		return '';
	}

	$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	if ( $paged > 1 && ! is_singular() ) {
		return get_pagenum_link( $paged );
	}

	if ( is_singular() ) {
		return wp_get_canonical_url() ?: get_permalink();
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		return $posts_page ? get_permalink( $posts_page ) : home_url( '/' );
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$url = get_term_link( get_queried_object() );
		return is_wp_error( $url ) ? '' : $url;
	}
	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$url = get_post_type_archive_link( $post_type );
		if ( 'mags_notice' === $post_type && isset( $_GET['notice_archive'] ) && 'archived' === sanitize_key( wp_unslash( $_GET['notice_archive'] ) ) ) {
			$url = add_query_arg( 'notice_archive', 'archived', $url );
		}
		return $url;
	}

	return '';
}

function mags_school_social_image_url() {
	if ( is_singular() ) {
		$image = mags_school_primary_image_url( get_queried_object_id(), 'full' );
		if ( $image ) {
			return $image;
		}
	}

	return get_theme_file_uri( '/assets/images/mags-school-building.jpg' );
}

function mags_school_output_seo_meta() {
	if ( mags_school_has_seo_plugin() || is_feed() ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = mags_school_meta_description();
	$canonical   = mags_school_canonical_url();
	$image       = mags_school_social_image_url();
	$og_type     = is_singular() && ! is_page() ? 'article' : 'website';
	?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>">
	<?php if ( $canonical ) : ?><link rel="canonical" href="<?php echo esc_url( $canonical ); ?>"><?php endif; ?>
	<meta property="og:locale" content="<?php echo esc_attr( get_locale() ); ?>">
	<meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<?php if ( $canonical ) : ?><meta property="og:url" content="<?php echo esc_url( $canonical ); ?>"><?php endif; ?>
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $image ); ?>">
	<meta property="og:image:alt" content="<?php esc_attr_e( 'Maharaja Agrasen Global School, Delhi', 'mags-school' ); ?>">
	<?php if ( 'article' === $og_type ) : ?>
	<meta property="article:published_time" content="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
	<meta property="article:modified_time" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
	<?php endif; ?>
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( $image ); ?>">
	<meta name="twitter:image:alt" content="<?php esc_attr_e( 'Maharaja Agrasen Global School, Delhi', 'mags-school' ); ?>">
	<?php
}
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'mags_school_output_seo_meta', 2 );

function mags_school_output_school_schema() {
	if ( mags_school_has_seo_plugin() || is_feed() || is_404() ) {
		return;
	}

	$school_name = mags_school_option( 'school_name', 'Maharaja Agrasen Global School' );
	$address     = mags_school_option( 'address', '1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086' );
	$email       = mags_school_option( 'email' );
	$phones      = mags_school_phone_numbers();
	$same_as     = array_values( array_filter( array_map( 'esc_url_raw', array( mags_school_option( 'facebook' ), mags_school_option( 'instagram' ), mags_school_option( 'youtube' ) ) ) ) );
	$schema      = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'School',
		'@id'         => home_url( '/#school' ),
		'name'        => $school_name,
		'url'         => home_url( '/' ),
		'logo'        => get_theme_file_uri( '/assets/images/mags-logo.png' ),
		'image'       => get_theme_file_uri( '/assets/images/mags-school-building.jpg' ),
		'description' => 'A value-based school in Delhi providing education from Pre-primary onwards.',
		'slogan'      => 'Be your own light',
	);

	if ( $address ) {
		$schema['address'] = array( '@type' => 'PostalAddress', 'streetAddress' => $address );
	}
	if ( $phones ) {
		$schema['telephone'] = count( $phones ) === 1 ? $phones[0] : $phones;
	}
	if ( $email ) {
		$schema['email'] = $email;
	}
	if ( $same_as ) {
		$schema['sameAs'] = $same_as;
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
	<?php
}
add_action( 'wp_head', 'mags_school_output_school_schema', 3 );

function mags_school_output_icon_fallback() {
	if ( has_site_icon() ) {
		return;
	}
	$icon = get_theme_file_uri( '/assets/images/mags-site-icon.png' );
	?>
	<link rel="icon" href="<?php echo esc_url( $icon ); ?>" type="image/png">
	<link rel="apple-touch-icon" href="<?php echo esc_url( $icon ); ?>">
	<meta name="theme-color" content="#25255c">
	<?php
}
add_action( 'wp_head', 'mags_school_output_icon_fallback', 4 );

function mags_school_contact_form() {
	if ( ! empty( $_POST['mags_contact_submit'] ) && isset( $_POST['mags_contact_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mags_contact_nonce'] ) ), 'mags_contact' ) ) { $name = sanitize_text_field( wp_unslash( $_POST['mags_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['mags_email'] ?? '' ) ); $message = sanitize_textarea_field( wp_unslash( $_POST['mags_message'] ?? '' ) ); if ( $name && is_email( $email ) && $message ) { wp_mail( mags_school_option( 'email', get_option( 'admin_email' ) ), 'Website enquiry from ' . $name, $message, array( 'Reply-To: ' . $email ) ); return '<p class="mags-message mags-message--success">Thank you. Your enquiry has been sent.</p>'; } return '<p class="mags-message">Please complete all fields with a valid email address.</p>'; }
	ob_start(); ?><form method="post" class="contact-form"><div class="form-row"><div class="form-field"><label for="mags_name">Name *</label><input id="mags_name" name="mags_name" required></div><div class="form-field"><label for="mags_email">Email *</label><input id="mags_email" type="email" name="mags_email" required></div></div><div class="form-field"><label for="mags_phone">Phone</label><input id="mags_phone" name="mags_phone"></div><div class="form-field"><label for="mags_message">How can we help? *</label><textarea id="mags_message" name="mags_message" rows="6" required></textarea></div><?php wp_nonce_field( 'mags_contact', 'mags_contact_nonce' ); ?><button name="mags_contact_submit" value="1" class="button" type="submit">Send enquiry</button></form><?php return ob_get_clean();
}
add_shortcode( 'mags_contact_form', 'mags_school_contact_form' );
