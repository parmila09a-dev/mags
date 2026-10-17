<?php
/**
 * Import the school-supplied ZIP into optimized, CMS-managed gallery albums.
 *
 * Usage:
 * php tools/import-school-gallery.php "C:\\path\\to\\school-photos.zip"
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "This importer can only be run from the command line.\n" );
}

$wordpress_loader = dirname( __DIR__ ) . '/app/public/wp-load.php';
if ( ! is_readable( $wordpress_loader ) ) {
	exit( "WordPress could not be found.\n" );
}

require $wordpress_loader;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if ( ! class_exists( 'ZipArchive' ) ) {
	exit( "The PHP ZIP extension is required.\n" );
}

$zip_path = isset( $argv[1] ) ? $argv[1] : '';
if ( ! $zip_path || ! is_readable( $zip_path ) ) {
	exit( "Pass the readable school photo ZIP as the first argument.\n" );
}

$albums = array(
	'Maharaja Agrasen Global School New Academic Session 2026-27 Begins with Vedic Havan (29 June 2026) Lakhi Ram Park' => array(
		'title'       => 'New Academic Session 2026–27: Vedic Havan',
		'slug'        => 'new-academic-session-2026-27-vedic-havan',
		'date'        => '2026-06-29 12:00:00',
		'description' => 'The new academic session began with a Vedic havan at the Lakhi Ram Park campus.',
	),
	'Ram Navami Hawan, Maharaja Agrasen Global School, Lakhi Ram Park (27 March 2026)' => array(
		'title'       => 'Ram Navami Havan 2026',
		'slug'        => 'ram-navami-havan-2026',
		'date'        => '2026-03-27 12:00:00',
		'description' => 'The school community marked Ram Navami with a traditional havan at the Lakhi Ram Park campus.',
	),
	'Bhumi Pujan Maharaja Agrasen Global School (30 May 2025)' => array(
		'title'       => 'Bhumi Pujan 2025',
		'slug'        => 'bhumi-pujan-2025',
		'date'        => '2025-05-30 12:00:00',
		'description' => 'Members of the school community participated in a traditional bhumi pujan ceremony.',
	),
	'New Academic Session 2019-20, A Havan Ceremony, Maharaja Agrasen Global School (10 July 2019)' => array(
		'title'       => 'New Academic Session 2019–20: Havan Ceremony',
		'slug'        => 'new-academic-session-2019-20-havan',
		'date'        => '2019-07-10 12:00:00',
		'description' => 'The 2019–20 academic session opened with a Vedic havan programme.',
	),
	'Teachers Day (5 Sep 2019) Lakhi Ram Park School' => array(
		'title'       => 'Teachers’ Day 2019',
		'slug'        => 'teachers-day-2019',
		'date'        => '2019-09-05 12:00:00',
		'description' => 'Students and staff celebrated Teachers’ Day at Maharaja Agrasen Global School.',
	),
	'Maharaja Agrasen Global School Inauguration(10 OCT 2018)' => array(
		'title'       => 'School Inauguration 2018',
		'slug'        => 'school-inauguration-2018',
		'date'        => '2018-10-10 12:00:00',
		'description' => 'Photographs from the inauguration of Maharaja Agrasen Global School.',
	),
	'Computer Lab' => array(
		'title'       => 'Learning in the Computer Lab',
		'slug'        => 'computer-lab',
		'date'        => '',
		'description' => 'Students learn with teacher guidance in the school computer laboratory.',
	),
	'Lakhi Ram Park School' => array(
		'title'       => 'Life at the Lakhi Ram Park Campus',
		'slug'        => 'lakhi-ram-park-campus',
		'date'        => '',
		'description' => 'A photographic view of school life at the Lakhi Ram Park campus.',
	),
);

function mags_import_log( $message ) {
	echo '[' . gmdate( 'H:i:s' ) . '] ' . $message . PHP_EOL;
}

function mags_existing_post_by_source( $post_type, $meta_key, $meta_value ) {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => $meta_key,
			'meta_value'     => $meta_value,
		)
	);
	return $posts ? (int) $posts[0] : 0;
}

function mags_optimize_editor( $source, $destination_base ) {
	$editor = wp_get_image_editor( $source );
	if ( is_wp_error( $editor ) ) {
		return $editor;
	}

	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
		$editor->maybe_exif_rotate();
	}

	$size = $editor->get_size();
	if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) && max( $size['width'], $size['height'] ) > 1800 ) {
		$resized = $editor->resize( 1800, 1800, false );
		if ( is_wp_error( $resized ) ) {
			return $resized;
		}
	}

	$editor->set_quality( 78 );
	$webp_path = $destination_base . '.webp';
	$saved     = $editor->save( $webp_path, 'image/webp' );
	if ( ! is_wp_error( $saved ) ) {
		return $saved;
	}

	$jpeg_path = $destination_base . '.jpg';
	return $editor->save( $jpeg_path, 'image/jpeg' );
}

function mags_import_optimized_file( $source, $attachment_name, $title, $alt_text, $parent_id, $source_key, $temporary_directory ) {
	$existing = mags_existing_post_by_source( 'attachment', '_mags_source_file', $source_key );
	if ( $existing ) {
		return $existing;
	}

	$input_path = trailingslashit( $temporary_directory ) . wp_unique_filename( $temporary_directory, sanitize_file_name( $attachment_name . '.jpg' ) );
	if ( ! copy( $source, $input_path ) ) {
		return new WP_Error( 'copy_failed', 'The source image could not be copied.' );
	}

	$destination_base = preg_replace( '/\.jpg$/i', '-optimized', $input_path );
	$saved            = mags_optimize_editor( $input_path, $destination_base );
	@unlink( $input_path );
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	$optimized_path = $saved['path'];
	$extension      = strtolower( pathinfo( $optimized_path, PATHINFO_EXTENSION ) );
	$file_array     = array(
		'name'     => sanitize_file_name( $attachment_name . '.' . $extension ),
		'tmp_name' => $optimized_path,
	);
	$attachment_id  = media_handle_sideload( $file_array, $parent_id, $title );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $optimized_path );
		return $attachment_id;
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
	update_post_meta( $attachment_id, '_mags_source_file', $source_key );
	wp_update_post(
		array(
			'ID'           => $attachment_id,
			'post_excerpt' => $alt_text,
		)
	);
	return (int) $attachment_id;
}

function mags_optimize_theme_jpeg( $path ) {
	if ( ! is_readable( $path ) ) {
		return false;
	}

	$actual_mime = wp_get_image_mime( $path );
	if ( 'image/jpeg' === $actual_mime && filesize( $path ) < 500 * KB_IN_BYTES ) {
		return false;
	}

	$editor = wp_get_image_editor( $path );
	if ( is_wp_error( $editor ) ) {
		mags_import_log( 'Theme image skipped: ' . basename( $path ) . ' (' . $editor->get_error_message() . ')' );
		return false;
	}
	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
		$editor->maybe_exif_rotate();
	}
	$size = $editor->get_size();
	if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) && max( $size['width'], $size['height'] ) > 1800 ) {
		$resized = $editor->resize( 1800, 1800, false );
		if ( is_wp_error( $resized ) ) {
			mags_import_log( 'Theme image skipped: ' . basename( $path ) . ' (' . $resized->get_error_message() . ')' );
			return false;
		}
	}
	$editor->set_quality( 78 );
	$saved = $editor->save( $path . '.mags-optimized.jpg', 'image/jpeg' );
	if ( is_wp_error( $saved ) ) {
		mags_import_log( 'Theme image skipped: ' . basename( $path ) . ' (' . $saved->get_error_message() . ')' );
		return false;
	}

	$optimized_path = $saved['path'];
	if ( 'image/jpeg' === $actual_mime && filesize( $optimized_path ) >= filesize( $path ) ) {
		@unlink( $optimized_path );
		return false;
	}

	$replacement = $path . '.replacement';
	if ( ! copy( $optimized_path, $replacement ) ) {
		@unlink( $optimized_path );
		return false;
	}
	@unlink( $optimized_path );
	if ( ! copy( $replacement, $path ) ) {
		@unlink( $replacement );
		return false;
	}
	@unlink( $replacement );
	return true;
}

$temporary_directory = trailingslashit( sys_get_temp_dir() ) . 'mags-gallery-import-' . getmypid();
if ( ! wp_mkdir_p( $temporary_directory ) ) {
	exit( "The temporary import directory could not be created.\n" );
}

$theme_images = get_template_directory() . '/assets/images';
foreach ( glob( $theme_images . '/*.jpg' ) as $theme_image ) {
	if ( mags_optimize_theme_jpeg( $theme_image ) ) {
		mags_import_log( 'Optimized theme image: ' . basename( $theme_image ) );
	}
}

$hero_source = $theme_images . '/mags-school-building.jpg';
if ( is_readable( $hero_source ) ) {
	$hero_id = mags_import_optimized_file(
		$hero_source,
		'maharaja-agrasen-global-school-building',
		'Maharaja Agrasen Global School building',
		'Maharaja Agrasen Global School building at Lakhi Ram Park, Rohini, Delhi',
		0,
		'theme:mags-school-building-hero',
		$temporary_directory
	);
	if ( ! is_wp_error( $hero_id ) ) {
		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id ) {
			set_post_thumbnail( $front_page_id, $hero_id );
		}
		mags_import_log( 'Optimized official school building image is now the homepage hero.' );
	}
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path ) ) {
	exit( "The school photo ZIP could not be opened.\n" );
}

$entries_by_album = array_fill_keys( array_keys( $albums ), array() );
for ( $index = 0; $index < $zip->numFiles; $index++ ) {
	$entry = $zip->statIndex( $index );
	if ( empty( $entry['name'] ) || ! preg_match( '/\.jpe?g$/i', $entry['name'] ) ) {
		continue;
	}
	$parts  = explode( '/', str_replace( '\\', '/', $entry['name'] ) );
	$folder = $parts[0];
	if ( isset( $entries_by_album[ $folder ] ) ) {
		$entries_by_album[ $folder ][] = array( 'index' => $index, 'name' => $entry['name'] );
	}
}

foreach ( $entries_by_album as $folder => $entries ) {
	if ( ! $entries ) {
		continue;
	}

	usort(
		$entries,
		function ( $left, $right ) {
			return strnatcasecmp( basename( $left['name'] ), basename( $right['name'] ) );
		}
	);
	$album = $albums[ $folder ];
	$post  = array(
		'post_type'    => 'mags_gallery',
		'post_status'  => 'publish',
		'post_title'   => $album['title'],
		'post_name'    => $album['slug'],
		'post_excerpt' => $album['description'],
		'post_content' => '<p>' . esc_html( $album['description'] ) . '</p>',
	);
	if ( $album['date'] ) {
		$post['post_date'] = $album['date'];
	}

	$gallery_id = mags_existing_post_by_source( 'mags_gallery', '_mags_source_album', $folder );
	if ( $gallery_id ) {
		$post['ID'] = $gallery_id;
	}
	$gallery_id = wp_insert_post( wp_slash( $post ), true );
	if ( is_wp_error( $gallery_id ) ) {
		mags_import_log( 'Album skipped: ' . $album['title'] . ' (' . $gallery_id->get_error_message() . ')' );
		continue;
	}
	update_post_meta( $gallery_id, '_mags_source_album', $folder );

	mags_import_log( 'Importing ' . count( $entries ) . ' photographs into “' . $album['title'] . '”…' );
	$image_ids = array();
	foreach ( $entries as $position => $entry ) {
		$source_key  = 'zip:' . $entry['name'];
		$existing_id = mags_existing_post_by_source( 'attachment', '_mags_source_file', $source_key );
		if ( $existing_id ) {
			$image_ids[] = $existing_id;
			continue;
		}

		$stream = $zip->getStream( $entry['name'] );
		if ( ! $stream ) {
			mags_import_log( 'Could not read: ' . $entry['name'] );
			continue;
		}
		$sequence   = $position + 1;
		$input_name = $album['slug'] . '-' . str_pad( (string) $sequence, 3, '0', STR_PAD_LEFT );
		$input_path = trailingslashit( $temporary_directory ) . $input_name . '.jpg';
		$output     = fopen( $input_path, 'wb' );
		if ( ! $output ) {
			fclose( $stream );
			continue;
		}
		stream_copy_to_stream( $stream, $output );
		fclose( $stream );
		fclose( $output );

		$alt_text         = $album['title'] . ' — photograph ' . $sequence;
		$destination_base = trailingslashit( $temporary_directory ) . $input_name . '-optimized';
		$saved            = mags_optimize_editor( $input_path, $destination_base );
		@unlink( $input_path );
		if ( is_wp_error( $saved ) ) {
			mags_import_log( 'Image skipped: ' . $entry['name'] . ' (' . $saved->get_error_message() . ')' );
			continue;
		}

		$optimized_path = $saved['path'];
		$extension      = strtolower( pathinfo( $optimized_path, PATHINFO_EXTENSION ) );
		$file_array     = array(
			'name'     => $input_name . '.' . $extension,
			'tmp_name' => $optimized_path,
		);
		$attachment_id  = media_handle_sideload( $file_array, $gallery_id, $album['title'] . ' — photograph ' . $sequence );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $optimized_path );
			mags_import_log( 'Upload skipped: ' . $entry['name'] . ' (' . $attachment_id->get_error_message() . ')' );
			continue;
		}
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		update_post_meta( $attachment_id, '_mags_source_file', $source_key );
		$image_ids[] = (int) $attachment_id;

		if ( 0 === $sequence % 20 ) {
			mags_import_log( $album['title'] . ': ' . $sequence . '/' . count( $entries ) . ' processed.' );
		}
	}

	$image_ids = array_values( array_unique( array_filter( array_map( 'absint', $image_ids ) ) ) );
	if ( $image_ids ) {
		update_post_meta( $gallery_id, '_mags_gallery_image_ids', $image_ids );
		set_post_thumbnail( $gallery_id, $image_ids[0] );
	}
	mags_import_log( 'Published “' . $album['title'] . '” with ' . count( $image_ids ) . ' photographs.' );
}

$zip->close();
@rmdir( $temporary_directory );
mags_import_log( 'Gallery import complete.' );
