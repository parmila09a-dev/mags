<?php
/**
 * Backup: the office downloads all portal data as one ZIP file.
 *
 * The file holds every portal table as a CSV file (opens in Excel), the same
 * tables as an SQL file (for the site administrator to load back into the
 * database), the list of portal logins (without passwords), the portal's
 * settings and, if the office ticks it, the uploaded files (homework, answers,
 * notes, photos and documents).
 *
 * The ZIP is written straight to the browser while the data is read, so a big
 * school does not need memory or disk space on the server for it, and no PHP
 * zip extension is needed. Because the file holds the personal details of every
 * child, the office types its password again before the download starts, and
 * each download is written in a log.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Rows read from the database at a time. */
define( 'MAGS_ERP_BACKUP_CHUNK', 1000 );

/**
 * A ZIP file written to the output as it is made. Text entries are compressed
 * (with a data descriptor after the data, since their size is known only at the
 * end); files are stored as they are, since photos and documents are already
 * compressed. Plain ZIP (no ZIP64): up to 4 GB and 65,535 entries.
 */
class MAGS_ERP_Zip {
	private $offset  = 0;
	private $entries = array();
	private $entry   = null;
	private $time;
	private $date;

	public function __construct() {
		$now        = getdate( (int) current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- DOS times are local times.
		$this->time = ( $now['hours'] << 11 ) | ( $now['minutes'] << 5 ) | intdiv( $now['seconds'], 2 );
		$this->date = ( ( $now['year'] - 1980 ) << 9 ) | ( $now['mon'] << 5 ) | $now['mday'];
	}

	private function out( $bytes ) {
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput -- binary ZIP data.
		$this->offset += strlen( $bytes );
	}

	private function header( $entry ) {
		return pack( 'VvvvvvVVVvv', 0x04034b50, 20, $entry['flags'], $entry['method'], $this->time, $this->date, $entry['crc'], $entry['csize'], $entry['size'], strlen( $entry['name'] ), 0 ) . $entry['name'];
	}

	/** Start a compressed text entry; write() adds to it and end() closes it. */
	public function begin( $name ) {
		$this->entry = array( 'name' => $name, 'offset' => $this->offset, 'flags' => 0x0808, 'method' => 8, 'crc' => 0, 'csize' => 0, 'size' => 0, 'hash' => hash_init( 'crc32b' ), 'deflate' => function_exists( 'deflate_init' ) ? deflate_init( ZLIB_ENCODING_RAW, array( 'level' => 6 ) ) : null, 'buffer' => '' );
		if ( ! $this->entry['deflate'] ) { $this->entry['method'] = 0; }
		$this->out( $this->header( $this->entry ) );
	}

	public function write( $data ) {
		if ( '' === $data ) { return; }
		hash_update( $this->entry['hash'], $data );
		$this->entry['size'] += strlen( $data );
		$chunk = $this->entry['deflate'] ? deflate_add( $this->entry['deflate'], $data, ZLIB_NO_FLUSH ) : $data;
		$this->entry['csize'] += strlen( $chunk );
		$this->out( $chunk );
	}

	public function end() {
		if ( $this->entry['deflate'] ) {
			$chunk = deflate_add( $this->entry['deflate'], '', ZLIB_FINISH );
			$this->entry['csize'] += strlen( $chunk );
			$this->out( $chunk );
		}
		$this->entry['crc'] = (int) hexdec( hash_final( $this->entry['hash'] ) );
		$this->out( pack( 'VVVV', 0x08074b50, $this->entry['crc'], $this->entry['csize'], $this->entry['size'] ) );
		unset( $this->entry['hash'], $this->entry['deflate'], $this->entry['buffer'] );
		$this->entries[] = $this->entry;
		$this->entry     = null;
	}

	public function add_string( $name, $data ) {
		$this->begin( $name );
		$this->write( $data );
		$this->end();
	}

	/** Add a file from the disk as it is. */
	public function add_file( $name, $path ) {
		$size  = (int) filesize( $path );
		$entry = array( 'name' => $name, 'offset' => $this->offset, 'flags' => 0x0800, 'method' => 0, 'crc' => (int) hexdec( (string) hash_file( 'crc32b', $path ) ), 'csize' => $size, 'size' => $size );
		$this->out( $this->header( $entry ) );
		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		while ( $handle && ! feof( $handle ) ) {
			$chunk = (string) fread( $handle, 1048576 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$this->out( $chunk );
			flush();
		}
		if ( $handle ) { fclose( $handle ); } // phpcs:ignore WordPress.WP.AlternativeFunctions
		$this->entries[] = $entry;
	}

	/** Write the table of contents. Nothing can be added afterwards. */
	public function finish() {
		$start = $this->offset;
		foreach ( $this->entries as $entry ) {
			$this->out( pack( 'VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, $entry['flags'], $entry['method'], $this->time, $this->date, $entry['crc'], $entry['csize'], $entry['size'], strlen( $entry['name'] ), 0, 0, 0, 0, 0, $entry['offset'] ) . $entry['name'] );
		}
		$this->out( pack( 'VvvvvVVv', 0x06054b50, 0, 0, count( $this->entries ), count( $this->entries ), $this->offset - $start, $start, 0 ) );
	}
}

/** The portal's own tables, named without the prefix. */
function mags_erp_backup_tables() {
	return array_keys( mags_erp_schema() );
}

/** Columns of the primary key of a table, from its definition, to read it in a fixed order. */
function mags_erp_backup_order( $name ) {
	return preg_match( '/PRIMARY KEY\s+\(([^)]+)\)/', mags_erp_schema()[ $name ], $match ) ? implode( ', ', array_map( 'trim', explode( ',', $match[1] ) ) ) : '1';
}

/** Every row of a table, a chunk at a time, to $each( row ). */
function mags_erp_backup_rows( $name, $each ) {
	global $wpdb;
	$table = mags_erp_table( $name );
	$order = mags_erp_backup_order( $name );
	for ( $offset = 0; ; $offset += MAGS_ERP_BACKUP_CHUNK ) {
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY {$order} LIMIT %d OFFSET %d", MAGS_ERP_BACKUP_CHUNK, $offset ), ARRAY_A );
		foreach ( $rows as $row ) { $each( $row ); }
		if ( count( $rows ) < MAGS_ERP_BACKUP_CHUNK ) { return; }
	}
}

/** Column names of a table (also when it is empty). */
function mags_erp_backup_columns( $name ) {
	$columns = array();
	foreach ( preg_split( '/,\s*\n/', mags_erp_schema()[ $name ] ) as $line ) {
		if ( preg_match( '/^\s*([a-z_]+)\s+(bigint|int|tinyint|varchar|char|text|longtext|date|datetime|decimal)/', $line, $match ) ) { $columns[] = $match[1]; }
	}
	return $columns;
}

/** One line of CSV text. */
function mags_erp_backup_csv_line( $cells ) {
	$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $handle, array_map( 'mags_erp_csv_cell', array_map( static function ( $cell ) { return null === $cell ? '' : (string) $cell; }, $cells ) ), ',', '"', '' );
	rewind( $handle );
	$line = (string) stream_get_contents( $handle );
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $line;
}

/** A value as SQL text, for the SQL file. */
function mags_erp_backup_sql_value( $value ) {
	if ( null === $value ) { return 'NULL'; }
	return "'" . strtr( (string) $value, array( '\\' => '\\\\', "\0" => '\\0', "\n" => '\\n', "\r" => '\\r', "'" => "\\'", '"' => '\\"', "\x1a" => '\\Z' ) ) . "'";
}

/** Number and total size of the uploaded files the backup can include. */
function mags_erp_backup_files_size() {
	global $wpdb;
	$row = $wpdb->get_row( 'SELECT COUNT(*) AS files, COALESCE(SUM(size), 0) AS bytes FROM ' . mags_erp_table( 'files' ) );
	return array( (int) ( $row->files ?? 0 ), (int) ( $row->bytes ?? 0 ) );
}

/** The last backups downloaded: list of array( at, by, files ). */
function mags_erp_backup_log() {
	return array_slice( (array) get_option( 'mags_erp_backup_log', array() ), 0, 20 );
}

/** Write the whole backup ZIP to the browser and stop. */
function mags_erp_send_backup( $with_files ) {
	global $wpdb;
	@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	while ( ob_get_level() ) { ob_end_clean(); }
	$stamp = current_time( 'Y-m-d-His' );
	$user  = wp_get_current_user();
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="mags-erp-backup-' . $stamp . '.zip"' );

	$zip    = new MAGS_ERP_Zip();
	$counts = array();
	$zip->add_string(
		'README.txt',
		implode(
			"\r\n",
			array(
				'MAGS ERP portal backup',
				'School: ' . mags_erp_school_name(),
				'Made: ' . current_time( 'j F Y, g:i a' ) . ' by ' . $user->display_name . ' (' . $user->user_login . ')',
				'Portal version: ' . MAGS_ERP_VERSION . '. Database table prefix: ' . $wpdb->prefix,
				'',
				'tables/      Every portal table as a CSV file. Open with Excel or Google Sheets.',
				'             A cell that would start a formula (= + - @) begins with an apostrophe.',
				'database.sql The same tables as SQL, for the site administrator. It creates the',
				'             tables if they are missing and inserts the rows. Load it into the',
				'             database of an empty portal with the same table prefix (phpMyAdmin,',
				'             Adminer or "wp db import").',
				'portal-logins.csv  The portal accounts (students, teachers, office) without passwords.',
				'             After loading a backup into a new site, give new passwords from the portal.',
				'settings.json      The portal settings.',
				$with_files ? 'files/       The uploaded files under their stored names; tables/files.csv gives the real name of each.' : 'The uploaded files are not in this backup (the box was not ticked).',
				'',
				'This file holds personal details of children and parents. Keep it safe:',
				'on a protected computer or drive, never on a shared or public place.',
			)
		) . "\r\n"
	);

	foreach ( mags_erp_backup_tables() as $name ) {
		$columns = mags_erp_backup_columns( $name );
		$zip->begin( 'tables/' . $name . '.csv' );
		$zip->write( "\xEF\xBB\xBF" . mags_erp_backup_csv_line( $columns ) );
		$counts[ $name ] = 0;
		mags_erp_backup_rows(
			$name,
			static function ( $row ) use ( $zip, $columns, &$counts, $name ) {
				$cells = array();
				foreach ( $columns as $column ) { $cells[] = $row[ $column ] ?? null; }
				$zip->write( mags_erp_backup_csv_line( $cells ) );
				$counts[ $name ]++;
			}
		);
		$zip->end();
	}

	$zip->begin( 'database.sql' );
	$zip->write( "-- MAGS ERP portal backup, " . current_time( 'Y-m-d H:i' ) . "\n-- Tables use the prefix {$wpdb->prefix}. Load into an empty portal with the same prefix.\nSET NAMES utf8mb4;\n\n" );
	foreach ( mags_erp_backup_tables() as $name ) {
		$table   = mags_erp_table( $name );
		$columns = mags_erp_backup_columns( $name );
		$zip->write( "-- {$name}: {$counts[ $name ]} rows\nCREATE TABLE IF NOT EXISTS `{$table}` (\n\t" . trim( mags_erp_schema()[ $name ] ) . "\n) DEFAULT CHARSET=utf8mb4;\n" );
		$batch = array();
		$flush = static function () use ( &$batch, $zip, $table, $columns ) {
			if ( ! $batch ) { return; }
			$zip->write( "INSERT INTO `{$table}` (`" . implode( '`, `', $columns ) . "`) VALUES\n" . implode( ",\n", $batch ) . ";\n" );
			$batch = array();
		};
		mags_erp_backup_rows(
			$name,
			static function ( $row ) use ( &$batch, $columns, $flush ) {
				$values = array();
				foreach ( $columns as $column ) { $values[] = mags_erp_backup_sql_value( $row[ $column ] ?? null ); }
				$batch[] = '(' . implode( ', ', $values ) . ')';
				if ( count( $batch ) >= 200 ) { $flush(); }
			}
		);
		$flush();
		$zip->write( "\n" );
	}
	$zip->end();

	$zip->begin( 'portal-logins.csv' );
	$zip->write( "\xEF\xBB\xBF" . mags_erp_backup_csv_line( array( 'User ID', 'Username', 'Name', 'Email', 'Role', 'Removed', 'Created' ) ) );
	foreach ( get_users( array( 'role__in' => array( 'mags_student', 'mags_teacher', 'mags_office' ), 'orderby' => 'ID' ) ) as $account ) {
		$zip->write( mags_erp_backup_csv_line( array( $account->ID, $account->user_login, $account->display_name, $account->user_email, implode( ' ', (array) $account->roles ), (string) get_user_meta( $account->ID, 'mags_erp_removed', true ), $account->user_registered ) ) );
	}
	$zip->end();

	$settings = array();
	foreach ( array( 'mags_erp_version', 'mags_erp_settings', 'mags_erp_admissions', 'mags_erp_former_staff' ) as $option ) { $settings[ $option ] = get_option( $option ); }
	$settings['school'] = mags_erp_school_details();
	$zip->add_string( 'settings.json', (string) wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

	if ( $with_files ) {
		$dir = mags_erp_private_dir();
		mags_erp_backup_rows(
			'files',
			static function ( $row ) use ( $zip, $dir ) {
				$path = $dir . '/' . basename( (string) $row['stored_name'] );
				if ( is_file( $path ) && is_readable( $path ) ) { $zip->add_file( 'files/' . basename( (string) $row['stored_name'] ), $path ); }
			}
		);
	}
	$zip->finish();
	exit;
}

/* ---------- Office action ---------- */

/** Check the password, write the log and send the ZIP. */
function mags_erp_action_download_backup() {
	$back     = mags_erp_url( 'backup' );
	$user     = wp_get_current_user();
	$password = isset( $_POST['confirm_password'] ) && is_string( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : ''; // phpcs:ignore WordPress.Security
	if ( '' === $password || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		mags_erp_flash( 'error', 'The password is not correct. The backup was not made.' );
		return $back;
	}
	$with_files = (bool) mags_erp_post_int( 'with_files' );
	if ( $with_files ) {
		list( $count, $bytes ) = mags_erp_backup_files_size();
		if ( $bytes > 3.9 * GB_IN_BYTES || $count > 60000 ) {
			mags_erp_flash( 'error', 'The uploaded files are too big for one backup file (more than 3.9 GB). Download the backup without the files, and ask the site administrator to copy the folder wp-content/uploads/mags-erp-private.' );
			return $back;
		}
	}
	$log = (array) get_option( 'mags_erp_backup_log', array() );
	array_unshift( $log, array( 'at' => current_time( 'mysql' ), 'by' => (int) $user->ID, 'files' => $with_files ) );
	update_option( 'mags_erp_backup_log', array_slice( $log, 0, 20 ), false );
	mags_erp_send_backup( $with_files );
}
