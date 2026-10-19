<?php
/** Student (and parent): the timetable of the student's own class. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}

$erp_class   = mags_erp_get_class( (int) $erp_student->class_id );
$erp_periods = mags_erp_get_periods();
$erp_days    = mags_erp_days();
$erp_dow     = mags_erp_timetable_dow( mags_erp_get_int( 'dow' ) );
$erp_week    = mags_erp_class_timetable( (int) $erp_student->class_id );
$erp_cell    = mags_erp_timetable_cell( 'class' );
$erp_tabs    = array();
foreach ( $erp_days as $erp_day_no => $erp_day_name ) { $erp_tabs[ mags_erp_url( 'timetable', array( 'dow' => $erp_day_no ) ) ] = substr( $erp_day_name, 0, 3 ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></p>
	<h1>My timetable</h1>
</header>

<?php if ( ! $erp_periods || ! $erp_week ) : ?>
	<p class="erp-card erp-empty">The school office has not made the timetable of your class yet.</p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-tt-day-title">
		<?php mags_erp_tabs( $erp_tabs, mags_erp_url( 'timetable', array( 'dow' => $erp_dow ) ), 'Day of the week' ); ?>
		<h2 id="erp-tt-day-title" class="erp-tabs__title"><?php echo esc_html( $erp_days[ $erp_dow ] ); ?><?php if ( mags_erp_today_dow() === $erp_dow ) : ?> <span class="erp-chip erp-chip--green">Today</span><?php endif; ?></h2>
		<?php mags_erp_print_timetable_day( $erp_periods, $erp_week[ $erp_dow ] ?? array(), $erp_cell, mags_erp_today_dow() === $erp_dow ); ?>
	</section>

	<section class="erp-card" aria-labelledby="erp-tt-week-title">
		<h2 id="erp-tt-week-title">Whole week</h2>
		<?php mags_erp_print_timetable_week( $erp_periods, $erp_week, $erp_cell, 'Timetable of ' . mags_erp_class_label( $erp_class ) ); ?>
	</section>
<?php endif; ?>
