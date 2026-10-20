<?php
/**
 * How a student's marks change from exam to exam: a line graph of the
 * percentage in every published exam, and a small graph per subject with
 * "up", "down" or "about the same" since the exam before. Parents see it under
 * More → My marks; the office on the student's profile.
 *
 * The graphs are drawn on the server as SVG (no script, no outside service)
 * and the same numbers are given as a table for screen readers and printing.
 * A paper the student missed (AB) is left out of the subject graph instead of
 * being drawn as 0, so one absence does not look like a fall in marks.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** A change of fewer points than this counts as "about the same". */
define( 'MAGS_ERP_PROGRESS_SAME', 2 );

/**
 * The student's published exams, oldest first, and the percentage in each:
 * array( exams => list of array( name, percent ), subjects => name => list of
 * percent|null per exam (null: no paper, 'AB': absent) ).
 */
function mags_erp_student_progress( $student ) {
	$exams = mags_erp_student_exams( $student );
	usort(
		$exams,
		static function ( $a, $b ) {
			return strcmp( (string) ( $a->start_date ?: substr( (string) $a->published_at, 0, 10 ) ), (string) ( $b->start_date ?: substr( (string) $b->published_at, 0, 10 ) ) ) ?: (int) $a->id - (int) $b->id;
		}
	);
	$out = array( 'exams' => array(), 'subjects' => array() );
	foreach ( $exams as $index => $exam ) {
		$out['exams'][] = array( 'name' => $exam->name, 'percent' => $exam->result['percent'] );
		foreach ( $exam->result['rows'] as $row ) {
			if ( ! isset( $out['subjects'][ $row->subject_name ] ) ) { $out['subjects'][ $row->subject_name ] = array_fill( 0, count( $exams ), null ); }
			if ( ! empty( $row->absent ) ) {
				$out['subjects'][ $row->subject_name ][ $index ] = 'AB';
			} elseif ( null !== $row->marks && (float) $row->max_marks > 0 ) {
				$out['subjects'][ $row->subject_name ][ $index ] = round( (float) $row->marks * 100 / (float) $row->max_marks, 1 );
			}
		}
	}
	ksort( $out['subjects'], SORT_NATURAL | SORT_FLAG_CASE );
	return $out;
}

/** array( text, class ) about the last two numbers of a list: up, down or about the same. */
function mags_erp_progress_trend( $values ) {
	$numbers = array_values( array_filter( $values, 'is_float' ) );
	if ( count( $numbers ) < 2 ) { return array( count( $numbers ) ? 'First exam' : 'No marks yet', '' ); }
	$change = round( end( $numbers ) - prev( $numbers ), 1 );
	if ( $change >= MAGS_ERP_PROGRESS_SAME ) { return array( sprintf( '▲ Up %s points', $change ), 'up' ); }
	if ( $change <= -MAGS_ERP_PROGRESS_SAME ) { return array( sprintf( '▼ Down %s points', abs( $change ) ), 'down' ); }
	return array( '● About the same', 'same' );
}

/**
 * An SVG line graph of percentages. $labels and $values have one entry per
 * exam; a value that is not a number leaves a gap. $small draws the little
 * subject graph without axes.
 */
function mags_erp_progress_svg( $labels, $values, $title, $small = false ) {
	$width  = $small ? 220 : 640;
	$height = $small ? 72 : 250;
	$left   = $small ? 8 : 44;
	$right  = $width - ( $small ? 8 : 20 );
	$top    = $small ? 10 : 24;
	$bottom = $height - ( $small ? 10 : 44 );
	$count  = max( 1, count( $values ) );
	// The first and last exam stand a little in from the edges, so their names fit under them.
	$pad    = $small ? 6 : 58;
	$x      = static function ( $i ) use ( $left, $right, $count, $pad ) { return 1 === $count ? ( $left + $right ) / 2 : $left + $pad + ( $right - $left - 2 * $pad ) * $i / ( $count - 1 ); };
	$y      = static function ( $value ) use ( $top, $bottom ) { return $bottom - ( $bottom - $top ) * max( 0, min( 100, (float) $value ) ) / 100; };
	$id     = 'erp-chart-' . wp_unique_id();
	$spoken = array();
	foreach ( $values as $i => $value ) { $spoken[] = ( $labels[ $i ] ?? '' ) . ': ' . ( is_float( $value ) ? $value . '%' : ( 'AB' === $value ? 'absent' : 'no paper' ) ); }

	$svg = '<svg class="erp-chart' . ( $small ? ' erp-chart--small' : '' ) . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-labelledby="' . esc_attr( $id ) . '" preserveAspectRatio="xMidYMid meet"><title id="' . esc_attr( $id ) . '">' . esc_html( $title . '. ' . implode( '; ', $spoken ) ) . '</title>';
	if ( ! $small ) {
		foreach ( array( 0, 25, 50, 75, 100 ) as $line ) {
			$svg .= '<line class="erp-chart__grid" x1="' . $left . '" x2="' . $right . '" y1="' . round( $y( $line ), 1 ) . '" y2="' . round( $y( $line ), 1 ) . '"/><text class="erp-chart__axis" x="' . ( $left - 8 ) . '" y="' . round( $y( $line ) + 4, 1 ) . '" text-anchor="end">' . $line . '%</text>';
		}
		$room  = $count > 1 ? min( ( $right - $left - 2 * $pad ) / ( $count - 1 ), 2 * $pad + 16 ) : $right - $left;
		$chars = max( 6, (int) floor( $room / 7 ) );
		foreach ( $labels as $i => $label ) {
			$short = mb_strlen( $label ) > $chars ? rtrim( mb_substr( $label, 0, $chars - 1 ) ) . '…' : $label;
			$svg  .= '<text class="erp-chart__label" x="' . round( $x( $i ), 1 ) . '" y="' . ( $bottom + 22 ) . '" text-anchor="middle">' . esc_html( $short ) . '</text>';
		}
	}
	// The line, in pieces between gaps.
	$piece = array();
	$lines = array();
	foreach ( $values as $i => $value ) {
		if ( is_float( $value ) ) { $piece[] = round( $x( $i ), 1 ) . ',' . round( $y( $value ), 1 ); continue; }
		if ( $piece ) { $lines[] = $piece; }
		$piece = array();
	}
	if ( $piece ) { $lines[] = $piece; }
	foreach ( $lines as $points ) {
		if ( count( $points ) > 1 ) { $svg .= '<polyline class="erp-chart__line" points="' . implode( ' ', $points ) . '"/>'; }
	}
	foreach ( $values as $i => $value ) {
		if ( is_float( $value ) ) {
			$svg .= '<circle class="erp-chart__dot" cx="' . round( $x( $i ), 1 ) . '" cy="' . round( $y( $value ), 1 ) . '" r="' . ( $small ? 3.5 : 5 ) . '"/>';
			if ( ! $small ) { $svg .= '<text class="erp-chart__value" x="' . round( $x( $i ), 1 ) . '" y="' . round( $y( $value ) - 11, 1 ) . '" text-anchor="middle">' . esc_html( $value ) . '%</text>'; }
		} elseif ( 'AB' === $value && ! $small ) {
			$svg .= '<text class="erp-chart__absent" x="' . round( $x( $i ), 1 ) . '" y="' . round( $bottom - 6, 1 ) . '" text-anchor="middle">AB</text>';
		}
	}
	return $svg . '</svg>';
}

/** The whole "how the marks are going" card for one student. $heading is the card title. */
function mags_erp_print_progress( $student, $heading = 'How the marks are going' ) {
	$data = mags_erp_student_progress( $student );
	if ( ! $data['exams'] ) { return; }
	$labels  = wp_list_pluck( $data['exams'], 'name' );
	$overall = array_map( static function ( $exam ) { return null === $exam['percent'] ? null : (float) $exam['percent']; }, $data['exams'] );
	$trend   = mags_erp_progress_trend( $overall );
	?>
	<section class="erp-card erp-progress" aria-labelledby="erp-progress-title">
		<div class="erp-work__head">
			<h2 id="erp-progress-title"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( '' !== $trend[1] ) : ?><span class="erp-trend erp-trend--<?php echo esc_attr( $trend[1] ); ?>"><?php echo esc_html( $trend[0] ); ?></span><?php endif; ?>
		</div>
		<?php if ( count( $data['exams'] ) < 2 ) : ?>
			<p class="erp-hint erp-hint--lead">The graph shows how the marks change once the results of a second exam are out.</p>
		<?php endif; ?>
		<?php echo mags_erp_progress_svg( $labels, $overall, 'Total percentage in each exam' ); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaped parts. ?>
		<?php if ( $data['subjects'] ) : ?>
			<h3>Each subject</h3>
			<ul class="erp-subjects">
				<?php foreach ( $data['subjects'] as $erp_subject => $erp_values ) : $erp_trend = mags_erp_progress_trend( $erp_values ); $erp_last = array_values( array_filter( $erp_values, 'is_float' ) ); ?>
					<li class="erp-subject">
						<div class="erp-subject__head"><strong><?php echo esc_html( $erp_subject ); ?></strong><span><?php echo esc_html( $erp_last ? end( $erp_last ) . '%' : '–' ); ?></span></div>
						<?php echo mags_erp_progress_svg( $labels, $erp_values, $erp_subject, true ); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaped parts. ?>
						<span class="erp-trend erp-trend--<?php echo esc_attr( '' !== $erp_trend[1] ? $erp_trend[1] : 'none' ); ?>"><?php echo esc_html( $erp_trend[0] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<details class="erp-removed">
			<summary class="erp-btn erp-btn--ghost erp-btn--sm">See the numbers</summary>
			<div class="erp-table-wrap">
				<table class="erp-table erp-table--marks">
					<caption class="erp-sr">Percentage in each exam</caption>
					<thead><tr><th scope="col">Subject</th><?php foreach ( $labels as $erp_label ) : ?><th scope="col" class="erp-num"><?php echo esc_html( $erp_label ); ?></th><?php endforeach; ?></tr></thead>
					<tbody>
						<?php foreach ( $data['subjects'] as $erp_subject => $erp_values ) : ?><tr><th scope="row"><?php echo esc_html( $erp_subject ); ?></th><?php foreach ( $erp_values as $erp_value ) : ?><td class="erp-num"><?php echo esc_html( is_float( $erp_value ) ? $erp_value . '%' : ( 'AB' === $erp_value ? 'AB' : '–' ) ); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
					</tbody>
					<tfoot><tr><th scope="row">Total</th><?php foreach ( $overall as $erp_value ) : ?><td class="erp-num"><strong><?php echo esc_html( null === $erp_value ? '–' : $erp_value . '%' ); ?></strong></td><?php endforeach; ?></tr></tfoot>
				</table>
			</div>
		</details>
		<p class="erp-hint">Percent of the maximum marks in each exam whose results are published. AB (absent) is not drawn. “Up” or “down” compares the last two exams; a change under <?php echo esc_html( MAGS_ERP_PROGRESS_SAME ); ?> points is “about the same”.</p>
	</section>
	<?php
}
