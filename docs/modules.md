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
| **Staff** | Staff list (DataTables) | `admin/Staff.php` | `staff`, `staff_roles`, `staff_designation`, `department` | `Staff/Controllers/StaffController.php` | In Progress / Phase 1 Complete (foundation) |
| **Staff** | Staff CRUD, documents, timeline | `admin/Staff.php`, `admin/Timeline.php` | `staff`, `staff_timeline`, `staff_attendance` | `app/Modules/Staff/` | Pending |
| **Academics** | Classes, sections, sessions | `Classes.php`, `Sections.php`, `Sessions.php` | `classes`, `sections`, `class_sections`, `sessions` | `app/Modules/Academics/` | Done (core CRUD + `sections/getByClass`) |
| **Academics** | Subjects & subject groups | `admin/Subject.php`, `admin/Subjectgroup.php`, `admin/Batchsubject.php` | `subjects`, `subject_groups`, `subject_group_subjects`, `subject_group_class_sections` | `app/Modules/Academics/` | Done (subjects + subject groups; batch subjects pending) |
| **Academics** | Grades, mark divisions, school houses | `admin/Grade.php`, `admin/Marksdivision.php`, `admin/Schoolhouse.php` | `grades`, `mark_divisions`, `school_houses` | `app/Modules/Academics/` | Done |
| **Academics** | Custom fields | `admin/Customfield.php` | `custom_fields`, `custom_field_values` | `app/Modules/Academics/` | Done (admin CRUD + reorder; Students create/edit/show use `CustomFieldValueService` + form partial) |
| **Students** | Admission, profile, documents | `Student.php`, `admin/Onlinestudent.php` | `students`, `student_session`, `student_doc`, `student_timeline` | `app/Modules/Students/` | In progress (create/view/edit/disable + custom fields + documents + timeline + sibling parent reuse on admit; online admission / fees-on-admit pending) |
| **Students** | Promotion / transfer | `admin/Stdtransfer.php` | `student_session`, `students` | `app/Modules/Students/` | Done (search + promote/leave/fail parity; CI typo `countinue` preserved) |
| **Students** | Disable / alumni | `admin/Disable_reason.php`, `admin/Alumni.php` | `disable_reason`, `alumni_students`, `alumni_events` | `app/Modules/Students/` | Pending |
| **Parents** | Parent accounts & linked children | `Student.php` (guardian fields), `user/User.php` | `users` (role=parent), `students` | `app/Modules/Parents/` | Pending |
| **Attendance** | Student attendance | `admin/Stuattendence.php`, `admin/Subjectattendence.php` | `student_attendences`, `student_subject_attendances`, `attendence_type` | `app/Modules/Attendance/` | In progress (day + by-date + subject/period mark-save done; deferred: period reportbydate, SMS, class-teacher filter) |
| **Attendance** | Staff attendance | `admin/Staffattendance.php` | `staff_attendance`, `staff_attendance_type`, `staff_attendence_schedules` | `app/Modules/Attendance/` | In progress (mark/save by role+date done; deferred: SMS, profile month view, biometric auto-mark) |
| **Fees** | Fee types, groups, masters | `Feetype.php`, `Feemaster.php`, `admin/Feegroup.php` | `feetype`, `feemasters`, `fee_groups`, `fee_groups_feetype`, `fee_session_groups` | `app/Modules/Fees/` | In progress (types/groups/master/assign done; cumulative fine deferred) |
| **Fees** | Fee collection & discounts | `Studentfee.php`, `admin/Feediscount.php`, `admin/Feesforward.php` | `student_fees`, `student_fees_master`, `student_fees_deposite`, `student_fees_discounts`, `fees_discounts`, `student_applied_discounts` | `app/Modules/Fees/` | In progress (collect + multi + due-fees + carry-forward done; deferred: transport, print/SMS) |
| **Fees** | Transport fees | `Studentfee.php` | `student_transport_fees`, `transport_feemaster` | `app/Modules/Fees/` | Pending |
| **Fees** | Offline fee payments | `admin/Offlinepayment.php` | `offline_fees_payments` | `app/Modules/Fees/` | Pending |
| **Exams** | Exam groups & exams in group | `admin/Examgroup.php` | `exam_groups`, `exam_group_*`, `exam_group_exam_connections`, `exam_group_exam_results` | `app/Modules/Exams/` | Done (assign/marks/link + publish flags; SMS/CSV deferred) |
| **Exams** | Exams, schedules, results | `admin/Exam.php`, `admin/Examschedule.php`, `admin/Examresult.php`, `admin/Mark.php` | `exams`, `exam_schedules`, `exam_group_*` | `app/Modules/Exams/` | Pending (legacy Exam path; primary flow is Examgroup) |
| **Exams** | Marksheets & admit cards | `admin/Marksheet.php`, `admin/Admitcard.php`, `admin/Examresult.php` | `template_marksheets`, `template_admitcards` | `app/Modules/Exams/` | In progress (design templates + HTML print done; mPDF/email deferred) |
| **OnlineExam** | Online exams & question bank | `admin/Onlineexam.php`, `admin/Question.php` | `onlineexam`, `onlineexam_questions`, `onlineexam_students`, `questions` | `app/Modules/OnlineExam/` | In progress (admin core through results/evaluation done; portal/ranking/reports deferred) |
| **Timetable** | Class timetable | `admin/Timetable.php` | `subject_timetable`, `class_section_times` | `app/Modules/Timetable/` | In progress (class create/save + class report done; deferred: teacher timetable, print, duplicate-check) |
| **Homework** | Homework & daily assignments | `Homework.php`, `admin/` (homework views) | `homework`, `homework_evaluation`, `daily_assignment`, `submit_assignment` | `app/Modules/Homework/` | Pending |
| **LessonPlan** | Lessons, topics, syllabus | `admin/Lessonplan.php`, `admin/Syllabus.php` | `lesson`, `topic`, `subject_syllabus`, `lesson_plan_forum` | `app/Modules/LessonPlan/` | Pending |
| **Library** | Books & issues | `admin/Book.php`, `admin/Member.php` | `books`, `book_issues`, `libarary_members` | `app/Modules/Library/` | Pending |
| **Transport** | Vehicles, routes, pickup points | `admin/Vehicle.php`, `admin/Route.php`, `admin/Vehroute.php`, `admin/Pickuppoint.php` | `vehicles`, `transport_route`, `vehicle_routes`, `pickup_point`, `route_pickup_point` | `app/Modules/Transport/` | Pending |
| **Hostel** | Hostels & rooms | `admin/Hostel.php`, `admin/Hostelroom.php`, `admin/Roomtype.php` | `hostel`, `hostel_rooms`, `room_types` | `app/Modules/Hostel/` | Pending |
| **Inventory** | Items, stock, issue | `admin/Item.php`, `admin/Itemstock.php`, `admin/Issueitem.php` | `item`, `item_stock`, `item_issue`, `item_category`, `item_store`, `item_supplier` | `app/Modules/Inventory/` | Pending |
| **Payroll** | Payroll & payslips | `admin/Payroll.php` | `staff_payroll`, `staff_payslip`, `payslip_allowance` | `app/Modules/Payroll/` | Pending |
| **Leave** | Leave types & requests | `admin/Leavetypes.php`, `admin/Leaverequest.php`, `admin/Approve_leave.php` | `leave_types`, `staff_leave_request`, `staff_leave_details`, `student_applyleave` | `app/Modules/Leave/` | Pending |
| **Finance** | Income & expense | `admin/Income.php`, `admin/Expense.php`, `admin/Incomehead.php`, `admin/Expensehead.php` | `income`, `expenses`, `income_head`, `expense_head` | `app/Modules/Finance/` | In progress (heads + income/expense CRUD + documents done; deferred: search screens, finance reports) |
| **Communication** | Email, SMS, notifications | `admin/Mailsms.php`, `admin/Notification.php`, `Emailconfig.php`, `Smsconfig.php` | `messages`, `send_notification`, `email_config`, `sms_config`, `notification_setting` | `app/Modules/Communication/` | Pending |
| **Chat** | In-app chat | `admin/Chat.php`, `user/Chat.php` | `chat_users`, `chat_connections`, `chat_messages` | `app/Modules/Chat/` | Pending |
| **FrontCms** | Public website CMS | `admin/Frontcms.php`, `admin/front/*`, `Welcome.php` | `front_cms_*` | `app/Modules/FrontCms/` | Pending |
| **FrontOffice** | Enquiry, visitors, complaints | `admin/Enquiry.php`, `admin/Visitors.php`, `admin/Complaint.php`, `admin/Dispatch.php` | `enquiry`, `visitors_book`, `complaint`, `dispatch_receive`, `general_calls` | `app/Modules/FrontOffice/` | Pending |
| **OnlineAdmission** | Online admission forms & payment | `admin/Onlineadmission.php`, `onlineadmission/*`, `Welcome.php` | `online_admissions`, `online_admission_*` | `app/Modules/OnlineAdmission/` | Pending |
| **Certificates** | ID cards, certificates, TC | `admin/Certificate.php`, `admin/Generatecertificate.php`, `admin/Transfercertificate.php`, `admin/Studentidcard.php` | `certificates`, `id_card`, `staff_id_card`, `transfer_certificate_*` | `app/Modules/Certificates/` | In progress (student/staff cert+ID templates & generate + TC settings done; TC download/verify pending) |
| **Reports** | Academic, attendance, finance reports | `Report.php`, `Attendencereports.php`, `Financereports.php`, `Balancefees.php` | (reads many domain tables) | `app/Modules/Reports/` | Pending |
| **Settings** | School settings, modules, themes | `Schsettings.php`, `admin/Module.php`, `Theme.php`, `admin/Currency.php` | `sch_settings`, `sidebar_menus`, `currencies` | `app/Modules/Settings/` | Pending |
| **Content** | Download center / upload contents | `admin/Content.php`, `admin/Contenttype.php`, `user/Content.php` | `contents`, `content_types`, `upload_contents`, `share_contents`, `share_upload_contents` | `app/Modules/Content/` | Pending |
| **Payments** | Payment gateway drivers (fees, admission) | `Studentfee.php`, `onlineadmission/*`, `Webhooks.php`, `admin/Paymentsettings.php` | `payment_settings`, `gateway_ins`, `gateway_ins_response` | `app/Modules/Payments/` | Pending |

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
