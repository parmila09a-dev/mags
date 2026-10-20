<?php
/** Office: make exams and their papers, follow the marks entry, publish the results. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_exam    = mags_erp_get_exam( mags_erp_get_int( 'id' ) );
$erp_classes = mags_erp_get_classes();
$erp_counts  = wp_list_pluck( $erp_classes, 'student_count', 'id' );

if ( $erp_exam ) :
	$erp_papers   = mags_erp_exam_papers( (int) $erp_exam->id );
	$erp_subjects = mags_erp_get_subjects();
	$erp_groups   = array();
	foreach ( $erp_papers as $erp_paper ) { $erp_groups[ $erp_paper->class_label ][] = $erp_paper; }
	$erp_gone = array();
	foreach ( mags_erp_removed_papers( (int) $erp_exam->id ) as $erp_paper ) { $erp_gone[] = array( (int) $erp_paper->id, $erp_paper->class_label . ' · ' . $erp_paper->subject_name, 'Out of ' . mags_erp_format_marks( $erp_paper->max_marks ) ); }
	?>
	<header class="erp-head">
		<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'exams' ) ); ?>">← All exams</a></p>
		<h1><?php echo esc_html( $erp_exam->name ); ?></h1>
		<p class="erp-head__meta">
			<?php if ( $erp_exam->published_at ) : ?><span class="erp-chip erp-chip--green">Results published <?php echo esc_html( mags_erp_format_datetime( $erp_exam->published_at, 'j M Y' ) ); ?></span><?php else : ?><span class="erp-chip erp-chip--orange">Open for marks</span><?php endif; ?>
			<?php if ( $erp_exam->start_date ) : ?><span class="erp-chip">From <?php echo esc_html( mags_erp_format_date( $erp_exam->start_date, 'j M Y' ) ); ?></span><?php endif; ?>
			<span class="erp-chip"><?php echo esc_html( sprintf( _n( '%d paper', '%d papers', count( $erp_papers ), 'mags-erp' ), count( $erp_papers ) ) ); ?></span>
		</p>
	</header>

	<section class="erp-card" aria-labelledby="erp-publish-title">
		<h2 id="erp-publish-title"><?php echo $erp_exam->published_at ? 'Results are published' : 'Results are not published'; ?></h2>
		<?php if ( $erp_exam->published_at ) : ?>
			<p class="erp-hint erp-hint--lead">Students can see their marks and download the report card. The marks are locked for teachers. To correct a mark, open the exam again, let the teacher change it, then publish again.</p>
			<form method="post" action="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) ); ?>" data-erp-confirm="Open this exam for marks again? Students will not see the results until you publish them again.">
				<?php mags_erp_form_fields( 'publish_exam' ); ?>
				<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>"><input type="hidden" name="publish" value="0">
				<button class="erp-btn erp-btn--ghost" type="submit">Open for marks again</button>
			</form>
		<?php else : ?>
			<p class="erp-hint erp-hint--lead">Teachers enter the marks of their classes under Marks. Students see nothing until you publish.</p>
			<form method="post" action="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) ); ?>" data-erp-confirm="Publish the results? Students will see their marks and teachers can no longer change them.">
				<?php mags_erp_form_fields( 'publish_exam' ); ?>
				<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>"><input type="hidden" name="publish" value="1">
				<button class="erp-btn erp-btn--orange" type="submit" <?php disabled( ! $erp_papers ); ?>>Publish results</button>
			</form>
		<?php endif; ?>
	</section>

	<section id="erp-admit" class="erp-card" aria-labelledby="erp-admit-title">
		<h2 id="erp-admit-title">Date sheet and admit cards <?php if ( $erp_exam->datesheet_at ) : ?><span class="erp-chip erp-chip--green">Shared with students</span><?php else : ?><span class="erp-chip">Not shared yet</span><?php endif; ?></h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) ); ?>">
			<?php mags_erp_form_fields( 'save_admit_settings' ); ?>
			<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>">
			<p class="erp-check"><label><input type="checkbox" name="share" value="1" <?php checked( (bool) $erp_exam->datesheet_at ); ?>> <span>Show the date sheet to students and parents (More → Exams) and let them download the admit card</span></label></p>
			<fieldset class="erp-checks">
				<legend>Who gets an admit card</legend>
				<?php foreach ( mags_erp_admit_rules() as $erp_rule => $erp_rule_label ) : ?>
					<label class="erp-pill"><input type="radio" name="admit_rule" value="<?php echo esc_attr( $erp_rule ); ?>" <?php checked( ( $erp_exam->admit_rule ?: 'none' ), $erp_rule ); ?>><span><?php echo esc_html( $erp_rule_label ); ?></span></label>
				<?php endforeach; ?>
			</fieldset>
			<p class="erp-field"><label for="erp_admit_note">Instructions printed on the date sheet and admit card <span>(optional)</span></label><textarea id="erp_admit_note" name="admit_note" rows="2" maxlength="600" placeholder="<?php echo esc_attr( mags_erp_admit_default_note() ); ?>"><?php echo esc_textarea( (string) $erp_exam->admit_note ); ?></textarea></p>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--navy" type="submit">Save</button></div>
		</form>
		<?php if ( $erp_groups ) : ?>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_groups as $erp_group_label => $erp_group ) : $erp_group_class = (int) $erp_group[0]->class_id; ?>
					<li class="erp-row">
						<div class="erp-row__main"><strong><?php echo esc_html( $erp_group_label ); ?></strong><span><?php echo esc_html( sprintf( _n( '%d paper', '%d papers', count( $erp_group ), 'mags-erp' ), count( $erp_group ) ) ); ?></span></div>
						<div class="erp-row__actions">
							<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'date-sheet', array( 'exam' => (int) $erp_exam->id, 'class' => $erp_group_class ) ) ); ?>">Date sheet (PDF)<span class="erp-sr"> of <?php echo esc_html( $erp_group_label ); ?></span></a>
							<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'admit-cards', array( 'exam' => (int) $erp_exam->id, 'class' => $erp_group_class ) ) ); ?>">Admit cards<span class="erp-sr"> of <?php echo esc_html( $erp_group_label ); ?></span></a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p class="erp-hint">With a fee rule, a student whose fees are not paid sees why the card is held. Under “Admit cards” you can hold one student’s card or let one through anyway, and print any card at the office.</p>
	</section>

	<?php if ( ! $erp_exam->published_at ) : ?>
		<section class="erp-card" aria-labelledby="erp-paper-form-title">
			<h2 id="erp-paper-form-title">Add papers</h2>
			<?php if ( ! $erp_subjects || ! $erp_classes ) : ?>
				<p class="erp-hint">Add the classes and the <a href="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>">subjects</a> first.</p>
			<?php else : ?>
				<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) ); ?>">
					<?php mags_erp_form_fields( 'save_papers' ); ?>
					<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>">
					<div class="erp-form__grid erp-form__grid--three">
						<p class="erp-field"><label for="erp_paper_subject">Subject</label><select id="erp_paper_subject" name="subject_id" required><option value="">Choose subject</option><?php foreach ( $erp_subjects as $erp_subject ) : ?><option value="<?php echo esc_attr( $erp_subject->id ); ?>"><?php echo esc_html( $erp_subject->name ); ?></option><?php endforeach; ?></select></p>
						<p class="erp-field"><label for="erp_paper_max">Maximum marks</label><input id="erp_paper_max" name="max_marks" type="text" inputmode="decimal" maxlength="7" placeholder="100" required></p>
						<p class="erp-field"><label for="erp_paper_date">Date of the paper <span>(optional)</span></label><input id="erp_paper_date" name="exam_date" type="date"></p>
						<p class="erp-field"><label for="erp_paper_start">Starts at <span>(optional)</span></label><input id="erp_paper_start" name="start_time" type="time" step="300"></p>
						<p class="erp-field"><label for="erp_paper_end">Ends at <span>(optional)</span></label><input id="erp_paper_end" name="end_time" type="time" step="300"></p>
					</div>
					<fieldset class="erp-checks">
						<legend>Classes that write this paper</legend>
						<?php foreach ( $erp_classes as $erp_class ) : ?>
							<label class="erp-pill"><input type="checkbox" name="class_ids[]" value="<?php echo esc_attr( $erp_class->id ); ?>"><span><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></span></label>
						<?php endforeach; ?>
					</fieldset>
					<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Add paper</button></div>
				</form>
				<p class="erp-hint">To change the maximum marks, date or time of a paper, add it again with the new values. The date and time are printed on the date sheet and the admit card.</p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<section aria-labelledby="erp-paper-list-title">
		<h2 id="erp-paper-list-title" class="erp-section-title">Papers <span class="erp-count"><?php echo esc_html( count( $erp_papers ) ); ?></span></h2>
		<?php if ( ! $erp_papers ) : ?>
			<p class="erp-card erp-empty">No papers yet. Add a subject and maximum marks for each class above.</p>
		<?php endif; ?>
		<?php foreach ( $erp_groups as $erp_group_label => $erp_group ) : ?>
			<h3 class="erp-subtitle"><?php echo esc_html( $erp_group_label ); ?> <a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'reports', array( 'report' => 'marks', 'exam' => (int) $erp_exam->id, 'class' => (int) $erp_group[0]->class_id ) ) ); ?>">Marks report</a></h3>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_group as $erp_paper ) : $erp_class_total = (int) ( $erp_counts[ (int) $erp_paper->class_id ] ?? 0 ); $erp_done = $erp_class_total && (int) $erp_paper->entered_count >= $erp_class_total; ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php echo esc_html( $erp_paper->subject_name ); ?></strong>
							<span>Out of <?php echo esc_html( mags_erp_format_marks( $erp_paper->max_marks ) ); ?><?php if ( $erp_paper->exam_date ) : ?> · <?php echo esc_html( mags_erp_format_date( $erp_paper->exam_date, 'D j M Y' ) ); ?><?php endif; ?><?php if ( '' !== mags_erp_paper_time( $erp_paper ) ) : ?> · <?php echo esc_html( mags_erp_paper_time( $erp_paper ) ); ?><?php endif; ?></span>
							<span class="erp-row__chips"><span class="erp-chip <?php echo $erp_done ? 'erp-chip--green' : 'erp-chip--orange'; ?>">Marks: <?php echo esc_html( $erp_paper->entered_count ); ?> of <?php echo esc_html( $erp_class_total ); ?></span></span>
						</div>
						<?php if ( ! $erp_exam->published_at ) : ?>
							<div class="erp-row__actions">
								<form method="post" action="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove the ' . $erp_paper->subject_name . ' paper of ' . $erp_paper->class_label . '? Its marks are kept and return if you restore it.' ); ?>">
									<?php mags_erp_form_fields( 'delete_paper' ); ?>
									<input type="hidden" name="paper_id" value="<?php echo esc_attr( $erp_paper->id ); ?>">
									<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
								</form>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
		<?php if ( ! $erp_exam->published_at ) { mags_erp_removed_list( $erp_gone, 'delete_paper', 'paper_id', 'exams', 'Removed papers' ); } ?>
	</section>

	<section class="erp-card" aria-labelledby="erp-exam-edit-title">
		<h2 id="erp-exam-edit-title">Exam details</h2>
		<form class="erp-form erp-form--row" method="post" action="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) ); ?>">
			<?php mags_erp_form_fields( 'save_exam' ); ?>
			<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>">
			<p class="erp-field"><label for="erp_exam_name">Exam name</label><input id="erp_exam_name" name="erp_name" type="text" maxlength="120" value="<?php echo esc_attr( $erp_exam->name ); ?>" required></p>
			<p class="erp-field"><label for="erp_exam_date">First day <span>(optional)</span></label><input id="erp_exam_date" name="start_date" type="date" value="<?php echo esc_attr( (string) $erp_exam->start_date ); ?>"></p>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--navy" type="submit">Save</button></div>
		</form>
		<form class="erp-removed" method="post" action="<?php echo esc_url( mags_erp_url( 'exams' ) ); ?>" data-erp-confirm="Remove this exam? Its papers and marks are kept and return if you restore it.">
			<?php mags_erp_form_fields( 'delete_exam' ); ?>
			<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>">
			<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove exam</button>
		</form>
	</section>
	<?php
	return;
endif;

$erp_exams = mags_erp_get_exams();
$erp_gone  = array();
foreach ( mags_erp_get_exams( true ) as $erp_item ) { $erp_gone[] = array( (int) $erp_item->id, $erp_item->name, 'Removed ' . mags_erp_format_datetime( $erp_item->hidden_at, 'j M Y' ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Exams</h1>
</header>

<section class="erp-card" aria-labelledby="erp-exam-form-title">
	<h2 id="erp-exam-form-title">Add an exam</h2>
	<form class="erp-form erp-form--row" method="post" action="<?php echo esc_url( mags_erp_url( 'exams' ) ); ?>">
		<?php mags_erp_form_fields( 'save_exam' ); ?>
		<input type="hidden" name="exam_id" value="0">
		<p class="erp-field"><label for="erp_exam_name">Exam name</label><input id="erp_exam_name" name="erp_name" type="text" maxlength="120" placeholder="Half Yearly Exam 2026" required></p>
		<p class="erp-field"><label for="erp_exam_date">First day <span>(optional)</span></label><input id="erp_exam_date" name="start_date" type="date"></p>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Add exam</button></div>
	</form>
</section>

<section aria-labelledby="erp-exam-list-title">
	<h2 id="erp-exam-list-title" class="erp-section-title">All exams <span class="erp-count"><?php echo esc_html( count( $erp_exams ) ); ?></span></h2>
	<?php if ( ! $erp_exams ) : ?>
		<p class="erp-card erp-empty">No exams yet. Add the first exam above.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_exams as $erp_item ) : $erp_item_papers = mags_erp_exam_papers( (int) $erp_item->id ); ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_item->name ); ?></strong>
						<span><?php echo esc_html( sprintf( _n( '%d paper', '%d papers', count( $erp_item_papers ), 'mags-erp' ), count( $erp_item_papers ) ) ); ?><?php if ( $erp_item->start_date ) : ?> · from <?php echo esc_html( mags_erp_format_date( $erp_item->start_date, 'j M Y' ) ); ?><?php endif; ?></span>
						<span class="erp-row__chips"><?php if ( $erp_item->published_at ) : ?><span class="erp-chip erp-chip--green">Results published</span><?php else : ?><span class="erp-chip erp-chip--orange">Open for marks</span><?php endif; ?></span>
					</div>
					<div class="erp-row__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_item->id ) ) ); ?>">Open</a></div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php mags_erp_removed_list( $erp_gone, 'delete_exam', 'exam_id', 'exams', 'Removed exams' ); ?>
</section>
