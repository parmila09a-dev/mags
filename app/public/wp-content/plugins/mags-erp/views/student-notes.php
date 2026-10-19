<?php
/** Student (and parent): notes shared with the student's own class. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) : ?>
	<section class="erp-card erp-empty"><h1>Student record not found</h1><p>Please contact the school office.</p></section>
	<?php
	return;
endif;
$erp_notes = mags_erp_classes_notes( array( (int) $erp_student->class_id ) );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( mags_erp_get_class( (int) $erp_student->class_id ) ) ); ?></p>
	<h1>Class notes</h1>
</header>

<?php if ( ! $erp_notes ) : ?>
	<p class="erp-card erp-empty">No notes have been shared with your class yet.</p>
<?php else : ?>
	<ul class="erp-list">
		<?php foreach ( $erp_notes as $erp_note ) : ?>
			<li class="erp-row">
				<div class="erp-row__main">
					<strong><?php echo esc_html( $erp_note->title ); ?></strong>
					<span><?php echo esc_html( mags_erp_teacher_name( $erp_note->teacher_id ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_note->created_at, 'j M Y' ) ); ?></span>
					<?php if ( '' !== trim( $erp_note->description ) ) : ?><span><?php echo esc_html( $erp_note->description ); ?></span><?php endif; ?>
				</div>
				<div class="erp-row__actions"><?php mags_erp_file_link( (int) $erp_note->file_id, 'erp-btn erp-btn--navy erp-btn--sm erp-filelink--btn' ); ?></div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
