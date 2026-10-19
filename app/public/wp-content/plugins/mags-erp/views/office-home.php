<?php
/** Office start screen: what exists and what to do next. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_classes  = mags_erp_get_classes();
$erp_teachers = mags_erp_get_teachers();
$erp_students = mags_erp_count_students();
$erp_tiles    = array(
	array( 'classes', 'Classes', count( $erp_classes ), 'blue' ),
	array( 'teachers', 'Teachers', count( $erp_teachers ), 'green' ),
	array( 'students', 'Students', $erp_students, 'orange' ),
);
$erp_steps    = array(
	array( 'classes', 'Add classes', 'Create each class and section, such as Class 5 – A.', (bool) $erp_classes ),
	array( 'teachers', 'Add teachers', 'Create a login for each teacher and tick the classes they mark.', (bool) $erp_teachers ),
	array( 'students', 'Add students', 'Add each student with admission number, class and roll number.', $erp_students > 0 ),
);
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Hello, <?php echo esc_html( $ctx['user']->display_name ); ?></h1>
</header>

<section class="erp-tiles" aria-label="School records">
	<?php foreach ( $erp_tiles as $erp_tile ) : ?>
		<a class="erp-tile erp-tile--<?php echo esc_attr( $erp_tile[3] ); ?>" href="<?php echo esc_url( mags_erp_url( $erp_tile[0] ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $erp_tile[2] ) ); ?></strong><span><?php echo esc_html( $erp_tile[1] ); ?></span></a>
	<?php endforeach; ?>
</section>

<section class="erp-card" aria-labelledby="erp-steps-title">
	<h2 id="erp-steps-title">Set up in three steps</h2>
	<ol class="erp-steps">
		<?php foreach ( $erp_steps as $erp_step ) : ?>
			<li class="<?php echo $erp_step[3] ? 'is-done' : ''; ?>">
				<div><strong><?php echo esc_html( $erp_step[1] ); ?></strong><?php if ( $erp_step[3] ) : ?> <span class="erp-chip erp-chip--green">Started</span><?php endif; ?><p><?php echo esc_html( $erp_step[2] ); ?></p></div>
				<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( $erp_step[0] ) ); ?>">Open<span class="erp-sr"> <?php echo esc_html( $erp_step[1] ); ?></span></a>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="erp-hint">When a student or teacher is added, the portal shows the login ID and password once. Note it down or copy it before leaving the page.</p>
</section>
