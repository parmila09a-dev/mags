<?php
/** Office: add, change, remove and restore the school's holidays and events. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_edit    = mags_erp_get_calendar_entry( mags_erp_get_int( 'edit' ) );
$erp_month   = mags_erp_calendar_month( mags_erp_get_text( 'month' ) ?: ( $erp_edit ? substr( $erp_edit->from_date, 0, 7 ) : '' ) );
$erp_entries = mags_erp_calendar_entries( $erp_month['start'], $erp_month['end'] );
$erp_kinds   = mags_erp_calendar_kinds();
$erp_kind    = $erp_edit ? $erp_edit->kind : 'holiday';
$erp_gone    = array();
foreach ( mags_erp_removed_calendar_entries() as $erp_entry ) { $erp_gone[] = array( (int) $erp_entry->id, $erp_entry->title, ( $erp_kinds[ $erp_entry->kind ][0] ?? 'Entry' ) . ' · ' . mags_erp_calendar_dates( $erp_entry ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>School calendar</h1>
</header>

<section class="erp-card" aria-labelledby="erp-calendar-form-title">
	<h2 id="erp-calendar-form-title"><?php echo $erp_edit ? 'Change holiday or event' : 'Add a holiday or event'; ?></h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'calendar' ) ); ?>">
		<?php mags_erp_form_fields( 'save_calendar' ); ?>
		<input type="hidden" name="entry_id" value="<?php echo esc_attr( $erp_edit ? $erp_edit->id : 0 ); ?>">
		<fieldset class="erp-seg erp-seg--kinds">
			<legend>This is a</legend>
			<?php foreach ( $erp_kinds as $erp_kind_key => $erp_kind_text ) : ?>
				<label class="erp-seg__item erp-seg__item--<?php echo esc_attr( $erp_kind_key ); ?>"><input type="radio" name="kind" value="<?php echo esc_attr( $erp_kind_key ); ?>" <?php checked( $erp_kind, $erp_kind_key ); ?>><span><?php echo esc_html( $erp_kind_text[0] ); ?></span></label>
			<?php endforeach; ?>
		</fieldset>
		<p class="erp-field"><label for="erp_cal_title">Name</label><input id="erp_cal_title" name="title" type="text" maxlength="120" placeholder="Diwali, Annual Day, Summer vacation…" value="<?php echo esc_attr( $erp_edit ? $erp_edit->title : '' ); ?>" required></p>
		<div class="erp-form__grid">
			<p class="erp-field"><label for="erp_cal_from">Date <span>(first day)</span></label><input id="erp_cal_from" name="from_date" type="date" value="<?php echo esc_attr( $erp_edit ? $erp_edit->from_date : '' ); ?>" required></p>
			<p class="erp-field"><label for="erp_cal_to">Last day <span>(empty for one day)</span></label><input id="erp_cal_to" name="to_date" type="date" value="<?php echo esc_attr( $erp_edit && $erp_edit->to_date !== $erp_edit->from_date ? $erp_edit->to_date : '' ); ?>"></p>
		</div>
		<p class="erp-field"><label for="erp_cal_details">Details <span>(optional)</span></label><textarea id="erp_cal_details" name="details" rows="2" maxlength="1000" placeholder="Time, place, what to bring…"><?php echo esc_textarea( $erp_edit ? $erp_edit->details : '' ); ?></textarea></p>
		<div class="erp-form__actions">
			<button class="erp-btn erp-btn--orange" type="submit"><?php echo $erp_edit ? 'Save' : 'Add to calendar'; ?></button>
			<?php if ( $erp_edit ) : ?><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'calendar', array( 'month' => $erp_month['month'] ) ) ); ?>">Cancel</a><?php endif; ?>
		</div>
	</form>
	<p class="erp-hint"><strong>Holiday</strong>: no school. Teachers cannot mark attendance on that day and it is not counted in the attendance percentage. <strong>Event</strong>: a school day with something on; it is only shown in the calendar. Teachers and students see both.</p>
</section>

<section class="erp-card" aria-labelledby="erp-calendar-title">
	<?php mags_erp_print_calendar_head( $erp_month, 'erp-calendar-title' ); ?>
	<p class="erp-month__note"><?php if ( ! $erp_month['is_current'] ) : ?><a href="<?php echo esc_url( mags_erp_url( 'calendar' ) ); ?>">Back to this month</a><?php else : ?>This is what teachers and students see.<?php endif; ?></p>
	<?php mags_erp_print_calendar_grid( $erp_month, $erp_entries ); ?>
</section>

<section aria-labelledby="erp-calendar-list-title">
	<h2 id="erp-calendar-list-title" class="erp-section-title">In <?php echo esc_html( mags_erp_format_date( $erp_month['start'], 'F Y' ) ); ?> <span class="erp-count"><?php echo esc_html( count( $erp_entries ) ); ?></span></h2>
	<?php mags_erp_print_calendar_list( $erp_month, $erp_entries, true ); ?>
	<?php mags_erp_removed_list( $erp_gone, 'delete_calendar', 'entry_id', 'calendar', 'Removed holidays and events' ); ?>
</section>
