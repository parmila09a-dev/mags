# Competition submission and hand-over notes

## CMS and original work declaration

**CMS:** WordPress (open source).  
**Theme:** MAGS School 2026, a custom theme prepared for this project.  
**Custom plugins:** MAGS Core and MAGS Security Baseline.  
**Premium themes/plugins/page builders:** None required.  
**Photography:** The working copy contains 384 photographs supplied by Maharaja Agrasen Global School. They are not third-party stock assets. The local import converted the JPGs to max-1800px WebP with WordPress responsive sizes and organized them into eight published CMS albums. The supplied school-building photograph is the homepage hero. Final public use remains subject to school approval and the required rights/consent records.

The public Gallery and homepage gallery section are driven by published WordPress Gallery Albums. Gallery entries are not maintained in theme source code. Events and Gallery Albums both support multiple Media Library photographs.

## What is implemented locally

- Mandatory Pages, Media, Notices/archive, News, Events, Downloads/categories, Gallery Albums and Enquiries CMS controls.
- Homepage promotion for selected News/Events and CMS-driven gallery content.
- Eight locally populated CMS Gallery Albums containing all 384 optimized supplied photographs, plus the official school-building homepage hero.
- Central **Settings → MAGS Settings** screen for school/contact/map/social information.
- Responsive branded theme, keyboard/mobile navigation and accessibility-focused interaction states.
- Native page/share metadata, canonical URLs, School structured data and favicon fallback.
- Contact enquiry storage and an email attempt through WordPress.

## What must be supplied or configured for the submitted production site

- Approved phone numbers, official email, final address, Google Maps embed source URL and social URLs.
- Production domain, HTTPS, hosting, SMTP/transactional email and backup configuration.
- Final approved Admissions, Fees, Scholarships, Transport, Notices, News and PDF documents.
- Written approval/consent for the locally prepared photography, final selection review and useful alternative text.
- Environment-specific database/uploads export and securely delivered evaluation credentials.

Do not present an empty setting, local test value, source photograph or undelivered `wp_mail()` call as a completed production configuration.

## Administrator hand-over

Create the evaluation account only on the final evaluation installation. Do not place passwords in source control, screenshots or this document. Supply a named, time-limited Administrator account through a secure channel and require a password reset when appropriate. Routine school publishing should use named Editor accounts unless Administrator privileges are genuinely required.

Provide the evaluator with:

- Production/staging URL and secure credentials.
- `USER-MANUAL.md` and `PRE-LAUNCH-CHECKLIST.md`.
- Final database export plus matching `wp-content/uploads` backup.
- A short statement identifying any deliberately unpopulated/production-dependent item.

## Database and media backup

The database, uploads and code are separate parts of a complete WordPress hand-over. Gallery albums store Media Library attachment IDs, so a theme-only copy does not contain the CMS album records or their uploaded images.

After final content and media are approved:

1. Export the WordPress database using the host control panel, phpMyAdmin or an approved WP-CLI process.
2. Back up the complete `wp-content/uploads` directory at the same point in time.
3. Record the production URL and WordPress/PHP/plugin versions.
4. Restore both database and uploads to a non-public staging copy and verify Gallery Albums, featured images, PDFs and contact settings.
5. Store the export securely; it may contain personal enquiry data and must not be committed to a public repository.

## Two-to-three minute demonstration outline

1. Show the homepage and primary navigation at desktop and phone widths, including keyboard focus and the mobile menu.
2. In **Pages**, show the rich-text block editor and Media Library without changing source code.
3. In **Notices**, publish a notice with a PDF, show newest-first public placement, then demonstrate the archive checkbox and Current/Archived filter.
4. In **Downloads**, attach a PDF and select one of the six required Download Types.
5. In **News/Events**, show Featured Image, homepage promotion, Event date and multiple event photographs.
6. In **Galleries**, create an album, select multiple photographs in one operation, publish it and show both the gallery archive and album page.
7. In **MAGS Settings**, point out phone/email/address/map/social fields; then submit one approved test enquiry and show the private Enquiry record plus SMTP delivery.

If the demonstration is strictly limited to three minutes, combine Pages/Media into the opening explanation and show only one document workflow while verbally identifying the parallel Download workflow.

## Screenshots recommended for the final manual/submission

- Pages block editor and Media Library.
- Notices document picker plus archive control.
- Downloads document picker and Download Types.
- Event/Gallery multiple-photo picker.
- Gallery archive and one public album.
- MAGS Settings and the private Enquiries list.
- Responsive homepage/mobile navigation.

Capture screenshots only after demo/private contact values and personal enquiry content have been removed or redacted.
