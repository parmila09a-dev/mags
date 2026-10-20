<?php
/** Parent (student login): pick a meeting slot with a teacher of the child's class, or give it back. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}
$erp_by_teacher = mags_erp_student_ptm_slots( $erp_student );
$erp_mine       = array();
foreach ( $erp_by_teacher as $erp_list ) { foreach ( $erp_list as $erp_slot ) { if ( $erp_slot->mine ) { $erp_mine[] = $erp_slot; } } }
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( $erp_student->full_name ); ?></p>
	<h1>Parent-teacher meeting</h1>
</header>

<?php if ( $erp_mine ) : ?>
	<section class="erp-card erp-card--alert" aria-labelledby="erp-ptm-mine-title">
		<h2 id="erp-ptm-mine-title">Your meetings</h2>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_mine as $erp_slot ) : ?>
				<li class="erp-row<?php echo $erp_slot->cancelled ? ' erp-row--off' : ''; ?>">
					<div class="erp-row__main">
						<strong><?php echo esc_html( mags_erp_teacher_name( (int) $erp_slot->teacher_id ) ); ?></strong>
						<span><?php echo esc_html( mags_erp_format_date( $erp_slot->slot_date, 'l, j F' ) . ' · ' . mags_erp_format_time( $erp_slot->start_time ) . ' – ' . mags_erp_format_time( $erp_slot->end_time ) . ( '' !== $erp_slot->place ? ' · ' . $erp_slot->place : '' ) ); ?></span>
						<?php if ( $erp_slot->cancelled ) : ?><span class="erp-row__chips"><span class="erp-chip erp-chip--status-A">Cancelled by the teacher. Please pick another slot.</span></span><?php endif; ?>
					</div>
					<?php if ( ! $erp_slot->cancelled && ! mags_erp_ptm_started( $erp_slot ) ) : ?>
						<div class="erp-row__actions">
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'ptm' ) ); ?>" data-erp-confirm="Give this slot back? Another parent can then take it.">
								<?php mags_erp_form_fields( 'cancel_ptm' ); ?>
								<input type="hidden" name="slot_id" value="<?php echo esc_attr( $erp_slot->id ); ?>">
								<button class="erp-btn erp-btn--ghost erp-btn--sm" type="submit">Give back</button>
							</form>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( ! $erp_by_teacher ) : ?>
	<p class="erp-card erp-empty">No teacher of your class has offered meeting times yet.</p>
<?php endif; ?>

<?php foreach ( $erp_by_teacher as $erp_teacher_id => $erp_list ) : ?>
	<section class="erp-card" aria-labelledby="erp-ptm-t-<?php echo esc_attr( $erp_teacher_id ); ?>">
		<h2 id="erp-ptm-t-<?php echo esc_attr( $erp_teacher_id ); ?>"><?php echo esc_html( mags_erp_teacher_name( (int) $erp_teacher_id ) ); ?></h2>
		<?php $erp_day = ''; foreach ( $erp_list as $erp_slot ) : if ( $erp_slot->cancelled ) { continue; } ?>
			<?php if ( $erp_slot->slot_date !== $erp_day ) : if ( '' !== $erp_day ) { echo '</div>'; } $erp_day = $erp_slot->slot_date; ?>
				<h3 class="erp-subtitle"><?php echo esc_html( mags_erp_format_date( $erp_day, 'l, j F' ) ); ?><?php echo '' !== $erp_slot->place ? ' · ' . esc_html( $erp_slot->place ) : ''; ?></h3>
				<div class="erp-slots">
			<?php endif; ?>
			<?php if ( $erp_slot->mine ) : ?>
				<span class="erp-slot erp-slot--mine"><?php echo esc_html( mags_erp_format_time( $erp_slot->start_time ) ); ?> <small>yours</small></span>
			<?php elseif ( (int) $erp_slot->student_id ) : ?>
				<span class="erp-slot erp-slot--taken" aria-label="<?php echo esc_attr( mags_erp_format_time( $erp_slot->start_time ) . ' taken' ); ?>"><?php echo esc_html( mags_erp_format_time( $erp_slot->start_time ) ); ?> <small>taken</small></span>
			<?php else : ?>
				<form class="erp-slot-form" method="post" action="<?php echo esc_url( mags_erp_url( 'ptm' ) ); ?>">
					<?php mags_erp_form_fields( 'book_ptm' ); ?>
					<input type="hidden" name="slot_id" value="<?php echo esc_attr( $erp_slot->id ); ?>">
					<button class="erp-slot" type="submit"><?php echo esc_html( mags_erp_format_time( $erp_slot->start_time ) ); ?><span class="erp-sr"> book with <?php echo esc_html( mags_erp_teacher_name( (int) $erp_slot->teacher_id ) ); ?></span></button>
				</form>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if ( '' !== $erp_day ) { echo '</div>'; } ?>
	</section>
<?php endforeach; ?>
<p class="erp-hint erp-hint--lead">Press a free time to book it. Each time is for one parent; if another parent was faster, you are told and can pick another. You can give a slot back until the meeting starts.</p>
