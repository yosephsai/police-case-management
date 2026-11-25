# Project Definition

## 1. Project identity

**Name:** Police Crime Reporting and Case Management System  
**Proposed location:** Adama, Ethiopia  
**Initial scope:** One assumed police station  
**Project type:** Academic prototype and practical Laravel learning project

This project is an information-management application for organizing complaint and case records. It is not an officially approved operational police system.

## 2. Purpose

The purpose is to develop a web application that helps authorized staff register complaints, review complaints, create formal case records from approved complaints, assign investigators, record investigation activity and evidence information, and monitor case progress.

A parallel learning objective is to become familiar with PHP and Laravel by implementing a real, bounded application and understanding the framework concepts used.

## 3. Project principles

1. Preserve and assess the existing Laravel codebase before changing it.
2. Keep the application a conventional Laravel monolith.
3. Prefer Laravel conventions and readable code over unnecessary abstractions.
4. Implement only the agreed first-version scope.
5. Use fictional demonstration data only.
6. Treat the workflow and software roles as proposed design assumptions, not verified police procedures.
7. The application records information and workflow decisions; it does not make legal decisions or replace official records.
8. Do not treat an investigator's report as a legal judgment. Decisions belonging to prosecutors, courts, or other authorized bodies remain outside the application's authority.

## 4. Technology constraints

The developer clarified that the application stack is PHP with Laravel only.

Use the existing project's actual configuration and dependencies. Prefer:
- PHP and Laravel.
- Laravel routing, controllers, middleware, validation, authentication, authorization, Eloquent ORM, migrations, and Blade.
- HTML and CSS within Blade views.
- Small amounts of vanilla JavaScript only where necessary.
- The database already configured in the repository unless an assessed and approved change is needed.

Do not introduce a separate backend, React, Vue, Angular, Python services, Node.js backend, microservices, or unnecessary infrastructure. Do not install dependencies without explaining why they are needed.

The original proposal mentioned MySQL and Bootstrap. Those are not automatic instructions to replace the current setup: inspect the repository and database configuration first. Use the current database unless a change is explicitly approved. Use Bootstrap only if it is already present or the developer approves adding it.

## 5. Scope

### Included in the first version

- User login and role-based access.
- Staff account management by an authorized administrator.
- Complaint registration and review.
- Case creation from an approved complaint.
- Assignment of one lead investigator and additional investigators.
- Recording investigation activities and reports.
- Evidence details and basic evidence handling history.
- Victims, suspects, and witnesses associated with cases.
- Case status tracking and history.
- Permission-controlled search and retrieval.
- Basic administrative summaries and reports.
- Traceability of important user actions.

### Outside the first version

- Integration with national police, court, prosecutor, or other government databases.
- Biometric identification, automated suspect identification, or facial recognition.
- Live location tracking, advanced forensic analysis, or predictive crime analytics.
- Public self-service portals or mobile applications.
- Multi-station or city-wide deployment.
- Replacement of official paper records or legally mandated procedures.

## 6. Proposed software roles

These are application roles and do not establish police ranks or legal powers.

| Role | Proposed application responsibility |
|---|---|
| System Administrator | Manage staff accounts, roles, basic settings, and system operation. This role does not automatically grant case-decision authority. |
| Station Commander | View station-level summaries and oversee system use, subject to permissions. |
| Reporting Officer | Register incoming complaints and record information supplied at reporting. |
| Reviewing Officer | Review complaints and record a decision and reason. |
| Investigation Supervisor | Assign approved cases, monitor progress, and review submitted reports. |
| Lead Detective | Coordinate the assigned team and submit the investigation report. |
| Assistant Investigator | Record permitted investigation activities and relevant information for assigned cases. |
| Records Officer | Organize and retrieve records according to permissions. |

Permissions must be configurable. Do not scatter hard-coded role-name checks throughout controllers and views.

## 7. Proposed workflow

1. A citizen reports an incident to authorized staff.
2. A Reporting Officer registers the complaint and receives a unique complaint reference.
3. A Reviewing Officer examines the complaint and records a decision and reason.
4. If the complaint is not approved for case creation, retain the complaint and its decision reason.
5. If approved, create a linked formal case record.
6. An Investigation Supervisor assigns one lead investigator and, where needed, additional investigators.
7. Assigned investigators record dated activities, relevant people, and evidence records.
8. Evidence handling or transfers are recorded for traceability.
9. The Lead Detective submits an investigation report.
10. The Investigation Supervisor accepts the report for referral or returns it for further investigation, recording the decision.
11. Record later outcomes only when supported by authorized decisions or documented information.

The exact workflow and terminology must be confirmed with the assignment provider before being described as official procedure.

## 8. Functional requirements

The following IDs and meanings are taken from the updated requirements document.

| ID | Requirement |
|---|---|
| FR-01 | The system shall require users to log in before accessing protected functions. |
| FR-02 | An authorized administrator shall be able to create, update, deactivate, and manage staff accounts. |
| FR-03 | The system shall assign roles and permissions to control access to functions and records. |
| FR-04 | A Reporting Officer shall be able to register a complaint and receive a unique complaint reference. |
| FR-05 | An authorized Reviewing Officer shall be able to review a complaint and record a decision and reason. |
| FR-06 | The system shall allow a case record to be created from a complaint approved for case creation. |
| FR-07 | An Investigation Supervisor shall be able to assign an approved case to investigators. |
| FR-08 | A case shall support one designated lead investigator and additional assigned investigators. |
| FR-09 | Assigned investigators shall be able to record dated investigation activities. |
| FR-10 | Authorized users shall be able to create evidence records and record basic evidence handling or transfer history. |
| FR-11 | The system shall support multiple victims, suspects, and witnesses associated with a case. |
| FR-12 | A Lead Detective shall be able to submit an investigation report for supervisory review. |
| FR-13 | An Investigation Supervisor shall be able to accept a report for referral or return it for further investigation, recording the decision. |
| FR-14 | The system shall display the current case status and maintain a history of status changes. |
| FR-15 | Authorized users shall be able to search and retrieve records according to their permissions. |
| FR-16 | The system shall provide basic administrative summaries and reports. |
| FR-17 | The system shall record important user actions, including record creation, updates, assignments, and status changes. |

## 9. Non-functional requirements

- **Security:** Protected functions require authentication; access is controlled by roles and permissions; passwords are stored securely using established framework facilities.
- **Confidentiality:** Sensitive case information is visible only to authorized users. Demonstrations and testing use fictional data.
- **Integrity:** Important records should not be silently removed or overwritten. Changes to important records should be traceable.
- **Usability:** Forms and screens use clear labels, validation messages, and a consistent layout.
- **Reliability:** Invalid input is handled safely and failures produce clear feedback.
- **Performance:** Common operations respond within a reasonable time on the target environment.
- **Auditability:** Important actions record who performed them and when.
- **Backup:** Define a basic database backup and recovery approach before any real operational use.

## 10. Information groups

The requirements identify these information groups; detailed columns and relationships must be designed after the repository audit:

- User and officer records.
- Complaints and review decisions.
- Cases linked to originating complaints.
- Case assignments.
- Investigation activities.
- People associated with cases: victims, suspects, and witnesses.
- Evidence records.
- Evidence handling history.
- Investigation reports and supervisory review outcomes.
- Case status history and audit logs.

## 11. Proposed case status labels

The document proposes the following labels, subject to confirmation:

`Registered → Awaiting Assignment → Assigned → Under Investigation → Pending Review → Further Investigation Required → Submitted to Prosecutor → Pending Court Proceedings → Concluded`

These are proposed software labels, not verified official terminology. Restrict transitions to authorized users and record reason, user, and date. Confirm available statuses and transitions before implementation.

## 12. Acceptance criteria

The initial system should demonstrate that:

- A user can log in and access only permitted functions.
- A Reporting Officer can register and retrieve a complaint by reference.
- A Reviewing Officer can record approval or rejection with a reason.
- An approved complaint can be converted into a case.
- A supervisor can assign a lead investigator and additional investigators.
- Assigned investigators can record activities and evidence information.
- A supervisor can review a submitted investigation report and record the outcome.
- Case status changes and important user actions are recorded and reviewable by authorized users.

## 13. Source and uncertainty

Primary source: *Police Crime Reporting and Case Management System — Initial Requirements and System Design Document*, Version 1.0, dated 20 November 2026, updated copy supplied by the developer.

The source says the workflow and roles are proposed assumptions and should be confirmed. It intentionally leaves detailed database columns, models, migrations, controllers, routes, and screen designs for the implementation stage. Do not present proposed details as source-approved facts.
