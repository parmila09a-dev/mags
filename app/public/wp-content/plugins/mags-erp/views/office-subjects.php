<?php
/** Office: the school's subjects and the periods of the school day. Both are used by the timetable; subjects also by exams. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_subjects     = mags_erp_get_subjects();
$erp_periods      = mags_erp_get_periods();
$erp_edit         = mags_erp_get_subject( mags_erp_get_int( 'edit' ) );
$erp_edit_period  = mags_erp_get_period( mags_erp_get_int( 'period' ) );
$erp_last_period  = $erp_periods ? end( $erp_periods ) : null;
$erp_gone         = array();
foreach ( mags_erp_get_subjects( true ) as $erp_subject ) { $erp_gone[] = array( (int) $erp_subject->id, $erp_subject->name, 'Removed ' . mags_erp_format_datetime( $erp_subject->hidden_at, 'j M Y' ) ); }
$erp_gone_periods = array();
foreach ( mags_erp_get_periods( true ) as $erp_period ) { $erp_gone_periods[] = array( (int) $erp_period->id, $erp_period->label, mags_erp_period_time( $erp_period ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Subjects and periods</h1>
</header>

<section class="erp-card" aria-labelledby="erp-subject-form-title">
	<h2 id="erp-subject-form-title"><?php echo $erp_edit ? 'Rename subject' : 'Add a subject'; ?></h2>
	<form class="erp-form erp-form--row" method="post" action="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>">
		<?php mags_erp_form_fields( 'save_subject' ); ?>
		<input type="hidden" name="subject_id" value="<?php echo esc_attr( $erp_edit ? $erp_edit->id : 0 ); ?>">
		<p class="erp-field erp-field--wide"><label for="erp_subject_name">Subject name</label><input id="erp_subject_name" name="erp_name" type="text" maxlength="80" placeholder="Mathematics" value="<?php echo esc_attr( $erp_edit ? $erp_edit->name : '' ); ?>" required></p>
		<div class="erp-form__actions">
			<button class="erp-btn erp-btn--orange" type="submit"><?php echo $erp_edit ? 'Save subject' : 'Add subject'; ?></button>
			<?php if ( $erp_edit ) : ?><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>">Cancel</a><?php endif; ?>
		</div>
	</form>
</section>

<section aria-labelledby="erp-subject-list-title">
	<h2 id="erp-subject-list-title" class="erp-section-title">Subjects <span class="erp-count"><?php echo esc_html( count( $erp_subjects ) ); ?></span></h2>
	<?php if ( ! $erp_subjects ) : ?>
		<p class="erp-card erp-empty">No subjects yet. Add the first subject above.</p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_subjects as $erp_subject ) : ?>
				<li class="erp-row">
					<div class="erp-row__main"><strong><?php echo esc_html( $erp_subject->name ); ?></strong></div>
					<div class="erp-row__actions">
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'subjects', array( 'edit' => (int) $erp_subject->id ) ) ); ?>">Rename</a>
						<form method="post" action="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove ' . $erp_subject->name . '? Marks already entered for it are kept.' ); ?>">
							<?php mags_erp_form_fields( 'delete_subject' ); ?>
							<input type="hidden" name="subject_id" value="<?php echo esc_attr( $erp_subject->id ); ?>">
							<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php mags_erp_removed_list( $erp_gone, 'delete_subject', 'subject_id', 'subjects', 'Removed subjects' ); ?>
</section>

<section class="erp-card" aria-labelledby="erp-period-form-title">
	<h2 id="erp-period-form-title"><?php echo $erp_edit_period ? 'Change period' : 'Add a period or break'; ?></h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>">
		<?php mags_erp_form_fields( 'save_period' ); ?>
		<input type="hidden" name="period_id" value="<?php echo esc_attr( $erp_edit_period ? $erp_edit_period->id : 0 ); ?>">
		<div class="erp-form__grid erp-form__grid--three">
			<p class="erp-field"><label for="erp_period_label">Name</label><input id="erp_period_label" name="label" type="text" maxlength="40" placeholder="1, 2, Lunch…" value="<?php echo esc_attr( $erp_edit_period ? $erp_edit_period->label : '' ); ?>" required></p>
			<p class="erp-field"><label for="erp_period_start">Starts</label><input id="erp_period_start" name="start_time" type="time" value="<?php echo esc_attr( $erp_edit_period ? $erp_edit_period->start_time : ( $erp_last_period ? $erp_last_period->end_time : '08:00' ) ); ?>" required></p>
			<p class="erp-field"><label for="erp_period_end">Ends</label><input id="erp_period_end" name="end_time" type="time" value="<?php echo esc_attr( $erp_edit_period ? $erp_edit_period->end_time : '' ); ?>" required></p>
		</div>
		<p class="erp-check"><label><input type="checkbox" name="is_break" value="1" <?php checked( $erp_edit_period && $erp_edit_period->is_break ); ?>> <span>This is a break (no subject is taught)</span></label></p>
		<div class="erp-form__actions">
			<button class="erp-btn erp-btn--orange" type="submit"><?php echo $erp_edit_period ? 'Save period' : 'Add period'; ?></button>
			<?php if ( $erp_edit_period ) : ?><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>">Cancel</a><?php endif; ?>
		</div>
	</form>
	<p class="erp-hint">The periods are the same for every class and every day. They are the rows of the timetable.</p>
</section>

<section aria-labelledby="erp-period-list-title">
	<h2 id="erp-period-list-title" class="erp-section-title">School day <span class="erp-count"><?php echo esc_html( count( $erp_periods ) ); ?></span></h2>
	<?php if ( ! $erp_periods ) : ?>
		<p class="erp-card erp-empty">No periods yet. Add the periods of the school day above, then fill the timetable.</p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_periods as $erp_period ) : ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_period->is_break ? $erp_period->label : 'Period ' . $erp_period->label ); ?><?php if ( $erp_period->is_break ) : ?> <span class="erp-chip">Break</span><?php endif; ?></strong>
						<span><?php echo esc_html( mags_erp_period_time( $erp_period ) ); ?></span>
					</div>
					<div class="erp-row__actions">
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'subjects', array( 'period' => (int) $erp_period->id ) ) ); ?>">Change</a>
						<form method="post" action="<?php echo esc_url( mags_erp_url( 'subjects' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove “' . $erp_period->label . '” from every timetable? Its entries are kept and return if you restore it.' ); ?>">
							<?php mags_erp_form_fields( 'delete_period' ); ?>
							<input type="hidden" name="period_id" value="<?php echo esc_attr( $erp_period->id ); ?>">
							<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="erp-removed"><a class="erp-btn erp-btn--navy erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'timetable' ) ); ?>">Fill the timetable</a></p>
	<?php endif; ?>
	<?php mags_erp_removed_list( $erp_gone_periods, 'delete_period', 'period_id', 'subjects', 'Removed periods' ); ?>
</section>
