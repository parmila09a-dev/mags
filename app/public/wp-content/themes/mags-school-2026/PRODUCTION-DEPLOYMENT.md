# MAGS production deployment runbook

This package provides the complete local WordPress theme/plugin implementation. A public launch is a separate controlled operation: production contact values, domain/HTTPS, SMTP, final CMS content, media consent and backups cannot be completed safely in theme source code.

## 1. Minimum production platform

- A supported, patched WordPress release on PHP 8.1+ and a maintained MySQL/MariaDB release.
- Approved public domain and DNS, HTTPS with automatic renewal, and HTTP-to-HTTPS redirect.
- Authenticated SMTP or a transactional email service approved by the school/host.
- Automated off-site database and `wp-content/uploads` backups with a tested restore.
- Named least-privilege accounts; use Editors for routine publishing and keep Administrators limited.
- A staging environment for plugin, theme, content and browser testing before release.

## 2. Choose the correct deployment method

### A. Migrate the completed local site

Use this method for the completed local site, whose database/uploads contain eight CMS albums and 384 prepared school-supplied WebP images with responsive sizes. It preserves the existing attachment IDs, album organization and official school-building homepage hero.

1. Take a synchronized backup of the local database and `wp-content/uploads`.
2. Copy/restore the database, uploads, `mags-school-2026` theme, `mags-core` plugin and `mags-security` plugin to staging/production.
3. Use the host's approved migration/search-replace process to replace the local URL with the final HTTPS URL, including serialized WordPress data.
4. Confirm that attachment IDs still resolve. Open Events, Gallery Albums, Featured Images, PDFs and the public Gallery after migration.
5. Save **Settings → Permalinks** once using **Post name** if archive URLs return 404.

Do not copy theme files alone and expect CMS Gallery Albums to move. Album photo selections reference attachment IDs stored in the database, and the actual files live under `wp-content/uploads`.

### B. Install a clean production copy

1. Install WordPress using a new database and unique database user.
2. Copy `mags-school-2026` to `wp-content/themes/`.
3. Copy the bundled `mags-core` and `mags-security` directories to `wp-content/plugins/`.
4. Activate **MAGS Core**, **MAGS Security Baseline** and **MAGS School 2026**.
5. Open **Tools → MAGS Site Setup**, choose **Create / verify site structure**, then set **Settings → Permalinks → Post name**.
6. Import/recreate only approved pages and media. Use the starter XML only when appropriate and review every item before publication.
7. Upload optimized, consent-approved photographs to the Media Library; add alternative text and create/publish the required CMS Gallery Albums.

## 3. Configure MAGS Settings

Open **Settings → MAGS Settings** and enter the approved values:

- School name
- Telephone numbers, one per line
- Official email / enquiry recipient
- Postal address
- Google Maps embed URL
- Facebook, Instagram and YouTube URLs

For Google Maps, choose **Share → Embed a map** and paste only the URL inside the generated iframe's `src="…"` attribute. Do not paste the full iframe and do not use the standard Share link. Save, then verify the header/footer and Contact page at mobile and desktop widths.

The source package must not be treated as the authoritative store for phone numbers, email or map information. These values belong in production WordPress settings and require school approval.

## 4. Contact form and SMTP

The form stores each valid submission privately under **Enquiries** and calls WordPress email delivery. A successful form message does not by itself prove that production email arrived.

1. Configure the host's approved relay or a maintained SMTP plugin using credentials kept outside source control.
2. Set the official recipient in **MAGS Settings**.
3. Submit one authorized test using a monitored address.
4. Verify that one private Enquiry is stored, the email arrives, Reply-To works and no duplicate is sent.
5. Confirm spam handling, enquiry access, retention/deletion policy and privacy wording with the school.
6. Delete/redact test personal data when no longer needed.

## 5. Photography and media release gate

The local working copy uses photographs supplied by the school rather than third-party stock. All 384 supplied JPGs have been converted to max-1800px WebP with WordPress responsive sizes and organized into eight published CMS albums, and the official building photograph is used as the homepage hero. This technical preparation does not automatically establish production publication rights.

Before publishing an Event or Gallery Album:

- Confirm ownership/licence and required guardian/subject consent for identifiable people.
- Remove duplicates, unapproved images and files that reveal private information.
- Correct orientation, crop appropriately and compress to practical web dimensions.
- Upload through the Media Library and add accurate alternative text.
- Review album title, description, order and cover image on phone and desktop.

The Gallery archive and homepage gallery section intentionally display published CMS albums only. Do not restore a hard-coded theme gallery as a substitute for CMS-managed albums.

## 6. SEO, favicon and accessibility release gate

The theme provides native descriptions, canonical URLs, Open Graph/Twitter metadata, School JSON-LD and a favicon fallback. It suppresses its metadata when a supported SEO plugin is responsible for output.

Before release:

- Set/approve the Site Icon and logo and check browser tabs and social-preview images.
- Inspect representative source pages for one description, one canonical URL and one structured-data implementation.
- Verify production URL, school name and available approved contact values in structured data.
- If adding an SEO plugin, configure it on staging and confirm there are no duplicate meta/canonical/schema tags.
- Review final heading order, descriptive links, image alternative text, form labels/messages, contrast, 200% zoom and reduced-motion behaviour.
- Test the mobile menu by keyboard: opening, closing with Escape/outside interaction, visible focus and focus return.

Search Console, sitemap submission and analytics consent/settings are organization-owned production tasks and are not encoded with guessed accounts in this package.

## 7. WordPress production constants

For a standard-hosting installation, set these before the “stop editing” line in `wp-config.php`:

```php
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_ENVIRONMENT_TYPE', 'production' );
```

Keep debug output disabled publicly. Store database, SMTP and service credentials in the host's approved secret/configuration facility, never in theme files or documentation.

## 8. Docker staging

For reproducible local/staging use, copy `.env.example` to `.env`, replace all example passwords with unique random values, then run:

```powershell
docker compose up -d
```

Open `http://127.0.0.1:8080`, complete WordPress installation and follow the clean-install steps above. Database and uploads persist in Docker volumes; theme/plugins are bind-mounted read-only to prevent dashboard source editing.

For a public deployment, place WordPress behind the organization's approved HTTPS reverse proxy or managed host. Do not expose the staging bind address as the final production service. Pin and review WordPress/container patch versions as part of scheduled maintenance.

## 9. Final verification and operations

- **Before release:** take database/uploads backups; record WordPress, PHP, theme and plugin versions; complete `PRE-LAUNCH-CHECKLIST.md`.
- **After migration/update:** verify homepage, navigation, Notice/archive order, PDFs, Download categories, News/Events promotion, Gallery Albums, Contact map/form, Enquiry record and login.
- **Browser/device pass:** current Chrome, Edge, Firefox, Android Chrome and iPhone/iPad Safari at representative mobile/tablet/desktop widths.
- **Performance pass:** measure Core Web Vitals after final photographs, map, analytics and plugins are present; correct oversized images and unnecessary plugins.
- **Daily:** encrypted remote backup of database and uploads; retain according to school policy.
- **Weekly:** review core/theme/plugin security updates and apply them after staging checks.
- **Monthly:** restore a backup to non-public staging and test it.

The final database/uploads backup and evaluator credentials are intentionally environment-specific. Generate and hand them over securely only after production content, domain and permissions are finalized.
