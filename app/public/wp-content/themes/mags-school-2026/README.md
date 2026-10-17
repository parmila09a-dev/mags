# MAGS School 2026 — WordPress Package

A responsive, original WordPress website prepared for Maharaja Agrasen Global School. The package contains the custom **MAGS School 2026** theme, the **MAGS Core** CMS plugin and the **MAGS Security Baseline** plugin. No premium theme, page builder or premium plugin is required.

Read [PRODUCTION-DEPLOYMENT.md](PRODUCTION-DEPLOYMENT.md) before moving the local site to a public domain, and complete [PRE-LAUNCH-CHECKLIST.md](PRE-LAUNCH-CHECKLIST.md) before launch.

## Implementation status

The local implementation includes the mandatory CMS structure, responsive theme, CMS-only gallery output, contact form, accessibility foundations and native SEO metadata. All 384 supplied JPG photographs have been prepared as max-1800px WebP images with WordPress responsive sizes and organized into eight published CMS albums; the supplied school-building image is used as the homepage hero. The public production launch still depends on school-approved telephone numbers, email, Google Map URL, domain/hosting, SMTP, final PDFs/content and documented permission for identifiable people in photographs.

## What is included

- WordPress-managed Pages, News, navigation, featured images and Media Library.
- Notice Board with PDF/document attachment, reverse-chronological display, Current/Archived filtering and a public archive.
- Downloads with six required document types: Circulars, Admission Brochure, Holiday Lists, Fee Structure, Newsletters and Other Documents.
- Events with event date, homepage promotion and a multiple-photograph Media Library picker.
- CMS-managed Gallery Albums with multiple photographs. The public gallery and homepage gallery section show published albums only; no source-code gallery fallback is required.
- Homepage News & Events promotion controls for selected News and Events.
- A protected enquiry form that stores valid submissions privately under **Enquiries** and attempts email delivery to the configured official recipient.
- **Settings → MAGS Settings** for school name, telephone numbers, official email, address, Google Maps embed source URL and social links.
- Native page descriptions, canonical URLs, Open Graph/Twitter metadata, School structured data and a configured 512×512 official-diya Site Icon, with safeguards to avoid duplicating a supported SEO plugin's output.
- Responsive layouts, semantic landmarks, skip navigation, visible focus treatment, reduced-motion support and an accessible mobile menu.
- School-supplied photography imported locally into eight CMS albums: 384 max-1800px WebP images plus WordPress responsive sizes. Production publication still requires school approval of the selection, alternative text and rights/consent records.
- Deployment, compliance, staff hand-over and launch documentation.

## Install

1. Install a current supported WordPress release on the hosting server and complete its database setup.
2. Copy the `mags-school-2026` theme directory to `wp-content/themes/`.
3. Copy the bundled `mags-core` and `mags-security` directories to `wp-content/plugins/`.
4. Activate **MAGS Core** and **MAGS Security Baseline**, then activate **MAGS School 2026** under **Appearance → Themes**.
5. Open **Tools → MAGS Site Setup** and choose **Create / verify site structure**. The tool creates or verifies the editable page structure and primary menu without deleting staff-authored content.
6. Open **Settings → Permalinks**, choose **Post name**, and save once to register the Notices, Downloads, Events and Gallery URLs.
7. If building a clean site instead of migrating the completed local database, optionally import `site-content/mags-starter-content.xml` through the WordPress Importer. Review every imported page before publication.
8. Enter approved contact and social information under **Settings → MAGS Settings**. For Google Maps, paste only the URL inside the embed iframe's `src="…"` attribute, not the full iframe and not the normal Share link.
9. Configure authenticated SMTP and complete the production checklist. When deploying the completed local site, migrate its database and uploads to preserve the eight prepared Gallery Albums; on a clean installation, import and approve the media/albums separately.

When moving the completed local site, migrate the database and `wp-content/uploads` together. Gallery albums store WordPress attachment IDs, so copying only theme files does not transfer their photographs or CMS content.

## Content editing guide

| Requirement | Where staff manage it |
| --- | --- |
| Pages and leadership messages | **Pages**, using the WordPress block editor |
| Images and documents | **Media → Library** |
| News articles | **News → Add News Article**; use Featured Image and optional homepage promotion |
| Events | **Events**; set Event date, content, multiple photographs and optional homepage promotion |
| Current and archived notices | **Notices**; attach a PDF and use the Notice archive checkbox when it becomes old |
| Circulars, brochures and other downloads | **Downloads**; attach the document and choose a Download Type |
| Gallery albums | **Galleries**; create an album and use **Event / album photographs → Add photographs** |
| Main navigation | **Appearance → Menus** |
| School name, phones, email, address, map and social URLs | **Settings → MAGS Settings** |
| Contact submissions | **Enquiries**; records are private in WordPress |

## Photography and gallery workflow

1. Confirm that the school owns the photograph or has permission to publish it, including guardian/subject consent where required.
2. Crop and compress the image to an appropriate web size before upload.
3. Upload it to the Media Library and add concise alternative text that communicates its purpose.
4. Create or edit a **Gallery Album**, select **Add photographs**, choose multiple images and publish/update the album.
5. The first selected image becomes the card image if no separate Featured Image is set. To change sequence, remove images and select them again in the required order.

The local working site already contains eight albums built from the 384 school-supplied photographs, converted to max-1800px WebP with responsive WordPress sizes. This preparation is not automatic production approval. Review every album, complete alternative text and retain only consent-approved images in the final Media Library and published albums.

## SEO and accessibility publishing workflow

- Write one clear page title and a useful opening paragraph; the native SEO layer derives descriptions from page excerpts/content.
- Use one logical heading hierarchy, descriptive links and alternative text for meaningful images. Leave purely decorative images with empty alternative text.
- Choose a relevant Featured Image for content that may be shared on social media.
- Review the page with keyboard-only navigation and at mobile and desktop widths before publishing.
- If an approved SEO plugin is installed, configure and test it on staging. The theme avoids duplicating common plugin metadata, but final source inspection remains part of launch QA.

## Recommended free optional plugins

The mandatory CMS functions without third-party plugins. Add only maintained, approved plugins when the production environment needs them:

- **WP Mail SMTP** — authenticated contact-enquiry delivery.
- **The SEO Framework** — editorial control beyond the native metadata.
- **UpdraftPlus** — scheduled backups where the host has no equivalent service.
- **Wordfence Security** — additional monitoring if approved by the host.
- **Smush** — optional ongoing Media Library compression.

Verify each plugin in staging, install it from the official WordPress.org directory, and remove anything unused.

## Theme and plugin responsibilities

- `front-page.php` supplies the homepage layout.
- `template-contact.php` supplies the Contact page and map/form presentation.
- Gallery, Notice and Download archive/single templates display CMS content.
- `functions.php`, `header.php`, `style.css` and `assets/js/` provide the theme, navigation, metadata and accessibility behaviour.
- **MAGS Core** owns the custom content types, settings, document/photo pickers, setup tool and enquiry records.
- **MAGS Security Baseline** supplies the documented application security defaults.
