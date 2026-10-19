<?php
/** Office: add students, move them between classes, reset passwords. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes   = mags_erp_get_classes();
$erp_class_map = array();
foreach ( $erp_classes as $erp_class ) { $erp_class_map[ (int) $erp_class->id ] = mags_erp_class_label( $erp_class ); }

// phpcs:disable WordPress.Security.NonceVerification -- read-only list filters.
$erp_class_id = isset( $_GET['class'] ) ? absint( $_GET['class'] ) : 0;
$erp_class_id = isset( $erp_class_map[ $erp_class_id ] ) ? $erp_class_id : 0;
$erp_search   = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : '';
$erp_page     = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
$erp_edit     = isset( $_GET['edit'] ) ? mags_erp_get_student( absint( $_GET['edit'] ) ) : null;
// phpcs:enable

$erp_per_page = 50;
list( $erp_students, $erp_total ) = mags_erp_query_students( $erp_class_id, $erp_search, $erp_page, $erp_per_page );
if ( $erp_class_id ) { $erp_students = mags_erp_natural_sort( $erp_students, array( 'roll_no', 'full_name' ) ); }
$erp_pages    = (int) ceil( $erp_total / $erp_per_page );
$erp_form_class = $erp_edit ? (int) $erp_edit->class_id : $erp_class_id;
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Students</h1>
</header>

<?php if ( ! $erp_class_map ) : ?>
	<p class="erp-card erp-empty">Add a class first, then add its students. <a class="erp-btn erp-btn--orange erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Add classes</a></p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-student-form-title">
		<h2 id="erp-student-form-title"><?php echo $erp_edit ? 'Edit student' : 'Add a student'; ?></h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'students' ) ); ?>">
			<?php mags_erp_form_fields( 'save_student' ); ?>
			<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_edit ? $erp_edit->id : 0 ); ?>">
			<div class="erp-form__grid">
				<?php if ( $erp_edit ) : ?>
					<p class="erp-field"><label for="erp_student_adm">Admission number</label><input id="erp_student_adm" type="text" value="<?php echo esc_attr( $erp_edit->admission_no ); ?>" readonly><small>This is the login ID and cannot be changed.</small></p>
				<?php else : ?>
					<p class="erp-field"><label for="erp_student_adm">Admission number</label><input id="erp_student_adm" name="admission_no" type="text" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9\/._\-]*" autocomplete="off" autocapitalize="characters" spellcheck="false" required><small>The student and parents sign in with this number.</small></p>
				<?php endif; ?>
				<p class="erp-field"><label for="erp_student_name">Student’s name</label><input id="erp_student_name" name="erp_name" type="text" maxlength="120" autocomplete="off" value="<?php echo esc_attr( $erp_edit ? $erp_edit->full_name : '' ); ?>" required></p>
				<p class="erp-field"><label for="erp_student_class">Class</label><select id="erp_student_class" name="class_id" required><option value="">Choose class</option><?php foreach ( $erp_class_map as $erp_map_id => $erp_map_label ) : ?><option value="<?php echo esc_attr( $erp_map_id ); ?>" <?php selected( $erp_form_class, $erp_map_id ); ?>><?php echo esc_html( $erp_map_label ); ?></option><?php endforeach; ?></select></p>
				<p class="erp-field"><label for="erp_student_roll">Roll number <span>(optional)</span></label><input id="erp_student_roll" name="roll_no" type="text" inputmode="numeric" maxlength="10" autocomplete="off" value="<?php echo esc_attr( $erp_edit ? $erp_edit->roll_no : '' ); ?>"></p>
			</div>
			<div class="erp-form__actions">
				<button class="erp-btn erp-btn--orange" type="submit"><?php echo $erp_edit ? 'Save student' : 'Add student'; ?></button>
				<?php if ( $erp_edit ) : ?><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'students', array( 'class' => (int) $erp_edit->class_id ) ) ); ?>">Cancel</a><?php endif; ?>
			</div>
		</form>
	</section>

	<section aria-labelledby="erp-student-list-title">
		<h2 id="erp-student-list-title" class="erp-section-title">Student list <span class="erp-count"><?php echo esc_html( number_format_i18n( $erp_total ) ); ?></span></h2>
		<form class="erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'students' ) ); ?>">
			<?php if ( ! get_option( 'permalink_structure' ) ) : ?><input type="hidden" name="mags_erp" value="students"><?php endif; ?>
			<p class="erp-field"><label for="erp_filter_class">Class</label><select id="erp_filter_class" name="class"><option value="">All classes</option><?php foreach ( $erp_class_map as $erp_map_id => $erp_map_label ) : ?><option value="<?php echo esc_attr( $erp_map_id ); ?>" <?php selected( $erp_class_id, $erp_map_id ); ?>><?php echo esc_html( $erp_map_label ); ?></option><?php endforeach; ?></select></p>
			<p class="erp-field"><label for="erp_filter_q">Name or admission number</label><input id="erp_filter_q" name="q" type="search" maxlength="60" value="<?php echo esc_attr( $erp_search ); ?>"></p>
			<button class="erp-btn erp-btn--navy" type="submit">Show</button>
		</form>

		<?php if ( ! $erp_students ) : ?>
			<p class="erp-card erp-empty"><?php echo esc_html( $erp_class_id || '' !== $erp_search ? 'No students match this filter.' : 'No students yet. Add the first student above.' ); ?></p>
		<?php else : ?>
			<ul class="erp-list">
				<?php foreach ( $erp_students as $erp_student ) : ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_student->full_name ); ?></strong>
							<span>Adm. no. <?php echo esc_html( $erp_student->admission_no ); ?> · <?php echo esc_html( $erp_class_map[ (int) $erp_student->class_id ] ?? 'No class' ); ?></span>
						</div>
						<div class="erp-row__actions">
							<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'students', array( 'edit' => (int) $erp_student->id ) ) ); ?>">Edit</a>
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'students' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Create a new password for ' . $erp_student->full_name . '? The old password will stop working.' ); ?>">
								<?php mags_erp_form_fields( 'reset_password' ); ?>
								<input type="hidden" name="kind" value="student"><input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
								<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">New password</button>
							</form>
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'students' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove ' . $erp_student->full_name . '? Their login and all their attendance will be deleted. This cannot be undone.' ); ?>">
								<?php mags_erp_form_fields( 'delete_student' ); ?>
								<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
								<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $erp_pages > 1 ) : ?>
				<nav class="erp-pager" aria-label="Student list pages">
					<?php if ( $erp_page > 1 ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'students', array( 'class' => $erp_class_id ?: '', 'q' => $erp_search, 'pg' => $erp_page - 1 ) ) ); ?>">← Previous</a><?php endif; ?>
					<span>Page <?php echo esc_html( $erp_page ); ?> of <?php echo esc_html( $erp_pages ); ?></span>
					<?php if ( $erp_page < $erp_pages ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'students', array( 'class' => $erp_class_id ?: '', 'q' => $erp_search, 'pg' => $erp_page + 1 ) ) ); ?>">Next →</a><?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</section>
<?php endif; ?>
