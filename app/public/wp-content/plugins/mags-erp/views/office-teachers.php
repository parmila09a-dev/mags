<?php
/** Office: add teachers, give them classes, reset passwords. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes   = mags_erp_get_classes();
$erp_class_map = array();
foreach ( $erp_classes as $erp_class ) { $erp_class_map[ (int) $erp_class->id ] = mags_erp_class_label( $erp_class ); }
$erp_teachers  = mags_erp_get_teachers();
$erp_edit_id   = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$erp_edit      = $erp_edit_id && mags_erp_is_managed_user( $erp_edit_id, 'mags_teacher' ) ? get_userdata( $erp_edit_id ) : null;
$erp_edit_own  = $erp_edit ? mags_erp_teacher_class_ids( $erp_edit->ID ) : array();
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Teachers</h1>
</header>

<section class="erp-card" aria-labelledby="erp-teacher-form-title">
	<h2 id="erp-teacher-form-title"><?php echo $erp_edit ? 'Edit teacher' : 'Add a teacher'; ?></h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'teachers' ) ); ?>">
		<?php mags_erp_form_fields( 'save_teacher' ); ?>
		<input type="hidden" name="teacher_id" value="<?php echo esc_attr( $erp_edit ? $erp_edit->ID : 0 ); ?>">
		<div class="erp-form__grid">
			<p class="erp-field"><label for="erp_teacher_name">Teacher’s name</label><input id="erp_teacher_name" name="erp_name" type="text" maxlength="120" autocomplete="off" value="<?php echo esc_attr( $erp_edit ? $erp_edit->display_name : '' ); ?>" required></p>
			<?php if ( $erp_edit ) : ?>
				<p class="erp-field"><label for="erp_teacher_username">Username</label><input id="erp_teacher_username" type="text" value="<?php echo esc_attr( $erp_edit->user_login ); ?>" readonly><small>The username cannot be changed.</small></p>
			<?php else : ?>
				<p class="erp-field"><label for="erp_teacher_username">Username</label><input id="erp_teacher_username" name="username" type="text" maxlength="40" pattern="[a-zA-Z][a-zA-Z0-9._\-]{2,39}" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="anita.sharma" required><small>Small letters, numbers, dots or dashes. The teacher signs in with this.</small></p>
			<?php endif; ?>
		</div>
		<fieldset class="erp-checks">
			<legend>Classes this teacher marks</legend>
			<?php if ( ! $erp_class_map ) : ?>
				<p class="erp-hint">No classes yet. <a href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Add classes</a> first, then tick them here.</p>
			<?php endif; ?>
			<?php foreach ( $erp_class_map as $erp_class_id => $erp_class_label ) : ?>
				<label class="erp-pill"><input type="checkbox" name="class_ids[]" value="<?php echo esc_attr( $erp_class_id ); ?>" <?php checked( in_array( $erp_class_id, $erp_edit_own, true ) ); ?>><span><?php echo esc_html( $erp_class_label ); ?></span></label>
			<?php endforeach; ?>
		</fieldset>
		<div class="erp-form__actions">
			<button class="erp-btn erp-btn--orange" type="submit"><?php echo $erp_edit ? 'Save teacher' : 'Add teacher'; ?></button>
			<?php if ( $erp_edit ) : ?><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'teachers' ) ); ?>">Cancel</a><?php endif; ?>
		</div>
	</form>
</section>

<section aria-labelledby="erp-teacher-list-title">
	<h2 id="erp-teacher-list-title" class="erp-section-title">All teachers <span class="erp-count"><?php echo esc_html( count( $erp_teachers ) ); ?></span></h2>
	<?php if ( ! $erp_teachers ) : ?>
		<p class="erp-card erp-empty">No teachers yet. Add the first teacher above.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_teachers as $erp_teacher ) : $erp_own = array_intersect_key( $erp_class_map, array_flip( mags_erp_teacher_class_ids( $erp_teacher->ID ) ) ); ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_teacher->display_name ); ?></strong>
						<span>Login ID: <?php echo esc_html( $erp_teacher->user_login ); ?></span>
						<span class="erp-row__chips"><?php if ( $erp_own ) : foreach ( $erp_own as $erp_own_label ) : ?><span class="erp-chip erp-chip--blue"><?php echo esc_html( $erp_own_label ); ?></span><?php endforeach; else : ?><span class="erp-chip erp-chip--orange">No class yet</span><?php endif; ?></span>
					</div>
					<div class="erp-row__actions">
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'teachers', array( 'edit' => $erp_teacher->ID ) ) ); ?>">Edit</a>
						<form method="post" action="<?php echo esc_url( mags_erp_url( 'teachers' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Create a new password for ' . $erp_teacher->display_name . '? The old password will stop working.' ); ?>">
							<?php mags_erp_form_fields( 'reset_password' ); ?>
							<input type="hidden" name="kind" value="teacher"><input type="hidden" name="teacher_id" value="<?php echo esc_attr( $erp_teacher->ID ); ?>">
							<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">New password</button>
						</form>
						<form method="post" action="<?php echo esc_url( mags_erp_url( 'teachers' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove ' . $erp_teacher->display_name . '? Their login will be deleted.' ); ?>">
							<?php mags_erp_form_fields( 'delete_teacher' ); ?>
							<input type="hidden" name="teacher_id" value="<?php echo esc_attr( $erp_teacher->ID ); ?>">
							<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
