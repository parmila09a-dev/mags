<?php
/** Student (and parent): the date sheets of coming exams and the admit card, unless the office holds it. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}
$erp_exams = mags_erp_class_datesheet_exams( (int) $erp_student->class_id );
$erp_today = mags_erp_today();
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( mags_erp_get_class( (int) $erp_student->class_id ) ) ); ?></p>
	<h1>Exams</h1>
</header>

<?php if ( ! $erp_exams ) : ?>
	<p class="erp-card erp-empty">No date sheet has been shared yet. The school office shares it before the exams. Results of past exams are under More → My marks.</p>
<?php endif; ?>

<?php foreach ( $erp_exams as $erp_exam ) : list( $erp_ok, $erp_state, $erp_why ) = mags_erp_admit_status( $erp_exam, $erp_student ); ?>
	<section class="erp-card" aria-labelledby="erp-exam-<?php echo esc_attr( $erp_exam->id ); ?>">
		<h2 id="erp-exam-<?php echo esc_attr( $erp_exam->id ); ?>"><?php echo esc_html( $erp_exam->name ); ?></h2>
		<div class="erp-table-wrap">
			<table class="erp-table">
				<caption class="erp-sr">Date sheet of <?php echo esc_html( $erp_exam->name ); ?></caption>
				<thead><tr><th scope="col">Date</th><th scope="col">Time</th><th scope="col">Subject</th><th scope="col" class="erp-num">Out of</th></tr></thead>
				<tbody>
					<?php foreach ( $erp_exam->papers as $erp_paper ) : $erp_row = mags_erp_datesheet_row( $erp_paper ); ?>
						<tr<?php echo $erp_paper->exam_date && $erp_paper->exam_date < $erp_today ? ' class="erp-row--off"' : ''; ?>><td><?php echo esc_html( trim( $erp_row[1] . ', ' . $erp_row[0], ', ' ) ); ?><?php if ( $erp_paper->exam_date === $erp_today ) : ?> <span class="erp-chip erp-chip--green">Today</span><?php endif; ?></td><td><?php echo esc_html( '' !== $erp_row[2] ? $erp_row[2] : '–' ); ?></td><th scope="row"><?php echo esc_html( $erp_row[3] ); ?></th><td class="erp-num"><?php echo esc_html( $erp_row[4] ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="erp-card__actions">
			<?php if ( $erp_ok ) : ?><a class="erp-btn erp-btn--navy" href="<?php echo esc_url( mags_erp_url( 'admit-card', array( 'exam' => (int) $erp_exam->id ) ) ); ?>">Download admit card</a><?php endif; ?>
			<a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'date-sheet', array( 'exam' => (int) $erp_exam->id ) ) ); ?>">Date sheet (PDF)</a>
		</p>
		<?php if ( ! $erp_ok ) : ?><p class="erp-flash erp-flash--error"><?php echo esc_html( $erp_why ); ?></p><?php endif; ?>
		<?php if ( '' !== trim( (string) $erp_exam->admit_note ) ) : ?><p class="erp-hint"><?php echo esc_html( $erp_exam->admit_note ); ?></p><?php endif; ?>
	</section>
<?php endforeach; ?>
