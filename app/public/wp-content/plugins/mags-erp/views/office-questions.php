<?php
/** Office: read every question and reply. Nothing here can be changed or deleted. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_question = mags_erp_get_question( mags_erp_get_int( 'id' ) );
if ( $erp_question ) {
	$erp_asker    = mags_erp_get_student( (int) $erp_question->student_id );
	$erp_viewer   = 'office';
	$erp_no_reply = 'The office reads questions but does not reply. The teacher replies from their own login.';
	require MAGS_ERP_PATH . 'views/part-thread.php';
	return;
}

$erp_classes  = mags_erp_get_classes();
$erp_labels   = mags_erp_all_class_labels();
$erp_class_id = mags_erp_get_int( 'class' );
$erp_search   = mags_erp_get_text( 'q' );
$erp_page     = max( 1, mags_erp_get_int( 'pg' ) );
$erp_per_page = 50;
list( $erp_questions, $erp_total ) = mags_erp_query_questions( $erp_class_id, $erp_search, $erp_page, $erp_per_page );
$erp_pages     = (int) ceil( $erp_total / $erp_per_page );
$erp_page_args = array( 'class' => $erp_class_id ?: '', 'q' => $erp_search );
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Questions</h1>
</header>

<section aria-labelledby="erp-questions-title">
	<h2 id="erp-questions-title" class="erp-section-title">All questions <span class="erp-count"><?php echo esc_html( number_format_i18n( $erp_total ) ); ?></span></h2>
	<p class="erp-hint erp-hint--lead">Students ask their teachers here and the teachers reply. The office can read everything. Questions and replies cannot be changed or deleted by anyone.</p>
	<form class="erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'questions' ) ); ?>">
		<?php mags_erp_route_field( 'questions' ); ?>
		<p class="erp-field"><label for="erp_filter_class">Class</label><select id="erp_filter_class" name="class"><?php mags_erp_class_options( $erp_classes, $erp_class_id, 'All classes' ); ?></select></p>
		<p class="erp-field"><label for="erp_filter_q">Student, topic or words</label><input id="erp_filter_q" name="q" type="search" maxlength="60" value="<?php echo esc_attr( $erp_search ); ?>"></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>

	<?php if ( ! $erp_questions ) : ?>
		<p class="erp-card erp-empty"><?php echo esc_html( $erp_class_id || '' !== $erp_search ? 'No questions match this filter.' : 'No student has asked a question yet.' ); ?></p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_questions as $erp_item ) : $erp_state = mags_erp_question_state( $erp_item ); $erp_reachable = mags_erp_teacher_has_class( (int) $erp_item->teacher_id, (int) $erp_item->class_id ); ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_item->title ); ?></strong>
						<span><?php echo esc_html( $erp_item->full_name ); ?> (<?php echo esc_html( $erp_item->admission_no ); ?>) · <?php echo esc_html( $erp_labels[ (int) $erp_item->class_id ] ?? '' ); ?> · to <?php echo esc_html( mags_erp_teacher_name( (int) $erp_item->teacher_id ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_item->last_at ) ); ?></span>
						<span class="erp-row__chips"><span class="erp-chip <?php echo esc_attr( $erp_state[1] ); ?>"><?php echo esc_html( $erp_state[0] ); ?></span><?php if ( ! $erp_reachable && 'student' === $erp_item->last_from ) : ?><span class="erp-chip erp-chip--status-A">Teacher no longer has this class</span><?php endif; ?><?php if ( $erp_item->student_removed ) : ?><span class="erp-chip">Student removed</span><?php endif; ?></span>
					</div>
					<div class="erp-row__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'questions', array( 'id' => (int) $erp_item->id ) ) ); ?>">Read<span class="erp-sr"> <?php echo esc_html( $erp_item->title ); ?></span></a></div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $erp_pages > 1 ) : ?>
			<nav class="erp-pager" aria-label="Question list pages">
				<?php if ( $erp_page > 1 ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'questions', array_merge( $erp_page_args, array( 'pg' => $erp_page - 1 ) ) ) ); ?>">← Previous</a><?php endif; ?>
				<span>Page <?php echo esc_html( $erp_page ); ?> of <?php echo esc_html( $erp_pages ); ?></span>
				<?php if ( $erp_page < $erp_pages ) : ?><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'questions', array_merge( $erp_page_args, array( 'pg' => $erp_page + 1 ) ) ) ); ?>">Next →</a><?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</section>
