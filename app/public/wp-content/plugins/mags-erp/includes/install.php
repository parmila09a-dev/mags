<?php
/** Database tables, roles and activation for the MAGS ERP portal. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mags_erp_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'mags_erp_' . $name;
}

/**
 * Portal roles. They carry only their own portal capability, so these accounts
 * cannot edit the website or open the WordPress Dashboard.
 */
function mags_erp_register_roles() {
	add_role( 'mags_student', 'MAGS Student', array( 'mags_erp_student' => true ) );
	add_role( 'mags_teacher', 'MAGS Teacher', array( 'mags_erp_teacher' => true ) );
	add_role( 'mags_office', 'MAGS Office', array( 'mags_erp_office' => true ) );
}

function mags_erp_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'classes' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(60) NOT NULL,
			section varchar(20) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY name_section (name,section)
		) {$charset};"
	);
	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'class_teachers' ) . " (
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (class_id,teacher_id),
			KEY teacher_id (teacher_id)
		) {$charset};"
	);
	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'students' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			admission_no varchar(30) NOT NULL,
			full_name varchar(120) NOT NULL,
			class_id bigint(20) unsigned NOT NULL DEFAULT 0,
			roll_no varchar(10) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id),
			UNIQUE KEY admission_no (admission_no),
			KEY class_id (class_id)
		) {$charset};"
	);
	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'attendance' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			class_id bigint(20) unsigned NOT NULL,
			att_date date NOT NULL,
			status char(1) NOT NULL,
			marked_by bigint(20) unsigned NOT NULL,
			marked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY student_date (student_id,att_date),
			KEY class_date (class_id,att_date)
		) {$charset};"
	);

	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'files' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			stored_name varchar(80) NOT NULL,
			original_name varchar(200) NOT NULL,
			mime varchar(120) NOT NULL,
			size bigint(20) unsigned NOT NULL DEFAULT 0,
			uploaded_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY stored_name (stored_name)
		) {$charset};"
	);
	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'homework' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			title varchar(160) NOT NULL,
			instructions text NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			due_date date NULL,
			max_marks decimal(6,2) NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY class_id (class_id),
			KEY teacher_id (teacher_id)
		) {$charset};"
	);
	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'submissions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			homework_id bigint(20) unsigned NOT NULL,
			student_id bigint(20) unsigned NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			student_note text NOT NULL,
			submitted_at datetime NOT NULL,
			marks decimal(6,2) NULL,
			feedback text NOT NULL,
			checked_by bigint(20) unsigned NOT NULL DEFAULT 0,
			checked_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY homework_student (homework_id,student_id),
			KEY student_id (student_id)
		) {$charset};"
	);
	dbDelta(
		'CREATE TABLE ' . mags_erp_table( 'notes' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			title varchar(160) NOT NULL,
			description text NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY class_id (class_id)
		) {$charset};"
	);

	mags_erp_register_roles();
	mags_erp_private_dir();
	update_option( 'mags_erp_version', MAGS_ERP_VERSION );
}

function mags_erp_activate() {
	mags_erp_install();
	mags_erp_add_rewrite_rules();
	flush_rewrite_rules();
}

/** Portal data and accounts are kept on deactivation; only the /portal/ address is released. */
function mags_erp_deactivate() {
	delete_option( 'rewrite_rules' );
}

/** Create or update the tables when the plugin files are updated without re-activation. */
function mags_erp_maybe_upgrade() {
	if ( get_option( 'mags_erp_version' ) === MAGS_ERP_VERSION ) { return; }
	mags_erp_install();
	flush_rewrite_rules();
}
add_action( 'init', 'mags_erp_maybe_upgrade', 20 );
