# MAGS School 2026 — Staff User Manual

This guide is for authorized school staff who manage the website without editing source code. It assumes WordPress, **MAGS Core** and **MAGS Security Baseline** are active and **Tools → MAGS Site Setup** has been run once.

## 1. Sign in and manage pages

Open `https://your-school-domain/wp-admin` and sign in with the account issued by the site administrator. Use your own account; never share an Administrator password.

Choose **Pages → Add New** to create a page, or **Pages → All Pages** to edit, preview, publish, unpublish or move a page to Trash. Text written in the editor of the **Infrastructure** and **Contact Us** pages appears on those pages; a Featured Image on Infrastructure replaces the building photograph. On the Patron’s, Chairman’s and Principal’s message pages, the **Leadership profile** box (name and role) and the page’s **Featured Image** (photograph) are shown on the page and on the homepage leadership card. The WordPress block editor is the rich-text editor: use it for headings, paragraphs, lists, buttons, links, images and other approved blocks. Use **Preview** before **Publish** or **Update**.

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

Use **News → Add News Article** for news. Add the title and article in the block editor, choose a **Featured Image**, and select **Feature this item in the homepage News & Events section** only when it should be promoted. Preview, then publish. The homepage shows three items: ticked items first (newest first), then the latest remaining items.

Use **Events → Add Event** for upcoming or past event content:

1. Add the event title and write-up.
2. Set the official date under **Event date**. It is displayed as, for example, 29 June 2026. Events dated today or later are also listed automatically under **Upcoming events** on the homepage and the News & Events page, soonest first.
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

To choose the albums shown in the homepage **From our galleries** section, tick **Show this album in the homepage “From our galleries” section** in the album’s **Homepage gallery** box. Up to three ticked albums are shown, newest first; when no album is ticked the three newest albums are shown.

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

## 10. Student & Staff Portal

The portal at `/portal/` is separate from the website editing described above. Its setup and rules are in `app/public/wp-content/plugins/mags-erp/README.md`. In short:

- **Office** adds classes, teachers and students (one by one or from a CSV file) and gives teachers their classes. It chooses the class teacher of each class, makes the subjects and the timetable, makes exams and publishes results, writes down fees and payments, reads all questions and opens the reports of attendance, marks and fees. New login details are shown once; note them before leaving the page.
- **Teachers** mark attendance, give homework (with files, a due date and maximum marks) and can change it later, check answers with marks or a comment, share notes, send notices to their classes, enter exam marks, reply to students' questions and see their own timetable. The class teacher answers the leave requests of the class.
- **Students and parents** sign in with the admission number and see only the student's own data: attendance, homework and its marks, class notes and notices, the timetable, exam marks with a report card to download, questions to teachers, leave requests, and fees paid and left to pay. Fees are paid at the school office; nothing is paid online.
- **School calendar**: the office adds holidays and events under More → School calendar; teachers and students see them month by month. On a holiday teachers cannot mark attendance and the day is not counted in the attendance percentage.
- **Student profile**: under Students → Profile the office keeps each student's photo, parents' names, date of birth, blood group, emergency number, address and scanned documents, downloads the ID card (PDF, with the photo) and makes bonafide, character and transfer certificates (PDF). Students see their own profile but cannot change it.
- **Fees**: the office can give a discount on a fee (scholarship, brother / sister), add a late fine after the last date, and download a receipt (PDF) for every payment.
- **Admission form**: parents fill it at `/portal/admission/` without a login once the office has opened it under More → Admissions. The office reads each form and accepts it, which makes the student with a login and a filled profile. Close the form when admissions are over.
- **Teacher attendance and leave**: the office marks teacher attendance under More → Teacher attendance; a teacher asks for leave under More → My attendance and leave and the office answers under More → Teacher leave.
- **Remove does not delete.** Removed classes, teachers, students, homework, notes and notices are hidden and kept, and can be restored from the “Removed” list on the same screen. Questions and replies cannot be removed by anyone.
- Everyone sees only their own classes or their own child's data. Portal accounts cannot open the WordPress Dashboard.
