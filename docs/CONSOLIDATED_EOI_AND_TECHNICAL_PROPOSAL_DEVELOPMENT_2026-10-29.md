# Arewa Civic Tracker: Consolidated Development Checklist

**Baseline date:** 29 October 2026  
**Documents compared:**
1. RAWYOD, *Call for Expression of Interest: Development of the Arewa Civic Tracker*, 7 September 2026, 12 pages, reference RAWYOD/NYFF/VOICES-TO-VOTES/2026/001.
2. InnoBytes Lab LTD, *Technical Proposal: Development of the Arewa Civic Tracker*, prepared September 2026, 19 pages, same tender reference.

**Code evidence:** Laravel/PHP web application, MySQL/MariaDB schema, web views and JavaScript, GIS and export modules, native Kotlin/Jetpack Compose source, R2 storage readiness module. Status is based on repository review and VPS inspections on 29 October 2026, not formal RAWYOD acceptance.

## Important scope decisions

- **Do not treat PWA-specific work as pending.** InnoBytes and RAWYOD are proceeding with native Android plus the responsive web portal. The underlying *offline, low-bandwidth, queued-sync and evidence* requirements remain in scope.
- **Do not count final deployment, hosting handover, manuals, training, post-launch support or source-code handover as unfinished software development.** These are separate delivery phases.
- **Do not equate an automated developer test with stakeholder acceptance.** Testing during development is routine; formal QA/UAT is a separate stage.
- **Status terminology:** Implemented = core software exists; Partial = meaningful implementation exists but named functional requirements remain; Missing = no verified implementation of the specifically promised feature.

## Preliminary design outputs (tracked separately from feature completion)

The EOI also names three design-stage outputs. These are not extra runtime modules and are **not included in the 22 feature-group counts**:

| Required output | Status | Remaining work |
|---|---|---|
| User Requirement Specification (URS) | Partial | Consolidate confirmed fields, roles, acceptance rules and mobile workflow into a reviewable document. RAWYOD approval is a later decision gate. |
| System architecture / technical design | Partial | Consolidate component, database, API, authorization, native offline synchronization and storage diagrams. |
| Final UI/UX design baseline | Partial | Consolidate completed screens and remaining mobile flows into a coherent, reviewable design. Stakeholder approval is tracked separately. |

## A. Implemented software features

| ID | Unified requirement (EOI + proposal) | Current implementation evidence | Remaining before formal acceptance |
|---|---|---|---|
| D01 | Secure staff access and roles | Admin, Focal Person and MEAL accounts, enable/disable, rate limiting, session login, role and LGA authorization, conditional TOTP | Formal permissions/UAT later |
| D02 | Staff account/profile management | Account creation, role changes, LGA assignments, profile photo, password change, security settings | Formal UAT later |
| D03 | Structured field reports | Public Project/Governance Issue, LGA, community, sector, observation date, details, progress, duty bearers and input validation | Full post-submission editing tracked separately |
| D04 | Web evidence capture and image compression | Private photo/PDF uploads, size/type validation, canvas image compression | Native evidence workflow is separate |
| D05 | Web GPS/location and fallback | Optional GPS coordinates, LGA and community fields usable without GPS | Native GPS workflow is separate |
| D06 | Unique reference and retry-safe submissions | Generated ACT-YOB report reference; idempotent client submission ID and payload digest checks | Does **not** replace semantic duplicate warnings |
| D07 | Reviewer workflow | Queue, reviewer assignment, Under Review, Clarification Requested, Verified, Approved and Rejected; attributable transitions and comments | Formal closure is separate |
| D08 | Follow-up and progress updates | Chronological follow-up notes and reviewer progress-state updates, with attributable events | Full detail revision and formal closure are separate |
| D09 | Role-based dashboards and charting | KPI cards, reporting activity, distributions and time-series analytics | Reconciliation/UAT later |
| D10 | GIS and map visualization | OpenStreetMap/Leaflet, 17 Yobe LGA boundaries, report markers, clustering, filters and map layers | Field coordinate checks later |
| D11 | Search, combined filters, pagination | Case/analytics filtering and paginated registers | Large-volume UAT later |
| D12 | Data exports and stakeholder reporting | CSV, XLSX and PDF reports with charts and permissions | Layout/UAT later |

## B. Partially implemented software features

| ID | Unified requirement | Already developed | Remaining development |
|---|---|---|---|
| P01 | **Native Android and offline reliability** | Native Kotlin/Compose project, authentication API, encrypted offline data/outbox, synchronization logic and QA debug build work | Finish native report/evidence flows, camera/GPS, photo size reduction, sync/retry/expiry recovery, installable final release and supporting app UX. Physical device UAT is a subsequent stage. |
| P02 | **Case resolution and closure** | Progress supports resolved/completed; follow-up events and reviewer state machine exist | Establish distinct Closed/Resolved lifecycle, closure reasons, re-open behavior if agreed, authorized actor and audit history |
| P03 | **Configurable reference/master data** | 17 LGAs and administrator-managed sectors | Administrator-managed community options, report categories and relevant status options, preserving safeguards against arbitrary changes to review state machine |
| P04 | **Comprehensive activity/change audit** | Login/logout, user, review, assignment, follow-up and progress events exist | Field-by-field report change history/version ledger, complete sensitive administrative audit coverage |
| P05 | **Private storage and Cloudflare R2** | Local private file access, SHA-256 digests, deployed R2 readiness/admin dashboard and verified migration command | Activate real private R2 bucket with authorized credentials; verify upload/download and backups. Credentials are an external dependency, not missing source code |
| P06 | **Automated backup and restore** | Manual database backups and operational notes exist | Schedule encrypted backups for database and evidence, monitor backup failures, implement safe restore workflow and recovery controls |
| P07 | **Data protection lifecycle** | Authentication, authorization, controlled evidence access, basic security headers and file validation | Implement agreed retention/deletion options, audit-preserving retention, and appropriate administrator controls. Incident procedures are a separate operational document |
| P08 | **Final UI/UX and device functionality** | Responsive flat interface, tagged LGA selection, filter alignment, profile photo, ring spinners, clear error feedback and navigation | Finalize accessible field flow, touch behavior, keyboard use and native mobile views. Formal device/UX testing follows development |

## C. Not yet implemented: explicit functional gaps

| ID | Required functionality | Current condition | Development required |
|---|---|---|---|
| M01 | **Post-submission report field editing with version-aware history** | Clarification, progress and follow-up notes exist, but they do not modify prior report fields as an auditable revision | Add authorized report revisions, old/new field snapshots, change reason, reviewer and approval safeguards, immutable history and UI/API paths |
| M02 | **Warning about potentially similar existing reports** | Idempotency protects repeated submission IDs; no semantic near-duplicate warning is established | Suggest potentially matching cases based on report type, LGA/community, title, date and other features before submission; allow valid distinct cases |

## D. Recommended software implementation order

1. Full report editing and version-aware audit trail (**M01** and **P04**).
2. Native Android completion with offline and evidence flows (**P01**).
3. Similar-report suggestions that are distinct from idempotency checks (**M02**).
4. Formal resolution/closure workflow (**P02**).
5. Configurable master data, with state-machine safety (**P03**).
6. Automated backup/recovery and lifecycle security controls (**P06**, **P07**).
7. Remaining mobile/UX refinements (**P08**).
8. Cloudflare R2 real-bucket activation when authorized credentials are supplied (**P05**).

## E. Later phases: deliberately excluded from incomplete software counts

| Stage | EOI deliverable | Tracking |
|---|---|---|
| Formal QA and stakeholder acceptance | 14: Testing and bug fixing | After functionality is completed; developer tests continue during construction |
| Final production deployment/configuration | 15 | Post-development |
| Hosting setup and final technical delivery documentation | 16 | Post-development; engineering notes can exist during construction |
| Administrator/focal-person manuals | 17 | Post-development delivery materials |
| RAWYOD personnel training/orientation | 18 | Post-development rollout |
| Initial post-deployment support | 19 | Post-launch support; proposal offers 60 days |
| Full source, data, configuration and asset handover | 20 | Final contractual handover |

## Key source crosswalk

- **EOI §§3A–H / proposal pp. 5–10:** roles, reporting, evidence, review, dashboards, maps, search and exports → D01–D12; remaining enhancements → P01–P04 and M01–M02.
- **Proposal p. 7:** image compression, offline sync, similarity warnings, timestamps and *version-aware update history* → D04, D06, P01, M01, M02.
- **Proposal p. 9:** distinct review and follow-up/closed stages → D07–D08 and P02.
- **Proposal pp. 6, 12:** configurable sectors/categories/communities/status and account/report activity logs → P03–P04.
- **Proposal pp. 11–13:** encrypted backup/restore, controlled evidence, privacy-by-design, low-bandwidth and responsive interface → P01, P05–P08.
- **Proposal pp. 14–17:** formal QA/UAT and later deployment, training, 60-day support and handover → later phases, not development gaps.

**Decision:** The web core is substantially implemented. Finish genuine report revisions and native Android first. Do not expand the scope with PWA-specific tasks and do not artificially treat later handover obligations as unimplemented features.
