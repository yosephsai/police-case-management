# Implementation Plan

## Planning rules

This plan is based on the updated requirements proposal and the developer's clarified goal of learning Laravel through the existing codebase.

**Do not begin by rebuilding.** First audit the repository and preserve existing working features. The phases below are a target sequence, not a claim that nothing has been implemented.

## Phase 0 — Repository audit and grounding

**Goal:** Understand what already exists.

**Status:** COMPLETED on 2026-11-24 (read-only audit). Developer approved implementation; work proceeding.

Tasks:
- Read existing `AGENTS.md` and `CLAUDE.md`.
- Inspect Laravel/PHP versions and dependencies.
- Inspect database configuration and migration status safely.
- Review models, controllers, routes, middleware, views, migrations, seeders, and tests.
- Identify existing complaint and case functionality.
- Map FR-01 through FR-17 to implementation and tests.
- Identify defects, duplication, and gaps.

Exit criteria:
- Audit report completed.
- Existing working code identified.
- Requirements matrix completed.
- Developer approves proposed next changes.

## Phase 1 — Foundation and database

**Goal:** Establish a reliable application foundation without disrupting existing data.

Tasks:
- Confirm the current database choice and configuration.
- Review existing schema and migrations.
- Define necessary entities and relationships only after the audit.
- Review authentication foundation.
- Plan role/permission enforcement.
- Define migration and rollback approach.

Exit criteria:
- Database design reviewed.
- Migrations are incremental and safe.
- Existing data impact is understood.
- Tests cover the foundation.

## Phase 2 — Authentication and staff management (FR-01 to FR-03)

**Status:** SUBSTANTIALLY COMPLETE (2026-11-24) — login/logout, inactive-account blocking, staff CRUD with activate/deactivate, password update, last-admin guards, and gate-based authorization. Role set remains limited to three roles.

Tasks:
- Ensure protected routes require login.
- Implement or improve staff account management.
- Implement configurable roles/permissions using the simplest sound approach for the current codebase.
- Enforce authorization on the server.
- Test permitted and denied actions.

## Phase 3 — Complaint registration and review (FR-04, FR-05)

**Status:** BASIC COMPLETE (2026-11-24) — registration, unique reference, search, review decisions with reason, rejected complaints retained, audit writes; tested.

Tasks:
- Register complaints.
- Generate unique complaint references.
- Validate submitted information.
- Retrieve complaints by reference and authorized search.
- Record review decisions and reasons.
- Retain rejected complaints.

## Phase 4 — Case creation and assignment (FR-06 to FR-08)

**Status:** BASIC COMPLETE (2026-11-24) — approved-complaint-only conversion, complaint link, single lead plus additional investigators, assignment audit; tested.

Tasks:
- Create a case only from an approved complaint.
- Preserve the relationship to the originating complaint.
- Assign one lead investigator and additional investigators.
- Record assignment details and dates.
- Enforce supervisor permissions and assignment integrity.

## Phase 5 — Investigation records (FR-09 to FR-11)

**Status:** BASIC COMPLETE (2026-11-24) — activities, evidence metadata, handling history, victims/suspects/witnesses, audit; tested.

Tasks:
- Record dated investigation activities.
- Record evidence metadata.
- Record basic evidence handling/transfer history.
- Associate multiple victims, suspects, and witnesses with a case.
- Enforce access and traceability.

## Phase 6 — Reports and supervisory review (FR-12, FR-13)

**Status:** BASIC COMPLETE (2026-11-24) — report submission, accept/return with required notes, decision storage, audit; tested. No legal-judgment claims are made by the system.

Tasks:
- Allow the lead investigator to submit an investigation report.
- Allow the supervisor to accept it for referral or return it for further investigation.
- Record the review decision and reason.
- Do not imply that the report is a legal judgment.

## Phase 7 — Status, search, reports, audit (FR-14 to FR-17)

**Status:** BASIC COMPLETE (2026-11-24) — validated case status transitions with history, complaint/case search, dashboard summaries, audit log UI; tested. Record-level scoping and exportable reports remain.

Tasks:
- Display current case status.
- Maintain status history.
- Restrict search/retrieval by permissions.
- Provide basic administrative summaries.
- Record important user actions.

## Phase 8 — Testing, security and refinement

**Status:** IN PROGRESS (2026-11-24) — 30 feature tests / 84 assertions passing. Remaining: final authorization matrix review, fictional demo seeder, backup/recovery documentation, responsive polish.

Tasks:
- Test the end-to-end complaint-to-case workflow.
- Test authorization boundaries and invalid inputs.
- Review data integrity and auditability.
- Improve usability and responsive layout.
- Document backup/recovery approach.
- Prepare a demonstration using fictional data.

## Priority rule

The requirements document identifies login, complaint registration, complaint review, case creation, assignment, and basic status tracking as priorities under limited development time. Preserve this priority when scheduling.

## Completion rule

A phase is complete only when:
- Its expected behavior exists.
- Relevant tests or reproducible manual checks pass.
- Authorization and validation are reviewed where relevant.
- Documentation is updated.
- Known limitations are recorded.
