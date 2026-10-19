<?php
/** Office: fill the weekly timetable of a class, one day at a time. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes  = mags_erp_get_classes();
$erp_periods  = mags_erp_get_periods();
$erp_subjects = mags_erp_get_subjects();
$erp_days     = mags_erp_days();
$erp_class    = mags_erp_get_class( mags_erp_get_int( 'class' ) );
if ( ! $erp_class && 1 === count( $erp_classes ) ) { $erp_class = $erp_classes[0]; }
$erp_dow      = mags_erp_timetable_dow( mags_erp_get_int( 'dow' ) );
$erp_week     = $erp_class ? mags_erp_class_timetable( (int) $erp_class->id ) : array();
$erp_teachers = $erp_class ? mags_erp_class_teachers( (int) $erp_class->id ) : array();
$erp_tabs     = array();
if ( $erp_class ) { foreach ( $erp_days as $erp_day_no => $erp_day_name ) { $erp_tabs[ mags_erp_url( 'timetable', array( 'class' => (int) $erp_class->id, 'dow' => $erp_day_no ) ) ] = substr( $erp_day_name, 0, 3 ); } }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1><?php echo esc_html( $erp_class ? 'Timetable: ' . mags_erp_class_label( $erp_class ) : 'Timetable' ); ?></h1>
</header>

<?php if ( ! $erp_classes || ! $erp_periods || ! $erp_subjects ) : ?>
	<section class="erp-card erp-empty">
		<p>The timetable needs classes, subjects and the periods of the school day.</p>
		<p>
			<?php if ( ! $erp_classes ) : ?><a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Add classes</a><?php endif; ?>
			<?php if ( ! $erp_periods || ! $erp_subjects ) : ?><a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>">Add subjects and periods</a><?php endif; ?>
		</p>
	</section>
<?php else : ?>
	<form class="erp-card erp-filter erp-filter--one" method="get" action="<?php echo esc_url( mags_erp_url( 'timetable' ) ); ?>" data-erp-autosubmit>
		<?php mags_erp_route_field( 'timetable' ); ?>
		<p class="erp-field"><label for="erp_tt_class">Class</label><select id="erp_tt_class" name="class" required><?php mags_erp_class_options( $erp_classes, $erp_class ? (int) $erp_class->id : 0 ); ?></select></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<?php if ( ! $erp_class ) : ?>
		<p class="erp-card erp-empty">Choose a class to see and fill its timetable.</p>
	<?php else : ?>
		<section class="erp-card" aria-labelledby="erp-tt-day-title">
			<?php mags_erp_tabs( $erp_tabs, mags_erp_url( 'timetable', array( 'class' => (int) $erp_class->id, 'dow' => $erp_dow ) ), 'Day of the week' ); ?>
			<h2 id="erp-tt-day-title" class="erp-tabs__title"><?php echo esc_html( $erp_days[ $erp_dow ] ); ?></h2>
			<?php if ( ! $erp_teachers ) : ?><p class="erp-hint">This class has no teacher yet, so only subjects can be chosen. Give the class to its teachers under <a href="<?php echo esc_url( mags_erp_url( 'teachers' ) ); ?>">Teachers</a>.</p><?php endif; ?>
			<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'timetable' ) ); ?>">
				<?php mags_erp_form_fields( 'save_timetable' ); ?>
				<input type="hidden" name="class_id" value="<?php echo esc_attr( $erp_class->id ); ?>">
				<input type="hidden" name="dow" value="<?php echo esc_attr( $erp_dow ); ?>">
				<ol class="erp-tt erp-tt--edit">
					<?php foreach ( $erp_periods as $erp_period ) : $erp_slot = $erp_week[ $erp_dow ][ (int) $erp_period->id ] ?? null; ?>
						<li class="erp-tt__row<?php echo $erp_period->is_break ? ' is-break' : ''; ?>">
							<div class="erp-tt__time"><strong><?php echo esc_html( $erp_period->label ); ?></strong><span><?php echo esc_html( mags_erp_period_time( $erp_period ) ); ?></span></div>
							<?php if ( $erp_period->is_break ) : ?>
								<div class="erp-tt__what"><strong>Break</strong></div>
							<?php else : ?>
								<div class="erp-tt__pick">
									<p class="erp-field"><label class="erp-sr" for="erp_tt_s_<?php echo esc_attr( $erp_period->id ); ?>">Subject in period <?php echo esc_html( $erp_period->label ); ?></label><select id="erp_tt_s_<?php echo esc_attr( $erp_period->id ); ?>" name="subject[<?php echo esc_attr( $erp_period->id ); ?>]"><option value="0">Free</option><?php foreach ( $erp_subjects as $erp_subject ) : ?><option value="<?php echo esc_attr( $erp_subject->id ); ?>" <?php selected( $erp_slot ? (int) $erp_slot->subject_id : 0, (int) $erp_subject->id ); ?>><?php echo esc_html( $erp_subject->name ); ?></option><?php endforeach; ?></select></p>
									<p class="erp-field"><label class="erp-sr" for="erp_tt_t_<?php echo esc_attr( $erp_period->id ); ?>">Teacher in period <?php echo esc_html( $erp_period->label ); ?></label><select id="erp_tt_t_<?php echo esc_attr( $erp_period->id ); ?>" name="teacher[<?php echo esc_attr( $erp_period->id ); ?>]"><option value="0">No teacher</option><?php foreach ( $erp_teachers as $erp_teacher_id => $erp_teacher_name ) : ?><option value="<?php echo esc_attr( $erp_teacher_id ); ?>" <?php selected( $erp_slot ? (int) $erp_slot->teacher_id : 0, $erp_teacher_id ); ?>><?php echo esc_html( $erp_teacher_name ); ?></option><?php endforeach; ?></select></p>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Save <?php echo esc_html( $erp_days[ $erp_dow ] ); ?></button></div>
			</form>
		</section>

		<section class="erp-card" aria-labelledby="erp-tt-week-title">
			<h2 id="erp-tt-week-title">Whole week</h2>
			<?php mags_erp_print_timetable_week( $erp_periods, $erp_week, mags_erp_timetable_cell( 'class' ), 'Timetable of ' . mags_erp_class_label( $erp_class ) ); ?>
		</section>
	<?php endif; ?>
<?php endif; ?>
