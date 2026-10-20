<?php
/** Office start screen: what exists, what needs attention and what to do next. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes  = mags_erp_get_classes();
$erp_teachers = mags_erp_get_teachers();
$erp_students = mags_erp_count_students();
$erp_fees     = mags_erp_school_fee_summary();
$erp_session  = mags_erp_session_range();
$erp_tiles    = array(
	array( 'classes', 'Classes', count( $erp_classes ), 'blue' ),
	array( 'teachers', 'Teachers', count( $erp_teachers ), 'green' ),
	array( 'students', 'Students', $erp_students, 'orange' ),
);
$erp_steps    = array(
	array( 'classes', 'Add classes', 'Create each class and section, such as Class 5 – A.', (bool) $erp_classes ),
	array( 'teachers', 'Add teachers', 'Create a login for each teacher and tick their classes. Then choose the class teacher of each class under Classes → Edit.', (bool) $erp_teachers ),
	array( 'students', 'Add students', 'Add each student with admission number, class and roll number, or add a whole list from a file.', $erp_students > 0 ),
	array( 'subjects', 'Add subjects and the timetable', 'List the subjects and the periods of the school day, then fill the timetable of each class.', (bool) mags_erp_get_subjects() ),
	array( 'exams', 'Create exams', 'Add an exam and its papers. Teachers enter the marks; you publish the results.', (bool) mags_erp_get_exams() ),
	array( 'fees', 'Enter fees', 'Write down the fees of each class and the payments received at the office.', $erp_fees['charged'] > 0 ),
	array( 'calendar', 'Add holidays and events', 'Put the holidays in the school calendar. Attendance is not marked or counted on a holiday.', (bool) mags_erp_calendar_entries( $erp_session['start'], $erp_session['end'] ) ),
);

// Work that cannot move on until the office does something.
$erp_alerts    = array();
$erp_names     = mags_erp_class_teacher_names();
$erp_unchecked = mags_erp_unchecked_answers_by_class();
$erp_leave     = mags_erp_unanswerable_leave_by_class();
foreach ( $erp_classes as $erp_class ) {
	$erp_label = mags_erp_class_label( $erp_class );
	if ( empty( $erp_names[ (int) $erp_class->id ] ) && ( (int) $erp_class->student_count || ! empty( $erp_unchecked[ (int) $erp_class->id ] ) ) ) {
		$erp_alerts[] = array( $erp_label . ' has no teacher.' . ( ! empty( $erp_unchecked[ (int) $erp_class->id ] ) ? ' ' . sprintf( _n( '%d homework answer is waiting to be checked.', '%d homework answers are waiting to be checked.', $erp_unchecked[ (int) $erp_class->id ], 'mags-erp' ), $erp_unchecked[ (int) $erp_class->id ] ) : '' ), mags_erp_url( 'teachers' ), 'Give it to a teacher' );
	}
	if ( ! empty( $erp_leave[ (int) $erp_class->id ] ) ) {
		$erp_alerts[] = array( $erp_label . ' has no class teacher. ' . sprintf( _n( '%d leave request is waiting.', '%d leave requests are waiting.', $erp_leave[ (int) $erp_class->id ], 'mags-erp' ), $erp_leave[ (int) $erp_class->id ] ), mags_erp_url( 'classes', array( 'edit' => (int) $erp_class->id ) ), 'Choose class teacher' );
	}
}
$erp_new_forms = mags_erp_admission_counts()['new'];
if ( $erp_new_forms ) {
	$erp_alerts[] = array( sprintf( _n( '%d new admission form is waiting.', '%d new admission forms are waiting.', $erp_new_forms, 'mags-erp' ), $erp_new_forms ), mags_erp_url( 'admissions' ), 'Read the forms' );
}
$erp_staff_leave = mags_erp_pending_staff_leave_count();
if ( $erp_staff_leave ) {
	$erp_alerts[] = array( sprintf( _n( '%d teacher is waiting for an answer about leave.', '%d teacher leave requests are waiting for an answer.', $erp_staff_leave, 'mags-erp' ), $erp_staff_leave ), mags_erp_url( 'staff-leave' ), 'Answer' );
}
// nginx does not read the private folder's .htaccess; until its rule is added the files can be downloaded directly.
if ( true === mags_erp_private_files_exposed() ) {
	$erp_alerts[] = array( 'Private files (homework, photos, documents) can be opened straight from the internet on this server. The web server needs the nginx rule shown under Settings.', mags_erp_url( 'settings' ) . '#erp-private-files', 'See the rule' );
}
$erp_stranded = mags_erp_stranded_questions_count();
if ( $erp_stranded ) {
	$erp_alerts[] = array( sprintf( _n( '%d question is waiting for a teacher who no longer teaches that class.', '%d questions are waiting for a teacher who no longer teaches that class.', $erp_stranded, 'mags-erp' ), $erp_stranded ), mags_erp_url( 'questions' ), 'Read questions' );
}
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Hello, <?php echo esc_html( $ctx['user']->display_name ); ?></h1>
</header>

<section class="erp-tiles" aria-label="School records">
	<?php foreach ( $erp_tiles as $erp_tile ) : ?>
		<a class="erp-tile erp-tile--<?php echo esc_attr( $erp_tile[3] ); ?>" href="<?php echo esc_url( mags_erp_url( $erp_tile[0] ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $erp_tile[2] ) ); ?></strong><span><?php echo esc_html( $erp_tile[1] ); ?></span></a>
	<?php endforeach; ?>
</section>

<?php
list( , $erp_day_totals ) = mags_erp_today_attendance( mags_erp_today() );
$erp_day_received = array_sum( array_map( static function ( $erp_payment ) { return (float) $erp_payment->amount; }, mags_erp_payments_on( mags_erp_today() ) ) );
$erp_day_pending  = mags_erp_pending_fees_overview();
?>
<section class="erp-card" aria-labelledby="erp-today-title">
	<div class="erp-work__head">
		<h2 id="erp-today-title">Today</h2>
		<a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'today' ) ); ?>">Open today’s page</a>
	</div>
	<dl class="erp-tally erp-tally--money">
		<div class="erp-tally__item erp-tally__item--A"><dt>Absent</dt><dd><?php echo esc_html( number_format_i18n( $erp_day_totals['A'] ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--P"><dt>Fees received</dt><dd><?php echo esc_html( mags_erp_money( $erp_day_received ) ); ?></dd></div>
		<div class="erp-tally__item erp-tally__item--L"><dt>Fees pending</dt><dd><?php echo esc_html( mags_erp_money( $erp_day_pending['left'] ) ); ?></dd></div>
	</dl>
	<p class="erp-hint"><?php echo esc_html( sprintf( '%1$d of %2$d students marked today.', $erp_day_totals['marked'], $erp_day_totals['students'] ) ); ?></p>
</section>

<?php if ( $erp_alerts ) : ?>
	<section class="erp-card erp-card--alert" aria-labelledby="erp-alerts-title">
		<h2 id="erp-alerts-title">Needs your attention</h2>
		<ul class="erp-alerts">
			<?php foreach ( $erp_alerts as $erp_alert ) : ?>
				<li><span><?php echo esc_html( $erp_alert[0] ); ?></span><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( $erp_alert[1] ); ?>"><?php echo esc_html( $erp_alert[2] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( $erp_fees['charged'] > 0 ) : ?>
	<section class="erp-card" aria-labelledby="erp-fees-title">
		<h2 id="erp-fees-title">Fees</h2>
		<dl class="erp-tally erp-tally--money">
			<div class="erp-tally__item erp-tally__item--L"><dt>Fees</dt><dd><?php echo esc_html( mags_erp_money( $erp_fees['charged'] ) ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--P"><dt>Received</dt><dd><?php echo esc_html( mags_erp_money( $erp_fees['paid'] ) ); ?></dd></div>
			<div class="erp-tally__item erp-tally__item--A"><dt>Left to collect</dt><dd><?php echo esc_html( mags_erp_money( max( 0, $erp_fees['left'] ) ) ); ?></dd></div>
		</dl>
	</section>
<?php endif; ?>

<section class="erp-card" aria-labelledby="erp-steps-title">
	<h2 id="erp-steps-title">Set up step by step</h2>
	<ol class="erp-steps">
		<?php foreach ( $erp_steps as $erp_step ) : ?>
			<li class="<?php echo $erp_step[3] ? 'is-done' : ''; ?>">
				<div><strong><?php echo esc_html( $erp_step[1] ); ?></strong><?php if ( $erp_step[3] ) : ?> <span class="erp-chip erp-chip--green">Started</span><?php endif; ?><p><?php echo esc_html( $erp_step[2] ); ?></p></div>
				<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( $erp_step[0] ) ); ?>">Open<span class="erp-sr"> <?php echo esc_html( $erp_step[1] ); ?></span></a>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="erp-hint">When a student or teacher is added, the portal shows the login ID and password once. Note it down or copy it before leaving the page. Nothing you remove is deleted: it is kept and can be restored.</p>
</section>
