<?php
/** Teacher: make time slots for parent-teacher meetings and see who booked them. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_today   = mags_erp_today();
$erp_classes = mags_erp_teacher_classes( $ctx['user']->ID );
$erp_slots   = mags_erp_teacher_ptm_slots( $ctx['user']->ID, $erp_today );
$erp_labels  = mags_erp_all_class_labels();
$erp_days    = array();
foreach ( $erp_slots as $erp_slot ) { $erp_days[ $erp_slot->slot_date ][] = $erp_slot; }
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>Parent-teacher meetings</h1>
</header>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">No class has been given to you yet, so no parent can book a meeting with you.</p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-ptm-make-title">
		<h2 id="erp-ptm-make-title">Make time slots</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'ptm' ) ); ?>">
			<?php mags_erp_form_fields( 'make_ptm_slots' ); ?>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_ptm_date">Day</label><input id="erp_ptm_date" name="slot_date" type="date" min="<?php echo esc_attr( $erp_today ); ?>" max="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $erp_today . ' +' . MAGS_ERP_PTM_DAYS_AHEAD . ' days' ) ) ); ?>" required></p>
				<p class="erp-field"><label for="erp_ptm_class">For the parents of</label><select id="erp_ptm_class" name="class_id"><?php if ( count( $erp_classes ) > 1 ) : ?><option value="0">All my classes</option><?php endif; ?><?php mags_erp_class_options( $erp_classes, 0, '' ); ?></select></p>
				<p class="erp-field"><label for="erp_ptm_from">From</label><input id="erp_ptm_from" name="from_time" type="time" step="300" value="10:00" required></p>
				<p class="erp-field"><label for="erp_ptm_to">To</label><input id="erp_ptm_to" name="to_time" type="time" step="300" value="12:00" required></p>
				<p class="erp-field"><label for="erp_ptm_minutes">Minutes for each parent</label><select id="erp_ptm_minutes" name="minutes"><?php foreach ( array( 5, 10, 15, 20, 30 ) as $erp_minutes ) : ?><option value="<?php echo esc_attr( $erp_minutes ); ?>" <?php selected( 10, $erp_minutes ); ?>><?php echo esc_html( $erp_minutes ); ?> minutes</option><?php endforeach; ?></select></p>
				<p class="erp-field"><label for="erp_ptm_place">Place <span>(optional)</span></label><input id="erp_ptm_place" name="place" type="text" maxlength="80" placeholder="Room 12"></p>
			</div>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Make slots</button></div>
		</form>
		<p class="erp-hint">10:00 to 12:00 with 10 minutes makes 12 slots. Times you already have a slot are left out. Each slot can be booked by one parent only.</p>
	</section>

	<section aria-labelledby="erp-ptm-list-title">
		<h2 id="erp-ptm-list-title" class="erp-section-title">Coming slots <span class="erp-count"><?php echo esc_html( count( $erp_slots ) ); ?></span></h2>
		<?php if ( ! $erp_slots ) : ?>
			<p class="erp-card erp-empty">No slots yet. Make them above, then tell the parents.</p>
		<?php endif; ?>
		<?php foreach ( $erp_days as $erp_day => $erp_list ) : $erp_booked = count( array_filter( $erp_list, static function ( $erp_slot ) { return (int) $erp_slot->student_id > 0; } ) ); ?>
			<h3 class="erp-subtitle"><?php echo esc_html( mags_erp_format_date( $erp_day, 'l, j F Y' ) ); ?> <span class="erp-chip"><?php echo esc_html( sprintf( '%1$d of %2$d booked', $erp_booked, count( $erp_list ) ) ); ?></span></h3>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_list as $erp_slot ) : ?>
					<li class="erp-row">
						<div class="erp-row__main">
							<strong><?php echo esc_html( mags_erp_format_time( $erp_slot->start_time ) . ' – ' . mags_erp_format_time( $erp_slot->end_time ) ); ?></strong>
							<span><?php echo esc_html( ( (int) $erp_slot->class_id ? ( $erp_labels[ (int) $erp_slot->class_id ] ?? '' ) : 'All my classes' ) . ( '' !== $erp_slot->place ? ' · ' . $erp_slot->place : '' ) ); ?></span>
							<span class="erp-row__chips"><?php if ( (int) $erp_slot->student_id ) : ?><span class="erp-chip erp-chip--green">Parent of <?php echo esc_html( $erp_slot->student_name . ( '' !== $erp_slot->student_class ? ' (' . $erp_slot->student_class . ')' : '' ) ); ?></span><?php if ( '' !== $erp_slot->phone ) : ?><a class="erp-chip" href="<?php echo esc_url( 'tel:' . $erp_slot->phone ); ?>"><?php echo esc_html( $erp_slot->phone ); ?></a><?php endif; ?><?php else : ?><span class="erp-chip">Free</span><?php endif; ?></span>
						</div>
						<div class="erp-row__actions">
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'ptm' ) ); ?>" data-erp-confirm="<?php echo esc_attr( (int) $erp_slot->student_id ? 'Remove this slot? The parent of ' . $erp_slot->student_name . ' has booked it and will see that the meeting is cancelled.' : 'Remove this free slot?' ); ?>">
								<?php mags_erp_form_fields( 'remove_ptm_slot' ); ?>
								<input type="hidden" name="slot_id" value="<?php echo esc_attr( $erp_slot->id ); ?>">
								<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">Remove<span class="erp-sr"> slot at <?php echo esc_html( mags_erp_format_time( $erp_slot->start_time ) ); ?></span></button>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</section>
<?php endif; ?>
