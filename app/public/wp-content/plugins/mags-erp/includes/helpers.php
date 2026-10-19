<?php
/** Small helpers shared by the portal: URLs, roles, dates and one-time messages. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Address of a portal screen, e.g. mags_erp_url( 'students', array( 'class' => 3 ) ). */
function mags_erp_url( $route = '', $args = array() ) {
	$route = 'home' === $route ? '' : trim( (string) $route, '/' );
	if ( get_option( 'permalink_structure' ) ) {
		$url = home_url( user_trailingslashit( 'portal' . ( $route ? '/' . $route : '' ) ) );
	} else {
		$url = add_query_arg( 'mags_erp', $route ?: 'home', home_url( '/' ) );
	}
	$args = array_filter( (array) $args, static function ( $value ) { return '' !== $value && null !== $value; } );
	return $args ? add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), $url ) : $url;
}

/** Portal role of a user: office, teacher, student or '' when the account has no portal access. */
function mags_erp_user_role( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	if ( ! $user instanceof WP_User || ! $user->exists() ) { return ''; }
	if ( user_can( $user, 'manage_options' ) || user_can( $user, 'mags_erp_office' ) ) { return 'office'; }
	if ( user_can( $user, 'mags_erp_teacher' ) ) { return 'teacher'; }
	if ( user_can( $user, 'mags_erp_student' ) ) { return 'student'; }
	return '';
}

/** True for accounts that exist only for the portal (no website editing rights). */
function mags_erp_is_portal_only_user( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	if ( ! $user instanceof WP_User || ! $user->exists() ) { return false; }
	if ( user_can( $user, 'manage_options' ) || user_can( $user, 'edit_posts' ) ) { return false; }
	return (bool) array_intersect( array( 'mags_student', 'mags_teacher', 'mags_office' ), (array) $user->roles );
}

/** True when the account was created by the portal with exactly this one role. */
function mags_erp_is_managed_user( $user_id, $role ) {
	$user = get_userdata( (int) $user_id );
	return $user && array_values( (array) $user->roles ) === array( $role );
}

function mags_erp_role_labels() {
	return array( 'student' => 'Student', 'teacher' => 'Teacher', 'office' => 'Office' );
}

function mags_erp_statuses() {
	return array( 'P' => 'Present', 'A' => 'Absent', 'L' => 'Leave' );
}

function mags_erp_today() {
	return current_time( 'Y-m-d' );
}

function mags_erp_valid_date( $date ) {
	if ( ! is_string( $date ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) ) { return false; }
	return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
}

/** Format a stored YYYY-MM-DD date for people, e.g. 29 June 2026. */
function mags_erp_format_date( $date, $format = 'j F Y' ) {
	return mags_erp_valid_date( $date ) ? mysql2date( $format, $date . ' 00:00:00' ) : '';
}

/**
 * Academic session (1 April to 31 March) that contains the given date.
 * Attendance percentages are counted inside this range.
 */
function mags_erp_session_range( $date = '' ) {
	$date        = mags_erp_valid_date( $date ) ? $date : mags_erp_today();
	$start_month = min( 12, max( 1, (int) apply_filters( 'mags_erp_session_start_month', 4 ) ) );
	$year        = (int) substr( $date, 0, 4 );
	if ( (int) substr( $date, 5, 2 ) < $start_month ) { $year--; }
	$start = sprintf( '%04d-%02d-01', $year, $start_month );
	$end   = gmdate( 'Y-m-d', strtotime( $start . ' +1 year -1 day' ) );
	$label = 1 === $start_month ? (string) $year : $year . '–' . substr( (string) ( $year + 1 ), -2 );
	return array( 'start' => $start, 'end' => $end, 'label' => $label );
}

function mags_erp_class_label( $class ) {
	if ( ! $class ) { return 'No class'; }
	return '' !== $class->section ? $class->name . ' – ' . $class->section : $class->name;
}

/** Order classes and roll numbers the way people read them: 2 before 10, A before B. */
function mags_erp_natural_sort( array $rows, array $fields ) {
	usort(
		$rows,
		static function ( $a, $b ) use ( $fields ) {
			foreach ( $fields as $field ) {
				$order = strnatcasecmp( (string) $a->$field, (string) $b->$field );
				if ( 0 !== $order ) { return $order; }
			}
			return 0;
		}
	);
	return $rows;
}

/** Attendance percentage from status counts; leave is counted as not present. */
function mags_erp_percentage( array $counts ) {
	$total = array_sum( $counts );
	return $total ? round( ( $counts['P'] ?? 0 ) * 100 / $total, 1 ) : null;
}

/**
 * One-time message shown on the next portal screen of the current user.
 * $details may carry login details that must be displayed once and never stored for long.
 */
function mags_erp_flash( $type, $message, $details = array() ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) { return; }
	set_transient( 'mags_erp_flash_' . $user_id, array( 'type' => $type, 'message' => $message, 'details' => $details ), 5 * MINUTE_IN_SECONDS );
}

function mags_erp_take_flash() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) { return null; }
	$flash = get_transient( 'mags_erp_flash_' . $user_id );
	if ( ! is_array( $flash ) ) { return null; }
	delete_transient( 'mags_erp_flash_' . $user_id );
	return $flash;
}

/** Hidden fields every portal form needs: the action name and its nonce. */
function mags_erp_form_fields( $action ) {
	echo '<input type="hidden" name="mags_erp_action" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( 'mags_erp_' . $action, '_mags_erp_nonce', false );
}

/** School logo for the portal header: Site Icon first, then the theme's diya icon. */
function mags_erp_logo_url() {
	$icon = get_site_icon_url( 192 );
	if ( $icon ) { return $icon; }
	return is_readable( get_theme_file_path( '/assets/images/mags-site-icon.png' ) ) ? get_theme_file_uri( '/assets/images/mags-site-icon.png' ) : '';
}

function mags_erp_school_name() {
	return function_exists( 'mags_core_option' ) ? mags_core_option( 'school_name', get_bloginfo( 'name' ) ) : get_bloginfo( 'name' );
}

/** A file-upload field with the allowed types and size limit spelled out. */
function mags_erp_file_input( $name, $label, $required = false, $hint = '' ) {
	$accept = array();
	foreach ( array_keys( mags_erp_allowed_mimes() ) as $ext ) { foreach ( explode( '|', $ext ) as $one ) { $accept[] = '.' . $one; } }
	?>
	<p class="erp-field erp-field--file">
		<label for="erp_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?><?php if ( ! $required ) : ?> <span>(optional)</span><?php endif; ?></label>
		<input id="erp_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" type="file" accept="<?php echo esc_attr( implode( ',', $accept ) ); ?>" <?php echo $required ? 'required' : ''; ?>>
		<small><?php echo esc_html( ( $hint ? $hint . ' ' : '' ) . mags_erp_allowed_extensions_label() . ', up to ' . size_format( mags_erp_max_file_size() ) . '.' ); ?></small>
	</p>
	<?php
}

/** Download link for a stored file, or nothing when the file is missing. */
function mags_erp_file_link( $file_id, $class = 'erp-filelink' ) {
	$file = mags_erp_get_file( $file_id );
	if ( ! $file ) { return; }
	?><a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( mags_erp_file_url( $file->id ) ); ?>"><?php echo mags_erp_icon( 'clip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $file->original_name ); ?></span><small><?php echo esc_html( size_format( (int) $file->size, 1 ) ); ?></small></a><?php
}
