<?php
/**
 * One question with its replies, shared by the student, teacher and office screens.
 * Expects: $erp_question, $erp_asker (the student row), $erp_viewer (student, teacher or office)
 * and $erp_no_reply ('' to show the reply form, otherwise the reason why there is none).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_state     = mags_erp_question_state( $erp_question );
$erp_teacher   = mags_erp_teacher_name( (int) $erp_question->teacher_id );
$erp_class_tag = mags_erp_all_class_labels()[ (int) $erp_question->class_id ] ?? '';
$erp_messages  = array_merge(
	array( (object) array( 'from_role' => 'student', 'body' => $erp_question->body, 'created_at' => $erp_question->created_at ) ),
	mags_erp_question_replies( (int) $erp_question->id )
);
?>
<header class="erp-head">
	<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'questions' ) ); ?>">← All questions</a></p>
	<h1><?php echo esc_html( $erp_question->title ); ?></h1>
	<p class="erp-head__meta">
		<?php if ( 'student' !== $erp_viewer ) : ?><span class="erp-chip erp-chip--blue"><?php echo esc_html( $erp_asker ? $erp_asker->full_name : 'Student' ); ?></span><span class="erp-chip"><?php echo esc_html( $erp_class_tag ); ?></span><?php endif; ?>
		<?php if ( 'teacher' !== $erp_viewer ) : ?><span class="erp-chip">To <?php echo esc_html( $erp_teacher ); ?></span><?php endif; ?>
		<span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span>
	</p>
</header>

<ol class="erp-thread" aria-label="Messages">
	<?php foreach ( $erp_messages as $erp_message ) : $erp_from_student = 'student' === $erp_message->from_role; ?>
		<li class="erp-msg erp-msg--<?php echo $erp_from_student ? 'student' : 'teacher'; ?><?php echo ( 'student' === $erp_viewer ) === $erp_from_student && 'office' !== $erp_viewer ? ' is-mine' : ''; ?>">
			<p class="erp-msg__who"><strong><?php echo esc_html( $erp_from_student ? ( $erp_asker ? $erp_asker->full_name : 'Student' ) : $erp_teacher ); ?></strong> <span><?php echo esc_html( mags_erp_format_datetime( $erp_message->created_at ) ); ?></span></p>
			<p class="erp-msg__text"><?php echo nl2br( esc_html( $erp_message->body ) ); ?></p>
		</li>
	<?php endforeach; ?>
</ol>

<?php if ( '' === $erp_no_reply ) : ?>
	<section class="erp-card" aria-labelledby="erp-reply-title">
		<h2 id="erp-reply-title"><?php echo 'teacher' === $erp_viewer ? 'Reply to the student' : 'Write again'; ?></h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'questions', array( 'id' => (int) $erp_question->id ) ) ); ?>">
			<?php mags_erp_form_fields( 'reply_question' ); ?>
			<input type="hidden" name="question_id" value="<?php echo esc_attr( $erp_question->id ); ?>">
			<p class="erp-field"><label for="erp_reply_body"><?php echo 'teacher' === $erp_viewer ? 'Your reply' : 'Your message'; ?></label><textarea id="erp_reply_body" name="body" rows="4" maxlength="3000" required></textarea></p>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit"><?php echo 'teacher' === $erp_viewer ? 'Send reply' : 'Send message'; ?></button></div>
		</form>
	</section>
<?php else : ?>
	<p class="erp-card erp-empty"><?php echo esc_html( $erp_no_reply ); ?></p>
<?php endif; ?>
<p class="erp-hint erp-hint--lead">Questions and replies cannot be changed or deleted. The school office can read them.</p>
