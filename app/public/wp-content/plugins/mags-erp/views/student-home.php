<?php
/** Student (and parent) screen: the signed-in student's own attendance only. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) : ?>
	<section class="erp-card erp-empty">
		<h1>Student record not found</h1>
		<p>Your login works, but the school office has not linked a student record to it. Please contact the school office.</p>
	</section>
	<?php
	return;
endif;

$erp_statuses = mags_erp_statuses();
$erp_today    = mags_erp_today();
$erp_class    = mags_erp_get_class( (int) $erp_student->class_id );
$erp_session  = mags_erp_session_range( $erp_today );
$erp_counts   = mags_erp_student_summary( (int) $erp_student->id, $erp_session['start'], $erp_today );
$erp_marked   = array_sum( $erp_counts );
$erp_percent  = mags_erp_percentage( $erp_counts );

// Month shown in the calendar: from the month the student was added up to the current month.
$erp_this_month  = substr( $erp_today, 0, 7 );
$erp_first_month = min( $erp_this_month, substr( (string) $erp_student->created_at, 0, 7 ), substr( $erp_session['start'], 0, 7 ) );
$erp_month       = isset( $_GET['month'] ) && is_string( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : $erp_this_month; // phpcs:ignore WordPress.Security.NonceVerification
$erp_month       = mags_erp_valid_date( $erp_month . '-01' ) && $erp_month <= $erp_this_month && $erp_month >= $erp_first_month ? $erp_month : $erp_this_month;
$erp_month_start = $erp_month . '-01';
$erp_month_days  = (int) gmdate( 't', strtotime( $erp_month_start ) );
$erp_month_end   = $erp_month . '-' . sprintf( '%02d', $erp_month_days );
$erp_prev_month  = gmdate( 'Y-m', strtotime( $erp_month_start . ' -1 month' ) );
$erp_next_month  = gmdate( 'Y-m', strtotime( $erp_month_start . ' +1 month' ) );
$erp_days        = mags_erp_student_days( (int) $erp_student->id, $erp_month_start, $erp_month_end );
$erp_month_tally = array_count_values( $erp_days );
$erp_month_pct   = mags_erp_percentage( $erp_month_tally );

$erp_week_start = (int) get_option( 'start_of_week', 1 );
$erp_blank_days = ( (int) gmdate( 'w', strtotime( $erp_month_start ) ) - $erp_week_start + 7 ) % 7;
$erp_today_mark = $erp_days[ $erp_today ] ?? '';
?>
<header class="erp-head">
	<p class="erp-eyebrow">My attendance</p>
	<h1><?php echo esc_html( $erp_student->full_name ); ?></h1>
	<p class="erp-head__meta"><span class="erp-chip erp-chip--blue"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></span><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-chip">Roll no. <?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><span class="erp-chip">Adm. no. <?php echo esc_html( $erp_student->admission_no ); ?></span></p>
</header>

<section class="erp-card erp-summary" aria-labelledby="erp-summary-title">
	<div class="erp-ring" style="--erp-pct: <?php echo esc_attr( null === $erp_percent ? 0 : $erp_percent ); ?>;" role="img" aria-label="<?php echo esc_attr( null === $erp_percent ? 'No attendance marked yet' : $erp_percent . ' percent attendance' ); ?>">
		<div><strong><?php echo esc_html( null === $erp_percent ? '–' : $erp_percent . '%' ); ?></strong><span>present</span></div>
	</div>
	<div class="erp-summary__text">
		<h2 id="erp-summary-title">Session <?php echo esc_html( $erp_session['label'] ); ?></h2>
		<?php if ( $erp_marked ) : ?>
			<p>Present on <strong><?php echo esc_html( $erp_counts['P'] ); ?></strong> of <strong><?php echo esc_html( $erp_marked ); ?></strong> school days marked since <?php echo esc_html( mags_erp_format_date( $erp_session['start'] ) ); ?>.</p>
		<?php else : ?>
			<p>No attendance has been marked yet in this session.</p>
		<?php endif; ?>
		<?php if ( $erp_today_mark ) : ?><p><span class="erp-chip erp-chip--status-<?php echo esc_attr( $erp_today_mark ); ?>">Today: <?php echo esc_html( $erp_statuses[ $erp_today_mark ] ); ?></span></p><?php endif; ?>
	</div>
	<dl class="erp-tally">
		<div class="erp-tally__item erp-tally__item--P"><dt>Present</dt><dd><?php echo esc_html( $erp_counts['P'] ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--A"><dt>Absent</dt><dd><?php echo esc_html( $erp_counts['A'] ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--L"><dt>Leave</dt><dd><?php echo esc_html( $erp_counts['L'] ); ?></dd></div>
	</dl>
	<p class="erp-hint">Percentage = days present ÷ days marked. Leave is counted as not present.</p>
</section>

<section class="erp-card" aria-labelledby="erp-month-title">
	<div class="erp-month">
		<?php if ( $erp_prev_month >= $erp_first_month ) : ?><a class="erp-iconbtn" href="<?php echo esc_url( mags_erp_url( 'home', array( 'month' => $erp_prev_month ) ) ); ?>" aria-label="Previous month">‹</a><?php else : ?><span class="erp-iconbtn is-disabled" aria-hidden="true">‹</span><?php endif; ?>
		<h2 id="erp-month-title"><?php echo esc_html( mags_erp_format_date( $erp_month_start, 'F Y' ) ); ?></h2>
		<?php if ( $erp_next_month <= $erp_this_month ) : ?><a class="erp-iconbtn" href="<?php echo esc_url( mags_erp_url( 'home', array( 'month' => $erp_next_month ) ) ); ?>" aria-label="Next month">›</a><?php else : ?><span class="erp-iconbtn is-disabled" aria-hidden="true">›</span><?php endif; ?>
	</div>
	<p class="erp-month__note">
		<?php if ( $erp_days ) : ?>
			<?php echo esc_html( sprintf( 'Present %d of %d days marked this month (%s%%).', $erp_month_tally['P'] ?? 0, count( $erp_days ), $erp_month_pct ) ); ?>
		<?php else : ?>
			No attendance marked in this month.
		<?php endif; ?>
	</p>

	<ol class="erp-cal" aria-label="<?php echo esc_attr( 'Attendance for ' . mags_erp_format_date( $erp_month_start, 'F Y' ) ); ?>">
		<?php for ( $erp_i = 0; $erp_i < 7; $erp_i++ ) : ?><li class="erp-cal__dow" aria-hidden="true"><?php echo esc_html( mysql2date( 'D', gmdate( 'Y-m-d', strtotime( 'Sunday +' . ( $erp_week_start + $erp_i ) . ' days' ) ) . ' 00:00:00' ) ); ?></li><?php endfor; ?>
		<?php for ( $erp_i = 0; $erp_i < $erp_blank_days; $erp_i++ ) : ?><li class="erp-cal__blank" aria-hidden="true"></li><?php endfor; ?>
		<?php for ( $erp_day = 1; $erp_day <= $erp_month_days; $erp_day++ ) : ?>
			<?php
			$erp_date = $erp_month . '-' . sprintf( '%02d', $erp_day );
			$erp_mark = $erp_days[ $erp_date ] ?? '';
			?>
			<li class="erp-cal__day<?php echo $erp_mark ? ' erp-cal__day--' . esc_attr( $erp_mark ) : ''; ?><?php echo $erp_date === $erp_today ? ' is-today' : ''; ?>">
				<span aria-hidden="true"><?php echo esc_html( $erp_day ); ?></span>
				<?php if ( $erp_mark ) : ?><b aria-hidden="true"><?php echo esc_html( $erp_mark ); ?></b><?php endif; ?>
				<span class="erp-sr"><?php echo esc_html( mags_erp_format_date( $erp_date, 'j F' ) . ': ' . ( $erp_mark ? $erp_statuses[ $erp_mark ] : 'not marked' ) ); ?></span>
			</li>
		<?php endfor; ?>
	</ol>
	<p class="erp-legend" aria-hidden="true"><span class="erp-dot erp-dot--P">P</span> Present <span class="erp-dot erp-dot--A">A</span> Absent <span class="erp-dot erp-dot--L">L</span> Leave</p>
</section>
