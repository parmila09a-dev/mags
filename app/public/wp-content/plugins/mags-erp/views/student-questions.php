<?php
/** Student (and parent): ask a teacher of the class a question and read the replies. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}

$erp_question = mags_erp_get_question( mags_erp_get_int( 'id' ) );
if ( $erp_question && (int) $erp_question->student_id !== (int) $erp_student->id ) { $erp_question = null; }

if ( $erp_question ) {
	$erp_asker    = $erp_student;
	$erp_viewer   = 'student';
	$erp_no_reply = mags_erp_teacher_has_class( (int) $erp_question->teacher_id, (int) $erp_question->class_id ) ? '' : 'This teacher no longer teaches that class, so they cannot reply here. Please ask your question to another teacher.';
	require MAGS_ERP_PATH . 'views/part-thread.php';
	return;
}

$erp_teachers  = mags_erp_class_teachers( (int) $erp_student->class_id );
$erp_questions = mags_erp_student_questions( (int) $erp_student->id );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( mags_erp_get_class( (int) $erp_student->class_id ) ) ); ?></p>
	<h1>My questions</h1>
</header>

<section class="erp-card" aria-labelledby="erp-ask-title">
	<h2 id="erp-ask-title">Ask a teacher</h2>
	<?php if ( ! $erp_teachers ) : ?>
		<p class="erp-hint">Your class has no teacher in the portal yet. Please ask the school office.</p>
	<?php else : ?>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'questions' ) ); ?>">
			<?php mags_erp_form_fields( 'ask_question' ); ?>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_q_teacher">Teacher</label><select id="erp_q_teacher" name="teacher_id" required><?php if ( count( $erp_teachers ) > 1 ) : ?><option value="">Choose teacher</option><?php endif; ?><?php foreach ( $erp_teachers as $erp_teacher_id => $erp_teacher_name ) : ?><option value="<?php echo esc_attr( $erp_teacher_id ); ?>"><?php echo esc_html( $erp_teacher_name ); ?></option><?php endforeach; ?></select></p>
				<p class="erp-field"><label for="erp_q_title">Topic</label><input id="erp_q_title" name="title" type="text" maxlength="160" placeholder="Maths: fractions, exercise 4.2" required></p>
			</div>
			<p class="erp-field"><label for="erp_q_body">Your question</label><textarea id="erp_q_body" name="body" rows="4" maxlength="3000" required></textarea></p>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Send question</button></div>
		</form>
		<p class="erp-hint">Only that teacher and the school office can read your question. A question cannot be changed or deleted after sending.</p>
	<?php endif; ?>
</section>

<section aria-labelledby="erp-questions-title">
	<h2 id="erp-questions-title" class="erp-section-title">Asked <span class="erp-count"><?php echo esc_html( count( $erp_questions ) ); ?></span></h2>
	<?php if ( ! $erp_questions ) : ?>
		<p class="erp-card erp-empty">You have not asked a question yet.</p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_questions as $erp_item ) : $erp_state = mags_erp_question_state( $erp_item ); ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_item->title ); ?></strong>
						<span>To <?php echo esc_html( mags_erp_teacher_name( (int) $erp_item->teacher_id ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_item->last_at ) ); ?></span>
						<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span></span>
					</div>
					<div class="erp-row__actions"><a class="erp-btn <?php echo 'teacher' === $erp_item->last_from ? 'erp-btn--navy' : 'erp-btn--ghost'; ?> erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'questions', array( 'id' => (int) $erp_item->id ) ) ); ?>"><?php echo 'teacher' === $erp_item->last_from ? 'Read reply' : 'Open'; ?><span class="erp-sr"> <?php echo esc_html( $erp_item->title ); ?></span></a></div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
