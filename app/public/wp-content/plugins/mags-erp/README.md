# MAGS ERP Portal

School portal inside the same WordPress site, at `/portal/`. It currently has four parts:

1. **Logins** for students, teachers and the office. Parents use their child's student login.
2. **Attendance**: teachers mark it, students see their own days and percentage.
3. **Homework**: a teacher gives homework to a class (with an optional file and due date), students upload their answer, the teacher checks it and gives marks or a comment.
4. **Notes**: a teacher uploads notes for a class and students download them.

More modules are added later on the same base.

## Switch it on

1. Open **Plugins** in the WordPress Dashboard and activate **MAGS ERP Portal**.
2. Open `https://your-school-domain/portal/`. If it shows "page not found", open **Settings → Permalinks** and press **Save Changes** once.
3. Set the school's time zone under **Settings → General → Timezone** (Kolkata). "Today" in the portal follows this setting.

The website footer shows a **Student & Staff Portal** link while the plugin is active.

## Who can do what

| Account | Signs in with | Can see and do |
| --- | --- | --- |
| Office | Username | Add, edit and remove classes, teachers and students. Give teachers their classes. Create new passwords. |
| Teacher | Username | Only their own classes. Mark or correct attendance; give, check and remove homework; share and remove notes. |
| Student / Parent | Admission number | Only that student's own data: attendance, homework of their class with their own answer, marks and comments, and their class's notes. |

Every account can change its own password under **Account**.

### Office accounts

A website Administrator can open the portal directly and works there as the office. To give office staff their own login, create a user under **Users → Add New** with the role **MAGS Office**.

### Teachers and students

The office adds them in the portal. After adding (or after **New password**), the portal shows the login ID and password **once**; note or copy it before leaving the page. A forgotten password is replaced by the office with **New password**.

Removing a student deletes their login and all their attendance. Removing a teacher keeps the attendance they marked.

## Homework and notes

- A homework belongs to a class. Every teacher of that class sees it and can check answers; students of the class see it.
- A student sends one answer per homework (a photo or a document) and may replace it until the teacher checks it. After checking it is locked.
- Marks can be whole or decimal (7.5). When the teacher sets maximum marks, marks above it are refused. A comment without marks is allowed.
- Removing a homework removes all answers sent for it. Removing a student removes their answers. Removing a teacher keeps their homework and notes.
- Files: PDF, Word, PowerPoint, Excel, JPG, PNG, WebP, TXT or ZIP, up to 10 MB (or the server's upload limit if lower).

### Where the files are

Uploaded files are **not** in the Media Library. They are stored in `wp-content/uploads/mags-erp-private/` under random names without a real extension, and are handed out only through `/portal/file/?id=…` after the portal has checked that the signed-in person belongs to that class (or owns that answer).

The folder contains an `.htaccess` that blocks direct access on Apache. On **nginx** (which Local uses) add this to the site's server block so the folder can never be served directly:

```nginx
location ~* /wp-content/uploads/mags-erp-private/ { deny all; return 404; }
```

## Rules built in

- Attendance is Present (P), Absent (A) or Leave (L), one mark per student per day. Saving again corrects the earlier mark.
- Percentage = days present ÷ days marked, inside the academic session (1 April to 31 March). Leave counts as not present.
- Teachers cannot open or save a class that the office has not given them, and cannot mark a future date.
- Portal accounts (MAGS Student, MAGS Teacher, MAGS Office) cannot open the WordPress Dashboard or edit the website.
- After 5 wrong passwords for one login ID, that ID is locked on that connection for 15 minutes.
- Portal pages are never cached and are hidden from search engines.

## For developers

- `mags-erp.php` loads the files in `includes/`; screens are in `views/`; styles and script in `assets/`.
- Tables (created on activation, kept on deactivation): `{prefix}mags_erp_classes`, `_class_teachers`, `_students`, `_attendance`, `_files`, `_homework`, `_submissions`, `_notes`.
- `includes/files.php` owns upload validation, private storage and the download route; `includes/classwork.php` owns homework, submissions and notes.
- Teachers, students and office staff are WordPress users with the roles `mags_teacher`, `mags_student`, `mags_office`. A student's WordPress username is internal (`stu-…`); the admission number is what they type.
- Routes and the role allowed on each are listed in `mags_erp_screens()` (`includes/router.php`); form actions and their roles in `mags_erp_actions()` (`includes/actions.php`). A new module adds a screen, a view and its actions there.
- Form field and URL parameter names must not be WordPress query variables (`name`, `error`, `page`, `s`, …).
- The session start month can be changed with the `mags_erp_session_start_month` filter.
