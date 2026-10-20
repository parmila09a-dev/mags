<?php
/** The public admission form: parents fill it without a login. Expects $ctx['admission'] from mags_erp_admission_screen(). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_form   = $ctx['admission'];
$erp_v      = $erp_form['values'];
$erp_e      = $erp_form['errors'];
$erp_school = mags_erp_school_name();
$erp_today  = mags_erp_today();
/** Print the message for one field, when there is one. */
$erp_error  = static function ( $key ) use ( $erp_e ) {
	if ( isset( $erp_e[ $key ] ) ) { echo '<small class="erp-field__error" id="erp_adm_' . esc_attr( $key ) . '_error">' . esc_html( $erp_e[ $key ] ) . '</small>'; }
};
$erp_invalid = static function ( $key ) use ( $erp_e ) {
	return isset( $erp_e[ $key ] ) ? ' aria-invalid="true" aria-describedby="erp_adm_' . esc_attr( $key ) . '_error"' : '';
};
?>
<section class="erp-login erp-admission">
	<div class="erp-login__intro">
		<p class="erp-eyebrow">Admission</p>
		<h1>Admission form</h1>
		<p><?php echo esc_html( $erp_school ); ?></p>
	</div>

	<?php if ( '' !== $erp_form['done'] ) : ?>
		<div class="erp-card erp-admission__done" role="status">
			<h2>Thank you. Your form has reached the school.</h2>
			<p>Your application number is</p>
			<p class="erp-admission__number"><?php echo esc_html( $erp_form['done'] ); ?></p>
			<p>Please write this number down. The school office will read the form and contact you on the phone number you gave. You do not need to send the form again.</p>
			<p>With this number and your phone number you can check later whether the admission is accepted.</p>
			<p><a class="erp-btn erp-btn--navy" href="<?php echo esc_url( mags_erp_url( 'admission-status', array( 'no' => $erp_form['done'] ) ) ); ?>">Check the status</a> <a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the school website</a></p>
		</div>
	<?php elseif ( ! $erp_form['open'] ) : ?>
		<div class="erp-card erp-empty">
			<h2>Admissions are closed now</h2>
			<p>The online admission form is not open at present. Please contact the school office for admission.</p>
			<p>Sent a form already? <a href="<?php echo esc_url( mags_erp_url( 'admission-status' ) ); ?>">Check the status of your form</a>.</p>
			<p><a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the school website</a></p>
		</div>
	<?php else : ?>
		<form class="erp-card erp-form erp-admission__form" method="post" action="<?php echo esc_url( mags_erp_url( 'admission' ) ); ?>" enctype="multipart/form-data" novalidate>
			<?php mags_erp_form_fields( 'apply_admission' ); ?>
			<input type="hidden" name="erp_started" value="<?php echo esc_attr( mags_erp_admission_stamp() ); ?>">
			<p class="erp-sr" aria-hidden="true"><label for="erp_website">Leave this empty</label><input id="erp_website" name="erp_website" type="text" tabindex="-1" autocomplete="off" value=""></p>

			<?php if ( '' !== $erp_form['note'] ) : ?><p class="erp-flash erp-flash--success"><?php echo esc_html( $erp_form['note'] ); ?></p><?php endif; ?>
			<?php if ( $erp_e ) : ?>
				<div class="erp-flash erp-flash--error" role="alert">
					<p><?php echo esc_html( $erp_e['form'] ?? 'The form is not sent yet. Please correct the fields marked below and press Send again.' ); ?></p>
					<?php if ( array_intersect_key( $erp_e, array( 'photo' => 1, 'document_file' => 1 ) ) || $erp_v['student_name'] ) : ?><p class="erp-flash__small">If you had chosen a photo or a document, please choose it again.</p><?php endif; ?>
				</div>
			<?php endif; ?>

			<h2>The child</h2>
			<p class="erp-field"><label for="erp_adm_name">Child’s full name</label><input id="erp_adm_name" name="student_name" type="text" maxlength="120" autocomplete="off" value="<?php echo esc_attr( $erp_v['student_name'] ); ?>" required<?php echo $erp_invalid( 'student_name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php $erp_error( 'student_name' ); ?></p>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_adm_dob">Date of birth</label><input id="erp_adm_dob" name="dob" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( $erp_v['dob'] ); ?>" required<?php echo $erp_invalid( 'dob' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php $erp_error( 'dob' ); ?></p>
				<p class="erp-field"><label for="erp_adm_gender">Boy or girl <span>(optional)</span></label><select id="erp_adm_gender" name="gender"><option value="">Choose</option><?php foreach ( mags_erp_genders() as $erp_key => $erp_text ) : ?><option value="<?php echo esc_attr( $erp_key ); ?>" <?php selected( $erp_v['gender'], $erp_key ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select><?php $erp_error( 'gender' ); ?></p>
				<p class="erp-field"><label for="erp_adm_class">Admission wanted in class</label>
					<?php if ( $erp_form['classes'] ) : ?>
						<select id="erp_adm_class" name="applied_class" required<?php echo $erp_invalid( 'applied_class' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><option value="">Choose class</option><?php foreach ( $erp_form['classes'] as $erp_text ) : ?><option value="<?php echo esc_attr( $erp_text ); ?>" <?php selected( $erp_v['applied_class'], $erp_text ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select>
					<?php else : ?>
						<input id="erp_adm_class" name="applied_class" type="text" maxlength="60" value="<?php echo esc_attr( $erp_v['applied_class'] ); ?>" required<?php echo $erp_invalid( 'applied_class' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php endif; ?>
					<?php $erp_error( 'applied_class' ); ?></p>
				<p class="erp-field"><label for="erp_adm_blood">Blood group <span>(optional)</span></label><select id="erp_adm_blood" name="blood_group"><option value="">Not known</option><?php foreach ( mags_erp_blood_groups() as $erp_text ) : ?><option value="<?php echo esc_attr( $erp_text ); ?>" <?php selected( $erp_v['blood_group'], $erp_text ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select><?php $erp_error( 'blood_group' ); ?></p>
			</div>
			<p class="erp-field"><label for="erp_adm_school">School the child goes to now <span>(optional)</span></label><input id="erp_adm_school" name="previous_school" type="text" maxlength="160" value="<?php echo esc_attr( $erp_v['previous_school'] ); ?>"><?php $erp_error( 'previous_school' ); ?></p>

			<h2>Parents</h2>
			<div class="erp-form__grid">
				<p class="erp-field"><label for="erp_adm_father">Father’s name</label><input id="erp_adm_father" name="father_name" type="text" maxlength="120" value="<?php echo esc_attr( $erp_v['father_name'] ); ?>"<?php echo $erp_invalid( 'father_name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php $erp_error( 'father_name' ); ?></p>
				<p class="erp-field"><label for="erp_adm_mother">Mother’s name</label><input id="erp_adm_mother" name="mother_name" type="text" maxlength="120" value="<?php echo esc_attr( $erp_v['mother_name'] ); ?>"><?php $erp_error( 'mother_name' ); ?></p>
				<p class="erp-field"><label for="erp_adm_phone">Phone number</label><input id="erp_adm_phone" name="phone" type="tel" inputmode="tel" maxlength="20" autocomplete="tel" value="<?php echo esc_attr( $erp_v['phone'] ); ?>" required<?php echo $erp_invalid( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><small>The school will call this number.</small><?php $erp_error( 'phone' ); ?></p>
				<p class="erp-field"><label for="erp_adm_email">Email <span>(optional)</span></label><input id="erp_adm_email" name="email" type="email" maxlength="120" autocomplete="email" value="<?php echo esc_attr( $erp_v['email'] ); ?>"<?php echo $erp_invalid( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php $erp_error( 'email' ); ?></p>
			</div>
			<p class="erp-field"><label for="erp_adm_address">Address</label><textarea id="erp_adm_address" name="address" rows="2" maxlength="500" autocomplete="street-address" required<?php echo $erp_invalid( 'address' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_textarea( $erp_v['address'] ); ?></textarea><?php $erp_error( 'address' ); ?></p>

			<h2>Photo and document <span class="erp-admission__optional">(optional)</span></h2>
			<?php if ( mags_erp_photo_supported() ) : ?>
				<p class="erp-field erp-field--file"><label for="erp_adm_photo">Child’s photo <span>(optional)</span></label><input id="erp_adm_photo" name="photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"><small>A clear picture of the face. JPG or PNG.</small><?php $erp_error( 'photo' ); ?></p>
			<?php endif; ?>
			<p class="erp-field erp-field--file"><label for="erp_adm_document">Birth certificate <span>(optional)</span></label><input id="erp_adm_document" name="document_file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"><small>PDF, JPG or PNG, up to <?php echo esc_html( size_format( mags_erp_admission_document_bytes() ) ); ?>. You can also bring it to the school.</small><?php $erp_error( 'document_file' ); ?></p>

			<p class="erp-check"><label><input type="checkbox" name="agree" value="1" <?php checked( '' !== $erp_v['agree'] ); ?> required> <span>I am the child’s parent or guardian and these details are correct. The school may use them for the admission.</span></label></p>
			<?php $erp_error( 'agree' ); ?>

			<button class="erp-btn erp-btn--orange erp-btn--block" type="submit">Send admission form</button>
			<p class="erp-hint">Sending the form does not mean that the admission is given. The school office will contact you.</p>
			<p class="erp-hint">Sent a form already? <a href="<?php echo esc_url( mags_erp_url( 'admission-status' ) ); ?>">Check the status of your form</a>.</p>
		</form>
	<?php endif; ?>
</section>
