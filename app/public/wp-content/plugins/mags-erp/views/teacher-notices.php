<?php
/** Teacher: send a notice to one of their own classes; remove and restore notices. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes   = mags_erp_teacher_classes( $ctx['user']->ID );
$erp_class_map = array();
foreach ( $erp_classes as $erp_class ) { $erp_class_map[ (int) $erp_class->id ] = $erp_class; }
$erp_notices = mags_erp_classes_notices( array_keys( $erp_class_map ) );
$erp_gone    = array();
foreach ( mags_erp_classes_notices( array_keys( $erp_class_map ), 100, true ) as $erp_notice ) { $erp_gone[] = array( (int) $erp_notice->id, $erp_notice->title, mags_erp_class_label( $erp_class_map[ (int) $erp_notice->class_id ] ) . ' · removed ' . mags_erp_format_datetime( $erp_notice->hidden_at, 'j M Y' ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">Teacher</p>
	<h1>Class notices</h1>
</header>

<?php if ( ! $erp_classes ) : ?>
	<p class="erp-card erp-empty">No class has been given to you yet. Please ask the school office to add your classes.</p>
<?php else : ?>
	<section class="erp-card" aria-labelledby="erp-notice-form-title">
		<h2 id="erp-notice-form-title">Send a notice</h2>
		<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'notices' ) ); ?>">
			<?php mags_erp_form_fields( 'save_notice' ); ?>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_notice_class">Class</label><select id="erp_notice_class" name="class_id" required><?php mags_erp_class_options( $erp_classes, 1 === count( $erp_classes ) ? (int) $erp_classes[0]->id : 0, count( $erp_classes ) > 1 ? 'Choose class' : '' ); ?></select></p>
				<p class="erp-field"><label for="erp_notice_title">Title</label><input id="erp_notice_title" name="title" type="text" maxlength="160" placeholder="Bring your art file on Friday" required></p>
			</div>
			<p class="erp-field"><label for="erp_notice_body">Notice</label><textarea id="erp_notice_body" name="body" rows="4" maxlength="3000" required></textarea></p>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Send notice</button></div>
		</form>
		<p class="erp-hint">Every student of the class (and their parents) sees the notice in the portal.</p>
	</section>

	<section aria-labelledby="erp-notice-list-title">
		<h2 id="erp-notice-list-title" class="erp-section-title">Sent notices <span class="erp-count"><?php echo esc_html( count( $erp_notices ) ); ?></span></h2>
		<?php if ( ! $erp_notices ) : ?>
			<p class="erp-card erp-empty">No notices sent yet.</p>
		<?php else : ?>
			<ul class="erp-list">
				<?php foreach ( $erp_notices as $erp_notice ) : ?>
					<li class="erp-card erp-work">
						<div class="erp-work__head">
							<div>
								<h3><?php echo esc_html( $erp_notice->title ); ?></h3>
								<p><?php echo esc_html( mags_erp_class_label( $erp_class_map[ (int) $erp_notice->class_id ] ) ); ?> · <?php echo esc_html( mags_erp_teacher_name( $erp_notice->teacher_id ) ); ?> · <?php echo esc_html( mags_erp_format_datetime( $erp_notice->created_at ) ); ?></p>
							</div>
						</div>
						<p class="erp-work__text"><?php echo nl2br( esc_html( $erp_notice->body ) ); ?></p>
						<div class="erp-work__foot">
							<form method="post" action="<?php echo esc_url( mags_erp_url( 'notices' ) ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove “' . $erp_notice->title . '”? The class will no longer see it. It is kept and you can restore it.' ); ?>">
								<?php mags_erp_form_fields( 'delete_notice' ); ?>
								<input type="hidden" name="notice_id" value="<?php echo esc_attr( $erp_notice->id ); ?>">
								<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove</button>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php mags_erp_removed_list( $erp_gone, 'delete_notice', 'notice_id', 'notices', 'Removed notices' ); ?>
	</section>
<?php endif; ?>
