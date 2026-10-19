<?php
/** Student (and parent): homework of the student's own class, upload the answer, see marks and comments. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) : ?>
	<section class="erp-card erp-empty"><h1>Student record not found</h1><p>Please contact the school office.</p></section>
	<?php
	return;
endif;

$erp_today  = mags_erp_today();
$erp_rows   = mags_erp_student_homework( $erp_student );
$erp_groups = array( 'todo' => array(), 'done' => array() );
foreach ( $erp_rows as $erp_row ) { $erp_groups[ in_array( mags_erp_homework_status( $erp_row ), array( 'pending', 'late' ), true ) ? 'todo' : 'done' ][] = $erp_row; }
$erp_labels = array( 'pending' => array( 'To do', 'erp-chip--orange' ), 'late' => array( 'Overdue', 'erp-chip--status-A' ), 'submitted' => array( 'Sent · waiting for checking', 'erp-chip--blue' ), 'checked' => array( 'Checked', 'erp-chip--green' ) );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( mags_erp_get_class( (int) $erp_student->class_id ) ) ); ?></p>
	<h1>My homework</h1>
</header>

<?php if ( ! $erp_rows ) : ?>
	<p class="erp-card erp-empty">No homework has been given to your class yet.</p>
<?php endif; ?>

<?php foreach ( array( 'todo' => 'To do', 'done' => 'Sent and checked' ) as $erp_group => $erp_group_title ) : if ( ! $erp_groups[ $erp_group ] ) { continue; } ?>
	<section aria-labelledby="erp-hw-<?php echo esc_attr( $erp_group ); ?>">
		<h2 id="erp-hw-<?php echo esc_attr( $erp_group ); ?>" class="erp-section-title"><?php echo esc_html( $erp_group_title ); ?> <span class="erp-count"><?php echo esc_html( count( $erp_groups[ $erp_group ] ) ); ?></span></h2>
		<ul class="erp-list">
			<?php foreach ( $erp_groups[ $erp_group ] as $erp_row ) : $erp_status = mags_erp_homework_status( $erp_row ); ?>
				<li class="erp-card erp-work erp-work--<?php echo esc_attr( $erp_status ); ?>">
					<div class="erp-work__head">
						<div>
							<h3><?php echo esc_html( $erp_row->title ); ?></h3>
							<p><?php echo esc_html( mags_erp_teacher_name( $erp_row->teacher_id ) ); ?> · given <?php echo esc_html( mags_erp_format_datetime( $erp_row->created_at, 'j M Y' ) ); ?><?php if ( $erp_row->due_date ) : ?> · <b>due <?php echo esc_html( mags_erp_format_date( $erp_row->due_date, 'j M Y' ) ); ?></b><?php endif; ?></p>
						</div>
						<span class="erp-chip <?php echo esc_attr( $erp_labels[ $erp_status ][1] ); ?>"><?php echo esc_html( $erp_labels[ $erp_status ][0] ); ?></span>
					</div>
					<?php if ( '' !== trim( $erp_row->instructions ) ) : ?><p class="erp-work__text"><?php echo nl2br( esc_html( $erp_row->instructions ) ); ?></p><?php endif; ?>
					<?php mags_erp_file_link( (int) $erp_row->file_id ); ?>

					<?php if ( 'checked' === $erp_status ) : ?>
						<div class="erp-result">
							<?php if ( null !== $erp_row->marks ) : ?><p class="erp-result__marks"><strong><?php echo esc_html( mags_erp_format_marks( $erp_row->marks ) ); ?></strong><?php if ( null !== $erp_row->max_marks ) : ?><span>/ <?php echo esc_html( mags_erp_format_marks( $erp_row->max_marks ) ); ?></span><?php endif; ?></p><?php endif; ?>
							<div>
								<?php if ( '' !== trim( $erp_row->feedback ) ) : ?><p class="erp-quote erp-quote--teacher"><?php echo esc_html( $erp_row->feedback ); ?></p><?php endif; ?>
								<p class="erp-hint">Checked <?php echo esc_html( mags_erp_format_datetime( $erp_row->checked_at, 'j M Y' ) ); ?>. Your answer: <?php mags_erp_file_link( (int) $erp_row->submission_file_id, 'erp-filelink erp-filelink--inline' ); ?></p>
							</div>
						</div>
					<?php else : ?>
						<?php if ( 'submitted' === $erp_status ) : ?>
							<p class="erp-hint">You sent <?php mags_erp_file_link( (int) $erp_row->submission_file_id, 'erp-filelink erp-filelink--inline' ); ?> on <?php echo esc_html( mags_erp_format_datetime( $erp_row->submitted_at ) ); ?>. You can send a new file until the teacher checks it.</p>
						<?php endif; ?>
						<details class="erp-upload" <?php echo 'submitted' === $erp_status ? '' : 'open'; ?>>
							<summary class="erp-btn erp-btn--ghost erp-btn--sm"><?php echo 'submitted' === $erp_status ? 'Send a different file' : 'Send my answer'; ?></summary>
							<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( mags_erp_url( 'homework' ) ); ?>" enctype="multipart/form-data">
								<?php mags_erp_form_fields( 'submit_homework' ); ?>
								<input type="hidden" name="homework_id" value="<?php echo esc_attr( $erp_row->id ); ?>">
								<?php mags_erp_file_input( 'answer_file', 'My answer', true, 'Take a clear photo of your copy, or choose a document.' ); ?>
								<p class="erp-field"><label for="erp_note_<?php echo esc_attr( $erp_row->id ); ?>">Message to teacher <span>(optional)</span></label><textarea id="erp_note_<?php echo esc_attr( $erp_row->id ); ?>" name="student_note" rows="2" maxlength="1000"></textarea></p>
								<button class="erp-btn erp-btn--orange" type="submit">Send answer</button>
							</form>
						</details>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endforeach; ?>
