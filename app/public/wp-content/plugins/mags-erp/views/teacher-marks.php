<?php
/** Teacher: enter the exam marks of own classes, paper by paper, until the office publishes the results. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_teacher_id = $ctx['user']->ID;
$erp_class_ids  = mags_erp_teacher_class_ids( $erp_teacher_id );
$erp_paper      = mags_erp_get_paper( mags_erp_get_int( 'paper' ) );
$erp_exam       = $erp_paper ? mags_erp_get_exam( (int) $erp_paper->exam_id ) : null;
if ( ! $erp_exam || ! in_array( (int) $erp_paper->class_id, $erp_class_ids, true ) ) { $erp_paper = null; }

if ( $erp_paper ) :
	$erp_class    = mags_erp_get_class( (int) $erp_paper->class_id );
	$erp_students = mags_erp_class_students( (int) $erp_paper->class_id );
	$erp_marks    = mags_erp_paper_marks( (int) $erp_paper->id );
	$erp_locked   = (bool) $erp_exam->published_at;
	$erp_max      = mags_erp_format_marks( $erp_paper->max_marks );
	?>
	<header class="erp-head">
		<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'marks' ) ); ?>">← All papers</a></p>
		<h1><?php echo esc_html( $erp_paper->subject_name ); ?></h1>
		<p class="erp-head__meta"><span class="erp-chip erp-chip--blue"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></span><span class="erp-chip"><?php echo esc_html( $erp_exam->name ); ?></span><span class="erp-chip">Out of <?php echo esc_html( $erp_max ); ?></span><?php if ( $erp_locked ) : ?><span class="erp-chip erp-chip--green">Published · locked</span><?php endif; ?></p>
	</header>

	<?php if ( ! $erp_students ) : ?>
		<p class="erp-card erp-empty">This class has no students yet.</p>
	<?php else : ?>
		<form class="erp-sheet" method="post" action="<?php echo esc_url( mags_erp_url( 'marks', array( 'paper' => (int) $erp_paper->id ) ) ); ?>" data-erp-guard>
			<?php mags_erp_form_fields( 'save_marks' ); ?>
			<input type="hidden" name="paper_id" value="<?php echo esc_attr( $erp_paper->id ); ?>">
			<div class="erp-sheet__head">
				<div>
					<h2>Marks sheet</h2>
					<p><?php echo $erp_locked ? 'The results are published, so the marks cannot be changed. Ask the school office to open the exam again if a mark is wrong.' : 'Type the marks of each student, or tick Absent. You can save and come back later.'; ?></p>
				</div>
			</div>
			<ol class="erp-marks">
				<?php foreach ( $erp_students as $erp_student ) : $erp_mark = $erp_marks[ (int) $erp_student->id ] ?? null; ?>
					<li class="erp-mark">
						<div class="erp-mark__who"><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><strong><?php echo esc_html( $erp_student->full_name ); ?></strong></div>
						<div class="erp-mark__entry">
							<p class="erp-field erp-field--marks"><label class="erp-sr" for="erp_m_<?php echo esc_attr( $erp_student->id ); ?>">Marks of <?php echo esc_html( $erp_student->full_name ); ?>, out of <?php echo esc_html( $erp_max ); ?></label><input id="erp_m_<?php echo esc_attr( $erp_student->id ); ?>" name="marks[<?php echo esc_attr( $erp_student->id ); ?>]" type="text" inputmode="decimal" maxlength="7" placeholder="–" pattern="\d{1,4}(\.\d{1,2})?" value="<?php echo esc_attr( $erp_mark && ! (int) $erp_mark->absent ? mags_erp_format_marks( $erp_mark->marks ) : '' ); ?>" <?php disabled( $erp_locked ); ?>></p>
							<label class="erp-pill erp-pill--sm"><input type="checkbox" name="absent[<?php echo esc_attr( $erp_student->id ); ?>]" value="1" <?php checked( $erp_mark && (int) $erp_mark->absent ); ?> <?php disabled( $erp_locked ); ?>><span>Absent</span></label>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php if ( ! $erp_locked ) : ?>
				<div class="erp-savebar">
					<p class="erp-savebar__counts"><?php echo esc_html( sprintf( _n( '%s student', '%s students', count( $erp_students ), 'mags-erp' ), number_format_i18n( count( $erp_students ) ) ) ); ?> · out of <?php echo esc_html( $erp_max ); ?></p>
					<button class="erp-btn erp-btn--orange" type="submit">Save marks</button>
				</div>
			<?php endif; ?>
		</form>
	<?php endif; ?>
	<?php
	return;
endif;

$erp_counts = wp_list_pluck( mags_erp_teacher_classes( $erp_teacher_id ), 'student_count', 'id' );
$erp_exams  = array();
foreach ( mags_erp_get_exams() as $erp_item ) {
	$erp_item->papers = mags_erp_exam_papers( (int) $erp_item->id, $erp_class_ids );
	if ( $erp_item->papers ) { $erp_exams[] = $erp_item; }
}
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>Exam marks</h1>
</header>

<?php if ( ! $erp_class_ids ) : ?>
	<p class="erp-card erp-empty">No class has been given to you yet. Please ask the school office to add your classes.</p>
<?php elseif ( ! $erp_exams ) : ?>
	<p class="erp-card erp-empty">The school office has not made an exam for your classes yet.</p>
<?php endif; ?>

<?php foreach ( $erp_exams as $erp_item ) : ?>
	<section aria-labelledby="erp-exam-<?php echo esc_attr( $erp_item->id ); ?>">
		<h2 id="erp-exam-<?php echo esc_attr( $erp_item->id ); ?>" class="erp-section-title"><?php echo esc_html( $erp_item->name ); ?> <?php if ( $erp_item->published_at ) : ?><span class="erp-chip erp-chip--green">Published · locked</span><?php else : ?><span class="erp-chip erp-chip--orange">Open for marks</span><?php endif; ?></h2>
		<ul class="erp-list">
			<?php foreach ( $erp_item->papers as $erp_row ) : $erp_total = (int) ( $erp_counts[ (int) $erp_row->class_id ] ?? 0 ); $erp_left = max( 0, $erp_total - (int) $erp_row->entered_count ); ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_row->subject_name ); ?></strong>
						<span><?php echo esc_html( $erp_row->class_label ); ?> · out of <?php echo esc_html( mags_erp_format_marks( $erp_row->max_marks ) ); ?><?php if ( $erp_row->exam_date ) : ?> · <?php echo esc_html( mags_erp_format_date( $erp_row->exam_date, 'j M Y' ) ); ?><?php endif; ?></span>
						<span class="erp-row__chips"><?php if ( ! $erp_total ) : ?><span class="erp-chip">No students yet</span><?php elseif ( $erp_left ) : ?><span class="erp-chip erp-chip--orange"><?php echo esc_html( $erp_left ); ?> of <?php echo esc_html( $erp_total ); ?> to enter</span><?php else : ?><span class="erp-chip erp-chip--green">All <?php echo esc_html( $erp_total ); ?> entered</span><?php endif; ?></span>
					</div>
					<div class="erp-row__actions"><a class="erp-btn <?php echo $erp_left && ! $erp_item->published_at ? 'erp-btn--orange' : 'erp-btn--ghost'; ?> erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'marks', array( 'paper' => (int) $erp_row->id ) ) ); ?>"><?php echo $erp_item->published_at ? 'See marks' : ( $erp_left ? 'Enter marks' : 'Change marks' ); ?><span class="erp-sr"> for <?php echo esc_html( $erp_row->subject_name . ', ' . $erp_row->class_label ); ?></span></a></div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endforeach; ?>
