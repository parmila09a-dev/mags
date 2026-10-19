<?php
/** Teacher and student (and parent): the school's holidays and events, month by month. The office has its own screen. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_month   = mags_erp_calendar_month( mags_erp_get_text( 'month' ) );
$erp_entries = mags_erp_calendar_entries( $erp_month['start'], $erp_month['end'] );
$erp_holiday = mags_erp_holiday_on( mags_erp_today() );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_school_name() ); ?></p>
	<h1>School calendar</h1>
	<?php if ( $erp_holiday ) : ?><p class="erp-head__meta"><span class="erp-chip erp-chip--blue">Today is a holiday: <?php echo esc_html( $erp_holiday->title ); ?></span></p><?php endif; ?>
</header>

<section class="erp-card" aria-labelledby="erp-calendar-title">
	<?php mags_erp_print_calendar_head( $erp_month, 'erp-calendar-title' ); ?>
	<p class="erp-month__note"><?php if ( ! $erp_month['is_current'] ) : ?><a href="<?php echo esc_url( mags_erp_url( 'calendar' ) ); ?>">Back to this month</a><?php else : ?>Holidays and events of the school.<?php endif; ?></p>
	<?php mags_erp_print_calendar_grid( $erp_month, $erp_entries ); ?>
</section>

<section aria-labelledby="erp-calendar-list-title">
	<h2 id="erp-calendar-list-title" class="erp-section-title">In <?php echo esc_html( mags_erp_format_date( $erp_month['start'], 'F Y' ) ); ?> <span class="erp-count"><?php echo esc_html( count( $erp_entries ) ); ?></span></h2>
	<?php mags_erp_print_calendar_list( $erp_month, $erp_entries ); ?>
	<p class="erp-hint erp-hint--lead">On a holiday there is no school: attendance is not marked and the day is not counted in the attendance percentage.</p>
</section>
