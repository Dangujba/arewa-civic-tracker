# Arewa Civic Tracker
## User Requirements Specification (URS) | Development baseline v1.0

**Prepared by:** InnoBytes Lab LTD  
**Client:** Roadmap for Women and Youth Development (RAWYOD)  
**Project:** VOICES TO VOTES, Yobe State  
**Reference:** RAWYOD/NYFF/VOICES-TO-VOTES/2026/001  
**Baseline date:** 29 October 2026  
**Status:** Complete development specification, available for RAWYOD review. No stakeholder sign-off is asserted.

### 1. Source and scope
This URS consolidates RAWYOD's *Call for Expression of Interest* (7 September 2026, sections 2, 3 and 5) and InnoBytes' *Technical Proposal* (September 2026, pp. 4 to 13, 19), then maps them to the current implementation. Functional scope includes the responsive Laravel web portal, administrative analytics and native Android field application. The original PWA delivery approach was subsequently replaced with a native Kotlin/Compose Android app. **PWA completion is outside the agreed current development scope.** Cloudflare R2 activation, production configuration, formal UAT, training, manuals and handover are separate phases.

### 2. Business goals and users
The Tracker supports structured, traceable civic monitoring of governance issues and public projects by an initial **34 Civic Intelligence Focal Persons** across **all 17 LGAs of Yobe State**. RAWYOD must be able to distinguish a field observation from a reviewed and approved record, identify unresolved issues, document follow-up actions and extract defensible trends for engagement with duty bearers. No user-supplied claim is automatically a verified fact.

| Role | Permitted responsibilities | Principal restrictions |
|---|---|---|
| RAWYOD Administrator | Manage accounts, LGA assignments, LGAs, sectors, communities and categories; assign reviewers; approve or reject according to workflow; close/reopen eligible cases; view statistics and audit logs; manage governance policy | Every privileged action authenticated and recorded; records remain auditable |
| Data / MEAL Monitoring Officer | Review assigned/unassigned eligible cases, request clarification, verify, update progress and follow-ups, view permitted analytics and exports | Cannot bypass admin-only approvals or user administration |
| Civic Intelligence Focal Person | Prepare, save and submit reports; attach evidence and GPS; respond to clarification; amend eligible submitted records; view own reports; use native offline queue | Cannot access another focal person's reports or privileged analytics |

### 3. Numbered functional requirements
| ID | Requirement and expected system behavior | UI or implementation reference |
|---|---|---|
| ACT-F01 | Secure account login, rate limiting, role-aware navigation, activation and deactivation, password rotation and optional TOTP two-factor controls | Web login, Account Security; API /api/v1/login |
| ACT-F02 | Administrator creates or updates staff, assigns focal-person LGAs and monitors activity | /admin/users; /admin/activity |
| ACT-F03 | Field form records type (public project or governance issue), title, narrative, observations, duty bearers, observation date, LGA, community, sector, category and progress | /cases/new |
| ACT-F04 | Submission validates fields and produces unique ACT-YOB references; retries carrying the same client submission ID do not create additional cases | /cases; CaseSubmissionService |
| ACT-F05 | Evidence accepts controlled photo/PDF uploads, validates file type/count/size, compresses supported photos and restricts downloads to authorized users | Field report, case evidence, private storage |
| ACT-F06 | GPS is optional; manual coordinates, LGA and community allow reporting when device permission or connectivity is unavailable | Field report and Android |
| ACT-F07 | Online similarity check presents relevant potential matches without blocking justified new reports | /cases/similar; Android online check |
| ACT-F08 | Focal person can edit an eligible submitted report, with a mandatory reason, conflict/version safeguards and attributable before/after history; approved, rejected and closed reports cannot be silently rewritten | /cases/{case}/edit; PUT /api/v1/reports/{case} |
| ACT-F09 | Reviewer workflow preserves Submitted, Under Review, Clarification Requested, Verified, Approved and Rejected transitions with access rules, actor, timestamp and comments | /reviews/queue; case detail |
| ACT-F10 | Responses to clarification update the existing case rather than creating a separate duplicate | Case detail and Android API |
| ACT-F11 | Follow-up notes, progress changes, final closure and reopening are attributable, with closure limited to eligible approved cases | Case detail and history |
| ACT-F12 | Role-specific dashboards show live counts, status, sectors, reporting trends and geographic distribution, without invented live data | /dashboard; /reports/analytics |
| ACT-F13 | Analysis filters combine LGA, community, sector, type, verification state, dates and text; community groups are separated by LGA and permit report drill-down | /reports/analytics |
| ACT-F14 | Authorized staff can export filtered reports to CSV, Excel and chart-bearing PDF using the same role-visible source data; configured status labels are used | /cases/export.csv; /reports/export.xlsx; /reports/export.pdf |
| ACT-F15 | Interactive GIS uses OpenStreetMap/Leaflet, Yobe's 17 LGA reference boundaries, clustered coordinates and report filters; reports without GPS remain visible in non-map registers | /map |
| ACT-F16 | Administrator can create/update reference LGAs (stable IDs), sectors, communities and report categories, and edit status display labels without changing legal workflow transitions | /admin/reference-data; /admin/sectors |
| ACT-F17 | Web app works on mobile phones, tablets and desktop, with keyboard and touch support, clear validation feedback, filterable selects and non-obstructive loading feedback | Main portal layout and forms |
| ACT-F18 | Native Android supports authenticated field reporting, private photo capture/PDF selection, optional GPS, encrypted local drafts, queued evidence and deferred synchronization | android-native/ |
| ACT-F19 | Android offline retries preserve report IDs and local drafts; a changed/expired token does not silently discard saved data; backend authorization remains authoritative on reconnection | Room, Android Keystore and WorkManager |
| ACT-F20 | Public APK download does not require portal login; access to submitted civic data still requires staff authentication | GET /download/android |
| ACT-F21 | Protected data is controlled by account role, field assignment, server-side validation and private evidence storage; report review and key admin actions are audited | Policies, audit records, evidence controller |
| ACT-F22 | Encrypted database/evidence backup commands, integrity validation and isolated restore utilities are provided; off-server destination remains an external configuration decision | Data Governance and backup commands |
| ACT-F23 | Retention-policy drafts, explicit policy approval records and case legal holds exist; irreversible destruction cannot be enabled without an authorized RAWYOD policy | /admin/data-governance |

### 4. Mandatory reporting fields and rules
A new report requires **report type, title, description, LGA, community, sector, observation date, progress status and client submission ID**. Category, observations, duty bearers, GPS coordinates and evidence may be optional as permitted by server validation. Future observation dates and inconsistent GPS pairs are rejected. A focal person must be assigned to the selected LGA; the backend rechecks eligibility when syncing an offline submission. Report references are generated by the server and are not user-editable.

The client should warn about possible duplicates based on geographic and content similarity, but must not silently discard a distinct valid observation. Evidence remains private and attachments are not made public merely because a case has been approved.

### 5. Workflow and audit acceptance criteria
| Transition / interaction | Acceptance behavior |
|---|---|
| Draft to submitted | Valid report stored once, assigned reference, actor timestamped |
| Submitted to under review | Review queue and reviewer attribution visible |
| Under review to clarification | Reason recorded; focal user notified through the case workflow |
| Clarification response | Same case resumes review with chronological message |
| Verified to approved | Admin authorization enforced; approval is distinct from project completion |
| Reject | Rejected records excluded from approved statistics by default |
| Follow-up or progress change | Dated entry records actor and value |
| Close/reopen | Admin authorization and reason stored, closed case locked |
| Versioned edit | Changes recorded, outdated version rejected, protected states enforced |

### 6. Non-functional requirements
- **Low bandwidth:** compressed images, paginated query results, no fabricated dashboard values and recoverable native offline drafts.
- **Privacy:** role visibility, scoped API tokens, private evidence, no public evidence bucket, GPS kept to the necessary data.
- **Integrity:** validation, idempotency, versioned edits, restricted state transitions, audit trails and recoverable encrypted snapshots.
- **Accessibility:** usable mobile touch targets, focus visibility, search/filter controls, readable form labels, reduced motion and keyboard navigation.
- **Maintainability:** Laravel/PHP, MySQL/MariaDB, Blade/JavaScript, Leaflet/OpenStreetMap and Kotlin native Android, with no mandatory per-user proprietary licensing.
- **Capacity target:** initial 34 focal persons across 17 LGAs; future expansion via administrator-managed reference data, without claiming measured load performance.

### 7. Business decisions not invented by the developer
RAWYOD must approve any retention/erasure periods, sensitive-data sharing policy, final UI/UX branding and public communication rules. Production-signed Android distribution and off-server backup storage depend on authorized keys or infrastructure. This URS documents the completed development baseline, not those external decisions.

### 8. Traceability and change handling
Requirements ACT-F01 through ACT-F23 are traced to EOI sections 3 and 5 and the Technical Proposal pages 6 to 13. The source of truth for behaviors is the Laravel routes, authorization services, native Android source and database migrations in this repository. Substantive changes require a recorded requirement revision before implementation.

**Document baseline:** v1.0 complete. **Client approval:** pending RAWYOD review, not presumed.
