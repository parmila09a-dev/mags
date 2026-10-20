<?php
/** Office: the profile of one student (details, photo, documents, ID card, certificates), or a search to find a student. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_labels  = mags_erp_all_class_labels();
$erp_student = mags_erp_get_int( 'student' ) ? mags_erp_get_student( mags_erp_get_int( 'student' ) ) : null;

if ( ! $erp_student ) :
	$erp_search = mags_erp_get_text( 'q' );
	$erp_found  = array();
	if ( '' !== $erp_search ) {
		list( $erp_active )  = mags_erp_query_students( 0, $erp_search, 1, 50 );
		list( $erp_removed ) = mags_erp_query_students( 0, $erp_search, 1, 50, true );
		$erp_found           = array_merge( $erp_active, $erp_removed );
	}
	?>
	<header class="erp-head">
		<p class="erp-eyebrow">School office</p>
		<h1>Student profiles</h1>
	</header>

	<section aria-labelledby="erp-profile-find-title">
		<h2 id="erp-profile-find-title" class="erp-section-title">Find a student<?php if ( $erp_found ) : ?> <span class="erp-count"><?php echo esc_html( count( $erp_found ) ); ?></span><?php endif; ?></h2>
		<form class="erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'profile' ) ); ?>">
			<?php mags_erp_route_field( 'profile' ); ?>
			<p class="erp-field erp-field--wide"><label for="erp_filter_q">Name or admission number</label><input id="erp_filter_q" name="q" type="search" maxlength="60" value="<?php echo esc_attr( $erp_search ); ?>" required></p>
			<button class="erp-btn erp-btn--navy" type="submit">Show</button>
		</form>
		<?php if ( '' === $erp_search ) : ?>
			<p class="erp-card erp-empty">Search for a student to open the profile: details, photo, documents, ID card and certificates. You can also press “Profile” in the list under <a href="<?php echo esc_url( mags_erp_url( 'students' ) ); ?>">Students</a>.</p>
		<?php elseif ( ! $erp_found ) : ?>
			<p class="erp-card erp-empty">No student matches this search.</p>
		<?php else : ?>
			<ul class="erp-list erp-list--tight">
				<?php foreach ( $erp_found as $erp_row ) : ?>
					<li class="erp-row<?php echo $erp_row->hidden_at ? ' erp-row--off' : ''; ?>">
						<div class="erp-row__main">
							<strong><?php echo esc_html( $erp_row->full_name ); ?></strong>
							<span>Adm. no. <?php echo esc_html( $erp_row->admission_no ); ?> · <?php echo esc_html( $erp_labels[ (int) $erp_row->class_id ] ?? 'No class' ); ?><?php echo $erp_row->left_on ? ' · left school' : ( $erp_row->hidden_at ? ' · removed' : '' ); ?></span>
						</div>
						<div class="erp-row__actions"><a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'profile', array( 'student' => (int) $erp_row->id ) ) ); ?>">Open profile<span class="erp-sr"> of <?php echo esc_html( $erp_row->full_name ); ?></span></a></div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
	return;
endif;

$erp_profile      = mags_erp_get_profile( (int) $erp_student->id );
$erp_back         = mags_erp_url( 'profile', array( 'student' => (int) $erp_student->id ) );
$erp_documents    = mags_erp_student_documents( (int) $erp_student->id );
$erp_certificates = mags_erp_student_certificates( (int) $erp_student->id );
$erp_kinds        = mags_erp_certificate_kinds();
$erp_today        = mags_erp_today();
$erp_has_parent   = '' !== $erp_profile->father_name || '' !== $erp_profile->mother_name;
$erp_gone         = array();
foreach ( mags_erp_student_documents( (int) $erp_student->id, true ) as $erp_document ) { $erp_gone[] = array( (int) $erp_document->id, $erp_document->title, 'Removed ' . mags_erp_format_datetime( $erp_document->hidden_at, 'j M Y' ) ); }
?>
<header class="erp-head erp-head--photo">
	<?php mags_erp_photo_img( (int) $erp_profile->photo_file_id, $erp_student->full_name ); ?>
	<div>
		<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'students', array( 'class' => $erp_student->hidden_at ? '' : (int) $erp_student->class_id, 'removed' => $erp_student->hidden_at ? 1 : '' ) ) ); ?>">← Students</a></p>
		<h1><?php echo esc_html( $erp_student->full_name ); ?></h1>
		<p class="erp-head__meta"><span class="erp-chip erp-chip--blue"><?php echo esc_html( $erp_labels[ (int) $erp_student->class_id ] ?? 'No class' ); ?></span><?php if ( '' !== $erp_student->roll_no ) : ?><span class="erp-chip">Roll no. <?php echo esc_html( $erp_student->roll_no ); ?></span><?php endif; ?><span class="erp-chip">Adm. no. <?php echo esc_html( $erp_student->admission_no ); ?></span><?php if ( $erp_student->left_on ) : ?><span class="erp-chip erp-chip--status-A">Left school on <?php echo esc_html( mags_erp_format_date( $erp_student->left_on, 'j M Y' ) ); ?></span><?php elseif ( $erp_student->hidden_at ) : ?><span class="erp-chip erp-chip--status-A">Removed student</span><?php endif; ?></p>
	</div>
</header>

<section class="erp-card" aria-labelledby="erp-profile-form-title">
	<h2 id="erp-profile-form-title">Details</h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>" enctype="multipart/form-data">
		<?php mags_erp_form_fields( 'save_profile' ); ?>
		<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
		<div class="erp-form__grid">
			<p class="erp-field"><label for="erp_p_father">Father’s name</label><input id="erp_p_father" name="father_name" type="text" maxlength="120" autocomplete="off" value="<?php echo esc_attr( $erp_profile->father_name ); ?>"></p>
			<p class="erp-field"><label for="erp_p_mother">Mother’s name</label><input id="erp_p_mother" name="mother_name" type="text" maxlength="120" autocomplete="off" value="<?php echo esc_attr( $erp_profile->mother_name ); ?>"></p>
			<p class="erp-field"><label for="erp_p_dob">Date of birth</label><input id="erp_p_dob" name="dob" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( (string) $erp_profile->dob ); ?>"></p>
			<p class="erp-field"><label for="erp_p_gender">Boy or girl</label><select id="erp_p_gender" name="gender"><option value="">Not filled</option><?php foreach ( mags_erp_genders() as $erp_key => $erp_text ) : ?><option value="<?php echo esc_attr( $erp_key ); ?>" <?php selected( $erp_profile->gender, $erp_key ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select></p>
			<p class="erp-field"><label for="erp_p_blood">Blood group</label><select id="erp_p_blood" name="blood_group"><option value="">Not known</option><?php foreach ( mags_erp_blood_groups() as $erp_text ) : ?><option value="<?php echo esc_attr( $erp_text ); ?>" <?php selected( $erp_profile->blood_group, $erp_text ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select></p>
			<p class="erp-field"><label for="erp_p_admitted">Date of admission</label><input id="erp_p_admitted" name="admitted_on" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( (string) $erp_profile->admitted_on ); ?>"></p>
			<p class="erp-field"><label for="erp_p_phone">Parent’s phone number</label><input id="erp_p_phone" name="guardian_phone" type="tel" inputmode="tel" maxlength="20" autocomplete="off" value="<?php echo esc_attr( $erp_profile->guardian_phone ); ?>"></p>
			<p class="erp-field"><label for="erp_p_email">Parent’s email <span>(optional)</span></label><input id="erp_p_email" name="parent_email" type="email" maxlength="120" autocomplete="off" value="<?php echo esc_attr( $erp_profile->parent_email ); ?>"><small>For the emails about absence, fees and results (More → Settings).</small></p>
			<p class="erp-field"><label for="erp_p_emergency">Emergency number</label><input id="erp_p_emergency" name="emergency_phone" type="tel" inputmode="tel" maxlength="20" autocomplete="off" value="<?php echo esc_attr( $erp_profile->emergency_phone ); ?>"><small>The number to call when the child is hurt or unwell.</small></p>
			<p class="erp-field"><label for="erp_p_emergency_name">Whose number is it <span>(optional)</span></label><input id="erp_p_emergency_name" name="emergency_name" type="text" maxlength="120" autocomplete="off" placeholder="Uncle, grandmother…" value="<?php echo esc_attr( $erp_profile->emergency_name ); ?>"></p>
		</div>
		<p class="erp-field"><label for="erp_p_address">Address</label><textarea id="erp_p_address" name="address" rows="2" maxlength="500"><?php echo esc_textarea( $erp_profile->address ); ?></textarea></p>
		<?php if ( mags_erp_photo_supported() ) : ?>
			<p class="erp-field erp-field--file">
				<label for="erp_p_photo"><?php echo (int) $erp_profile->photo_file_id ? 'Change the photo' : 'Photo'; ?> <span>(optional)</span></label>
				<input id="erp_p_photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
				<small>A clear picture of the face, like a passport photo. JPG or PNG, up to <?php echo esc_html( size_format( mags_erp_max_file_size() ) ); ?>. It is cut to the shape of the ID card.</small>
			</p>
		<?php else : ?>
			<p class="erp-hint">This server cannot process photos (the GD or Imagick extension is missing). Please tell the site administrator.</p>
		<?php endif; ?>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Save profile</button></div>
	</form>
	<?php if ( (int) $erp_profile->photo_file_id ) : ?>
		<form class="erp-removed" method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="Remove the photo of this student?">
			<?php mags_erp_form_fields( 'remove_photo' ); ?>
			<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
			<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove photo</button>
		</form>
	<?php endif; ?>
	<p class="erp-hint">The student and parents can see these details and the photo in their portal, but cannot change them. Teachers do not see profiles.<?php if ( $erp_profile->updated_at ) : ?> Last changed <?php echo esc_html( mags_erp_format_datetime( $erp_profile->updated_at ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_profile->updated_by ) ); ?>.<?php endif; ?></p>
</section>

<section class="erp-card" aria-labelledby="erp-idcard-title">
	<h2 id="erp-idcard-title">ID card</h2>
	<?php if ( $erp_student->hidden_at ) : ?>
		<p class="erp-hint">A removed student has no ID card.</p>
	<?php else : ?>
		<p class="erp-card__actions">
			<a class="erp-btn erp-btn--navy" href="<?php echo esc_url( mags_erp_url( 'id-card', array( 'student' => (int) $erp_student->id ) ) ); ?>">Download ID card (PDF)</a>
			<a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'id-card', array( 'class' => (int) $erp_student->class_id ) ) ); ?>">ID cards of the whole class</a>
		</p>
		<p class="erp-hint">The card shows the photo, name, class, admission and roll number on the front, and date of birth, blood group, parent, emergency number and address on the back. Print the page at 100% and cut along the dotted lines.<?php if ( ! (int) $erp_profile->photo_file_id ) : ?> <strong>This student has no photo yet</strong>, so the card has an empty box for it.<?php endif; ?></p>
	<?php endif; ?>
</section>

<?php mags_erp_print_progress( $erp_student, 'Marks in each exam' ); ?>
<?php $erp_class_history = mags_erp_student_class_history( (int) $erp_student->id ); ?>
<?php if ( $erp_class_history ) : ?>
	<section class="erp-card" aria-labelledby="erp-history-title">
		<h2 id="erp-history-title">Class history</h2>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_class_history as $erp_moved ) : ?>
				<li class="erp-row"><div class="erp-row__main"><strong><?php echo esc_html( $erp_moved->class_label ); ?><?php echo '' !== $erp_moved->session ? esc_html( ' · session ' . $erp_moved->session ) : ''; ?></strong><span><?php echo esc_html( ( '' !== $erp_moved->roll_no ? 'Roll no. ' . $erp_moved->roll_no . ' · ' : '' ) . 'then ' . ( (int) $erp_moved->to_class_id ? 'moved to ' . $erp_moved->to_label : $erp_moved->to_label ) . ' on ' . mags_erp_format_datetime( $erp_moved->moved_at, 'j M Y' ) ); ?></span></div></li>
			<?php endforeach; ?>
			<li class="erp-row"><div class="erp-row__main"><strong><?php echo esc_html( $erp_student->hidden_at ? 'Left the school' : ( $erp_labels[ (int) $erp_student->class_id ] ?? 'No class' ) . ' · now' ); ?></strong></div></li>
		</ul>
		<p class="erp-hint">Attendance, marks and report cards of the earlier classes are kept; the report cards are under Reports → Marks.</p>
	</section>
<?php endif; ?>

<section aria-labelledby="erp-documents-title">
	<h2 id="erp-documents-title" class="erp-section-title">Documents <span class="erp-count"><?php echo esc_html( count( $erp_documents ) ); ?></span></h2>
	<div class="erp-card">
		<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>" enctype="multipart/form-data">
			<?php mags_erp_form_fields( 'add_document' ); ?>
			<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>">
			<p class="erp-field"><label for="erp_doc_title">Document</label><input id="erp_doc_title" name="title" type="text" maxlength="120" placeholder="Birth certificate, Aadhaar card, TC of earlier school…" required></p>
			<?php mags_erp_file_input( 'document_file', 'File', true, 'A scan or a clear photo.' ); ?>
			<div class="erp-form__actions"><button class="erp-btn erp-btn--navy" type="submit">Add document</button></div>
		</form>
		<p class="erp-hint">Only the school office can open these documents.</p>
	</div>
	<?php if ( $erp_documents ) : ?>
		<ul class="erp-list erp-list--tight erp-list--after">
			<?php foreach ( $erp_documents as $erp_document ) : ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_document->title ); ?></strong>
						<span>Added <?php echo esc_html( mags_erp_format_datetime( $erp_document->created_at, 'j M Y' ) ); ?> by <?php echo esc_html( 0 === (int) $erp_document->added_by ? 'the parent (admission form)' : mags_erp_teacher_name( (int) $erp_document->added_by ) ); ?></span>
						<?php mags_erp_file_link( (int) $erp_document->file_id ); ?>
					</div>
					<div class="erp-row__actions">
						<form method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="<?php echo esc_attr( 'Remove “' . $erp_document->title . '”? It is kept and can be restored.' ); ?>">
							<?php mags_erp_form_fields( 'delete_document' ); ?>
							<input type="hidden" name="document_id" value="<?php echo esc_attr( $erp_document->id ); ?>">
							<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove<span class="erp-sr"> <?php echo esc_html( $erp_document->title ); ?></span></button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php mags_erp_removed_list( $erp_gone, 'delete_document', 'document_id', 'profile', 'Removed documents' ); ?>
</section>

<section id="erp-certificates" aria-labelledby="erp-certificates-title">
	<h2 id="erp-certificates-title" class="erp-section-title">Certificates <span class="erp-count"><?php echo esc_html( count( $erp_certificates ) ); ?></span></h2>
	<?php if ( $erp_certificates ) : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_certificates as $erp_certificate ) : ?>
				<li class="erp-row<?php echo $erp_certificate->hidden_at ? ' erp-row--off' : ''; ?>">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_kinds[ $erp_certificate->kind ][0] ?? 'Certificate' ); ?> <span class="erp-chip"><?php echo esc_html( $erp_certificate->serial_no ); ?></span><?php if ( $erp_certificate->hidden_at ) : ?> <span class="erp-chip erp-chip--status-A">Cancelled</span><?php endif; ?></strong>
						<span>Made <?php echo esc_html( mags_erp_format_date( $erp_certificate->issued_on, 'j M Y' ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_certificate->created_by ) ); ?></span>
					</div>
					<div class="erp-row__actions">
						<?php if ( ! $erp_certificate->hidden_at ) : ?><a class="erp-btn erp-btn--navy erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'certificate', array( 'id' => (int) $erp_certificate->id ) ) ); ?>">Download PDF<span class="erp-sr"> <?php echo esc_html( $erp_certificate->serial_no ); ?></span></a><?php endif; ?>
						<form method="post" action="<?php echo esc_url( $erp_back ); ?>"<?php if ( ! $erp_certificate->hidden_at ) : ?> data-erp-confirm="<?php echo esc_attr( 'Cancel certificate ' . $erp_certificate->serial_no . '? It stays in the list but can no longer be downloaded.' ); ?>"<?php endif; ?>>
							<?php mags_erp_form_fields( 'cancel_certificate' ); ?>
							<input type="hidden" name="certificate_id" value="<?php echo esc_attr( $erp_certificate->id ); ?>"><input type="hidden" name="restore" value="<?php echo $erp_certificate->hidden_at ? 1 : 0; ?>">
							<button class="erp-btn <?php echo $erp_certificate->hidden_at ? 'erp-btn--ghost' : 'erp-btn--danger'; ?> erp-btn--sm" type="submit"><?php echo $erp_certificate->hidden_at ? 'Make valid again' : 'Cancel'; ?><span class="erp-sr"> <?php echo esc_html( $erp_certificate->serial_no ); ?></span></button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="erp-card<?php echo $erp_certificates ? ' erp-list--after' : ''; ?>">
		<h3>Make a certificate</h3>
		<?php if ( ! $erp_has_parent ) : ?>
			<p class="erp-hint">Fill the father’s or mother’s name in the details above first. It is printed on every certificate.</p>
		<?php else : ?>
			<p class="erp-hint erp-hint--lead">The certificate uses the details above as they are now. Check them before you make it.<?php if ( ! $erp_profile->dob ) : ?> The bonafide and transfer certificates also need the date of birth.<?php endif; ?></p>
			<?php if ( ! $erp_student->hidden_at && $erp_profile->dob ) : ?>
				<details class="erp-upload">
					<summary class="erp-btn erp-btn--ghost erp-btn--sm">Bonafide certificate</summary>
					<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
						<?php mags_erp_form_fields( 'issue_certificate' ); ?>
						<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>"><input type="hidden" name="kind" value="bonafide">
						<p class="erp-field"><label for="erp_c_purpose">Purpose <span>(optional)</span></label><input id="erp_c_purpose" name="purpose" type="text" maxlength="160" placeholder="opening a bank account, passport, scholarship…"><small>Printed as “for the purpose of …”.</small></p>
						<div class="erp-form__actions"><button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Make bonafide certificate</button></div>
					</form>
				</details>
			<?php endif; ?>
			<details class="erp-upload">
				<summary class="erp-btn erp-btn--ghost erp-btn--sm">Character certificate</summary>
				<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( $erp_back ); ?>">
					<?php mags_erp_form_fields( 'issue_certificate' ); ?>
					<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>"><input type="hidden" name="kind" value="character">
					<p class="erp-field"><label for="erp_c_conduct">Conduct</label><select id="erp_c_conduct" name="conduct"><?php foreach ( mags_erp_conduct_words() as $erp_text ) : ?><option value="<?php echo esc_attr( $erp_text ); ?>" <?php selected( 'Good', $erp_text ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select></p>
					<p class="erp-field"><label for="erp_c_remarks">Remarks <span>(optional)</span></label><input id="erp_c_remarks" name="remarks" type="text" maxlength="300"></p>
					<div class="erp-form__actions"><button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Make character certificate</button></div>
				</form>
			</details>
			<?php if ( $erp_profile->dob ) : ?>
				<details class="erp-upload">
					<summary class="erp-btn erp-btn--ghost erp-btn--sm">Transfer certificate (TC)</summary>
					<form class="erp-form erp-upload__form" method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="<?php echo esc_attr( $erp_student->hidden_at ? 'Make the transfer certificate with these details?' : 'Make the transfer certificate with these details? ' . $erp_student->full_name . ' will be marked as left and can no longer sign in.' ); ?>">
						<?php mags_erp_form_fields( 'issue_certificate' ); ?>
						<input type="hidden" name="student_id" value="<?php echo esc_attr( $erp_student->id ); ?>"><input type="hidden" name="kind" value="tc">
						<div class="erp-form__grid">
							<p class="erp-field"><label for="erp_tc_leaving">Date of leaving the school</label><input id="erp_tc_leaving" name="leaving_date" type="date" max="<?php echo esc_attr( $erp_today ); ?>" value="<?php echo esc_attr( $erp_today ); ?>" required></p>
							<p class="erp-field"><label for="erp_tc_reason">Reason for leaving</label><input id="erp_tc_reason" name="reason" type="text" maxlength="160" value="Parent’s request"></p>
							<p class="erp-field"><label for="erp_tc_promoted">Qualified for promotion to the next class</label><select id="erp_tc_promoted" name="promoted"><option value="yes">Yes</option><option value="no">No</option><option value="na">Not applicable</option></select></p>
							<p class="erp-field"><label for="erp_tc_conduct">General conduct</label><select id="erp_tc_conduct" name="conduct"><?php foreach ( mags_erp_conduct_words() as $erp_text ) : ?><option value="<?php echo esc_attr( $erp_text ); ?>" <?php selected( 'Good', $erp_text ); ?>><?php echo esc_html( $erp_text ); ?></option><?php endforeach; ?></select></p>
							<p class="erp-field"><label for="erp_tc_nationality">Nationality</label><input id="erp_tc_nationality" name="nationality" type="text" maxlength="40" value="Indian"></p>
							<p class="erp-field"><label for="erp_tc_remarks">Remarks <span>(optional)</span></label><input id="erp_tc_remarks" name="remarks" type="text" maxlength="300"></p>
						</div>
						<p class="erp-hint">The portal fills in the class, the date of admission, the days present in the session and whether the fees are paid. Making the TC marks the student as left: they leave the class lists and the login is switched off. Attendance, marks and fees are kept, and the student can be restored under Students → Removed students.</p>
						<div class="erp-form__actions"><button class="erp-btn erp-btn--orange erp-btn--sm" type="submit">Make transfer certificate</button></div>
					</form>
				</details>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>
