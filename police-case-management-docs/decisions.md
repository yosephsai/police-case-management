# Architecture and Project Decisions

Record decisions here with date, context, decision, alternatives, and consequences. Distinguish confirmed decisions from proposals awaiting approval.

## D-001 — Existing codebase must be audited before modification

**Status:** Confirmed  
**Date:** 2026-11-24

**Context:** A Laravel project already exists at `~/police-case-management`, including earlier complaint/case work and existing `AGENTS.md` and `CLAUDE.md`.

**Decision:** Inspect and assess the existing repository before changing application code. Preserve verified working functionality. Do not create a replacement project.

**Consequence:** The first local-agent run must be read-only and produce an audit and requirements matrix.

## D-002 — Primary learning objective

**Status:** Confirmed  
**Date:** 2026-11-24

**Context:** The developer clarified that the assignment's purpose is to become familiar with Laravel.

**Decision:** Build through hands-on, incremental Laravel work and explain important concepts. Prefer readable conventional Laravel code over opaque automation or unnecessary architecture.

**Consequence:** Local-agent changes must be small, explained, tested, and understandable.

## D-003 — PHP and Laravel application stack

**Status:** Confirmed  
**Date:** 2026-11-24

**Context:** The developer clarified that the technology stack was changed to PHP/Laravel only.

**Decision:** Keep the application in PHP/Laravel, using Laravel-native features and Blade. Do not introduce a separate backend framework or frontend framework.

**Consequence:** Inspect existing database and frontend assets; do not automatically add Node-based frameworks or dependencies.

## D-004 — Single-station prototype scope

**Status:** Source-stated project assumption  
**Date:** 2026-11-24

**Context:** The updated requirements document describes one assumed police station in Adama.

**Decision:** Keep the first version limited to one assumed station.

**Consequence:** Multi-station support is outside the first version unless the assignment provider changes the scope.

## D-005 — Complaint and case are distinct records

**Status:** Confirmed by requirements workflow  
**Date:** 2026-11-24

**Context:** The requirements distinguish complaint registration/review from formal case creation.

**Decision:** A formal case is created from a complaint approved for case creation. A rejected complaint remains stored with its decision reason.

**Consequence:** Preserve this relationship in database design and enforce it in application logic.

## D-006 — Workflow and roles are proposed, not official

**Status:** Source-stated limitation  
**Date:** 2026-11-24

**Context:** The requirements document explicitly says its workflow and roles are proposed software-design assumptions, not verified internal police procedures.

**Decision:** Describe them as proposed application workflow and software roles. Seek confirmation from the assignment provider before presenting them as official.

## D-007 — Application is not a legal decision-maker

**Status:** Confirmed by requirements  
**Date:** 2026-11-24

**Decision:** The system records investigation progress and documented workflow outcomes. It does not make legal judgments or replace decisions belonging to prosecutors, courts, or other authorized bodies.

## D-008 — Database technology must be verified before change

**Status:** Pending repository audit  
**Context:** The proposal mentions MySQL, while the developer has requested PHP/Laravel-only application development and the existing project may already be configured for a database.

**Decision:** Inspect `.env` safely, `config/database.php`, migrations, and actual database state. Do not switch databases or reset data automatically.

## D-009 — Frontend dependency additions require justification

**Status:** Confirmed developer constraint  
**Decision:** Prefer existing Blade/CSS assets and native browser capabilities. Do not add Bootstrap or another frontend dependency automatically; inspect what the project already uses and ask before unnecessary additions.

## D-010 — Phase 1 audit completed without modifying application code

**Status:** Completed 2026-11-24  
**Date:** 2026-11-24

**Context:** The master instructions required a read-only audit before any modification.

**Decision:** Perform read-only inspection and safe baseline checks only; update project documentation; present findings; await developer approval before implementation.

**Consequence:** The audit identified the review workflow route defect, missing implementation modules, and authorization gaps. Implementation remains blocked pending developer review.

## D-011 — Existing database technology confirmed as SQLite

**Status:** Verified 2026-11-24  
**Date:** 2026-11-24

**Context:** Requirements proposal mentioned MySQL; developer preferred minimal changes and the repository already used SQLite.

**Decision:** Preserve the existing SQLite configuration unless the developer explicitly approves a database change.

**Consequence:** No database change was made during the audit.

## D-012 — Route parameter names must match controller argument names

**Status:** Confirmed 2026-11-24  
**Date:** 2026-11-24

**Context:** A route defined as `/investigation-reports/{investigation_report}/review` dispatched to `review(Request $request, InvestigationReport $report)`. Implicit model binding silently resolved an empty model because the names differed, causing incorrect early-return behavior that was hard to spot.

**Decision:** Keep route parameter names and controller method argument names identical (e.g. `{report}` ↔ `$report`, `{case}` ↔ `$case`). Verify with feature tests that exercise the full request.

**Consequence:** Recorded as defect D-002 in `testing.md`. All new routes should follow this naming rule.

## D-013 — Runtime role permission overrides

**Decision:** Keep `config/permissions.php` as the shipped defaults, and store administrator changes as
per-role, per-capability overrides in a `permission_overrides` table. A request-scoped `PermissionService`
merges overrides over defaults; `User::hasPermission()` delegates to it. The editor is administrator-only
and the `manage-permissions` capability is not editable.

**Rationale:** The security guidance requires configurable permissions without hard-coded role checks.
A DB override table keeps the shipped defaults auditable in code while allowing operational adjustment,
and avoids editing PHP files on a running system. Request-scoped caching avoids per-gate queries while
keeping test isolation intact.

## D-014 — Password reset via the Laravel password broker

**Decision:** Use Laravel's built-in password broker (`password_reset_tokens`, 60-minute expiry) and the
framework's `ResetPassword` notification, with `MAIL_MAILER=log` for local/demo use and SMTP for production.
Responses are enumeration-safe and resets are audited.

**Rationale:** Reuses framework primitives without new dependencies, works offline for demos (link in the log),
and satisfies self-service recovery while preserving the audit trail.

## D-015 — Complaint retrieval scoping

**Decision:** Record `registered_by` on complaints and scope complaint retrieval with two capabilities:
`view-all-complaints` and `view-assigned-complaints`. Officers without either see only complaints they
registered; investigators/lead detectives also see complaints linked to their assigned cases.

**Rationale:** Aligns with least privilege and FR-15 without introducing a separate ACL table. Scoping is
applied to list, show, review, conversion, and dashboard aggregates so counts cannot leak hidden records.

## D-016 — Search filters and relevance ordering

**Decision:** Implement complaint/case search with validated `search`, `status`, `from`, and `to` query
parameters. Order results by a `CASE` expression that ranks exact reference matches first, then reference
prefix matches, then other field matches, with recency as the tiebreaker. Date filters use `whereDate` on
`complaints.incident_date` and `cases.opened_at`.

**Rationale:** Delivers useful filtering without a search service or new dependencies, keeps counts and
scoping intact, and gives predictable ordering for references users search by most.

## D-017 — Per-user permission overrides

**Decision:** Add `user_permission_overrides` and resolve capabilities as per-user override → role override → config default in `PermissionService`. Administrators edit per-user overrides at `/staff/{staff}/permissions`; `manage-permissions` is not delegatable.

**Rationale:** Supports least-privilege exceptions without creating new roles or hard-coded checks, while preserving the single gate interface used throughout the app.

## D-018 — Dependency-free TOTP two-factor authentication

**Decision:** Implement RFC 6238 TOTP locally (`App\Support\Totp`, base32 secret, HMAC-SHA1, 6 digits, 30-second period, ±1 period window). Encrypt secrets and recovery codes with `APP_KEY`; hash recovery codes and make them single-use. Challenge attempts are rate-limited and audited.

**Rationale:** Adds a second authentication factor without new packages or external services, and keeps operations demonstrable offline.

## D-019 — SQLite FTS5 index with LIKE fallback

**Decision:** Create FTS5 virtual tables and sync triggers for complaints and cases, query them with `MATCH` and rank by `CASE` + `bm25`, and fall back to the previous `LIKE` query when the driver is not SQLite or the term has no alphanumeric characters.

**Rationale:** Provides real full-text indexing on the project's SQLite database without search infrastructure, while keeping portability and the deterministic reference-first ordering established earlier.

## D-020 — Security hardening and bulk demo data

**Decision:** Add application-level security headers to all web responses and an optional `FORCE_HTTPS`
301 redirect middleware that runs before session/CSRF middleware. Add an idempotent 100-record bulk
fictional seeder for performance checks. Also allow `lead_detective` as an assignable case role.

**Rationale:** These changes close a role-assignment gap and provide demonstrable hardening and scale
verification without introducing dependencies or touching infrastructure configuration. TLS termination
and production cookie settings remain deployment concerns.

## D-021 — Record-level checks for capability-only endpoints

**Decision:** Apply the `view-case` gate to case status changes, investigation report reviews, and case assignments in addition to their capability middleware.

**Rationale:** A per-user capability override could otherwise grant write access to a case the user cannot see. These three endpoints were the only workflow actions that lacked the record-level check already used by evidence, activities, people, and reports.

## D-022 — Complaint phone and date constraints

**Decision:** Normalize complainant phone numbers to `+251` plus exactly 9 digits, and restrict incident dates to today or earlier using both browser constraints and server validation.

**Rationale:** Matches the demo station's Ethiopian numbering convention without adding fields or dependencies, and keeps stored records grounded in present/past events.

## D-023 — Curated dark theme

**Decision:** Implement dark mode by overriding the existing semantic CSS tokens under `[data-theme="dark"]`, with independently chosen backgrounds, surfaces, borders, text, accents, and status colors. Apply the theme before CSS load, persist the choice in `localStorage`, default to light when unset, and force light tokens for printing.

**Rationale:** Keeps one component library and one layout while avoiding a simple colour inversion that would break hierarchy, contrast, and status meaning.

## D-024 — Complaint registration belongs to Reporting Officers

**Decision:** Gate `complaints.create` and `complaints.store` behind a `register-complaints` capability, defaulted to `reporting_officer` only, and hide the registration button from other roles.

**Rationale:** `project.md` FR-04 and the role table assign intake to the Reporting Officer. Allowing the administrator or other roles to register complaints was a deviation from the documented workflow; the capability remains configurable if the deployment needs a different assignment.

## D-025 — Supervisor role scope

**Decision:** Remove case-work capabilities (`record-activities`, `add-evidence`, `handle-evidence`, `manage-people`, `submit-report`) from the supervisor role. Supervisors assign, monitor, set status, review reports, and use oversight/reporting views.

**Rationale:** `project.md` assigns investigation recording to assigned investigators, report submission to the Lead Detective, and supervisory review to the Investigation Supervisor. The extra capabilities were inherited from earlier shortcuts and contradicted the documented separation of duties.

## D-026 — Role-aware UI visibility beyond action buttons

**Decision:** Gate informational dashboard cards and secondary navigation with the same capabilities as the underlying screens, using `User::hasCaseAccess()` (`view-all-cases` or an assignment) for case-related UI. Hide links whose target would be forbidden under runtime permission overrides.

**Rationale:** Hiding forms is not enough if the surrounding navigation and metrics still advertise functionality a role cannot use. This keeps the UI consistent with the permission map without adding new permissions or routes.
