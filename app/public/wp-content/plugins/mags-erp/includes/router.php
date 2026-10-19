<?php
/** The /portal/ address: rewrite rules, access checks and screen rendering. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mags_erp_add_rewrite_rules() {
	add_rewrite_rule( '^portal/?$', 'index.php?mags_erp=home', 'top' );
	add_rewrite_rule( '^portal/([a-z-]+)/?$', 'index.php?mags_erp=$matches[1]', 'top' );
}
add_action( 'init', 'mags_erp_add_rewrite_rules' );

function mags_erp_query_vars( $vars ) {
	$vars[] = 'mags_erp';
	return $vars;
}
add_filter( 'query_vars', 'mags_erp_query_vars' );

/** Screens each role can open: route => array( view file, title, navigation label, icon ). */
function mags_erp_screens( $role ) {
	$account = array( 'account', 'My account', 'Account', 'user' );
	$screens = array(
		'office'  => array(
			'home'     => array( 'office-home', 'Office', 'Home', 'home' ),
			'classes'  => array( 'office-classes', 'Classes', 'Classes', 'grid' ),
			'teachers' => array( 'office-teachers', 'Teachers', 'Teachers', 'badge' ),
			'students' => array( 'office-students', 'Students', 'Students', 'people' ),
			'account'  => $account,
		),
		'teacher' => array(
			'home'       => array( 'teacher-home', 'My classes', 'Home', 'home' ),
			'attendance' => array( 'teacher-attendance', 'Mark attendance', 'Attendance', 'check' ),
			'homework'   => array( 'teacher-homework', 'Homework', 'Homework', 'book' ),
			'notes'      => array( 'teacher-notes', 'Notes', 'Notes', 'file' ),
			'account'    => $account,
		),
		'student' => array(
			'home'     => array( 'student-home', 'My attendance', 'Attendance', 'check' ),
			'homework' => array( 'student-homework', 'My homework', 'Homework', 'book' ),
			'notes'    => array( 'student-notes', 'Class notes', 'Notes', 'file' ),
			'account'  => $account,
		),
	);
	return $screens[ $role ] ?? array();
}

function mags_erp_icon( $name ) {
	$paths = array(
		'home'   => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/>',
		'grid'   => '<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/>',
		'badge'  => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c1-4 4-6 7.5-6s6.5 2 7.5 6"/>',
		'people' => '<circle cx="9" cy="8.5" r="3.5"/><path d="M2.5 20c.8-3.6 3.3-5.5 6.5-5.5s5.7 1.9 6.5 5.5"/><path d="M16 5.200a3.5 3.5 0 0 1 0 6.6"/><path d="M18 14.800c2 .7 3.2 2.5 3.6 5.2"/>',
		'check'  => '<rect x="3.5" y="4.5" width="17" height="16" rx="3.5"/><path d="M8 2.500v4M16 2.500v4"/><path d="m8.5 13 2.5 2.5 4.5-5"/>',
		'user'   => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.5 18.500c1.2-2.3 3.2-3.5 5.5-3.500s4.3 1.2 5.5 3.5"/>',
		'logout' => '<path d="M14 4.500h3.500a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H14"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9.5"/>',
		'book'   => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"/><path d="M9 8h7M9 11.5h5"/>',
		'file'   => '<path d="M6 3.5h8l5 5v12H6z"/><path d="M14 3.5v5h5"/><path d="M9 13h6M9 16.5h6"/>',
		'clip'   => '<path d="m20 11.5-7.8 7.8a5 5 0 0 1-7-7L13 4.5a3.3 3.3 0 0 1 4.7 4.7L9.9 17a1.6 1.6 0 0 1-2.3-2.3L15 7.3"/>',
	);
	return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Print one portal screen inside the shared header and footer. */
function mags_erp_render( $view, array $ctx ) {
	status_header( 200 );
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
	require MAGS_ERP_PATH . 'views/header.php';
	require MAGS_ERP_PATH . 'views/' . $view . '.php';
	require MAGS_ERP_PATH . 'views/footer.php';
	exit;
}

function mags_erp_dispatch() {
	$route = get_query_var( 'mags_erp' );
	if ( ! is_string( $route ) || '' === $route ) { return; }
	$route = sanitize_key( $route );

	// Portal screens are personal: never cached, never indexed.
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	mags_erp_handle_post( $route );

	$ctx = array( 'route' => $route, 'role' => '', 'screens' => array(), 'user' => wp_get_current_user(), 'title' => 'Sign in', 'flash' => null );
	if ( ! is_user_logged_in() ) {
		if ( 'login' !== $route ) {
			wp_safe_redirect( mags_erp_url( 'login' ) );
			exit;
		}
		mags_erp_render( 'login', $ctx );
	}

	$ctx['role']    = mags_erp_user_role();
	$ctx['screens'] = mags_erp_screens( $ctx['role'] );
	if ( '' === $ctx['role'] ) {
		$ctx['title'] = 'No portal access';
		mags_erp_render( 'no-access', $ctx );
	}
	if ( 'file' === $route ) {
		mags_erp_serve_file( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	if ( ! isset( $ctx['screens'][ $route ] ) ) {
		wp_safe_redirect( mags_erp_url() );
		exit;
	}

	$ctx['title'] = $ctx['screens'][ $route ][1];
	$ctx['flash'] = mags_erp_take_flash();
	mags_erp_render( $ctx['screens'][ $route ][0], $ctx );
}
add_action( 'template_redirect', 'mags_erp_dispatch', 0 );

/** Shortcut from the Plugins screen. */
function mags_erp_plugin_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( mags_erp_url() ) . '">Open portal</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( MAGS_ERP_PATH . 'mags-erp.php' ), 'mags_erp_plugin_links' );
