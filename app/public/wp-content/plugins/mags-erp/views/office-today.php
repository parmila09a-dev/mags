<?php
/** Office: one day at a glance. Children absent, classes not marked, teachers away, fees received and fees still pending. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_today = mags_erp_today();
$erp_date  = mags_erp_get_text( 'date' );
$erp_date  = mags_erp_valid_date( $erp_date ) && $erp_date <= $erp_today ? $erp_date : $erp_today;
$erp_is_today = $erp_date === $erp_today;
$erp_holiday  = mags_erp_holiday_on( $erp_date );

list( $erp_classes, $erp_totals, $erp_absent ) = mags_erp_today_attendance( $erp_date );
$erp_unmarked = array_values( array_filter( $erp_classes, static function ( $erp_line ) { return $erp_line['students'] > 0 && $erp_line['marked'] < $erp_line['students']; } ) );
$erp_on_leave = mags_erp_approved_leave_on( wp_list_pluck( $erp_absent, 'id' ), $erp_date );
$erp_away     = mags_erp_teachers_away( $erp_date );
$erp_subs     = mags_erp_substitutions_on( $erp_date );
$erp_payments = mags_erp_payments_on( $erp_date );
$erp_received = 0.0;
$erp_by_mode  = array();
foreach ( $erp_payments as $erp_payment ) {
	$erp_received                        += (float) $erp_payment->amount;
	$erp_by_mode[ $erp_payment->mode ]    = ( $erp_by_mode[ $erp_payment->mode ] ?? 0.0 ) + (float) $erp_payment->amount;
}
$erp_modes   = mags_erp_payment_modes();
$erp_pending = mags_erp_pending_fees_overview();
$erp_names   = mags_erp_class_teacher_names();
$erp_day     = $erp_is_today ? 'today' : 'on ' . mags_erp_format_date( $erp_date, 'j M' );
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1><?php echo $erp_is_today ? 'Today at school' : esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></h1>
	<p class="erp-head__meta"><span class="erp-chip"><?php echo esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></span><?php if ( $erp_holiday ) : ?><span class="erp-chip erp-chip--blue">Holiday: <?php echo esc_html( $erp_holiday->title ); ?></span><?php endif; ?></p>
</header>

<form class="erp-card erp-filter erp-filter--one" method="get" action="<?php echo esc_url( mags_erp_url( 'today' ) ); ?>" data-erp-autosubmit>
	<?php mags_erp_route_field( 'today' ); ?>
	<p class="erp-field"><label for="erp_today_date">Day</label><input id="erp_today_date" name="date" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( $erp_date ); ?>" required></p>
	<button class="erp-btn erp-btn--navy" type="submit">Show</button>
</form>

<section class="erp-tiles" aria-label="<?php echo esc_attr( 'The school ' . $erp_day ); ?>">
	<a class="erp-tile erp-tile--orange" href="#erp-absent-title"><strong><?php echo esc_html( number_format_i18n( $erp_totals['A'] ) ); ?></strong><span>Absent <?php echo esc_html( $erp_day ); ?></span></a>
	<a class="erp-tile erp-tile--green" href="#erp-received-title"><strong><?php echo esc_html( mags_erp_money( $erp_received ) ); ?></strong><span>Fees received <?php echo esc_html( $erp_day ); ?></span></a>
	<a class="erp-tile erp-tile--blue" href="#erp-pending-title"><strong><?php echo esc_html( mags_erp_money( $erp_pending['left'] ) ); ?></strong><span>Fees still pending</span></a>
</section>

<section class="erp-card" aria-labelledby="erp-att-title">
	<h2 id="erp-att-title">Attendance</h2>
	<?php if ( $erp_holiday ) : ?>
		<p class="erp-hint erp-hint--lead">No school <?php echo esc_html( $erp_day ); ?> (<?php echo esc_html( $erp_holiday->title ); ?>). Attendance is not marked on a holiday.</p>
	<?php else : ?>
		<dl class="erp-tally">
			<div class="erp-tally__item erp-tally__item--P"><dt>Present</dt><dd><?php echo esc_html( $erp_totals['P'] ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--A"><dt>Absent</dt><dd><?php echo esc_html( $erp_totals['A'] ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--L"><dt>Leave</dt><dd><?php echo esc_html( $erp_totals['L'] ); ?></dd></div>
		</dl>
		<p class="erp-hint"><?php echo esc_html( sprintf( '%1$d of %2$d students marked. %3$s', $erp_totals['marked'], $erp_totals['students'], $erp_unmarked ? sprintf( _n( '%d class is not fully marked yet.', '%d classes are not fully marked yet.', count( $erp_unmarked ), 'mags-erp' ), count( $erp_unmarked ) ) : 'Every class is marked.' ) ); ?></p>
		<?php if ( $erp_classes ) : ?>
			<div class="erp-table-wrap">
				<table class="erp-table">
					<caption class="erp-sr">Attendance of each class</caption>
					<thead><tr><th scope="col">Class</th><th scope="col" class="erp-num">Students</th><th scope="col" class="erp-num">Present</th><th scope="col" class="erp-num">Absent</th><th scope="col" class="erp-num">Leave</th><th scope="col">Marked</th></tr></thead>
					<tbody>
						<?php foreach ( $erp_classes as $erp_line ) : if ( ! $erp_line['students'] && ! $erp_line['marked'] ) { continue; } $erp_missing = $erp_line['students'] - $erp_line['marked']; ?>
							<tr>
								<th scope="row"><?php echo esc_html( mags_erp_class_label( $erp_line['class'] ) ); ?></th>
								<td class="erp-num"><?php echo esc_html( $erp_line['students'] ); ?></td>
								<td class="erp-num"><?php echo esc_html( $erp_line['P'] ); ?></td>
								<td class="erp-num"><strong><?php echo esc_html( $erp_line['A'] ); ?></strong></td>
								<td class="erp-num"><?php echo esc_html( $erp_line['L'] ); ?></td>
								<td><?php if ( $erp_missing > 0 ) : $erp_class_teacher = $erp_names[ (int) $erp_line['class']->id ][ (int) $erp_line['class']->class_teacher_id ] ?? ''; ?><span class="erp-chip erp-chip--orange"><?php echo esc_html( $erp_line['marked'] ? $erp_missing . ' not marked' : 'Not marked' ); ?></span><?php if ( '' !== $erp_class_teacher ) : ?> <small><?php echo esc_html( $erp_class_teacher ); ?></small><?php endif; ?><?php else : ?><span class="erp-chip erp-chip--green">Done</span><?php endif; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>

<section aria-labelledby="erp-absent-title">
	<h2 id="erp-absent-title" class="erp-section-title">Children absent <?php echo esc_html( $erp_day ); ?> <span class="erp-count"><?php echo esc_html( count( $erp_absent ) ); ?></span></h2>
	<?php if ( ! $erp_absent ) : ?>
		<p class="erp-card erp-empty"><?php echo esc_html( $erp_totals['marked'] ? 'No child is marked absent.' : 'Attendance has not been marked yet.' ); ?></p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_absent as $erp_student ) : ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_student->full_name ); ?></strong>
						<span><?php echo esc_html( $erp_student->class_label ); ?> · adm. no. <?php echo esc_html( $erp_student->admission_no ); ?></span>
						<?php if ( isset( $erp_on_leave[ (int) $erp_student->id ] ) ) : ?><span class="erp-row__chips"><span class="erp-chip erp-chip--orange">Leave was approved</span></span><?php endif; ?>
					</div>
					<div class="erp-row__actions">
						<?php if ( '' !== $erp_student->phone ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( 'tel:' . $erp_student->phone ); ?>">Call <?php echo esc_html( $erp_student->phone ); ?></a><?php endif; ?>
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'profile', array( 'student' => (int) $erp_student->id ) ) ); ?>">Profile<span class="erp-sr"> of <?php echo esc_html( $erp_student->full_name ); ?></span></a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>

<section class="erp-card" aria-labelledby="erp-away-title">
	<h2 id="erp-away-title">Teachers away <span class="erp-count"><?php echo esc_html( count( $erp_away ) ); ?></span></h2>
	<?php if ( ! $erp_away ) : ?>
		<p class="erp-hint erp-hint--lead">No teacher is on leave or marked absent <?php echo esc_html( $erp_day ); ?>.</p>
	<?php else : ?>
		<ul class="erp-alerts">
			<?php foreach ( $erp_away as $erp_teacher_id => $erp_why ) : $erp_covered = count( array_filter( $erp_subs, static function ( $erp_sub ) use ( $erp_teacher_id ) { return (int) $erp_sub->absent_teacher_id === (int) $erp_teacher_id; } ) ); ?>
				<li><span><strong><?php echo esc_html( mags_erp_teacher_name( (int) $erp_teacher_id ) ); ?></strong> · <?php echo esc_html( $erp_why ); ?><?php echo $erp_covered ? esc_html( ' · ' . sprintf( _n( '%d substitute given', '%d substitutes given', $erp_covered, 'mags-erp' ), $erp_covered ) ) : ''; ?></span></li>
			<?php endforeach; ?>
		</ul>
		<p class="erp-card__actions"><a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'substitutes', array( 'date' => $erp_date ) ) ); ?>">Give their classes to other teachers</a></p>
	<?php endif; ?>
</section>

<section class="erp-card" aria-labelledby="erp-received-title">
	<h2 id="erp-received-title">Fees received <?php echo esc_html( $erp_day ); ?></h2>
	<p class="erp-big"><strong><?php echo esc_html( mags_erp_money( $erp_received ) ); ?></strong> <span><?php echo esc_html( sprintf( _n( 'from %d payment', 'from %d payments', count( $erp_payments ), 'mags-erp' ), count( $erp_payments ) ) ); ?></span></p>
	<?php if ( $erp_by_mode ) : ?>
		<p class="erp-row__chips"><?php foreach ( $erp_by_mode as $erp_mode => $erp_sum ) : ?><span class="erp-chip"><?php echo esc_html( ( $erp_modes[ $erp_mode ] ?? $erp_mode ) . ': ' . mags_erp_money( $erp_sum ) ); ?></span><?php endforeach; ?></p>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_payments as $erp_payment ) : ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_money( $erp_payment->amount ) ); ?> · <?php echo esc_html( $erp_payment->student_name ); ?></strong>
						<span><?php echo esc_html( $erp_payment->class_label ); ?> · <?php echo esc_html( $erp_modes[ $erp_payment->mode ] ?? $erp_payment->mode ); ?> · <?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?> · recorded by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_payment->recorded_by ) ); ?></span>
					</div>
					<div class="erp-row__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'receipt', array( 'id' => (int) $erp_payment->id ) ) ); ?>">Receipt<span class="erp-sr"> <?php echo esc_html( mags_erp_receipt_no( $erp_payment ) ); ?></span></a></div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<p class="erp-hint">Payments are counted on the day they were received (the date typed with the payment). Cancelled payments are not counted.</p>
</section>

<section class="erp-card" aria-labelledby="erp-pending-title">
	<h2 id="erp-pending-title">Fees still pending</h2>
	<dl class="erp-tally erp-tally--money">
		<div class="erp-tally__item erp-tally__item--L"><dt>Left to collect</dt><dd><?php echo esc_html( mags_erp_money( $erp_pending['left'] ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--A"><dt>Overdue now</dt><dd><?php echo esc_html( mags_erp_money( $erp_pending['overdue'] ) ); ?></dd></div>
	</dl>
	<p class="erp-hint"><?php echo esc_html( sprintf( '%1$d students still owe fees; %2$d of them have a fee whose last date has passed. Students who paid in advance are not set against these amounts.', $erp_pending['owing'], $erp_pending['overdue_students'] ) ); ?></p>
	<p class="erp-card__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'reports', array( 'report' => 'fees', 'left' => 1 ) ) ); ?>">List of students who owe</a><?php if ( mags_erp_mail_enabled( 'fees' ) && $erp_pending['overdue_students'] ) : ?> <a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'fees' ) . '#erp-reminders' ); ?>">Send fee reminders</a><?php endif; ?></p>
</section>
