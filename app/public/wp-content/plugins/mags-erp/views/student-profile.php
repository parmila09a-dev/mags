<?php
/** Student (and parent): the student's own details and photo as the school office has them. Read only. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_student = mags_erp_get_student_by_user( $ctx['user']->ID );
if ( ! $erp_student ) {
	mags_erp_no_student_record();
	return;
}
$erp_profile = mags_erp_get_profile( (int) $erp_student->id );
$erp_class   = mags_erp_get_class( (int) $erp_student->class_id );
$erp_genders = mags_erp_genders();
$erp_facts   = array(
	'Class'              => mags_erp_class_label( $erp_class ),
	'Roll number'        => $erp_student->roll_no,
	'Admission number'   => $erp_student->admission_no,
	'Date of birth'      => $erp_profile->dob ? mags_erp_format_date( $erp_profile->dob ) : '',
	'Boy or girl'        => $erp_genders[ $erp_profile->gender ] ?? '',
	'Blood group'        => $erp_profile->blood_group,
	'Father’s name'      => $erp_profile->father_name,
	'Mother’s name'      => $erp_profile->mother_name,
	'Parent’s phone'     => $erp_profile->guardian_phone,
	'Parent’s email'     => $erp_profile->parent_email,
	'Emergency number'   => trim( $erp_profile->emergency_phone . ( '' !== $erp_profile->emergency_phone && '' !== $erp_profile->emergency_name ? ' (' . $erp_profile->emergency_name . ')' : '' ) ),
	'Address'            => $erp_profile->address,
	'Date of admission'  => $erp_profile->admitted_on ? mags_erp_format_date( $erp_profile->admitted_on ) : '',
);
?>
<header class="erp-head erp-head--photo">
	<?php mags_erp_photo_img( (int) $erp_profile->photo_file_id, $erp_student->full_name ); ?>
	<div>
		<p class="erp-eyebrow">My profile</p>
		<h1><?php echo esc_html( $erp_student->full_name ); ?></h1>
	</div>
</header>

<section class="erp-card" aria-labelledby="erp-profile-title">
	<h2 id="erp-profile-title">Details with the school</h2>
	<dl class="erp-facts">
		<?php foreach ( $erp_facts as $erp_label => $erp_value ) : ?>
			<div><dt><?php echo esc_html( $erp_label ); ?></dt><dd><?php echo esc_html( '' !== (string) $erp_value ? $erp_value : 'Not filled' ); ?></dd></div>
		<?php endforeach; ?>
	</dl>
	<p class="erp-hint">These details are kept by the school office and are used for the ID card and certificates. If something is wrong or missing, especially the emergency number, please tell the school office.</p>
</section>
