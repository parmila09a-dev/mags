<?php
/** Production homepage controller. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/front-page', 'modern' );
get_footer();
