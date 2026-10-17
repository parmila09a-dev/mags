<?php
/** Infrastructure page using only school-supplied facility information. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
while ( have_posts() ) :
	the_post();
	$building_image = get_theme_file_uri( '/assets/images/mags-school-building.jpg' );
	$lab_image      = get_theme_file_uri( '/assets/images/gallery-computer-lab.jpg' );
	$facility_names = array( 'Building', 'Library', 'Computer Lab', 'Mathematics Lab', 'Biotechnology Lab', 'Chemistry Lab', 'Sports Facilities', 'Music, Dance, Drama & Cultural Activities' );
	?>
	<header class="page-header">
		<div class="wrap">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'mags-school' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mags-school' ); ?></a></li>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>

	<section class="content-area infrastructure-page">
		<div class="wrap">
			<section class="infrastructure-intro">
				<div>
					<p class="eyebrow">Lakhi Ram Park campus</p>
					<h2 class="display-heading">Spaces for learning and participation.</h2>
					<p>Maharaja Agrasen Global School operates from 1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086. The facility areas below follow the infrastructure list supplied by the school.</p>
				</div>
				<img src="<?php echo esc_url( $building_image ); ?>" alt="Maharaja Agrasen Global School building at Lakhi Ram Park" decoding="async">
			</section>

			<section class="infrastructure-feature">
				<img src="<?php echo esc_url( $lab_image ); ?>" alt="Students working in the Maharaja Agrasen Global School computer laboratory" loading="lazy" decoding="async">
				<div>
					<p class="eyebrow">Computer laboratory</p>
					<h2 class="display-heading">Learning with technology.</h2>
					<p>School-supplied photographs show students using individual computer workstations with guidance from their teacher.</p>
				</div>
			</section>

			<section aria-labelledby="facility-areas-heading">
				<div class="section-heading">
					<div><p class="eyebrow">Infrastructure</p><h2 id="facility-areas-heading">Facility areas listed by the school.</h2></div>
				</div>
				<div class="facility-grid facility-grid--page">
					<?php foreach ( $facility_names as $index => $facility ) : ?>
						<article class="facility-card"><span><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><h3><?php echo esc_html( $facility ); ?></h3></article>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
	</section>
	<?php
endwhile;
get_footer();
