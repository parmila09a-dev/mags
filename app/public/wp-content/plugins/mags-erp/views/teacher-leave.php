<?php
/** Class teacher: approve or refuse the leave requests of the students of their class. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_class_ids = mags_erp_class_teacher_class_ids( $ctx['user']->ID );
$erp_labels    = mags_erp_all_class_labels();
$erp_pending   = mags_erp_classes_leaves( $erp_class_ids, true );
$erp_answered  = mags_erp_classes_leaves( $erp_class_ids, false, 60 );
$erp_statuses  = mags_erp_leave_statuses();
?>
<header class="erp-head">
	<p class="erp-eyebrow">Class teacher</p>
	<h1>Leave requests</h1>
	<?php if ( $erp_class_ids ) : ?><p class="erp-head__meta"><?php foreach ( $erp_class_ids as $erp_own_class ) : ?><span class="erp-chip erp-chip--blue"><?php echo esc_html( $erp_labels[ $erp_own_class ] ?? '' ); ?></span><?php endforeach; ?></p><?php endif; ?>
</header>

<?php if ( ! $erp_class_ids ) : ?>
	<p class="erp-card erp-empty">You are not the class teacher of a class. Leave requests go to the class teacher; the school office chooses the class teacher of each class.</p>
<?php else : ?>
	<section aria-labelledby="erp-leave-pending-title">
		<h2 id="erp-leave-pending-title" class="erp-section-title">Waiting for your answer <span class="erp-count"><?php echo esc_html( count( $erp_pending ) ); ?></span></h2>
		<?php if ( ! $erp_pending ) : ?>
			<p class="erp-card erp-empty">No leave request is waiting.</p>
		<?php else : ?>
			<ul class="erp-list">
				<?php foreach ( $erp_pending as $erp_leave ) : ?>
					<li class="erp-row erp-answer">
						<div class="erp-row__main">
							<strong><?php if ( '' !== $erp_leave->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_leave->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_leave->full_name ); ?></strong>
							<span><?php echo esc_html( $erp_labels[ (int) $erp_leave->current_class_id ] ?? '' ); ?> · asked <?php echo esc_html( mags_erp_format_datetime( $erp_leave->created_at ) ); ?></span>
							<span class="erp-row__chips"><span class="erp-chip erp-chip--orange"><?php echo esc_html( mags_erp_leave_dates( $erp_leave ) ); ?></span></span>
							<span class="erp-quote">“<?php echo esc_html( $erp_leave->reason ); ?>”</span>
						</div>
						<form class="erp-form erp-decide" method="post" action="<?php echo esc_url( mags_erp_url( 'leave' ) ); ?>">
							<?php mags_erp_form_fields( 'decide_leave' ); ?>
							<input type="hidden" name="leave_id" value="<?php echo esc_attr( $erp_leave->id ); ?>">
							<p class="erp-field"><label for="erp_leave_note_<?php echo esc_attr( $erp_leave->id ); ?>">Note to the student <span>(optional)</span></label><textarea id="erp_leave_note_<?php echo esc_attr( $erp_leave->id ); ?>" name="decision_note" rows="2" maxlength="500"></textarea></p>
							<div class="erp-form__actions">
								<button class="erp-btn erp-btn--orange erp-btn--sm" type="submit" name="decision" value="approved" data-erp-ask="<?php echo esc_attr( 'Approve the leave of ' . $erp_leave->full_name . '? This cannot be changed afterwards.' ); ?>">Approve</button>
								<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit" name="decision" value="rejected" data-erp-ask="<?php echo esc_attr( 'Do not approve the leave of ' . $erp_leave->full_name . '? This cannot be changed afterwards.' ); ?>">Do not approve</button>
							</div>
						</form>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="erp-hint erp-hint--lead">An answer cannot be changed afterwards. Approved leave is offered as “Leave” on the attendance sheet of those days.</p>
		<?php endif; ?>
	</section>

	<?php if ( $erp_answered ) : ?>
		<section aria-labelledby="erp-leave-done-title">
			<h2 id="erp-leave-done-title" class="erp-section-title">Answered <span class="erp-count"><?php echo esc_html( count( $erp_answered ) ); ?></span></h2>
			<ul class="erp-list">
				<?php foreach ( $erp_answered as $erp_leave ) : $erp_status = $erp_statuses[ $erp_leave->status ]; ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php if ( '' !== $erp_leave->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_leave->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_leave->full_name ); ?></strong>
							<span><?php echo esc_html( mags_erp_leave_dates( $erp_leave ) ); ?> · <?php echo esc_html( $erp_leave->reason ); ?></span>
							<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_status[1] ); ?>"><?php echo esc_html( $erp_status[0] ); ?></span><span class="erp-chip"><?php echo esc_html( mags_erp_teacher_name( (int) $erp_leave->decided_by ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_leave->decided_at, 'j M Y' ) ); ?></span></span>
							<?php if ( '' !== trim( $erp_leave->decision_note ) ) : ?><span class="erp-quote erp-quote--teacher"><?php echo esc_html( $erp_leave->decision_note ); ?></span><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
<?php endif; ?>
