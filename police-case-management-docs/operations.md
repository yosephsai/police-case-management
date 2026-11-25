# Operations, Backup and Recovery

> Practical notes for the local Police Crime Reporting and Case Management System.
> This application records operational and investigative data; it does not make legal decisions.

## 1. Environment

- Application: Laravel 13.43.0, PHP 8.3.
- Database: SQLite at `database/database.sqlite`.
- Configuration: `.env` (`DB_CONNECTION=sqlite`). Do not commit `.env`.

## 2. Backing up the database

Stop the application (or at least avoid writes) before a plain file copy.

```bash
# Recommended: consistent online backup using the sqlite3 CLI
sqlite3 database/database.sqlite ".backup 'storage/backups/database-$(date +%Y%m%d-%H%M%S).sqlite'"

# Alternative: simple file copy while the app is stopped
cp database/database.sqlite storage/backups/database-$(date +%Y%m%d-%H%M%S).sqlite
```

Keep `storage/backups/` out of version control and restrict permissions.

Evidence attachments are stored on the private disk at `storage/app/private/evidence`. Back this directory up together with the database so attachment metadata and files stay consistent.

## 3. Restoring from a backup

```bash
# Stop the application first
cp storage/backups/database-YYYYmmdd-HHMMSS.sqlite database/database.sqlite
php artisan migrate:status   # confirm expected migrations are present
```

Do not delete the current database until the restored copy is verified.

## 4. Migrations

- Migrations are additive and incremental. Do not edit applied migrations; add new ones.
- Apply with `php artisan migrate`. Check `php artisan migrate:status` first.
- The project has corrective rebuild migrations (see `database/migrations/2026_11_24_*`) used to fix foreign keys on SQLite. Keep them; they are part of the applied history.

## 5. Demonstration data (fictional only)

A fictional demo seeder exists:

```bash
php artisan db:seed --class=DemoDataSeeder
```

- It is idempotent and creates fixed fictional users, a complaint, a case, assignment, activity, evidence, person, and an accepted report.
- Demo accounts (fictional, local only), password `password`:
  - `admin@demo.test` (System Administrator)
  - `commander@demo.test` (Station Commander)
  - `reporting@demo.test` (Reporting Officer)
  - `reviewing@demo.test` (Reviewing Officer)
  - `supervisor@demo.test` (Investigation Supervisor)
  - `lead.investigator@demo.test` (Lead Detective)
  - `investigator@demo.test` (Assistant Investigator)
  - `records@demo.test` (Records Officer)
- Never use these accounts or data in an operational deployment.

## 6. Roles in the current build

Roles and their capabilities are defined in `config/permissions.php`:

- `admin` — staff management, audit log, administrative reports; no complaint review or case conversion.
- `station_commander` — audit log, reports, case assignment/status, report review, view all cases.
- `reporting_officer` — register complaints (the only role with `register-complaints` by default).
- `reviewing_officer` — review complaints and convert approved complaints to cases.
- `supervisor` — assign cases, update case status, review reports, view all cases/complaints, audit log, reports. Case-work capabilities (activities, evidence, people, report submission) belong to detectives/investigators.
- `lead_detective` — assigned cases: activities, evidence, people, submit reports.
- `investigator` — assigned cases: activities, evidence, people (no report submission).
- `records_officer` — audit log, reports, view all cases and complaints, evidence handling.

Complaint visibility follows these capabilities: `view-all-complaints` (all records) and
`view-assigned-complaints` (records for assigned cases). Reporting officers without either capability
see only the complaints they registered.

These defaults can be changed at runtime by the System Administrator at **Permissions** (`/permissions`).
Overrides are stored in the `permission_overrides` table and take precedence over `config/permissions.php`;
saving a value equal to the default removes the override. The `manage-permissions` capability itself is
locked to the System Administrator to prevent lockout.

## 7. Recovery checklist

1. Stop the application.
2. Restore the most recent verified backup.
3. Run `php artisan migrate:status` and `php artisan test`.
4. Confirm key pages load (dashboard, complaints, cases, audit log) before resuming use.

## Password reset delivery

Staff can reset their own password at `/forgot-password`. Delivery uses `MAIL_MAILER`:

- Local/demo (`MAIL_MAILER=log`): the reset link is written to `storage/logs/laravel.log`. Retrieve it with
  `grep -A2 "Reset Password" storage/logs/laravel.log | tail`.
- Production: configure SMTP (or another mailer) so links are emailed. Reset links expire after 60 minutes.

Completed resets are recorded in the audit log as `auth.password_reset`. Reset responses are intentionally
generic so the form does not reveal whether an email belongs to an account.

## Two-factor authentication

- Staff can enable TOTP at **Security** (`/two-factor`). Scan the setup link/key with an authenticator app, then confirm a 6-digit code.
- Eight single-use recovery codes are shown once after enabling; regenerate them at any time with the current password.
- Sign-in requires a second step after 2FA is enabled. Challenge attempts are limited to 6 per minute.
- Disabling 2FA requires the current password. Secrets and recovery codes are encrypted with `APP_KEY`; rotating that key requires staff to re-enroll.
- Enable/disable/challenge/recovery events are written to the audit log.

## Per-user permissions

Administrators can open **Staff → Permissions** (`/staff/{staff}/permissions`) to allow, deny, or inherit individual capabilities. Per-user overrides beat role overrides and config defaults. The `manage-permissions` capability cannot be delegated.

## Full-text search

On SQLite, FTS5 indexes with sync triggers back complaint/case free-text search (`complaints_fts`, `cases_fts`). Other database drivers automatically fall back to `LIKE` matching. Migrations rebuild the indexes from existing rows when applied.

## Security headers and HTTPS

Every web response includes `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
`X-XSS-Protection`, and `Permissions-Policy` headers. Set `FORCE_HTTPS=true` in `.env` to redirect all HTTP
requests to HTTPS with a 301 before session and CSRF middleware run. Session cookie defaults are documented
in `.env.example`; set `SESSION_SECURE_COOKIE=true` for production TLS deployments.

## Bulk fictional dataset

For performance checks, run `php artisan db:seed --class=BulkDemoDataSeeder`. It creates 100 fictional
complaints and cases with status histories, assignments, activities, evidence, people, and reports. It is
idempotent (deterministic reference numbers) and uses only fictional data. Tests run it via
`$this->seed(BulkDemoDataSeeder::class)`.


## Interface assets

The UI is served from `public/css/app.css` and `public/js/app.js`. No CDN or npm build is needed.
Desktop users can collapse the sidebar with the topbar toggle; the preference is stored in the browser.
On small screens the same control opens the sidebar drawer; click the overlay, the close button, or press Escape to dismiss it.
Confirmations for destructive actions are handled by `public/js/app.js` through `data-confirm` attributes.


## Complaint phone numbers and dates

The registration form presents a fixed `+251` prefix and accepts exactly 9 digits after it. Stored numbers use the form `+251XXXXXXXXX` (13 characters). Incident dates cannot be in the future; both the browser and server enforce this.


## Theme

Use the sun/moon control in the topbar (or the corner control on sign-in screens) to switch between light and dark themes. The choice is stored in the browser. Light mode is the default until the user chooses otherwise. Printed documents always use the light palette.
