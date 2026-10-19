<?php
/** Office: approve or refuse the leave requests of teachers. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_pending  = mags_erp_staff_leaves( true );
$erp_answered = mags_erp_staff_leaves( false, 60 );
$erp_statuses = mags_erp_leave_statuses();
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Teacher leave</h1>
</header>

<section aria-labelledby="erp-sl-pending-title">
	<h2 id="erp-sl-pending-title" class="erp-section-title">Waiting for your answer <span class="erp-count"><?php echo esc_html( count( $erp_pending ) ); ?></span></h2>
	<?php if ( ! $erp_pending ) : ?>
		<p class="erp-card erp-empty">No leave request is waiting.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_pending as $erp_leave ) : $erp_name = mags_erp_teacher_name( (int) $erp_leave->teacher_id ); ?>
				<li class="erp-row erp-answer">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_name ); ?></strong>
						<span>Asked <?php echo esc_html( mags_erp_format_datetime( $erp_leave->created_at ) ); ?></span>
						<span class="erp-row__chips"><span class="erp-chip erp-chip--orange"><?php echo esc_html( mags_erp_leave_dates( $erp_leave ) ); ?></span></span>
						<span class="erp-quote">“<?php echo esc_html( $erp_leave->reason ); ?>”</span>
					</div>
					<form class="erp-form erp-decide" method="post" action="<?php echo esc_url( mags_erp_url( 'staff-leave' ) ); ?>">
						<?php mags_erp_form_fields( 'decide_staff_leave' ); ?>
						<input type="hidden" name="leave_id" value="<?php echo esc_attr( $erp_leave->id ); ?>">
						<p class="erp-field"><label for="erp_sl_note_<?php echo esc_attr( $erp_leave->id ); ?>">Note to the teacher <span>(optional)</span></label><textarea id="erp_sl_note_<?php echo esc_attr( $erp_leave->id ); ?>" name="decision_note" rows="2" maxlength="500"></textarea></p>
						<div class="erp-form__actions">
							<button class="erp-btn erp-btn--orange erp-btn--sm" type="submit" name="decision" value="approved" data-erp-ask="<?php echo esc_attr( 'Approve the leave of ' . $erp_name . '? This cannot be changed afterwards.' ); ?>">Approve</button>
							<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit" name="decision" value="rejected" data-erp-ask="<?php echo esc_attr( 'Do not approve the leave of ' . $erp_name . '? This cannot be changed afterwards.' ); ?>">Do not approve</button>
						</div>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="erp-hint erp-hint--lead">An answer cannot be changed afterwards. Approved leave is offered as “Leave” on the teacher attendance sheet of those days.</p>
	<?php endif; ?>
</section>

<?php if ( $erp_answered ) : ?>
	<section aria-labelledby="erp-sl-done-title">
		<h2 id="erp-sl-done-title" class="erp-section-title">Answered <span class="erp-count"><?php echo esc_html( count( $erp_answered ) ); ?></span></h2>
		<ul class="erp-list">
			<?php foreach ( $erp_answered as $erp_leave ) : $erp_status = $erp_statuses[ $erp_leave->status ]; ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_teacher_name( (int) $erp_leave->teacher_id ) ); ?></strong>
						<span><?php echo esc_html( mags_erp_leave_dates( $erp_leave ) ); ?> · <?php echo esc_html( $erp_leave->reason ); ?></span>
						<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_status[1] ); ?>"><?php echo esc_html( $erp_status[0] ); ?></span><span class="erp-chip"><?php echo esc_html( mags_erp_teacher_name( (int) $erp_leave->decided_by ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_leave->decided_at, 'j M Y' ) ); ?></span></span>
						<?php if ( '' !== trim( $erp_leave->decision_note ) ) : ?><span class="erp-quote erp-quote--teacher"><?php echo esc_html( $erp_leave->decision_note ); ?></span><?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>
<p class="erp-removed"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'staff' ) ); ?>">Teacher attendance</a></p>
