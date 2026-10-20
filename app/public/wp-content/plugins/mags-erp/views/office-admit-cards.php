<?php
/** Office: the admit cards of one class in one exam: who may have one, holds and exceptions, and the PDFs. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_exam  = mags_erp_get_exam( mags_erp_get_int( 'exam' ) );
$erp_class = mags_erp_get_class( mags_erp_get_int( 'class' ) );
if ( ! $erp_exam || ! $erp_class ) {
	echo '<section class="erp-card erp-empty"><h1>Choose an exam and a class</h1><p><a href="' . esc_url( mags_erp_url( 'exams' ) ) . '">Open the exams</a> and choose “Admit cards” next to a class.</p></section>';
	return;
}
$erp_papers    = mags_erp_datesheet( (int) $erp_exam->id, (int) $erp_class->id );
$erp_students  = mags_erp_class_students( (int) $erp_class->id );
$erp_overrides = mags_erp_admit_overrides( (int) $erp_exam->id );
$erp_dues      = mags_erp_fee_dues( wp_list_pluck( $erp_students, 'id' ) );
$erp_chips     = array( 'ready' => array( 'Can download', 'erp-chip--green' ), 'allowed' => array( 'Allowed by the office', 'erp-chip--blue' ), 'fees' => array( 'Held: fees', 'erp-chip--status-A' ), 'held' => array( 'Held by the office', 'erp-chip--status-A' ) );
$erp_ready     = 0;
$erp_lines     = array();
foreach ( $erp_students as $erp_student ) {
	$erp_status  = mags_erp_admit_status( $erp_exam, $erp_student, $erp_overrides, $erp_dues );
	$erp_ready  += (int) $erp_status[0];
	$erp_lines[] = array( $erp_student, $erp_status );
}
$erp_back = mags_erp_url( 'admit-cards', array( 'exam' => (int) $erp_exam->id, 'class' => (int) $erp_class->id ) );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'exams', array( 'id' => (int) $erp_exam->id ) ) . '#erp-admit' ); ?>">← <?php echo esc_html( $erp_exam->name ); ?></a></p>
	<h1>Admit cards · <?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></h1>
	<p class="erp-head__meta"><span class="erp-chip"><?php echo esc_html( mags_erp_admit_rules()[ $erp_exam->admit_rule ?: 'none' ] ?? '' ); ?></span><?php if ( $erp_exam->datesheet_at ) : ?><span class="erp-chip erp-chip--green">Shared with students</span><?php else : ?><span class="erp-chip erp-chip--orange">Not shared with students yet</span><?php endif; ?></p>
</header>

<section class="erp-card" aria-labelledby="erp-ds-title">
	<h2 id="erp-ds-title">Date sheet</h2>
	<?php if ( ! $erp_papers ) : ?>
		<p class="erp-hint">This class has no papers in the exam.</p>
	<?php else : ?>
		<div class="erp-table-wrap">
			<table class="erp-table">
				<thead><tr><th scope="col">Date</th><th scope="col">Day</th><th scope="col">Time</th><th scope="col">Subject</th><th scope="col" class="erp-num">Out of</th></tr></thead>
				<tbody><?php foreach ( $erp_papers as $erp_paper ) : $erp_row = mags_erp_datesheet_row( $erp_paper ); ?><tr><td><?php echo esc_html( $erp_row[0] ); ?></td><td><?php echo esc_html( $erp_row[1] ); ?></td><td><?php echo esc_html( '' !== $erp_row[2] ? $erp_row[2] : '–' ); ?></td><th scope="row"><?php echo esc_html( $erp_row[3] ); ?></th><td class="erp-num"><?php echo esc_html( $erp_row[4] ); ?></td></tr><?php endforeach; ?></tbody>
			</table>
		</div>
		<p class="erp-card__actions">
			<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'date-sheet', array( 'exam' => (int) $erp_exam->id, 'class' => (int) $erp_class->id ) ) ); ?>">Date sheet (PDF)</a>
			<?php if ( $erp_ready ) : ?><a class="erp-btn erp-btn--navy erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'admit-card', array( 'exam' => (int) $erp_exam->id, 'class' => (int) $erp_class->id ) ) ); ?>">Admit cards of the class (PDF, <?php echo esc_html( $erp_ready ); ?>)</a><?php endif; ?>
		</p>
	<?php endif; ?>
</section>

<section aria-labelledby="erp-admit-list-title">
	<h2 id="erp-admit-list-title" class="erp-section-title">Students <span class="erp-count"><?php echo esc_html( count( $erp_lines ) ); ?></span></h2>
	<p class="erp-hint erp-hint--lead"><?php echo esc_html( sprintf( '%1$d can download the admit card, %2$d are held. The class PDF has only the cards that are not held; “Print” gives any one card.', $erp_ready, count( $erp_lines ) - $erp_ready ) ); ?></p>
	<?php if ( ! $erp_lines ) : ?>
		<p class="erp-card erp-empty">This class has no students.</p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_lines as $erp_line ) : list( $erp_student, $erp_status ) = $erp_line; $erp_chip = $erp_chips[ $erp_status[1] ]; $erp_own = $erp_overrides[ (int) $erp_student->id ] ?? ''; ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_student->full_name ); ?></strong>
						<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_chip[1] ); ?>"><?php echo esc_html( $erp_chip[0] ); ?></span><?php if ( isset( $erp_dues[ (int) $erp_student->id ] ) ) : ?><span class="erp-chip"><?php echo esc_html( mags_erp_money( $erp_dues[ (int) $erp_student->id ]['left'] ) ); ?> to pay<?php echo $erp_dues[ (int) $erp_student->id ]['overdue'] > 0 ? esc_html( ', ' . mags_erp_money( $erp_dues[ (int) $erp_student->id ]['overdue'] ) . ' overdue' ) : ''; ?></span><?php endif; ?></span>
					</div>
					<div class="erp-row__actions">
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'admit-card', array( 'exam' => (int) $erp_exam->id, 'student' => (int) $erp_student->id ) ) ); ?>">Print<span class="erp-sr"> the admit card of <?php echo esc_html( $erp_student->full_name ); ?></span></a>
						<?php foreach ( array( 'hold' => 'Hold', 'allow' => 'Allow anyway', '' => 'Follow the rule' ) as $erp_state => $erp_label ) : if ( $erp_state === $erp_own || ( 'allow' === $erp_state && 'ready' === $erp_status[1] ) ) { continue; } ?>
							<form method="post" action="<?php echo esc_url( $erp_back ); ?>">
								<?php mags_erp_form_fields( 'set_admit' ); ?>
								<input type="hidden" name="exam_id" value="<?php echo esc_attr( $erp_exam->id ); ?>"><input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>"><input type="hidden" name="state" value="<?php echo esc_attr( $erp_state ); ?>">
								<button class="erp-btn <?php echo 'hold' === $erp_state ? 'erp-btn--danger' : 'erp-btn--ghost'; ?> erp-btn--sm" type="submit"><?php echo esc_html( $erp_label ); ?><span class="erp-sr"> · <?php echo esc_html( $erp_student->full_name ); ?></span></button>
							</form>
						<?php endforeach; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
