<?php
/**
 * Fees. The office writes down what each student has to pay (fees) and what
 * was received at the school (payments). A student sees their own fees, the
 * payments, and how much is left. Nothing is paid through the portal.
 *
 * A wrong entry is cancelled, not deleted: it stays in the list, struck out,
 * and is no longer counted.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** How a payment reached the school. Recorded for the office only. */
function mags_erp_payment_modes() {
	return array( 'cash' => 'Cash', 'cheque' => 'Cheque', 'upi' => 'UPI', 'bank' => 'Bank transfer', 'card' => 'Card', 'other' => 'Other' );
}

/** Receipt number shown for a payment, e.g. R-00042. */
function mags_erp_receipt_no( $payment ) {
	return 'R-' . str_pad( (string) (int) $payment->id, 5, '0', STR_PAD_LEFT );
}

/** Why a fee is made smaller. Shown to the office and the student. */
function mags_erp_discount_reasons() {
	return array( 'scholarship' => 'Scholarship', 'sibling' => 'Brother / sister', 'staff' => 'Staff child', 'other' => 'Other' );
}

/**
 * Fees and late fines of one student, oldest due date first. Each row carries
 * ->discounts (its discount rows), ->discount (their sum) and ->net (what is to
 * be paid for it). Cancelled rows are included only for the office.
 */
function mags_erp_student_fees( $student_id, $with_cancelled = false ) {
	global $wpdb;
	$rows      = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'fees' ) . ' WHERE student_id = %d' . ( $with_cancelled ? '' : ' AND hidden_at IS NULL' ) . ' ORDER BY COALESCE(due_date, DATE(created_at)), id', $student_id ) );
	$fees      = array();
	$discounts = array();
	foreach ( $rows as $row ) {
		if ( 'discount' === $row->kind ) { $discounts[ (int) $row->parent_id ][] = $row; } else { $fees[] = $row; }
	}
	foreach ( $fees as $fee ) {
		$fee->discounts = $discounts[ (int) $fee->id ] ?? array();
		$fee->discount  = 0.0;
		foreach ( $fee->discounts as $discount ) { if ( ! $discount->hidden_at ) { $fee->discount += -1 * (float) $discount->amount; } }
		$fee->discount = round( $fee->discount, 2 );
		$fee->net      = round( max( 0.0, (float) $fee->amount - $fee->discount ), 2 );
	}
	return $fees;
}

/** Fees before discounts, discounts and late fines of one student that are counted now. */
function mags_erp_fee_breakdown( $student_id ) {
	global $wpdb;
	$sums = array( 'fee' => 0.0, 'discount' => 0.0, 'fine' => 0.0 );
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT kind, SUM(amount) AS total FROM ' . mags_erp_table( 'fees' ) . ' WHERE student_id = %d AND hidden_at IS NULL GROUP BY kind', $student_id ) ) as $row ) {
		if ( isset( $sums[ $row->kind ] ) ) { $sums[ $row->kind ] = round( abs( (float) $row->total ), 2 ); }
	}
	return $sums;
}

/** Payments of one student, newest first. */
function mags_erp_student_payments( $student_id, $with_cancelled = false ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'fee_payments' ) . ' WHERE student_id = %d' . ( $with_cancelled ? '' : ' AND hidden_at IS NULL' ) . ' ORDER BY paid_on DESC, id DESC', $student_id ) );
}

function mags_erp_get_fee( $fee_id ) {
	global $wpdb;
	return $fee_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'fees' ) . ' WHERE id = %d', $fee_id ) ) : null;
}

function mags_erp_get_payment( $payment_id ) {
	global $wpdb;
	return $payment_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mags_erp_table( 'fee_payments' ) . ' WHERE id = %d', $payment_id ) ) : null;
}

/**
 * student_id => array( charged, paid ) for the given students (all students
 * when null). "charged" is fees plus late fines minus discounts (a discount is
 * stored as a minus amount). Cancelled fees and payments are not counted.
 */
function mags_erp_fee_totals( $student_ids = null ) {
	global $wpdb;
	if ( is_array( $student_ids ) && ! $student_ids ) { return array(); }
	$only   = is_array( $student_ids ) ? ' AND student_id IN (' . mags_erp_id_list( $student_ids ) . ')' : '';
	$totals = array();
	foreach ( array( 'fees' => 'charged', 'fee_payments' => 'paid' ) as $table => $key ) {
		foreach ( (array) $wpdb->get_results( 'SELECT student_id, SUM(amount) AS total FROM ' . mags_erp_table( $table ) . " WHERE hidden_at IS NULL{$only} GROUP BY student_id" ) as $row ) {
			$totals[ (int) $row->student_id ] = ( $totals[ (int) $row->student_id ] ?? array( 'charged' => 0.0, 'paid' => 0.0 ) );
			$totals[ (int) $row->student_id ][ $key ] = round( (float) $row->total, 2 );
		}
	}
	return $totals;
}

/** array( charged, paid, left ) for one student. "left" is negative when the student has paid in advance. */
function mags_erp_fee_summary( $student_id ) {
	$totals = mags_erp_fee_totals( array( $student_id ) )[ (int) $student_id ] ?? array( 'charged' => 0.0, 'paid' => 0.0 );
	return array( 'charged' => $totals['charged'], 'paid' => $totals['paid'], 'left' => round( $totals['charged'] - $totals['paid'], 2 ) );
}

/** Totals for the whole school (students who are in a class now). */
function mags_erp_school_fee_summary() {
	global $wpdb;
	$sum = array( 'charged' => 0.0, 'paid' => 0.0 );
	foreach ( array( 'fees' => 'charged', 'fee_payments' => 'paid' ) as $table => $key ) {
		$sum[ $key ] = round( (float) $wpdb->get_var( 'SELECT SUM(f.amount) FROM ' . mags_erp_table( $table ) . ' f INNER JOIN ' . mags_erp_table( 'students' ) . ' s ON s.id = f.student_id WHERE f.hidden_at IS NULL AND s.hidden_at IS NULL' ), 2 );
	}
	$sum['left'] = round( $sum['charged'] - $sum['paid'], 2 );
	return $sum;
}

/**
 * Work out how much of each fee is paid. Payments are counted against the
 * oldest fee first, after its discounts. Sets ->paid_amount and ->state (paid, part, due, overdue)
 * on every fee that is not cancelled; $fees must be in due-date order.
 */
function mags_erp_allocate_payments( $fees, $paid_total ) {
	$money = round( (float) $paid_total, 2 );
	$today = mags_erp_today();
	foreach ( $fees as $fee ) {
		if ( $fee->hidden_at ) { $fee->paid_amount = 0.0; $fee->state = 'cancelled'; continue; }
		$net              = isset( $fee->net ) ? (float) $fee->net : (float) $fee->amount;
		$fee->paid_amount = round( min( $net, max( 0.0, $money ) ), 2 );
		$money            = round( $money - $fee->paid_amount, 2 );
		if ( $fee->paid_amount >= $net - 0.004 ) {
			$fee->state = 'paid';
		} elseif ( $fee->due_date && $fee->due_date < $today ) {
			$fee->state = 'overdue';
		} else {
			$fee->state = $fee->paid_amount > 0 ? 'part' : 'due';
		}
	}
	return $fees;
}

/** state => array( label, chip class ). */
function mags_erp_fee_states() {
	return array(
		'paid'      => array( 'Paid', 'erp-chip--green' ),
		'part'      => array( 'Part paid', 'erp-chip--blue' ),
		'due'       => array( 'To pay', 'erp-chip--orange' ),
		'overdue'   => array( 'Overdue', 'erp-chip--status-A' ),
		'cancelled' => array( 'Cancelled', '' ),
	);
}

function mags_erp_validate_fee( $title, $amount, $due_date ) {
	if ( '' === $title ) { return new WP_Error( 'mags_erp_fee', 'Enter what the fee is for, such as Tuition fee April–June.' ); }
	if ( mb_strlen( $title ) > 120 ) { return new WP_Error( 'mags_erp_fee', 'The fee name is too long.' ); }
	if ( is_wp_error( $amount ) ) { return $amount; }
	if ( '' !== $due_date && ! mags_erp_valid_date( $due_date ) ) { return new WP_Error( 'mags_erp_fee', 'The due date is not a valid date.' ); }
	return true;
}

/** Write one fee for each of the students. Fees added together share a group key so they can be cancelled together. */
function mags_erp_add_fee( $student_ids, $title, $amount, $due_date ) {
	global $wpdb;
	$group = count( $student_ids ) > 1 ? wp_generate_password( 12, false ) : '';
	$now   = current_time( 'mysql' );
	$added = 0;
	foreach ( $student_ids as $student_id ) {
		$added += (int) $wpdb->insert(
			mags_erp_table( 'fees' ),
			array( 'student_id' => (int) $student_id, 'title' => $title, 'amount' => $amount, 'due_date' => $due_date ?: null, 'group_key' => $group, 'created_by' => get_current_user_id(), 'created_at' => $now ),
			array( '%d', '%s', '%f', '%s', '%s', '%d', '%s' )
		);
	}
	return $added;
}

/* ---------- Office actions ---------- */

/** Add a fee for one student (student_id) or for every student of a class (class_id). */
function mags_erp_action_save_fee() {
	$student_id = mags_erp_post_int( 'student_id' );
	$class_id   = mags_erp_post_int( 'class_id' );
	$title      = mags_erp_post_text( 'title' );
	$amount     = mags_erp_parse_money( mags_erp_post_text( 'amount' ) );
	$due_date   = mags_erp_post_text( 'due_date' );
	$back       = $student_id ? mags_erp_url( 'fees', array( 'student' => $student_id ) ) : mags_erp_url( 'fees', array( 'class' => $class_id ?: '' ) );

	if ( $student_id ) {
		$student = mags_erp_get_student( $student_id );
		if ( ! $student ) { mags_erp_flash( 'error', 'That student no longer exists.' ); return mags_erp_url( 'fees' ); }
		$targets = array( (int) $student->id );
		$who     = $student->full_name;
	} else {
		$class = mags_erp_get_class( $class_id );
		if ( ! $class ) { mags_erp_flash( 'error', 'Choose the class.' ); return $back; }
		$targets = array_map( 'intval', wp_list_pluck( mags_erp_class_students( $class_id ), 'id' ) );
		$who     = sprintf( _n( '%1$d student of %2$s', '%1$d students of %2$s', count( $targets ), 'mags-erp' ), count( $targets ), mags_erp_class_label( $class ) );
		if ( ! $targets ) { mags_erp_flash( 'error', 'This class has no students yet.' ); return $back; }
	}
	$valid = mags_erp_validate_fee( $title, $amount, $due_date );
	if ( is_wp_error( $valid ) ) { mags_erp_flash( 'error', $valid->get_error_message() ); return $back; }

	$added = mags_erp_add_fee( $targets, $title, $amount, $due_date );
	if ( $added !== count( $targets ) ) { mags_erp_flash( 'error', sprintf( 'Only %d of %d fees could be saved. Please check the list and add the missing ones.', $added, count( $targets ) ) ); return $back; }
	mags_erp_flash( 'success', sprintf( 'Fee added: %s, %s, for %s.', $title, mags_erp_money( $amount ), $who ) );
	return $back;
}

function mags_erp_action_save_payment() {
	global $wpdb;
	$student   = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
	$amount    = mags_erp_parse_money( mags_erp_post_text( 'amount' ) );
	$paid_on   = mags_erp_post_text( 'paid_on' );
	$mode      = mags_erp_post_text( 'mode' );
	$reference = mags_erp_post_text( 'reference' );
	$note      = mags_erp_post_text( 'note' );
	if ( ! $student ) { mags_erp_flash( 'error', 'That student no longer exists.' ); return mags_erp_url( 'fees' ); }
	$back = mags_erp_url( 'fees', array( 'student' => (int) $student->id ) );

	if ( is_wp_error( $amount ) ) { mags_erp_flash( 'error', $amount->get_error_message() ); return $back; }
	if ( ! mags_erp_valid_date( $paid_on ) || $paid_on > mags_erp_today() ) { mags_erp_flash( 'error', 'Choose the day the payment was received: today or an earlier date.' ); return $back; }
	if ( ! isset( mags_erp_payment_modes()[ $mode ] ) ) { mags_erp_flash( 'error', 'Choose how the payment was made.' ); return $back; }
	if ( mb_strlen( $reference ) > 60 || mb_strlen( $note ) > 200 ) { mags_erp_flash( 'error', 'The reference or the note is too long.' ); return $back; }

	$saved = $wpdb->insert(
		mags_erp_table( 'fee_payments' ),
		array( 'student_id' => (int) $student->id, 'amount' => $amount, 'paid_on' => $paid_on, 'mode' => $mode, 'reference' => $reference, 'note' => $note, 'recorded_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%f', '%s', '%s', '%s', '%s', '%d', '%s' )
	);
	if ( ! $saved ) { mags_erp_flash( 'error', 'The payment could not be saved. Please try again.' ); return $back; }
	$summary = mags_erp_fee_summary( (int) $student->id );
	mags_erp_flash( 'success', sprintf( 'Payment of %s recorded for %s. Receipt %s. %s', mags_erp_money( $amount ), $student->full_name, mags_erp_receipt_no( (object) array( 'id' => $wpdb->insert_id ) ), $summary['left'] > 0 ? 'Left to pay: ' . mags_erp_money( $summary['left'] ) . '.' : ( $summary['left'] < 0 ? 'Paid in advance: ' . mags_erp_money( -$summary['left'] ) . '.' : 'Nothing is left to pay.' ) ) );
	return $back;
}

/**
 * Give a discount on one fee of one student. The amount may be typed in rupees
 * ("500") or as a part of the fee ("10%"). All discounts of a fee together
 * cannot be more than the fee.
 */
function mags_erp_action_save_discount() {
	global $wpdb;
	$fee = mags_erp_get_fee( mags_erp_post_int( 'fee_id' ) );
	if ( ! $fee || 'fee' !== $fee->kind ) { mags_erp_flash( 'error', 'A discount is given on a fee. That fee no longer exists.' ); return mags_erp_url( 'fees' ); }
	$back    = mags_erp_url( 'fees', array( 'student' => (int) $fee->student_id ) ) . '#erp-fee-' . (int) $fee->id;
	$reasons = mags_erp_discount_reasons();
	$reason  = mags_erp_post_text( 'reason' );
	$typed   = str_replace( ' ', '', mags_erp_post_text( 'amount' ) );
	$note    = mags_erp_post_text( 'note' );
	if ( $fee->hidden_at ) { mags_erp_flash( 'error', 'This fee is cancelled. Count it again before giving a discount.' ); return $back; }
	if ( ! isset( $reasons[ $reason ] ) ) { mags_erp_flash( 'error', 'Choose what the discount is for.' ); return $back; }
	if ( mb_strlen( $note ) > 80 ) { mags_erp_flash( 'error', 'The note is too long (80 characters at most).' ); return $back; }

	if ( preg_match( '/^(\d{1,3}(?:\.\d{1,2})?)%$/', $typed, $match ) ) {
		$amount = (float) $match[1] > 0 && (float) $match[1] <= 100 ? round( (float) $fee->amount * (float) $match[1] / 100, 2 ) : new WP_Error( 'mags_erp_money', 'A discount in per cent must be above 0 and not more than 100.' );
	} else {
		$amount = mags_erp_parse_money( $typed );
	}
	if ( is_wp_error( $amount ) ) { mags_erp_flash( 'error', 'Enter the discount in rupees (500) or in per cent (10%).' ); return $back; }

	$given = -1 * (float) $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(amount) FROM ' . mags_erp_table( 'fees' ) . " WHERE parent_id = %d AND kind = 'discount' AND hidden_at IS NULL", $fee->id ) );
	$room  = round( (float) $fee->amount - $given, 2 );
	if ( $amount > $room + 0.004 ) { mags_erp_flash( 'error', sprintf( 'The discount cannot be more than the fee. At most %s can still be taken off.', mags_erp_money( max( 0, $room ) ) ) ); return $back; }

	$title = $reasons[ $reason ] . ( '' !== $note ? ': ' . $note : '' );
	$saved = $wpdb->insert(
		mags_erp_table( 'fees' ),
		array( 'student_id' => (int) $fee->student_id, 'title' => $title, 'amount' => -1 * $amount, 'kind' => 'discount', 'parent_id' => (int) $fee->id, 'reason' => $reason, 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%s', '%f', '%s', '%d', '%s', '%d', '%s' )
	);
	mags_erp_flash( $saved ? 'success' : 'error', $saved ? sprintf( 'Discount of %1$s (%2$s) given on “%3$s”.', mags_erp_money( $amount ), $title, $fee->title ) : 'The discount could not be saved. Please try again.' );
	return $back;
}

/** Add a late fine for a fee whose last date has passed. The fine is one more amount the student has to pay. */
function mags_erp_action_save_fine() {
	global $wpdb;
	$fee = mags_erp_get_fee( mags_erp_post_int( 'fee_id' ) );
	if ( ! $fee || 'fee' !== $fee->kind ) { mags_erp_flash( 'error', 'A late fine is added to a fee. That fee no longer exists.' ); return mags_erp_url( 'fees' ); }
	$back   = mags_erp_url( 'fees', array( 'student' => (int) $fee->student_id ) ) . '#erp-fee-' . (int) $fee->id;
	$amount = mags_erp_parse_money( mags_erp_post_text( 'amount' ) );
	$note   = mags_erp_post_text( 'note' );
	if ( $fee->hidden_at ) { mags_erp_flash( 'error', 'This fee is cancelled.' ); return $back; }
	if ( ! $fee->due_date || $fee->due_date >= mags_erp_today() ) { mags_erp_flash( 'error', 'A late fine can be added only after the last date of the fee has passed.' ); return $back; }
	if ( is_wp_error( $amount ) ) { mags_erp_flash( 'error', $amount->get_error_message() ); return $back; }
	if ( mb_strlen( $note ) > 80 ) { mags_erp_flash( 'error', 'The note is too long (80 characters at most).' ); return $back; }

	$title = mb_substr( 'Late fine: ' . $fee->title . ( '' !== $note ? ' (' . $note . ')' : '' ), 0, 120 );
	$saved = $wpdb->insert(
		mags_erp_table( 'fees' ),
		array( 'student_id' => (int) $fee->student_id, 'title' => $title, 'amount' => $amount, 'kind' => 'fine', 'parent_id' => (int) $fee->id, 'reason' => 'late', 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ),
		array( '%d', '%s', '%f', '%s', '%d', '%s', '%d', '%s' )
	);
	mags_erp_flash( $saved ? 'success' : 'error', $saved ? sprintf( 'Late fine of %1$s added for “%2$s”.', mags_erp_money( $amount ), $fee->title ) : 'The fine could not be saved. Please try again.' );
	return $back;
}

/**
 * Cancel a fee, a late fine or a discount (or count it again). Cancelling a
 * fee also cancels its discounts and late fines; counting it again brings back
 * the ones that were cancelled with it. With whole_group a class fee is
 * cancelled for every student it was added for together; counting again then
 * brings back only the fees cancelled in that same step.
 */
function mags_erp_action_cancel_fee() {
	global $wpdb;
	$fee = mags_erp_get_fee( mags_erp_post_int( 'fee_id' ) );
	if ( ! $fee ) { mags_erp_flash( 'error', 'That fee no longer exists.' ); return mags_erp_url( 'fees' ); }
	$back    = mags_erp_url( 'fees', array( 'student' => (int) $fee->student_id ) );
	$restore = (bool) mags_erp_post_int( 'restore' );
	$table   = mags_erp_table( 'fees' );
	$labels  = array( 'fee' => 'Fee', 'fine' => 'Late fine', 'discount' => 'Discount' );
	$label   = $labels[ $fee->kind ] ?? 'Fee';

	if ( $restore && ! $fee->hidden_at ) { mags_erp_flash( 'error', 'That is already counted.' ); return $back; }
	if ( ! $restore && $fee->hidden_at ) { mags_erp_flash( 'error', 'That is already cancelled.' ); return $back; }
	if ( $restore && (int) $fee->parent_id ) {
		$parent = mags_erp_get_fee( (int) $fee->parent_id );
		if ( ! $parent || $parent->hidden_at ) { mags_erp_flash( 'error', 'The fee this belongs to is cancelled. Count that fee again first.' ); return $back; }
		if ( 'discount' === $fee->kind ) {
			$given = -1 * (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table} WHERE parent_id = %d AND kind = 'discount' AND hidden_at IS NULL", $parent->id ) );
			if ( $given - (float) $fee->amount > (float) $parent->amount + 0.004 ) { mags_erp_flash( 'error', 'With this discount the discounts would be more than the fee.' ); return $back; }
		}
	}

	$ids = array( (int) $fee->id );
	if ( mags_erp_post_int( 'whole_group' ) && '' !== $fee->group_key ) {
		$ids = $restore
			? (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE group_key = %s AND hidden_at = %s", $fee->group_key, $fee->hidden_at ) )
			: (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE group_key = %s AND hidden_at IS NULL", $fee->group_key ) );
	}
	$list = mags_erp_id_list( $ids );
	if ( $restore ) {
		$changed = (int) $wpdb->query( "UPDATE {$table} SET hidden_at = NULL, hidden_by = 0 WHERE id IN ({$list}) AND hidden_at IS NOT NULL" );
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hidden_at = NULL, hidden_by = 0 WHERE parent_id IN ({$list}) AND hidden_at = %s", $fee->hidden_at ) );
	} else {
		$now     = current_time( 'mysql' );
		$changed = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hidden_at = %s, hidden_by = %d WHERE id IN ({$list}) AND hidden_at IS NULL", $now, get_current_user_id() ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hidden_at = %s, hidden_by = %d WHERE parent_id IN ({$list}) AND hidden_at IS NULL", $now, get_current_user_id() ) );
	}
	if ( ! $changed ) { mags_erp_flash( 'error', 'Nothing was changed.' ); return $back; }
	$for = $changed > 1 ? sprintf( ' for %d students', $changed ) : '';
	mags_erp_flash( 'success', sprintf( $restore ? '%1$s “%2$s” is counted again%3$s.' : '%1$s “%2$s” cancelled%3$s. It is kept in the list but no longer counted.', $label, $fee->title, $for ) );
	return $back;
}

function mags_erp_action_cancel_payment() {
	$payment = mags_erp_get_payment( mags_erp_post_int( 'payment_id' ) );
	if ( ! $payment ) { mags_erp_flash( 'error', 'That payment no longer exists.' ); return mags_erp_url( 'fees' ); }
	$restore = (bool) mags_erp_post_int( 'restore' );
	if ( $restore ) { mags_erp_unhide( 'fee_payments', (int) $payment->id ); } else { mags_erp_hide( 'fee_payments', (int) $payment->id ); }
	mags_erp_flash( 'success', sprintf( $restore ? 'Payment %s is counted again.' : 'Payment %s cancelled. It is kept in the list but no longer counted.', mags_erp_receipt_no( $payment ) ) );
	return mags_erp_url( 'fees', array( 'student' => (int) $payment->student_id ) );
}
