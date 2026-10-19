<?php
/** Office: write down the fees of a class or a student, discounts and late fines, and the payments received at the school (with a receipt). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes = mags_erp_get_classes();
$erp_labels  = mags_erp_all_class_labels();
$erp_today   = mags_erp_today();
$erp_student = mags_erp_get_int( 'student' ) ? mags_erp_get_student( mags_erp_get_int( 'student' ) ) : null;

if ( $erp_student ) :
	$erp_summary  = mags_erp_fee_summary( (int) $erp_student->id );
	$erp_fees     = mags_erp_allocate_payments( mags_erp_student_fees( (int) $erp_student->id, true ), $erp_summary['paid'] );
	$erp_payments = mags_erp_student_payments( (int) $erp_student->id, true );
	$erp_states   = mags_erp_fee_states();
	$erp_modes    = mags_erp_payment_modes();
	$erp_back     = mags_erp_url( 'fees', array( 'student' => (int) $erp_student->id ) );
	$erp_sums     = mags_erp_fee_breakdown( (int) $erp_student->id );
	$erp_reasons  = mags_erp_discount_reasons();
	?>
	<header class="erp-head">
		<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'fees', array( 'class' => $erp_student->hidden_at ? '' : (int) $erp_student->class_id ) ) ); ?>">← Fees</a></p>
		<h1><?php echo esc_html( $erp_student->full_name ); ?></h1>
		<p class="erp-head__meta"><span class="erp-chip erp-chip--blue"><?php echo esc_html( $erp_labels[ (int) $erp_student->class_id ] ?? 'No class' ); ?></span><span class="erp-chip">Adm. no. <?php echo esc_html( $erp_student->admission_no ); ?></span><?php if ( $erp_student->hidden_at ) : ?><span class="erp-chip erp-chip--status-A">Removed student</span><?php endif; ?></p>
	</header>

	<section class="erp-card" aria-label="Fees summary">
		<dl class="erp-tally erp-tally--money">
			<div class="erp-tally__item erp-tally__item--L"><dt>Fees</dt><dd><?php echo esc_html( mags_erp_money( $erp_summary['charged'] ) ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--P"><dt>Paid</dt><dd><?php echo esc_html( mags_erp_money( $erp_summary['paid'] ) ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--<?php echo $erp_summary['left'] > 0 ? 'A' : 'P'; ?>"><dt><?php echo $erp_summary['left'] < 0 ? 'Paid in advance' : 'Left to pay'; ?></dt><dd><?php echo esc_html( mags_erp_money( abs( $erp_summary['left'] ) ) ); ?></dd></div>
		</dl>
		<?php if ( $erp_sums['discount'] > 0 || $erp_sums['fine'] > 0 ) : ?>
			<p class="erp-hint">“Fees” is <?php echo esc_html( mags_erp_money( $erp_sums['fee'] ) ); ?><?php if ( $erp_sums['discount'] > 0 ) : ?> less <?php echo esc_html( mags_erp_money( $erp_sums['discount'] ) ); ?> discount<?php endif; ?><?php if ( $erp_sums['fine'] > 0 ) : ?> plus <?php echo esc_html( mags_erp_money( $erp_sums['fine'] ) ); ?> late fine<?php endif; ?>.</p>
		<?php endif; ?>
		<p class="erp-card__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'profile', array( 'student' => (int) $erp_student->id ) ) ); ?>">Profile</a></p>
	</section>

	<section class="erp-card" aria-labelledby="erp-pay-title">
		<h2 id="erp-pay-title">Record a payment</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
			<?php mags_erp_form_fields( 'save_payment' ); ?>
			<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_pay_amount">Amount received (₹)</label><input id="erp_pay_amount" name="amount" type="text" inputmode="decimal" maxlength="12" placeholder="<?php echo esc_attr( $erp_summary['left'] > 0 ? number_format( $erp_summary['left'], 2, '.', '' ) : '0' ); ?>" required></p>
				<p class="erp-field"><label for="erp_pay_date">Received on</label><input id="erp_pay_date" name="paid_on" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( $erp_today ); ?>" required></p>
				<p class="erp-field"><label for="erp_pay_mode">Paid by</label><select id="erp_pay_mode" name="mode"><?php foreach ( $erp_modes as $erp_mode => $erp_mode_label ) : ?><option value="<?php echo esc_attr( $erp_mode ); ?>"><?php echo esc_html( $erp_mode_label ); ?></option><?php endforeach; ?></select></p>
				<p class="erp-field"><label for="erp_pay_ref">Cheque / UPI / receipt book number <span>(optional)</span></label><input id="erp_pay_ref" name="reference" type="text" maxlength="60" autocomplete="off"></p>
			</div>
			<p class="erp-field"><label for="erp_pay_note">Note <span>(optional)</span></label><input id="erp_pay_note" name="note" type="text" maxlength="200" autocomplete="off"></p>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Record payment</button></div>
		</form>
		<p class="erp-hint">This only records money received at the school. No money is taken through the portal.</p>
	</section>

	<section aria-labelledby="erp-pay-list-title">
		<h2 id="erp-pay-list-title" class="erp-section-title">Payments <span class="erp-count"><?php echo esc_html( count( $erp_payments ) ); ?></span></h2>
		<?php if ( ! $erp_payments ) : ?>
			<p class="erp-card erp-empty">No payment recorded yet.</p>
		<?php else : ?>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_payments as $erp_payment ) : ?>
					<li class="erp-row<?php echo $erp_payment->hidden_at ? ' erp-row--off' : ''; ?>">
						<div class="erp-row__main">
							<strong><?php echo esc_html( mags_erp_money( $erp_payment->amount ) ); ?> <span class="erp-chip"><?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?></span><?php if ( $erp_payment->hidden_at ) : ?> <span class="erp-chip erp-chip--status-A">Cancelled</span><?php endif; ?></strong>
							<span><?php echo esc_html( mags_erp_format_date( $erp_payment->paid_on, 'j M Y' ) ); ?> · <?php echo esc_html( $erp_modes[ $erp_payment->mode ] ?? $erp_payment->mode ); ?><?php if ( '' !== $erp_payment->reference ) : ?> · <?php echo esc_html( $erp_payment->reference ); ?><?php endif; ?> · recorded by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_payment->recorded_by ) ); ?></span>
							<?php if ( '' !== $erp_payment->note ) : ?><span><?php echo esc_html( $erp_payment->note ); ?></span><?php endif; ?>
						</div>
						<div class="erp-row__actions">
							<?php if ( ! $erp_payment->hidden_at ) : ?><a class="erp-btn erp-btn--navy erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'receipt', array( 'id' => (int) $erp_payment->id ) ) ); ?>">Receipt PDF<span class="erp-sr"> <?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?></span></a><?php endif; ?>
							<form method="post" action="<?php echo esc_url( $erp_back ); ?>"<?php if ( ! $erp_payment->hidden_at ) : ?> data-erp-confirm="<?php echo esc_attr( 'Cancel payment ' . mags_erp_receipt_no( $erp_payment ) . ' of ' . mags_erp_money( $erp_payment->amount ) . '? It stays in the list but is no longer counted.' ); ?>"<?php endif; ?>>
								<?php mags_erp_form_fields( 'cancel_payment' ); ?>
								<input type="hidden" name="payment_id" value="<?php echo esc_attr( $erp_payment->id ); ?>"><input type="hidden" name="restore" value="<?php echo $erp_payment->hidden_at ? 1 : 0; ?>">
								<button class="erp-btn <?php echo $erp_payment->hidden_at ? 'erp-btn--ghost' : 'erp-btn--danger'; ?> erp-btn--sm" type="submit"><?php echo $erp_payment->hidden_at ? 'Count again' : 'Cancel'; ?><span class="erp-sr"> payment <?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?></span></button>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="erp-card" aria-labelledby="erp-fee-form-title">
		<h2 id="erp-fee-form-title">Add a fee for this student</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
			<?php mags_erp_form_fields( 'save_fee' ); ?>
			<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
			<div class="erp-form__grid erp-form__grid--three">
				<p class="erp-field"><label for="erp_fee_title">Fee for</label><input id="erp_fee_title" name="title" type="text" maxlength="120" placeholder="Bus fee April" required></p>
				<p class="erp-field"><label for="erp_fee_amount">Amount (₹)</label><input id="erp_fee_amount" name="amount" type="text" inputmode="decimal" maxlength="12" required></p>
				<p class="erp-field"><label for="erp_fee_due">Pay by <span>(optional)</span></label><input id="erp_fee_due" name="due_date" type="date"></p>
			</div>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--navy" type="submit">Add fee</button></div>
		</form>
	</section>

	<section aria-labelledby="erp-fee-list-title">
		<h2 id="erp-fee-list-title" class="erp-section-title">Fees <span class="erp-count"><?php echo esc_html( count( $erp_fees ) ); ?></span></h2>
		<?php if ( ! $erp_fees ) : ?>
			<p class="erp-card erp-empty">No fee written for this student yet.</p>
		<?php else : ?>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_fees as $erp_fee ) : $erp_state = $erp_states[ $erp_fee->state ]; ?>
					<li id="erp-fee-<?php echo esc_attr( $erp_fee->id ); ?>" class="erp-row<?php echo $erp_fee->hidden_at ? ' erp-row--off' : ''; ?>">
						<div class="erp-row__main">
							<strong><?php echo esc_html( $erp_fee->title ); ?> · <?php echo esc_html( mags_erp_money( $erp_fee->amount ) ); ?><?php if ( 'fine' === $erp_fee->kind ) : ?> <span class="erp-chip erp-chip--status-A">Late fine</span><?php endif; ?></strong>
							<?php if ( $erp_fee->discount > 0 ) : ?><span><b>To pay after discount: <?php echo esc_html( mags_erp_money( $erp_fee->net ) ); ?></b></span><?php endif; ?>
							<span><?php echo esc_html( $erp_fee->due_date ? 'Pay by ' . mags_erp_format_date( $erp_fee->due_date, 'j M Y' ) : 'No last date' ); ?> · added <?php echo esc_html( mags_erp_format_datetime( $erp_fee->created_at, 'j M Y' ) ); ?><?php if ( '' !== $erp_fee->group_key ) : ?> · added for the whole class<?php endif; ?></span>
							<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span><?php if ( 'part' === $erp_fee->state || ( 'overdue' === $erp_fee->state && $erp_fee->paid_amount > 0 ) ) : ?><span class="erp-chip"><?php echo esc_html( mags_erp_money( $erp_fee->paid_amount ) ); ?> paid</span><?php endif; ?></span>
						</div>
						<div class="erp-row__actions">
							<form method="post" action="<?php echo esc_url( $erp_back ); ?>"<?php if ( ! $erp_fee->hidden_at ) : ?> data-erp-confirm="<?php echo esc_attr( 'Cancel the fee “' . $erp_fee->title . '” for ' . $erp_student->full_name . '? It stays in the list but is no longer counted.' ); ?>"<?php endif; ?>>
								<?php mags_erp_form_fields( 'cancel_fee' ); ?>
								<input type="hidden" name="fee_id" value="<?php echo esc_attr( $erp_fee->id ); ?>"><input type="hidden" name="restore" value="<?php echo $erp_fee->hidden_at ? 1 : 0; ?>">
								<button class="erp-btn <?php echo $erp_fee->hidden_at ? 'erp-btn--ghost' : 'erp-btn--danger'; ?> erp-btn--sm" type="submit"><?php echo $erp_fee->hidden_at ? 'Count again' : 'Cancel'; ?><span class="erp-sr"> fee <?php echo esc_html( $erp_fee->title ); ?></span></button>
							</form>
							<?php if ( '' !== $erp_fee->group_key ) : ?>
								<form method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="<?php echo esc_attr( ( $erp_fee->hidden_at ? 'Count the fee “' : 'Cancel the fee “' ) . $erp_fee->title . '” for every student it was added for together?' ); ?>">
									<?php mags_erp_form_fields( 'cancel_fee' ); ?>
									<input type="hidden" name="fee_id" value="<?php echo esc_attr( $erp_fee->id ); ?>"><input type="hidden" name="restore" value="<?php echo $erp_fee->hidden_at ? 1 : 0; ?>"><input type="hidden" name="whole_group" value="1">
									<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit"><?php echo $erp_fee->hidden_at ? 'Count again for whole class' : 'Cancel for whole class'; ?></button>
								</form>
							<?php endif; ?>
						</div>
						<?php if ( 'fee' === $erp_fee->kind && ( $erp_fee->discounts || ! $erp_fee->hidden_at ) ) : ?>
							<div class="erp-row__extra">
								<?php foreach ( $erp_fee->discounts as $erp_discount ) : ?>
									<div class="erp-subrow<?php echo $erp_discount->hidden_at ? ' is-off' : ''; ?>">
										<span><span class="erp-chip erp-chip--green">Discount</span> <strong><?php echo esc_html( $erp_discount->title ); ?> · <?php echo esc_html( mags_erp_money( $erp_discount->amount ) ); ?></strong><?php echo $erp_discount->hidden_at ? ' · cancelled' : ''; ?> <small>given <?php echo esc_html( mags_erp_format_datetime( $erp_discount->created_at, 'j M Y' ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_discount->created_by ) ); ?></small></span>
										<?php if ( ! $erp_fee->hidden_at || ! $erp_discount->hidden_at ) : ?>
											<form method="post" action="<?php echo esc_url( $erp_back ); ?>"<?php if ( ! $erp_discount->hidden_at ) : ?> data-erp-confirm="<?php echo esc_attr( 'Cancel the discount “' . $erp_discount->title . '”? It stays in the list but is no longer counted.' ); ?>"<?php endif; ?>>
												<?php mags_erp_form_fields( 'cancel_fee' ); ?>
												<input type="hidden" name="fee_id" value="<?php echo esc_attr( $erp_discount->id ); ?>"><input type="hidden" name="restore" value="<?php echo $erp_discount->hidden_at ? 1 : 0; ?>">
												<button class="erp-btn <?php echo $erp_discount->hidden_at ? 'erp-btn--ghost' : 'erp-btn--danger'; ?> erp-btn--sm" type="submit"><?php echo $erp_discount->hidden_at ? 'Count again' : 'Cancel'; ?><span class="erp-sr"> discount <?php echo esc_html( $erp_discount->title ); ?></span></button>
											</form>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
								<?php if ( ! $erp_fee->hidden_at ) : ?>
									<details class="erp-upload">
										<summary class="erp-btn erp-btn--ghost erp-btn--sm">Give a discount<span class="erp-sr"> on <?php echo esc_html( $erp_fee->title ); ?></span></summary>
										<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
											<?php mags_erp_form_fields( 'save_discount' ); ?>
											<input type="hidden" name="fee_id" value="<?php echo esc_attr( $erp_fee->id ); ?>">
											<div class="erp-form__grid erp-form__grid--three">
												<p class="erp-field"><label for="erp_d_reason_<?php echo esc_attr( $erp_fee->id ); ?>">Discount for</label><select id="erp_d_reason_<?php echo esc_attr( $erp_fee->id ); ?>" name="reason"><?php foreach ( $erp_reasons as $erp_key => $erp_text ) : ?><option value="<?php echo esc_attr( $erp_key ); ?>"><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select></p>
												<p class="erp-field"><label for="erp_d_amount_<?php echo esc_attr( $erp_fee->id ); ?>">Amount (₹) or per cent</label><input id="erp_d_amount_<?php echo esc_attr( $erp_fee->id ); ?>" name="amount" type="text" maxlength="12" placeholder="500 or 10%" required></p>
												<p class="erp-field"><label for="erp_d_note_<?php echo esc_attr( $erp_fee->id ); ?>">Note <span>(optional)</span></label><input id="erp_d_note_<?php echo esc_attr( $erp_fee->id ); ?>" name="note" type="text" maxlength="80" placeholder="Merit scholarship 2026"></p>
											</div>
											<div class="erp-form__actions"><button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Give discount</button></div>
										</form>
									</details>
									<?php if ( $erp_fee->due_date && $erp_fee->due_date < $erp_today ) : ?>
										<details class="erp-upload">
											<summary class="erp-btn erp-btn--ghost erp-btn--sm">Add late fine<span class="erp-sr"> for <?php echo esc_html( $erp_fee->title ); ?></span></summary>
											<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
												<?php mags_erp_form_fields( 'save_fine' ); ?>
												<input type="hidden" name="fee_id" value="<?php echo esc_attr( $erp_fee->id ); ?>">
												<div class="erp-form__grid">
													<p class="erp-field"><label for="erp_f_amount_<?php echo esc_attr( $erp_fee->id ); ?>">Fine (₹)</label><input id="erp_f_amount_<?php echo esc_attr( $erp_fee->id ); ?>" name="amount" type="text" inputmode="decimal" maxlength="12" placeholder="100" required></p>
													<p class="erp-field"><label for="erp_f_note_<?php echo esc_attr( $erp_fee->id ); ?>">Note <span>(optional)</span></label><input id="erp_f_note_<?php echo esc_attr( $erp_fee->id ); ?>" name="note" type="text" maxlength="80" placeholder="paid 3 weeks late"></p>
												</div>
												<div class="erp-form__actions"><button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Add late fine</button></div>
											</form>
										</details>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="erp-hint erp-hint--lead">Payments are counted against the oldest fee first, after its discounts. A late fine can be added once the last date of a fee has passed; it is one more amount to pay.</p>
		<?php endif; ?>
	</section>
	<?php
	return;
endif;

$erp_class_id = mags_erp_get_int( 'class' );
$erp_class    = mags_erp_get_class( $erp_class_id );
$erp_search   = mags_erp_get_text( 'q' );
$erp_school   = mags_erp_school_fee_summary();
$erp_rows     = array();
if ( $erp_class || '' !== $erp_search ) {
	list( $erp_found ) = mags_erp_query_students( $erp_class ? (int) $erp_class->id : 0, $erp_search, 1, 200 );
	if ( $erp_class ) { $erp_found = mags_erp_natural_sort( $erp_found, array( 'roll_no', 'full_name' ) ); }
	$erp_totals = mags_erp_fee_totals( wp_list_pluck( $erp_found, 'id' ) );
	foreach ( $erp_found as $erp_row ) {
		$erp_own    = $erp_totals[ (int) $erp_row->id ] ?? array( 'charged' => 0.0, 'paid' => 0.0 );
		$erp_rows[] = array( $erp_row, $erp_own['charged'], $erp_own['paid'], round( $erp_own['charged'] - $erp_own['paid'], 2 ) );
	}
}
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Fees</h1>
</header>

<section class="erp-card" aria-label="Fees of the whole school">
	<dl class="erp-tally erp-tally--money">
		<div class="erp-tally__item erp-tally__item--L"><dt>Fees</dt><dd><?php echo esc_html( mags_erp_money( $erp_school['charged'] ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--P"><dt>Received</dt><dd><?php echo esc_html( mags_erp_money( $erp_school['paid'] ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--A"><dt>Left to collect</dt><dd><?php echo esc_html( mags_erp_money( max( 0, $erp_school['left'] ) ) ); ?></dd></div>
	</dl>
	<p class="erp-hint">Whole school, students who are in a class now. The portal only records fees and payments; nothing is paid online.</p>
</section>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">Add classes and students first. <a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Add classes</a></p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-class-fee-title">
		<h2 id="erp-class-fee-title">Add a fee for a whole class</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'fees' ) ); ?>" data-erp-confirm="Add this fee for every student of the class?">
			<?php mags_erp_form_fields( 'save_fee' ); ?>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_fee_class">Class</label><select id="erp_fee_class" name="class_id" required><?php mags_erp_class_options( $erp_classes, $erp_class ? (int) $erp_class->id : 0 ); ?></select></p>
				<p class="erp-field"><label for="erp_fee_title">Fee for</label><input id="erp_fee_title" name="title" type="text" maxlength="120" placeholder="Tuition fee April–June" required></p>
				<p class="erp-field"><label for="erp_fee_amount">Amount for each student (₹)</label><input id="erp_fee_amount" name="amount" type="text" inputmode="decimal" maxlength="12" placeholder="4500" required></p>
				<p class="erp-field"><label for="erp_fee_due">Pay by <span>(optional)</span></label><input id="erp_fee_due" name="due_date" type="date"></p>
			</div>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Add fee for the class</button></div>
		</form>
		<p class="erp-hint">Each student of the class gets this fee. For one student only, open the student below and add the fee there.</p>
	</section>

	<section aria-labelledby="erp-fee-students-title">
		<h2 id="erp-fee-students-title" class="erp-section-title">Students<?php if ( $erp_rows ) : ?> <span class="erp-count"><?php echo esc_html( count( $erp_rows ) ); ?></span><?php endif; ?></h2>
		<form class="erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'fees' ) ); ?>">
			<?php mags_erp_route_field( 'fees' ); ?>
			<p class="erp-field"><label for="erp_filter_class">Class</label><select id="erp_filter_class" name="class"><?php mags_erp_class_options( $erp_classes, $erp_class ? (int) $erp_class->id : 0, 'All classes' ); ?></select></p>
			<p class="erp-field"><label for="erp_filter_q">Name or admission number</label><input id="erp_filter_q" name="q" type="search" maxlength="60" value="<?php echo esc_attr( $erp_search ); ?>"></p>
			<button class="erp-btn erp-btn--navy" type="submit">Show</button>
		</form>
		<?php if ( ! $erp_class && '' === $erp_search ) : ?>
			<p class="erp-card erp-empty">Choose a class or search for a student to record a payment or see what is left to pay.</p>
		<?php elseif ( ! $erp_rows ) : ?>
			<p class="erp-card erp-empty">No students match this filter.</p>
		<?php else : ?>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_rows as $erp_row ) : ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php if ( '' !== $erp_row[0]->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_row[0]->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_row[0]->full_name ); ?></strong>
							<span>Adm. no. <?php echo esc_html( $erp_row[0]->admission_no ); ?> · <?php echo esc_html( $erp_labels[ (int) $erp_row[0]->class_id ] ?? '' ); ?> · fees <?php echo esc_html( mags_erp_money( $erp_row[1] ) ); ?> · paid <?php echo esc_html( mags_erp_money( $erp_row[2] ) ); ?></span>
							<span class="erp-row__chips"><?php if ( $erp_row[3] > 0 ) : ?><span class="erp-chip erp-chip--status-A"><?php echo esc_html( mags_erp_money( $erp_row[3] ) ); ?> left to pay</span><?php elseif ( $erp_row[3] < 0 ) : ?><span class="erp-chip erp-chip--blue"><?php echo esc_html( mags_erp_money( -$erp_row[3] ) ); ?> paid in advance</span><?php elseif ( $erp_row[1] > 0 ) : ?><span class="erp-chip erp-chip--green">All paid</span><?php else : ?><span class="erp-chip">No fee yet</span><?php endif; ?></span>
						</div>
						<div class="erp-row__actions"><a class="erp-btn <?php echo $erp_row[3] > 0 ? 'erp-btn--orange' : 'erp-btn--ghost'; ?> erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'fees', array( 'student' => (int) $erp_row[0]->id ) ) ); ?>"><?php echo $erp_row[3] > 0 ? 'Record payment' : 'Open'; ?><span class="erp-sr"> for <?php echo esc_html( $erp_row[0]->full_name ); ?></span></a></div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $erp_class ) : ?><p class="erp-removed"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'reports', array( 'report' => 'fees', 'class' => (int) $erp_class->id ) ) ); ?>">Fees report of this class</a></p><?php endif; ?>
		<?php endif; ?>
	</section>
<?php endif; ?>
