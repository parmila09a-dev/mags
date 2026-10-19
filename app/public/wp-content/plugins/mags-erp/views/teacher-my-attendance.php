<?php
/** Teacher: own attendance as the office marked it, and own leave requests to the office. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_teacher_id = (int) $ctx['user']->ID;
$erp_statuses   = mags_erp_statuses();
$erp_today      = mags_erp_today();
$erp_session    = mags_erp_session_range( $erp_today );
$erp_counts     = mags_erp_staff_summary( $erp_teacher_id, $erp_session['start'], $erp_today );
$erp_marked     = array_sum( $erp_counts );
$erp_percent    = mags_erp_percentage( $erp_counts );

$erp_this_month  = substr( $erp_today, 0, 7 );
$erp_first_month = substr( $erp_session['start'], 0, 7 );
$erp_month       = mags_erp_get_text( 'month' );
$erp_month       = preg_match( '/^\d{4}-\d{2}$/', $erp_month ) && mags_erp_valid_date( $erp_month . '-01' ) && $erp_month <= $erp_this_month && $erp_month >= $erp_first_month ? $erp_month : $erp_this_month;
$erp_month_start = $erp_month . '-01';
$erp_month_days  = (int) gmdate( 't', strtotime( $erp_month_start ) );
$erp_month_end   = $erp_month . '-' . sprintf( '%02d', $erp_month_days );
$erp_prev_month  = gmdate( 'Y-m', strtotime( $erp_month_start . ' -1 month' ) );
$erp_next_month  = gmdate( 'Y-m', strtotime( $erp_month_start . ' +1 month' ) );
$erp_days        = mags_erp_staff_days( $erp_teacher_id, $erp_month_start, $erp_month_end );
$erp_holidays    = mags_erp_holiday_dates( $erp_month_start, $erp_month_end );
$erp_week_start  = (int) get_option( 'start_of_week', 1 );
$erp_blank_days  = ( (int) gmdate( 'w', strtotime( $erp_month_start ) ) - $erp_week_start + 7 ) % 7;

$erp_leaves       = mags_erp_teacher_own_leaves( $erp_teacher_id );
$erp_leave_states = mags_erp_leave_statuses();
$erp_leave_states['pending'][0] = 'Waiting for the school office';
$erp_earliest     = gmdate( 'Y-m-d', strtotime( $erp_today . ' -' . MAGS_ERP_STAFF_LEAVE_BACK_DAYS . ' days' ) );
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>My attendance and leave</h1>
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
			<p>The school office has not marked your attendance yet in this session.</p>
		<?php endif; ?>
	</div>
	<dl class="erp-tally">
		<div class="erp-tally__item erp-tally__item--P"><dt>Present</dt><dd><?php echo esc_html( $erp_counts['P'] ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--A"><dt>Absent</dt><dd><?php echo esc_html( $erp_counts['A'] ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--L"><dt>Leave</dt><dd><?php echo esc_html( $erp_counts['L'] ); ?></dd></div>
	</dl>
	<p class="erp-hint">The school office marks teacher attendance. Percentage = days present ÷ days marked. Leave is counted as not present. Holidays are not counted.</p>
</section>

<section class="erp-card" aria-labelledby="erp-month-title">
	<div class="erp-month">
		<?php if ( $erp_prev_month >= $erp_first_month ) : ?><a class="erp-iconbtn" href="<?php echo esc_url( mags_erp_url( 'my-attendance', array( 'month' => $erp_prev_month ) ) ); ?>" aria-label="Previous month">‹</a><?php else : ?><span class="erp-iconbtn is-disabled" aria-hidden="true">‹</span><?php endif; ?>
		<h2 id="erp-month-title"><?php echo esc_html( mags_erp_format_date( $erp_month_start, 'F Y' ) ); ?></h2>
		<?php if ( $erp_next_month <= $erp_this_month ) : ?><a class="erp-iconbtn" href="<?php echo esc_url( mags_erp_url( 'my-attendance', array( 'month' => $erp_next_month ) ) ); ?>" aria-label="Next month">›</a><?php else : ?><span class="erp-iconbtn is-disabled" aria-hidden="true">›</span><?php endif; ?>
	</div>
	<p class="erp-month__note"><?php echo $erp_days ? esc_html( sprintf( 'Present %d of %d days marked this month.', array_count_values( $erp_days )['P'] ?? 0, count( $erp_days ) ) ) : 'No attendance marked in this month.'; ?></p>
	<ol class="erp-cal" aria-label="<?php echo esc_attr( 'My attendance for ' . mags_erp_format_date( $erp_month_start, 'F Y' ) ); ?>">
		<?php for ( $erp_i = 0; $erp_i < 7; $erp_i++ ) : ?><li class="erp-cal__dow" aria-hidden="true"><?php echo esc_html( mysql2date( 'D', gmdate( 'Y-m-d', strtotime( 'Sunday +' . ( $erp_week_start + $erp_i ) . ' days' ) ) . ' 00:00:00' ) ); ?></li><?php endfor; ?>
		<?php for ( $erp_i = 0; $erp_i < $erp_blank_days; $erp_i++ ) : ?><li class="erp-cal__blank" aria-hidden="true"></li><?php endfor; ?>
		<?php for ( $erp_day = 1; $erp_day <= $erp_month_days; $erp_day++ ) : ?>
			<?php
			$erp_date = $erp_month . '-' . sprintf( '%02d', $erp_day );
			$erp_mark = isset( $erp_holidays[ $erp_date ] ) ? 'H' : ( $erp_days[ $erp_date ] ?? '' );
			?>
			<li class="erp-cal__day<?php echo $erp_mark ? ' erp-cal__day--' . esc_attr( $erp_mark ) : ''; ?><?php echo $erp_date === $erp_today ? ' is-today' : ''; ?>">
				<span aria-hidden="true"><?php echo esc_html( $erp_day ); ?></span>
				<?php if ( $erp_mark ) : ?><b aria-hidden="true"><?php echo esc_html( $erp_mark ); ?></b><?php endif; ?>
				<span class="erp-sr"><?php echo esc_html( mags_erp_format_date( $erp_date, 'j F' ) . ': ' . ( 'H' === $erp_mark ? 'Holiday: ' . $erp_holidays[ $erp_date ] : ( $erp_mark ? $erp_statuses[ $erp_mark ] : 'not marked' ) ) ); ?></span>
			</li>
		<?php endfor; ?>
	</ol>
	<p class="erp-legend" aria-hidden="true"><span class="erp-dot erp-dot--P">P</span> Present <span class="erp-dot erp-dot--A">A</span> Absent <span class="erp-dot erp-dot--L">L</span> Leave <span class="erp-legend__pair"><span class="erp-dot erp-dot--H">H</span> Holiday</span></p>
</section>

<section id="erp-my-leave" class="erp-card" aria-labelledby="erp-leave-form-title">
	<h2 id="erp-leave-form-title">Ask for leave</h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'my-attendance' ) ); ?>">
		<?php mags_erp_form_fields( 'ask_staff_leave' ); ?>
		<div class="erp-form__grid">
			<p class="erp-field"><label for="erp_leave_from">First day</label><input id="erp_leave_from" name="from_date" type="date" min="<?php echo esc_attr( $erp_earliest ); ?>" value="<?php echo esc_attr( $erp_today ); ?>" required></p>
			<p class="erp-field"><label for="erp_leave_to">Last day <span>(empty for one day)</span></label><input id="erp_leave_to" name="to_date" type="date" min="<?php echo esc_attr( $erp_earliest ); ?>"></p>
		</div>
		<p class="erp-field"><label for="erp_leave_reason">Reason</label><textarea id="erp_leave_reason" name="reason" rows="3" maxlength="1000" required></textarea></p>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Send request</button></div>
	</form>
	<p class="erp-hint">The school office answers the request. You can withdraw it while it is waiting.</p>
</section>

<section aria-labelledby="erp-leave-list-title">
	<h2 id="erp-leave-list-title" class="erp-section-title">My leave requests <span class="erp-count"><?php echo esc_html( count( $erp_leaves ) ); ?></span></h2>
	<?php if ( ! $erp_leaves ) : ?>
		<p class="erp-card erp-empty">You have not asked for leave yet.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_leaves as $erp_leave ) : $erp_state = $erp_leave_states[ $erp_leave->status ] ?? array( $erp_leave->status, '' ); ?>
				<li class="erp-row<?php echo 'withdrawn' === $erp_leave->status ? ' erp-row--off' : ''; ?>">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_leave_dates( $erp_leave ) ); ?></strong>
						<span><?php echo esc_html( $erp_leave->reason ); ?></span>
						<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span></span>
						<?php if ( in_array( $erp_leave->status, array( 'approved', 'rejected' ), true ) ) : ?>
							<span>School office · <?php echo esc_html( mags_erp_format_datetime( $erp_leave->decided_at ) ); ?></span>
							<?php if ( '' !== trim( $erp_leave->decision_note ) ) : ?><span class="erp-quote erp-quote--teacher"><?php echo esc_html( $erp_leave->decision_note ); ?></span><?php endif; ?>
						<?php endif; ?>
					</div>
					<?php if ( 'pending' === $erp_leave->status ) : ?>
						<div class="erp-row__actions">
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'my-attendance' ) ); ?>" data-erp-confirm="Withdraw this leave request?">
								<?php mags_erp_form_fields( 'withdraw_staff_leave' ); ?>
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
