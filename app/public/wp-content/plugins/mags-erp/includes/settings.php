<?php
/**
 * Portal settings the office changes under More → Settings: how many wrong
 * passwords lock a login and for how long (on the portal and on wp-login.php
 * alike), and the largest file that can be uploaded. The settings screen also
 * shows whether the private upload folder is closed to direct downloads.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Each setting: key => array( default, lowest, highest ). */
function mags_erp_setting_limits() {
	return array(
		'login_tries'  => array( MAGS_ERP_LOGIN_TRIES, 3, 20 ),
		'lock_minutes' => array( MAGS_ERP_LOGIN_WINDOW / MINUTE_IN_SECONDS, 1, 1440 ),
		'max_file_mb'  => array( 10, 1, 100 ),
	);
}

/** One setting as saved by the office, or its default; always inside its limits. */
function mags_erp_setting( $key ) {
	$limits = mags_erp_setting_limits()[ $key ];
	$saved  = (array) get_option( 'mags_erp_settings', array() );
	return max( $limits[1], min( $limits[2], isset( $saved[ $key ] ) ? (int) $saved[ $key ] : (int) $limits[0] ) );
}

/** Wrong passwords for one login ID from one network address before it is locked. */
function mags_erp_login_tries() {
	return mags_erp_setting( 'login_tries' );
}

/** How long a lock lasts, in seconds. */
function mags_erp_login_window() {
	return mags_erp_setting( 'lock_minutes' ) * MINUTE_IN_SECONDS;
}

/** "15 minutes", "1 hour" or "1 hour 30 minutes". */
function mags_erp_minutes_text( $minutes ) {
	$minutes = (int) $minutes;
	$hours   = intdiv( $minutes, 60 );
	$rest    = $minutes % 60;
	$parts   = array();
	if ( $hours ) { $parts[] = sprintf( _n( '%d hour', '%d hours', $hours, 'mags-erp' ), $hours ); }
	if ( $rest || ! $hours ) { $parts[] = sprintf( _n( '%d minute', '%d minutes', $rest, 'mags-erp' ), $rest ); }
	return implode( ' ', $parts );
}

/** The rule for nginx that closes the private folder, with this site's upload path. */
function mags_erp_nginx_rule() {
	$upload = wp_upload_dir( null, false );
	$path   = trailingslashit( (string) wp_parse_url( $upload['baseurl'], PHP_URL_PATH ) ) . 'mags-erp-private/';
	return "# MAGS ERP: never hand out the private upload folder directly.\nlocation ^~ {$path} {\n\tdeny all;\n}";
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_settings() {
	$back   = mags_erp_url( 'settings' );
	$labels = array( 'login_tries' => 'Wrong passwords before the lock', 'lock_minutes' => 'Lock time', 'max_file_mb' => 'Largest file' );
	$values = array();
	foreach ( mags_erp_setting_limits() as $key => $limits ) {
		$typed = mags_erp_post_text( $key );
		if ( ! preg_match( '/^\d{1,5}$/', $typed ) || (int) $typed < $limits[1] || (int) $typed > $limits[2] ) {
			mags_erp_flash( 'error', sprintf( '%1$s must be a whole number from %2$d to %3$d. Nothing was saved.', $labels[ $key ], $limits[1], $limits[2] ) );
			return $back;
		}
		$values[ $key ] = (int) $typed;
	}
	// The same option keeps the email switches (includes/notify.php); they stay as they are.
	update_option( 'mags_erp_settings', array_merge( (array) get_option( 'mags_erp_settings', array() ), $values ), true );

	$server = wp_max_upload_size();
	$note   = $server && $values['max_file_mb'] * MB_IN_BYTES > $server ? ' ' . sprintf( 'This server takes files up to %s, so that is the limit for now. The site administrator can raise upload_max_filesize and post_max_size in PHP.', size_format( $server ) ) : '';
	mags_erp_flash( 'success', sprintf( 'Settings saved. A login is locked for %1$s after %2$d wrong passwords; files can be up to %3$s.', mags_erp_minutes_text( $values['lock_minutes'] ), $values['login_tries'], size_format( mags_erp_max_file_size() ) ) . $note );
	return $back;
}

/** Test again now whether the private folder can be opened directly. */
function mags_erp_action_check_private_files() {
	$exposed = mags_erp_private_files_exposed( true );
	if ( true === $exposed ) {
		mags_erp_flash( 'error', 'The private files can still be opened directly. Add the nginx rule below to the site and restart the site.' );
	} elseif ( false === $exposed ) {
		mags_erp_flash( 'success', 'The private files are closed: the web server refuses to hand them out directly.' );
	} else {
		mags_erp_flash( 'error', 'The test could not reach the website from the server, so it is not known. Check by hand as described below.' );
	}
	return mags_erp_url( 'settings' ) . '#erp-private-files';
}
