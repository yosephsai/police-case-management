# Project State

> Living status document. Update after each meaningful, verified development session.

## Current status

**Status:** Implementation in progress against the approved plan. Core complaint-to-case workflow, investigation records, supervisory review, case status history, administrative summaries, and audit log are implemented and covered by feature tests.
**Last updated:** 2026-11-25
**Current phase:** Phases 1–8 complete; full UI/UX redesign completed and verified; site-wide form audit and responsive hardening completed and verified.
**Verification baseline:** `php artisan test` → **137 tests, 658 assertions, all passing**. `php artisan migrate` applied all migrations successfully. `php artisan view:cache` compiled all Blade templates without errors.

## Environment facts

- Project directory: `/home/hose/police-case-management`.
- Framework: Laravel **13.43.0**; PHP **8.3**.
- Database: **SQLite** at `database/database.sqlite` (`DB_CONNECTION=sqlite`). Preserved; not switched or reset.
- Frontend: locally served `public/css/app.css` and `public/js/app.js`; no Bootstrap CDN, no framework, no build step. All 19 application/auth screens use the shared design system.
- No git repository initialized in the project directory.

## Implemented behavior

### Authentication and staff (FR-01, FR-02, FR-03)

- Login/logout with session regeneration and validation (`AuthController`), plus an optional persistent-login checkbox wired to the existing `remember` handling.
- Login throttled to 5 attempts per minute per email+IP (`throttle:login`).
- Deactivated accounts cannot log in (`users.is_active`).
- Self-service password reset via emailed token (`/forgot-password`, `/reset-password/{token}`); reset completion is audited (`auth.password_reset`); responses avoid account enumeration.
- Optional TOTP-based two-factor authentication (`/two-factor`) with single-use recovery codes, a login challenge (`throttle:two-factor`, 6/min), and audit events; secrets and recovery codes are encrypted at rest.
- Staff create/edit/delete with optional administrator-set password change.
- Staff activate/deactivate toggle; self-deactivation blocked; last active administrator cannot be deleted/deactivated/demoted.
- Staff actions are audited (`staff.created`, `staff.updated`, `staff.activated`, `staff.deactivated`, `staff.deleted`).
- Eight proposed software roles (`admin`, `station_commander`, `reporting_officer`, `reviewing_officer`, `supervisor`, `lead_detective`, `investigator`, `records_officer`) defined in `config/permissions.php`.
- Permissions are a configurable role→capability map; gates delegate to `User::hasPermission()` instead of hard-coded role checks.
- Administrators can edit the role→capability map at runtime (`GET/PUT /permissions`); overrides persist in `permission_overrides` and take precedence over config defaults. `manage-permissions` is locked to the System Administrator.
- Individual users can be granted or denied capabilities at `/staff/{staff}/permissions`; per-user overrides take precedence over role overrides and defaults, and `manage-permissions` cannot be delegated.
- The System Administrator no longer has complaint-review or case-conversion authority by default.

### Complaints (FR-04, FR-05, FR-06)

- Registration with unique `CMP-YYYYMMDD-XXXXXX` reference and validation.
- Search by reference, complainant, or location.
- Review with decision `approved|rejected`, rejection reason required, double-review prevention.
- Approved complaint can be converted into a case (duplicate prevention, transaction-free but guarded).
- Audit writes for registration, review, and conversion.
- Initial `open` status history recorded when a case is created.

### Cases, assignment, and investigation records (FR-07 to FR-11)

- `GET /cases` with search and status filter; `GET /cases/{case}` case workspace.
- Assignment of one lead investigator plus additional investigators; duplicate and second-lead prevention; only investigators/supervisors assignable; audited.
- Dated investigation activities with audit.
- Evidence records with per-evidence handling history, optional private file attachment (PDF/image/Word/text, max 10 MB), authorized download, and audit.
- Victims, suspects, witnesses, and other people linked to a case with audit.

### Reports and status (FR-12 to FR-14)

- Lead investigator can submit an investigation report.
- Supervisor can accept or return a report; return requires notes; double-review prevention; audited.
- Case status transitions validated: `open → under_investigation → pending_review → (under_investigation | closed)`; each change written to `case_status_histories` with actor and notes, plus audit.

### Search, reports, audit (FR-15 to FR-17)

- Complaint and case search/filtering implemented: free-text search, status filter, and incident/opened date-range filters, with relevance ordering (exact reference, then reference prefix, then other fields).
- SQLite FTS5 indexes (`complaints_fts`, `cases_fts`) with sync triggers cover reference numbers, names, titles, and descriptions; non-SQLite drivers fall back to `LIKE` search.
- Dashboard shows totals, status breakdowns, recent complaints, recent cases, pending actions, and (where permitted) recent audit activity.
- Audit log listing at `GET /audit-logs` (admin/supervisor) with filters by action/details, user, and table, plus pagination.
- Printable case record (`GET /cases/{case}/print`, including evidence handling history) and printable administrative summary (`GET /reports/summary/print`) now use the shared app layout; print-only CSS hides the sidebar/topbar/footer so the PDF stays clean. `layouts/print.blade.php` is retained but unused.
- CSV export of complaints and cases at `GET /reports/complaints.csv` and `GET /reports/cases.csv` (admin/supervisor); each export is audited.
- UI polish: responsive collapsible navbar, active navigation states, skip-to-content link, and a fictional-data/legal-status disclaimer footer.
- Security headers middleware (`nosniff`, `DENY`, referrer policy, permissions policy) and optional HTTPS-only redirect (`FORCE_HTTPS=true`).
- Lead Detectives can now be assigned as lead investigators; the assignment UI and validation accept `lead_detective`, `investigator`, and `supervisor` roles.
- Case access scoping: investigators only see/open cases they are assigned to; investigators can only add activities, evidence, people, and reports to assigned cases (`work-on-case` gate); admin/supervisor can view all cases but do not hold case-work capabilities.
- Complaint access scoping: reporting officers see only complaints they registered; lead detectives/investigators see complaints for their assigned cases; reviewing/supervisory/records/admin roles see all (`view-all-complaints`, `view-assigned-complaints`). New complaints record `registered_by`.
- Login throttling: 5 attempts per minute per email+IP.

### Interface redesign

- One shared authenticated layout (`layouts/app.blade.php`) with a persistent desktop sidebar, collapsible desktop mode, and a mobile slide-out drawer with overlay and close control.
- Shared `partials.sidebar`, `partials.topbar`, `partials.breadcrumbs`, `partials.alerts`, `partials.status-badge`, `partials.empty-state`, `partials.icon`, and an accessible `partials.pagination`.
- Centralized design tokens in `public/css/app.css` (colors, typography, spacing, radii, shadows, layout widths, sidebar/header dimensions, transitions) with reusable buttons, forms, tables, cards, badges, alerts, and detail rows. A curated dark palette uses the same structure with distinct surfaces, borders, text, and status colors (not an inversion).
- Permission-aware sidebar sections: Overview, Records, Reporting, Oversight, Administration. No links to nonexistent screens; investigation/evidence/people remain sections inside case details.
- Auth screens use `layouts/auth.blade.php` with the same design language; printable reports use a restyled `layouts/print.blade.php`.
- Vanilla JS handles sidebar collapse/drawer, user menu, confirmation dialogs, and a persisted light/dark theme toggle available on every screen. Light is the default; print media forces the light palette.
- All existing routes, form field names, gates, validation, pagination, search filters, exports, and 2FA flows preserved.

## Requirement status

| Requirement | Status | Notes |
|---|---|---|
| FR-01 Authentication | COMPLETE (basic) | Login/logout, login throttling, inactive-account blocking, self-service password reset, optional TOTP two-factor authentication with recovery codes. |
| FR-02 Staff accounts | BASIC COMPLETE | Create/edit/delete, activate/deactivate, password update, last-admin protections. |
| FR-03 Roles and permissions | COMPLETE (basic) | Eight roles, config defaults, administrator role editor, per-user overrides, and editable `view-case`/`work-on-case` scoping; gates delegate to `User::hasPermission()`. |
| FR-04 Complaint registration | COMPLETE (basic) | Reporting Officer role only (`register-complaints`), unique reference, phone/date validation, `+251` normalization, audit. |
| FR-05 Complaint review | COMPLETE (basic) | Route, validation, decision storage, reason, audit; tested. |
| FR-06 Case from approved complaint | COMPLETE (basic) | Approved-only, duplicate guard, audit, initial status history; tested. |
| FR-07 Case assignment | COMPLETE (basic) | Supervisor/admin assign; duplicate guard; audit; tested. |
| FR-08 Lead and additional investigators | COMPLETE (basic) | Lead Detectives, investigators, and supervisors can be assigned; single lead enforced; additional investigators. |
| FR-09 Investigation activities | COMPLETE (basic) | Dated activities, actor recorded, audit; tested. |
| FR-10 Evidence + handling history | COMPLETE (basic) | Evidence metadata, handling history, optional private attachment with authorized download; tested. |
| FR-11 Victims/suspects/witnesses | COMPLETE (basic) | Case people with role; tested. |
| FR-12 Investigation report submission | COMPLETE (basic) | Submit with summary; tested. |
| FR-13 Supervisory report review | COMPLETE (basic) | Accept/return with notes requirement; tested. |
| FR-14 Case status tracking/history | COMPLETE (basic) | Validated transitions, history table, UI, audit; tested. |
| FR-15 Permission-controlled search | COMPLETE (basic) | Complaint/case search with status/date filters, relevance ordering, and SQLite FTS5 full-text indexes with `LIKE` fallback; complaint and case retrieval scoped by role/assignment. |
| FR-16 Administrative reports | BASIC COMPLETE | Dashboard summaries, CSV exports of complaints/cases, and a printable summary (browser print/Save as PDF). No server-side PDF binary generation. |
| FR-17 Audit history | BASIC COMPLETE | Writes across implemented actions; filterable listing UI; tested. |

## Known limitations

- Password reset delivery uses the configured mailer; the default `log` mailer writes links to the application log, so real email requires SMTP configuration.
- PDF output relies on the browser print dialog (no server-side PDF generator dependency).
- Full-text indexing uses SQLite FTS5; other database drivers fall back to `LIKE` search.
- Security headers and HTTPS-only mode are application-level hardening; TLS termination and cookie policy should still be configured at the infrastructure layer.
- Permission changes (role or per-user) are not subject to an approval workflow.
- Two-factor secrets are encrypted with `APP_KEY`; rotating that key invalidates existing 2FA setup.

## Security review (Phase 8)

Reviewed 2026-11-24:

- All mutating routes are protected by `auth` plus a gate (`can:*`); unauthorized users receive 403.
- All forms include `@csrf`; Laravel CSRF middleware is active for the web group.
- Blade uses escaped output only (`{{ }}`); no `{!! !!}` raw output found.
- Mass assignment is constrained by `$fillable` and validated arrays; no request-to-model bulk assignment of unvalidated data.
- Passwords are hashed; deactivated accounts are blocked at login.
- Audit writes cover complaint, case, assignment, activity, evidence, people, report, status, and staff actions.
- Record-level authorization now also guards case status changes, investigation report reviews, and case assignments with `view-case`, preventing capability-only overrides from acting on invisible cases.
- Decision-critical multi-write flows (complaint review, complaint-to-case conversion, case status change, report review) run inside database transactions.
- Evidence attachments are validated (type/size), stored on the private `local` disk, and downloaded only through an authorized controller route (no public path).
- The System Administrator is excluded from complaint-review and case-conversion permissions, matching the security guidance.
- TOTP verification uses `hash_equals` with a ±1 period window; recovery codes are hashed and single-use.
- Permission overrides are resolved per user: per-user override → role override → config default; administrators cannot delegate `manage-permissions`.
- Security headers are added to every web response; `FORCE_HTTPS` redirects HTTP requests with a 301 before session/CSRF middleware runs.

## Next actions

1. Optional: configure real SMTP delivery for password-reset emails.
2. Optional: approval workflow for role/permission changes.
3. Optional: load testing beyond the 100-record fictional bulk dataset.
4. Optional: visual regression tooling for the new design system.

## Session log

### 2026-11-24 — Milestones 4–8 completed and verified

- Case assignment, investigation activity, evidence (+history), case people, and investigation report backends added with gates and audit writes.
- Fixed original FK bugs: `evidence_histories.evidence_id` now `constrained('evidences')`; `investigation_reports` FKs made explicit (`cases`, `users`) with corrective rebuild migrations.
- Added feature tests for review, assignment, activity, activity validation, case conversion, evidence, case people, and investigation reports.
- Full suite at the time: 16 tests passing; then 19 tests passing after the binding fix.

### 2026-11-24 — Investigation report review binding defect fixed

- Symptom: supervisor review returned "already reviewed" and never updated the row.
- Root cause: route parameter `{investigation_report}` did not match controller argument `$report`, so implicit model binding resolved an empty model.
- Fix: renamed route parameter to `{report}` so it matches the controller variable.
- Verification: all 4 investigation report tests pass; full suite 19 tests / 47 assertions.

### 2026-11-24 — Milestone 9: cases module UI and routes

- Added `GET /cases` and `GET /cases/{case}` (resource routes) with search and status filters.
- Added `cases/index.blade.php` and `cases/show.blade.php`; the case workspace exposes assignments, activities, evidence and handling history, people, reports, status history, and status changes.
- Added shared navigation to `layouts/app.blade.php` and fixed dashboard sidebar links.
- Enforced single lead investigator and assignee role validation; added assignment audit.
- Verified with `php artisan view:cache` and feature tests.

### 2026-11-24 — Milestone 10: case status tracking and history (FR-14)

- New table `case_status_histories` and model `CaseStatusHistory`.
- `PATCH /cases/{case}/status` (gate `update-case-status`) with transition validation, history row, and audit.
- Initial `open` history written when converting a complaint into a case.

### 2026-11-24 — Milestone 11: audit log UI (FR-17)

- Implemented `AuditLogController@index` with action/details search, user and table filters, pagination.
- Added `audit-logs/index.blade.php`; route protected by gate `view-audit-logs`.

### 2026-11-24 — Milestone 12: dashboard summaries (FR-16)

- Dashboard now shows complaints by status, cases by status, reports awaiting review, and recent cases.

### 2026-11-24 — Milestone 13: staff management completion (FR-02/FR-03)

- Added `users.is_active`; deactivated accounts blocked at login.
- Added activate/deactivate toggle and password update on staff edit.
- Guarded self-deactivation, last active administrator deletion/deactivation/demotion.
- Added audit writes for staff actions.

### 2026-11-24 — Verification

- `php artisan migrate` applied `case_status_histories` and `users.is_active` migrations.
- `php artisan view:cache` compiled all Blade templates successfully.
- Added `DemoDataSeeder` (fictional, idempotent) and `operations.md` backup/recovery notes.
- `php artisan test` → **39 tests, 120 assertions, passed**.

### 2026-11-24 — Phase 8 hardening: throttling, pagination, CSV exports, case scoping

- Added login throttling: named `login` rate limiter (5 attempts/minute per email+IP) applied to `POST /login`; verified by `LoginThrottleTest` (429 after repeated failures).
- Added pagination (20 per page) to complaints, cases, and audit log listings with a shared Bootstrap partial (`partials/pagination.blade.php`).
- Added CSV exports for complaints and cases (`ReportExportController`) gated by `view-reports` and recorded in the audit log; verified by `ReportExportTest` (supervisor allowed, investigator forbidden, audit row written).
- Added case-level access scoping: `PoliceCase::scopeVisibleTo()` and `view-case` gate; investigators only see/open assigned cases; dashboard case summaries scoped too. Verified by `CaseAccessTest` (4 tests).
- Added `work-on-case` gate: investigators can add activities, evidence, handling history, people, and reports only to cases they are assigned to; admin/supervisor are unaffected. Unassigned investigators receive 403. Verified by `CaseAccessTest`.
- Final suite: **40 tests, 124 assertions, all passing**.

### 2026-11-24 — Eight-role permission model, attachments, printable reports

- Added `config/permissions.php` with the eight proposed software roles and a configurable permission map; `User::hasPermission()`/`roleLabel()`; gates now delegate to the map.
- Removed automatic System Administrator review/convert authority (admin denied by `PermissionsTest`).
- Staff forms/validation now use the full role list from config.
- Added evidence file attachments (private `local` disk, type/size validation, authorized download route) and fixed missing-nullable-key bugs for optional fields.
- Added database transactions around decision-critical multi-write flows.
- Added printable case record and printable administrative summary (browser print/save as PDF) with audit on summary view.
- Updated tests for role semantics; added `PermissionsTest` (7), `EvidenceAttachmentTest` (3), `PrintableReportTest` (4).
- Final suite: **54 tests, 153 assertions, all passing**.

### 2026-11-24 — Editable role permissions (FR-03)

- Added `permission_overrides` table + `PermissionOverride` model and a request-scoped `PermissionService` that merges DB overrides over `config/permissions.php` defaults.
- `User::hasPermission()` now delegates to `PermissionService`; gates and views are unchanged.
- Added administrator-only permission editor (`GET/PUT /permissions`, gate `manage-permissions`) with a role × capability matrix, audited as `permissions.updated`.
- Posting a value equal to the default deletes the override, keeping the table minimal; `manage-permissions` is locked to the System Administrator to prevent lockout.
- Added `PermissionOverrideTest` (6 tests: access control, grant, revoke, default pruning, locked capability).
- Final suite: **60 tests, 170 assertions, all passing**.

### 2026-11-24 — Self-service password reset (FR-01)

- Added `PasswordResetController` with `GET/POST /forgot-password` and `GET/POST /reset-password` using Laravel's password broker and the existing `password_reset_tokens` table.
- Reset completion updates the password (hashed cast), rotates `remember_token`, and writes `auth.password_reset` to the audit log.
- Responses are enumeration-safe: unknown emails get the same generic confirmation as known emails.
- Added `auth/forgot-password` and `auth/reset-password` views plus a login-page link; delivery uses the configured mailer (`log` by default, SMTP for real email).
- Added `PasswordResetTest` (6 tests: link sent, enumeration safety, valid reset + audit, invalid token, forms render).
- Final suite: **66 tests, 192 assertions, all passing**.

### 2026-11-24 — Complaint retrieval scoping (FR-15)

- Added nullable `complaints.registered_by` (indexed) and record it on complaint registration.
- Added `view-all-complaints` (admin, station commander, reviewing officer, supervisor, records officer) and `view-assigned-complaints` (lead detective, investigator) capabilities, editable in the permission editor.
- Added `Complaint::scopeVisibleTo()` / `isVisibleTo()`; applied to the complaints list, complaint show, review, conversion, and dashboard counts (complaints, visible evidence, pending reports).
- Reporting officers now see only their own complaints; investigators see complaints for assigned cases; reviewers/supervisors/records/admin see all.
- Added `ComplaintAccessTest` (5 tests) and updated the demo seeder to attribute its complaint.
- Final suite: **71 tests, 204 assertions, all passing**.

### 2026-11-24 — Search filters and relevance ordering (FR-15)

- Complaint and case lists accept `search`, `status`, `from`, and `to` query parameters with server-side validation (`to` must not precede `from`).
- Free-text search matches reference/title/complainant/location; results are relevance-ordered (exact reference → reference prefix → other fields) before recency.
- Date filters apply to `complaints.incident_date` and `cases.opened_at`; invalid ranges redirect back with validation errors.
- Added filter controls (status select, from/to date inputs) to both list views.
- Added `SearchFilterTest` (5 tests: complaint ranking, complaint status/date filters, case ranking, case status/date filters, invalid range).
- Final suite: **76 tests, 219 assertions, all passing**.

### 2026-11-24 — Final optional features (all FR items)

- **Per-user permission delegation:** `user_permission_overrides` table, `UserPermissionDelegate` support in `PermissionService` (per-user > role > default), administrator editor at `/staff/{staff}/permissions`, audit `user_permissions.updated`; `PermissionOverrideTest`/`UserPermissionDelegationTest` cover grant, deny, inherit, and lockout protections.
- **Two-factor authentication:** dependency-free RFC 6238 TOTP service (`App\Support\Totp`), encrypted secret/recovery-code storage, setup/disable/regenerate flows, login challenge with rate limiting, single-use hashed recovery codes, and audit events (`TwoFactorTest`, 8 tests).
- **Full-text search:** SQLite FTS5 virtual tables with sync triggers for complaints and cases, relevance ordering (`CASE` + `bm25`) with a `LIKE` fallback for non-SQLite drivers (`FullTextSearchTest`, 5 tests).
- **UI polish:** responsive navbar collapse, active nav states, skip-to-content link, footer disclaimer, and consistent page containers.
- Final suite: **95 tests, 303 assertions, all passing**; all migrations applied; Blade compiles cleanly.

### 2026-11-24 — Assignment fix, security headers, and bulk dataset

- Fixed a functional gap: Lead Detectives can now be assigned as lead investigators (controller validation, assignment UI, dashboard case-show list).
- Added `AddSecurityHeaders` middleware (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `X-XSS-Protection`, `Permissions-Policy`) and `RedirectToHttps` middleware controlled by `FORCE_HTTPS`; `.env.example` now documents HTTPS/session cookie defaults.
- Added `BulkDemoDataSeeder` (100 fictional complaints/cases with histories, assignments, activities, evidence, people, reports; idempotent) and `BulkSeederTest` to verify list/dashboard performance with a larger dataset.
- Added `SecurityHeadersTest` (3) and a Lead Detective assignment test (1).
- Final suite: **102 tests, 336 assertions, all passing**.

### 2026-11-24 — Complete UI/UX redesign

- Audited all 23 Blade views, 48 routes, controllers/gates, and frontend assets before changes.
- Replaced the Bootstrap CDN layout with a locally served token-based design system (`public/css/app.css`, `public/js/app.js`).
- Added shared layout/partials; removed the duplicate dashboard sidebar and every page-specific navigation/alerts implementation.
- Redesigned every authenticated screen (dashboard, complaints list/create/detail, case list/detail, staff list/create/edit, role and per-user permissions, audit log, 2FA settings) plus all four auth screens and both printable views.
- Added `UiConsistencyTest` (5 tests) asserting the shared shell, permission-aware navigation, accessible markup, absence of CDN/Bootstrap legacy classes, and globally rendered validation errors.
- Final suite: **107 tests, 452 assertions, all passing**. Local HTTP smoke test confirmed login, dashboard, CSS, and JS assets return 200 with no Bootstrap references.

### 2026-11-24 — Redesign follow-up

- Dashboard now surfaces recent complaints, pending actions (pending complaints/reports and cases under investigation), and an audit activity card for users with `view-audit-logs`.
- Printable case records now include evidence handling history, matching the detail screen.
- Added confirmation before recording a complaint review decision.
- Extended `UiConsistencyTest` to cover every authenticated form/detail screen and the print shell; dashboard overview sections are asserted too.
- Final suite: **110 tests, 495 assertions, all passing**.

### 2026-11-24 — Final authorization and login polish

- Added `view-case` checks to case status updates, investigation report reviews, and case assignments; these endpoints previously relied on a capability gate alone.
- Added `RecordLevelSecurityTest` (3 tests) proving that a supervisor denied `view-all-cases` cannot mutate an invisible case through any of the three endpoints.
- Exposed the existing persistent-login option with a "Keep me signed in" checkbox on the login form.
- Added subtle submit-button loading feedback via `public/js/app.js`.
- Final suite: **113 tests, 503 assertions, all passing**.

### 2026-11-24 — Print-shell and login refinements

- Printable views now extend `layouts.app`, so the persistent sidebar and topbar remain visible while viewing the report; `@media print` rules remove application chrome and keep only the document.
- Added a shared `partials.print-bar` and `print-document` wrapper; verified live at `/reports/summary/print` (sidebar present on screen).
- Redesigned the login screen: wider auth column/card, larger inputs and button, secure-access eyebrow, login options row, and a security/fictional-data note.
- Updated `UiConsistencyTest` for the new print behavior; final suite **113 tests, 507 assertions, all passing**.

### 2026-11-24 — Complaint input validation

- Phone number entry now shows a fixed `+251` country prefix and accepts exactly 9 digits; the server stores `+251` plus those digits and rejects other formats.
- Incident date is limited to today or earlier in both the browser (`max`) and server validation (`before_or_equal:today`).
- Added `ComplaintInputValidationTest` (5 tests). Final suite: **118 tests, 531 assertions, all passing**.

### 2026-11-24 — Dark mode

- Added `[data-theme="dark"]` token overrides: separate background/surface/border/text scales, brighter accent, and adjusted success/warning/danger/info colors for dark surfaces.
- Added a theme toggle to the topbar and a floating control on authentication screens; the choice persists in `localStorage` and falls back to `prefers-color-scheme`.
- Applied the theme before stylesheet load to avoid a flash, and forced light tokens inside `@media print` so printed documents remain unchanged.
- Added `ThemeTest` (3 tests). Final suite: **121 tests, 544 assertions, all passing**.

### 2026-11-24 — End-to-end role workflow verification

- Added `RoleWorkflowTest` (8 tests) that performs real HTTP tasks for all eight roles: registration/visibility, complaint review/conversion, assignment/status/report review, full detective case work, investigator limits, records-officer evidence handling, and administrator management without case-decision powers.
- All eight role workflows passed with no application changes required. Two initial failures were in the test harness itself (chained response getters and a missing evidence actor) and were corrected.
- Final suite: **129 tests, 603 assertions, all passing**.

### 2026-11-24 — Complaint registration role fix

- `project.md` FR-04 and the role table specify that a Reporting Officer registers complaints; the application previously allowed any authenticated user, including the administrator.
- Added the `register-complaints` capability (default: `reporting_officer` only), gated `complaints.create`/`complaints.store`, and hid the registration button for other roles.
- Added `ComplaintRegistrationRoleTest` (3 tests) proving Reporting Officer access and 403 for all seven other roles.
- Final suite: **132 tests, 628 assertions, all passing**.

### 2026-11-24 — Role boundary corrections

- Audited `project.md` role responsibilities against `config/permissions.php`. The supervisor role had been given detective/investigator case-work capabilities that the docs assign only to assigned investigators and the Lead Detective.
- Removed `record-activities`, `add-evidence`, `handle-evidence`, `manage-people`, and `submit-report` from the supervisor role. Supervisors retain assignment, status, report review, oversight, and reporting access.
- Updated tests that used supervisors for evidence work to use an assigned Lead Detective, and changed the per-user deny test to use a capability supervisors actually hold.
- Extended `RoleWorkflowTest` to assert supervisors cannot record activities or submit reports.
- Final suite remains **132 tests, 628 assertions, all passing**.

### 2026-11-24 — UI role-visibility audit

- Audited every Blade screen against the permission map. All action buttons/forms already matched their capabilities; no action leaks were found.
- Fixed informational/navigation gaps: roles without case access no longer see the Cases nav item or dashboard case metrics; review workload counts are limited to roles with the matching review capability; staff-directory count is limited to staff/reporting roles.
- Fixed links that could lead to 403 under runtime overrides: the per-user permission editor no longer links to the staff screen when the holder lacks `manage-staff`, and a case no longer links to an invisible complaint.
- Added `UiRoleVisibilityTest` (5 tests). Final suite: **137 tests, 658 assertions, all passing**.

### 2026-11-25 — Login branding

- Added the Ethiopian Federal Police emblem from `assets/Ethiopia logo.webp` (copied to `public/assets/ethiopia-police-logo.webp`) to the blue side of the login screen, both as a compact brand mark and a centred emblem.
- Removed the "Demonstration application — fictional data only. Not an official record or legal judgment." text from the application footer and the authentication aside.
- Final suite: **137 tests, 658 assertions, all passing**.

### 2026-11-25 — Login panel copy

- Blue login panel now centres the emblem vertically and horizontally, with the title "Ethiopian Police Management System" and a smaller quoted slogan: "Service with integrity. Records with accountability."
- Final suite: **137 tests, 658 assertions, all passing**.

### 2026-11-25 — Remember-me checkbox fix

- Native checkbox rendering could appear black because form controls followed the OS colour scheme. The page now declares `color-scheme: light` (and `dark` only under the dark theme), and the checkbox uses a custom styled appearance with a visible blue checked state and focus ring.
- Final suite: **137 tests, 658 assertions, all passing**.

### 2026-11-25 — Responsive form grids

- `.form-row` and `.filter-bar` now use `auto-fit` with `minmax(min(100%, ...), 1fr)` so fields wrap instead of overlapping when the content area is narrow (complaints/cases filters and staff/create forms).
- Buttons keep their natural width inside grid rows, and inputs have `min-width: 0` to prevent grid blowout.
- Final suite: **137 tests, 658 assertions, all passing**.

### 2026-11-25 — Site-wide form audit, alignment and responsive improvements

- Audited all 22 discovered application forms (auth: login/forgot/reset/two-factor; complaints: index filter, create, review, convert-to-case; cases: index filter, status update, assignment, activity, evidence handling, add evidence, add person, submit report, report review; staff: create, edit; permissions: role matrix, user overrides; two-factor settings).
- Added a reusable Blade partial `partials/field-error.blade.php` that renders the first Laravel validation message for a given field inside a `form-error` element, and wired it into every submission form that carries server-validated fields (auth, complaints, cases, staff, two-factor, filter date fields).
- Shared styling hardening in `public/css/app.css`: grid children now get `min-width: 0` to prevent grid blowout; inputs no longer suppress the global `:focus-visible` ring (keyboard focus now visible on inputs/selects/textareas); invalid-state focus styling added; card headers wrap; table cells break long unbroken strings; table wrapper gets touch scrolling; prose boxes allow long-word wrapping.
- Responsive hardening in `public/css/app.css`: `.grid-3` now collapses to two columns at ≤1180px (matching the existing dashboard statistics behaviour); mobile sidebar collapsed-width override corrected; small-screen topbar spacing tightened.
- Final suite: **137 tests, 658 assertions, all passing**.
