# Migration process and phases

CodeIgniter 3 Smart School 7.2 → Laravel 12 re-engineering workflow.

## Ground rules

- Reference app `smart_7.2/` is **read-only**
- Laravel app lives in `complete_school_management_system/`
- **Functional parity only** — no new features during migration
- PHP **8.2+**, Laravel **12** (not Laravel 13 — requires PHP 8.3)
- Keep existing table and column names

## Per-module playbook

```text
1. Read CI controllers, models, views for the module
2. Document business rules and edge cases
3. Confirm DB tables (docs/modules.md)
4. Implement Laravel module (routes, requests, controllers, services, views)
5. Wire permissions and module-enabled checks
6. Match AJAX/JSON response shapes
7. Feature tests + parity check (tools/compare_with_codeigniter)
8. Update docs/modules.md status
```

A module is complete when routes, validation, permissions, views, AJAX, reports, uploads, and tests all match CI behaviour.

## Phases

| Phase | Scope | Status |
|-------|-------|--------|
| **0** | Inventory + docs (`docs/modules.md`, `database.md`, `known_issues.md`) | Done (baseline) |
| **1** | Shared, Auth, Roles, Staff — foundation | Done (core) |
| **2** | Academics, Students (Parents/Timetable partial deferred) | Done (core Academics + Students; disable/alumni + alumni report done; Parents credentials/login-detail/send-password endpoints done — live gateway deferred; Timetable class create/report + teacher mytimetable + print + duplicate-check done; quick period generator deferred) |
| **3** | **Fees** (delivery pivot: Fees first; Attendance follows as 3b) — was Phase 4 in original plan | Operational core done (multi-collect, due-fees, carry-forward + transport single/multi collect + offline bank payments + student getfees portal ledger/print/processing-banner + online pay modal persist + printFeesByName/ByGroup/ByGroupArray + cumulative fine + thermal print + fees reminder settings + fee_submission notification persist + download-receipt PDF + fee-reminder cron persist; deferred: live gateway charges → Payments, fee_submission/fees_reminder live mail/SMS → Communication; transport fees-master admin → Transport) |
| **3b** | Attendance (day / subject / staff) + remaining student history | Operational core done (day + by-date + subject/period + staff mark-save + period reportbydate); deferred: SMS, biometric, class-teacher filter, staff profile month view |
| **4** | Finance, Payments (gateway infrastructure) | In progress (Finance operational core done — heads + income/expense CRUD + search screens; finance reports owned by Reports; Payments admin method credentials + online admission checkout + student fee `gateway_ins`/`student_fees_processing` persist + callback/webhook stubs done; live gateway drivers + fee settlement pending) |
| **5** | Exams, OnlineExam, Certificates | In progress (Exams + Certificates done; OnlineExam admin + student take-exam + reports + ranking generation done; mail deferred) |
| **6** | Library, Transport, Hostel, Inventory, Payroll, Leave, Homework, LessonPlan | In progress (Homework + Library + Transport operational core + Hostel + Inventory + Payroll + Leave done; LessonPlan core done incl. weekly syllabus + admin forum; deferred: student comments, class-teacher scope) |
| **7** | Communication, Chat, FrontCms, FrontOffice, OnlineAdmission | In progress (Communication persist + staff/user Chat persist/polling + FrontOffice persist slices done + FrontCms admin persist + public site + Welcome examresult persist done + OnlineAdmission admin persist + enroll + public form/review/submit/edit + checkout + applicant files + custom fields + enroll copy to student + enroll document/photo copy + enroll barcode/qrcode + admission captcha persist done; live send + live gateways + SaaS quota deferred) |
| **8** | Reports, Settings, Content, remaining checklist items | In progress (Settings captcha + general setting + logo + login page background + backend theme + theme.css/fronttheme.css + mobile app URL/colors + student/guardian panel + fees flags + ID auto-generation + attendance type core + staff/student auto-attendance schedules + class times + maintenance + WhatsApp + chat delete flags + Google Drive picker + miscellaneous + module toggles + currency persist done; Content type + upload + share + user portal persist done; Reports student information (incl. alumni report) + Attendencereports + Financereports + Balancefees/due_fees_report + Human Resource hub/staff_report + Lesson Plan syllabus/teacher reports + Online Exam hub/exams report persist done; Content deferred: live YouTube oEmbed, SaaS quota, CI pixel-parity JS, legacy contents category pages) |

> **Note:** Original plan had Phase 3 = Attendance and Phase 4 = Fees. Delivery now runs **Fees as Phase 3** (per product priority), then Attendance as **3b**, keeping functional parity and module boundaries.

## Phase 0 deliverables

- [x] Workspace scaffold (`docs/`, `database/`, `tools/`, `README.md`)
- [x] SQL dump in `database/dumps/smart_school_7.2_install.sql`
- [x] Baseline migration generator (`tools/make_migrations/generate.php`)
- [x] Module skeleton under `app/Modules/`
- [ ] Full feature inventory (`docs/modules.md`)
- [ ] Schema documentation (`docs/database.md`)

## Phase 1 deliverables

- [x] Laravel 12 scaffold with module autoload
- [x] `public/backend/` CI asset copy
- [x] Shared: BaseModel, YesNoBoolean, SchoolContext, DataTableResponse, middleware, layouts
- [x] Auth: staff + student/parent login, LegacyPasswordVerifier, choose-class flow
- [x] Roles: permission service, role list, permission matrix
- [x] Staff: list + DataTables + create/edit + profile + attendance AJAX + documents + timeline + disable/enable
- [ ] Baseline migrations applied to `ssnodb_laravel`
- [ ] Feature tests for auth and permissions
- [ ] Language conversion (77 locales)

## Database migration steps

```bash
# 1. Create databases and import (or run Laravel migrations)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ssnodb_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root ssnodb_laravel < database/dumps/smart_school_7.2_install.sql

# 2. Or generate and run Laravel baselines
php tools/make_migrations/generate.php
cd complete_school_management_system
php artisan migrate
```

Migrations skip if `sch_settings` exists (already imported).

## Tools

| Tool | Purpose |
|------|---------|
| `tools/make_migrations/generate.php` | Generate baseline schema migrations |
| `tools/build_module_list/` | Build/update module inventory |
| `tools/convert_languages/` | CI language folders → Laravel `lang/` |
| `tools/compare_with_codeigniter/` | HTTP/response parity diffs |

## What is excluded

- Envato license / DRM / remote updater / addon installer
- SaaS multi-tenant quota code
- UI redesign or URL cleanup (see `future_improvements.md`)

## Tracking progress

Update the **Status** column in `docs/modules.md` as each feature reaches parity. Phase 1 modules marked **In Progress / Phase 1 Complete (foundation)** until full CRUD and tests land.
