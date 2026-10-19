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

/**
 * Table definitions. Records are never deleted by the portal: a removed class,
 * student, homework, fee and so on keeps its row and gets a hidden_at date.
 */
function mags_erp_schema() {
	$hidden = "hidden_at datetime NULL,
			hidden_by bigint(20) unsigned NOT NULL DEFAULT 0,";
	return array(
		'classes'          => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(60) NOT NULL,
			section varchar(20) NOT NULL DEFAULT '',
			class_teacher_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			UNIQUE KEY name_section (name,section)",
		'class_teachers'   => 'class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (class_id,teacher_id),
			KEY teacher_id (teacher_id)',
		'students'         => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			admission_no varchar(30) NOT NULL,
			full_name varchar(120) NOT NULL,
			class_id bigint(20) unsigned NOT NULL DEFAULT 0,
			roll_no varchar(10) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id),
			UNIQUE KEY admission_no (admission_no),
			KEY class_id (class_id)",
		'attendance'       => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			class_id bigint(20) unsigned NOT NULL,
			att_date date NOT NULL,
			status char(1) NOT NULL,
			marked_by bigint(20) unsigned NOT NULL,
			marked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY student_date (student_id,att_date),
			KEY class_date (class_id,att_date)',
		'files'            => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			stored_name varchar(80) NOT NULL,
			original_name varchar(200) NOT NULL,
			mime varchar(120) NOT NULL,
			size bigint(20) unsigned NOT NULL DEFAULT 0,
			uploaded_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			UNIQUE KEY stored_name (stored_name)",
		'homework'         => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			title varchar(160) NOT NULL,
			instructions text NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			due_date date NULL,
			max_marks decimal(6,2) NULL,
			created_at datetime NOT NULL,
			updated_at datetime NULL,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			{$hidden}
			PRIMARY KEY  (id),
			KEY class_id (class_id),
			KEY teacher_id (teacher_id)",
		'homework_files'   => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			homework_id bigint(20) unsigned NOT NULL,
			file_id bigint(20) unsigned NOT NULL,
			added_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY homework_id (homework_id),
			KEY file_id (file_id)",
		'submissions'      => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
			KEY student_id (student_id)',
		'notes'            => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			title varchar(160) NOT NULL,
			description text NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY class_id (class_id)",
		'subjects'         => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(80) NOT NULL,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			UNIQUE KEY name (name)",
		'periods'          => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			label varchar(40) NOT NULL,
			start_time char(5) NOT NULL,
			end_time char(5) NOT NULL,
			is_break tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY start_time (start_time)",
		'timetable'        => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			class_id bigint(20) unsigned NOT NULL,
			dow tinyint(1) unsigned NOT NULL,
			period_id bigint(20) unsigned NOT NULL,
			subject_id bigint(20) unsigned NOT NULL DEFAULT 0,
			teacher_id bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY class_slot (class_id,dow,period_id),
			KEY teacher_dow (teacher_id,dow)',
		'exams'            => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(120) NOT NULL,
			start_date date NULL,
			published_at datetime NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id)",
		'exam_papers'      => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			exam_id bigint(20) unsigned NOT NULL,
			class_id bigint(20) unsigned NOT NULL,
			subject_id bigint(20) unsigned NOT NULL,
			max_marks decimal(6,2) NOT NULL,
			exam_date date NULL,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			UNIQUE KEY exam_class_subject (exam_id,class_id,subject_id),
			KEY class_id (class_id)",
		'exam_marks'       => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			paper_id bigint(20) unsigned NOT NULL,
			student_id bigint(20) unsigned NOT NULL,
			marks decimal(6,2) NULL,
			absent tinyint(1) unsigned NOT NULL DEFAULT 0,
			entered_by bigint(20) unsigned NOT NULL DEFAULT 0,
			entered_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY paper_student (paper_id,student_id),
			KEY student_id (student_id)',
		'notices'          => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			title varchar(160) NOT NULL,
			body text NOT NULL,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY class_id (class_id)",
		'questions'        => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			class_id bigint(20) unsigned NOT NULL,
			teacher_id bigint(20) unsigned NOT NULL,
			title varchar(160) NOT NULL,
			body text NOT NULL,
			created_at datetime NOT NULL,
			last_at datetime NOT NULL,
			last_from varchar(10) NOT NULL DEFAULT 'student',
			PRIMARY KEY  (id),
			KEY student_id (student_id),
			KEY teacher_id (teacher_id),
			KEY class_id (class_id)",
		'question_replies' => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			from_role varchar(10) NOT NULL,
			body text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY question_id (question_id)',
		'leave_requests'   => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			class_id bigint(20) unsigned NOT NULL,
			from_date date NOT NULL,
			to_date date NOT NULL,
			reason text NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'pending',
			decided_by bigint(20) unsigned NOT NULL DEFAULT 0,
			decided_at datetime NULL,
			decision_note text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY student_id (student_id),
			KEY class_status (class_id,status)",
		'fees'             => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			title varchar(120) NOT NULL,
			amount decimal(10,2) NOT NULL,
			due_date date NULL,
			group_key varchar(20) NOT NULL DEFAULT '',
			kind varchar(10) NOT NULL DEFAULT 'fee',
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reason varchar(20) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY student_id (student_id),
			KEY group_key (group_key),
			KEY parent_id (parent_id)",
		'calendar'         => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			kind varchar(10) NOT NULL DEFAULT 'holiday',
			title varchar(120) NOT NULL,
			from_date date NOT NULL,
			to_date date NOT NULL,
			details text NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY kind_dates (kind,from_date,to_date),
			KEY from_date (from_date)",
		'student_profiles' => "student_id bigint(20) unsigned NOT NULL,
			gender varchar(10) NOT NULL DEFAULT '',
			dob date NULL,
			father_name varchar(120) NOT NULL DEFAULT '',
			mother_name varchar(120) NOT NULL DEFAULT '',
			guardian_phone varchar(20) NOT NULL DEFAULT '',
			emergency_name varchar(120) NOT NULL DEFAULT '',
			emergency_phone varchar(20) NOT NULL DEFAULT '',
			blood_group varchar(5) NOT NULL DEFAULT '',
			address varchar(500) NOT NULL DEFAULT '',
			admitted_on date NULL,
			photo_file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_at datetime NULL,
			PRIMARY KEY  (student_id)",
		'student_documents' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			title varchar(120) NOT NULL,
			file_id bigint(20) unsigned NOT NULL,
			added_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY student_id (student_id),
			KEY file_id (file_id)",
		'certificates'     => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			kind varchar(12) NOT NULL,
			session varchar(10) NOT NULL,
			seq int(10) unsigned NOT NULL,
			serial_no varchar(40) NOT NULL,
			issued_on date NOT NULL,
			data longtext NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			UNIQUE KEY kind_session_seq (kind,session,seq),
			KEY student_id (student_id)",
		'admissions'       => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_name varchar(120) NOT NULL,
			gender varchar(10) NOT NULL DEFAULT '',
			dob date NULL,
			applied_class varchar(60) NOT NULL DEFAULT '',
			father_name varchar(120) NOT NULL DEFAULT '',
			mother_name varchar(120) NOT NULL DEFAULT '',
			phone varchar(20) NOT NULL DEFAULT '',
			email varchar(120) NOT NULL DEFAULT '',
			address varchar(500) NOT NULL DEFAULT '',
			previous_school varchar(160) NOT NULL DEFAULT '',
			blood_group varchar(5) NOT NULL DEFAULT '',
			photo_file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			document_file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(10) NOT NULL DEFAULT 'new',
			decision_note varchar(500) NOT NULL DEFAULT '',
			decided_by bigint(20) unsigned NOT NULL DEFAULT 0,
			decided_at datetime NULL,
			student_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY status (status),
			KEY phone (phone)",
		'staff_attendance' => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			teacher_id bigint(20) unsigned NOT NULL,
			att_date date NOT NULL,
			status char(1) NOT NULL,
			marked_by bigint(20) unsigned NOT NULL,
			marked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY teacher_date (teacher_id,att_date),
			KEY att_date (att_date)',
		'staff_leave'      => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			teacher_id bigint(20) unsigned NOT NULL,
			from_date date NOT NULL,
			to_date date NOT NULL,
			reason text NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'pending',
			decided_by bigint(20) unsigned NOT NULL DEFAULT 0,
			decided_at datetime NULL,
			decision_note text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY teacher_id (teacher_id),
			KEY status (status)",
		'fee_payments'     => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			amount decimal(10,2) NOT NULL,
			paid_on date NOT NULL,
			mode varchar(20) NOT NULL DEFAULT 'cash',
			reference varchar(60) NOT NULL DEFAULT '',
			note varchar(200) NOT NULL DEFAULT '',
			recorded_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			{$hidden}
			PRIMARY KEY  (id),
			KEY student_id (student_id),
			KEY paid_on (paid_on)",
	);
}

function mags_erp_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	foreach ( mags_erp_schema() as $name => $columns ) {
		dbDelta( 'CREATE TABLE ' . mags_erp_table( $name ) . " (\n\t\t\t" . $columns . "\n\t\t) {$charset};" );
	}

	mags_erp_migrate();
	mags_erp_register_roles();
	mags_erp_private_dir();
	update_option( 'mags_erp_version', MAGS_ERP_VERSION );
}

/**
 * Carry data made by an older version into the current tables. Safe to run again.
 * 0.3.0: a homework can have several files, so the single file of an older
 * homework is listed in homework_files (the old column is left as it was).
 */
function mags_erp_migrate() {
	global $wpdb;
	$homework = mags_erp_table( 'homework' );
	$links    = mags_erp_table( 'homework_files' );
	$wpdb->query( "INSERT INTO {$links} (homework_id, file_id, added_by, created_at) SELECT h.id, h.file_id, h.teacher_id, h.created_at FROM {$homework} h WHERE h.file_id > 0 AND NOT EXISTS (SELECT 1 FROM {$links} l WHERE l.homework_id = h.id AND l.file_id = h.file_id)" );
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
