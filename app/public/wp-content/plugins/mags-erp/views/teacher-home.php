<?php
/** Teacher start screen: own classes and whether today's attendance is marked. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_today   = mags_erp_today();
$erp_classes = mags_erp_teacher_classes( $ctx['user']->ID );
$erp_summary = mags_erp_day_summary( wp_list_pluck( $erp_classes, 'id' ), $erp_today );
?>
<header class="erp-head">
	<p class="erp-eyebrow"><?php echo esc_html( mags_erp_format_date( $erp_today, 'l, j F Y' ) ); ?></p>
	<h1>Hello, <?php echo esc_html( $ctx['user']->display_name ); ?></h1>
</header>

<section aria-labelledby="erp-my-classes-title">
	<h2 id="erp-my-classes-title" class="erp-section-title">My classes <span class="erp-count"><?php echo esc_html( count( $erp_classes ) ); ?></span></h2>
	<?php if ( ! $erp_classes ) : ?>
		<p class="erp-card erp-empty">No class has been given to you yet. Please ask the school office to add your classes.</p>
	<?php else : ?>
		<ul class="erp-cards">
			<?php foreach ( $erp_classes as $erp_class ) : ?>
				<?php
				$erp_marks  = $erp_summary[ (int) $erp_class->id ] ?? array();
				$erp_marked = array_sum( $erp_marks );
				$erp_total  = (int) $erp_class->student_count;
				?>
				<li class="erp-card erp-classcard">
					<div>
						<h3><?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></h3>
						<p><?php echo esc_html( sprintf( _n( '%s student', '%s students', $erp_total, 'mags-erp' ), number_format_i18n( $erp_total ) ) ); ?></p>
						<?php if ( ! $erp_total ) : ?>
							<span class="erp-chip">No students yet</span>
						<?php elseif ( ! $erp_marked ) : ?>
							<span class="erp-chip erp-chip--orange">Today not marked</span>
						<?php else : ?>
							<span class="erp-chip erp-chip--green">Today: <?php echo esc_html( sprintf( '%d present · %d absent · %d leave', $erp_marks['P'] ?? 0, $erp_marks['A'] ?? 0, $erp_marks['L'] ?? 0 ) ); ?></span>
							<?php if ( $erp_marked < $erp_total ) : ?><span class="erp-chip erp-chip--orange"><?php echo esc_html( $erp_total - $erp_marked ); ?> not marked</span><?php endif; ?>
						<?php endif; ?>
					</div>
					<?php if ( $erp_total ) : ?>
						<a class="erp-btn <?php echo $erp_marked ? 'erp-btn--ghost' : 'erp-btn--orange'; ?>" href="<?php echo esc_url( mags_erp_url( 'attendance', array( 'class' => (int) $erp_class->id ) ) ); ?>"><?php echo $erp_marked ? 'Edit today' : 'Mark attendance'; ?><span class="erp-sr"> for <?php echo esc_html( mags_erp_class_label( $erp_class ) ); ?></span></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
