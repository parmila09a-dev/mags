<?php
/** Student (and parent): the student's own fees, the payments the office has recorded (each with its receipt to download) and what is left to pay. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}

$erp_summary  = mags_erp_fee_summary( (int) $erp_student->id );
$erp_fees     = mags_erp_allocate_payments( mags_erp_student_fees( (int) $erp_student->id ), $erp_summary['paid'] );
$erp_payments = mags_erp_student_payments( (int) $erp_student->id );
$erp_states   = mags_erp_fee_states();
$erp_modes    = mags_erp_payment_modes();
$erp_sums     = mags_erp_fee_breakdown( (int) $erp_student->id );
?>
<header class="erp-head">
	<p class="erp-eyebrow">My fees</p>
	<h1><?php echo esc_html( $erp_student->full_name ); ?></h1>
</header>

<section class="erp-card" aria-labelledby="erp-fees-summary-title">
	<h2 id="erp-fees-summary-title" class="erp-sr">Summary</h2>
	<dl class="erp-tally erp-tally--money">
		<div class="erp-tally__item erp-tally__item--L"><dt>Fees</dt><dd><?php echo esc_html( mags_erp_money( $erp_summary['charged'] ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--P"><dt>Paid</dt><dd><?php echo esc_html( mags_erp_money( $erp_summary['paid'] ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--<?php echo $erp_summary['left'] > 0 ? 'A' : 'P'; ?>"><dt><?php echo $erp_summary['left'] < 0 ? 'Paid in advance' : 'Left to pay'; ?></dt><dd><?php echo esc_html( mags_erp_money( abs( $erp_summary['left'] ) ) ); ?></dd></div>
	</dl>
	<?php if ( $erp_sums['discount'] > 0 || $erp_sums['fine'] > 0 ) : ?>
		<p class="erp-hint">“Fees” is <?php echo esc_html( mags_erp_money( $erp_sums['fee'] ) ); ?><?php if ( $erp_sums['discount'] > 0 ) : ?> less <?php echo esc_html( mags_erp_money( $erp_sums['discount'] ) ); ?> discount<?php endif; ?><?php if ( $erp_sums['fine'] > 0 ) : ?> plus <?php echo esc_html( mags_erp_money( $erp_sums['fine'] ) ); ?> late fine<?php endif; ?>.</p>
	<?php endif; ?>
	<p class="erp-hint">Fees are paid at the school office. Online payment is not available in the portal. If a payment is missing here, please show the receipt at the office.</p>
</section>

<section aria-labelledby="erp-fee-list-title">
	<h2 id="erp-fee-list-title" class="erp-section-title">Fees <span class="erp-count"><?php echo esc_html( count( $erp_fees ) ); ?></span></h2>
	<?php if ( ! $erp_fees ) : ?>
		<p class="erp-card erp-empty">The school office has not entered any fee yet.</p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_fees as $erp_fee ) : $erp_state = $erp_states[ $erp_fee->state ]; ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_fee->title ); ?></strong>
						<span><?php echo esc_html( mags_erp_money( $erp_fee->net ) ); ?><?php if ( $erp_fee->due_date ) : ?> · pay by <?php echo esc_html( mags_erp_format_date( $erp_fee->due_date, 'j M Y' ) ); ?><?php endif; ?></span>
						<?php foreach ( $erp_fee->discounts as $erp_discount ) : ?><span>Fee <?php echo esc_html( mags_erp_money( $erp_fee->amount ) ); ?> · discount <?php echo esc_html( $erp_discount->title ); ?> <?php echo esc_html( mags_erp_money( $erp_discount->amount ) ); ?></span><?php endforeach; ?>
					</div>
					<div class="erp-row__actions"><?php if ( 'fine' === $erp_fee->kind ) : ?><span class="erp-chip erp-chip--status-A">Late fine</span><?php endif; ?><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span><?php if ( 'paid' !== $erp_fee->state && $erp_fee->paid_amount > 0 ) : ?><span class="erp-chip"><?php echo esc_html( mags_erp_money( $erp_fee->net - $erp_fee->paid_amount ) ); ?> left</span><?php endif; ?></div>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="erp-hint erp-hint--lead">Payments are counted against the oldest fee first.</p>
	<?php endif; ?>
</section>

<section aria-labelledby="erp-pay-list-title">
	<h2 id="erp-pay-list-title" class="erp-section-title">Payments <span class="erp-count"><?php echo esc_html( count( $erp_payments ) ); ?></span></h2>
	<?php if ( ! $erp_payments ) : ?>
		<p class="erp-card erp-empty">No payment has been recorded yet.</p>
	<?php else : ?>
		<p class="erp-hint erp-hint--lead">Each payment has a receipt to download and print, the same as the one the school office gives.</p>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_payments as $erp_payment ) : ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_money( $erp_payment->amount ) ); ?></strong>
						<span><?php echo esc_html( mags_erp_format_date( $erp_payment->paid_on, 'j M Y' ) ); ?> · <?php echo esc_html( $erp_modes[ $erp_payment->mode ] ?? $erp_payment->mode ); ?></span>
					</div>
					<div class="erp-row__actions"><span class="erp-chip">Receipt <?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?></span><a class="erp-btn erp-btn--navy erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'receipt', array( 'id' => (int) $erp_payment->id ) ) ); ?>">Download receipt<span class="erp-sr"> <?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?> (PDF)</span></a></div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
