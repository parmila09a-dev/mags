<?php
/** Teacher: mark or correct one day's attendance for one of their own classes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_today    = mags_erp_today();
$erp_classes  = mags_erp_teacher_classes( $ctx['user']->ID );
$erp_statuses = mags_erp_statuses();

// phpcs:disable WordPress.Security.NonceVerification -- read-only choice of class and date.
$erp_class_id = isset( $_GET['class'] ) ? absint( $_GET['class'] ) : 0;
$erp_date     = isset( $_GET['date'] ) && is_string( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : $erp_today;
// phpcs:enable
$erp_date     = mags_erp_valid_date( $erp_date ) && $erp_date <= $erp_today ? $erp_date : $erp_today;

$erp_class = null;
foreach ( $erp_classes as $erp_own_class ) {
	if ( (int) $erp_own_class->id === $erp_class_id || ( ! $erp_class_id && 1 === count( $erp_classes ) ) ) { $erp_class = $erp_own_class; }
}
$erp_students = $erp_class ? mags_erp_class_students( (int) $erp_class->id ) : array();
$erp_marks    = mags_erp_attendance_for_students( wp_list_pluck( $erp_students, 'id' ), $erp_date );
// Attendance is not marked on a holiday of the school calendar.
$erp_holiday  = mags_erp_holiday_on( $erp_date );
// A student with approved leave starts as "Leave" on a day that is not saved yet; the teacher can still change it.
$erp_on_leave = mags_erp_approved_leave_on( wp_list_pluck( $erp_students, 'id' ), $erp_date );
?>
<header class="erp-head">
	<p class="erp-eyebrow">Attendance</p>
	<h1><?php echo esc_html( $erp_class ? mags_erp_class_label( $erp_class ) : 'Mark attendance' ); ?></h1>
</header>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">No class has been given to you yet. Please ask the school office to add your classes.</p>
<?php else : ?>
	<form class="erp-card erp-filter erp-filter--pair" method="get" action="<?php echo esc_url( mags_erp_url( 'attendance' ) ); ?>" data-erp-autosubmit>
		<?php if ( ! get_option( 'permalink_structure' ) ) : ?><input type="hidden" name="mags_erp" value="attendance"><?php endif; ?>
		<p class="erp-field"><label for="erp_att_class">Class</label><select id="erp_att_class" name="class" required><?php if ( count( $erp_classes ) > 1 ) : ?><option value="">Choose class</option><?php endif; ?><?php foreach ( $erp_classes as $erp_own_class ) : ?><option value="<?php echo esc_attr( $erp_own_class->id ); ?>" <?php selected( $erp_class ? (int) $erp_class->id : 0, (int) $erp_own_class->id ); ?>><?php echo esc_html( mags_erp_class_label( $erp_own_class ) ); ?></option><?php endforeach; ?></select></p>
		<p class="erp-field"><label for="erp_att_date">Date</label><input id="erp_att_date" name="date" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( $erp_date ); ?>" required></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<?php if ( ! $erp_class ) : ?>
		<p class="erp-card erp-empty">Choose a class to mark its attendance.</p>
	<?php elseif ( $erp_holiday ) : ?>
		<section class="erp-card erp-empty erp-holiday">
			<h2><?php echo esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></h2>
			<p><span class="erp-chip erp-chip--blue">Holiday: <?php echo esc_html( $erp_holiday->title ); ?></span></p>
			<p>Attendance is not marked on a holiday, and the day is not counted in the percentage. Choose another date above.</p>
			<p><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'calendar', array( 'month' => substr( $erp_date, 0, 7 ) ) ) ); ?>">School calendar</a></p>
		</section>
	<?php elseif ( ! $erp_students ) : ?>
		<p class="erp-card erp-empty">This class has no students yet. The school office adds students.</p>
	<?php else : ?>
		<form class="erp-sheet" method="post" action="<?php echo esc_url( mags_erp_url( 'attendance' ) ); ?>" data-erp-sheet>
			<?php mags_erp_form_fields( 'save_attendance' ); ?>
			<input type="hidden" name="class_id" value="<?php echo esc_attr( $erp_class->id ); ?>">
			<input type="hidden" name="date" value="<?php echo esc_attr( $erp_date ); ?>">

			<div class="erp-sheet__head">
				<div>
					<h2><?php echo esc_html( mags_erp_format_date( $erp_date, 'l, j F Y' ) ); ?></h2>
					<p>
						<?php if ( $erp_marks ) : ?><span class="erp-chip erp-chip--green">Already saved · you can correct it</span>
						<?php else : ?><span class="erp-chip erp-chip--orange">Not saved yet</span> Everyone starts as Present<?php echo $erp_on_leave ? ', and students with approved leave as Leave' : ''; ?>. Change the absent students, then save.<?php endif; ?>
					</p>
				</div>
				<button class="erp-btn erp-btn--ghost erp-btn--sm" type="button" data-erp-all-present hidden>All present</button>
			</div>
			<p class="erp-legend" aria-hidden="true"><span class="erp-dot erp-dot--P">P</span> Present <span class="erp-dot erp-dot--A">A</span> Absent <span class="erp-dot erp-dot--L">L</span> Leave</p>

			<ol class="erp-marks">
				<?php foreach ( $erp_students as $erp_student ) : $erp_current = $erp_marks[ (int) $erp_student->id ] ?? ( isset( $erp_on_leave[ (int) $erp_student->id ] ) ? 'L' : 'P' ); ?>
					<li class="erp-mark">
						<div class="erp-mark__who"><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><strong><?php echo esc_html( $erp_student->full_name ); ?><?php if ( isset( $erp_on_leave[ (int) $erp_student->id ] ) ) : ?> <span class="erp-chip erp-chip--orange">Leave approved</span><?php endif; ?></strong></div>
						<fieldset class="erp-seg erp-seg--status">
							<legend class="erp-sr">Attendance for <?php echo esc_html( $erp_student->full_name ); ?></legend>
							<?php foreach ( $erp_statuses as $erp_code => $erp_label ) : ?>
								<label class="erp-seg__item erp-seg__item--<?php echo esc_attr( $erp_code ); ?>"><input type="radio" name="status[<?php echo esc_attr( $erp_student->id ); ?>]" value="<?php echo esc_attr( $erp_code ); ?>" aria-label="<?php echo esc_attr( $erp_label ); ?>" <?php checked( $erp_current, $erp_code ); ?>><span aria-hidden="true"><?php echo esc_html( $erp_code ); ?></span></label>
							<?php endforeach; ?>
						</fieldset>
					</li>
				<?php endforeach; ?>
			</ol>

			<div class="erp-savebar">
				<p class="erp-savebar__counts" data-erp-counts aria-live="polite"><?php echo esc_html( sprintf( _n( '%s student', '%s students', count( $erp_students ), 'mags-erp' ), number_format_i18n( count( $erp_students ) ) ) ); ?></p>
				<button class="erp-btn erp-btn--orange" type="submit">Save attendance</button>
			</div>
		</form>
	<?php endif; ?>
<?php endif; ?>
