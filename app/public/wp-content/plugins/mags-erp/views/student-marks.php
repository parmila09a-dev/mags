<?php
/** Student (and parent): the student's own marks in the exams whose results are published, a graph of how they change, and the report card. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}
$erp_exams = mags_erp_student_exams( $erp_student );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( mags_erp_get_class( (int) $erp_student->class_id ) ) ); ?></p>
	<h1>My marks</h1>
</header>

<?php if ( ! $erp_exams ) : ?>
	<p class="erp-card erp-empty">No exam results have been published yet.</p>
<?php endif; ?>

<?php mags_erp_print_progress( $erp_student ); ?>

<?php foreach ( $erp_exams as $erp_exam ) : $erp_result = $erp_exam->result; ?>
	<section class="erp-card" aria-labelledby="erp-exam-<?php echo esc_attr( $erp_exam->id ); ?>">
		<div class="erp-work__head">
			<div>
				<h2 id="erp-exam-<?php echo esc_attr( $erp_exam->id ); ?>"><?php echo esc_html( $erp_exam->name ); ?></h2>
				<p>Results published <?php echo esc_html( mags_erp_format_datetime( $erp_exam->published_at, 'j M Y' ) ); ?></p>
			</div>
			<?php if ( null !== $erp_result['percent'] ) : ?><span class="erp-chip erp-chip--green"><?php echo esc_html( $erp_result['percent'] ); ?>%</span><?php endif; ?>
		</div>
		<div class="erp-table-wrap">
			<table class="erp-table erp-table--marks">
				<caption class="erp-sr">Marks in <?php echo esc_html( $erp_exam->name ); ?></caption>
				<thead><tr><th scope="col">Subject</th><th scope="col" class="erp-num">Out of</th><th scope="col" class="erp-num">Marks</th></tr></thead>
				<tbody>
					<?php foreach ( $erp_result['rows'] as $erp_row ) : ?>
						<tr><th scope="row"><?php echo esc_html( $erp_row->subject_name ); ?></th><td class="erp-num"><?php echo esc_html( mags_erp_format_marks( $erp_row->max_marks ) ); ?></td><td class="erp-num"><strong><?php echo esc_html( mags_erp_marks_text( $erp_row ) ); ?></strong></td></tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot><tr><th scope="row">Total</th><td class="erp-num"><?php echo esc_html( mags_erp_format_marks( $erp_result['max'] ) ); ?></td><td class="erp-num"><strong><?php echo esc_html( mags_erp_format_marks( $erp_result['total'] ) ); ?></strong></td></tr></tfoot>
			</table>
		</div>
		<p class="erp-hint">AB means absent and counts as 0. A dash means the marks are not entered yet and are not counted in the total.</p>
		<p class="erp-card__actions"><a class="erp-btn erp-btn--navy" href="<?php echo esc_url( mags_erp_url( 'report-card', array( 'exam' => (int) $erp_exam->id ) ) ); ?>">Download report card</a></p>
	</section>
<?php endforeach; ?>
