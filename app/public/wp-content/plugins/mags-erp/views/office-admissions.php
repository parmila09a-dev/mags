<?php
/** Office: open or close the admission form, read the applications, accept them (this makes the student) or not. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_statuses = mags_erp_admission_statuses();
$erp_classes  = mags_erp_get_classes();
$erp_item     = mags_erp_get_admission( mags_erp_get_int( 'id' ) );

if ( $erp_item ) :
	$erp_status  = $erp_statuses[ $erp_item->status ] ?? array( $erp_item->status, '' );
	$erp_genders = mags_erp_genders();
	$erp_back    = mags_erp_url( 'admissions', array( 'id' => (int) $erp_item->id ) );
	$erp_facts   = array(
		'Admission wanted in'   => $erp_item->applied_class,
		'Date of birth'         => $erp_item->dob ? mags_erp_format_date( $erp_item->dob ) : '',
		'Boy or girl'           => $erp_genders[ $erp_item->gender ] ?? '',
		'Blood group'           => $erp_item->blood_group,
		'Father’s name'         => $erp_item->father_name,
		'Mother’s name'         => $erp_item->mother_name,
		'Phone number'          => $erp_item->phone,
		'Email'                 => $erp_item->email,
		'Address'               => $erp_item->address,
		'School the child goes to now' => $erp_item->previous_school,
		'Form sent on'          => mags_erp_format_datetime( $erp_item->created_at ),
	);
	// Suggest the class the parent asked for, when exactly one class has that name.
	$erp_suggest = 0;
	$erp_same    = array_values( array_filter( $erp_classes, static function ( $erp_class ) use ( $erp_item ) { return $erp_class->name === $erp_item->applied_class; } ) );
	if ( 1 === count( $erp_same ) ) { $erp_suggest = (int) $erp_same[0]->id; }
	?>
	<header class="erp-head erp-head--photo">
		<?php mags_erp_photo_img( (int) $erp_item->photo_file_id, $erp_item->student_name ); ?>
		<div>
			<p class="erp-eyebrow"><a href="<?php echo esc_url( mags_erp_url( 'admissions', array( 'status' => $erp_item->status ) ) ); ?>">← Admissions</a></p>
			<h1><?php echo esc_html( $erp_item->student_name ); ?></h1>
			<p class="erp-head__meta"><span class="erp-chip"><?php echo esc_html( mags_erp_admission_no_text( $erp_item->id ) ); ?></span><span class="erp-chip <?php echo esc_attr( $erp_status[1] ); ?>"><?php echo esc_html( $erp_status[0] ); ?></span></p>
		</div>
	</header>

	<section class="erp-card" aria-labelledby="erp-adm-details-title">
		<h2 id="erp-adm-details-title">What the parent filled</h2>
		<dl class="erp-facts">
			<?php foreach ( $erp_facts as $erp_label => $erp_value ) : ?>
				<div><dt><?php echo esc_html( $erp_label ); ?></dt><dd><?php echo esc_html( '' !== (string) $erp_value ? $erp_value : '–' ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
		<?php if ( 'approved' !== $erp_item->status && mags_erp_get_file( (int) $erp_item->document_file_id ) ) : ?>
			<p class="erp-hint">Document sent with the form:</p>
			<?php mags_erp_file_link( (int) $erp_item->document_file_id ); ?>
		<?php endif; ?>
		<p class="erp-hint">This form was filled on the website by someone who was not signed in. Check the details and the documents with the parent before accepting.</p>
	</section>

	<?php if ( 'approved' === $erp_item->status ) : ?>
		<section class="erp-card" aria-labelledby="erp-adm-done-title">
			<h2 id="erp-adm-done-title">Accepted</h2>
			<p class="erp-hint erp-hint--lead">Accepted <?php echo esc_html( mags_erp_format_datetime( $erp_item->decided_at ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_item->decided_by ) ); ?>.<?php if ( '' !== $erp_item->decision_note ) : ?> Note: <?php echo esc_html( $erp_item->decision_note ); ?><?php endif; ?></p>
			<p class="erp-card__actions"><a class="erp-btn erp-btn--navy" href="<?php echo esc_url( mags_erp_url( 'profile', array( 'student' => (int) $erp_item->student_id ) ) ); ?>">Open the student’s profile</a></p>
		</section>
	<?php else : ?>
		<section class="erp-card" aria-labelledby="erp-adm-accept-title">
			<h2 id="erp-adm-accept-title">Accept and add as a student</h2>
			<?php if ( 'rejected' === $erp_item->status ) : ?><p class="erp-hint erp-hint--lead">Marked as not accepted <?php echo esc_html( mags_erp_format_datetime( $erp_item->decided_at ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_item->decided_by ) ); ?>.<?php if ( '' !== $erp_item->decision_note ) : ?> Note: <?php echo esc_html( $erp_item->decision_note ); ?><?php endif; ?> It can still be accepted.</p><?php endif; ?>
			<?php if ( ! $erp_classes ) : ?>
				<p class="erp-hint">Add the classes first. <a href="<?php echo esc_url( mags_erp_url( 'classes' ) ); ?>">Add classes</a></p>
			<?php else : ?>
				<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="<?php echo esc_attr( 'Accept this application and add ' . $erp_item->student_name . ' as a student?' ); ?>">
					<?php mags_erp_form_fields( 'decide_admission' ); ?>
					<input type="hidden" name="admission_id" value="<?php echo esc_attr( $erp_item->id ); ?>"><input type="hidden" name="decision" value="approved">
					<div class="erp-form__grid erp-form__grid--three">
						<p class="erp-field"><label for="erp_adm_class">Class and section</label><select id="erp_adm_class" name="class_id" required><?php mags_erp_class_options( $erp_classes, $erp_suggest ); ?></select></p>
						<p class="erp-field"><label for="erp_adm_no">Admission number</label><input id="erp_adm_no" name="admission_no" type="text" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9\/._\-]*" autocomplete="off" autocapitalize="characters" spellcheck="false" required><small>The student and parents sign in with this number.</small></p>
						<p class="erp-field"><label for="erp_adm_roll">Roll number <span>(optional)</span></label><input id="erp_adm_roll" name="roll_no" type="text" inputmode="numeric" maxlength="10" autocomplete="off"></p>
					</div>
					<p class="erp-field"><label for="erp_adm_note">Note for the office <span>(optional)</span></label><input id="erp_adm_note" name="decision_note" type="text" maxlength="500" autocomplete="off"></p>
					<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Accept and add student</button></div>
				</form>
				<p class="erp-hint">Accepting makes the student and the portal login, and fills the profile (parents, date of birth, phone, address, photo and document) from this form. The login details are shown once on the next screen.</p>
			<?php endif; ?>
		</section>

		<?php if ( 'new' === $erp_item->status ) : ?>
			<section class="erp-card" aria-labelledby="erp-adm-reject-title">
				<h2 id="erp-adm-reject-title">Do not accept</h2>
				<form class="erp-form" method="post" action="<?php echo esc_url( $erp_back ); ?>" data-erp-confirm="Mark this application as not accepted?">
					<?php mags_erp_form_fields( 'decide_admission' ); ?>
					<input type="hidden" name="admission_id" value="<?php echo esc_attr( $erp_item->id ); ?>"><input type="hidden" name="decision" value="rejected">
					<p class="erp-field"><label for="erp_adm_reason">Reason, for the office <span>(optional)</span></label><input id="erp_adm_reason" name="decision_note" type="text" maxlength="500" autocomplete="off" placeholder="No seat in Class 5, under age…"></p>
					<div class="erp-form__actions"><button class="erp-btn erp-btn--danger" type="submit">Do not accept</button></div>
				</form>
				<p class="erp-hint">The parent is not told by the portal. The application stays in the list “Not accepted”.</p>
			</section>
		<?php endif; ?>

		<form class="erp-removed" method="post" action="<?php echo esc_url( mags_erp_url( 'admissions' ) ); ?>" data-erp-confirm="Remove this application from the lists? Use this for forms sent by mistake or as a joke. It is kept and can be restored.">
			<?php mags_erp_form_fields( 'delete_admission' ); ?>
			<input type="hidden" name="admission_id" value="<?php echo esc_attr( $erp_item->id ); ?>">
			<button class="erp-btn erp-btn--danger erp-btn--sm" type="submit">Remove from the lists</button>
		</form>
	<?php endif; ?>
	<?php
	return;
endif;

$erp_settings = mags_erp_admission_settings();
$erp_counts   = mags_erp_admission_counts();
$erp_status   = mags_erp_get_text( 'status' );
$erp_status   = isset( $erp_statuses[ $erp_status ] ) ? $erp_status : 'new';
$erp_search   = mags_erp_get_text( 'q' );
$erp_rows     = mags_erp_query_admissions( $erp_status, $erp_search );
$erp_tabs     = array();
foreach ( $erp_statuses as $erp_key => $erp_text ) { $erp_tabs[ mags_erp_url( 'admissions', array( 'status' => $erp_key ) ) ] = $erp_text[0] . ' (' . $erp_counts[ $erp_key ] . ')'; }
$erp_gone     = array();
foreach ( mags_erp_query_admissions( '', '', 100, true ) as $erp_row ) { $erp_gone[] = array( (int) $erp_row->id, $erp_row->student_name, mags_erp_admission_no_text( $erp_row->id ) . ' · ' . $erp_row->applied_class . ' · sent ' . mags_erp_format_datetime( $erp_row->created_at, 'j M Y' ) ); }
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Admissions</h1>
</header>

<section class="erp-card" aria-labelledby="erp-adm-open-title">
	<h2 id="erp-adm-open-title">Admission form on the website <span class="erp-chip <?php echo $erp_settings['open'] ? 'erp-chip--green' : 'erp-chip--status-A'; ?>"><?php echo $erp_settings['open'] ? 'Open' : 'Closed'; ?></span></h2>
	<form class="erp-form" method="post" action="<?php echo esc_url( mags_erp_url( 'admissions' ) ); ?>">
		<?php mags_erp_form_fields( 'save_admission_settings' ); ?>
		<fieldset class="erp-seg erp-seg--kinds">
			<legend>Parents can fill the form</legend>
			<label class="erp-seg__item erp-seg__item--P"><input type="radio" name="open" value="1" <?php checked( $erp_settings['open'] ); ?>><span>Open</span></label>
			<label class="erp-seg__item erp-seg__item--A"><input type="radio" name="open" value="0" <?php checked( ! $erp_settings['open'] ); ?>><span>Closed</span></label>
		</fieldset>
		<p class="erp-field"><label for="erp_adm_line">Line shown above the form <span>(optional)</span></label><input id="erp_adm_line" name="note" type="text" maxlength="200" placeholder="Admissions open for session 2027–28, Nursery to Class 8." value="<?php echo esc_attr( $erp_settings['note'] ); ?>"></p>
		<div class="erp-form__actions">
			<button class="erp-btn erp-btn--navy" type="submit">Save</button>
			<a class="erp-btn erp-btn--ghost" href="<?php echo esc_url( mags_erp_url( 'admission' ) ); ?>">See the form</a>
		</div>
	</form>
	<p class="erp-hint">Address of the form, to share with parents: <strong><?php echo esc_html( mags_erp_url( 'admission' ) ); ?></strong>. While it is open, the website footer and the portal sign-in page link to it. Anyone can fill it without a login, so close it when admissions are over.</p>
</section>

<section aria-labelledby="erp-adm-list-title">
	<h2 id="erp-adm-list-title" class="erp-section-title">Applications <span class="erp-count"><?php echo esc_html( count( $erp_rows ) ); ?></span></h2>
	<?php mags_erp_tabs( $erp_tabs, mags_erp_url( 'admissions', array( 'status' => $erp_status ) ), 'Applications by answer' ); ?>
	<form class="erp-filter erp-filter--below" method="get" action="<?php echo esc_url( mags_erp_url( 'admissions' ) ); ?>">
		<?php mags_erp_route_field( 'admissions' ); ?>
		<input type="hidden" name="status" value="<?php echo esc_attr( $erp_status ); ?>">
		<p class="erp-field erp-field--wide"><label for="erp_filter_q">Child’s or parent’s name, or phone number</label><input id="erp_filter_q" name="q" type="search" maxlength="60" value="<?php echo esc_attr( $erp_search ); ?>"></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>
	<?php if ( ! $erp_rows ) : ?>
		<p class="erp-card erp-empty"><?php echo esc_html( '' !== $erp_search ? 'No application matches this search.' : ( 'new' === $erp_status ? 'No new application.' : 'Nothing in this list.' ) ); ?></p>
	<?php else : ?>
		<ul class="erp-list">
			<?php foreach ( $erp_rows as $erp_row ) : ?>
				<li class="erp-row">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_row->student_name ); ?></strong>
						<span><?php echo esc_html( mags_erp_admission_no_text( $erp_row->id ) ); ?> · wants <?php echo esc_html( $erp_row->applied_class ); ?> · born <?php echo esc_html( $erp_row->dob ? mags_erp_format_date( $erp_row->dob, 'j M Y' ) : '–' ); ?></span>
						<span><?php echo esc_html( trim( $erp_row->father_name . ( '' !== $erp_row->father_name && '' !== $erp_row->mother_name ? ' / ' : '' ) . $erp_row->mother_name ) ); ?> · <?php echo esc_html( $erp_row->phone ); ?> · sent <?php echo esc_html( mags_erp_format_datetime( $erp_row->created_at, 'j M Y' ) ); ?></span>
					</div>
					<div class="erp-row__actions"><a class="erp-btn <?php echo 'new' === $erp_row->status ? 'erp-btn--orange' : 'erp-btn--ghost'; ?> erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'admissions', array( 'id' => (int) $erp_row->id ) ) ); ?>"><?php echo 'new' === $erp_row->status ? 'Read and answer' : 'Open'; ?><span class="erp-sr"> <?php echo esc_html( $erp_row->student_name ); ?></span></a></div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php mags_erp_removed_list( $erp_gone, 'delete_admission', 'admission_id', 'admissions', 'Removed applications' ); ?>
</section>
