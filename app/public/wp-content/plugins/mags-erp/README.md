# MAGS ERP Portal

School portal inside the same WordPress site, at `/portal/`. It has these parts:

1. **Logins** for students, teachers and the office. Parents use their child's student login.
2. **Attendance**: teachers mark it, students see their own days and percentage.
3. **Homework**: a teacher gives homework to a class (with files and a due date) and can change it later, students upload their answer, the teacher checks it and gives marks or a comment.
4. **Notes**: a teacher uploads notes for a class and students download them.
5. **Subjects and timetable**: the office makes the timetable of each class; students see the timetable of their class and teachers see their own periods.
6. **Exams and marks**: the office makes an exam, teachers enter the marks, the office publishes the results, students see their marks, a graph of how they change from exam to exam, and download a report card. The office shares the date sheet and students download an admit card, which the office can hold when fees are not paid.
7. **Class notices**: a teacher sends a notice to one of their classes.
8. **Questions**: a student asks a teacher, the teacher replies. The office can read everything. Nobody can change or delete a question or a reply.
9. **Leave**: a student asks for leave, the class teacher approves it or not.
10. **Parent-teacher meetings**: a teacher makes time slots and parents book one; one slot is never booked by two parents.
11. **Fees**: the office writes down fees and payments; a student sees how much is paid and how much is left. Nothing is paid online.
12. **School calendar**: the office adds holidays and events; teachers and students see them month by month. On a holiday attendance cannot be marked and the day is not counted in the percentage. Every Sunday is a holiday.
13. **Student profile and ID card**: photo, parents, date of birth, blood group, emergency number, address and scanned documents; an ID card PDF with the photo.
14. **Certificates**: the office makes a bonafide, character or transfer certificate (TC) and downloads it as a PDF.
15. **Fee receipts, discounts and late fines**: a receipt PDF for every payment, which the student and parents can also download; a discount (scholarship, brother / sister…) on a fee; a fine when a fee is paid late.
16. **Admission form**: parents fill it on the website without a login and later check its status with the application number; the office reads it and accepts it, which makes the student.
17. **Settings**: the office sets how many wrong passwords lock a login and for how long, the largest file that can be uploaded, and which emails parents get (child absent, fees overdue, results out).
18. **Teacher attendance, leave and substitutes**: the office marks the teachers’ attendance; a teacher asks for leave and the office approves it or not; the office gives the periods or class of a teacher who is away to another teacher for that day.
19. **Office tools**: a “Today” page (children absent, fees received today, fees pending); add many students from a CSV file; reports of attendance, marks and fees, each with a CSV file for Excel; move every class up for the new session in one go; download all portal data as a backup.

## Switch it on

1. Open **Plugins** in the WordPress Dashboard and activate **MAGS ERP Portal**.
2. Open `https://your-school-domain/portal/`. If it shows "page not found", open **Settings → Permalinks** and press **Save Changes** once.
3. Set the school's time zone under **Settings → General → Timezone** (Kolkata). "Today" in the portal follows this setting.

The website footer shows a **Student & Staff Portal** link while the plugin is active.

After the plugin files are updated, the portal adds its new tables by itself the next time any page is opened. Nothing has to be re-activated and no existing record is changed.

## Who can do what

| Account | Signs in with | Can see and do |
| --- | --- | --- |
| Office | Username | Classes, teachers and students (also from a CSV file). The class teacher of each class. Subjects, periods and the timetable. Exams, papers and publishing results. Fees and payments. Fee discounts, late fines and receipts. Student profiles, photos, documents, ID cards and certificates. Admission applications. Teacher attendance and teacher leave. Holidays and events of the school calendar. Reads all questions. Reports. Creates new passwords. Settings: the sign-in lock and the largest file. |
| Teacher | Username | Only their own classes. Mark or correct attendance; give, change, check and remove homework; share and remove notes; send and remove notices; enter exam marks; reply to questions asked to them; see their own timetable and the school calendar; see their own attendance and ask the office for leave. The class teacher also answers the leave requests of the class. Teachers do not see student profiles, documents or fees. |
| Student / Parent | Admission number | Only that student's own data: attendance, homework of their class with their own answer, marks and comments, class notes and notices, the class timetable, own exam marks and report card, own questions, own leave requests, own fees (with discounts and fines) and the receipts of own payments, own profile and photo (read only, without the documents), and the school calendar. |

Every account can change its own password under **Account**.

On a phone the bar at the bottom shows the four screens used most and **More**, which lists every section.

### Office accounts

A website Administrator can open the portal directly and works there as the office. To give office staff their own login, create a user under **Users → Add New** with the role **MAGS Office**.

### Teachers and students

The office adds them in the portal. After adding (or after **New password**), the portal shows the login ID and password **once**; note or copy it before leaving the page. A forgotten password is replaced by the office with **New password**.

### Add students from a file

**More → Add students from a file**. Make a spreadsheet with the columns `Admission No`, `Name`, `Class`, `Section`, `Roll No` and, if you have it, `Parent Email` (the screen has a sample file), save it as CSV and upload it. The parent email goes into the student’s profile, for the emails to parents.

- The whole file is checked first. If one line is wrong, nobody is added and the screen lists the lines to correct.
- The class must already exist. `Class 5`, `class 5`, `5` and `Std 5` are read as the same class.
- Students whose admission number is already in the portal are skipped, so the same file can be uploaded again.
- After the upload the login ID and password of every new student are shown once, with **Download login list**, **Copy all** and **Print**.
- Up to 500 students and 1 MB in one file.

## Nothing is deleted

**Remove** in the portal hides a record; it does not delete it. This is true for classes, teachers, students, homework and its files, notes, notices, subjects, periods, exams and papers, the holidays and events of the calendar, student documents and admission applications. Fees, late fines, discounts, payments and certificates are **cancelled** in the same way. Every list has a **Removed** drawer (students: **Removed students**) with a **Restore** button.

- **Student removed**: the login is switched off and the student leaves the class lists. Attendance, homework answers, marks, fees, questions and leave requests are kept. The admission number stays taken. Restore puts the student back with the same password.
- **Teacher removed**: the login is switched off and their classes are freed. Their account, name, attendance, homework, notes, notices, marks and replies are kept. Homework belongs to the class, so the other teachers of the class go on checking and changing it. If a class is left with no teacher, the office home screen says so (with the number of answers waiting) until the class is given to another teacher. Restore gives the teacher the same password and the classes they had.
- **Class removed**: only possible when no student is in it. Its homework, notes, timetable and marks return when it is restored. If a teacher of its timetable was given the same period in another class meanwhile, that period comes back without the teacher and the portal says so.
- **Files**: a file taken off a homework, the file of removed notes and a photo taken off a profile stay on the server but are no longer handed out. A file that a new upload replaces (an answer sent again, a new photo) is deleted from the server.
- A question or a reply cannot be removed by anyone, not even the office.
- If an account is deleted from the WordPress **Users** screen, the portal still keeps its records: a student moves to **Removed students** (restoring makes a new login), a teacher's classes are freed and the name stays on their work.

## Homework and notes

- A homework belongs to a class. Every teacher of that class sees it and can check answers, change it and add files; students of the class see it.
- A homework can carry up to 10 files. **Change homework or add files** edits the title, instructions, due date and maximum marks and adds files; **Take off** removes one file. The class cannot be changed. The maximum marks cannot go below marks already given. Students see when a homework was changed.
- A student sends one answer per homework (a photo or a document) and may replace it until the teacher checks it. After checking it is locked. Every answer sent is an upload, so a student can send at most 30 files in one day; the file of a replaced answer is deleted.
- Marks can be whole or decimal (7.5). When the teacher sets maximum marks, marks above it are refused. A comment without marks is allowed.
- Files: PDF, Word, PowerPoint, Excel, JPG, PNG, WebP, TXT or ZIP, up to the size set under **More → Settings** (10 MB unless changed, and never more than the server's upload limit).

### Where the files are

Uploaded files are **not** in the Media Library. They are stored in `wp-content/uploads/mags-erp-private/` under random names without a real extension, and are handed out only through `/portal/file/?id=…` after the portal has checked that the signed-in person belongs to that class (or owns that answer).

The folder contains an `.htaccess` that blocks direct access on Apache. **nginx (which Local uses) does not read `.htaccess`**, so without the rule below anyone who has the address of a file can download it. Add this inside the site's `server { … }` block (the same rule is in `docs/nginx-mags-erp-private.conf` and on **More → Settings**, with this site's upload path):

```nginx
location ^~ /wp-content/uploads/mags-erp-private/ {
	deny all;
}
```

- `^~` makes this rule win over every other `location` (also the ones for images and PHP files), wherever it stands in the block.
- **Local**: open the site's folder, then `conf/nginx/site.conf.hbs`, paste the rule inside `server { … }` above the first `location` line, save, and restart the site in Local. (`conf/` is not in this repository; each computer adds the rule once.)
- **Hosting with nginx**: the hosting company or server administrator adds it to the site's server block and reloads nginx.
- The portal tests this itself: it puts a small file in the folder and asks the website for it. While the file can be downloaded, the office home screen says so and **More → Settings → Private files** shows **Open to direct downloads**; after the rule is added, **Check again** shows **Closed**. The answer is kept for 12 hours.

Removed files stay (they can be restored with their record), so this folder grows. Include it in the site backup.

## Timetable

1. **More → Subjects and periods**: add the subjects, then the periods of the school day (name, start, end; tick "break" for lunch). Periods are the same for every class and may not overlap.
2. **More → Timetable**: choose a class and a day, pick the subject and the teacher of each period, and save that day.

- Only teachers who have the class (Teachers → Edit) can be chosen.
- A teacher cannot be in two classes in the same period; the portal refuses the day and names the clash.
- When a class is taken away from a teacher, or a teacher is removed, they are cleared from the timetable of that class; the subject stays.
- A subject that is used in a timetable cannot be removed until those periods are changed.
- School days are Monday to Saturday (`mags_erp_school_days` filter).

## Exams, marks and report cards

1. **More → Exams → Add an exam**, then add its **papers**: a subject, the maximum marks and the classes that write it. Adding the same paper again changes its maximum marks, date or time.
2. Teachers open **More → Exam marks** and type the marks of each of their classes, or tick **Absent**. They can save and come back. A wrong value (above the maximum, not a number) is refused for that student only; the other rows are saved.
3. The office presses **Publish results**. From then on students see their marks and the marks are locked for teachers. **Open for marks again** hides the results and unlocks them for a correction.

- Any teacher of the class can enter its marks, the same rule as for checking homework.
- Total and percentage count the papers that have marks. Absent counts as 0. A paper without marks shows a dash and is not counted.
- **Download report card** gives a PDF with the marks, total, percentage and the attendance of the session. The office can download the report card of any student from **Reports → Marks**.
- The PDF is written by the portal itself (no library to install). English is written in Helvetica; Hindi (Devanagari) names and subjects and the rupee sign ₹ in Noto Sans Devanagari, which ships with the plugin. Only a text in another script (Tamil or Urdu, for example) makes the report card open as a print page instead; **Print or save as PDF** keeps a copy.

### Date sheet and admit card

- Give each paper a date and a start and end time (**Add papers**; adding a paper again changes them). The papers of a class in date order are its **date sheet**.
- In the exam, under **Date sheet and admit cards**: tick **Show the date sheet to students and parents**, choose who gets an admit card, and, if you wish, type the instructions printed under the papers. Who gets one: **Every student**, **Only students with no overdue fee** (a fee whose last date has passed and is not fully paid) or **Only students who have paid all fees**.
- Students and parents open **More → Exams**: the date sheet, **Download admit card** and **Date sheet (PDF)**. A student whose card is held sees why (for example “fees of ₹7,449 are overdue”) and can download it as soon as the fee is paid.
- **Admit cards** next to each class lists every student with the reason a card is held. **Hold** keeps one card back, **Allow anyway** lets one through whatever the fees, **Follow the rule** undoes either. **Print** gives any single card, held or not, so the office can still hand one out. **Admit cards of the class (PDF)** has every card that is not held, two to an A4 page with the photo, roll number, parent’s name and the papers, with cutting lines.
- Teachers download the date sheets of their classes from **More → Exam marks**. When the results are published, the date sheet no longer shows under More → Exams.

### Marks graph

- **More → My marks** starts with a line graph of the percentage in every published exam, oldest first, and a small graph for each subject with “▲ Up 8 points”, “▼ Down 5 points” or “About the same” (a change under 2 points) since the exam before. **See the numbers** shows the same as a table.
- A paper the child missed (AB) is not drawn, so one absence does not look like a fall. The graph shows a trend once there are results of two exams.
- The office sees the same graph on the student’s profile. It is drawn by the portal itself; nothing is sent to another website.

## Notices and questions

- A teacher sends a notice to one of their own classes; every student of that class sees it. Any teacher of the class can remove (hide) it.
- A student asks a question to one teacher of their class. Only that student, that teacher and the office can read it. Both can write again in the same thread.
- The office reads all questions under **More → Questions** and cannot reply. Nobody can change or delete a question or a reply; the portal has no such action.
- A student can ask 10 questions and write 30 follow-up messages in one day.
- If the teacher who was asked is removed or no longer has that class, the student is told to ask another teacher and the office home screen shows that a question is waiting.

## Leave

- A student asks for leave from a first day to a last day with a reason. Leave can start up to 7 days in the past and one request can be up to 30 days. Two requests cannot cover the same day.
- The **class teacher** of the student's class answers. The office chooses the class teacher under **Classes → Edit**; it must be a teacher who has that class. Until a class has a class teacher its requests wait, and the office home screen says so.
- An answer cannot be changed. A student can withdraw a request while it is still waiting.
- Approved leave is offered as **Leave** on the attendance sheet of those days. The teacher still saves the attendance and can change it.

## Parent-teacher meetings

- **Teachers: More → Parent-teacher meetings.** Choose the day, the time from and to, the minutes for each parent and the class (or all your classes), and an optional place. 10:00 to 12:00 with 10 minutes makes 12 slots. Times you already have a slot are left out; at most 60 slots at a time, up to 90 days ahead, not on a holiday of the school calendar (Sundays are allowed).
- The list shows every coming slot with the child, class and parent’s phone of the booking. **Remove** takes a slot away; when it was booked, the parent sees “Cancelled by the teacher”.
- **Parents: More → Parent-teacher meeting** (student login). Every teacher of the child’s class who has offered slots is listed with the free times. Press a time to book it. A child can have one slot per teacher per day. **Give back** frees the slot until the meeting starts.
- **One slot, one parent.** The portal takes a slot only while it is still free, in one step in the database. When two parents press the same time together, one gets it and the other is told “another parent has just taken this slot” and picks another time.
- The teacher’s home screen lists the meetings of the day.

## School calendar

**More → School calendar**. The office chooses **Holiday** or **Event**, a name, the date (and a last day for more than one day) and optional details. Teachers and students open the same calendar from **More** and turn it month by month; they cannot change it.

- **Holiday**: there is no school. A teacher cannot open the attendance sheet or save attendance for that day; the screen shows the name of the holiday instead.
- A holiday is **not counted** anywhere attendance is counted: the student's percentage and month view, the teacher's class cards, the office reports and their CSV files, and the attendance line of the report card.
- If attendance was already saved on a day that later becomes a holiday, the marks are kept but not counted, and the office is told when adding the holiday. If the holiday is removed or moved, those marks count again.
- **Event**: a school day with something on (sports day, parent meeting). It is only shown in the calendar; attendance is marked as usual.
- The student's and teacher's attendance calendars and the school calendar show holidays as **H**.
- One entry can be 92 days at most, so a wrong year cannot close the school for years. A longer break is added in two parts.
- Removing an entry hides it; it can be restored from **Removed holidays and events**.
- **Every Sunday is a holiday**, without an entry in the calendar: teachers cannot mark students and the office cannot mark teachers on a Sunday, and attendance saved on a Sunday before is kept but not counted. The weekly holidays are the days that are not school days of the timetable (`mags_erp_school_days` filter, Monday to Saturday).

## Fees

- **Fees → Add a fee for a whole class** gives every student of the class one fee (name, amount, last date). For one student, open the student and add the fee there.
- **Record a payment** on the student's fee screen: amount, date (not in the future), how it was paid and an optional reference. Each payment gets a receipt number (`R-00001`).
- A student sees fees, paid, and left to pay. Payments are counted against the oldest fee first; a fee is shown as Paid, Part paid, To pay or Overdue.
- A wrong fee or payment is **cancelled**: it stays in the office list, struck out, and is no longer counted. **Count again** brings it back. A fee added for a whole class can be cancelled for the whole class in one step; counting it again for the whole class brings back only the fees cancelled in that step, not one that was cancelled for a single student before.
- There is no online payment. The portal only records what the office enters.

### Discounts, late fines and receipts

All three are on the student's fee screen (**Fees → choose the student**).

- **Give a discount** on a fee: choose what it is for (Scholarship, Brother / sister, Staff child, Other), type the amount in rupees (`500`) or as a part of the fee (`10%`), and an optional note. A fee can have several discounts; together they cannot be more than the fee. Discounts, late fines and cancelling are saved one at a time, so when two people give a discount on the same fee at the same moment, the second is checked against the first and refused if together they would be more than the fee. The student sees the discount under the fee.
- **Add late fine**: offered on a fee once its last date has passed. The office types the amount; the portal never adds a fine by itself. The fine is one more amount to pay and is shown as “Late fine”.
- “Fees” in every total means fees less discounts plus late fines. Payments are counted against the oldest fee first, after its discounts.
- A discount or a fine can be cancelled and counted again like a fee. Cancelling a fee also cancels its discounts and fines; counting the fee again brings back the ones cancelled with it.
- **Receipt PDF**: every payment that is not cancelled has a receipt with the school's name and address, receipt number, student, amount in figures and words, how it was paid, and the fee account (fees, discount, fine, paid, left). One A4 page holds a parent copy and an office copy. Amounts are printed with the ₹ sign and Hindi names in Devanagari. The office downloads any receipt; the student (and parents) download the receipts of their own payments under **More → My fees**. The school's address and phone come from **MAGS Core** settings.

## Student profile, documents and ID card

**Students → Profile** (or **More → Student profiles** to search).

- **Details**: father's and mother's name, date of birth, boy or girl, blood group, date of admission, parent's phone, emergency number (and whose number it is) and address. Phone numbers must have 10 to 15 digits.
- **Photo**: JPG or PNG up to 10 MB and 25 megapixels. The portal turns it upright, cuts it to the shape of the ID card (4:5) and saves it again as a 480 × 600 JPEG, which also drops the place and camera data hidden in phone pictures. A photo replaced by a new one is deleted from the server; a photo taken off with **Remove photo** is kept, hidden.
- **Documents**: scans such as the birth certificate, each with a name; up to 20 per student. Removing hides; **Removed documents** restores.
- **Who sees it**: the office sees and changes everything. The student (and parents) see their own details and photo under **More → My profile** and cannot change them; they do not see the documents. Teachers do not see profiles.
- **ID card**: **Download ID card (PDF)** on the profile, or **ID cards of this class** on the Students screen. An A4 page holds the front and back of up to three cards at their real size (54 × 85.6 mm) with cutting lines: print at 100%, cut and laminate. Names, parents and addresses in Hindi are printed in Devanagari. A student without a photo gets an empty box. A removed student has no ID card.
- Photos need the GD or Imagick extension of PHP (WordPress normally has one). Without it the photo field is not shown.

## Certificates

Made from the student's profile, under **Make a certificate**; **More → Certificates** is the register of all certificates made.

- **Bonafide certificate**: for a student who is in the school now. Optional purpose (“for the purpose of …”).
- **Character certificate**: conduct (Excellent, Very good, Good, Satisfactory) and optional remarks. Says “was a student” for a removed student.
- **Transfer certificate (TC)**: date of leaving, reason, whether qualified for promotion, conduct, nationality and remarks. The portal fills in the parents, date of birth (also in words), admission number and date, class, days marked and present in the session, and whether fees are left to pay.
- The profile must have the father's or mother's name; bonafide and TC also need the date of birth.
- Each certificate gets a serial number that counts up inside its kind and session, such as `TC/2026-27/001`.
- A certificate keeps a copy of what was printed. Downloading it again later gives the same paper, even when the profile has changed since.
- A wrong certificate is **cancelled**: it stays in the register, can no longer be downloaded, and its number is not used again.
- **Making a TC marks the student as left**: they leave the class lists, their login is switched off, and the date of leaving is kept. They are listed under **Students → Removed students** as “left school” with the date, and their profile says **Left school on …**. Attendance, marks, fees and documents are kept. Cancelling a wrong TC does not bring the student back by itself; restore them under **Removed students** if they did not leave.

## Admission form

The form is at `/portal/admission/` and needs no login. It is **closed until the office opens it** under **More → Admissions**; while it is open the website footer and the sign-in page link to it. The office can add one line that is shown above the form.

- The parent fills the child's name, date of birth, class wanted, parents' names, phone, address and, if they wish, a photo and the birth certificate (PDF, JPG or PNG up to 5 MB, or the office's file limit if lower). After sending they see an application number such as `APP-00012`.
- **Status of a form**: at `/portal/admission-status/` (linked from the thank-you page, the form, the sign-in page and the website footer) the parent types the application number and the phone number written on the form, and sees whether it is waiting, accepted (with the class) or not accepted. Both must match, so nobody can read other families' forms by trying numbers; one connection can check 20 times in an hour. The office's notes are never shown.
- The office reads each application under **Admissions** and presses **Accept and add student** (choosing the class and section, the admission number and the roll number) or **Do not accept**. Accepting makes the student and the login, fills the profile from the form (parents, date of birth, phone, address, blood group, photo, admission date) and puts the document under the student's documents. The login details are shown once.
- An application that was not accepted stays in its list and can still be accepted later. An accepted one cannot be changed; its photo and document are then with the student's profile. The portal does not write to the parent; the office calls them.
- Because anyone can send this form, it has limits: at most 5 forms from one connection in an hour (and 20 tries, counting refused ones) and 300 on the whole site in a day; the document must be a PDF, JPG or PNG by what is inside the file, not only by its name; a form sent within a few seconds or with a hidden field filled is refused (these are programs, not people); the same child and phone number cannot be sent twice while the first is waiting.
- A form sent by mistake or as a joke is removed with **Remove from the lists**; it is kept under **Removed applications**.
- Close the form when admissions are over.

## Teacher attendance and leave

- **More → Teacher attendance**: the office chooses a date and marks every teacher Present, Absent or Leave, like a class sheet. Saving again corrects the day. A future date, a holiday of the school calendar or a Sunday cannot be marked. The table below shows each teacher's month.
- A teacher sees their own attendance, percentage and month under **More → My attendance and leave**, and nothing of other teachers.
- On the same screen a teacher asks for leave (first day, last day, reason). Leave can start up to 7 days in the past and one request can be up to 92 days. A waiting request can be withdrawn.
- **More → Teacher leave**: the office approves or refuses, with an optional note. An answer is final. Approved leave is offered as “Leave” on the teacher attendance sheet of those days; the office still saves the sheet.
- The percentage is days present ÷ days marked; leave counts as not present and holidays are not counted, the same as for students.

### Substitute teachers

- **More → Substitute teachers** (also linked from the Today page). Choose the day: today or up to 60 days ahead.
- **Teachers away** lists the teachers with approved leave or marked Absent / Leave on that day, with their periods from the timetable. For each period choose a free teacher and press **Give**; or give the teacher’s class for the **whole day** (the substitute looks after it and marks its attendance).
- The pick list has only teachers who are free: not away that day, not teaching another class in that period and not already standing in elsewhere then. **Give any class to a teacher** covers an absence that is not recorded.
- The substitute’s home screen shows **You stand in today** with the period, class, subject, whose place and the office’s note. On that day only they can open and save the attendance of that class; the next day it is gone.
- **Remove** takes a substitute back (hidden, not deleted).

## Reports

**Reports** has three kinds, each with **Download CSV**:

- **Attendance**: one class (each student's present, absent, leave and percentage) or all classes, between two dates (up to one year).
- **Marks**: one exam and one class, with every subject, total and percentage, and a link to each report card.
- **Fees**: one class or all classes, with fees, paid and left to pay, and the option to list only students who still owe.

The attendance and marks reports of a class count the marks made in that class, so after the classes move up for a new session the reports of the year before still list the students who were in it (with the class they are in now). The fees report lists the students in a class now. CSV files open in Excel; a cell that would start a formula is written as text.

## Today at school

**More → Today** (and the **Today** card at the top of the office home). One look at the school for one day; choose another day to look back.

- **Absent**: how many children are absent, the counts of each class, which classes are not marked yet (with their class teacher), and the list of absent children with a **Call** button for the parent’s phone. A child whose leave was approved is marked so.
- **Teachers away**, with how many substitutes are given and a link to give their classes to other teachers.
- **Fees received** that day: the total, by cash / UPI / cheque…, and each payment with its receipt.
- **Fees still pending**: what is left to collect from the students in the school now, and how much of it is overdue, with the number of students. Advances paid by some students are not set against what others owe.

## Emails to parents

**More → Settings → Emails to parents.** Tick what parents get:

- **Child absent**: when today’s attendance is saved with the child Absent. The email waits 15 minutes and is not sent when the teacher corrects the mark by then. Attendance saved for an earlier day sends nothing.
- **Fees pending**: once, in the morning after the last date of a fee has passed and it is not paid, with the fees and amounts overdue. On **Fees** the office can also press **Send fee reminders now** (everyone with an overdue fee) or **Send fee reminder email** on one student. At most one reminder per student per day.
- **Results out**: when an exam is published. The email says the results are out; the marks are seen in the portal. If the results are hidden again before the email went, it is held back and goes when they are published again.

Good to know:

- Emails go to the **parent’s email** in the student’s profile. It is filled from the admission form, from the **Parent Email** column of the student file, or by the office. The settings screen says how many students have none.
- One child gets one email per kind and day (or exam). An email whose reason is no longer true when it is time to send (the fee was paid, the mark corrected) is not sent.
- Emails are sent a few at a time by WordPress’s scheduled tasks (WP-Cron), so saving attendance is never slow. If sending fails, it is tried again twice, 10 minutes apart; the reason is shown under **Newest emails**. **Send waiting emails now** sends at once (useful where WP-Cron is switched off).
- The live site must be able to send email: set up the school’s SMTP or email service (see `docs/PRODUCTION-DEPLOYMENT.md`). Local does not send real email.

## New session: move classes up

**More → Promotion**, at the end of the session (after the results and the TCs of the year are done).

1. The portal fills in a plan from the class names: Class 5 – A → Class 6 – A (same section when it exists), Nursery → LKG → UKG → Class 1, and the highest class → **Leave the school**. Check every line; choose **Stay in this class** for a class that does not move.
2. Children who repeat the year: open **Keep back** under their class and tick them.
3. Choose the session that ends, tick **I have checked the plan** and press **Move classes up**.

- All classes move at the same moment, so Class 5 → 6 and Class 6 → 7 in one plan never carry a child two classes up. Children of the last class are marked as left (like after a TC) and their login is switched off.
- **Nothing is lost.** Attendance, homework answers, exam marks, report cards, fees and certificates stay with the class the child was in. The class reports of the year before still list those children (“now in Class 6 – A”), report cards still say the old class, teachers still see the answers to their old homework, and each profile shows the **Class history** (class, session, roll number). Fees left to pay go with the child.
- After the move a child sees only the homework, notes and notices their new class gets from then on, not those given to the class the year before.
- Classes keep their teachers, class teacher and timetable; change them if they are different in the new session. Roll numbers stay; change them under Students if needed.
- **Undo this promotion** puts every child back in the class and roll number they had and brings back the children who left, unless the office has changed a child since (those are counted and left as they are). Only the newest promotion can be undone.

## Backup

**More → Backup.** Type your password again and press **Download backup**.

- One ZIP file: every portal table as a CSV file (opens in Excel), the same data as `database.sql` for the site administrator to load back into an empty portal with the same table prefix, `portal-logins.csv` (the accounts, **without passwords**), `settings.json` and a `README.txt`. Tick the box to add the uploaded files (homework, answers, notes, photos, documents); the page shows how big they are.
- The file is written straight to the browser while it is read, so a big school needs no extra space on the server, and no PHP zip extension is needed.
- Each download is logged (who and when) under **Last backups**. The file holds every child’s details: keep it on a safe computer or drive.
- It backs up the portal. The website pages, news and photos are backed up by the hosting company or the site administrator. Uploaded files of more than 3.9 GB in all do not fit in one ZIP; then copy `wp-content/uploads/mags-erp-private` instead.

## Rules built in

- Attendance is Present (P), Absent (A) or Leave (L), one mark per student per day. Saving again corrects the earlier mark.
- Percentage = days present ÷ days marked, inside the academic session (1 April to 31 March). Leave counts as not present.
- Teachers cannot open or save a class that the office has not given them, and cannot mark a future date, a holiday of the school calendar or a Sunday.
- Attendance saved on a day that is a holiday (or a Sunday) is not counted in any percentage or report.
- Every form is checked on the server for the role and for a one-time token; hiding a button is never the only protection.
- Portal accounts (MAGS Student, MAGS Teacher, MAGS Office) cannot open the WordPress Dashboard or edit the website.
- A removed teacher or student cannot sign in on the portal or on `wp-login.php`, and is signed out everywhere at once.
- A substitute teacher can mark the attendance of the class they stand in for on that day only.
- A meeting slot is booked by one parent only; the booking happens in one step in the database.
- After 5 wrong passwords for one login ID, that ID is locked on that connection for 15 minutes. The office changes both numbers under **More → Settings**. The lock is the same on the portal and on `wp-login.php` (and other password sign-ins such as application passwords), and wrong passwords on both count together; while locked, even the right password is refused.
- Portal pages are never cached and are hidden from search engines.
- The admission form is the only screen that can be used without a login. Everything it receives is checked, limited in number and stored like any other private record; nothing a visitor sends is shown to other visitors.
- Photos, student documents and admission form files are private files: the office can open them, a student only their own photo.
- Portal accounts have no author page on the website: `/?author=…` answers "not found" for them, so a visitor cannot read students' or teachers' names there.

## For developers

- `mags-erp.php` loads the files in `includes/`; screens are in `views/`; styles and script in `assets/`.
- Tables (created on activation and when the version changes, kept on deactivation) are listed in `mags_erp_schema()` in `includes/install.php`: `{prefix}mags_erp_classes`, `_class_teachers`, `_students`, `_attendance`, `_files`, `_homework`, `_homework_files`, `_submissions`, `_notes`, `_subjects`, `_periods`, `_timetable`, `_exams`, `_exam_papers`, `_exam_marks`, `_notices`, `_questions`, `_question_replies`, `_leave_requests`, `_fees`, `_fee_payments`, `_calendar`, `_student_profiles`, `_student_documents`, `_certificates`, `_admissions`, `_staff_attendance`, `_staff_leave`, `_promotions`, `_class_history`, `_mail_queue`, `_exam_admit`, `_substitutions`, `_ptm_slots`. A discount is a row of `_fees` with `kind = 'discount'`, a minus amount and `parent_id` of its fee; a late fine is a row with `kind = 'fine'`. `mags_erp_migrate()` carries older data forward.
- Removable records have `hidden_at` and `hidden_by`; use `mags_erp_hide()` / `mags_erp_unhide()` (`includes/helpers.php`) and filter lists with `hidden_at IS NULL`. Do not add `DELETE` for school records. Files leave the disk in two places only: `mags_erp_discard_upload()`, for a file whose record could not be saved, and `mags_erp_delete_replaced_file()`, for a file a new upload replaced (its row stays, hidden; a file another record still uses is only hidden).
- A student who got a TC has `left_on` and `left_certificate_id` in `_students` (`mags_erp_mark_student_left()`); restoring clears them.
- `_students.class_since` is set when a promotion moves a child; the student's lists of homework, notes and notices (and file access) leave out what the class got before that (`mags_erp_student_since_sql()`). `_class_history` keeps each move with the old class and roll number. Attendance rows keep the `class_id` they were marked in, and the class reports count by it.
- A sixth value `true` in `mags_erp_screens()` keeps a screen off the More list (a screen opened from another one, such as Admit cards).
- Saves that check a total before writing (discounts, fines, cancelling fees) take the `fees` lock with `mags_erp_lock()` / `mags_erp_unlock()` (MySQL `GET_LOCK`).
- One file per module, each with its database functions and its form handlers: `includes/files.php` (uploads, private storage, download route), `classwork.php` (homework, submissions, notes), `timetable.php`, `exams.php`, `report-card.php` (the PDF writer and the report card), `messages.php` (notices, questions), `leave.php`, `fees.php`, `calendar.php` (holidays and events; `mags_erp_holiday_on()` and `mags_erp_not_holiday_sql()` are what the attendance code uses), `import.php`, `reports.php` (reports and CSV export), `documents.php` (pieces shared by the PDF documents and the print-page fallback), `pdf-font.php` (the embedded font for Hindi and ₹), `profiles.php` (profile, photo, documents and who may open them), `certificates.php`, `receipts.php`, `id-card.php`, `admissions.php` (the public form, its status page and the office's answers), `staff.php` (teacher attendance and leave), `settings.php` (the office's settings: lock and file size), `today.php` (the office's day page), `notify.php` (emails to parents and their queue; WP-Cron hooks `mags_erp_send_mail` and `mags_erp_daily`), `backup.php` (the streaming ZIP writer and the backup), `promotion.php` (new session and undo), `admit-card.php` (date sheet, admit card rules and PDFs), `substitutes.php`, `ptm.php`, `progress.php` (the marks graph as SVG). `mags_erp_fee_dues()` in `fees.php` gives what each student owes and what is overdue; the Today page, fee reminders and the admit card rule all use it.
- `MAGS_ERP_PDF` in `includes/report-card.php` writes every PDF: text, wrapped paragraphs with bold words, lines, boxes and JPEG photos. Text is cut into runs: Windows-1252 characters are written in Helvetica, everything else with `MAGS_ERP_Font` (`includes/pdf-font.php`), which reads `assets/fonts/NotoSansDevanagari-*.ttf`, shapes the text like HarfBuzz (GSUB/GPOS with the Indic rules: conjuncts, half forms, reph, pre-base vowel signs) and embeds only the glyphs used, with the real text for copying and searching. Each document is still built as data first and falls back to `views/print-document.php` when a text has characters neither font has (`mags_erp_pdf_can_write_all()`). The font can be swapped with the `mags_erp_pdf_font` filter. Noto Sans Devanagari is under the SIL Open Font License (`assets/fonts/OFL.txt`).
- `/portal/admission/` and `/portal/admission-status/` are handled before the login check in `mags_erp_dispatch()` by `mags_erp_admission_screen()` and `mags_erp_admission_status_screen()`; they never go through `mags_erp_actions()`.
- Teachers, students and office staff are WordPress users with the roles `mags_teacher`, `mags_student`, `mags_office`. A student's WordPress username is internal (`stu-…`); the admission number is what they type. A removed login has the user meta `mags_erp_removed`.
- Routes and the role allowed on each are listed in `mags_erp_screens()` (`includes/router.php`); the fifth value puts a screen in the navigation bar. `/portal/file/`, `/portal/report-card/`, `/portal/export/`, `/portal/certificate/`, `/portal/receipt/` and `/portal/id-card/` are downloads handled in `mags_erp_dispatch()`; each checks the role itself (`/portal/receipt/` also lets a student have their own receipts). Form actions and their roles are in `mags_erp_actions()` (`includes/actions.php`). A new module adds a screen, a view and its actions there.
- Form field and URL parameter names must not be WordPress query variables (`name`, `error`, `page`, `s`, `day`, `year`, `more`, `calendar`, …).
- The session start month can be changed with the `mags_erp_session_start_month` filter.
