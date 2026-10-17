<?php
/**
 * Plugin Name: MAGS Core
 * Description: CMS content modules, school settings, enquiry handling and initial setup for Maharaja Agrasen Global School.
 * Version: 1.8.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: MAGS Website Competition Team
 * License: GPL-2.0-or-later
 * Text Domain: mags-core
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'MAGS_CORE_VERSION', '1.8.0' );
define( 'MAGS_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAGS_CORE_URL', plugin_dir_url( __FILE__ ) );

function mags_core_labels( $plural, $singular ) {
	return array( 'name' => $plural, 'singular_name' => $singular, 'add_new_item' => "Add {$singular}", 'edit_item' => "Edit {$singular}", 'all_items' => "All {$plural}", 'menu_name' => $plural );
}

function mags_core_register_content() {
	$public_supports = array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' );
	register_post_type( 'mags_notice', array( 'labels' => mags_core_labels( 'Notices', 'Notice' ), 'public' => true, 'show_in_rest' => true, 'supports' => $public_supports, 'has_archive' => true, 'rewrite' => array( 'slug' => 'notices' ), 'menu_icon' => 'dashicons-megaphone', 'menu_position' => 20 ) );
	register_post_type( 'mags_download', array( 'labels' => mags_core_labels( 'Downloads', 'Download' ), 'public' => true, 'show_in_rest' => true, 'supports' => $public_supports, 'has_archive' => true, 'rewrite' => array( 'slug' => 'downloads' ), 'menu_icon' => 'dashicons-media-document', 'menu_position' => 21 ) );
	register_taxonomy( 'mags_download_type', 'mags_download', array( 'labels' => array( 'name' => 'Download Types', 'singular_name' => 'Download Type' ), 'public' => true, 'show_in_rest' => true, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'download-type' ) ) );
	register_post_type( 'mags_event', array( 'labels' => mags_core_labels( 'Events', 'Event' ), 'public' => true, 'show_in_rest' => true, 'supports' => $public_supports, 'has_archive' => true, 'rewrite' => array( 'slug' => 'events' ), 'menu_icon' => 'dashicons-calendar-alt', 'menu_position' => 22 ) );
	register_post_type( 'mags_gallery', array( 'labels' => mags_core_labels( 'Galleries', 'Gallery Album' ), 'public' => true, 'show_in_rest' => true, 'supports' => $public_supports, 'has_archive' => true, 'rewrite' => array( 'slug' => 'gallery' ), 'menu_icon' => 'dashicons-format-gallery', 'menu_position' => 23 ) );
	register_post_type( 'mags_enquiry', array( 'labels' => mags_core_labels( 'Enquiries', 'Enquiry' ), 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'supports' => array( 'title', 'editor' ), 'capability_type' => 'post', 'map_meta_cap' => true, 'menu_icon' => 'dashicons-email-alt', 'menu_position' => 24 ) );
}
add_action( 'init', 'mags_core_register_content', 0 );

function mags_core_label_news_posts() {
	$post_type = get_post_type_object( 'post' );
	if ( ! $post_type ) { return; }
	$post_type->label                  = 'News';
	$post_type->labels->name          = 'News';
	$post_type->labels->menu_name     = 'News';
	$post_type->labels->singular_name = 'News Article';
	$post_type->labels->add_new_item  = 'Add News Article';
	$post_type->labels->edit_item     = 'Edit News Article';
	$post_type->labels->all_items     = 'All News';
}
add_action( 'init', 'mags_core_label_news_posts', 1 );

/** Create a private CMS album used only by the homepage hero slideshow. */
function mags_core_ensure_homepage_hero_album() {
	if ( (int) get_option( 'mags_core_system_albums_version', 0 ) >= 1 ) { return; }

	$album = get_page_by_path( 'homepage-hero', OBJECT, 'mags_gallery' );
	if ( ! $album ) {
		$album_id = wp_insert_post(
			array(
				'post_type'    => 'mags_gallery',
				'post_status'  => 'private',
				'post_title'   => 'Homepage Hero Photos',
				'post_name'    => 'homepage-hero',
				'post_content' => 'Use the Event / album photographs panel to select and arrange the photographs shown in the homepage hero. Landscape photographs are recommended.',
			),
			true
		);

		if ( is_wp_error( $album_id ) ) { return; }
	}

	update_option( 'mags_core_system_albums_version', 1 );
}
add_action( 'init', 'mags_core_ensure_homepage_hero_album', 22 );

function mags_core_ensure_download_types() {
	foreach ( array( 'Circulars', 'Admission Brochure', 'Holiday Lists', 'Fee Structure', 'Newsletters', 'Other Documents' ) as $term ) {
		if ( ! term_exists( $term, 'mags_download_type' ) ) { wp_insert_term( $term, 'mags_download_type' ); }
	}
}
function mags_core_activate() { mags_core_register_content(); mags_core_ensure_download_types(); add_option( 'mags_core_needs_setup', 1 ); flush_rewrite_rules(); }
function mags_core_deactivate() { flush_rewrite_rules(); }
register_activation_hook( __FILE__, 'mags_core_activate' );
register_deactivation_hook( __FILE__, 'mags_core_deactivate' );

function mags_core_option( $key, $default = '' ) { $options = (array) get_option( 'mags_core_settings', array() ); return isset( $options[ $key ] ) && $options[ $key ] !== '' ? $options[ $key ] : $default; }
function mags_core_setting_fields() { return array( 'school_name' => array( 'School name', 'text' ), 'phone' => array( 'Telephone numbers', 'textarea' ), 'email' => array( 'Official email / enquiry recipient', 'email' ), 'address' => array( 'Postal address', 'textarea' ), 'map_url' => array( 'Google Maps embed URL', 'url' ), 'facebook' => array( 'Facebook URL', 'url' ), 'instagram' => array( 'Instagram URL', 'url' ), 'youtube' => array( 'YouTube URL', 'url' ) ); }

/** Accept either an embed URL or pasted iframe markup and retain only its src URL. */
function mags_core_sanitize_map_url( $value ) {
	$value = trim( html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( $value === '' ) { return ''; }

	if ( preg_match( '/<iframe\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/is', $value, $matches ) ) {
		$value = $matches[2];
	} elseif ( preg_match( '/https?:\/\/[^\s"\'<>]+/i', $value, $matches ) ) {
		$value = $matches[0];
	}

	// Repair values where iframe attributes were URL-encoded onto the end of src.
	$parts = preg_split( '/(?:\s|%20)+(?:width|height|style|allowfullscreen|loading|referrerpolicy|title)(?:\s*=\s*|%3D)/i', $value, 2 );
	$value = $parts ? $parts[0] : $value;
	$value = preg_replace( '/(?:%22|%27|["\'])+$/i', '', $value );
	return esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) );
}

function mags_core_sanitize_settings( $input ) {
	$out = array();
	foreach ( mags_core_setting_fields() as $key => $field ) {
		$value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
		if ( $key === 'map_url' ) { $out[ $key ] = mags_core_sanitize_map_url( $value ); }
		elseif ( $field[1] === 'email' ) { $out[ $key ] = sanitize_email( $value ); }
		elseif ( $field[1] === 'url' ) { $out[ $key ] = esc_url_raw( $value, array( 'http', 'https' ) ); }
		elseif ( $field[1] === 'textarea' ) { $out[ $key ] = sanitize_textarea_field( $value ); }
		else { $out[ $key ] = sanitize_text_field( $value ); }
	}
	return $out;
}

/** One-time repair for an already saved iframe/attribute-contaminated map value. */
function mags_core_upgrade_map_setting() {
	if ( (int) get_option( 'mags_core_settings_version', 0 ) >= 1 ) { return; }
	$options = (array) get_option( 'mags_core_settings', array() );
	if ( ! empty( $options['map_url'] ) ) {
		$clean = mags_core_sanitize_map_url( $options['map_url'] );
		if ( $clean !== '' && $clean !== $options['map_url'] ) {
			$options['map_url'] = $clean;
			update_option( 'mags_core_settings', $options );
		}
	}
	update_option( 'mags_core_settings_version', 1 );
}
add_action( 'init', 'mags_core_upgrade_map_setting', 21 );

function mags_core_settings_init() { register_setting( 'mags_core_settings_group', 'mags_core_settings', array( 'sanitize_callback' => 'mags_core_sanitize_settings' ) ); add_settings_section( 'mags_core_contact', 'Official school information', '__return_false', 'mags-core-settings' ); foreach ( mags_core_setting_fields() as $key => $field ) { add_settings_field( 'mags_core_' . $key, $field[0], 'mags_core_settings_field', 'mags-core-settings', 'mags_core_contact', array( 'key' => $key, 'type' => $field[1] ) ); } }
add_action( 'admin_init', 'mags_core_settings_init' );
function mags_core_settings_field( $args ) { $key = $args['key']; $type = $args['type']; $value = mags_core_option( $key ); $name = 'mags_core_settings[' . $key . ']'; if ( $type === 'textarea' ) { echo '<textarea class="large-text" rows="3" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>'; } else { echo '<input class="regular-text" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; } if ( $key === 'phone' ) { echo '<p class="description">Enter one telephone number per line.</p>'; } if ( $key === 'map_url' ) { echo '<p class="description">In Google Maps, choose Share → Embed a map and paste only the iframe src URL.</p>'; } }
function mags_core_add_admin_pages() { add_options_page( 'MAGS Settings', 'MAGS Settings', 'manage_options', 'mags-core-settings', 'mags_core_settings_page' ); add_management_page( 'MAGS Site Setup', 'MAGS Site Setup', 'manage_options', 'mags-core-setup', 'mags_core_setup_page' ); }
add_action( 'admin_menu', 'mags_core_add_admin_pages' );
function mags_core_settings_page() { ?><div class="wrap"><h1>MAGS Settings</h1><form action="options.php" method="post"><?php settings_fields( 'mags_core_settings_group' ); do_settings_sections( 'mags-core-settings' ); submit_button( 'Save school information' ); ?></form></div><?php }

function mags_core_document_box() {
	foreach ( array( 'mags_notice', 'mags_download' ) as $type ) { add_meta_box( 'mags_core_document', 'Attached PDF / document', 'mags_core_document_box_html', $type, 'side' ); }
	add_meta_box( 'mags_core_notice_archive', 'Notice archive', 'mags_core_notice_archive_html', 'mags_notice', 'side' );
	add_meta_box( 'mags_core_event_date', 'Event date', 'mags_core_event_date_html', 'mags_event', 'side' );
	foreach ( array( 'post', 'mags_event' ) as $type ) { add_meta_box( 'mags_core_featured_home', 'Homepage promotion', 'mags_core_featured_home_html', $type, 'side' ); }
	foreach ( array( 'mags_event', 'mags_gallery' ) as $type ) { add_meta_box( 'mags_core_photos', 'Event / album photographs', 'mags_core_photos_html', $type, 'normal', 'high' ); }
	add_meta_box( 'mags_core_enquiry_details', 'Contact details', 'mags_core_enquiry_details_html', 'mags_enquiry', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'mags_core_document_box' );
function mags_core_homepage_hero_box( $post_type, $post ) {
	if ( 'mags_gallery' !== $post_type || ! $post instanceof WP_Post || 'homepage-hero' !== $post->post_name ) { return; }
	add_meta_box( 'mags_core_hero_slides', 'Homepage hero slide content', 'mags_core_hero_slides_html', 'mags_gallery', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'mags_core_homepage_hero_box', 20, 2 );
function mags_core_leadership_profile_box( $post ) {
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_name, array( 'patrons-message', 'chairmans-message', 'principals-message' ), true ) ) { return; }
	add_meta_box( 'mags_core_leadership_profile', 'Leadership profile', 'mags_core_leadership_profile_html', 'page', 'side', 'high' );
}
add_action( 'add_meta_boxes_page', 'mags_core_leadership_profile_box' );
function mags_core_leadership_profile_html( $post ) {
	wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' );
	$defaults = array(
		'patrons-message'    => array( 'Dr. Nand Kishore Garg', 'Patron' ),
		'chairmans-message'  => array( 'Er. T. R. Garg', 'Chairman' ),
		'principals-message' => array( 'Meetu Aggarwal', 'Principal' ),
	);
	$profile = $defaults[ $post->post_name ];
	$name    = get_post_meta( $post->ID, '_mags_leader_name', true ) ?: $profile[0];
	$role    = get_post_meta( $post->ID, '_mags_leader_role', true ) ?: $profile[1];
	?><p><label for="mags_leader_name"><strong>Leader name</strong></label><input class="widefat" id="mags_leader_name" name="mags_leader_name" value="<?php echo esc_attr( $name ); ?>"></p><p><label for="mags_leader_role"><strong>Role</strong></label><input class="widefat" id="mags_leader_role" name="mags_leader_role" value="<?php echo esc_attr( $role ); ?>"></p><p class="description">The homepage leadership card uses these details, this page’s Featured Image, excerpt and message.</p><?php
}
function mags_core_document_box_html( $post ) { wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' ); $url = get_post_meta( $post->ID, '_mags_document_url', true ); ?><p><label class="screen-reader-text" for="mags_document_url">Document URL</label><input class="widefat" id="mags_document_url" name="mags_document_url" type="url" value="<?php echo esc_url( $url ); ?>" placeholder="https://..."></p><p><button type="button" class="button mags-upload-document">Choose from Media Library</button></p><p class="description">Upload a tagged, accessible PDF whenever possible.</p><?php }
function mags_core_notice_archive_html( $post ) { wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' ); ?><p><label><input name="mags_notice_archived" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_mags_notice_archived', true ), '1' ); ?>> Move this notice to the public archive</label></p><p class="description">Archived notices remain available under “Archived notices” but are removed from the current Notice Board.</p><?php }
function mags_core_event_date_html( $post ) { wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' ); ?><p><label for="mags_event_date">Event date</label><input class="widefat" id="mags_event_date" name="mags_event_date" type="date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_mags_event_date', true ) ); ?>"></p><?php }
function mags_core_featured_home_html( $post ) { wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' ); ?><p><label><input name="mags_featured_homepage" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_mags_featured_homepage', true ), '1' ); ?>> Feature this item in the homepage News &amp; Events section</label></p><?php }
function mags_core_gallery_image_ids( $post_id ) {
	$ids = get_post_meta( $post_id, '_mags_gallery_image_ids', true );
	if ( is_string( $ids ) ) { $ids = explode( ',', $ids ); }
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	return array_values( array_filter( $ids, 'wp_attachment_is_image' ) );
}
function mags_core_photos_html( $post ) {
	wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' );
	$is_homepage_hero = 'mags_gallery' === $post->post_type && 'homepage-hero' === $post->post_name;
	$ids              = mags_core_gallery_image_ids( $post->ID );
	if ( $is_homepage_hero ) { $ids = array_slice( $ids, 0, 5 ); }
	?>
	<input type="hidden" id="mags_gallery_image_ids" name="mags_gallery_image_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<div id="mags-gallery-preview" class="mags-gallery-preview">
		<?php foreach ( $ids as $image_id ) : ?><span data-id="<?php echo esc_attr( $image_id ); ?>"><?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?><span class="mags-photo-actions"><button type="button" class="mags-move-photo mags-move-photo-earlier" aria-label="Move photograph earlier">←</button><button type="button" class="mags-move-photo mags-move-photo-later" aria-label="Move photograph later">→</button><button type="button" class="mags-remove-photo" aria-label="Remove photograph">×</button></span></span><?php endforeach; ?>
	</div>
	<p><button type="button" class="button button-primary mags-upload-photos">Add photographs</button> <button type="button" class="button mags-clear-photos">Clear album</button></p>
	<p class="description"><?php echo esc_html( $is_homepage_hero ? 'Add up to 5 photographs. Drag thumbnails or use the arrow buttons to arrange them; this numbered order is the homepage playback order.' : 'Add photographs together or one at a time; new selections are appended without replacing saved photographs. Drag thumbnails or use the arrow buttons to arrange them. The numbered thumbnail order is the website playback order.' ); ?></p>
	<?php
}
function mags_core_hero_slide_content( $post_id ) {
	$stored = get_post_meta( $post_id, '_mags_hero_slide_content', true );
	return is_array( $stored ) ? $stored : array();
}
function mags_core_hero_fixed_content( $post_id ) {
	$stored = get_post_meta( $post_id, '_mags_hero_fixed_content', true );
	return is_array( $stored ) ? $stored : array();
}
function mags_core_hero_field_limit( $key ) {
	$limits = array(
		'eyebrow'         => 80,
		'heading'         => 100,
		'motto_hi'        => 80,
		'motto_en'        => 80,
		'description'     => 320,
		'primary_label'   => 40,
		'primary_url'     => 2048,
		'secondary_label' => 40,
		'secondary_url'   => 2048,
		'badge_title'     => 80,
		'badge_detail'    => 100,
	);
	return $limits[ $key ] ?? 200;
}
function mags_core_hero_limit_value( $value, $key ) {
	$limit = mags_core_hero_field_limit( $key );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $limit ) : substr( $value, 0, $limit );
}
function mags_core_hero_slide_input( $image_id, $key, $label, $value, $type = 'text', $class = '' ) {
	$field_id = 'mags_hero_' . $key . '_' . $image_id;
	$name     = 'mags_hero_slides[' . $image_id . '][' . $key . ']';
	$limit    = mags_core_hero_field_limit( $key );
	?>
	<p class="mags-hero-field <?php echo esc_attr( $class ); ?>">
		<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
		<?php if ( 'textarea' === $type ) : ?>
			<textarea class="widefat" rows="3" maxlength="<?php echo esc_attr( (string) $limit ); ?>" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input class="widefat" type="<?php echo esc_attr( $type ); ?>" maxlength="<?php echo esc_attr( (string) $limit ); ?>" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<?php endif; ?>
	</p>
	<?php
}
function mags_core_hero_fixed_input( $key, $label, $value, $type = 'text', $class = '' ) {
	$field_id = 'mags_hero_fixed_' . $key;
	$name     = 'mags_hero_fixed[' . $key . ']';
	$limit    = mags_core_hero_field_limit( $key );
	?>
	<p class="mags-hero-field <?php echo esc_attr( $class ); ?>">
		<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
		<?php if ( 'textarea' === $type ) : ?>
			<textarea class="widefat" rows="3" maxlength="<?php echo esc_attr( (string) $limit ); ?>" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input class="widefat" type="<?php echo esc_attr( $type ); ?>" maxlength="<?php echo esc_attr( (string) $limit ); ?>" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<?php endif; ?>
	</p>
	<?php
}
function mags_core_hero_slide_row_html( $image_id, $index, $slide ) {
	$image_id     = absint( $image_id );
	$slide        = is_array( $slide ) ? $slide : array();
	$badge_exists = array_key_exists( 'badge_enabled', $slide );
	$badge_on     = $badge_exists ? '1' === (string) $slide['badge_enabled'] : 0 === (int) $index;
	$badge_name   = 'mags_hero_slides[' . $image_id . '][badge_enabled]';
	?>
	<section class="mags-hero-slide-fields" data-hero-slide-id="<?php echo esc_attr( $image_id ); ?>">
		<header class="mags-hero-slide-fields__header">
			<?php echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'alt' => '' ) ); ?>
			<div><strong class="mags-hero-slide-number">Slide <?php echo esc_html( (string) ( $index + 1 ) ); ?></strong><span><?php echo esc_html( get_the_title( $image_id ) ?: 'Selected photograph' ); ?></span></div>
		</header>
		<div class="mags-hero-slide-fields__grid">
			<fieldset class="mags-hero-badge-fields mags-hero-field--wide">
				<legend><strong>Contextual achievement badge</strong></legend>
				<input type="hidden" name="<?php echo esc_attr( $badge_name ); ?>" value="0">
				<label class="mags-hero-badge-toggle"><input type="checkbox" name="<?php echo esc_attr( $badge_name ); ?>" value="1" <?php checked( $badge_on ); ?>> Show badge on this slide</label>
				<div class="mags-hero-badge-fields__grid">
					<?php
					mags_core_hero_slide_input( $image_id, 'badge_title', 'Badge title', $slide['badge_title'] ?? '' );
					mags_core_hero_slide_input( $image_id, 'badge_detail', 'Badge detail', $slide['badge_detail'] ?? '' );
					?>
				</div>
			</fieldset>
		</div>
	</section>
	<?php
}
function mags_core_hero_slides_html( $post ) {
	wp_nonce_field( 'mags_core_meta', 'mags_core_meta_nonce' );
	$ids       = array_slice( mags_core_gallery_image_ids( $post->ID ), 0, 5 );
	$content   = mags_core_hero_slide_content( $post->ID );
	$legacy    = $ids && isset( $content[ $ids[0] ] ) && is_array( $content[ $ids[0] ] ) ? $content[ $ids[0] ] : array();
	$fixed     = array_merge( $legacy, mags_core_hero_fixed_content( $post->ID ) );
	$defaults  = array(
		'eyebrow'         => 'Delhi · Established 2010',
		'heading'         => mags_core_option( 'school_name', 'Maharaja Agrasen Global School' ),
		'motto_hi'        => 'आत्म दीपो भवः',
		'motto_en'        => 'Be your own light',
		'description'     => 'Where discipline shapes character, innovation drives learning, sports build spirit, and academics form the core.',
		'primary_label'   => 'Explore MAGS',
		'primary_url'     => home_url( '/about-the-school/' ),
		'secondary_label' => 'Admissions',
		'secondary_url'   => home_url( '/admissions/' ),
	);
	foreach ( $defaults as $key => $value ) {
		if ( ! isset( $fixed[ $key ] ) || '' === trim( (string) $fixed[ $key ] ) ) { $fixed[ $key ] = $value; }
	}
	?>
	<section class="mags-hero-fixed-fields">
		<h3>Fixed hero content</h3>
		<p class="description">This institutional message stays in place while the background photographs change.</p>
		<div class="mags-hero-slide-fields__grid">
			<?php
			mags_core_hero_fixed_input( 'eyebrow', 'Eyebrow', $fixed['eyebrow'] );
			mags_core_hero_fixed_input( 'heading', 'Heading', $fixed['heading'], 'text', 'mags-hero-field--wide' );
			mags_core_hero_fixed_input( 'motto_hi', 'Hindi motto', $fixed['motto_hi'] );
			mags_core_hero_fixed_input( 'motto_en', 'English motto', $fixed['motto_en'] );
			mags_core_hero_fixed_input( 'description', 'Introduction', $fixed['description'], 'textarea', 'mags-hero-field--wide' );
			mags_core_hero_fixed_input( 'primary_label', 'Primary button label', $fixed['primary_label'] );
			mags_core_hero_fixed_input( 'primary_url', 'Primary button URL', $fixed['primary_url'], 'url' );
			mags_core_hero_fixed_input( 'secondary_label', 'Secondary button label', $fixed['secondary_label'] );
			mags_core_hero_fixed_input( 'secondary_url', 'Secondary button URL', $fixed['secondary_url'], 'url' );
			?>
		</div>
	</section>
	<hr>
	<p class="description">Use 3–4 carefully selected photographs where possible (maximum 5). Their numbered order above is the playback order. Each photograph can show its own compact achievement badge.</p>
	<p class="mags-hero-slide-status" aria-live="polite"><strong><span class="mags-hero-slide-count"><?php echo esc_html( (string) count( $ids ) ); ?></span> of 5 slides</strong></p>
	<div id="mags-hero-slide-editor" class="mags-hero-slide-editor">
		<?php foreach ( $ids as $index => $image_id ) { mags_core_hero_slide_row_html( $image_id, $index, $content[ $image_id ] ?? array() ); } ?>
		<p class="mags-hero-slide-empty" <?php echo $ids ? 'hidden' : ''; ?>>Add photographs above to create editable hero slides.</p>
	</div>
	<?php
}
function mags_core_enquiry_details_html( $post ) {
	$name  = get_post_meta( $post->ID, '_mags_enquiry_name', true );
	$email = get_post_meta( $post->ID, '_mags_enquiry_email', true );
	$phone = get_post_meta( $post->ID, '_mags_enquiry_phone', true );
	echo '<p><strong>Name</strong><br>' . esc_html( $name ) . '</p><p><strong>Email</strong><br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
	if ( $phone ) { echo '<p><strong>Phone</strong><br>' . esc_html( $phone ) . '</p>'; }
}
function mags_core_save_meta( $post_id ) {
	$nonce = isset( $_POST['mags_core_meta_nonce'] ) && is_scalar( $_POST['mags_core_meta_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['mags_core_meta_nonce'] ) ) : '';
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! $nonce || ! wp_verify_nonce( $nonce, 'mags_core_meta' ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$post_type = get_post_type( $post_id );
	if ( $post_type === 'page' && isset( $_POST['mags_leader_name'], $_POST['mags_leader_role'] ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['mags_leader_name'] ) );
		$role = sanitize_text_field( wp_unslash( $_POST['mags_leader_role'] ) );
		if ( $name ) { update_post_meta( $post_id, '_mags_leader_name', $name ); } else { delete_post_meta( $post_id, '_mags_leader_name' ); }
		if ( $role ) { update_post_meta( $post_id, '_mags_leader_role', $role ); } else { delete_post_meta( $post_id, '_mags_leader_role' ); }
	}
	if ( isset( $_POST['mags_document_url'] ) ) { update_post_meta( $post_id, '_mags_document_url', esc_url_raw( wp_unslash( $_POST['mags_document_url'] ) ) ); }
	if ( isset( $_POST['mags_event_date'] ) ) { $date = sanitize_text_field( wp_unslash( $_POST['mags_event_date'] ) ); if ( $date === '' || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { update_post_meta( $post_id, '_mags_event_date', $date ); } }
	if ( $post_type === 'mags_notice' ) { if ( ! empty( $_POST['mags_notice_archived'] ) ) { update_post_meta( $post_id, '_mags_notice_archived', '1' ); } else { delete_post_meta( $post_id, '_mags_notice_archived' ); } }
	if ( in_array( $post_type, array( 'post', 'mags_event' ), true ) ) { if ( ! empty( $_POST['mags_featured_homepage'] ) ) { update_post_meta( $post_id, '_mags_featured_homepage', '1' ); } else { delete_post_meta( $post_id, '_mags_featured_homepage' ); } }
	if ( in_array( $post_type, array( 'mags_event', 'mags_gallery' ), true ) && isset( $_POST['mags_gallery_image_ids'] ) && is_scalar( $_POST['mags_gallery_image_ids'] ) ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( (string) $_POST['mags_gallery_image_ids'] ) ) ) ) ) ) );
		$ids = array_values( array_filter( $ids, 'wp_attachment_is_image' ) );
		if ( 'mags_gallery' === $post_type && 'homepage-hero' === get_post_field( 'post_name', $post_id ) ) { $ids = array_slice( $ids, 0, 5 ); }
		if ( $ids ) {
			update_post_meta( $post_id, '_mags_gallery_image_ids', $ids );
			if ( 'homepage-hero' === get_post_field( 'post_name', $post_id ) || ! has_post_thumbnail( $post_id ) ) { set_post_thumbnail( $post_id, $ids[0] ); }
		} else {
			delete_post_meta( $post_id, '_mags_gallery_image_ids' );
			if ( 'homepage-hero' === get_post_field( 'post_name', $post_id ) ) { delete_post_thumbnail( $post_id ); }
		}
	}
	if ( 'mags_gallery' === $post_type && 'homepage-hero' === get_post_field( 'post_name', $post_id ) && isset( $_POST['mags_gallery_image_ids'] ) && is_scalar( $_POST['mags_gallery_image_ids'] ) ) {
		$hero_ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( (string) $_POST['mags_gallery_image_ids'] ) ) ) ) ) ) );
		$hero_ids = array_slice( array_values( array_filter( $hero_ids, 'wp_attachment_is_image' ) ), 0, 5 );
		$raw      = isset( $_POST['mags_hero_slides'] ) && is_array( $_POST['mags_hero_slides'] ) ? wp_unslash( $_POST['mags_hero_slides'] ) : array();
		$existing = mags_core_hero_slide_content( $post_id );
		$clean    = array();

		foreach ( $hero_ids as $image_id ) {
			$slide = isset( $raw[ $image_id ] ) && is_array( $raw[ $image_id ] ) ? $raw[ $image_id ] : array();
			$row   = isset( $existing[ $image_id ] ) && is_array( $existing[ $image_id ] ) ? $existing[ $image_id ] : array();
			$row['badge_enabled'] = ! empty( $slide['badge_enabled'] ) ? '1' : '0';

			foreach ( array( 'badge_title', 'badge_detail' ) as $field ) {
				$raw_value = isset( $slide[ $field ] ) && is_scalar( $slide[ $field ] ) ? (string) $slide[ $field ] : '';
				$value     = mags_core_hero_limit_value( sanitize_text_field( $raw_value ), $field );
				if ( '' !== $value ) { $row[ $field ] = $value; } else { unset( $row[ $field ] ); }
			}

			$clean[ $image_id ] = $row;
		}

		if ( $clean ) { update_post_meta( $post_id, '_mags_hero_slide_content', $clean ); } else { delete_post_meta( $post_id, '_mags_hero_slide_content' ); }

		if ( isset( $_POST['mags_hero_fixed'] ) && is_array( $_POST['mags_hero_fixed'] ) ) {
			$fixed_raw   = wp_unslash( $_POST['mags_hero_fixed'] );
			$fixed_clean = array();

			foreach ( array( 'eyebrow', 'heading', 'motto_hi', 'motto_en', 'primary_label', 'secondary_label' ) as $field ) {
				$raw_value = isset( $fixed_raw[ $field ] ) && is_scalar( $fixed_raw[ $field ] ) ? (string) $fixed_raw[ $field ] : '';
				$value     = mags_core_hero_limit_value( sanitize_text_field( $raw_value ), $field );
				if ( '' !== $value ) { $fixed_clean[ $field ] = $value; }
			}

			$description_value = isset( $fixed_raw['description'] ) && is_scalar( $fixed_raw['description'] ) ? (string) $fixed_raw['description'] : '';
			$description       = mags_core_hero_limit_value( sanitize_textarea_field( $description_value ), 'description' );
			if ( '' !== $description ) { $fixed_clean['description'] = $description; }

			foreach ( array( 'primary_url', 'secondary_url' ) as $field ) {
				$raw_value = isset( $fixed_raw[ $field ] ) && is_scalar( $fixed_raw[ $field ] ) ? (string) $fixed_raw[ $field ] : '';
				$value     = mags_core_hero_limit_value( esc_url_raw( $raw_value ), $field );
				if ( '' !== $value ) { $fixed_clean[ $field ] = $value; }
			}

			if ( $fixed_clean ) { update_post_meta( $post_id, '_mags_hero_fixed_content', $fixed_clean ); } else { delete_post_meta( $post_id, '_mags_hero_fixed_content' ); }
		}
	}
}
add_action( 'save_post', 'mags_core_save_meta' );
function mags_core_admin_assets( $hook ) { if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { wp_enqueue_media(); wp_enqueue_script( 'mags-core-admin', MAGS_CORE_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), MAGS_CORE_VERSION, true ); wp_enqueue_style( 'mags-core-admin', MAGS_CORE_URL . 'assets/admin.css', array(), MAGS_CORE_VERSION ); } }
add_action( 'admin_enqueue_scripts', 'mags_core_admin_assets' );

function mags_core_order_notices( $query ) {
	if ( is_admin() ) {
		if ( $query->is_main_query() && $query->get( 'post_type' ) === 'mags_notice' && isset( $_GET['mags_notice_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['mags_notice_status'] ) );
			if ( $status === 'archived' ) { $query->set( 'meta_key', '_mags_notice_archived' ); $query->set( 'meta_value', '1' ); }
			if ( $status === 'current' ) { $query->set( 'meta_query', array( array( 'key' => '_mags_notice_archived', 'compare' => 'NOT EXISTS' ) ) ); }
		}
		return;
	}
	$post_type = $query->get( 'post_type' );
	$is_notice = $query->is_post_type_archive( 'mags_notice' ) || $post_type === 'mags_notice' || ( is_array( $post_type ) && in_array( 'mags_notice', $post_type, true ) );
	if ( ! $is_notice ) { return; }
	$show_archived = isset( $_GET['notice_archive'] ) && sanitize_key( wp_unslash( $_GET['notice_archive'] ) ) === 'archived';
	$query->set( 'meta_query', $show_archived ? array( array( 'key' => '_mags_notice_archived', 'value' => '1' ) ) : array( array( 'key' => '_mags_notice_archived', 'compare' => 'NOT EXISTS' ) ) );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
	if ( $query->is_main_query() ) { $query->set( 'posts_per_page', 12 ); }
}
add_action( 'pre_get_posts', 'mags_core_order_notices' );

function mags_core_notice_admin_filter() {
	if ( get_current_screen()->post_type !== 'mags_notice' ) { return; }
	$current = isset( $_GET['mags_notice_status'] ) ? sanitize_key( wp_unslash( $_GET['mags_notice_status'] ) ) : '';
	echo '<label class="screen-reader-text" for="mags_notice_status">Filter by notice status</label><select name="mags_notice_status" id="mags_notice_status"><option value="">All notice statuses</option><option value="current"' . selected( $current, 'current', false ) . '>Current</option><option value="archived"' . selected( $current, 'archived', false ) . '>Archived</option></select>';
}
add_action( 'restrict_manage_posts', 'mags_core_notice_admin_filter' );

function mags_core_admin_columns( $columns ) {
	$post_type = get_current_screen()->post_type;
	if ( $post_type === 'mags_notice' ) { $columns['mags_status'] = 'Status'; $columns['mags_document'] = 'PDF'; }
	if ( $post_type === 'mags_download' ) { $columns['mags_download_type'] = 'Document type'; $columns['mags_document'] = 'PDF'; }
	if ( $post_type === 'mags_event' ) { $columns['mags_event_date'] = 'Event date'; $columns['mags_photos'] = 'Photos'; $columns['mags_featured'] = 'Homepage'; }
	if ( $post_type === 'mags_gallery' ) { $columns['mags_photos'] = 'Photos'; }
	if ( $post_type === 'mags_enquiry' ) { return array( 'cb' => $columns['cb'], 'title' => 'Enquiry', 'mags_name' => 'Name', 'mags_email' => 'Email', 'mags_phone' => 'Phone', 'date' => 'Received' ); }
	return $columns;
}
foreach ( array( 'mags_notice', 'mags_download', 'mags_event', 'mags_gallery', 'mags_enquiry' ) as $mags_core_column_type ) { add_filter( 'manage_' . $mags_core_column_type . '_posts_columns', 'mags_core_admin_columns' ); }
function mags_core_admin_column_content( $column, $post_id ) {
	if ( $column === 'mags_status' ) { echo get_post_meta( $post_id, '_mags_notice_archived', true ) === '1' ? '<strong>Archived</strong>' : 'Current'; }
	if ( $column === 'mags_document' ) { $url = get_post_meta( $post_id, '_mags_document_url', true ); echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Open PDF</a>' : '—'; }
	if ( $column === 'mags_download_type' ) { $terms = get_the_terms( $post_id, 'mags_download_type' ); echo $terms && ! is_wp_error( $terms ) ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ) : 'Uncategorized'; }
	if ( $column === 'mags_event_date' ) { echo esc_html( get_post_meta( $post_id, '_mags_event_date', true ) ?: '—' ); }
	if ( $column === 'mags_photos' ) { echo esc_html( (string) count( mags_core_gallery_image_ids( $post_id ) ) ); }
	if ( $column === 'mags_featured' ) { echo get_post_meta( $post_id, '_mags_featured_homepage', true ) === '1' ? 'Featured' : '—'; }
	if ( $column === 'mags_name' ) { echo esc_html( get_post_meta( $post_id, '_mags_enquiry_name', true ) ); }
	if ( $column === 'mags_email' ) { $email = get_post_meta( $post_id, '_mags_enquiry_email', true ); echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'; }
	if ( $column === 'mags_phone' ) { echo esc_html( get_post_meta( $post_id, '_mags_enquiry_phone', true ) ?: '—' ); }
}
foreach ( array( 'mags_notice', 'mags_download', 'mags_event', 'mags_gallery', 'mags_enquiry' ) as $mags_core_column_type ) { add_action( 'manage_' . $mags_core_column_type . '_posts_custom_column', 'mags_core_admin_column_content', 10, 2 ); }

function mags_core_contact_shortcode() {
	$status = isset( $_GET['mags_enquiry'] ) ? sanitize_key( wp_unslash( $_GET['mags_enquiry'] ) ) : '';
	$message = $status === 'sent' ? '<p class="mags-message mags-message--success" role="status">Thank you. Your enquiry has been received.</p>' : ( $status === 'error' ? '<p class="mags-message" role="alert">Please complete all required fields and try again.</p>' : ( $status === 'limit' ? '<p class="mags-message" role="alert">Please wait a few minutes before submitting another enquiry.</p>' : '' ) );
	ob_start(); echo $message; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="contact-form"><input type="hidden" name="action" value="mags_send_enquiry"><div class="form-row"><div class="form-field"><label for="mags_name">Name *</label><input id="mags_name" name="mags_name" autocomplete="name" required></div><div class="form-field"><label for="mags_email">Email *</label><input id="mags_email" type="email" name="mags_email" autocomplete="email" required></div></div><div class="form-field"><label for="mags_phone">Phone</label><input id="mags_phone" name="mags_phone" autocomplete="tel"></div><div class="form-field"><label for="mags_message">How can we help? *</label><textarea id="mags_message" name="mags_message" rows="6" required></textarea></div><div class="screen-reader-text" aria-hidden="true"><label for="mags_website">Leave this blank</label><input id="mags_website" name="mags_website" tabindex="-1" autocomplete="off"></div><input type="hidden" name="mags_return" value="<?php echo esc_url( ( is_ssl() ? 'https://' : 'http://' ) . wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) . wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ); ?>"><?php wp_nonce_field( 'mags_contact', 'mags_contact_nonce' ); ?><button class="button" type="submit">Send enquiry</button></form><?php return ob_get_clean();
}
function mags_core_contact_register_shortcode() { remove_shortcode( 'mags_contact_form' ); add_shortcode( 'mags_contact_form', 'mags_core_contact_shortcode' ); }
add_action( 'after_setup_theme', 'mags_core_contact_register_shortcode', 20 );
function mags_core_contact_redirect( $status ) { $return = isset( $_POST['mags_return'] ) ? wp_unslash( $_POST['mags_return'] ) : home_url( '/' ); $return = wp_validate_redirect( $return, home_url( '/' ) ); wp_safe_redirect( add_query_arg( 'mags_enquiry', $status, $return ) ); exit; }
function mags_core_send_enquiry() {
	if ( ! isset( $_POST['mags_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mags_contact_nonce'] ) ), 'mags_contact' ) ) { mags_core_contact_redirect( 'error' ); }
	if ( ! empty( $_POST['mags_website'] ) ) { mags_core_contact_redirect( 'sent' ); }
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown'; $key = 'mags_enquiry_' . hash( 'sha256', $ip ); $count = (int) get_transient( $key ); if ( $count >= 5 ) { mags_core_contact_redirect( 'limit' ); }
	$name = sanitize_text_field( wp_unslash( $_POST['mags_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['mags_email'] ?? '' ) ); $phone = sanitize_text_field( wp_unslash( $_POST['mags_phone'] ?? '' ) ); $body = sanitize_textarea_field( wp_unslash( $_POST['mags_message'] ?? '' ) ); if ( $name === '' || ! is_email( $email ) || $body === '' ) { mags_core_contact_redirect( 'error' ); }
	$post_id = wp_insert_post( array( 'post_type' => 'mags_enquiry', 'post_status' => 'private', 'post_title' => sprintf( 'Enquiry — %s — %s', $name, current_time( 'Y-m-d H:i' ) ), 'post_content' => $body, 'meta_input' => array( '_mags_enquiry_name' => $name, '_mags_enquiry_email' => $email, '_mags_enquiry_phone' => $phone ) ), true );
	if ( is_wp_error( $post_id ) ) { mags_core_contact_redirect( 'error' ); }
	set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS ); $recipient = mags_core_option( 'email', get_option( 'admin_email' ) ); $subject = sprintf( '[%s] Website enquiry from %s', mags_core_option( 'school_name', get_bloginfo( 'name' ) ), $name ); $mail_body = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\n\nMessage:\n{$body}"; wp_mail( $recipient, $subject, $mail_body, array( 'Reply-To: ' . $email ) ); mags_core_contact_redirect( 'sent' );
}
add_action( 'admin_post_nopriv_mags_send_enquiry', 'mags_core_send_enquiry' );
add_action( 'admin_post_mags_send_enquiry', 'mags_core_send_enquiry' );

function mags_core_setup_page() { if ( ! current_user_can( 'manage_options' ) ) { return; } ?><div class="wrap"><h1>MAGS Site Setup</h1><p>This creates the school-approved editable page structure, homepage assignment and primary navigation. Existing staff-authored content is preserved.</p><form method="post"><?php wp_nonce_field( 'mags_core_run_setup', 'mags_core_setup_nonce' ); ?><input type="hidden" name="mags_core_run_setup" value="1"><?php submit_button( 'Create / verify site structure', 'primary', 'submit', false ); ?></form><?php if ( get_option( 'mags_core_setup_done' ) ) { echo '<p><strong>Setup completed.</strong> Add the remaining approved contact details, admissions information and photographs before launch.</p>'; } ?></div><?php }

function mags_core_official_pages() {
	return array(
		'home' => array( 'Home', 'The MAGS homepage is supplied by the active theme.' ),
		'news-and-events' => array( 'News & Events', '' ),
		'about-the-school' => array( 'About the School', '<h2>Maharaja Agrasen—the legendary king</h2><p>Maharaja Agrasen Global School and its sister institutions are named after the ancient Indian king Maharaja Agrasen. He established a deeply egalitarian society in his empire with Agroha in North India as its capital. His core philosophy was rooted in inclusive governance, social equality and economic empowerment through entrepreneurship, trade and commerce.</p><p>His era is renowned for a unique “one brick, one coin” policy under which existing citizens supported new families settling in the kingdom.</p>' ),
		'history' => array( 'History', '<p>Maharaja Agrasen Global School is sponsored by the Maharaja Agrasen Technical Education Society, Delhi.</p><p>The Society was founded in 1998 with the vision to establish institutions of excellence, provide quality education to our youth and infuse them with the noble values of Indian culture and a spirit of service to the community, the nation and humanity.</p><p>The founding members of the Society include distinguished educationists, captains of industry, senior civil servants and leading social activists. The Founder Chairman of the Society is Dr. Nand Kishore Garg, eminent for his long years of public service.</p><p>In pursuance of its vision, the Society established the following institutions of higher education:</p><ul><li>Maharaja Agrasen Institute of Technology, Delhi — 1999</li><li>Maharaja Agrasen Institute of Management Studies, Delhi — 2003</li><li>Maharaja Agrasen University, Baddi, Himachal Pradesh — 2013</li><li>Maharaja Agrasen Business School, Delhi — 2023</li></ul><p>The Institute of Technology and Institute of Management Studies, Delhi, are affiliated to Guru Gobind Singh Indraprastha University, Delhi. Both institutes are nationally accredited by the National Board of Accreditation with A++ ranking.</p><p>Maharaja Agrasen University was incorporated by an Act of the Government of Himachal Pradesh. The University was inaugurated by the then Hon’ble President of India, Shri Pranab Mukherjee, on May 21, 2013.</p><p>To provide school education from Pre-primary onwards, Maharaja Agrasen Global School was established in 2010. The school was rated a School of Excellence by Indian Talent Olympiad in 2024.</p>' ),
		'vision-and-mission' => array( 'School’s Mission', '<h2>आत्म दीपो भवः — Be your own light</h2><p>The school’s mission is expressed in the motto in its logo: “आत्म दीपो भवः”. It embodies a unique approach to learning: true learning comes from within.</p><p>The traditional diya, or oil lamp, in the logo is a metaphor for an ignited inner light.</p><p>In practical terms, the mission of the school is to foster every student to their fullest potential, ultimately helping each learner evolve into the best version of themselves through skill development, intellectual growth and a spirit of service to the community, the country and humanity.</p><p>To achieve this, the school will adopt the best pedagogy, practices and systems of teaching and learning within a model ecosystem, led by highly qualified and fully motivated teachers.</p>' ),
		'school-motto' => array( 'School Motto', '<h2>आत्म दीपो भवः</h2><p><strong>“Be your own light.”</strong></p><p>The school’s motto embodies its unique approach to learning: true learning comes from within.</p><p>The traditional diya, or oil lamp, in the school logo represents the ignited inner light of knowledge, character, confidence and service.</p>' ),
		'patrons-message' => array( 'Patron’s Message', '<p>With the founding of Maharaja Agrasen Technical Education Society, Delhi, in 1998, we had a dream of providing value-based, quality educational facilities to our youth from Nursery to PhD.</p><p>The dream took shape gradually with our Institutes of Technology, Management Studies, University, Business School and junior school being established during the last 27 years. Currently, we have a total of more than 10,000 students. Our dream has become a reality.</p><p>Maharaja Agrasen Global School, Delhi, facilitating the first steps of learning from Pre-primary onwards, occupies a very special place in our group of institutions.</p><p>The school’s motto “आत्म दीपो भवः”—“Be your own light”—is the guiding principle for all of us and all our students.</p><p>The dream of ensuring value-based, quality education for each of our students is a continuing dream. We will keep pursuing this dream with deep dedication and earnest commitment.</p><p><strong>Dr. Nand Kishore Garg</strong><br>Patron<br>Maharaja Agrasen Global School</p>' ),
		'chairmans-message' => array( 'Chairman’s Message', '<p><strong>Er. T. R. Garg</strong><br>Chairman<br>Maharaja Agrasen Global School</p>' ),
		'principals-message' => array( 'Principal’s Message', '<p>Welcome to Maharaja Agrasen Global School!</p><p>At MAGS, we believe discipline is the foundation, innovation is the spark, sports build the spirit, and academics form the core.</p><p><strong>Discipline shapes character.</strong> We nurture self-discipline, respect and responsibility so students grow into citizens of integrity.</p><p><strong>Innovation drives learning.</strong> Our classrooms go beyond textbooks with technology, creativity and critical thinking to prepare students for tomorrow’s world.</p><p><strong>Sports build champions.</strong> Through teamwork, fitness and fair play, we teach resilience and leadership that last far beyond the playground.</p><p><strong>Academics form the core.</strong> With a strong CBSE curriculum and dedicated faculty, we ensure conceptual clarity and excellence in every subject.</p><p>Welcome once again.</p><p><strong>Meetu Aggarwal</strong><br>Principal<br>Maharaja Agrasen Global School</p>' ),
		'academics' => array( 'Academics', '<h2>Academics form the core</h2><p>With a strong CBSE curriculum and dedicated faculty, Maharaja Agrasen Global School focuses on conceptual clarity and excellence in every subject. Technology, creativity and critical thinking help learning go beyond textbooks.</p>' ),
		'infrastructure' => array( 'Infrastructure', '<p>The school’s learning infrastructure includes the school building, library, computer laboratory, mathematics laboratory, biotechnology laboratory, chemistry laboratory, sports facilities, and spaces for music, dance, drama and cultural activities.</p>' ),
		'laboratories' => array( 'Laboratories', '<h2>Learning through exploration</h2><ul><li>Computer Laboratory</li><li>Mathematics Laboratory</li><li>Biotechnology Laboratory</li><li>Chemistry Laboratory</li></ul>' ),
		'library' => array( 'Library', '<h2>A space for reading and discovery</h2><p>The school library is an essential part of the learning infrastructure. Programme details and photographs will be published after school approval.</p>' ),
		'sports-facilities' => array( 'Sports Facilities', '<h2>Sports build the spirit</h2><p>Through teamwork, fitness and fair play, the school seeks to develop resilience and leadership that last far beyond the playground.</p>' ),
		'student-activities' => array( 'Student Activities', '<p>Music, dance, drama, cultural activities and sport form an important part of student life at Maharaja Agrasen Global School.</p>' ),
		'student-support' => array( 'Student Support', '<p>Student support at Maharaja Agrasen Global School includes counselling and school transport information.</p>' ),
		'counselling' => array( 'Counselling', '<p>The school’s counselling support covers:</p><ul><li>Personal counselling</li><li>Educational counselling</li><li>Developmental counselling</li><li>Social counselling</li><li>Spiritual and cultural guidance</li></ul>' ),
		'transport-facilities' => array( 'Transport Facilities', '<p>School transport route, stop and safety information is available through the school office.</p>' ),
		'admissions' => array( 'Admissions', '<h2>Admissions at MAGS</h2><p>Approved admission schedule, eligibility, required documents and application procedure will be published here by the school.</p>' ),
		'fees' => array( 'Fees', '<h2>School fee information</h2><p>The current, school-approved fee structure will be published here and in Downloads.</p>' ),
		'scholarships' => array( 'Scholarships', '<h2>Scholarship support</h2><p>Maharaja Agrasen Global School provides for the following scholarship categories, subject to the school’s approved eligibility and application policy:</p><ul><li>Merit scholarships</li><li>Merit-cum-means scholarships</li></ul>' ),
		'contact-us' => array( 'Contact Us', '' ),
	);
}

function mags_core_is_starter_content( $content ) {
	$starter_phrases = array( 'Replace this starter copy', 'Add approved', 'Add the approved', 'Add the official school motto and its meaning', 'Every learner develops knowledge, character and confidence', 'Offer inclusive, engaging learning rooted in empathy, integrity and service', 'Describe curriculum', 'Describe classrooms', 'Describe science', 'Describe reading', 'Describe indoor', 'Describe clubs', 'Replace with approved', 'Publish the approved' );
	if ( trim( wp_strip_all_tags( $content ) ) === '' ) { return true; }
	foreach ( $starter_phrases as $phrase ) { if ( strpos( $content, $phrase ) !== false ) { return true; } }
	return false;
}

/**
 * Content shipped before the school supplied its final History, Mission and Motto copy.
 * These exact values are retained only so the one-time migration can distinguish
 * untouched plugin content from content written by a school administrator.
 */
function mags_core_previous_approved_page_content() {
	return array(
		'history' => array(
			'<p>Maharaja Agrasen Global School is sponsored by the Maharaja Agrasen Technical Education Society, Delhi.</p><p>The Society was founded in 1998 with the vision to establish institutions of excellence, provide quality education to young people and infuse them with noble values of Indian culture and a spirit of service to the community, the nation and humanity. Its founding members include distinguished educationists, captains of industry, senior civil servants and leading social activists. The Founder Chairman is Dr. Nand Kishore Garg, eminent for his long years of public service.</p><h2>Institutions established by the Society</h2><ul><li>Maharaja Agrasen Institute of Technology, Delhi — 1999</li><li>Maharaja Agrasen Institute of Management Studies, Delhi — 2003</li><li>Maharaja Agrasen Global School, Delhi — 2010</li><li>Maharaja Agrasen University, Baddi, Himachal Pradesh — 2013</li><li>Maharaja Agrasen Business School, Delhi — 2023</li></ul><p>The Institute of Technology and Institute of Management Studies are affiliated to Guru Gobind Singh Indraprastha University, Delhi. The school, established for education from Pre-primary onwards, was rated a School of Excellence by Indian Talent Olympiad in 2024.</p>',
		),
		'vision-and-mission' => array(
			'<h2>आत्म दीपो भवः — Be your own light</h2><p>The school’s mission is expressed through the motto in its logo: “आत्म दीपो भवः”. It embodies a distinctive approach to education—the belief that true learning comes from within.</p><p>The traditional diya, or oil lamp, in the logo is a metaphor for an ignited inner light. In practical terms, the mission is to foster every student to their fullest potential and help them evolve into their best version through skill development, intellectual growth and a spirit of service to the community, the country and humanity.</p><p>To achieve this, the school is committed to effective pedagogy, sound teaching and learning practices, strong systems, a model ecosystem and qualified, fully motivated teachers.</p>',
		),
		'school-motto' => array(
			'<h2>आत्म दीपो भवः</h2><p><strong>“Be your own light.”</strong></p><p>The diya in the school logo represents the inner light of knowledge, character, confidence and service.</p>',
		),
	);
}

function mags_core_normalize_page_content( $content ) {
	$content = html_entity_decode( wp_strip_all_tags( (string) $content ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$content = preg_replace( '/\s+/u', ' ', $content );
	return trim( (string) $content );
}

/**
 * Safely migrate only empty/starter pages or pages that still contain a known,
 * untouched earlier MAGS Core version. Administrator-authored copy is preserved.
 */
function mags_core_migrate_approved_page_content( $ids ) {
	$pages    = mags_core_official_pages();
	$previous = mags_core_previous_approved_page_content();

	foreach ( array( 'history', 'vision-and-mission', 'school-motto' ) as $slug ) {
		if ( empty( $ids[ $slug ] ) || is_wp_error( $ids[ $slug ] ) || empty( $pages[ $slug ][1] ) ) { continue; }
		$existing = get_post( $ids[ $slug ] );
		if ( ! $existing instanceof WP_Post ) { continue; }

		$existing_normalized = mags_core_normalize_page_content( $existing->post_content );
		$known_previous      = false;
		foreach ( $previous[ $slug ] as $previous_content ) {
			if ( hash_equals( hash( 'sha256', mags_core_normalize_page_content( $previous_content ) ), hash( 'sha256', $existing_normalized ) ) ) {
				$known_previous = true;
				break;
			}
		}

		if ( ! mags_core_is_starter_content( $existing->post_content ) && ! $known_previous ) { continue; }
		wp_update_post(
			array(
				'ID'           => $existing->ID,
				'post_title'   => $pages[ $slug ][0],
				'post_content' => $pages[ $slug ][1],
			)
		);
	}
}

function mags_core_find_page_by_slug( $slug ) {
	$matches = get_posts(
		array(
			'name'             => $slug,
			'post_type'        => 'page',
			'post_status'      => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page'   => 1,
			'suppress_filters' => false,
		)
	);
	return $matches ? $matches[0] : null;
}

function mags_core_apply_official_structure() {
	$pages = mags_core_official_pages();
	$ids   = array();
	foreach ( $pages as $slug => $page ) {
		$existing = mags_core_find_page_by_slug( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			if ( mags_core_is_starter_content( $existing->post_content ) && $page[1] !== '' ) {
				wp_update_post( array( 'ID' => $existing->ID, 'post_title' => $page[0], 'post_content' => $page[1] ) );
			}
		} else {
			$ids[ $slug ] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page[0], 'post_name' => $slug, 'post_content' => $page[1] ) );
		}
	}

	$all_pages = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'posts_per_page' => -1, 'suppress_filters' => false ) );
	foreach ( $pages as $slug => $page ) {
		if ( empty( $ids[ $slug ] ) || is_wp_error( $ids[ $slug ] ) ) { continue; }
		foreach ( $all_pages as $candidate ) {
			if ( (int) $candidate->ID === (int) $ids[ $slug ] ) { continue; }
			if ( preg_match( '/^' . preg_quote( $slug, '/' ) . '-\d+$/', $candidate->post_name ) && trim( $candidate->post_content ) === trim( $page[1] ) ) { wp_delete_post( $candidate->ID, true ); }
		}
	}

	foreach ( array( 'history', 'vision-and-mission', 'school-motto', 'patrons-message', 'chairmans-message', 'principals-message' ) as $slug ) {
		if ( ! empty( $ids[ $slug ] ) && ! empty( $ids['about-the-school'] ) && ! is_wp_error( $ids[ $slug ] ) ) { wp_update_post( array( 'ID' => $ids[ $slug ], 'post_parent' => $ids['about-the-school'] ) ); }
	}
	foreach ( array( 'laboratories', 'library', 'sports-facilities', 'student-activities' ) as $slug ) {
		if ( ! empty( $ids[ $slug ] ) && ! empty( $ids['infrastructure'] ) && ! is_wp_error( $ids[ $slug ] ) ) { wp_update_post( array( 'ID' => $ids[ $slug ], 'post_parent' => $ids['infrastructure'] ) ); }
	}
	foreach ( array( 'counselling', 'transport-facilities' ) as $slug ) {
		if ( ! empty( $ids[ $slug ] ) && ! empty( $ids['student-support'] ) && ! is_wp_error( $ids[ $slug ] ) ) { wp_update_post( array( 'ID' => $ids[ $slug ], 'post_parent' => $ids['student-support'] ) ); }
	}
	foreach ( array( 'fees', 'scholarships' ) as $slug ) {
		if ( ! empty( $ids[ $slug ] ) && ! empty( $ids['admissions'] ) && ! is_wp_error( $ids[ $slug ] ) ) { wp_update_post( array( 'ID' => $ids[ $slug ], 'post_parent' => $ids['admissions'] ) ); }
	}

	if ( ! empty( $ids['contact-us'] ) && ! is_wp_error( $ids['contact-us'] ) ) { update_post_meta( $ids['contact-us'], '_wp_page_template', 'template-contact.php' ); }
	foreach ( array( 'patrons-message', 'chairmans-message', 'principals-message' ) as $slug ) { if ( ! empty( $ids[ $slug ] ) && ! is_wp_error( $ids[ $slug ] ) ) { update_post_meta( $ids[ $slug ], '_wp_page_template', 'template-leadership.php' ); } }
	if ( ! empty( $ids['home'] ) && ! is_wp_error( $ids['home'] ) ) { update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $ids['home'] ); }
	if ( ! empty( $ids['news-and-events'] ) && ! is_wp_error( $ids['news-and-events'] ) ) { update_option( 'page_for_posts', $ids['news-and-events'] ); }

	$options = (array) get_option( 'mags_core_settings', array() );
	if ( empty( $options['address'] ) || $options['address'] === 'Maharaja Agrasen Global School, Delhi, India' ) { $options['address'] = '1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086'; }
	if ( isset( $options['phone'] ) && $options['phone'] === '011-0000 0000' ) { $options['phone'] = ''; }
	if ( isset( $options['email'] ) && $options['email'] === 'info@mags.edu.in' ) { $options['email'] = ''; }
	update_option( 'mags_core_settings', $options );
	if ( get_option( 'blogname' ) === 'My WordPress' ) { update_option( 'blogname', 'Maharaja Agrasen Global School' ); }
	update_option( 'blogdescription', 'आत्म दीपो भवः — Be your own light' );
	return array( $pages, $ids );
}

function mags_core_ensure_primary_menu( $pages, $ids ) {
	$menu_name = 'MAGS Primary';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) { return; }

	$existing_items = (array) wp_get_nav_menu_items( $menu_id );
	$existing_pages = array();
	$menu_items_by_page = array();
	$subpage_ids = array_filter( array( isset( $ids['history'] ) ? (int) $ids['history'] : 0, isset( $ids['vision-and-mission'] ) ? (int) $ids['vision-and-mission'] : 0 ) );
	foreach ( $existing_items as $item ) {
		if ( $item->type !== 'post_type' || $item->object !== 'page' ) { continue; }
		if ( (int) $item->menu_item_parent === 0 && in_array( (int) $item->object_id, $subpage_ids, true ) ) { wp_delete_post( $item->ID, true ); continue; }
		$existing_pages[] = (int) $item->object_id;
		$menu_items_by_page[ (int) $item->object_id ] = (int) $item->ID;
	}
	foreach ( array( 'home', 'about-the-school', 'academics', 'infrastructure', 'student-activities', 'admissions', 'news-and-events', 'contact-us' ) as $slug ) {
		if ( empty( $ids[ $slug ] ) || is_wp_error( $ids[ $slug ] ) || in_array( (int) $ids[ $slug ], $existing_pages, true ) ) { continue; }
		$item_id = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $pages[ $slug ][0], 'menu-item-object' => 'page', 'menu-item-object-id' => $ids[ $slug ], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
		if ( ! is_wp_error( $item_id ) ) { $existing_pages[] = (int) $ids[ $slug ]; $menu_items_by_page[ (int) $ids[ $slug ] ] = (int) $item_id; }
	}

	$submenu_pages = array(
		'student-activities' => array( 'counselling', 'transport-facilities' ),
		'admissions'         => array( 'fees', 'scholarships' ),
	);
	foreach ( $submenu_pages as $parent_slug => $child_slugs ) {
		if ( empty( $ids[ $parent_slug ] ) || empty( $menu_items_by_page[ (int) $ids[ $parent_slug ] ] ) ) { continue; }
		$parent_item_id = $menu_items_by_page[ (int) $ids[ $parent_slug ] ];
		foreach ( $child_slugs as $child_slug ) {
			if ( empty( $ids[ $child_slug ] ) || is_wp_error( $ids[ $child_slug ] ) ) { continue; }
			$child_page_id = (int) $ids[ $child_slug ];
			$child_item_id = isset( $menu_items_by_page[ $child_page_id ] ) ? $menu_items_by_page[ $child_page_id ] : 0;
			$updated_item  = wp_update_nav_menu_item( $menu_id, $child_item_id, array( 'menu-item-title' => $pages[ $child_slug ][0], 'menu-item-object' => 'page', 'menu-item-object-id' => $child_page_id, 'menu-item-type' => 'post_type', 'menu-item-parent-id' => $parent_item_id, 'menu-item-status' => 'publish' ) );
			if ( ! is_wp_error( $updated_item ) ) { $menu_items_by_page[ $child_page_id ] = (int) $updated_item; }
		}
	}
	$locations            = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Rebuild the plugin-managed menu in the order supplied by the school.
 * Page content remains editable; only the managed navigation hierarchy changes.
 */
function mags_core_ensure_primary_menu_v2( $pages, $ids ) {
	$menu_name = 'MAGS Primary';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) { return; }

	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( (int) $item->ID, true );
	}

	$position = 1;
	$add_page = static function ( $slug, $title = '', $parent = 0 ) use ( $menu_id, $pages, $ids, &$position ) {
		if ( empty( $ids[ $slug ] ) || is_wp_error( $ids[ $slug ] ) ) { return 0; }
		$item_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title' => $title ?: $pages[ $slug ][0], 'menu-item-object' => 'page',
			'menu-item-object-id' => (int) $ids[ $slug ], 'menu-item-type' => 'post_type',
			'menu-item-parent-id' => (int) $parent, 'menu-item-position' => $position++,
			'menu-item-status' => 'publish',
		) );
		return is_wp_error( $item_id ) ? 0 : (int) $item_id;
	};
	$add_custom = static function ( $title, $url, $parent ) use ( $menu_id, &$position ) {
		if ( ! $url ) { return 0; }
		$item_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-type' => 'custom',
			'menu-item-parent-id' => (int) $parent, 'menu-item-position' => $position++,
			'menu-item-status' => 'publish',
		) );
		return is_wp_error( $item_id ) ? 0 : (int) $item_id;
	};

	$add_page( 'home' );
	$about_item = $add_page( 'about-the-school', 'About' );
	foreach ( array( 'history', 'vision-and-mission', 'patrons-message', 'chairmans-message', 'principals-message' ) as $slug ) { $add_page( $slug, '', $about_item ); }
	$add_page( 'academics' );
	$infrastructure_item = $add_page( 'infrastructure' );
	foreach ( array( 'library', 'laboratories', 'sports-facilities', 'student-activities' ) as $slug ) { $add_page( $slug, '', $infrastructure_item ); }
	$support_item = $add_page( 'student-support' );
	foreach ( array( 'counselling', 'transport-facilities' ) as $slug ) { $add_page( $slug, '', $support_item ); }
	$admissions_item = $add_page( 'admissions' );
	foreach ( array( 'fees', 'scholarships' ) as $slug ) { $add_page( $slug, '', $admissions_item ); }
	$updates_item = $add_page( 'news-and-events', 'Updates' );
	$add_custom( 'Notice Board', get_post_type_archive_link( 'mags_notice' ) ?: home_url( '/notices/' ), $updates_item );
	$add_custom( 'Downloads', get_post_type_archive_link( 'mags_download' ) ?: home_url( '/downloads/' ), $updates_item );
	$add_custom( 'Gallery', get_post_type_archive_link( 'mags_gallery' ) ?: home_url( '/gallery/' ), $updates_item );
	$add_page( 'contact-us', 'Contact' );

	$locations            = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

function mags_core_upgrade_official_content() {
	if ( (int) get_option( 'mags_core_content_version', 0 ) >= 11 ) { return; }
	list( $pages, $ids ) = mags_core_apply_official_structure();
	mags_core_migrate_approved_page_content( $ids );
	mags_core_ensure_download_types();
	mags_core_ensure_primary_menu_v2( $pages, $ids );
	update_option( 'mags_core_content_version', 11 );
}
add_action( 'init', 'mags_core_upgrade_official_content', 20 );

function mags_core_run_setup() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_POST['mags_core_run_setup'] ) ) { return; } if ( ! isset( $_POST['mags_core_setup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mags_core_setup_nonce'] ) ), 'mags_core_run_setup' ) ) { return; }
	list( $pages, $ids ) = mags_core_apply_official_structure();
	mags_core_migrate_approved_page_content( $ids );
	mags_core_ensure_download_types();
	mags_core_ensure_primary_menu_v2( $pages, $ids );
	update_option( 'mags_core_content_version', 11 ); update_option( 'mags_core_setup_done', 1 ); delete_option( 'mags_core_needs_setup' ); flush_rewrite_rules(); wp_safe_redirect( add_query_arg( 'mags_setup', 'done', admin_url( 'tools.php?page=mags-core-setup' ) ) ); exit;
}
add_action( 'admin_init', 'mags_core_run_setup' );
function mags_core_setup_notice() { if ( get_option( 'mags_core_needs_setup' ) && current_user_can( 'manage_options' ) ) { echo '<div class="notice notice-info"><p><strong>MAGS Core:</strong> <a href="' . esc_url( admin_url( 'tools.php?page=mags-core-setup' ) ) . '">Create the initial MAGS page structure and navigation</a>.</p></div>'; } }
add_action( 'admin_notices', 'mags_core_setup_notice' );
