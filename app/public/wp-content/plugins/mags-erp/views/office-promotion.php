<?php
/** Office: at the start of a new session, move every class up in one go; undo the newest move. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes    = mags_erp_get_classes();
$erp_guess      = mags_erp_promotion_guess( $erp_classes );
$erp_sessions   = mags_erp_promotion_sessions();
$erp_last       = mags_erp_last_promotion();
$erp_history    = mags_erp_get_promotions();
$erp_with_kids  = array_values( array_filter( $erp_classes, static function ( $erp_class ) { return (int) $erp_class->student_count > 0; } ) );
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>New session: move classes up</h1>
</header>

<?php if ( $erp_last ) : ?>
	<section class="erp-card erp-card--alert" aria-labelledby="erp-undo-title">
		<h2 id="erp-undo-title">Last promotion: <?php echo esc_html( mags_erp_format_datetime( $erp_last->created_at ) ); ?></h2>
		<p><?php echo esc_html( sprintf( '%1$d students moved up, %2$d left the school, by %3$s. Session that ended: %4$s.', $erp_last->moved_count, $erp_last->left_count, mags_erp_teacher_name( (int) $erp_last->created_by ), $erp_last->session ) ); ?></p>
		<form method="post" action="<?php echo esc_url( mags_erp_url( 'promotion' ) ); ?>" data-erp-confirm="Undo the last promotion? Every student goes back to the class and roll number they had before it, and the students of the last class come back.">
			<?php mags_erp_form_fields( 'undo_promotion' ); ?>
			<input type="hidden" name="promotion_id" value="<?php echo esc_attr( $erp_last->id ); ?>">
			<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Undo this promotion</button>
		</form>
		<p class="erp-hint">Undo only puts back students the office has not changed since. A promotion that was undone can be done again from the plan below.</p>
	</section>
<?php endif; ?>

<section class="erp-card" aria-labelledby="erp-promo-how-title">
	<h2 id="erp-promo-how-title">What happens</h2>
	<ul class="erp-code-steps erp-hint">
		<li>The students of each class move to the class you choose: Class 5 – A to Class 6 – A, and so on. All classes move at the same moment, so nobody goes up two classes.</li>
		<li>The students of the last class (choose “Leave the school”) are marked as left, like after a TC; their login is switched off.</li>
		<li>Students who repeat the year: open “Keep back” under their class and tick them. They stay in their class.</li>
		<li><strong>Nothing is lost.</strong> Attendance, homework answers, exam marks, report cards, fees and certificates stay with the class the student was in, and each student’s profile shows their class history. Fees still to pay go with the student.</li>
		<li>Classes keep their teachers, class teacher and timetable. Change those under Classes, Teachers and Timetable if they are different in the new session. Roll numbers stay; change them under Students if needed.</li>
	</ul>
</section>

<?php if ( ! $erp_with_kids ) : ?>
	<p class="erp-card erp-empty">There are no students in any class, so there is nothing to move.</p>
<?php else : ?>
	<form class="erp-card erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'promotion' ) ); ?>" data-erp-confirm="Move the classes up now, as planned? You can undo it afterwards if something is wrong." aria-labelledby="erp-plan-title">
		<?php mags_erp_form_fields( 'promote_classes' ); ?>
		<h2 id="erp-plan-title">The plan</h2>
		<p class="erp-field"><label for="erp_promo_session">Session that ends</label><select id="erp_promo_session" name="session"><?php foreach ( $erp_sessions['choices'] as $erp_label ) : ?><option value="<?php echo esc_attr( $erp_label ); ?>" <?php selected( $erp_sessions['default'], $erp_label ); ?>><?php echo esc_html( $erp_label ); ?></option><?php endforeach; ?></select><small>Written in each student’s class history.</small></p>
		<p class="erp-hint">The portal has filled in the next class from the class names. Check every line. Classes without students are not listed.</p>
		<ul class="erp-list">
			<?php foreach ( $erp_with_kids as $erp_class ) : $erp_class_id = (int) $erp_class->id; $erp_pick = $erp_guess[ $erp_class_id ] ?? 0; ?>
				<li class="erp-row erp-promo">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></strong>
						<span><?php echo esc_html( sprintf( _n( '%d student', '%d students', (int) $erp_class->student_count, 'mags-erp' ), (int) $erp_class->student_count ) ); ?></span>
						<details class="erp-promo__keep">
							<summary>Keep back (repeat the year)</summary>
							<fieldset class="erp-checks">
								<legend class="erp-sr">Students of <?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?> who stay</legend>
								<?php foreach ( mags_erp_class_students( $erp_class_id ) as $erp_student ) : ?>
									<label class="erp-pill"><input type="checkbox" name="keep[]" value="<?php echo esc_attr( $erp_student->id ); ?>"><span><?php echo esc_html( ( '' !== $erp_student->roll_no ? $erp_student->roll_no . '. ' : '' ) . $erp_student->full_name ); ?></span></label>
								<?php endforeach; ?>
							</fieldset>
						</details>
					</div>
					<p class="erp-field erp-promo__to">
						<label for="erp_move_<?php echo esc_attr( $erp_class_id ); ?>">Move students to</label>
						<select id="erp_move_<?php echo esc_attr( $erp_class_id ); ?>" name="move[<?php echo esc_attr( $erp_class_id ); ?>]">
							<option value="0" <?php selected( 0 === $erp_pick ); ?>>Stay in this class (no change)</option>
							<?php foreach ( $erp_classes as $erp_other ) : if ( (int) $erp_other->id === $erp_class_id ) { continue; } ?>
								<option value="<?php echo esc_attr( $erp_other->id ); ?>" <?php selected( $erp_pick, (int) $erp_other->id ); ?>><?php echo esc_html( mags_erp_class_label( $erp_other ) ); ?></option>
							<?php endforeach; ?>
							<option value="<?php echo esc_attr( MAGS_ERP_PROMOTE_LEAVE ); ?>" <?php selected( MAGS_ERP_PROMOTE_LEAVE === $erp_pick ); ?>>Leave the school (passed out)</option>
						</select>
					</p>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="erp-check"><label><input type="checkbox" name="checked" value="1" required> <span>I have checked the plan. The results of the session are published and the TCs of students who left during the year are made.</span></label></p>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Move classes up</button></div>
	</form>
<?php endif; ?>

<?php if ( $erp_history ) : ?>
	<section aria-labelledby="erp-promo-history-title">
		<h2 id="erp-promo-history-title" class="erp-section-title">Earlier promotions <span class="erp-count"><?php echo esc_html( count( $erp_history ) ); ?></span></h2>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_history as $erp_item ) : ?>
				<li class="erp-row<?php echo $erp_item->undone_at ? ' erp-row--off' : ''; ?>">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_format_datetime( $erp_item->created_at ) ); ?> · session <?php echo esc_html( $erp_item->session ); ?></strong>
						<span><?php echo esc_html( sprintf( '%1$d moved up, %2$d left, by %3$s', $erp_item->moved_count, $erp_item->left_count, mags_erp_teacher_name( (int) $erp_item->created_by ) ) ); ?></span>
						<?php if ( $erp_item->undone_at ) : ?><span class="erp-row__chips"><span class="erp-chip erp-chip--status-A">Undone <?php echo esc_html( mags_erp_format_datetime( $erp_item->undone_at, 'j M Y' ) ); ?></span></span><?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>
