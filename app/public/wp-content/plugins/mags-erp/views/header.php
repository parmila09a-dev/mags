<?php
/** Shared top of every portal screen. Expects $ctx from mags_erp_render(). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$erp_logo   = mags_erp_logo_url();
$erp_school = mags_erp_school_name();
$erp_role   = $ctx['role'];
$erp_labels = mags_erp_role_labels();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="robots" content="noindex, nofollow">
	<meta name="theme-color" content="#e8ecfa">
	<title><?php echo esc_html( $ctx['title'] . ' · ' . $erp_school . ' Portal' ); ?></title>
	<?php if ( $erp_logo ) : ?><link rel="icon" href="<?php echo esc_url( $erp_logo ); ?>"><?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( MAGS_ERP_URL . 'assets/portal.css?ver=' . MAGS_ERP_VERSION ); ?>">
</head>
<body class="erp<?php echo $erp_role ? ' erp--' . esc_attr( $erp_role ) . ' erp--app' : ' erp--guest'; ?>">
<a class="erp-skip" href="#erp-main">Skip to content</a>

<header class="erp-top">
	<a class="erp-brand" href="<?php echo esc_url( $erp_role ? mags_erp_url() : home_url( '/' ) ); ?>">
		<?php if ( $erp_logo ) : ?><img src="<?php echo esc_url( $erp_logo ); ?>" alt="" width="44" height="44"><?php else : ?><span class="erp-brand__mark" aria-hidden="true">M</span><?php endif; ?>
		<span><strong><?php echo esc_html( $erp_school ); ?></strong><small>School Portal</small></span>
	</a>
	<?php if ( is_user_logged_in() ) : ?>
		<div class="erp-top__user">
			<?php if ( $erp_role ) : ?><span class="erp-chip erp-chip--role"><?php echo esc_html( $erp_labels[ $erp_role ] ); ?></span><?php endif; ?>
			<form method="post" action="<?php echo esc_url( mags_erp_url() ); ?>">
				<?php mags_erp_form_fields( 'logout' ); ?>
				<button class="erp-iconbtn" type="submit" aria-label="Sign out"><?php echo mags_erp_icon( 'logout' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Sign out</span></button>
			</form>
		</div>
	<?php endif; ?>
</header>

<?php if ( $ctx['screens'] ) : ?>
	<?php
	// The bar shows the main screens and "More"; "More" is lit while a screen from its list is open.
	$erp_in_bar   = ! empty( $ctx['screens'][ $ctx['route'] ][4] );
	$erp_waiting  = array_sum( array_diff_key( $ctx['badges'], array_filter( $ctx['screens'], static function ( $erp_screen ) { return ! empty( $erp_screen[4] ); } ) ) );
	?>
	<nav class="erp-nav" aria-label="Portal sections">
		<?php foreach ( $ctx['screens'] as $erp_nav_route => $erp_nav_screen ) : if ( empty( $erp_nav_screen[4] ) ) { continue; } ?>
			<?php $erp_nav_count = 'more' === $erp_nav_route ? $erp_waiting : ( $ctx['badges'][ $erp_nav_route ] ?? 0 ); ?>
			<a href="<?php echo esc_url( mags_erp_url( $erp_nav_route ) ); ?>"<?php echo $erp_nav_route === $ctx['route'] || ( 'more' === $erp_nav_route && ! $erp_in_bar ) ? ' aria-current="page"' : ''; ?>><?php echo mags_erp_icon( $erp_nav_screen[3] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $erp_nav_screen[2] ); ?></span><?php if ( $erp_nav_count ) : ?><b class="erp-nav__count"><?php echo esc_html( $erp_nav_count ); ?><span class="erp-sr"> waiting</span></b><?php endif; ?></a>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>

<main id="erp-main" class="erp-main" tabindex="-1">
	<?php if ( $ctx['flash'] ) : ?>
		<div class="erp-flash erp-flash--<?php echo 'error' === $ctx['flash']['type'] ? 'error' : 'success'; ?>" role="<?php echo 'error' === $ctx['flash']['type'] ? 'alert' : 'status'; ?>">
			<p><?php echo esc_html( $ctx['flash']['message'] ); ?></p>
			<?php if ( ! empty( $ctx['flash']['details']['lines'] ) ) : ?>
				<ul class="erp-flash__lines">
					<?php foreach ( (array) $ctx['flash']['details']['lines'] as $erp_line ) : ?><li><?php echo esc_html( $erp_line ); ?></li><?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $ctx['flash']['details']['logins'] ) ) : $erp_logins = (array) $ctx['flash']['details']['logins']; ?>
				<?php
				// Login details of students added from a file: shown once, with a file to download and keep.
				$erp_login_text = '';
				foreach ( $erp_logins as $erp_login ) { $erp_login_text .= sprintf( "%s (%s)\nLogin ID: %s\nPassword: %s\n\n", $erp_login[0], $erp_login[1], $erp_login[2], $erp_login[3] ); }
				?>
				<div class="erp-table-wrap erp-credentials erp-credentials--list" data-erp-logins>
					<table class="erp-table">
						<caption class="erp-sr">Login details of the new students</caption>
						<thead><tr><th scope="col">Name</th><th scope="col">Class</th><th scope="col">Login ID</th><th scope="col">Password</th></tr></thead>
						<tbody>
							<?php foreach ( $erp_logins as $erp_login ) : ?>
								<tr><td><?php echo esc_html( $erp_login[0] ); ?></td><td><?php echo esc_html( $erp_login[1] ); ?></td><td><code><?php echo esc_html( $erp_login[2] ); ?></code></td><td><code><?php echo esc_html( $erp_login[3] ); ?></code></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p class="erp-flash__actions">
					<button class="erp-btn erp-btn--navy erp-btn--sm" type="button" data-erp-save-logins="<?php echo esc_attr( 'student-logins-' . mags_erp_today() . '.csv' ); ?>" hidden>Download login list</button>
					<button class="erp-btn erp-btn--ghost erp-btn--sm" type="button" data-erp-copy="<?php echo esc_attr( 'Portal: ' . mags_erp_url( 'login' ) . "\nSign in as: Student / Parent\n\n" . $erp_login_text ); ?>" hidden>Copy all</button>
					<button class="erp-btn erp-btn--ghost erp-btn--sm" type="button" data-erp-print hidden>Print</button>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $ctx['flash']['details']['password'] ) ) : $erp_details = $ctx['flash']['details']; ?>
				<dl class="erp-credentials">
					<div><dt>Name</dt><dd><?php echo esc_html( $erp_details['name'] ); ?></dd></div>
					<div><dt>Sign in as</dt><dd><?php echo esc_html( $erp_details['login_as'] ); ?></dd></div>
					<div><dt>Login ID</dt><dd><code><?php echo esc_html( $erp_details['login'] ); ?></code></dd></div>
					<div><dt>Password</dt><dd><code><?php echo esc_html( $erp_details['password'] ); ?></code></dd></div>
				</dl>
				<button class="erp-btn erp-btn--navy erp-btn--sm" type="button" data-erp-copy="<?php echo esc_attr( sprintf( "%s\nPortal: %s\nSign in as: %s\nLogin ID: %s\nPassword: %s", $erp_details['name'], mags_erp_url( 'login' ), $erp_details['login_as'], $erp_details['login'], $erp_details['password'] ) ); ?>" hidden>Copy login details</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
