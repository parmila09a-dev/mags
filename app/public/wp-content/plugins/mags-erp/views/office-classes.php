<?php
/** Office: add, rename and remove classes, choose the class teacher, restore removed classes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes  = mags_erp_get_classes();
$erp_teachers = mags_erp_class_teacher_names();
$erp_edit     = isset( $_GET['edit'] ) ? mags_erp_get_class( absint( $_GET['edit'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
$erp_edit_own = $erp_edit ? ( $erp_teachers[ (int) $erp_edit->id ] ?? array() ) : array();
$erp_removed  = array();
foreach ( mags_erp_get_removed_classes() as $erp_class ) { $erp_removed[] = array( (int) $erp_class->id, mags_erp_class_label( $erp_class ), 'Removed ' . mags_erp_format_datetime( $erp_class->hidden_at, 'j M Y' ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Classes</h1>
</header>

<section class="erp-card" aria-labelledby="erp-class-form-title">
	<h2 id="erp-class-form-title"><?php echo $erp_edit ? 'Edit class' : 'Add a class'; ?></h2>
	<form class="erp-form erp-form--row" method="post" action="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">
		<?php mags_erp_form_fields( 'save_class' ); ?>
		<input type="hidden" name="class_id" value="<?php echo esc_attr( $erp_edit ? $erp_edit->id : 0 ); ?>">
		<p class="erp-field"><label for="erp_class_name">Class name</label><input id="erp_class_name" name="erp_name" type="text" maxlength="60" placeholder="Class 5" value="<?php echo esc_attr( $erp_edit ? $erp_edit->name : '' ); ?>" required></p>
		<p class="erp-field"><label for="erp_class_section">Section <span>(optional)</span></label><input id="erp_class_section" name="section" type="text" maxlength="20" placeholder="A" value="<?php echo esc_attr( $erp_edit ? $erp_edit->section : '' ); ?>"></p>
		<?php if ( $erp_edit ) : ?>
			<p class="erp-field erp-field--wide"><label for="erp_class_teacher">Class teacher</label><select id="erp_class_teacher" name="class_teacher_id"><option value="0">No class teacher</option><?php foreach ( $erp_edit_own as $erp_teacher_id => $erp_teacher_name ) : ?><option value="<?php echo esc_attr( $erp_teacher_id ); ?>" <?php selected( (int) $erp_edit->class_teacher_id, $erp_teacher_id ); ?>><?php echo esc_html( $erp_teacher_name ); ?></option><?php endforeach; ?></select><small>The class teacher answers the leave requests of this class. Only teachers who have this class are listed (Teachers → Edit).</small></p>
		<?php endif; ?>
		<div class="erp-form__actions">
			<button class="erp-btn erp-btn--orange" type="submit"><?php echo $erp_edit ? 'Save class' : 'Add class'; ?></button>
			<?php if ( $erp_edit ) : ?><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Cancel</a><?php endif; ?>
		</div>
	</form>
</section>

<section aria-labelledby="erp-class-list-title">
	<h2 id="erp-class-list-title" class="erp-section-title">All classes <span class="erp-count"><?php echo esc_html( count( $erp_classes ) ); ?></span></h2>
	<?php if ( ! $erp_classes ) : ?>
		<p class="erp-card erp-empty">No classes yet. Add the first class above.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_classes as $erp_class ) : $erp_names = $erp_teachers[ (int) $erp_class->id ] ?? array(); ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></strong>
						<span><?php echo esc_html( sprintf( _n( '%s student', '%s students', (int) $erp_class->student_count, 'mags-erp' ), number_format_i18n( (int) $erp_class->student_count ) ) ); ?> · <?php echo esc_html( $erp_names ? 'Teacher: ' . implode( ', ', $erp_names ) : 'No teacher yet' ); ?></span>
						<span class="erp-row__chips"><?php if ( isset( $erp_names[ (int) $erp_class->class_teacher_id ] ) ) : ?><span class="erp-chip erp-chip--blue">Class teacher: <?php echo esc_html( $erp_names[ (int) $erp_class->class_teacher_id ] ); ?></span><?php else : ?><span class="erp-chip erp-chip--orange">No class teacher</span><?php endif; ?></span>
					</div>
					<div class="erp-row__actions">
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'students', array( 'class' => (int) $erp_class->id ) ) ); ?>">Students</a>
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'timetable', array( 'class' => (int) $erp_class->id ) ) ); ?>">Timetable</a>
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'classes', array( 'edit' => (int) $erp_class->id ) ) ); ?>">Edit</a>
						<?php if ( ! (int) $erp_class->student_count ) : ?>
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove ' . mags_erp_class_label( $erp_class ) . '? Its records are kept and it can be restored.' ); ?>">
								<?php mags_erp_form_fields( 'delete_class' ); ?>
								<input type="hidden" name="class_id" value="<?php echo esc_attr( $erp_class->id ); ?>">
								<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
							</form>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php mags_erp_removed_list( $erp_removed, 'delete_class', 'class_id', 'classes', 'Removed classes' ); ?>
</section>
