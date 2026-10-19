<?php
/**
 * Plugin Name: MAGS ERP Portal
 * Description: School portal at /portal/ with logins for students (parents use the student login), teachers and the office. The office manages classes, teachers and students; teachers mark attendance, give homework and share notes; students see their own attendance, submit homework and download notes.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: MAGS Website Competition Team
 * License: GPL-2.0-or-later
 * Text Domain: mags-erp
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'MAGS_ERP_VERSION', '0.2.0' );
define( 'MAGS_ERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAGS_ERP_URL', plugin_dir_url( __FILE__ ) );

require_once MAGS_ERP_PATH . 'includes/install.php';
require_once MAGS_ERP_PATH . 'includes/helpers.php';
require_once MAGS_ERP_PATH . 'includes/files.php';
require_once MAGS_ERP_PATH . 'includes/data.php';
require_once MAGS_ERP_PATH . 'includes/classwork.php';
require_once MAGS_ERP_PATH . 'includes/auth.php';
require_once MAGS_ERP_PATH . 'includes/actions.php';
require_once MAGS_ERP_PATH . 'includes/router.php';

register_activation_hook( __FILE__, 'mags_erp_activate' );
register_deactivation_hook( __FILE__, 'mags_erp_deactivate' );
