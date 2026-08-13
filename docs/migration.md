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
| **2** | Academics, Students (Parents/Timetable partial deferred) | Done (core Academics + Students; Parents pending; Timetable class create/report in progress) |
| **3** | **Fees** (delivery pivot: Fees first; Attendance follows as 3b) — was Phase 4 in original plan | In progress (operational core done incl. multi-collect, due-fees, carry-forward; deferred: transport, print/SMS) |
| **3b** | Attendance (day / subject / staff) + remaining student history | Operational core done (day + by-date + subject/period + staff mark-save); deferred: period reportbydate, SMS, biometric |
| **4** | Finance, Payments (gateway infrastructure) | In progress (Finance heads + income/expense CRUD done; Payments + finance search/reports pending) |
| **5** | Exams, OnlineExam, Certificates | In progress (Exams + Certificates done; OnlineExam admin + student take-exam incl. descriptive/upload done; ranking/reports/mail deferred) |
| **6** | Library, Transport, Hostel, Inventory, Payroll, Leave, Homework, LessonPlan | In progress (Homework + Library + Transport + Hostel + Inventory done; others pending) |
| **7** | Communication, Chat, FrontCms, FrontOffice, OnlineAdmission | Pending |
| **8** | Reports, Settings, Content, remaining checklist items | Pending |

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
- [x] Staff: list + DataTables endpoint
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
