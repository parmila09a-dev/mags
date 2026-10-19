<?php
/** Teacher: share notes files with own classes, remove and restore them. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes   = mags_erp_teacher_classes( $ctx['user']->ID );
$erp_class_map = array();
foreach ( $erp_classes as $erp_class ) { $erp_class_map[ (int) $erp_class->id ] = $erp_class; }
$erp_notes = mags_erp_classes_notes( array_keys( $erp_class_map ) );
$erp_gone  = array();
foreach ( mags_erp_classes_notes( array_keys( $erp_class_map ), 100, true ) as $erp_note ) { $erp_gone[] = array( (int) $erp_note->id, $erp_note->title, mags_erp_class_label( $erp_class_map[ (int) $erp_note->class_id ] ) . ' · removed ' . mags_erp_format_datetime( $erp_note->hidden_at, 'j M Y' ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>Notes</h1>
</header>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">No class has been given to you yet. Please ask the school office to add your classes.</p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-note-form-title">
		<h2 id="erp-note-form-title">Share notes</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'notes' ) ); ?>" enctype="multipart/form-data">
			<?php mags_erp_form_fields( 'save_note' ); ?>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_note_class">Class</label><select id="erp_note_class" name="class_id" required><?php if ( count( $erp_classes ) > 1 ) : ?><option value="">Choose class</option><?php endif; ?><?php foreach ( $erp_classes as $erp_class ) : ?><option value="<?php echo esc_attr( $erp_class->id ); ?>"><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></option><?php endforeach; ?></select></p>
				<p class="erp-field"><label for="erp_note_title">Title</label><input id="erp_note_title" name="title" type="text" maxlength="160" placeholder="Science: Chapter 3 summary" required></p>
			</div>
			<p class="erp-field"><label for="erp_note_text">Description <span>(optional)</span></label><textarea id="erp_note_text" name="description" rows="2" maxlength="2000"></textarea></p>
			<?php mags_erp_file_input( 'note_file', 'Notes file', true ); ?>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Share notes</button></div>
		</form>
	</section>

	<section aria-labelledby="erp-notes-list-title">
		<h2 id="erp-notes-list-title" class="erp-section-title">Shared notes <span class="erp-count"><?php echo esc_html( count( $erp_notes ) ); ?></span></h2>
		<?php if ( ! $erp_notes ) : ?>
			<p class="erp-card erp-empty">No notes shared yet.</p>
		<?php else : ?>
			<ul class="erp-list">
				<?php foreach ( $erp_notes as $erp_note ) : ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php echo esc_html( $erp_note->title ); ?></strong>
							<span><?php echo esc_html( mags_erp_class_label( $erp_class_map[ (int) $erp_note->class_id ] ) ); ?> · <?php echo esc_html( mags_erp_teacher_name( $erp_note->teacher_id ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_note->created_at, 'j M Y' ) ); ?></span>
							<?php if ( '' !== trim( $erp_note->description ) ) : ?><span><?php echo esc_html( $erp_note->description ); ?></span><?php endif; ?>
							<?php mags_erp_file_link( (int) $erp_note->file_id ); ?>
						</div>
						<div class="erp-row__actions">
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'notes' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove “' . $erp_note->title . '”? Students will no longer be able to download it. The file is kept and you can restore it.' ); ?>">
								<?php mags_erp_form_fields( 'delete_note' ); ?>
								<input type="hidden" name="note_id" value="<?php echo esc_attr( $erp_note->id ); ?>">
								<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php mags_erp_removed_list( $erp_gone, 'delete_note', 'note_id', 'notes', 'Removed notes' ); ?>
	</section>
<?php endif; ?>
