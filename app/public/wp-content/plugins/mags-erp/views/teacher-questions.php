<?php
/** Teacher: questions that students of own classes asked this teacher, and the replies. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_teacher_id = $ctx['user']->ID;
$erp_question   = mags_erp_get_question( mags_erp_get_int( 'id' ) );
if ( ! mags_erp_teacher_can_open_question( $erp_teacher_id, $erp_question ) ) { $erp_question = null; }

if ( $erp_question ) {
	$erp_asker    = mags_erp_get_student( (int) $erp_question->student_id );
	$erp_viewer   = 'teacher';
	$erp_no_reply = $erp_asker && $erp_asker->hidden_at ? 'This student has left the school, so a reply cannot be sent.' : '';
	require MAGS_ERP_PATH . 'views/part-thread.php';
	return;
}

$erp_questions = mags_erp_teacher_questions( $erp_teacher_id );
$erp_labels    = mags_erp_all_class_labels();
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>Questions</h1>
</header>

<?php if ( ! $erp_questions ) : ?>
	<p class="erp-card erp-empty">No student has asked you a question yet. Students of your classes can ask from their portal.</p>
<?php else : ?>
	<ul class="erp-list">
		<?php foreach ( $erp_questions as $erp_item ) : $erp_state = mags_erp_question_state( $erp_item ); $erp_waits = 'student' === $erp_item->last_from && ! $erp_item->student_removed; ?>
			<li class="erp-row">
				<div class="erp-row__main">
					<strong><?php echo esc_html( $erp_item->title ); ?></strong>
					<span><?php if ( '' !== $erp_item->roll_no ) : ?><span class="erp-roll"><?php echo esc_html( $erp_item->roll_no ); ?></span><?php endif; ?><?php echo esc_html( $erp_item->full_name ); ?> · <?php echo esc_html( $erp_labels[ (int) $erp_item->class_id ] ?? '' ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_item->last_at ) ); ?></span>
					<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( 'student' === $erp_item->last_from ? 'Waiting for your reply' : 'Answered' ); ?></span><?php if ( $erp_item->student_removed ) : ?><span class="erp-chip">Student has left</span><?php endif; ?></span>
				</div>
				<div class="erp-row__actions"><a class="erp-btn <?php echo $erp_waits ? 'erp-btn--orange' : 'erp-btn--ghost'; ?> erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'questions', array( 'id' => (int) $erp_item->id ) ) ); ?>"><?php echo $erp_waits ? 'Reply' : 'Open'; ?><span class="erp-sr"> <?php echo esc_html( $erp_item->title ); ?></span></a></div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
