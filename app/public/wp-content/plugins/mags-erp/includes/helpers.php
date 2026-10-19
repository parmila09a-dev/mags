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
	if ( mags_erp_is_removed_user( $user ) ) { return ''; }
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

/**
 * True for a teacher or student login that the office has removed. The account
 * and everything it wrote are kept, but it can no longer sign in or open anything.
 */
function mags_erp_is_removed_user( $user ) {
	$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );
	return $user && mags_erp_is_portal_only_user( $user ) && '' !== (string) get_user_meta( $user->ID, 'mags_erp_removed', true );
}

/** Switch a portal login off (or on again) and sign it out everywhere. */
function mags_erp_set_user_removed( $user_id, $removed ) {
	if ( $removed ) {
		update_user_meta( $user_id, 'mags_erp_removed', current_time( 'mysql' ) );
		WP_Session_Tokens::get_instance( $user_id )->destroy_all();
	} else {
		delete_user_meta( $user_id, 'mags_erp_removed' );
	}
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

/** A file-upload field with the allowed types and size limit spelled out. $multiple lets several files be chosen at once. */
function mags_erp_file_input( $name, $label, $required = false, $hint = '', $multiple = false ) {
	$accept = array();
	foreach ( array_keys( mags_erp_allowed_mimes() ) as $ext ) { foreach ( explode( '|', $ext ) as $one ) { $accept[] = '.' . $one; } }
	?>
	<p class="erp-field erp-field--file">
		<label for="erp_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?><?php if ( ! $required ) : ?> <span>(optional)</span><?php endif; ?></label>
		<input id="erp_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name . ( $multiple ? '[]' : '' ) ); ?>" type="file" accept="<?php echo esc_attr( implode( ',', $accept ) ); ?>" <?php echo $required ? 'required' : ''; ?> <?php echo $multiple ? 'multiple' : ''; ?>>
		<small><?php echo esc_html( ( $hint ? $hint . ' ' : '' ) . mags_erp_allowed_extensions_label() . ', up to ' . size_format( mags_erp_max_file_size() ) . ( $multiple ? ' each.' : '.' ) ); ?></small>
	</p>
	<?php
}

/** Download link for a stored file (its id or its row), or nothing when the file is missing or removed. */
function mags_erp_file_link( $file_id, $class = 'erp-filelink' ) {
	$file = is_object( $file_id ) ? $file_id : mags_erp_get_file( $file_id );
	if ( ! $file ) { return; }
	?><a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( mags_erp_file_url( $file->id ) ); ?>"><?php echo mags_erp_icon( 'clip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $file->original_name ); ?></span><small><?php echo esc_html( size_format( (int) $file->size, 1 ) ); ?></small></a><?php
}

/* ---------- Request values ---------- */

/** Whole number from the address bar, e.g. ?class=3. Names avoid WordPress query variables (day, year, name, …). */
function mags_erp_get_int( $key ) {
	return isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ? absint( $_GET[ $key ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
}

function mags_erp_get_text( $key ) {
	return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

/* ---------- Hide instead of delete ---------- */

/**
 * Remove a record from the portal without deleting it: the row stays in the
 * database with the date and the person who removed it, and can be restored.
 */
function mags_erp_hide( $table, $id ) {
	global $wpdb;
	return (bool) $wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( $table ) . ' SET hidden_at = %s, hidden_by = %d WHERE id = %d AND hidden_at IS NULL', current_time( 'mysql' ), get_current_user_id(), $id ) );
}

function mags_erp_unhide( $table, $id ) {
	global $wpdb;
	return (bool) $wpdb->query( $wpdb->prepare( 'UPDATE ' . mags_erp_table( $table ) . ' SET hidden_at = NULL, hidden_by = 0 WHERE id = %d AND hidden_at IS NOT NULL', $id ) );
}

/**
 * The "Removed" drawer under a list. $items is a list of array( id, title, detail );
 * each row gets a Restore button that posts $action with $field = id.
 */
function mags_erp_removed_list( $items, $action, $field, $route, $label = 'Removed' ) {
	if ( ! $items ) { return; }
	?>
	<details class="erp-removed">
		<summary class="erp-btn erp-btn--ghost erp-btn--sm"><?php echo esc_html( $label ); ?> <span class="erp-count"><?php echo esc_html( count( $items ) ); ?></span></summary>
		<p class="erp-hint">Nothing is deleted. Removed items are kept here and can be put back.</p>
		<ul class="erp-list">
			<?php foreach ( $items as $item ) : ?>
				<li class="erp-row erp-row--off">
					<div class="erp-row__main"><strong><?php echo esc_html( $item[1] ); ?></strong><?php if ( '' !== (string) $item[2] ) : ?><span><?php echo esc_html( $item[2] ); ?></span><?php endif; ?></div>
					<div class="erp-row__actions">
						<form method="post" action="<?php echo esc_url( mags_erp_url( $route ) ); ?>">
							<?php mags_erp_form_fields( $action ); ?>
							<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $item[0] ); ?>"><input type="hidden" name="restore" value="1">
							<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">Restore<span class="erp-sr"> <?php echo esc_html( $item[1] ); ?></span></button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</details>
	<?php
}

/* ---------- Small pieces shared by the screens ---------- */

/** School days shown in the timetable: 1 = Monday … 7 = Sunday. */
function mags_erp_days() {
	$names = array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' );
	$days  = array_map( 'absint', (array) apply_filters( 'mags_erp_school_days', array( 1, 2, 3, 4, 5, 6 ) ) );
	return array_intersect_key( $names, array_flip( $days ) ) ?: $names;
}

/** Today's day number (1 = Monday … 7 = Sunday) in the school's time zone. */
function mags_erp_today_dow() {
	return (int) current_time( 'N' );
}

function mags_erp_valid_time( $time ) {
	return is_string( $time ) && (bool) preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time );
}

/** "08:30" as "8:30 am". */
function mags_erp_format_time( $time ) {
	return mags_erp_valid_time( $time ) ? gmdate( 'g:i a', strtotime( '1970-01-01 ' . $time . ':00 UTC' ) ) : '';
}

/** Rupee amount for people: ₹1,250 or ₹1,250.50. */
function mags_erp_money( $amount ) {
	$amount = round( (float) $amount, 2 );
	$text   = number_format( abs( $amount ), abs( $amount - round( $amount ) ) < 0.005 ? 0 : 2, '.', ',' );
	return ( $amount < 0 ? '−' : '' ) . '₹' . $text;
}

/** Amount typed by the office: a number above 0 with up to two decimals. Returns float or WP_Error. */
function mags_erp_parse_money( $text ) {
	$text = str_replace( array( ',', '₹', ' ' ), '', trim( (string) $text ) );
	if ( ! preg_match( '/^\d{1,7}(\.\d{1,2})?$/', $text ) || (float) $text <= 0 ) { return new WP_Error( 'mags_erp_money', 'Enter the amount as a number above 0, for example 4500 or 4500.50.' ); }
	return (float) $text;
}

/** <option> list of classes. $classes is a list of class rows. */
function mags_erp_class_options( $classes, $selected = 0, $placeholder = 'Choose class' ) {
	if ( '' !== $placeholder ) { echo '<option value="">' . esc_html( $placeholder ) . '</option>'; }
	foreach ( $classes as $class ) {
		echo '<option value="' . esc_attr( $class->id ) . '"' . selected( (int) $selected, (int) $class->id, false ) . '>' . esc_html( mags_erp_class_label( $class ) ) . '</option>';
	}
}

/** Row of links that looks like the segmented choice: $tabs is url => label, $current the open url. */
function mags_erp_tabs( $tabs, $current, $label ) {
	echo '<nav class="erp-seg erp-seg--tabs" aria-label="' . esc_attr( $label ) . '">';
	foreach ( $tabs as $url => $text ) {
		echo '<a class="erp-seg__item" href="' . esc_url( $url ) . '"' . ( $url === $current ? ' aria-current="true"' : '' ) . '><span>' . esc_html( $text ) . '</span></a>';
	}
	echo '</nav>';
}

/** Hidden field that keeps the screen name in GET forms when pretty permalinks are off. */
function mags_erp_route_field( $route ) {
	if ( ! get_option( 'permalink_structure' ) ) { echo '<input type="hidden" name="mags_erp" value="' . esc_attr( $route ) . '">'; }
}

/** The message every student screen shows when the login has no student record. */
function mags_erp_no_student_record() {
	echo '<section class="erp-card erp-empty"><h1>Student record not found</h1><p>Please contact the school office.</p></section>';
}

/* ---------- School details, words and phone numbers ---------- */

/** Name, postal address, telephone and email of the school, each on one line ('' when not set under MAGS Core). */
function mags_erp_school_details() {
	$get = static function ( $key ) {
		$value = function_exists( 'mags_core_option' ) ? (string) mags_core_option( $key, '' ) : '';
		return trim( preg_replace( '/\s+/', ' ', preg_replace( '/\s*[\r\n]+\s*/', ', ', trim( $value ) ) ) );
	};
	return array( 'name' => mags_erp_school_name(), 'address' => $get( 'address' ), 'phone' => $get( 'phone' ), 'email' => $get( 'email' ) );
}

/** A whole number in words, the Indian way: 125000 is "One Lakh Twenty Five Thousand". */
function mags_erp_number_words( $number ) {
	$number = (int) $number;
	if ( $number <= 0 ) { return 'Zero'; }
	$ones = array( '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen' );
	$tens = array( '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety' );
	$two  = static function ( $n ) use ( $ones, $tens ) { return $n < 20 ? $ones[ $n ] : trim( $tens[ intdiv( $n, 10 ) ] . ' ' . $ones[ $n % 10 ] ); };

	$parts = array();
	$crore = intdiv( $number, 10000000 );
	$rest  = $number % 10000000;
	if ( $crore ) { $parts[] = mags_erp_number_words( $crore ) . ' Crore'; }
	if ( intdiv( $rest, 100000 ) ) { $parts[] = $two( intdiv( $rest, 100000 ) ) . ' Lakh'; }
	$rest %= 100000;
	if ( intdiv( $rest, 1000 ) ) { $parts[] = $two( intdiv( $rest, 1000 ) ) . ' Thousand'; }
	$rest %= 1000;
	if ( intdiv( $rest, 100 ) ) { $parts[] = $ones[ intdiv( $rest, 100 ) ] . ' Hundred'; }
	if ( $rest % 100 ) { $parts[] = ( $parts ? 'and ' : '' ) . $two( $rest % 100 ); }
	return implode( ' ', $parts );
}

/** "Rupees Four Thousand Five Hundred and Fifty Paise Only" for a receipt. */
function mags_erp_amount_words( $amount ) {
	$paise  = (int) round( abs( (float) $amount ) * 100 );
	$rupees = intdiv( $paise, 100 );
	return 'Rupees ' . mags_erp_number_words( $rupees ) . ( $paise % 100 ? ' and ' . mags_erp_number_words( $paise % 100 ) . ' Paise' : '' ) . ' Only';
}

/** A date in words for a transfer certificate: "Fifteen August Two Thousand and Twelve". */
function mags_erp_date_words( $date ) {
	if ( ! mags_erp_valid_date( $date ) ) { return ''; }
	return mags_erp_number_words( (int) substr( $date, 8, 2 ) ) . ' ' . mags_erp_format_date( $date, 'F' ) . ' ' . mags_erp_number_words( (int) substr( $date, 0, 4 ) );
}

/** Amount for a PDF, whose built-in fonts have no rupee sign: "Rs. 4,500" or "Rs. 4,500.50". */
function mags_erp_money_plain( $amount ) {
	$amount = round( (float) $amount, 2 );
	return ( $amount < 0 ? '- ' : '' ) . 'Rs. ' . number_format( abs( $amount ), abs( $amount - round( $amount ) ) < 0.005 ? 0 : 2, '.', ',' );
}

/** A phone number as typed, cleaned to digits with an optional +. Returns '' when it is not 10 to 15 digits. */
function mags_erp_clean_phone( $text ) {
	$phone = preg_replace( '/[\s().-]+/', '', (string) $text );
	return preg_match( '/^\+?[0-9]{10,15}$/', $phone ) ? $phone : '';
}

function mags_erp_blood_groups() {
	return array( 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' );
}

function mags_erp_genders() {
	return array( 'boy' => 'Boy', 'girl' => 'Girl', 'other' => 'Other' );
}

/** Date typed in a form: '' stays '', a valid date is returned, anything else is a WP_Error. $latest is the last day allowed. */
function mags_erp_posted_date( $key, $label, $latest = '' ) {
	$date = mags_erp_post_text( $key );
	if ( '' === $date ) { return ''; }
	if ( ! mags_erp_valid_date( $date ) || $date < '1900-01-01' ) { return new WP_Error( 'mags_erp_date', sprintf( '%s is not a valid date.', $label ) ); }
	if ( '' !== $latest && $date > $latest ) { return new WP_Error( 'mags_erp_date', sprintf( '%s cannot be after %s.', $label, mags_erp_format_date( $latest ) ) ); }
	return $date;
}
