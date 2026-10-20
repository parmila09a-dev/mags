<?php
/** Teacher: give homework to own classes, change it and add files, see who has answered, give marks or a comment. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_teacher_id = $ctx['user']->ID;
$erp_classes    = mags_erp_teacher_classes( $erp_teacher_id );
$erp_class_map  = array();
foreach ( $erp_classes as $erp_class ) { $erp_class_map[ (int) $erp_class->id ] = $erp_class; }
$erp_homework_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$erp_homework    = $erp_homework_id ? mags_erp_get_homework( $erp_homework_id ) : null;
if ( $erp_homework && ! isset( $erp_class_map[ (int) $erp_homework->class_id ] ) ) { $erp_homework = null; }
$erp_today = mags_erp_today();

if ( $erp_homework ) :
	$erp_class       = $erp_class_map[ (int) $erp_homework->class_id ];
	$erp_students    = mags_erp_homework_students( $erp_homework );
	$erp_submissions = array_intersect_key( mags_erp_homework_submissions( (int) $erp_homework->id ), array_flip( array_map( 'intval', wp_list_pluck( $erp_students, 'id' ) ) ) );
	$erp_files       = mags_erp_homework_files( (int) $erp_homework->id );
	$erp_room        = MAGS_ERP_HOMEWORK_FILES - count( $erp_files );
	$erp_checked     = 0;
	foreach ( $erp_submissions as $erp_sub ) { if ( $erp_sub->checked_at ) { $erp_checked++; } }
	?>
	<header class="erp-head">
		<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'homework' ) ); ?>">← All homework</a></p>
		<h1><?php echo esc_html( $erp_homework->title ); ?></h1>
		<p class="erp-head__meta"><span class="erp-chip erp-chip--blue"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></span><span class="erp-chip">Given <?php echo esc_html( mags_erp_format_datetime( $erp_homework->created_at, 'j M Y' ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( $erp_homework->teacher_id ) ); ?></span><?php if ( $erp_homework->due_date ) : ?><span class="erp-chip <?php echo $erp_homework->due_date < $erp_today ? 'erp-chip--status-A' : 'erp-chip--orange'; ?>">Due <?php echo esc_html( mags_erp_format_date( $erp_homework->due_date, 'j M Y' ) ); ?></span><?php endif; ?><?php if ( null !== $erp_homework->max_marks ) : ?><span class="erp-chip">Out of <?php echo esc_html( mags_erp_format_marks( $erp_homework->max_marks ) ); ?></span><?php endif; ?><?php if ( $erp_homework->updated_at ) : ?><span class="erp-chip">Changed <?php echo esc_html( mags_erp_format_datetime( $erp_homework->updated_at, 'j M Y' ) ); ?></span><?php endif; ?></p>
	</header>

	<section class="erp-card erp-work">
		<?php if ( '' !== trim( $erp_homework->instructions ) ) : ?><p class="erp-work__text"><?php echo nl2br( esc_html( $erp_homework->instructions ) ); ?></p><?php endif; ?>
		<?php if ( $erp_files ) : ?>
			<div class="erp-files"><?php foreach ( $erp_files as $erp_file ) { mags_erp_file_link( $erp_file ); } ?></div>
		<?php endif; ?>
		<div class="erp-work__foot">
			<span class="erp-chip erp-chip--green"><?php echo esc_html( count( $erp_submissions ) ); ?> of <?php echo esc_html( count( $erp_students ) ); ?> answered</span>
			<span class="erp-chip"><?php echo esc_html( $erp_checked ); ?> checked</span>
			<form method="post" action="<?php echo esc_url( mags_erp_url( 'homework' ) ); ?>" data-erp-confirm="Remove this homework? Students will no longer see it. The answers and marks are kept and you can restore it.">
				<?php mags_erp_form_fields( 'delete_homework' ); ?>
				<input type="hidden" name="homework_id" value="<?php echo esc_attr( $erp_homework->id ); ?>">
				<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove homework</button>
			</form>
		</div>

		<details class="erp-upload"<?php echo isset( $_GET['change'] ) ? ' open' : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>>
			<summary class="erp-btn erp-btn--ghost erp-btn--sm">Change homework or add files</summary>
			<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( mags_erp_url( 'homework', array( 'id' => (int) $erp_homework->id ) ) ); ?>" enctype="multipart/form-data">
				<?php mags_erp_form_fields( 'update_homework' ); ?>
				<input type="hidden" name="homework_id" value="<?php echo esc_attr( $erp_homework->id ); ?>">
				<div class="erp-form__grid">
					<p class="erp-field"><label for="erp_hw_title">Title</label><input id="erp_hw_title" name="title" type="text" maxlength="160" value="<?php echo esc_attr( $erp_homework->title ); ?>" required></p>
					<p class="erp-field"><label for="erp_hw_class">Class</label><input id="erp_hw_class" type="text" value="<?php echo esc_attr( mags_erp_class_label( $erp_class ) ); ?>" readonly><small>The class cannot be changed, because the answers belong to its students.</small></p>
					<p class="erp-field"><label for="erp_hw_due">Due date <span>(optional)</span></label><input id="erp_hw_due" name="due_date" type="date" value="<?php echo esc_attr( (string) $erp_homework->due_date ); ?>"></p>
					<p class="erp-field"><label for="erp_hw_max">Maximum marks <span>(optional)</span></label><input id="erp_hw_max" name="max_marks" type="text" inputmode="decimal" maxlength="7" value="<?php echo esc_attr( mags_erp_format_marks( $erp_homework->max_marks ) ); ?>"></p>
				</div>
				<p class="erp-field"><label for="erp_hw_text">Instructions <span>(optional)</span></label><textarea id="erp_hw_text" name="instructions" rows="3" maxlength="5000"><?php echo esc_textarea( $erp_homework->instructions ); ?></textarea></p>
				<?php if ( $erp_room > 0 ) : ?>
					<?php mags_erp_file_input( 'homework_files', 'Add more files', false, sprintf( 'You can add %d more.', $erp_room ), true ); ?>
				<?php else : ?>
					<p class="erp-hint">This homework has <?php echo esc_html( MAGS_ERP_HOMEWORK_FILES ); ?> files, the most it can carry. Take one off below to add another.</p>
				<?php endif; ?>
				<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Save changes</button></div>
			</form>
			<?php if ( $erp_files ) : ?>
				<h3 class="erp-subtitle">Files of this homework</h3>
				<ul class="erp-list erp-list--tight">
					<?php foreach ( $erp_files as $erp_file ) : ?>
						<li class="erp-row">
							<div class="erp-row__main"><strong><?php echo esc_html( $erp_file->original_name ); ?></strong><span><?php echo esc_html( size_format( (int) $erp_file->size, 1 ) ); ?> · added <?php echo esc_html( mags_erp_format_datetime( $erp_file->created_at, 'j M Y' ) ); ?></span></div>
							<div class="erp-row__actions">
								<form method="post" action="<?php echo esc_url( mags_erp_url( 'homework', array( 'id' => (int) $erp_homework->id ) ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Take “' . $erp_file->original_name . '” off this homework? Students will no longer be able to open it.' ); ?>">
									<?php mags_erp_form_fields( 'remove_homework_file' ); ?>
									<input type="hidden" name="link_id" value="<?php echo esc_attr( $erp_file->link_id ); ?>">
									<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Take off</button>
								</form>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</details>
	</section>

	<section aria-labelledby="erp-answers-title">
		<h2 id="erp-answers-title" class="erp-section-title">Answers <span class="erp-count"><?php echo esc_html( count( $erp_students ) ); ?></span></h2>
		<?php if ( ! $erp_students ) : ?>
			<p class="erp-card erp-empty">This class has no students yet.</p>
		<?php else : ?>
			<ul class="erp-list">
				<?php foreach ( $erp_students as $erp_student ) : $erp_sub = $erp_submissions[ (int) $erp_student->id ] ?? null; ?>
					<li class="erp-row erp-answer">
						<div class="erp-row__main">
							<strong><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_student->full_name ); ?><?php if ( ! empty( $erp_student->now_in ) ) : ?> <span class="erp-chip">Now in <?php echo esc_html( $erp_student->now_in ); ?></span><?php endif; ?></strong>
							<?php if ( ! $erp_sub ) : ?>
								<span class="erp-row__chips"><span class="erp-chip <?php echo $erp_homework->due_date && $erp_homework->due_date < $erp_today ? 'erp-chip--status-A' : 'erp-chip--orange'; ?>">Not answered</span></span>
							<?php else : ?>
								<span>Sent <?php echo esc_html( mags_erp_format_datetime( $erp_sub->submitted_at ) ); ?><?php if ( $erp_homework->due_date && substr( $erp_sub->submitted_at, 0, 10 ) > $erp_homework->due_date ) : ?> · <b class="erp-late">late</b><?php endif; ?></span>
								<?php mags_erp_file_link( (int) $erp_sub->file_id ); ?>
								<?php if ( '' !== trim( $erp_sub->student_note ) ) : ?><span class="erp-quote">“<?php echo esc_html( $erp_sub->student_note ); ?>”</span><?php endif; ?>
								<?php if ( $erp_sub->checked_at ) : ?>
									<span class="erp-row__chips"><span class="erp-chip erp-chip--green">Checked<?php echo null !== $erp_sub->marks ? ' · ' . esc_html( mags_erp_format_marks( $erp_sub->marks ) . ( null !== $erp_homework->max_marks ? ' / ' . mags_erp_format_marks( $erp_homework->max_marks ) : '' ) ) : ''; ?></span></span>
									<?php if ( '' !== trim( $erp_sub->feedback ) ) : ?><span class="erp-quote erp-quote--teacher"><?php echo esc_html( $erp_sub->feedback ); ?></span><?php endif; ?>
								<?php else : ?>
									<span class="erp-row__chips"><span class="erp-chip erp-chip--blue">Waiting for checking</span></span>
								<?php endif; ?>
							<?php endif; ?>
						</div>
						<?php if ( $erp_sub ) : ?>
							<details class="erp-grade" <?php echo $erp_sub->checked_at ? '' : 'open'; ?>>
								<summary class="erp-btn erp-btn--ghost erp-btn--sm"><?php echo $erp_sub->checked_at ? 'Change marks / comment' : 'Give marks / comment'; ?></summary>
								<form class="erp-grade__form" method="post" action="<?php echo esc_url( mags_erp_url( 'homework', array( 'id' => (int) $erp_homework->id ) ) ); ?>">
									<?php mags_erp_form_fields( 'grade_homework' ); ?>
									<input type="hidden" name="submission_id" value="<?php echo esc_attr( $erp_sub->id ); ?>">
									<p class="erp-field erp-field--marks"><label for="erp_marks_<?php echo esc_attr( $erp_sub->id ); ?>">Marks<?php if ( null !== $erp_homework->max_marks ) : ?> <span>/ <?php echo esc_html( mags_erp_format_marks( $erp_homework->max_marks ) ); ?></span><?php endif; ?></label><input id="erp_marks_<?php echo esc_attr( $erp_sub->id ); ?>" name="marks" type="text" inputmode="decimal" maxlength="7" placeholder="–" value="<?php echo esc_attr( mags_erp_format_marks( $erp_sub->marks ) ); ?>"></p>
									<p class="erp-field"><label for="erp_feedback_<?php echo esc_attr( $erp_sub->id ); ?>">Comment</label><textarea id="erp_feedback_<?php echo esc_attr( $erp_sub->id ); ?>" name="feedback" rows="2" maxlength="2000" placeholder="Good work…"><?php echo esc_textarea( $erp_sub->feedback ); ?></textarea></p>
									<button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Save</button>
								</form>
							</details>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
	return;
endif;

$erp_list = mags_erp_classes_homework( array_keys( $erp_class_map ) );
$erp_gone = array();
foreach ( mags_erp_classes_homework( array_keys( $erp_class_map ), 100, true ) as $erp_item ) { $erp_gone[] = array( (int) $erp_item->id, $erp_item->title, mags_erp_class_label( $erp_class_map[ (int) $erp_item->class_id ] ) . ' · removed ' . mags_erp_format_datetime( $erp_item->hidden_at, 'j M Y' ) . ' · ' . sprintf( _n( '%d answer kept', '%d answers kept', (int) $erp_item->submitted_count, 'mags-erp' ), (int) $erp_item->submitted_count ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>Homework</h1>
</header>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">No class has been given to you yet. Please ask the school office to add your classes.</p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-homework-form-title">
		<h2 id="erp-homework-form-title">Give homework</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'homework' ) ); ?>" enctype="multipart/form-data">
			<?php mags_erp_form_fields( 'save_homework' ); ?>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_hw_class">Class</label><select id="erp_hw_class" name="class_id" required><?php if ( count( $erp_classes ) > 1 ) : ?><option value="">Choose class</option><?php endif; ?><?php foreach ( $erp_classes as $erp_class ) : ?><option value="<?php echo esc_attr( $erp_class->id ); ?>"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></option><?php endforeach; ?></select></p>
				<p class="erp-field"><label for="erp_hw_title">Title</label><input id="erp_hw_title" name="title" type="text" maxlength="160" placeholder="Maths: Exercise 4.2, Q1–10" required></p>
				<p class="erp-field"><label for="erp_hw_due">Due date <span>(optional)</span></label><input id="erp_hw_due" name="due_date" type="date" min="<?php echo esc_attr( $erp_today ); ?>"></p>
				<p class="erp-field"><label for="erp_hw_max">Maximum marks <span>(optional)</span></label><input id="erp_hw_max" name="max_marks" type="text" inputmode="decimal" maxlength="7" placeholder="10"></p>
			</div>
			<p class="erp-field"><label for="erp_hw_text">Instructions <span>(optional)</span></label><textarea id="erp_hw_text" name="instructions" rows="3" maxlength="5000" placeholder="What the students have to do…"></textarea></p>
			<?php mags_erp_file_input( 'homework_files', 'Attach files', false, 'Worksheet or question paper. You can choose several files and add more later.', true ); ?>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Give homework</button></div>
		</form>
	</section>

	<section aria-labelledby="erp-homework-list-title">
		<h2 id="erp-homework-list-title" class="erp-section-title">Given homework <span class="erp-count"><?php echo esc_html( count( $erp_list ) ); ?></span></h2>
		<?php if ( ! $erp_list ) : ?>
			<p class="erp-card erp-empty">No homework yet. Give the first one above.</p>
		<?php else : ?>
			<ul class="erp-list">
				<?php foreach ( $erp_list as $erp_item ) : $erp_item_class = $erp_class_map[ (int) $erp_item->class_id ]; $erp_pending = (int) $erp_item->submitted_count - (int) $erp_item->checked_count; ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php echo esc_html( $erp_item->title ); ?></strong>
							<span><?php echo esc_html( mags_erp_class_label( $erp_item_class ) ); ?> · given <?php echo esc_html( mags_erp_format_datetime( $erp_item->created_at, 'j M Y' ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( $erp_item->teacher_id ) ); ?><?php if ( $erp_item->due_date ) : ?> · due <?php echo esc_html( mags_erp_format_date( $erp_item->due_date, 'j M Y' ) ); ?><?php endif; ?></span>
							<span class="erp-row__chips">
								<span class="erp-chip erp-chip--blue"><?php echo esc_html( $erp_item->submitted_count ); ?> of <?php echo esc_html( $erp_item_class->student_count ); ?> answered</span>
								<?php if ( $erp_pending > 0 ) : ?><span class="erp-chip erp-chip--orange"><?php echo esc_html( $erp_pending ); ?> to check</span><?php elseif ( (int) $erp_item->submitted_count > 0 ) : ?><span class="erp-chip erp-chip--green">All checked</span><?php endif; ?>
							</span>
						</div>
						<div class="erp-row__actions">
							<a class="erp-btn <?php echo $erp_pending > 0 ? 'erp-btn--orange' : 'erp-btn--ghost'; ?> erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'homework', array( 'id' => (int) $erp_item->id ) ) ); ?>"><?php echo $erp_pending > 0 ? 'Check answers' : 'Open'; ?><span class="erp-sr"> <?php echo esc_html( $erp_item->title ); ?></span></a>
							<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'homework', array( 'id' => (int) $erp_item->id, 'change' => 1 ) ) ); ?>">Change<span class="erp-sr"> <?php echo esc_html( $erp_item->title ); ?></span></a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php mags_erp_removed_list( $erp_gone, 'delete_homework', 'homework_id', 'homework', 'Removed homework' ); ?>
	</section>
<?php endif; ?>
