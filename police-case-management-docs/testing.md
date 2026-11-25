# Testing and Verification Plan

## 1. Testing principles

- Verify behavior, not just file existence.
- Use fictional data only.
- Test both authorized and unauthorized behavior.
- Never run destructive tests against important local data.
- Do not claim a test passed unless it was actually executed and passed.
- Record environment, command, result, and relevant limitation.

## 2. Existing project baseline

The repository must be audited before a baseline can be declared.

Potential safe inspection commands, to be selected after reading project instructions:

```bash
php artisan --version
php artisan route:list
php artisan about
php artisan migrate:status
```

Do not run migrations, resets, seeders, or destructive database commands during the initial read-only audit.

Check `composer.json` and the existing test setup before choosing test commands.

## 3. Functional test scenarios

### Authentication and permissions

- Guest cannot access protected pages.
- Authenticated user can access permitted pages.
- User without a permission cannot perform the protected action.
- User cannot access a record outside their permitted scope.

### Complaints

- Reporting Officer can register a complaint.
- Complaint receives a unique reference.
- Invalid data is rejected with useful validation messages.
- Authorized user can retrieve a complaint by reference.
- Reviewing Officer can record approval or rejection with a reason.
- Rejected complaint remains retrievable.
- Unauthorized user cannot approve or reject.

### Cases and assignments

- Case creation is possible only from an approved complaint.
- Case links to its originating complaint.
- Duplicate case creation from the same complaint is prevented according to the approved design.
- Supervisor can assign one lead investigator and additional investigators.
- Unauthorized users cannot change assignments.

### Investigation

- Assigned investigators can record dated activities within their permissions.
- Evidence records can be created by authorized users.
- Evidence handling/transfer history is retained.
- Multiple victims, suspects, and witnesses can be associated with a case.

### Reports and status

- Lead investigator can submit a report.
- Supervisor can accept it for referral or return it for further investigation.
- Review decision and reason are recorded.
- Case status changes are validated and historized.
- Unauthorized status transitions are rejected.

### Search, reports, and audit

- Search results respect user permissions.
- Administrative summaries use the expected data.
- Important create/update/assignment/status actions are recorded with actor and time.

## 4. Non-functional verification

- Validation handles invalid input safely.
- User-controlled output is escaped.
- CSRF protection is active for web forms.
- Passwords use Laravel's hashing.
- Sensitive data is not exposed to unauthorized users.
- Important records are not silently overwritten.
- Common pages and searches perform reasonably in the target local environment.
- Backup and recovery approach is documented before any real operational use.

## 5. Test log

| Date | Feature / requirement | Test or command | Result | Evidence / notes |
|---|---|---|---|---|
| 2026-11-24 | Baseline — repository health | `php artisan --version`, `php artisan route:list`, `php artisan migrate:status`, `php artisan test`, `php -l` | PASS / OK | Laravel 13.43.0; 18 routes; all 11 migrations `Ran`; `php artisan test` → 2 passed, 2 assertions. No application code changed. |
| 2026-11-24 | Documentation baseline | Read-only audit of docs and code | PASS / OK | Audit findings recorded in `state.md`. |
| 2026-11-24 | Implementation task — complaint review route | `php -l routes/web.php`, `php artisan route:list`, `php artisan test` | PASS / OK | Added missing `complaints.review` route; route now listed; tests still pass (2 passed, 2 assertions). |
| 2026-11-24 | Implementation task — complaint search | `php -l app/Http/Controllers/ComplaintController.php`, `php artisan test` | PASS / OK | Added `search` filter and view form; syntax OK; tests pass (2 passed, 2 assertions). |
| 2026-11-24 | Implementation task — complaint review behavior tests | `php artisan test` | PASS / OK | Added `tests/Feature/ComplaintReviewTest.php` (4 tests); suite now runs 6 tests, 16 assertions, all passed. |
| 2026-11-24 | FR-07/FR-08 case assignment | `php artisan test` | PASS | `CaseAssignmentTest` (2 tests): assignment and duplicate prevention. |
| 2026-11-24 | FR-09 investigation activities | `php artisan test` | PASS | `InvestigationActivityTest` (2 tests): recording and validation. |
| 2026-11-24 | FR-06 complaint-to-case | `php artisan test` | PASS | `ComplaintCaseTest` (2 tests): approved conversion and rejection of non-approved. |
| 2026-11-24 | FR-10 evidence and handling | `php artisan test` | PASS | `EvidenceTest` (2 tests): evidence creation and handling history. |
| 2026-11-24 | FR-11 case people | `php artisan test` | PASS | `CasePersonTest` (1 test). |
| 2026-11-24 | FR-12/FR-13 investigation reports | `php artisan test` | PASS | `InvestigationReportTest` (4 tests) after fixing the route-model binding defect D-002. |
| 2026-11-24 | FR-14 case status history | `php artisan test` | PASS | `CaseStatusTest` (3 tests): valid transition, disallowed transition, unauthorized user. |
| 2026-11-24 | Cases module pages | `php artisan test`, `php artisan view:cache` | PASS | `CasesPageTest` renders index/show with related records; Blade compilation OK. |
| 2026-11-24 | FR-17 audit log UI | `php artisan test` | PASS | `AuditLogTest` (2 tests): supervisor allowed, investigator forbidden. |
| 2026-11-24 | FR-02/FR-03 staff management | `php artisan test` | PASS | `StaffManagementTest` (4 tests): deactivation, self-protection, inactive login block, forbidden access. |
| 2026-11-24 | FR-16 dashboard summaries | `php artisan test` | PASS | `DashboardTest` (1 test). |
| 2026-11-24 | Full suite after Milestones 9–13 | `php artisan test` | PASS | **30 tests, 84 assertions, all passed.** |
| 2026-11-24 | Demo seeder + Phase 8 security review | `php artisan test` | PASS | `DemoDataSeederTest` verified fictional data and idempotency; full suite **31 tests, 94 assertions, all passed**. |
| 2026-11-24 | Login throttling | `php artisan test` | PASS | `LoginThrottleTest`: 5 allowed attempts, 6th returns 429. |
| 2026-11-24 | CSV report exports (FR-16) | `php artisan test` | PASS | `ReportExportTest` (3 tests): cases CSV, complaints CSV, investigator forbidden; audit row written. |
| 2026-11-24 | Case access scoping (FR-15) | `php artisan test` | PASS | `CaseAccessTest` (4 tests): assigned view allowed, unassigned forbidden, index filtered, supervisor sees all. |
| 2026-11-24 | Full suite after Phase 8 hardening | `php artisan test` | PASS | **39 tests, 120 assertions, all passed.** |
| 2026-11-24 | Case write scoping (`work-on-case`) | `php artisan test` | PASS | Unassigned investigators receive 403 for activities/evidence; assigned investigators pass. Full suite **40 tests, 124 assertions, all passed**. |
| 2026-11-24 | Eight-role permission model (FR-03) | `php artisan test` | PASS | `PermissionsTest` (7 tests): admin denied complaint review/convert; reviewing officer allowed; station commander audit access; records officer read-only. |
| 2026-11-24 | Evidence attachments (FR-10) | `php artisan test` | PASS | `EvidenceAttachmentTest` (3 tests): upload+download, unassigned download forbidden, invalid type rejected. |
| 2026-11-24 | Printable reports (FR-16) | `php artisan test` | PASS | `PrintableReportTest` (4 tests): case print view, access control, printable summary + audit. |
| 2026-11-24 | Full suite after role/attachment/print work | `php artisan test` | PASS | **54 tests, 153 assertions, all passed.** |
| 2026-11-24 | Editable role permissions (FR-03) | `php artisan test --filter=PermissionOverrideTest` | PASS | 6 tests: admin-only editor, grant/revoke overrides, default pruning, locked `manage-permissions`. |
| 2026-11-24 | Full suite after permission editor | `php artisan test` | PASS | **60 tests, 170 assertions, all passed.** |
| 2026-11-24 | Self-service password reset (FR-01) | `php artisan test --filter=PasswordResetTest` | PASS | 6 tests: link sent, enumeration safety, valid reset + audit, invalid token, forms. |
| 2026-11-24 | Full suite after password reset | `php artisan test` | PASS | **66 tests, 192 assertions, all passed.** |
| 2026-11-24 | Complaint retrieval scoping (FR-15) | `php artisan test --filter=ComplaintAccessTest` | PASS | 5 tests: officer sees own only, cross-officer show forbidden, supervisor sees all, investigator sees assigned-case complaints, registration attribution. |
| 2026-11-24 | Full suite after complaint scoping | `php artisan test` | PASS | **71 tests, 204 assertions, all passed.** |
| 2026-11-24 | Search filters and relevance (FR-15) | `php artisan test --filter=SearchFilterTest` | PASS | 5 tests: complaint/case relevance ordering, status + date filters, invalid range rejected. |
| 2026-11-24 | Full suite after search work | `php artisan test` | PASS | **76 tests, 219 assertions, all passed.** |
| 2026-11-24 | Per-user permission delegation | `php artisan test --filter=UserPermissionDelegationTest` | PASS | 6 tests: grant, deny, inherit, access control, locked capability, UI. |
| 2026-11-24 | Two-factor authentication | `php artisan test --filter=TwoFactorTest` | PASS | 8 tests: enable, invalid code, login challenge, single-use recovery code, disable, regeneration, direct login fallback. |
| 2026-11-24 | SQLite FTS5 full-text index | `php artisan test --filter=FullTextSearchTest` + `--filter=SearchFilterTest` | PASS | 10 tests: index presence, description matching, trigger sync, case FTS, punctuation fallback, plus prior ranking/filter tests. |
| 2026-11-24 | Full suite after final optional features | `php artisan test` | PASS | **95 tests, 303 assertions, all passed.** |
| 2026-11-24 | Lead Detective assignment (FR-08) | `php artisan test --filter=CaseAssignmentTest` | PASS | 3 tests: investigator lead assignment, duplicate guard, Lead Detective assignment + activity access. |
| 2026-11-24 | Security headers + HTTPS redirect | `php artisan test --filter=SecurityHeadersTest` | PASS | 3 tests: headers present, HTTP→HTTPS 301 when forced, HTTPS not redirected. |
| 2026-11-24 | Bulk fictional dataset | `php artisan test --filter=BulkSeederTest` | PASS | 3 tests: 100-record dataset created, lists/dashboard work, seeder idempotency. |
| 2026-11-24 | Full suite after hardening | `php artisan test` | PASS | **102 tests, 336 assertions, all passed.** |
| 2026-11-24 | Complete UI/UX redesign | `php artisan test --filter=UiConsistencyTest` | PASS | 5 tests: shared shell on every authenticated screen, active navigation, permission-aware links, auth layout, global validation errors. |
| 2026-11-24 | Full suite after redesign | `php artisan test` | PASS | **107 tests, 452 assertions, all passed.** |
| 2026-11-24 | Local UI smoke test | `php artisan serve` + HTTP requests | PASS | Login 200, login POST redirects to dashboard, dashboard 200 with shared sidebar/topbar, CSS and JS assets 200, no Bootstrap references. |
| 2026-11-24 | Full UI coverage + dashboard overview | `php artisan test --filter=UiConsistencyTest` | PASS | 8 tests: all form/detail screens use the shared shell, print shell separates correctly, dashboard overview sections render. |
| 2026-11-24 | Full suite after redesign follow-up | `php artisan test` | PASS | **110 tests, 495 assertions, all passed.** |
| 2026-11-24 | Record-level authorization (status/review/assignment) | `php artisan test --filter=RecordLevelSecurityTest` | PASS | 3 tests: invisible cases cannot be status-changed, assigned, or have reports reviewed. |
| 2026-11-24 | Full suite after final hardening | `php artisan test` | PASS | **113 tests, 503 assertions, all passed.** |













The complaint review behavior tests cover: authorized approval, rejection with reason, rejection reason validation, and double-review prevention.

## 6. Defect log

Record each defect with:
- ID.
- Date discovered.
- Affected requirement.
- Reproduction steps.
- Expected behavior.
- Actual behavior.
- Severity.
- Fix and verification result.

### D-001 — Complaint review workflow is unreachable (missing route; broken view reference)

- **Date:** 2026-11-24
- **Affected requirement:** FR-05 (partial; blocks approval/rejection workflow).
- **Reproduction steps:** Log in as an authorized user, open a pending complaint from complaints index, open complaint details. The details view calls `route('complaints.review', $complaint)`.
- **Expected behavior:** A Reviewing Officer could submit the decision and reason.
- **Actual behavior:** No route named `complaints.review` exists in `routes/web.php`; `ComplaintController@review` has no route and is effectively dead code. The Blade view would throw a missing-route error for authorized users viewing pending complaints.
- **Severity:** High for FR-05 acceptance; medium for overall workflow progress.
- **Fix and verification result:** Implemented on 2026-11-24: missing review route added in `routes/web.php`; route-level verification passed. Behavior-level workflow tests still outstanding.

### D-002 — Investigation report review never updated the record ("already reviewed")

- **Date:** 2026-11-24
- **Affected requirement:** FR-13 (supervisory report review); blocked FR-12/FR-13 verification.
- **Reproduction steps:** As a supervisor, POST to `investigation-reports/{investigation_report}/review` for a pending report.
- **Expected behavior:** Report review status changes to `accepted` or `returned`, with reviewer and notes saved.
- **Actual behavior:** Response redirected with "This investigation report has already been reviewed." and the row stayed `pending`; the "returned requires notes" validation error was never shown.
- **Root cause:** The route parameter `{investigation_report}` did not match the controller method argument `$report`, so Laravel's implicit model binding did not resolve the record and an empty model (`review_status === null`) was injected, triggering the early-return branch.
- **Severity:** High for FR-12/FR-13.
- **Fix and verification result:** Renamed the route parameter to `{report}` to match the controller variable. All 4 investigation report tests pass; full suite 30 tests / 84 assertions passing.
- **Lesson recorded:** Route parameter names must match controller method argument names for implicit model binding (see decisions D-012).

| 2026-11-24 | Print shell + login redesign | `php artisan test --filter=UiConsistencyTest` + HTTP smoke test | PASS | Print screens keep the shared sidebar and expose a print bar; login uses wider fields; live `/reports/summary/print` returns 200 with sidebar present. Full suite **113 tests, 507 assertions**. |

| 2026-11-24 | Complaint phone/date validation | `php artisan test --filter=ComplaintInputValidationTest` | PASS | 5 tests: 9-digit normalization, full +251 acceptance, invalid numbers rejected, future date rejected, form constraints rendered. Full suite **118 tests, 531 assertions**. |

| 2026-11-24 | Dark mode | `php artisan test --filter=ThemeTest` + HTTP asset check | PASS | 3 tests: toggle on login/auth screens, dark token definitions, persisted theme JS and system preference. Live CSS serves dark tokens. Full suite **121 tests, 544 assertions**. |

| 2026-11-24 | All-role workflow verification | `php artisan test --filter=RoleWorkflowTest` | PASS | 8 tests simulate real tasks for all eight roles; no application defects found. Harness issues (chained response calls, missing evidence actor) were corrected. Full suite **129 tests, 603 assertions**. |

| 2026-11-24 | Complaint registration role (FR-04) | `php artisan test --filter=ComplaintRegistrationRoleTest` | PASS | 3 tests: Reporting Officer can register; all other roles get 403 and no button. Full suite **132 tests, 628 assertions**. |

| 2026-11-24 | UI role visibility audit | `php artisan test --filter=UiRoleVisibilityTest` | PASS | 5 tests: reporting officer hidden case/review metrics, assigned investigator sees case metrics, admin oversight metrics only, no 403 staff link, invisible complaint link hidden. Full suite **137 tests, 658 assertions**. |
