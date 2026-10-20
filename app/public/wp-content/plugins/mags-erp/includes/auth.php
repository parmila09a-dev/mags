<?php
/** Portal sign-in, sign-out, the lock after wrong passwords (also on wp-login.php) and keeping portal accounts out of the WordPress Dashboard. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Defaults of the lock; the office changes both under More → Settings (mags_erp_login_tries(), mags_erp_login_window()). */
define( 'MAGS_ERP_LOGIN_TRIES', 5 );
define( 'MAGS_ERP_LOGIN_WINDOW', 15 * MINUTE_IN_SECONDS );

function mags_erp_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
}

/**
 * Failed sign-ins are counted per login ID + network address (5 tries unless
 * the office set another number), and per network address (20 times as many,
 * so a whole school on one connection is not locked out). The portal form and
 * wp-login.php count together.
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
	return (int) get_transient( $keys['login'] ) >= mags_erp_login_tries() || (int) get_transient( $keys['ip'] ) >= mags_erp_login_tries() * 20;
}

function mags_erp_login_failed( $login ) {
	foreach ( mags_erp_login_keys( $login ) as $key ) {
		set_transient( $key, (int) get_transient( $key ) + 1, mags_erp_login_window() );
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
		$account    = $student && ! $student->hidden_at ? get_userdata( (int) $student->user_id ) : false;
		$user_login = $account ? $account->user_login : '';
	}

	// The portal counts its own tries (by admission number for students), so the wp-login.php lock stands aside here.
	mags_erp_portal_signing_in( true );
	$user = '' !== $user_login ? wp_signon( array( 'user_login' => $user_login, 'user_password' => $password, 'remember' => ! empty( $_POST['mags_erp_remember'] ) ) ) : new WP_Error( 'mags_erp_login', 'Unknown login.' );
	mags_erp_portal_signing_in( false );
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
		'locked'  => sprintf( 'Too many wrong attempts. Please wait %s and try again, or ask the school office to reset your password.', mags_erp_minutes_text( mags_erp_login_window() / MINUTE_IN_SECONDS ) ),
		'expired' => 'Please sign in again to continue.',
	);
	return $messages[ $code ] ?? '';
}

/* ---------- The same lock on wp-login.php ---------- */

/** True while the portal's own form checks a password. $set switches it on or off. */
function mags_erp_portal_signing_in( $set = null ) {
	static $active = false;
	if ( null !== $set ) { $active = (bool) $set; }
	return $active;
}

/**
 * wp-login.php (and every other sign-in with a password, such as application
 * passwords) is locked like the portal: after too many wrong passwords for one
 * username from one network address, even the right password is refused until
 * the lock time has passed.
 */
function mags_erp_wp_login_lock( $user, $username ) {
	if ( mags_erp_portal_signing_in() || ! is_string( $username ) || '' === trim( $username ) ) { return $user; }
	if ( mags_erp_login_is_locked( trim( $username ) ) ) {
		return new WP_Error( 'mags_erp_locked', '<strong>' . esc_html__( 'Error:', 'mags-erp' ) . '</strong> ' . esc_html( mags_erp_login_error_message( 'locked' ) ) );
	}
	return $user;
}
add_filter( 'authenticate', 'mags_erp_wp_login_lock', 30, 2 );

/** Count a wrong password typed on wp-login.php. A try while the login is locked does not make the lock longer. */
function mags_erp_wp_login_failed( $username, $error = null ) {
	if ( mags_erp_portal_signing_in() || ! is_string( $username ) || '' === trim( $username ) ) { return; }
	if ( $error instanceof WP_Error && 'mags_erp_locked' === $error->get_error_code() ) { return; }
	mags_erp_login_failed( trim( $username ) );
}
add_action( 'wp_login_failed', 'mags_erp_wp_login_failed', 10, 2 );

/** A successful sign-in on wp-login.php starts the count again, as on the portal. */
function mags_erp_wp_login_succeeded( $user_login ) {
	if ( mags_erp_portal_signing_in() ) { return; }
	delete_transient( mags_erp_login_keys( (string) $user_login )['login'] );
}
add_action( 'wp_login', 'mags_erp_wp_login_succeeded' );

/**
 * A teacher or student that the office has removed cannot sign in anywhere,
 * neither on the portal nor on wp-login.php. The portal form answers with its
 * usual "not correct" message, so it does not reveal that the ID exists.
 */
function mags_erp_block_removed_login( $user ) {
	if ( $user instanceof WP_User && mags_erp_is_removed_user( $user ) ) { return new WP_Error( 'mags_erp_removed', 'This account is no longer in use. Please contact the school office.' ); }
	return $user;
}
add_filter( 'authenticate', 'mags_erp_block_removed_login', 99 );

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

/**
 * Portal accounts are not website authors. Without this, /?author=… would show
 * a student's or teacher's name (and internal username) to any visitor.
 */
function mags_erp_hide_author_pages() {
	if ( ! is_author() ) { return; }
	$author = get_queried_object();
	if ( $author instanceof WP_User && mags_erp_is_portal_only_user( $author ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'mags_erp_hide_author_pages', 1 );
