# MAGS production launch checklist

`[x]` records functionality completed in the local project. `[ ]` records a production/content decision or verification that still has to be completed. Re-test completed items after any theme/plugin change or migration.

## Completed local implementation

- [x] WordPress Pages support create, edit, preview, publish, update and Trash workflows with the block editor.
- [x] Media Library supports managed images and PDF documents.
- [x] Notices support document attachment, newest-first public display, Current/Archived filtering and a public archive.
- [x] News and Events support featured images; Events support date and multiple photographs; selected News/Events can be promoted on the homepage.
- [x] Downloads support document attachment and all six mandatory document types.
- [x] Gallery Albums support multiple-photo selection; public Gallery/homepage output is driven by published CMS albums rather than hard-coded gallery entries.
- [x] All 384 supplied JPG photographs are imported into eight published CMS albums as max-1800px WebP images with WordPress responsive sizes.
- [x] The supplied official school-building photograph is used as the homepage hero.
- [x] Contact form validates submissions, stores private Enquiries and attempts email delivery.
- [x] **Settings → MAGS Settings** centralizes school name, phones, email, address, Google Maps embed URL and social links.
- [x] Responsive layout, semantic landmarks, skip navigation, visible focus treatment, reduced-motion handling and accessible mobile-menu behaviour are implemented.
- [x] Native descriptions, canonical/share metadata, School structured data and a 512×512 official-diya Site Icon are implemented with SEO-plugin duplication safeguards.
- [x] Staff manual, compliance matrix, deployment runbook and submission notes describe the current CMS workflow.

## Official information and content — required before public launch

- [ ] Written school approval exists for all public copy, leadership messages, admissions/fees/scholarship details, policies and document dates.
- [ ] Official telephone numbers, enquiry email, postal address, Google Maps embed `src` URL and approved social links are saved in **MAGS Settings**.
- [x] A square 512×512 Site Icon based on the official MAGS diya is configured and returns standard browser icon sizes.
- [ ] The final logo and school name display are approved by the school on desktop and mobile.
- [ ] Current approved News, Notices, Events and Downloads are populated; no demo/placeholder content remains public.
- [ ] Every PDF is current, clearly named, accessible where practical and opens correctly on a phone.
- [ ] Each required Download Type contains the intended documents, including **Other Documents** where applicable.

## Photography and Gallery Albums — required before public launch

- [ ] The school has confirmed ownership/licence and documented required consent for every identifiable student, staff member and guest shown.
- [ ] Supplied source photographs have been reviewed; unapproved, duplicate and poor-quality files are excluded.
- [x] Local album images are compressed to max-1800px WebP and have responsive WordPress sizes.
- [ ] Orientation/crop and visual suitability are approved for every image selected for production.
- [ ] Meaningful photographs have concise, accurate alternative text; decorative images use empty alternative text.
- [ ] Gallery Albums are organized by event/session/subject and contain the intended photographs in the intended order.
- [ ] Album titles, descriptions, cover images, publication status and public single-album pages are reviewed.
- [ ] No source/camera-original or hard-coded fallback gallery is publicly exposed.

## Contact and email — required before public launch

- [ ] Authenticated SMTP or the host's approved transactional email service is configured.
- [ ] A valid public form submission creates one private **Enquiry** and arrives at the configured official recipient.
- [ ] Reply-To behaviour, spam handling, privacy notice and enquiry retention/deletion responsibility are approved.
- [ ] The Google Map loads the correct school location without mixed-content or consent-policy issues.
- [ ] No test enquiries containing personal information remain in the production database.

## SEO, accessibility and usability acceptance

- [ ] Every public page has a unique useful title and opening/excerpt text; final descriptions and canonical URLs are inspected in page source.
- [ ] Open Graph/Twitter preview image, title and description are checked on representative public pages.
- [ ] School JSON-LD contains the approved production URL, name and available contact details; no SEO plugin creates duplicates.
- [ ] Page heading order, landmarks, descriptive links, controls, form labels and error/success messages are reviewed with final content.
- [ ] Primary navigation and mobile menu work by keyboard; Escape closes the menu and visible focus is never lost.
- [ ] Text/background and interactive-state contrast is checked after final branding/content changes.
- [ ] Reduced-motion mode and 200% zoom/reflow are checked on key pages.

## Hosting, browser, performance and operations

- [ ] Production domain/DNS are approved; HTTPS is active with HTTP-to-HTTPS redirect and no browser security warning.
- [ ] WordPress, PHP and database versions are supported and patched.
- [ ] Named Administrator/Editor accounts use strong unique passwords and MFA where available; no shared personal account is handed over.
- [ ] `DISALLOW_FILE_EDIT` remains enabled; unused themes/plugins are removed and XML-RPC policy is confirmed against integrations.
- [ ] Off-site database and uploads backups run automatically, and a restore to staging has been tested.
- [ ] Final deployed pages are tested in current Chrome, Edge, Firefox, Android Chrome and iPhone/iPad Safari.
- [ ] Mobile, tablet and desktop checks cover homepage, menus, Notices/archive, Downloads, News/Event, Gallery Album and Contact form.
- [ ] Core Web Vitals and total page/image weight are measured after final media, map, analytics and plugins are installed; oversized assets are corrected.
- [ ] A staff owner is named for content, contact enquiries, consent records, security updates and backups.
- [ ] Final database plus `wp-content/uploads` migration/backup is stored securely; evaluator credentials are delivered separately through a secure channel.
