<?php
/** Office: give the periods or the class of a teacher who is away to another teacher, for one day. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_today    = mags_erp_today();
$erp_last_day = gmdate( 'Y-m-d', strtotime( $erp_today . ' +' . MAGS_ERP_SUBSTITUTE_DAYS_AHEAD . ' days' ) );
$erp_date     = mags_erp_get_text( 'date' );
$erp_date     = mags_erp_valid_date( $erp_date ) && $erp_date <= $erp_last_day ? $erp_date : $erp_today;
$erp_can_edit = $erp_date >= $erp_today;
$erp_holiday  = mags_erp_holiday_on( $erp_date );
$erp_away     = mags_erp_teachers_away( $erp_date );
$erp_subs     = mags_erp_substitutions_on( $erp_date );
$erp_taken    = array();
foreach ( $erp_subs as $erp_sub ) { $erp_taken[ (int) $erp_sub->class_id . '-' . (int) $erp_sub->period_id ] = $erp_sub; }
$erp_classes  = mags_erp_get_classes();
$erp_periods  = array_values( array_filter( mags_erp_get_periods(), static function ( $erp_period ) { return ! $erp_period->is_break; } ) );
$erp_teachers = mags_erp_get_teachers();
$erp_back     = mags_erp_url( 'substitutes', array( 'date' => $erp_date ) );

/** The small form that gives one period (or the whole day) of a class to a free teacher. */
$erp_give = static function ( $class_id, $period_id, $absent_id, $label ) use ( $erp_date, $erp_back ) {
	$free = mags_erp_free_teachers( $erp_date, $period_id, $class_id );
	unset( $free[ (int) $absent_id ] );
	if ( ! $free ) { echo '<span class="erp-chip erp-chip--orange">No teacher is free</span>'; return; }
	?>
	<form class="erp-inline-form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
		<?php mags_erp_form_fields( 'save_substitution' ); ?>
		<input type="hidden" name="date" value="<?php echo esc_attr( $erp_date ); ?>"><input type="hidden" name="class_id" value="<?php echo esc_attr( $class_id ); ?>"><input type="hidden" name="period_id" value="<?php echo esc_attr( $period_id ); ?>"><input type="hidden" name="absent_teacher_id" value="<?php echo esc_attr( $absent_id ); ?>">
		<label class="erp-sr" for="<?php echo esc_attr( 'erp_sub_' . $class_id . '_' . $period_id . '_' . $absent_id ); ?>">Teacher for <?php echo esc_html( $label ); ?></label>
		<select id="<?php echo esc_attr( 'erp_sub_' . $class_id . '_' . $period_id . '_' . $absent_id ); ?>" name="teacher_id" required><option value="">Choose a free teacher</option><?php foreach ( $free as $teacher_id => $name ) : ?><option value="<?php echo esc_attr( $teacher_id ); ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select>
		<button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Give<span class="erp-sr"> <?php echo esc_html( $label ); ?></span></button>
	</form>
	<?php
};
/** The teacher who has a period or day now, with a Remove button. */
$erp_given = static function ( $sub ) use ( $erp_back, $erp_can_edit ) {
	echo '<span class="erp-chip erp-chip--green">' . esc_html( mags_erp_teacher_name( (int) $sub->teacher_id ) ) . '</span>';
	if ( ! $erp_can_edit ) { return; }
	?>
	<form method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="Remove this substitute?">
		<?php mags_erp_form_fields( 'remove_substitution' ); ?>
		<input type="hidden" name="substitution_id" value="<?php echo esc_attr( $sub->id ); ?>">
		<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">Remove<span class="erp-sr"> <?php echo esc_html( mags_erp_teacher_name( (int) $sub->teacher_id ) ); ?></span></button>
	</form>
	<?php
};
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Substitute teachers</h1>
	<p class="erp-head__meta"><span class="erp-chip"><?php echo esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></span><?php if ( $erp_holiday ) : ?><span class="erp-chip erp-chip--blue">Holiday: <?php echo esc_html( $erp_holiday->title ); ?></span><?php endif; ?></p>
</header>

<form class="erp-card erp-filter erp-filter--one" method="get" action="<?php echo esc_url( mags_erp_url( 'substitutes' ) ); ?>" data-erp-autosubmit>
	<?php mags_erp_route_field( 'substitutes' ); ?>
	<p class="erp-field"><label for="erp_sub_date">Day</label><input id="erp_sub_date" name="date" type="date" max="<?php echo esc_attr( $erp_last_day ); ?>" value="<?php echo esc_attr( $erp_date ); ?>" required></p>
	<button class="erp-btn erp-btn--navy" type="submit">Show</button>
</form>

<?php if ( $erp_holiday ) : ?>
	<p class="erp-card erp-empty">No school on this day, so there is nothing to cover.</p>
<?php else : ?>
	<section aria-labelledby="erp-away-title">
		<h2 id="erp-away-title" class="erp-section-title">Teachers away <span class="erp-count"><?php echo esc_html( count( $erp_away ) ); ?></span></h2>
		<?php if ( ! $erp_away ) : ?>
			<p class="erp-card erp-empty">No teacher has approved leave or is marked absent on this day. You can still give a class to a teacher below.</p>
		<?php endif; ?>
		<?php foreach ( $erp_away as $erp_teacher_id => $erp_why ) : $erp_slots = mags_erp_away_teacher_periods( $erp_teacher_id, $erp_date ); $erp_name = mags_erp_teacher_name( (int) $erp_teacher_id ); ?>
			<div class="erp-card">
				<h3><?php echo esc_html( $erp_name ); ?> <span class="erp-chip erp-chip--orange"><?php echo esc_html( $erp_why ); ?></span></h3>
				<?php if ( ! $erp_slots ) : ?><p class="erp-hint">No periods in the timetable on this day.</p><?php endif; ?>
				<ul class="erp-list erp-list--tight">
					<?php foreach ( $erp_slots as $erp_slot ) : $erp_label = $erp_slot->period->label . ', ' . $erp_slot->class_label; $erp_sub = $erp_taken[ (int) $erp_slot->class_id . '-' . (int) $erp_slot->period->id ] ?? null; ?>
						<li class="erp-row">
							<div class="erp-row__main"><strong>Period <?php echo esc_html( $erp_slot->period->label ); ?> · <?php echo esc_html( $erp_slot->class_label ); ?></strong><span><?php echo esc_html( mags_erp_period_time( $erp_slot->period ) . ' · ' . ( mags_erp_subject_names()[ (int) $erp_slot->subject_id ] ?? '' ) ); ?></span></div>
							<div class="erp-row__actions"><?php if ( $erp_sub ) { $erp_given( $erp_sub ); } elseif ( $erp_can_edit ) { $erp_give( (int) $erp_slot->class_id, (int) $erp_slot->period->id, (int) $erp_teacher_id, $erp_label ); } else { echo '<span class="erp-chip">Not covered</span>'; } ?></div>
						</li>
					<?php endforeach; ?>
					<?php foreach ( mags_erp_teacher_class_ids( $erp_teacher_id ) as $erp_class_id ) : $erp_label = ( mags_erp_all_class_labels()[ $erp_class_id ] ?? 'Class' ) . ', whole day'; $erp_sub = $erp_taken[ $erp_class_id . '-0' ] ?? null; ?>
						<li class="erp-row">
							<div class="erp-row__main"><strong>Whole day · <?php echo esc_html( mags_erp_all_class_labels()[ $erp_class_id ] ?? 'Class' ); ?></strong><span>Looks after the class and marks its attendance</span></div>
							<div class="erp-row__actions"><?php if ( $erp_sub ) { $erp_given( $erp_sub ); } elseif ( $erp_can_edit ) { $erp_give( $erp_class_id, 0, (int) $erp_teacher_id, $erp_label ); } else { echo '<span class="erp-chip">Not covered</span>'; } ?></div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</section>

	<?php if ( $erp_can_edit && $erp_classes && $erp_teachers ) : ?>
		<section class="erp-card" aria-labelledby="erp-sub-any-title">
			<h2 id="erp-sub-any-title">Give any class to a teacher</h2>
			<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
				<?php mags_erp_form_fields( 'save_substitution' ); ?>
				<input type="hidden" name="date" value="<?php echo esc_attr( $erp_date ); ?>">
				<div class="erp-form__grid">
					<p class="erp-field"><label for="erp_sub_class">Class</label><select id="erp_sub_class" name="class_id" required><?php mags_erp_class_options( $erp_classes ); ?></select></p>
					<p class="erp-field"><label for="erp_sub_period">Period</label><select id="erp_sub_period" name="period_id"><option value="0">Whole day</option><?php foreach ( $erp_periods as $erp_period ) : ?><option value="<?php echo esc_attr( $erp_period->id ); ?>"><?php echo esc_html( $erp_period->label . ' (' . mags_erp_period_time( $erp_period ) . ')' ); ?></option><?php endforeach; ?></select></p>
					<p class="erp-field"><label for="erp_sub_teacher">Teacher who stands in</label><select id="erp_sub_teacher" name="teacher_id" required><option value="">Choose teacher</option><?php foreach ( $erp_teachers as $erp_teacher ) : ?><option value="<?php echo esc_attr( $erp_teacher->ID ); ?>"><?php echo esc_html( $erp_teacher->display_name ); ?></option><?php endforeach; ?></select></p>
					<p class="erp-field"><label for="erp_sub_for">In place of <span>(optional)</span></label><select id="erp_sub_for" name="absent_teacher_id"><option value="0">—</option><?php foreach ( $erp_teachers as $erp_teacher ) : ?><option value="<?php echo esc_attr( $erp_teacher->ID ); ?>"><?php echo esc_html( $erp_teacher->display_name ); ?></option><?php endforeach; ?></select></p>
				</div>
				<p class="erp-field"><label for="erp_sub_note">Note for the teacher <span>(optional)</span></label><input id="erp_sub_note" name="note" type="text" maxlength="200" placeholder="Revise chapter 4, the notebooks are on the table…"></p>
				<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Give the class</button></div>
			</form>
			<p class="erp-hint">The portal refuses a teacher who is away that day or already teaches in that period.</p>
		</section>
	<?php endif; ?>

	<section aria-labelledby="erp-sub-list-title">
		<h2 id="erp-sub-list-title" class="erp-section-title">All substitutes on this day <span class="erp-count"><?php echo esc_html( count( $erp_subs ) ); ?></span></h2>
		<?php if ( ! $erp_subs ) : ?>
			<p class="erp-card erp-empty">No substitute on this day yet.</p>
		<?php else : ?>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_subs as $erp_sub ) : ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php echo esc_html( ( $erp_sub->period ? 'Period ' . $erp_sub->period->label : 'Whole day' ) . ' · ' . $erp_sub->class_label ); ?></strong>
							<span><?php echo esc_html( trim( ( $erp_sub->period ? mags_erp_period_time( $erp_sub->period ) . ' · ' : '' ) . ( '' !== $erp_sub->subject_name ? $erp_sub->subject_name . ' · ' : '' ) . ( '' !== $erp_sub->absent_name ? 'for ' . $erp_sub->absent_name : '' ), ' ·' ) ); ?></span>
							<?php if ( '' !== $erp_sub->note ) : ?><span class="erp-quote"><?php echo esc_html( $erp_sub->note ); ?></span><?php endif; ?>
						</div>
						<div class="erp-row__actions"><?php $erp_given( $erp_sub ); ?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
<?php endif; ?>
