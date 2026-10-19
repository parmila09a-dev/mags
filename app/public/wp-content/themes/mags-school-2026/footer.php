<?php
/** Site footer. */
$mags_footer_logo    = get_theme_file_uri( '/assets/images/mags-logo.png' );
$mags_footer_logo_id = (int) get_theme_mod( 'custom_logo' );
$mags_footer_name    = mags_school_option( 'school_name', 'Maharaja Agrasen Global School' );
$mags_footer_address = mags_school_option( 'address', '1, Lakhi Ram Park, Opp. Sector-22, Rohini, Delhi-110086' );
$mags_footer_phones  = mags_school_phone_numbers();
$mags_footer_email   = mags_school_option( 'email' );
$mags_notice_url     = get_post_type_archive_link( 'mags_notice' ) ?: home_url( '/notices/' );
$mags_download_url   = get_post_type_archive_link( 'mags_download' ) ?: home_url( '/downloads/' );
$mags_gallery_url    = get_post_type_archive_link( 'mags_gallery' ) ?: home_url( '/gallery/' );
$mags_about_url      = mags_school_page_url( 'about-the-school' );
$mags_history_url    = mags_school_page_url( 'history', '/about-the-school/history/' );
$mags_mission_url    = mags_school_page_url( 'vision-and-mission', '/about-the-school/vision-and-mission/' );
$mags_infrastructure_url = mags_school_page_url( 'infrastructure' );
$mags_admission_url  = mags_school_page_url( 'admissions' );
$mags_scholarship_url = mags_school_page_url( 'scholarships', '/admissions/scholarships/' );
$mags_contact_url    = mags_school_page_url( 'contact-us' );
?>
</main>

<footer class="site-footer">
	<div class="wrap footer-grid">
		<div>
			<a class="brand brand--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $mags_footer_name . ' home' ); ?>">
				<?php if ( $mags_footer_logo_id ) : ?>
					<?php echo wp_get_attachment_image( $mags_footer_logo_id, 'full', false, array( 'class' => 'brand-logo', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
				<?php else : ?>
					<img class="brand-logo" src="<?php echo esc_url( $mags_footer_logo ); ?>" alt="" width="1600" height="943" loading="lazy" decoding="async">
				<?php endif; ?>
				<span class="brand-copy">
					<strong class="brand-title"><?php echo esc_html( $mags_footer_name ); ?></strong>
					<small class="brand-subtitle" lang="hi">आत्म दीपो भवः</small>
				</span>
			</a>
			<p>Value-based learning from Pre-primary onwards, guided by the belief: <em>Be your own light.</em></p>
			<div class="socials">
				<?php
				foreach ( array( 'facebook' => 'f', 'instagram' => 'ig', 'youtube' => '▶' ) as $network => $label ) {
					$url = mags_school_option( $network );
					if ( $url ) {
						echo '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( ucfirst( $network ) ) . '">' . esc_html( $label ) . '</a>';
					}
				}
				?>
			</div>
		</div>

		<div>
			<h2>About MAGS</h2>
			<ul>
				<li><a href="<?php echo esc_url( $mags_about_url ); ?>">About the School</a></li>
				<li><a href="<?php echo esc_url( $mags_history_url ); ?>">History</a></li>
				<li><a href="<?php echo esc_url( $mags_mission_url ); ?>">Mission &amp; Motto</a></li>
				<li><a href="<?php echo esc_url( $mags_infrastructure_url ); ?>">Infrastructure</a></li>
			</ul>
		</div>

		<div>
			<h2>Useful links</h2>
			<ul>
				<li><a href="<?php echo esc_url( $mags_admission_url ); ?>">Admissions</a></li>
				<li><a href="<?php echo esc_url( $mags_scholarship_url ); ?>">Scholarships</a></li>
				<li><a href="<?php echo esc_url( $mags_download_url ); ?>">Downloads</a></li>
				<li><a href="<?php echo esc_url( $mags_gallery_url ); ?>">Gallery</a></li>
				<li><a href="<?php echo esc_url( $mags_notice_url ); ?>">Notice Board</a></li>
				<?php if ( function_exists( 'mags_erp_url' ) ) : ?><li><a href="<?php echo esc_url( mags_erp_url() ); ?>">Student &amp; Staff Portal</a></li><?php endif; ?>
			</ul>
		</div>

		<div>
			<h2>Connect with us</h2>
			<p><?php echo nl2br( esc_html( $mags_footer_address ) ); ?></p>
			<?php if ( $mags_footer_phones || $mags_footer_email ) : ?>
				<p>
					<?php foreach ( $mags_footer_phones as $index => $mags_footer_phone ) : ?><?php if ( $index ) : ?><br><?php endif; ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mags_footer_phone ) ); ?>"><?php echo esc_html( $mags_footer_phone ); ?></a><?php endforeach; ?>
					<?php if ( $mags_footer_phones && $mags_footer_email ) : ?><br><?php endif; ?>
					<?php if ( $mags_footer_email ) : ?>
						<a href="mailto:<?php echo esc_attr( $mags_footer_email ); ?>"><?php echo esc_html( $mags_footer_email ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( $mags_contact_url ); ?>">Directions &amp; contact form →</a></p>
		</div>
	</div>
	<div class="wrap copyright">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> Maharaja Agrasen Global School, Delhi. All rights reserved.</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
