# MAGS mandatory CMS and design compliance matrix

This matrix distinguishes functionality implemented in the local WordPress project from environment/content work that must be completed and approved for the production website.

## Mandatory CMS features

| Requirement | Local implementation | Production acceptance action |
| --- | --- | --- |
| Authorized users manage content without source editing | WordPress roles/capabilities; Pages, News, Media and all MAGS content types are managed in `/wp-admin`. Dashboard theme/plugin editing is disabled by the security baseline. | Create named least-privilege staff accounts and securely issue credentials. |
| Create, edit, publish and delete pages | Native WordPress Pages and status/Trash workflow. | Complete approved school copy and test with the assigned staff role. |
| Rich text editor | Native WordPress block editor is enabled for Pages and public content types. | Train staff using `USER-MANUAL.md`; restrict unapproved blocks/plugins if required. |
| Media management | Native Media Library for images and PDFs. | Import only approved files, complete alternative text and remove obsolete/duplicate media. |
| Publish notices | Public **Notices** content type with block-editor content. | Add approved notices and verify dates/content. |
| Upload notice PDFs | **Attached PDF / document** Media Library picker on Notices. | Upload current, accessible PDFs and test every link. |
| Reverse chronological Notice Board | Public current notices are queried newest first with pagination. | Publish dated test/approved notices and verify order after migration. |
| Archive old notices | Per-notice archive checkbox, public Archived notices view and Current/Archived admin filter. | Agree retention policy and archive an approved sample without deleting it. |
| Publish news articles | Native Posts relabeled **News**, with block editor and Featured Image. | Populate approved News content. |
| Add event photographs | **Events** include an event date and multiple-photograph Media Library picker. | Import optimized, consent-approved images and publish an Event test. |
| Feature news/events on homepage | Homepage promotion checkbox for News and Events; homepage prioritizes promoted items. | Select only current approved promotions and verify card image/excerpt. |
| Manage Circulars, Admission Brochure, Holiday Lists, Fee Structure, Newsletters and Other PDFs | Public **Downloads** type with document picker and all six required Download Types. | Upload approved current documents into the correct categories. |
| Create photo galleries and organize albums | Public **Galleries** album type with title, description, status and archive/single views. Homepage and Gallery archive render published CMS albums only. Eight initial albums are populated locally from the supplied photography. | Review the album organization and publish only consent-approved albums. |
| Upload multiple gallery photographs | Multiple-selection Media Library picker, per-image removal, clear action and first-image cover fallback. The local import prepared 384 photographs as max-1800px WebP images with responsive WordPress sizes. | Complete alternative text, consent review and final order/cover approval. |
| Contact address, telephone, official email and Google Map | Contact template reads all values centrally from **Settings → MAGS Settings**; multiple telephone numbers and Maps embed source URL are supported. | Enter and approve the real telephone numbers, email, address and Google Maps embed `src` URL. |
| Contact form | Nonce, hidden spam trap, rate limiting, input sanitization, private **Enquiries** record and WordPress email attempt. | Configure authenticated SMTP, confirm recipient/privacy/retention policy and complete a real delivery test. |

## Design, SEO and operational expectations

| Requirement | Local implementation | Production acceptance action |
| --- | --- | --- |
| Fully responsive / modern UI | Custom responsive grids, mobile navigation, restrained interaction states and flexible media. | Test final content on representative phone, tablet and desktop devices. |
| Consistent branding | Official MAGS logo assets, reusable colour/spacing/type system and consistent components. | Obtain final school sign-off on logo use, colours and public copy. |
| Intuitive navigation | Primary navigation, grouped menus, breadcrumbs, quick links and CMS archive paths. | Approve the final menu labels/order and test common parent/student journeys. |
| High-quality typography and images | Local typography system without external font dependency; responsive/lazy image rendering; 384 supplied JPGs converted to max-1800px WebP with WordPress responsive sizes; official school-building hero. | Approve the final image selection and remove unused/unapproved media. |
| Major-browser compatibility | Standards-based HTML, CSS and JavaScript with progressive behaviour. | Re-test the final deployed site in current Chrome, Edge, Firefox and Safari. |
| Accessibility and usability | Semantic landmarks, skip link, keyboard-contained splash, keyboard-operable mobile menu, Escape/focus return handling, visible focus styles, WCAG-oriented contrast, reduced-motion support and accessible native blocks. | Review final authored headings, link text, image alternative text, PDF accessibility and keyboard flow. |
| Search and sharing metadata | Native titles, homepage-specific description, canonical URLs, Open Graph/Twitter cards and School JSON-LD; a 512×512 official-diya Site Icon is configured; supported SEO-plugin output is not duplicated. | Inspect production source, submit sitemap/search-console properties if required and approve shared previews. |
| Performance | No external web-font request; lazy image loading; optimized Media Library assets; Data Saver bypasses the splash, parallax and scroll effects. | Measure Core Web Vitals after final photographs, map, analytics and plugins are present. |
| Security and maintenance | Sanitization/escaping, nonces, capability checks, private enquiries, spam trap, rate limiting, XML-RPC setting, security headers, disabled dashboard file editor and documented update/backup process. | Enable HTTPS, strong named accounts/MFA where available, SMTP, off-site backups, monitoring and patching. |
| Documentation | README, staff manual, deployment runbook, launch checklist, compliance matrix and submission notes are included. | Add final environment credentials through a secure channel; never place passwords in these files. |

## Supplied photography status

The local project contains photography supplied by Maharaja Agrasen Global School rather than third-party stock imagery. Its 384 supplied JPGs are prepared as max-1800px WebP Media Library images with responsive sizes and organized into eight published CMS albums; the official building photograph is the homepage hero. The public Gallery implementation remains CMS-managed. This technical preparation is not proof of production consent: before launch, the school must approve the selection, confirm rights/consent for identifiable people, complete useful alternative text and unpublish/remove anything not approved.

## Items that cannot be finalized safely in source code

- Official production telephone numbers, email recipient, final address/map embed URL and social accounts.
- Domain, hosting, DNS, HTTPS certificate, analytics/search-console ownership and SMTP credentials.
- Final Admissions, Fees, Scholarships, Transport, Notices, News and PDF content.
- Photograph rights, guardian/subject consent records and the school's retention/privacy decisions.
- Environment-specific database/uploads backup and evaluator/staff credentials.

The local code can be considered functionally implemented without these values; the public website must not be considered launch-approved until every applicable production action above is complete.
