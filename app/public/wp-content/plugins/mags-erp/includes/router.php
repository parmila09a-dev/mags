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

/**
 * Screens each role can open: route => array( view file, title, navigation label, icon, in the navigation bar ).
 * The bar holds the four screens used most and "More"; the More screen lists every section.
 */
function mags_erp_screens( $role ) {
	$more    = array( 'more', 'All sections', 'More', 'dots', true );
	$account = array( 'account', 'My account', 'Account', 'user', false );
	$screens = array(
		'office'  => array(
			'home'      => array( 'office-home', 'Office', 'Home', 'home', true ),
			'students'  => array( 'office-students', 'Students', 'Students', 'people', true ),
			'fees'      => array( 'office-fees', 'Fees', 'Fees', 'rupee', true ),
			'reports'   => array( 'office-reports', 'Reports', 'Reports', 'chart', true ),
			'more'      => $more,
			'classes'   => array( 'office-classes', 'Classes', 'Classes', 'grid', false ),
			'teachers'  => array( 'office-teachers', 'Teachers', 'Teachers', 'badge', false ),
			'import'    => array( 'office-import', 'Add students from a file', 'Import students', 'upload', false ),
			'subjects'  => array( 'office-subjects', 'Subjects and periods', 'Subjects', 'layers', false ),
			'timetable' => array( 'office-timetable', 'Timetable', 'Timetable', 'clock', false ),
			'exams'     => array( 'office-exams', 'Exams', 'Exams', 'award', false ),
			'questions' => array( 'office-questions', 'Questions', 'Questions', 'chat', false ),
			'calendar'  => array( 'office-calendar', 'School calendar', 'Calendar', 'calendar', false ),
			'profile'      => array( 'office-profile', 'Student profiles', 'Profiles', 'idcard', false ),
			'certificates' => array( 'office-certificates', 'Certificates', 'Certificates', 'stamp', false ),
			'admissions'   => array( 'office-admissions', 'Admissions', 'Admissions', 'inbox', false ),
			'staff'        => array( 'office-staff', 'Teacher attendance', 'Teacher attendance', 'check', false ),
			'staff-leave'  => array( 'office-staff-leave', 'Teacher leave', 'Teacher leave', 'leave', false ),
			'account'   => $account,
		),
		'teacher' => array(
			'home'       => array( 'teacher-home', 'My classes', 'Home', 'home', true ),
			'attendance' => array( 'teacher-attendance', 'Mark attendance', 'Attendance', 'check', true ),
			'homework'   => array( 'teacher-homework', 'Homework', 'Homework', 'book', true ),
			'timetable'  => array( 'teacher-timetable', 'My timetable', 'Timetable', 'clock', true ),
			'more'       => $more,
			'notes'      => array( 'teacher-notes', 'Notes', 'Notes', 'file', false ),
			'notices'    => array( 'teacher-notices', 'Class notices', 'Notices', 'megaphone', false ),
			'marks'      => array( 'teacher-marks', 'Exam marks', 'Marks', 'award', false ),
			'questions'  => array( 'teacher-questions', 'Questions', 'Questions', 'chat', false ),
			'leave'      => array( 'teacher-leave', 'Leave requests', 'Leave', 'leave', false ),
			'calendar'   => array( 'calendar', 'School calendar', 'Calendar', 'calendar', false ),
			'my-attendance' => array( 'teacher-my-attendance', 'My attendance and leave', 'My attendance', 'badge', false ),
			'account'    => $account,
		),
		'student' => array(
			'home'      => array( 'student-home', 'My attendance', 'Attendance', 'check', true ),
			'homework'  => array( 'student-homework', 'My homework', 'Homework', 'book', true ),
			'timetable' => array( 'student-timetable', 'My timetable', 'Timetable', 'clock', true ),
			'notices'   => array( 'student-notices', 'Class notices', 'Notices', 'megaphone', true ),
			'more'      => $more,
			'notes'     => array( 'student-notes', 'Class notes', 'Notes', 'file', false ),
			'marks'     => array( 'student-marks', 'My marks', 'Marks', 'award', false ),
			'questions' => array( 'student-questions', 'My questions', 'Questions', 'chat', false ),
			'leave'     => array( 'student-leave', 'Leave', 'Leave', 'leave', false ),
			'fees'      => array( 'student-fees', 'My fees', 'Fees', 'rupee', false ),
			'calendar'  => array( 'calendar', 'School calendar', 'Calendar', 'calendar', false ),
			'profile'   => array( 'student-profile', 'My profile', 'Profile', 'idcard', false ),
			'account'   => $account,
		),
	);
	return $screens[ $role ] ?? array();
}

/** route => number of things that wait for this person, shown as a small count in the menu. */
function mags_erp_badges( $role, $user_id ) {
	if ( 'office' === $role ) { return array_filter( array( 'admissions' => mags_erp_admission_counts()['new'], 'staff-leave' => mags_erp_pending_staff_leave_count() ) ); }
	if ( 'teacher' !== $role ) { return array(); }
	$to_check = 0;
	foreach ( mags_erp_classes_homework( mags_erp_teacher_class_ids( $user_id ) ) as $homework ) { $to_check += (int) $homework->submitted_count - (int) $homework->checked_count; }
	return array_filter( array( 'homework' => $to_check, 'questions' => mags_erp_waiting_questions_count( $user_id ), 'leave' => mags_erp_pending_leave_count( $user_id ) ) );
}

function mags_erp_icon( $name ) {
	$paths = array(
		'home'      => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/>',
		'grid'      => '<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/>',
		'badge'     => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c1-4 4-6 7.5-6s6.5 2 7.5 6"/>',
		'people'    => '<circle cx="9" cy="8.5" r="3.5"/><path d="M2.5 20c.8-3.6 3.3-5.5 6.5-5.5s5.7 1.9 6.5 5.5"/><path d="M16 5.200a3.5 3.5 0 0 1 0 6.6"/><path d="M18 14.800c2 .7 3.2 2.5 3.6 5.2"/>',
		'check'     => '<rect x="3.5" y="4.5" width="17" height="16" rx="3.5"/><path d="M8 2.500v4M16 2.500v4"/><path d="m8.5 13 2.5 2.5 4.5-5"/>',
		'user'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.5 18.500c1.2-2.3 3.2-3.5 5.5-3.500s4.3 1.2 5.5 3.5"/>',
		'logout'    => '<path d="M14 4.500h3.500a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H14"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9.5"/>',
		'book'      => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"/><path d="M9 8h7M9 11.5h5"/>',
		'file'      => '<path d="M6 3.5h8l5 5v12H6z"/><path d="M14 3.5v5h5"/><path d="M9 13h6M9 16.5h6"/>',
		'clip'      => '<path d="m20 11.5-7.8 7.8a5 5 0 0 1-7-7L13 4.5a3.3 3.3 0 0 1 4.7 4.7L9.9 17a1.6 1.6 0 0 1-2.3-2.3L15 7.3"/>',
		'dots'      => '<circle cx="5" cy="12" r="1.800"/><circle cx="12" cy="12" r="1.800"/><circle cx="19" cy="12" r="1.800"/>',
		'clock'     => '<circle cx="12" cy="12" r="8.500"/><path d="M12 7.500V12l3 2"/>',
		'award'     => '<circle cx="12" cy="9" r="5.500"/><path d="m8.500 13.500-1.500 7 5-2.500 5 2.500-1.500-7"/>',
		'megaphone' => '<path d="M4 10v4h3l8 4.500v-13L7 10z"/><path d="M18 9.500a3.500 3.500 0 0 1 0 5"/><path d="M8 14v4.500h2.500"/>',
		'chat'      => '<path d="M4.500 5.500h15v10.500h-8l-4.500 3.500v-3.500h-2.500z"/><path d="M8.500 9.500h7M8.500 12.500h4.500"/>',
		'leave'     => '<rect x="3.500" y="4.500" width="17" height="16" rx="3.500"/><path d="M8 2.500v4M16 2.500v4"/><path d="m9.500 11 5 5M14.500 11l-5 5"/>',
		'rupee'     => '<path d="M7 5h10M7 9h10"/><path d="M7 5h3.500a4 4 0 0 1 0 8H7l7 6.500"/>',
		'calendar'  => '<rect x="3.500" y="4.500" width="17" height="16" rx="3.500"/><path d="M8 2.500v4M16 2.500v4"/><path d="M3.500 10h17"/><path d="M8 14h.010M12 14h.010M16 14h.010M8 17.500h.010M12 17.500h.010"/>',
		'idcard'    => '<rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="8.500" cy="11" r="2"/><path d="M5.500 16c.500-1.500 1.600-2.200 3-2.200s2.500.700 3 2.200"/><path d="M14 10h4M14 13.500h4"/>',
		'stamp'     => '<path d="M9 13.500V11a3 3 0 1 1 6 0v2.500"/><rect x="5" y="13.500" width="14" height="4" rx="1.500"/><path d="M5 20.500h14"/>',
		'inbox'     => '<path d="M4 13.500 6.500 5h11L20 13.500V19H4z"/><path d="M4 13.500h4.500l1 2.500h5l1-2.500H20"/>',
		'chart'     => '<path d="M4 20V5"/><path d="M4 20h16.500"/><path d="M8.500 16.500v-5M13 16.500V8M17.500 16.500v-7"/>',
		'upload'    => '<path d="M12 15.500V4.500"/><path d="m7.500 8.500 4.500-4.500 4.500 4.500"/><path d="M4.500 15v4.500h15V15"/>',
		'layers'    => '<path d="m12 3.500 8.500 4.500-8.500 4.500-8.500-4.500z"/><path d="m3.500 12 8.500 4.500 8.500-4.500"/><path d="m3.500 16 8.500 4.500 8.500-4.500"/>',
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
	// The admission form is the one screen that needs no login; it handles its own post.
	if ( 'admission' === $route ) {
		mags_erp_admission_screen();
	}
	mags_erp_handle_post( $route );

	// A removed teacher or student who is still signed in on some device is signed out here.
	if ( is_user_logged_in() && mags_erp_is_removed_user( wp_get_current_user() ) ) {
		wp_logout();
		wp_safe_redirect( mags_erp_url( 'login' ) );
		exit;
	}

	$ctx = array( 'route' => $route, 'role' => '', 'screens' => array(), 'badges' => array(), 'user' => wp_get_current_user(), 'title' => 'Sign in', 'flash' => null );
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
	if ( 'report-card' === $route ) {
		mags_erp_serve_report_card();
	}
	if ( 'export' === $route && 'office' === $ctx['role'] ) {
		mags_erp_export();
	}
	// Documents the office downloads; each checks the role itself.
	$downloads = array( 'certificate' => 'mags_erp_serve_certificate', 'receipt' => 'mags_erp_serve_receipt', 'id-card' => 'mags_erp_serve_id_card' );
	if ( isset( $downloads[ $route ] ) ) {
		call_user_func( $downloads[ $route ] );
	}
	if ( ! isset( $ctx['screens'][ $route ] ) ) {
		wp_safe_redirect( mags_erp_url() );
		exit;
	}

	$ctx['title']  = $ctx['screens'][ $route ][1];
	$ctx['badges'] = mags_erp_badges( $ctx['role'], $ctx['user']->ID );
	$ctx['flash']  = mags_erp_take_flash();
	mags_erp_render( $ctx['screens'][ $route ][0], $ctx );
}
add_action( 'template_redirect', 'mags_erp_dispatch', 0 );

/** Shortcut from the Plugins screen. */
function mags_erp_plugin_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( mags_erp_url() ) . '">Open portal</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( MAGS_ERP_PATH . 'mags-erp.php' ), 'mags_erp_plugin_links' );
