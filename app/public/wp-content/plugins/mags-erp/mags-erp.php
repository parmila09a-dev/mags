<?php
/**
 * Plugin Name: MAGS ERP Portal
 * Description: School portal at /portal/ with logins for students (parents use the student login), teachers and the office: attendance, homework, notes, timetable, exams, date sheets, admit cards and report cards with a marks graph, class notices, questions to teachers, leave requests, parent-teacher meeting booking, fees with receipts, emails to parents, student profiles, certificates and ID cards, the admission form, teacher attendance and substitutes, the school calendar, the office's day page, new-session promotion, backup and office reports.
 * Version: 0.7.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: MAGS Website Competition Team
 * License: GPL-2.0-or-later
 * Text Domain: mags-erp
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'MAGS_ERP_VERSION', '0.7.0' );
define( 'MAGS_ERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAGS_ERP_URL', plugin_dir_url( __FILE__ ) );

require_once MAGS_ERP_PATH . 'includes/install.php';
require_once MAGS_ERP_PATH . 'includes/helpers.php';
require_once MAGS_ERP_PATH . 'includes/settings.php';
require_once MAGS_ERP_PATH . 'includes/files.php';
require_once MAGS_ERP_PATH . 'includes/data.php';
require_once MAGS_ERP_PATH . 'includes/classwork.php';
require_once MAGS_ERP_PATH . 'includes/timetable.php';
require_once MAGS_ERP_PATH . 'includes/exams.php';
require_once MAGS_ERP_PATH . 'includes/pdf-font.php';
require_once MAGS_ERP_PATH . 'includes/report-card.php';
require_once MAGS_ERP_PATH . 'includes/messages.php';
require_once MAGS_ERP_PATH . 'includes/leave.php';
require_once MAGS_ERP_PATH . 'includes/fees.php';
require_once MAGS_ERP_PATH . 'includes/documents.php';
require_once MAGS_ERP_PATH . 'includes/profiles.php';
require_once MAGS_ERP_PATH . 'includes/certificates.php';
require_once MAGS_ERP_PATH . 'includes/receipts.php';
require_once MAGS_ERP_PATH . 'includes/id-card.php';
require_once MAGS_ERP_PATH . 'includes/admissions.php';
require_once MAGS_ERP_PATH . 'includes/staff.php';
require_once MAGS_ERP_PATH . 'includes/calendar.php';
require_once MAGS_ERP_PATH . 'includes/import.php';
require_once MAGS_ERP_PATH . 'includes/reports.php';
require_once MAGS_ERP_PATH . 'includes/today.php';
require_once MAGS_ERP_PATH . 'includes/notify.php';
require_once MAGS_ERP_PATH . 'includes/backup.php';
require_once MAGS_ERP_PATH . 'includes/promotion.php';
require_once MAGS_ERP_PATH . 'includes/admit-card.php';
require_once MAGS_ERP_PATH . 'includes/substitutes.php';
require_once MAGS_ERP_PATH . 'includes/ptm.php';
require_once MAGS_ERP_PATH . 'includes/progress.php';
require_once MAGS_ERP_PATH . 'includes/auth.php';
require_once MAGS_ERP_PATH . 'includes/actions.php';
require_once MAGS_ERP_PATH . 'includes/router.php';

register_activation_hook( __FILE__, 'mags_erp_activate' );
register_deactivation_hook( __FILE__, 'mags_erp_deactivate' );
