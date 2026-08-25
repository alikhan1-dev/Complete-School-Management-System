# Module inventory

Master checklist for Smart School 7.2 (CodeIgniter) → Laravel migration.  
Source reference: `smart_7.2/` (read-only). Target: `complete_school_management_system/app/Modules/`.

## Feature inventory

| Module | Feature | CI Location | Tables | Laravel Target | Status |
|--------|---------|-------------|--------|----------------|--------|
| **Shared** | Base model, casts, school context | `application/core/`, `application/libraries/Customlib.php` | `sch_settings`, `sessions`, `sidebar_menus`, `sidebar_sub_menus` | `app/Modules/Shared/` | In Progress / Phase 1 Complete (foundation) |
| **Shared** | Auth middleware (staff, student/parent) | `application/core/MY_Controller.php` | — | `Shared/Middleware/` | In Progress / Phase 1 Complete (foundation) |
| **Shared** | Admin & student/parent layouts | `application/views/layout/` | — | `Shared/Views/layouts/` | In Progress / Phase 1 Complete (foundation) |
| **Shared** | DataTables JSON helper | Various admin controllers | — | `Shared/Services/DataTableResponse.php` | In Progress / Phase 1 Complete (foundation) |
| **Auth** | Staff login / logout | `application/controllers/Site.php` | `staff`, `staff_roles` | `Auth/Controllers/StaffLoginController.php` | In Progress / Phase 1 Complete (foundation) |
| **Auth** | Student/parent login / logout | `application/controllers/Site.php`, `user/User.php` | `users`, `users_authentication` | `Auth/Controllers/StudentParentLoginController.php` | In Progress / Phase 1 Complete (foundation) |
| **Auth** | Choose class (student portal) | `user/User.php` | `student_session`, `classes`, `sections` | `Auth/Controllers/StudentParentLoginController.php` | In Progress / Phase 1 Complete (foundation) |
| **Auth** | Legacy password verification | `application/models/User_model.php` | `users.password`, `staff.password` | `Auth/Services/LegacyPasswordVerifier.php` | In Progress / Phase 1 Complete (foundation) |
| **Roles** | Role CRUD | `admin/Roles.php` | `roles` | `Roles/Controllers/RoleController.php` | In Progress / Phase 1 Complete (foundation) |
| **Roles** | Permission matrix | `admin/Roles.php` | `roles_permissions`, `permission_category`, `permission_group` | `Roles/Services/PermissionService.php` | In Progress / Phase 1 Complete (foundation) |
| **Roles** | Student/parent permissions | `admin/Roles.php` | `permission_student` | `Roles/Models/PermissionStudent.php` | In Progress / Phase 1 Complete (foundation) |
| **Roles** | Menu permission parser | Sidebar views | — | `Shared/Support/MenuPermissionParser.php` | In Progress / Phase 1 Complete (foundation) |
| **Staff** | Staff list (DataTables) | `admin/Staff.php` | `staff`, `staff_roles`, `staff_designation`, `department` | `Staff/Controllers/StaffController.php` | In progress (list/DataTables + create + edit + profile incl. payroll + photo + leave summary + rating + attendance AJAX + documents incl. create/edit upload + timeline + disable/enable + delete + import + SaaS/credential hooks + teachers rating admin list + department/designation masters done; deferred: live credential gateways) |
| **Staff** | Department & designation masters | `admin/Department.php`, `admin/Designation.php` | `department`, `staff_designation` | `Staff/Controllers/DepartmentController.php`, `DesignationController.php` | Done |
| **Staff** | Staff CRUD, documents, timeline | `admin/Staff.php`, `admin/Timeline.php` | `staff`, `staff_timeline`, `staff_attendance` | `app/Modules/Staff/` | In progress (create + edit + profile + documents incl. create/edit upload + timeline + disable/enable + delete + import done) |
| **Academics** | Classes, sections, sessions | `Classes.php`, `Sections.php`, `Sessions.php` | `classes`, `sections`, `class_sections`, `sessions` | `app/Modules/Academics/` | Done (core CRUD + `sections/getByClass`) |
| **Academics** | Subjects & subject groups | `admin/Subject.php`, `admin/Subjectgroup.php`, `admin/Batchsubject.php` | `subjects`, `subject_groups`, `subject_group_subjects`, `subject_group_class_sections` | `app/Modules/Academics/` | Done (subjects + subject groups; batch subjects skipped — vanilla 7.2 schema has no `class_batches`/`batch` tables) |
| **Academics** | Grades, mark divisions, school houses | `admin/Grade.php`, `admin/Marksdivision.php`, `admin/Schoolhouse.php` | `grades`, `mark_divisions`, `school_houses` | `app/Modules/Academics/` | Done |
| **Academics** | Custom fields | `admin/Customfield.php` | `custom_fields`, `custom_field_values` | `app/Modules/Academics/` | Done (admin CRUD + reorder; Students create/edit/show use `CustomFieldValueService` + form partial) |
| **Students** | Admission, profile, documents | `Student.php`, `admin/Onlinestudent.php` | `students`, `student_session`, `student_doc`, `student_timeline` | `app/Modules/Students/` | In progress (create/view/edit/disable + custom fields + documents + timeline + sibling parent reuse + fees-on-admit fee groups/discounts/transport assign + multi-class admin search/save + admit/edit extras + delete guard + class-teacher matrix filter done; online admission enroll owned by OnlineAdmission module) |
| **Students** | Promotion / transfer | `admin/Stdtransfer.php` | `student_session`, `students` | `app/Modules/Students/` | Done (search + promote/leave/fail parity; CI typo `countinue` preserved) |
| **Students** | Disable / alumni | `admin/Disable_reason.php`, `Student.php` (`disablestudentslist`), `admin/Alumni.php` | `disable_reason`, `students`, `alumni_students`, `alumni_events` | `app/Modules/Students/` | In progress (disable reason + disabled students list with class-teacher scope + details view + visible_on_table custom-field columns + alumni list/details/events + alumni class-teacher scope + alumni table custom fields + details view + calendar getevent feed done; alumni mail/SMS deferred to Communication) |
| **Parents** | Parent accounts & linked children | `Student.php` (guardian fields / getlogindetail / send passwords), `user/User.php` | `users` (role=parent), `students` | `app/Modules/Parents/` | In progress (admit parent user + sibling reuse + profile credentials + getlogindetail + send password endpoints done; live mail/SMS/WhatsApp deferred) |
| **Attendance** | Student attendance | `admin/Stuattendence.php`, `admin/Subjectattendence.php` | `student_attendences`, `student_subject_attendances`, `attendence_type` | `app/Modules/Attendance/` | In progress (day + by-date + subject/period mark-save + period reportbydate + day/by-date + subject/period class-teacher scope done; deferred: SMS) |
| **Attendance** | Staff attendance | `admin/Staffattendance.php` | `staff_attendance`, `staff_attendance_type`, `staff_attendence_schedules` | `app/Modules/Attendance/` | In progress (mark/save by role+date + profile month AJAX done; deferred: SMS, biometric auto-mark) |
| **Fees** | Fee types, groups, masters | `Feetype.php`, `Feemaster.php`, `admin/Feegroup.php` | `feetype`, `feemasters`, `fee_groups`, `fee_groups_feetype`, `fee_session_groups`, `cumulative_fine` | `app/Modules/Fees/` | Operational core done (types/groups/master/assign + cumulative fine slabs + collect remaining-fine calc) |
| **Fees** | Fee collection & discounts | `Studentfee.php`, `admin/Feediscount.php`, `admin/Feesforward.php` | `student_fees`, `student_fees_master`, `student_fees_deposite`, `student_fees_discounts`, `fees_discounts`, `student_applied_discounts` | `app/Modules/Fees/` | Operational core done (collect + multi + due-fees + carry-forward + transport single/multi collect + printFeesByName/ByGroup/ByGroupArray + thermal print + fees reminder settings + fee_submission persist + download-receipt + fee-reminder cron persist; deferred: live mail/SMS sends → Communication) |
| **Fees** | Transport fees | `Studentfee.php` | `student_transport_fees`, `transport_feemaster` | `app/Modules/Fees/` + `Transport/` | Collect ledger done in Fees; fees-master admin + student fees assign done in Transport |
| **Fees** | Offline fee payments | `admin/Offlinepayment.php`, `user/Offlinepayment.php` | `offline_fees_payments` | `app/Modules/Fees/` | Operational core done (admin + portal submit + getfees offline entry; deferred: DataTables AJAX pixel-parity, SaaS quota) |
| **Fees** | Student portal fees | `user/User.php` (`getfees`) | (reads fee + transport ledgers; `student_fees_processing` + `gateway_ins`) | `app/Modules/Fees/` | Operational core done (getfees ledger + offline pay entry + previous-session flag + printFeesByName/ByGroupArray + processing-fee banner + online pay modal persist; deferred: live gateway charge APIs → Payments) |
| **Exams** | Exam groups & exams in group | `admin/Examgroup.php` | `exam_groups`, `exam_group_*`, `exam_group_exam_connections`, `exam_group_exam_results` | `app/Modules/Exams/` | Done (assign/marks/link + publish flags; SMS/CSV deferred) |
| **Exams** | Exams, schedules, results | `admin/Exam.php`, `admin/Examschedule.php`, `admin/Examresult.php`, `admin/Mark.php` | `exams`, `exam_schedules`, `exam_group_*` | `app/Modules/Exams/` | Pending (legacy Exam path; primary flow is Examgroup) |
| **Exams** | Marksheets & admit cards | `admin/Marksheet.php`, `admin/Admitcard.php`, `admin/Examresult.php` | `template_marksheets`, `template_admitcards` | `app/Modules/Exams/` | In progress (design templates + HTML print done; mPDF/email deferred) |
| **OnlineExam** | Online exams & question bank | `admin/Onlineexam.php`, `admin/Question.php` | `onlineexam`, `onlineexam_questions`, `onlineexam_students`, `questions` | `app/Modules/OnlineExam/` | In progress (admin core + student take-exam + reports incl. class-teacher scope + ranking generation done; deferred: mail/SMS) |
| **Timetable** | Class timetable | `admin/Timetable.php` | `subject_timetable`, `class_section_times` | `app/Modules/Timetable/` | Done (class create/save + class report + teacher mytimetable + print + duplicate-check + quick period generator) |
| **Homework** | Homework & daily assignments | `Homework.php`, `admin/` (homework views) | `homework`, `homework_evaluation`, `daily_assignment`, `submit_assignment` | `app/Modules/Homework/` | In progress (CRUD + evaluation + portal + daily + reports incl. homework report class-teacher scope done; mail/SMS deferred to Communication) |
| **LessonPlan** | Lessons, topics, syllabus | `admin/Lessonplan.php`, `admin/Syllabus.php` | `lesson`, `topic`, `subject_syllabus`, `lesson_plan_forum` | `app/Modules/LessonPlan/` | In progress (lesson + topic + status + copy + weekly manage + admin forum + full admin class-teacher scope incl. weekly matrix done; deferred: student portal comments, DataTables AJAX, SaaS quota) |
| **Library** | Books & issues | `admin/Book.php`, `admin/Member.php` | `books`, `book_issues`, `libarary_members` | `app/Modules/Library/` | Done (admin + reports incl. book issue class-teacher empty-matrix deny + library reports superadmin_visible staff masking + portal; minor: admin issue_report list, member list superadmin filter) |
| **Transport** | Vehicles, routes, pickup points | `admin/Vehicle.php`, `admin/Route.php`, `admin/Vehroute.php`, `admin/Pickuppoint.php`, `admin/Transport.php` (`feemaster`) | `vehicles`, `transport_route`, `vehicle_routes`, `pickup_point`, `route_pickup_point`, `transport_feemaster`, `student_transport_fees` | `app/Modules/Transport/` | Operational core done (admin core + student transport report incl. class-teacher scope + fees-master + student transport fees assign + pickup reorder + pointmap; Google Maps key via `GOOGLE_MAPS_API_KEY`) |
| **Hostel** | Hostels & rooms | `admin/Hostel.php`, `admin/Hostelroom.php`, `admin/Roomtype.php` | `hostel`, `hostel_rooms`, `room_types` | `app/Modules/Hostel/` | Done (admin core + student hostel report incl. class-teacher scope) |
| **Inventory** | Items, stock, issue | `admin/Item.php`, `admin/Itemstock.php`, `admin/Issueitem.php` | `item`, `item_stock`, `item_issue`, `item_category`, `item_store`, `item_supplier` | `app/Modules/Inventory/` | Done (masters, items, stock, issue, reports) |
| **Payroll** | Payroll & payslips | `admin/Payroll.php` | `staff_payroll`, `staff_payslip`, `payslip_allowance` | `app/Modules/Payroll/` | Done (admin core + report; deferred: currency helpers, print header image, SMS/mail, superadmin_visible) |
| **Leave** | Leave types & requests | `admin/Leavetypes.php`, `admin/Leaverequest.php`, `admin/Approve_leave.php` | `leave_types`, `staff_leave_request`, `staff_leave_details`, `student_applyleave` | `app/Modules/Leave/` | Done (types + staff approve/self-apply + student approve_leave incl. class-teacher scope + reports; deferred: SaaS quota, mail/SMS) |
| **Finance** | Income & expense | `admin/Income.php`, `admin/Expense.php`, `admin/Incomehead.php`, `admin/Expensehead.php` | `income`, `expenses`, `income_head`, `expense_head` | `app/Modules/Finance/` | Operational core done (heads + income/expense CRUD + documents + search_income/search_expense; finance reports owned by Reports) |
| **Communication** | Email, SMS, notifications | `admin/Mailsms.php`, `admin/Notification.php`, `Emailconfig.php`, `Smsconfig.php` | `messages`, `send_notification`, `email_config`, `sms_config`, `notification_setting`, `email_template`, `sms_template` | `app/Modules/Communication/` | In progress (config + notice board + notification templates + compose persist + schedule editors + email/SMS template CRUD done; deferred: live send at schedule time) |
| **Chat** | In-app chat | `admin/Chat.php`, `user/Chat.php` | `chat_users`, `chat_connections`, `chat_messages` | `app/Modules/Chat/` | In progress (staff + user/parent persist/polling done; deferred: live mail/SMS/push, leftover `chat` table/`chatdemo`) |
| **FrontCms** | Public website CMS | `admin/Frontcms.php`, `admin/front/*`, `Welcome.php` | `front_cms_*` | `app/Modules/FrontCms/` | In progress (admin persist + public site + Welcome examresult persist done; deferred: SaaS quota, live YouTube oEmbed, live contact/complain mail, CI theme pixel-parity, connected-exam consolidate UI) |
| **FrontOffice** | Enquiry, visitors, complaints | `admin/Enquiry.php`, `admin/Visitors.php`, `admin/Complaint.php`, `admin/Dispatch.php`, `admin/Receive.php`, `admin/Generalcall.php`, `admin/Visitorspurpose.php`, `admin/Complainttype.php`, `admin/Source.php`, `admin/Reference.php` | `enquiry`, `visitors_book`, `complaint`, `dispatch_receive`, `general_calls`, `visitors_purpose`, `complaint_type`, `source`, `reference` | `app/Modules/FrontOffice/` | In progress (enquiry + visitor book + complaint + postal dispatch/receive + phone call log + setup masters persist done; deferred: SaaS quota) |
| **OnlineAdmission** | Online admission forms & payment | `admin/Onlineadmission.php`, `admin/Onlinestudent.php`, `onlineadmission/*`, `Welcome.php` | `online_admissions`, `online_admission_*` | `app/Modules/OnlineAdmission/` | In progress (admin persist + enroll + public form/review/submit/edit + checkout + applicant files + custom fields + enroll copy to student `custom_field_values` + enroll document/photo copy + enroll barcode/qrcode + admission captcha persist done; deferred: live gateway APIs, live mail/SMS, SaaS quota) |
| **Certificates** | ID cards, certificates, TC | `admin/Certificate.php`, `admin/Generatecertificate.php`, `admin/Transfercertificate.php`, `admin/Studentidcard.php` | `certificates`, `id_card`, `staff_id_card`, `transfer_certificate_*` | `app/Modules/Certificates/` | Done for Phase 5 admin (TC mPDF included; certificate email deferred) |
| **Reports** | Academic, attendance, finance reports | `Report.php`, `Attendencereports.php`, `Financereports.php`, `Balancefees.php` | (reads many domain tables) | `app/Modules/Reports/` | In progress (student information incl. alumni report + class-teacher scope + student_profile table custom fields + alumni report table custom fields + class-section view-students modal + Attendencereports incl. class-teacher scope + Financereports incl. class-teacher scope + transport fee lines + Balancefees/due_fees_report + Human Resource hub/staff_report + Lesson Plan syllabus/teacher reports + Online Exam full report suite persist done; deferred: client print/excel checkbox parity, CI pixel-parity JS/DataTables/Chart.js) |
| **Settings** | School settings, modules, themes | `Schsettings.php`, `admin/Module.php`, `Theme.php`, `admin/Currency.php`, `admin/Captcha.php` | `sch_settings`, `sidebar_menus`, `currencies`, `captcha` | `app/Modules/Settings/` | In progress (captcha + general setting + logo + login page background + backend theme + `theme.css`/`fronttheme.css` + mobile app URL/colors + student/guardian panel + fees flags + ID auto-generation + attendance type core + staff/student auto-attendance schedules + class times + maintenance + WhatsApp + chat delete flags + Google Drive picker + miscellaneous + module toggles + currency persist done; deferred: Envato andapp register, SaaS quota, CI Pickr/wysihtml5 pixel-parity) |
| **Content** | Download center / upload contents | `admin/Content.php`, `admin/Contenttype.php`, `user/Content.php` | `contents`, `content_types`, `upload_contents`, `share_contents`, `share_upload_contents` | `app/Modules/Content/` | In progress (content type + upload + share + user portal persist done; deferred: live YouTube oEmbed, SaaS quota, CI pixel-parity JS, legacy `contents` category pages) |
| **Payments** | Payment gateway drivers (fees, admission) | `Studentfee.php`, `onlineadmission/*`, `Webhooks.php`, `admin/Paymentsettings.php` | `payment_settings`, `gateway_ins`, `gateway_ins_response` | `app/Modules/Payments/` | In progress (admin payment settings + online admission checkout + student fee `gateway_ins`/`student_fees_processing` persist on portal gateway show + `gateway_ins/{gateway}` callback stub + Instamojo webhook stub done; deferred: live drivers, fee settlement, SaaS quota) |

> **Note:** `Payments` is a cross-cutting infrastructure module (gateway contracts + drivers), not a school feature screen. `Content` owns share/download tables (`share_contents`, etc.) that CI treats as “Share” in the UI.

---

## AJAX endpoints (representative)

Most admin lists use jQuery DataTables posting to `*/datatable` or inline controller methods. Preserve URL and JSON shape during migration.

| Area | CI endpoint (examples) | Purpose |
|------|------------------------|---------|
| Staff | `admin/staff/getDatatable` | Staff list |
| Students | `student/ajaxsearch`, `student/getByClassAndSection` | Search & class filters |
| Fees | `studentfee/ajaxSearch`, `studentfee/getcollectfee`, `studentfee/getBalanceFee` | Fee search & balances |
| Attendance | `admin/stuattendence/*` (AJAX save) | Mark attendance |
| Exams | `admin/onlineexam/*` (question load) | Online exam builder |
| Roles | `admin/roles/*` | Permission saves |
| CMS | `Welcome/getSections`, `Welcome/ajaxPaginationData` | Front admission & CMS |
| User portal | `user/user/getfees`, `user/mark/*` | Student fee & marks |
| Chat | `admin/chat/*`, `user/chat/*` | Message polling |
| Shared | Various `get*By*` methods on domain controllers | Cascading dropdowns |

Laravel target: same paths registered in each module's `Routes/web.php`, responses via `Shared/Services/DataTableResponse.php`.

---

## Reports

| CI controller | Report types |
|---------------|--------------|
| `Report.php` | Student fee PDF, invoice, deposit, exam, attendance, library, transport, homework, online exam, staff, payroll, and more (~80+ actions) |
| `Attendencereports.php` | Student/staff attendance summaries |
| `Financereports.php` | Income, expense, balance |
| `Balancefees.php` | Fee balance reports |

Laravel target: `app/Modules/Reports/` orchestrates queries; PDF via `mpdf/mpdf`. Reports module does not own domain tables.

---

## Upload paths

CI stores files under `uploads/` (web root). Laravel mirrors this at `public/uploads/` with disk `legacy_uploads` in `config/filesystems.php`.

| Path | Usage |
|------|-------|
| `uploads/student_images/` | Student photos |
| `uploads/staff_images/`, `uploads/teacher_images/`, `uploads/accountant_images/`, `uploads/librarian_images/` | Staff role photos |
| `uploads/student_documents/` | Student docs |
| `uploads/staff_documents/` | Staff docs |
| `uploads/student_id_card/`, `uploads/staff_id_card/`, `uploads/transfer_certificate/` | ID card assets, barcodes, QR; TC header/signatures |
| `uploads/certificate/`, `uploads/marksheet/`, `uploads/admit_card/` | Generated PDFs/images |
| `uploads/homework/`, `uploads/syllabus_attachment/` | Assignments & syllabus |
| `uploads/gallery/`, `uploads/school_content/` | CMS media |
| `uploads/communicate/` | Email attachments & templates |
| `uploads/front_office/` | Visitors, complaints, dispatch |
| `uploads/inventory_items/`, `uploads/book/` | Inventory & library |
| `uploads/offline_payments/` | Offline payment proofs |
| `uploads/onlinexam_images/` | Online exam images |
| `uploads/print_headerfooter/` | Receipt/header templates |
| `uploads/school_income/`, `uploads/school_expense/` | Finance attachments |
| `uploads/video_tutorial/` | Video tutorial thumbs |
| `uploads/vehicle_photo/` | Transport |
| `uploads/admission_form/` | Admission forms |

Copy existing `smart_7.2/uploads/` tree into `complete_school_management_system/public/uploads/` for parity testing.

---

## Integrations

| Category | CI location | Notes |
|----------|-------------|-------|
| **Payment gateways** | `onlineadmission/*` (28 drivers), `Studentfee.php`, `Webhooks.php` | PayPal, Stripe, Razorpay, Paystack, Paytm, Instamojo, Ccavenue, Billplz, Flutterwave, Midtrans, Mollie, Skrill, PayU, SSLCommerz, Cashfree, Toyyibpay, Payfast, Payhere, Pesapal, Jazzcash, Momopay, Onepay, Dpopay, Walkingm, Kowri, Twocheckout, Checkout, Ipayafrica |
| **SMS providers** | `Smsconfig.php`, `admin/Mailsms.php` | Configured per school in `sms_config` |
| **Email** | `Emailconfig.php`, `admin/Mailsms.php` | `email_config`, templates in DB |
| **Google Drive** | `admin/` (settings) | `google_drive_setting` table |
| **Biometric** | `Biometric.php` | Device integration |
| **Mobile API** | `App.php` | Legacy mobile app endpoints |
| **PDF** | mPDF library | Same engine in Laravel (`mpdf/mpdf`) |

Laravel: gateway logic in `Payments` module; SMS/email in `Communication` module. Only gateways already present in CI are in scope.

---

## Cron tasks

CI entry: `Cron.php` — all methods require `cron_secret_key` from `sch_settings`.

| CI method | Purpose | Laravel target |
|-----------|---------|----------------|
| `index/{key}` | Runs autobackup, feereminder, eventreminder, schedulesmsemails | `school:cron` or individual Artisan commands |
| `student_attendance/{key}` | Auto-mark absent when attendance not submitted | `school:student-attendance` |
| `autobackup/{key}` | DB backup to `backup/database_backup/` | `school:autobackup` |
| `feereminder/{key}` | Fee due reminders (email/SMS) | `school:fee-reminder` |
| `eventreminder/{key}` | Calendar event reminders | `school:event-reminder` |
| `schedulesmsemails/{key}` | Scheduled SMS/email queue | `school:schedule-notifications` |

Optional HTTP parity route: `cron/{key}` forwarding to the same commands.
