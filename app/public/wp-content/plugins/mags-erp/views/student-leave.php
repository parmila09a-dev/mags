<?php
/** Student (and parent): ask the class teacher for leave and see the answers. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}

$erp_class    = mags_erp_get_class( (int) $erp_student->class_id );
$erp_teacher  = $erp_class && (int) $erp_class->class_teacher_id && mags_erp_teacher_has_class( (int) $erp_class->class_teacher_id, (int) $erp_class->id ) ? mags_erp_teacher_name( (int) $erp_class->class_teacher_id ) : '';
$erp_leaves   = mags_erp_student_leaves( (int) $erp_student->id );
$erp_statuses = mags_erp_leave_statuses();
$erp_today    = mags_erp_today();
$erp_earliest = gmdate( 'Y-m-d', strtotime( $erp_today . ' -' . MAGS_ERP_LEAVE_BACK_DAYS . ' days' ) );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></p>
	<h1>Leave</h1>
</header>

<section class="erp-card" aria-labelledby="erp-leave-form-title">
	<h2 id="erp-leave-form-title">Ask for leave</h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'leave' ) ); ?>">
		<?php mags_erp_form_fields( 'ask_leave' ); ?>
		<div class="erp-form__grid">
			<p class="erp-field"><label for="erp_leave_from">First day</label><input id="erp_leave_from" name="from_date" type="date" min="<?php echo esc_attr( $erp_earliest ); ?>" value="<?php echo esc_attr( $erp_today ); ?>" required></p>
			<p class="erp-field"><label for="erp_leave_to">Last day <span>(empty for one day)</span></label><input id="erp_leave_to" name="to_date" type="date" min="<?php echo esc_attr( $erp_earliest ); ?>"></p>
		</div>
		<p class="erp-field"><label for="erp_leave_reason">Reason</label><textarea id="erp_leave_reason" name="reason" rows="3" maxlength="1000" placeholder="Fever, family function…" required></textarea></p>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Send request</button></div>
	</form>
	<p class="erp-hint"><?php echo esc_html( '' !== $erp_teacher ? 'Your class teacher, ' . $erp_teacher . ', answers the request.' : 'Your class has no class teacher in the portal yet. The request waits until the school office chooses one.' ); ?></p>
</section>

<section aria-labelledby="erp-leave-list-title">
	<h2 id="erp-leave-list-title" class="erp-section-title">My requests <span class="erp-count"><?php echo esc_html( count( $erp_leaves ) ); ?></span></h2>
	<?php if ( ! $erp_leaves ) : ?>
		<p class="erp-card erp-empty">You have not asked for leave yet.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_leaves as $erp_leave ) : $erp_status = $erp_statuses[ $erp_leave->status ] ?? array( $erp_leave->status, '' ); ?>
				<li class="erp-row<?php echo 'withdrawn' === $erp_leave->status ? ' erp-row--off' : ''; ?>">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_leave_dates( $erp_leave ) ); ?></strong>
						<span><?php echo esc_html( $erp_leave->reason ); ?></span>
						<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_status[1] ); ?>"><?php echo esc_html( $erp_status[0] ); ?></span></span>
						<?php if ( in_array( $erp_leave->status, array( 'approved', 'rejected' ), true ) ) : ?>
							<span><?php echo esc_html( mags_erp_teacher_name( (int) $erp_leave->decided_by ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_leave->decided_at ) ); ?></span>
							<?php if ( '' !== trim( $erp_leave->decision_note ) ) : ?><span class="erp-quote erp-quote--teacher"><?php echo esc_html( $erp_leave->decision_note ); ?></span><?php endif; ?>
						<?php endif; ?>
					</div>
					<?php if ( 'pending' === $erp_leave->status ) : ?>
						<div class="erp-row__actions">
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'leave' ) ); ?>" data-erp-confirm="Withdraw this leave request?">
								<?php mags_erp_form_fields( 'withdraw_leave' ); ?>
								<input type="hidden" name="leave_id" value="<?php echo esc_attr( $erp_leave->id ); ?>">
								<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">Withdraw</button>
							</form>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
