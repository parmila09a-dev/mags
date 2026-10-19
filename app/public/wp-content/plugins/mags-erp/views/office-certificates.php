<?php
/** Office: the register of every certificate made, with download and cancel. A new certificate is made from the student's profile. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_kinds  = mags_erp_certificate_kinds();
$erp_kind   = mags_erp_get_text( 'kind' );
$erp_kind   = isset( $erp_kinds[ $erp_kind ] ) ? $erp_kind : '';
$erp_search = mags_erp_get_text( 'q' );
$erp_rows   = mags_erp_query_certificates( $erp_kind, $erp_search );
?>
<header class="erp-head">
	<p class="erp-eyebrow">School office</p>
	<h1>Certificates</h1>
</header>

<section class="erp-card" aria-labelledby="erp-cert-new-title">
	<h2 id="erp-cert-new-title">Make a certificate</h2>
	<p class="erp-hint erp-hint--lead">Bonafide, character and transfer certificates are made from the student’s profile, because they print the parents’ names and the date of birth from it.</p>
	<form class="erp-form erp-form--row" method="get" action="<?php echo esc_url( mags_erp_url( 'profile' ) ); ?>">
		<?php mags_erp_route_field( 'profile' ); ?>
		<p class="erp-field erp-field--wide"><label for="erp_cert_find">Student’s name or admission number</label><input id="erp_cert_find" name="q" type="search" maxlength="60" required></p>
		<div class="erp-form__actions"><button class="erp-btn erp-btn--orange" type="submit">Find student</button></div>
	</form>
</section>

<section aria-labelledby="erp-cert-list-title">
	<h2 id="erp-cert-list-title" class="erp-section-title">Register <span class="erp-count"><?php echo esc_html( count( $erp_rows ) ); ?></span></h2>
	<form class="erp-filter" method="get" action="<?php echo esc_url( mags_erp_url( 'certificates' ) ); ?>">
		<?php mags_erp_route_field( 'certificates' ); ?>
		<p class="erp-field"><label for="erp_filter_kind">Kind</label><select id="erp_filter_kind" name="kind"><option value="">All certificates</option><?php foreach ( $erp_kinds as $erp_key => $erp_text ) : ?><option value="<?php echo esc_attr( $erp_key ); ?>" <?php selected( $erp_kind, $erp_key ); ?>><?php echo esc_html( $erp_text[0] ); ?></option><?php endforeach; ?></select></p>
		<p class="erp-field"><label for="erp_filter_q">Student, admission or certificate number</label><input id="erp_filter_q" name="q" type="search" maxlength="60" value="<?php echo esc_attr( $erp_search ); ?>"></p>
		<button class="erp-btn erp-btn--navy" type="submit">Show</button>
	</form>
	<?php if ( ! $erp_rows ) : ?>
		<p class="erp-card erp-empty"><?php echo esc_html( $erp_kind || '' !== $erp_search ? 'No certificate matches this filter.' : 'No certificate has been made yet.' ); ?></p>
	<?php else : ?>
		<ul class="erp-list erp-list--tight">
			<?php foreach ( $erp_rows as $erp_row ) : ?>
				<li class="erp-row<?php echo $erp_row->hidden_at ? ' erp-row--off' : ''; ?>">
					<div class="erp-row__main">
						<strong><?php echo esc_html( $erp_row->serial_no ); ?> · <?php echo esc_html( $erp_row->full_name ); ?></strong>
						<span><?php echo esc_html( $erp_kinds[ $erp_row->kind ][0] ?? 'Certificate' ); ?> · adm. no. <?php echo esc_html( $erp_row->admission_no ); ?> · made <?php echo esc_html( mags_erp_format_date( $erp_row->issued_on, 'j M Y' ) ); ?> by <?php echo esc_html( mags_erp_teacher_name( (int) $erp_row->created_by ) ); ?></span>
						<?php if ( $erp_row->hidden_at ) : ?><span class="erp-row__chips"><span class="erp-chip erp-chip--status-A">Cancelled</span></span><?php endif; ?>
					</div>
					<div class="erp-row__actions">
						<?php if ( ! $erp_row->hidden_at ) : ?><a class="erp-btn erp-btn--navy erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'certificate', array( 'id' => (int) $erp_row->id ) ) ); ?>">Download PDF<span class="erp-sr"> <?php echo esc_html( $erp_row->serial_no ); ?></span></a><?php endif; ?>
						<a class="erp-btn erp-btn--ghost erp-btn--sm" href="<?php echo esc_url( mags_erp_url( 'profile', array( 'student' => (int) $erp_row->student_id ) ) ); ?>">Profile<span class="erp-sr"> of <?php echo esc_html( $erp_row->full_name ); ?></span></a>
						<form method="post" action="<?php echo esc_url( mags_erp_url( 'certificates' ) ); ?>"<?php if ( ! $erp_row->hidden_at ) : ?> data-erp-confirm="<?php echo esc_attr( 'Cancel certificate ' . $erp_row->serial_no . '? It stays in the register but can no longer be downloaded.' ); ?>"<?php endif; ?>>
							<?php mags_erp_form_fields( 'cancel_certificate' ); ?>
							<input type="hidden" name="certificate_id" value="<?php echo esc_attr( $erp_row->id ); ?>"><input type="hidden" name="restore" value="<?php echo $erp_row->hidden_at ? 1 : 0; ?>"><input type="hidden" name="from" value="certificates">
							<button class="erp-btn <?php echo $erp_row->hidden_at ? 'erp-btn--ghost' : 'erp-btn--danger'; ?> erp-btn--sm" type="submit"><?php echo $erp_row->hidden_at ? 'Make valid again' : 'Cancel'; ?><span class="erp-sr"> <?php echo esc_html( $erp_row->serial_no ); ?></span></button>
						</form>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="erp-hint erp-hint--lead">The newest 200 are listed. A cancelled certificate keeps its number; the number is not used again.</p>
	<?php endif; ?>
</section>
