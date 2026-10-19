<?php
/** Office: reports of attendance, exam marks and fees, each with a CSV file for Excel. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_kinds   = array( 'attendance' => 'Attendance', 'marks' => 'Marks', 'fees' => 'Fees' );
$erp_report  = mags_erp_get_text( 'report' );
$erp_report  = isset( $erp_kinds[ $erp_report ] ) ? $erp_report : 'attendance';
$erp_classes = mags_erp_get_classes();
$erp_class   = mags_erp_get_class( mags_erp_get_int( 'class' ) );
$erp_tabs    = array();
foreach ( $erp_kinds as $erp_kind => $erp_kind_label ) { $erp_tabs[ mags_erp_url( 'reports', array( 'report' => $erp_kind ) ) ] = $erp_kind_label; }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Reports</h1>
</header>

<?php mags_erp_tabs( $erp_tabs, mags_erp_url( 'reports', array( 'report' => $erp_report ) ), 'Kind of report' ); ?>

<?php if ( 'attendance' === $erp_report ) : ?>
	<?php
	list( $erp_from, $erp_to ) = mags_erp_report_range( mags_erp_get_text( 'from' ), mags_erp_get_text( 'to' ) );
	$erp_lines  = $erp_class ? mags_erp_report_attendance( (int) $erp_class->id, $erp_from, $erp_to ) : mags_erp_report_attendance_classes( $erp_from, $erp_to );
	$erp_export = mags_erp_url( 'export', array( 'report' => 'attendance', 'class' => $erp_class ? (int) $erp_class->id : '', 'from' => $erp_from, 'to' => $erp_to ) );
	?>
	<form class="erp-card erp-filter erp-filter--report" method="get" action="<?php echo esc_url( mags_erp_url( 'reports' ) ); ?>">
		<?php mags_erp_route_field( 'reports' ); ?>
		<input type="hidden" name="report" value="attendance">
		<p class="erp-field"><label for="erp_rep_class">Class</label><select id="erp_rep_class" name="class"><?php mags_erp_class_options( $erp_classes, $erp_class ? (int) $erp_class->id : 0, 'All classes' ); ?></select></p>
		<p class="erp-field"><label for="erp_rep_from">From</label><input id="erp_rep_from" name="from" type="date" max="<?php echo esc_attr( mags_erp_today() ); ?>" value="<?php echo esc_attr( $erp_from ); ?>" required></p>
		<p class="erp-field"><label for="erp_rep_to">To</label><input id="erp_rep_to" name="to" type="date" max="<?php echo esc_attr( mags_erp_today() ); ?>" value="<?php echo esc_attr( $erp_to ); ?>" required></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<section class="erp-card" aria-labelledby="erp-rep-title">
		<div class="erp-work__head">
			<div>
				<h2 id="erp-rep-title"><?php echo esc_html( 'Attendance: ' . ( $erp_class ? mags_erp_class_label( $erp_class ) : 'all classes' ) ); ?></h2>
				<p><?php echo esc_html( mags_erp_format_date( $erp_from ) . ' to ' . mags_erp_format_date( $erp_to ) ); ?></p>
			</div>
			<?php if ( $erp_lines ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( $erp_export ); ?>">Download CSV</a><?php endif; ?>
		</div>
		<?php if ( ! $erp_lines ) : ?>
			<p class="erp-empty"><?php echo $erp_class ? 'This class has no students yet.' : 'No classes yet.'; ?></p>
		<?php elseif ( $erp_class ) : ?>
			<div class="erp-table-wrap">
				<table class="erp-table">
					<thead><tr><th scope="col">Roll</th><th scope="col">Student</th><th scope="col" class="erp-num">Present</th><th scope="col" class="erp-num">Absent</th><th scope="col" class="erp-num">Leave</th><th scope="col" class="erp-num">Present %</th></tr></thead>
					<tbody>
						<?php foreach ( $erp_lines as $erp_line ) : ?>
							<tr><td><?php echo esc_html( $erp_line['student']->roll_no ); ?></td><th scope="row"><?php echo esc_html( $erp_line['student']->full_name ); ?><small><?php echo esc_html( $erp_line['student']->admission_no ); ?></small></th><td class="erp-num"><?php echo esc_html( $erp_line['P'] ); ?></td><td class="erp-num"><?php echo esc_html( $erp_line['A'] ); ?></td><td class="erp-num"><?php echo esc_html( $erp_line['L'] ); ?></td><td class="erp-num"><strong><?php echo esc_html( null === $erp_line['percent'] ? '–' : $erp_line['percent'] . '%' ); ?></strong></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="erp-table-wrap">
				<table class="erp-table">
					<thead><tr><th scope="col">Class</th><th scope="col" class="erp-num">Students</th><th scope="col" class="erp-num">Present</th><th scope="col" class="erp-num">Absent</th><th scope="col" class="erp-num">Leave</th><th scope="col" class="erp-num">Present %</th></tr></thead>
					<tbody>
						<?php foreach ( $erp_lines as $erp_line ) : ?>
							<tr><th scope="row"><a href="<?php echo esc_url( mags_erp_url( 'reports', array( 'report' => 'attendance', 'class' => (int) $erp_line['class']->id, 'from' => $erp_from, 'to' => $erp_to ) ) ); ?>"><?php echo esc_html( mags_erp_class_label( $erp_line['class'] ) ); ?></a></th><td class="erp-num"><?php echo esc_html( $erp_line['class']->student_count ); ?></td><td class="erp-num"><?php echo esc_html( $erp_line['P'] ); ?></td><td class="erp-num"><?php echo esc_html( $erp_line['A'] ); ?></td><td class="erp-num"><?php echo esc_html( $erp_line['L'] ); ?></td><td class="erp-num"><strong><?php echo esc_html( null === $erp_line['percent'] ? '–' : $erp_line['percent'] . '%' ); ?></strong></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<p class="erp-hint">Percentage = days present ÷ days marked. Leave is counted as not present. Holidays of the school calendar are not counted. Present, Absent and Leave are counts of student-days in the chosen dates.</p>
	</section>

<?php elseif ( 'marks' === $erp_report ) : ?>
	<?php
	$erp_exams = mags_erp_get_exams();
	$erp_exam  = mags_erp_get_exam( mags_erp_get_int( 'exam' ) );
	if ( ! $erp_exam && 1 === count( $erp_exams ) ) { $erp_exam = $erp_exams[0]; }
	$erp_papers = array();
	$erp_lines  = array();
	if ( $erp_exam && $erp_class ) { list( $erp_papers, $erp_lines ) = mags_erp_report_marks( (int) $erp_exam->id, (int) $erp_class->id ); }
	?>
	<form class="erp-card erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'reports' ) ); ?>">
		<?php mags_erp_route_field( 'reports' ); ?>
		<input type="hidden" name="report" value="marks">
		<p class="erp-field"><label for="erp_rep_exam">Exam</label><select id="erp_rep_exam" name="exam" required><option value="">Choose exam</option><?php foreach ( $erp_exams as $erp_item ) : ?><option value="<?php echo esc_attr( $erp_item->id ); ?>" <?php selected( $erp_exam ? (int) $erp_exam->id : 0, (int) $erp_item->id ); ?>><?php echo esc_html( $erp_item->name ); ?></option><?php endforeach; ?></select></p>
		<p class="erp-field"><label for="erp_rep_class">Class</label><select id="erp_rep_class" name="class" required><?php mags_erp_class_options( $erp_classes, $erp_class ? (int) $erp_class->id : 0 ); ?></select></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<?php if ( ! $erp_exams ) : ?>
		<p class="erp-card erp-empty">No exams yet. <a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'exams' ) ); ?>">Add an exam</a></p>
	<?php elseif ( ! $erp_exam || ! $erp_class ) : ?>
		<p class="erp-card erp-empty">Choose an exam and a class to see the marks.</p>
	<?php elseif ( ! $erp_papers ) : ?>
		<p class="erp-card erp-empty"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?> has no paper in this exam.</p>
	<?php else : ?>
		<section class="erp-card" aria-labelledby="erp-rep-title">
			<div class="erp-work__head">
				<div>
					<h2 id="erp-rep-title"><?php echo esc_html( $erp_exam->name . ': ' . mags_erp_class_label( $erp_class ) ); ?></h2>
					<p><?php echo $erp_exam->published_at ? 'Results published' : 'Open for marks: students cannot see these results yet'; ?></p>
				</div>
				<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'export', array( 'report' => 'marks', 'exam' => (int) $erp_exam->id, 'class' => (int) $erp_class->id ) ) ); ?>">Download CSV</a>
			</div>
			<?php if ( ! $erp_lines ) : ?>
				<p class="erp-empty">This class has no students yet.</p>
			<?php else : ?>
				<div class="erp-table-wrap">
					<table class="erp-table">
						<thead><tr><th scope="col">Roll</th><th scope="col">Student</th><?php foreach ( $erp_papers as $erp_paper ) : ?><th scope="col" class="erp-num"><?php echo esc_html( $erp_paper->subject_name ); ?><small>/ <?php echo esc_html( mags_erp_format_marks( $erp_paper->max_marks ) ); ?></small></th><?php endforeach; ?><th scope="col" class="erp-num">Total</th><th scope="col" class="erp-num">%</th><th scope="col"><span class="erp-sr">Report card</span></th></tr></thead>
						<tbody>
							<?php foreach ( $erp_lines as $erp_line ) : ?>
								<tr>
									<td><?php echo esc_html( $erp_line['student']->roll_no ); ?></td>
									<th scope="row"><?php echo esc_html( $erp_line['student']->full_name ); ?><small><?php echo esc_html( $erp_line['student']->admission_no ); ?></small></th>
									<?php foreach ( $erp_line['cells'] as $erp_cell ) : ?><td class="erp-num"><?php echo esc_html( $erp_cell ); ?></td><?php endforeach; ?>
									<td class="erp-num"><strong><?php echo esc_html( mags_erp_format_marks( $erp_line['total'] ) ); ?></strong><small>/ <?php echo esc_html( mags_erp_format_marks( $erp_line['max'] ) ); ?></small></td>
									<td class="erp-num"><strong><?php echo esc_html( null === $erp_line['percent'] ? '–' : $erp_line['percent'] . '%' ); ?></strong></td>
									<td><a href="<?php echo esc_url( mags_erp_url( 'report-card', array( 'exam' => (int) $erp_exam->id, 'student' => (int) $erp_line['student']->id ) ) ); ?>">Report card<span class="erp-sr"> of <?php echo esc_html( $erp_line['student']->full_name ); ?></span></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
			<p class="erp-hint">AB = absent, counted as 0. A dash = marks not entered, not counted in the total.</p>
		</section>
	<?php endif; ?>

<?php else : ?>
	<?php
	$erp_only_left = (bool) mags_erp_get_int( 'left' );
	list( $erp_lines, $erp_totals ) = mags_erp_report_fees( $erp_class ? (int) $erp_class->id : 0, $erp_only_left );
	?>
	<form class="erp-card erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'reports' ) ); ?>">
		<?php mags_erp_route_field( 'reports' ); ?>
		<input type="hidden" name="report" value="fees">
		<p class="erp-field"><label for="erp_rep_class">Class</label><select id="erp_rep_class" name="class"><?php mags_erp_class_options( $erp_classes, $erp_class ? (int) $erp_class->id : 0, 'All classes' ); ?></select></p>
		<p class="erp-check"><label><input type="checkbox" name="left" value="1" <?php checked( $erp_only_left ); ?>> <span>Only students with fees left to pay</span></label></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<section class="erp-card" aria-labelledby="erp-rep-title">
		<div class="erp-work__head">
			<div>
				<h2 id="erp-rep-title"><?php echo esc_html( 'Fees: ' . ( $erp_class ? mags_erp_class_label( $erp_class ) : 'all classes' ) ); ?></h2>
				<p><?php echo esc_html( 'On ' . mags_erp_format_date( mags_erp_today() ) . ( $erp_only_left ? ' · only students with fees left' : '' ) ); ?></p>
			</div>
			<?php if ( $erp_lines ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'export', array( 'report' => 'fees', 'class' => $erp_class ? (int) $erp_class->id : '', 'left' => $erp_only_left ? 1 : '' ) ) ); ?>">Download CSV</a><?php endif; ?>
		</div>
		<dl class="erp-tally erp-tally--money">
			<div class="erp-tally__item erp-tally__item--L"><dt>Fees</dt><dd><?php echo esc_html( mags_erp_money( $erp_totals['charged'] ) ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--P"><dt>Paid</dt><dd><?php echo esc_html( mags_erp_money( $erp_totals['paid'] ) ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--A"><dt>Left to pay</dt><dd><?php echo esc_html( mags_erp_money( $erp_totals['left'] ) ); ?></dd></div>
		</dl>
		<?php if ( ! $erp_lines ) : ?>
			<p class="erp-empty"><?php echo $erp_only_left ? 'No student has fees left to pay.' : 'No students yet.'; ?></p>
		<?php else : ?>
			<div class="erp-table-wrap">
				<table class="erp-table">
					<thead><tr><th scope="col">Roll</th><th scope="col">Student</th><th scope="col">Class</th><th scope="col" class="erp-num">Fees</th><th scope="col" class="erp-num">Paid</th><th scope="col" class="erp-num">Left to pay</th></tr></thead>
					<tbody>
						<?php foreach ( $erp_lines as $erp_line ) : ?>
							<tr><td><?php echo esc_html( $erp_line['student']->roll_no ); ?></td><th scope="row"><a href="<?php echo esc_url( mags_erp_url( 'fees', array( 'student' => (int) $erp_line['student']->id ) ) ); ?>"><?php echo esc_html( $erp_line['student']->full_name ); ?></a><small><?php echo esc_html( $erp_line['student']->admission_no ); ?></small></th><td><?php echo esc_html( $erp_line['class'] ); ?></td><td class="erp-num"><?php echo esc_html( mags_erp_money( $erp_line['charged'] ) ); ?></td><td class="erp-num"><?php echo esc_html( mags_erp_money( $erp_line['paid'] ) ); ?></td><td class="erp-num"><strong><?php echo esc_html( mags_erp_money( $erp_line['left'] ) ); ?></strong></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<p class="erp-hint">A minus sign in “Left to pay” means the student has paid in advance. Cancelled fees and payments are not counted.</p>
	</section>
<?php endif; ?>
