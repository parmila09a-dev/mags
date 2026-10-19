<?php
/** Upcoming events (event date today or later) shown above the News & Events list. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$upcoming_events = mags_school_upcoming_events( 6 );
if ( ! $upcoming_events ) { return; }
?>
<section class="upcoming-events" aria-labelledby="upcoming-events-heading">
	<div class="section-heading"><div><p class="eyebrow">Mark your calendar</p><h2 id="upcoming-events-heading">Upcoming events.</h2></div></div>
	<ol class="upcoming-events-list">
		<?php foreach ( $upcoming_events as $upcoming_event ) : ?>
			<li><a href="<?php echo esc_url( get_permalink( $upcoming_event ) ); ?>"><time datetime="<?php echo esc_attr( get_post_meta( $upcoming_event->ID, '_mags_event_date', true ) ); ?>"><b><?php echo esc_html( mags_school_event_date( $upcoming_event->ID, 'j' ) ); ?></b><span><?php echo esc_html( mags_school_event_date( $upcoming_event->ID, 'M Y' ) ); ?></span></time><span><strong><?php echo esc_html( get_the_title( $upcoming_event ) ); ?></strong><small><?php echo esc_html( mags_school_event_date( $upcoming_event->ID, 'l, j F Y' ) ); ?></small></span></a></li>
		<?php endforeach; ?>
	</ol>
</section>
