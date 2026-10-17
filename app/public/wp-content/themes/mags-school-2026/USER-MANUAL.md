# MAGS School 2026 — Staff User Manual

This guide is for authorized school staff who manage the website without editing source code. It assumes WordPress, **MAGS Core** and **MAGS Security Baseline** are active and **Tools → MAGS Site Setup** has been run once.

## 1. Sign in and manage pages

Open `https://your-school-domain/wp-admin` and sign in with the account issued by the site administrator. Use your own account; never share an Administrator password.

Choose **Pages → Add New** to create a page, or **Pages → All Pages** to edit, preview, publish, unpublish or move a page to Trash. The WordPress block editor is the rich-text editor: use it for headings, paragraphs, lists, buttons, links, images and other approved blocks. Use **Preview** before **Publish** or **Update**.

Manage uploaded images and documents under **Media → Library**. Give files clear names, add alternative text to meaningful images and remove obsolete duplicates only after checking that no page, event or gallery still uses them.

## 2. Publish and archive notices

Use **Notices → Add Notice** for parent circulars, urgent updates and other notices. Published current notices appear newest first automatically.

1. Enter a clear title, such as “Holiday List 2026–27”.
2. Add a short explanation in the block editor where useful.
3. In **Attached PDF / document**, choose **Choose from Media Library**, then upload or select the approved final PDF.
4. Preview and publish the notice.

When a notice is no longer current, edit it, select **Move this notice to the public archive** under **Notice archive**, and update it. The notice remains publicly available under **Archived notices** but is removed from the current Notice Board. Use the Current/Archived filter on the Notices admin screen to find it later. Do not delete an old notice when it should remain in the public record.

## 3. Publish downloads

Use **Downloads → Add Download** for reusable documents. Add a title and optional explanation, attach the PDF under **Attached PDF / document**, then select the correct **Download Type**:

- Circulars
- Admission Brochure
- Holiday Lists
- Fee Structure
- Newsletters
- Other Documents

Preview the document link on a phone before publishing. Replace a file only with an approved current version and use a clear filename that includes the year/session where appropriate.

## 4. Publish News and Events

Use **News → Add News Article** for news. Add the title and article in the block editor, choose a **Featured Image**, and select **Feature this item in the homepage News & Events section** only when it should be promoted. Preview, then publish.

Use **Events → Add Event** for upcoming or past event content:

1. Add the event title and write-up.
2. Set the official date under **Event date**.
3. Under **Event / album photographs**, choose **Add photographs** and select multiple Media Library images in one operation.
4. Optionally select homepage promotion.
5. Preview and publish.

The first selected photograph becomes the card image when no separate Featured Image is set. New selections are appended without replacing existing photographs. Drag the numbered thumbnails or use their arrow buttons to change the sequence, and remove an individual photograph with its × button. **Clear album** removes the current selection after the Event is updated; it does not delete the original files from the Media Library.

## 5. Create and organize Gallery Albums

The public Gallery is completely CMS-managed. Only published **Gallery Albums** appear; photographs are not maintained in theme source code.

1. Open **Galleries → Add Gallery Album**.
2. Enter an album title and a short description or event context.
3. Under **Event / album photographs**, choose **Add photographs** and select multiple images.
4. Review the numbered preview thumbnails, remove any wrong image, and drag thumbnails or use the arrow buttons to arrange them.
5. Add a separate Featured Image only if the album card should use a different cover; otherwise the first photograph is used.
6. Preview and publish the album.

Use one album per event, session or clear subject so visitors can understand the organization. Unpublishing or moving an album to Trash removes it from public display without requiring a code change.

The local project starts with eight published albums containing all 384 school-supplied photographs. The import converted each JPG to a max-1800px WebP image and generated normal WordPress responsive sizes; the official school-building photograph is also the homepage hero. This technical preparation is not publication permission. Before keeping any image public, confirm ownership and the required consent for identifiable students, staff and guests, review orientation/crop, and complete accurate alternative text in the Media Library. Unpublish or remove anything the school does not approve.

## 6. Update contact details, map and social links

Open **Settings → MAGS Settings**. Authorized administrators can maintain:

- School name
- Telephone numbers, one number per line
- Official email / enquiry recipient
- Postal address
- Google Maps embed URL
- Facebook, Instagram and YouTube URLs

For the map, open the school location in Google Maps, choose **Share → Embed a map**, copy the HTML, and paste only the URL inside the iframe's `src="…"` attribute into **Google Maps embed URL**. Do not paste the complete iframe and do not use the normal Share link.

Save the settings and check the Contact page. Every valid contact-form submission is stored privately under **Enquiries**. WordPress also attempts to send it to the configured official email; reliable production delivery requires authenticated SMTP configured by the site administrator.

## 7. Update the homepage and leadership cards

The homepage uses a CMS-managed editorial hero. Open **Galleries → Homepage Hero Photos** to manage it; this is a private system album and does not appear in the public Gallery.

- In **Event / album photographs**, add the full-width background photographs. Drag the thumbnails, or use the arrow buttons, to set their playback order. Use 3–4 carefully selected landscape photographs where possible; the technical maximum is 5.
- In **Fixed hero content**, edit the school heading, motto, introduction and the two buttons. This content remains fixed while photographs change.
- Under each numbered slide, enable or disable its **Contextual achievement badge** and edit the short badge title and detail.
- Update the album to publish the changes. The first photograph loads immediately; remaining photographs load efficiently in the background.

Use clear, high-resolution landscape photographs with the main subject away from the left-side text area. The carousel pauses for keyboard interaction and respects reduced-motion and data-saving preferences.

To update a leadership card, edit **Patron’s Message**, **Chairman’s Message** or **Principal’s Message** under Pages. Use the **Leadership profile** panel for the person’s name and role, the page **Featured Image** for the portrait, the **Excerpt** for the card summary, and the block editor for the full message. Updating the page updates the homepage card automatically.

## 8. Update navigation

Open **Appearance → Menus**, edit the menu assigned to **Primary navigation**, and save it. Keep labels short and group related pages under About, Academics, Campus Life and Admissions. After a change, test the menu with a mouse, keyboard and phone-size screen.

## 9. SEO, accessibility and quality checks

Before publishing or updating any public content:

- Use a specific page title and a useful opening paragraph; these support search and sharing descriptions.
- Keep headings in logical order. Do not choose headings only because they look larger.
- Use descriptive link text instead of “click here”.
- Add concise alternative text to meaningful images; leave decorative images empty.
- Choose a relevant Featured Image for News or Events promoted on the homepage or social media.
- Compress photographs to a practical web size before upload and avoid camera-original files.
- Check text, links, buttons and document downloads at mobile and desktop widths.
- Navigate the page with the keyboard and confirm that focus remains visible.
- Check the public Notice Board, Download category or Gallery Album after publishing.
- Send one test enquiry after any SMTP, email, domain or hosting change.

The theme supplies native descriptions, canonical/share metadata and structured data. If the school later installs an approved SEO plugin, an administrator should configure it on staging and check the final page source for duplicate metadata.

For backups, user accounts, plugins, SMTP, domain settings or code changes, contact the site administrator.
