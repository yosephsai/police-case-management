# Security and Data Protection Guide

## 1. Security posture

This application is an academic prototype for fictional demonstration data. It is not approved for real confidential police information or operational deployment.

The requirements call for authentication, role/permission-controlled access, secure password storage, confidentiality, record integrity, auditability, and a basic backup/recovery approach.

## 2. Authentication

- Require authentication for protected application functions.
- Use Laravel's established authentication facilities and password hashing.
- Do not implement custom password cryptography.
- Protect state-changing routes with Laravel CSRF protection.
- Ensure logout invalidates the session appropriately.
- Review session configuration for the target environment.
- Do not expose credentials, session IDs, or secrets in logs or documentation.

## 3. Authorization

- Enforce authorization on the server for every protected read and write action.
- Use Laravel policies, gates, middleware, or a justified existing role/permission implementation.
- Inspect existing authorization before adding a new system.
- Keep permissions configurable where practical.
- Do not assume that System Administrator automatically has authority to approve complaints or make case decisions.
- Do not rely on hiding buttons or navigation links.
- Apply record-level restrictions where users should only access assigned or otherwise permitted records.
- Test both allowed and denied access paths.

## 4. Input validation and output handling

- Validate all incoming data on the server.
- Use Laravel validation or Form Request classes where useful.
- Use Eloquent or parameterized query mechanisms; never concatenate untrusted input into SQL.
- Use Blade's escaped output by default.
- Avoid `{!! !!}` for user-controlled content.
- Validate route identifiers and ownership/relationship constraints.
- Return clear validation errors without disclosing internal details.

## 5. Data confidentiality

- Use fictional sample data only.
- Do not use real names, addresses, phone numbers, case details, or evidence information in demonstrations.
- Show sensitive information only to authorized users.
- Avoid logging full sensitive payloads.
- Do not expose `.env`, backups, database files, debug output, or private storage files through the public web directory.
- Keep secrets out of Git and all project documentation.

## 6. Integrity and workflow enforcement

- A complaint and a case are separate records.
- A case can be created from a complaint only after the required approval is recorded.
- Retain rejected complaints and their recorded reasons.
- Enforce one designated lead investigator per case while allowing additional investigators.
- Validate permitted status transitions on the server.
- Use database transactions when multiple related writes must succeed or fail together.
- Do not silently overwrite important decisions, report review outcomes, status history, or evidence handling history.
- Record the responsible user and timestamp for important actions.
- Do not treat an investigation report as a legal judgment.

## 7. Auditability

At minimum, the requirements call for recording important actions such as:
- Record creation.
- Record updates.
- Assignments.
- Case status changes.

Where applicable, record actor, action, affected record, timestamp, and a concise non-sensitive description. Avoid storing passwords, tokens, or unnecessary sensitive payloads in audit records.

Inspect existing audit/logging code before introducing another mechanism.

## 8. File handling

If file uploads are implemented:
- Confirm they are within approved project scope.
- Validate file size, extension, and MIME type server-side.
- Generate safe server-side filenames.
- Store private files outside the public web root or use controlled private storage.
- Authorize downloads.
- Do not trust a client-supplied path or filename.
- Do not execute uploaded files.

## 9. Database and backup

- Inspect current database configuration and migration state before changes.
- Do not run destructive migrations or database resets without explicit approval.
- Define and document a backup and recovery approach before any real operational use.
- Keep backups protected and outside public web access.
- Test restoration in a safe environment before claiming recovery readiness.

## 10. Environment and deployment

- Keep debug mode and detailed error pages disabled in any deployed environment.
- Keep secrets in environment configuration, not source control.
- Use HTTPS for any non-local deployment.
- Apply least privilege to database credentials and file permissions.
- Do not claim production readiness based only on local tests.

## 11. Security verification checklist

- [ ] Protected routes require authentication.
- [ ] Each sensitive action has server-side authorization.
- [ ] Users cannot approve their own actions unless the approved requirements explicitly allow it.
- [ ] Unauthorized record access is denied.
- [ ] Validation rejects malformed and unauthorized relationship IDs.
- [ ] CSRF protection is active for web forms.
- [ ] Passwords use Laravel hashing.
- [ ] User content is escaped.
- [ ] Important changes are traceable.
- [ ] Secrets are not committed or printed.
- [ ] Fictional data is used.
- [ ] Backup/recovery plan is documented before any real operational use.
