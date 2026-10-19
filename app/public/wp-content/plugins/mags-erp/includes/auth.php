<?php
/** Portal sign-in, sign-out and keeping portal accounts out of the WordPress Dashboard. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MAGS_ERP_LOGIN_TRIES', 5 );
define( 'MAGS_ERP_LOGIN_WINDOW', 15 * MINUTE_IN_SECONDS );

function mags_erp_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
}

/**
 * Failed sign-ins are counted per login ID + network address (5 tries), and per
 * network address (100 tries, so a whole school on one connection is not locked out).
 */
function mags_erp_login_keys( $login ) {
	$ip = mags_erp_client_ip();
	return array(
		'login' => 'mags_erp_try_' . md5( strtolower( $login ) . '|' . $ip ),
		'ip'    => 'mags_erp_tryip_' . md5( $ip ),
	);
}

function mags_erp_login_is_locked( $login ) {
	$keys = mags_erp_login_keys( $login );
	return (int) get_transient( $keys['login'] ) >= MAGS_ERP_LOGIN_TRIES || (int) get_transient( $keys['ip'] ) >= MAGS_ERP_LOGIN_TRIES * 20;
}

function mags_erp_login_failed( $login ) {
	foreach ( mags_erp_login_keys( $login ) as $key ) {
		set_transient( $key, (int) get_transient( $key ) + 1, MAGS_ERP_LOGIN_WINDOW );
	}
}

/**
 * Sign in from the portal form. Students (and their parents) type the admission
 * number; teachers and the office type their username. The same message is
 * shown for every wrong combination so it never reveals which IDs exist.
 */
function mags_erp_handle_login() {
	$as = isset( $_POST['mags_erp_as'] ) ? sanitize_key( wp_unslash( $_POST['mags_erp_as'] ) ) : 'student';
	$as = isset( mags_erp_role_labels()[ $as ] ) ? $as : 'student';
	$login    = isset( $_POST['mags_erp_login'] ) && is_string( $_POST['mags_erp_login'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['mags_erp_login'] ) ) ) : '';
	// Like WordPress itself, the password is checked exactly as submitted.
	$password = isset( $_POST['mags_erp_password'] ) && is_string( $_POST['mags_erp_password'] ) ? $_POST['mags_erp_password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$fail     = static function ( $code ) use ( $as ) {
		wp_safe_redirect( mags_erp_url( 'login', array( 'as' => $as, 'msg' => $code ) ) );
		exit;
	};

	if ( is_user_logged_in() ) { wp_safe_redirect( mags_erp_url() ); exit; }
	if ( '' === $login || '' === $password ) { $fail( 'empty' ); }
	if ( mags_erp_login_is_locked( $login ) ) { $fail( 'locked' ); }

	$user_login = $login;
	if ( 'student' === $as ) {
		$student    = mags_erp_get_student_by_admission( $login );
		$account    = $student ? get_userdata( (int) $student->user_id ) : false;
		$user_login = $account ? $account->user_login : '';
	}

	$user = '' !== $user_login ? wp_signon( array( 'user_login' => $user_login, 'user_password' => $password, 'remember' => ! empty( $_POST['mags_erp_remember'] ) ) ) : new WP_Error( 'mags_erp_login', 'Unknown login.' );
	if ( is_wp_error( $user ) ) {
		mags_erp_login_failed( $login );
		$fail( 'wrong' );
	}

	delete_transient( mags_erp_login_keys( $login )['login'] );
	wp_safe_redirect( mags_erp_url() );
	exit;
}

function mags_erp_login_error_message( $code ) {
	$messages = array(
		'empty'   => 'Enter your login ID and password.',
		'wrong'   => 'The login ID or password is not correct. Please check and try again.',
		'locked'  => 'Too many wrong attempts. Please wait 15 minutes and try again, or ask the school office to reset your password.',
		'expired' => 'Please sign in again to continue.',
	);
	return $messages[ $code ] ?? '';
}

/** Portal accounts have no business in the Dashboard: send them to the portal instead. */
function mags_erp_keep_out_of_dashboard() {
	global $pagenow;
	if ( wp_doing_ajax() || in_array( $pagenow, array( 'admin-ajax.php', 'admin-post.php' ), true ) ) { return; }
	if ( mags_erp_is_portal_only_user() ) {
		wp_safe_redirect( mags_erp_url() );
		exit;
	}
}
add_action( 'admin_init', 'mags_erp_keep_out_of_dashboard', 1 );
add_action( 'admin_page_access_denied', 'mags_erp_keep_out_of_dashboard', 1 );

function mags_erp_hide_admin_bar( $show ) {
	return mags_erp_is_portal_only_user() ? false : $show;
}
add_filter( 'show_admin_bar', 'mags_erp_hide_admin_bar' );

/** Signing in through wp-login.php also lands portal accounts on the portal. */
function mags_erp_login_redirect( $redirect_to, $requested, $user ) {
	return $user instanceof WP_User && mags_erp_is_portal_only_user( $user ) ? mags_erp_url() : $redirect_to;
}
add_filter( 'login_redirect', 'mags_erp_login_redirect', 10, 3 );

/** Portal accounts do not need application passwords (REST/XML-RPC access). */
function mags_erp_disable_app_passwords( $available, $user ) {
	return $user instanceof WP_User && mags_erp_is_portal_only_user( $user ) ? false : $available;
}
add_filter( 'wp_is_application_passwords_available_for_user', 'mags_erp_disable_app_passwords', 10, 2 );
