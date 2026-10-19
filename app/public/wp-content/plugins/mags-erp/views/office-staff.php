<?php
/** Office: mark the teachers' attendance for one day and see each teacher's month. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_today    = mags_erp_today();
$erp_statuses = mags_erp_statuses();
$erp_teachers = mags_erp_get_teachers();
$erp_date     = mags_erp_get_text( 'date' );
$erp_date     = mags_erp_valid_date( $erp_date ) && $erp_date <= $erp_today ? $erp_date : $erp_today;
$erp_marks    = mags_erp_staff_attendance_on( $erp_date );
$erp_holiday  = mags_erp_holiday_on( $erp_date );
$erp_on_leave = mags_erp_staff_on_leave( $erp_date );
$erp_saved    = (bool) array_intersect_key( $erp_marks, array_flip( wp_list_pluck( $erp_teachers, 'ID' ) ) );

// The month of the chosen date, for the summary below.
$erp_from   = substr( $erp_date, 0, 8 ) . '01';
$erp_to     = min( $erp_today, gmdate( 'Y-m-t', strtotime( $erp_from ) ) );
$erp_report = mags_erp_staff_report( $erp_from, $erp_to );
$erp_wait   = mags_erp_pending_staff_leave_count();
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Teacher attendance</h1>
	<?php if ( $erp_wait ) : ?><p class="erp-head__meta"><a class="erp-chip erp-chip--orange" href="<?php echo esc_url( mags_erp_url( 'staff-leave' ) ); ?>"><?php echo esc_html( sprintf( _n( '%d leave request waiting', '%d leave requests waiting', $erp_wait, 'mags-erp' ), $erp_wait ) ); ?></a></p><?php endif; ?>
</header>

<?php if ( ! $erp_teachers ) : ?>
	<p class="erp-card erp-empty">No teachers yet. <a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'teachers' ) ); ?>">Add teachers</a></p>
<?php else : ?>
	<form class="erp-card erp-filter erp-filter--one" method="get" action="<?php echo esc_url( mags_erp_url( 'staff' ) ); ?>" data-erp-autosubmit>
		<?php mags_erp_route_field( 'staff' ); ?>
		<p class="erp-field"><label for="erp_staff_date">Date</label><input id="erp_staff_date" name="date" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( $erp_date ); ?>" required></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<?php if ( $erp_holiday ) : ?>
		<section class="erp-card erp-empty erp-holiday">
			<h2><?php echo esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></h2>
			<p><span class="erp-chip erp-chip--blue">Holiday: <?php echo esc_html( $erp_holiday->title ); ?></span></p>
			<p>Attendance is not marked on a holiday, and the day is not counted in the percentage. Choose another date above.</p>
		</section>
	<?php else : ?>
		<form class="erp-sheet" method="post" action="<?php echo esc_url( mags_erp_url( 'staff' ) ); ?>" data-erp-sheet>
			<?php mags_erp_form_fields( 'save_staff_attendance' ); ?>
			<input type="hidden" name="date" value="<?php echo esc_attr( $erp_date ); ?>">
			<div class="erp-sheet__head">
				<div>
					<h2><?php echo esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></h2>
					<p>
						<?php if ( $erp_saved ) : ?><span class="erp-chip erp-chip--green">Already saved · you can correct it</span>
						<?php else : ?><span class="erp-chip erp-chip--orange">Not saved yet</span> Everyone starts as Present<?php echo $erp_on_leave ? ', and teachers with approved leave as Leave' : ''; ?>. Change the absent teachers, then save.<?php endif; ?>
					</p>
				</div>
				<button class="erp-btn erp-btn--ghost erp-btn--sm" type="button" data-erp-all-present hidden>All present</button>
			</div>
			<p class="erp-legend" aria-hidden="true"><span class="erp-dot erp-dot--P">P</span> Present <span class="erp-dot erp-dot--A">A</span> Absent <span class="erp-dot erp-dot--L">L</span> Leave</p>
			<ol class="erp-marks">
				<?php foreach ( $erp_teachers as $erp_teacher ) : $erp_current = $erp_marks[ (int) $erp_teacher->ID ] ?? ( isset( $erp_on_leave[ (int) $erp_teacher->ID ] ) ? 'L' : 'P' ); ?>
					<li class="erp-mark">
						<div class="erp-mark__who"><strong><?php echo esc_html( $erp_teacher->display_name ); ?><?php if ( isset( $erp_on_leave[ (int) $erp_teacher->ID ] ) ) : ?> <span class="erp-chip erp-chip--orange">Leave approved</span><?php endif; ?></strong></div>
						<fieldset class="erp-seg erp-seg--status">
							<legend class="erp-sr">Attendance of <?php echo esc_html( $erp_teacher->display_name ); ?></legend>
							<?php foreach ( $erp_statuses as $erp_code => $erp_label ) : ?>
								<label class="erp-seg__item erp-seg__item--<?php echo esc_attr( $erp_code ); ?>"><input type="radio" name="status[<?php echo esc_attr( $erp_teacher->ID ); ?>]" value="<?php echo esc_attr( $erp_code ); ?>" aria-label="<?php echo esc_attr( $erp_label ); ?>" <?php checked( $erp_current, $erp_code ); ?>><span aria-hidden="true"><?php echo esc_html( $erp_code ); ?></span></label>
							<?php endforeach; ?>
						</fieldset>
					</li>
				<?php endforeach; ?>
			</ol>
			<div class="erp-savebar">
				<p class="erp-savebar__counts" data-erp-counts aria-live="polite"><?php echo esc_html( sprintf( _n( '%s teacher', '%s teachers', count( $erp_teachers ), 'mags-erp' ), number_format_i18n( count( $erp_teachers ) ) ) ); ?></p>
				<button class="erp-btn erp-btn--orange" type="submit">Save attendance</button>
			</div>
		</form>
	<?php endif; ?>

	<section class="erp-card" aria-labelledby="erp-staff-month-title">
		<h2 id="erp-staff-month-title"><?php echo esc_html( mags_erp_format_date( $erp_from, 'F Y' ) ); ?></h2>
		<div class="erp-table-wrap">
			<table class="erp-table">
				<caption class="erp-sr">Teacher attendance in <?php echo esc_html( mags_erp_format_date( $erp_from, 'F Y' ) ); ?></caption>
				<thead><tr><th scope="col">Teacher</th><th scope="col" class="erp-num">Present</th><th scope="col" class="erp-num">Absent</th><th scope="col" class="erp-num">Leave</th><th scope="col" class="erp-num">Present %</th></tr></thead>
				<tbody>
					<?php foreach ( $erp_teachers as $erp_teacher ) : $erp_own = array_merge( array( 'P' => 0, 'A' => 0, 'L' => 0 ), $erp_report[ (int) $erp_teacher->ID ] ?? array() ); $erp_pct = mags_erp_percentage( $erp_own ); ?>
						<tr><th scope="row"><?php echo esc_html( $erp_teacher->display_name ); ?></th><td class="erp-num"><?php echo esc_html( $erp_own['P'] ); ?></td><td class="erp-num"><?php echo esc_html( $erp_own['A'] ); ?></td><td class="erp-num"><?php echo esc_html( $erp_own['L'] ); ?></td><td class="erp-num"><strong><?php echo esc_html( null === $erp_pct ? '–' : $erp_pct . '%' ); ?></strong></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="erp-hint">The month of the date chosen above, up to today. Percentage = days present ÷ days marked. Leave is counted as not present; holidays are not counted. Choose a date in another month to see that month.</p>
	</section>
<?php endif; ?>
