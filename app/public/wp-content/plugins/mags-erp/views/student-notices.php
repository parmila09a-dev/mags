<?php
/** Student (and parent): notices the teachers have sent to the student's own class. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}
$erp_notices = mags_erp_classes_notices( array( (int) $erp_student->class_id ), 200, false, $erp_student );
$erp_today   = mags_erp_today();
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_class_label( mags_erp_get_class( (int) $erp_student->class_id ) ) ); ?></p>
	<h1>Class notices</h1>
</header>

<?php if ( ! $erp_notices ) : ?>
	<p class="erp-card erp-empty">No notices have been sent to your class yet.</p>
<?php else : ?>
	<ul class="erp-list">
		<?php foreach ( $erp_notices as $erp_notice ) : ?>
			<li class="erp-card erp-work">
				<div class="erp-work__head">
					<div>
						<h2><?php echo esc_html( $erp_notice->title ); ?></h2>
						<p><?php echo esc_html( mags_erp_teacher_name( $erp_notice->teacher_id ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_notice->created_at ) ); ?></p>
					</div>
					<?php if ( substr( $erp_notice->created_at, 0, 10 ) === $erp_today ) : ?><span class="erp-chip erp-chip--orange">Today</span><?php endif; ?>
				</div>
				<p class="erp-work__text"><?php echo nl2br( esc_html( $erp_notice->body ) ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
