# Arewa Civic Tracker
## UI/UX Design and Screen Baseline | v1.0

**Client:** RAWYOD | **Design and implementation:** InnoBytes Lab LTD  
**Date:** 29 October 2026 | **Status:** Complete development-stage design baseline for stakeholder review. Visual sign-off has not been claimed.

### 1. Design goals
A low-tech civic reporting platform should make it easy for a field user to record a valid observation, find a draft, respond to a reviewer and understand whether a case has been verified. Supervisors need confident navigation between KPIs, underlying cases, geographic distribution and exports. The UI must not imply that an unverified submission is independently proven.

The actual developed solution includes a **responsive web portal** for staff and a **native Android** field application. PWA-specific installability is excluded from this baseline.

### 2. Design system
| Element | Baseline |
|---|---|
| Brand | RAWYOD / VOICES TO VOTES identity and hand-drawn map-with-pin project symbol |
| Colors | Forest greens for navigation and core actions, warm gold for secondary emphasis, neutral paper/white backgrounds, muted green and gray for supporting text; status colors must be accompanied by words |
| Components | Compact tabular administration, clear outlines, flat controls and minimal border rounding; no unnecessary shadows or oversized decorative cards |
| Typography | Legible sans-serif hierarchy with modest uppercase overlines, clear labels and compact rows |
| Responsive design | Desktop sidebar and data tables; stacked mobile forms; no horizontal overflow of primary content |
| Interaction feedback | SweetAlert2 toast for ordinary success/error feedback, confirmation dialog for sensitive actions such as sign-out, restrained dotted spinner during asynchronous waits |
| Navigation | Sidebar groups for Workspace, Geographic Intelligence and Administration; active route marked; protected links omitted for unauthorized roles |
| Forms | Searchable choices where applicable, explicit required-field indicators, validation at field level, clear errors and consistent submit states |
| Accessibility | Keyboard focus rings, skip-to-content link, Escape-to-close navigation, readable buttons and labels, adequate touch targets and reduced-motion support |
| Bandwidth | Smaller pages, compressed images, lazy or filtered data retrieval, no forced downloading of every report or evidence file |

### 3. Responsive web screen inventory
| Screen | Primary users | Screen information and key actions | Implementation route |
|---|---|---|---|
| Public landing | Everyone | Purpose, platform entry and public Android APK download | / |
| Login / account security | Staff | Email/password, forced first-login change and optional TOTP challenge | /login; /account/security |
| Monitoring dashboard | All staff, role-specific | Real counts, case status, recent records and LGA/sector visualizations | /dashboard |
| Report register | Authorized staff | Compact report rows, LGA and review-state filters, pagination and case drill-down | /cases |
| New field report | Focal person | Type, description, LGA/community/sector, date, status, observations, GPS and evidence; duplicate hints | /cases/new |
| Case detail | Authorized staff | Reference, submitted fields, verification and progress, evidence, follow-ups, revision history and permitted actions | /cases/{case} |
| Edit report | Eligible owner | Explicit reason, editable fields, version-aware save and error feedback | /cases/{case}/edit |
| Verification queue | MEAL and admin | Status filter, reviewer assignment context, due dates and report navigation | /reviews/queue |
| Reports and analytics | MEAL and admin | LGA, community, sector, type, date, verification filters; time chart, status chart, community drill-down, exports | /reports/analytics |
| Interactive map | Authorized staff | Leaflet markers, 17 Yobe boundary layers, spatial clustering and case links | /map |
| User administration | Administrator | Account creation/update, role and LGA assignment, deactivation | /admin/users |
| Reference Data | Administrator | LGA create/update, community and category management, workflow display labels | /admin/reference-data |
| Sector management | Administrator | Sector list and protected updates | /admin/sectors |
| Audit and operations | Administrator | Activity history, review workloads, storage and system operational signals | /admin/activity; /admin/operations |
| Data Governance | Administrator | Draft and approve policy metadata, review aging counts and legal-hold actions | /admin/data-governance |

### 4. Field user journey
1. A focal person signs in with a registered, active staff account. An initial password change may be required before access.
2. In **New Field Report**, they choose Public Project or Governance Issue, select assigned LGA and sector, enter the community and observations, and optionally add location/evidence.
3. Any online similar-report suggestions are shown as nonblocking prompts. Drafts may be saved locally in the Android application if offline.
4. Submission displays a generated case reference and current Submitted state. A user can review their own cases and history.
5. If RAWYOD requests clarification, the user responds within the existing case. A valid submitted report can be amended with a reason while its workflow state permits editing.
6. If the network is interrupted on Android, pending data remains in an encrypted outbox and is retried. A failed item must show a recoverable error rather than disappear.

### 5. Review and management journey
1. Reviewer opens **Verification Queue**, sees eligible records, assignments and deadlines, and opens a case.
2. Reviewer examines evidence, requests clarification, marks Verified or records Rejected where permitted. Administrator performs approval/closure functions according to permissions.
3. Reviewer or administrator can record a dated follow-up or status change. Historical changes remain visible with actor and reason.
4. Manager opens **Reports & Analytics**, applies LGA/sector/community/date/type filters, drills from community counts into the matching case register, and downloads a chart-bearing PDF or Excel output.
5. GIS users navigate clustered report markers and LGA boundary layers; an absent GPS coordinate never blocks non-map records.

### 6. Native Android screen baseline
| Screen / state | Inputs and expected feedback |
|---|---|
| Account login and optional authenticator | Correct credentials accepted only for an enabled focal person; clear failure and token-refresh messaging |
| Field home and own-report history | Own cases and statuses only; cached reference data for assigned LGAs |
| New or editable offline draft | Type, LGA, community, category, sector, notes, progress, observed date; explicit Save Draft / Submit actions |
| Camera and evidence picker | Photo capture or PDF selection, compression for supported photos, private file handling and size/count validation |
| GPS capture | Request permission; declined permission leaves optional/manual location usable |
| Offline outbox | Pending, synchronizing, retryable error and completed states; user knows what reached the server |
| Submitted report and edit | Online load of latest version, mandatory reason for eligible changes, locked-state explanation if approved/rejected/closed |
| Network or session failure | Draft preserved; no misleading claim of successful upload |

Native app version available in staging is **0.3.1, debug signed**. A production signing decision is not a UI/UX design task. The website APK download does not require login, but field data in the Android app does.

### 7. Wireframe-level interaction structure
**Field report, narrow screen:** Header and step context > Report type + date > LGA and community > Sector/category > narrative, observations and progress > optional duty bearers > GPS > attached evidence > Similar Report Check (online) > Save Draft or Submit > reference and status feedback.

**Management analytics, wide screen:** Page header + Excel/PDF actions > combined search filters > KPI strip > trend and verification charts > progress summary > LGA/sector rankings > community table with per-LGA drill-down > paginated underlying case register.

**Reference Data, wide screen:** Page heading > compact LGA editor > community editor > category editor > status display label editor. Editing a label must not create or remove machine workflow states.

### 8. Review checklist and design boundaries
The implementation baseline covers desktop, tablet and mobile layouts; keyboard navigation; low-bandwidth principles; information architecture; role separation; stakeholder-facing charts and reporting; offline native states; and recoverable forms. All displays must use real case data, never fictional operational statistics. Case disclosure and GPS access follow backend authorization.

Brand sign-off, usability acceptance on representative devices and any RAWYOD-requested changes are later stakeholder checkpoints. Screens in source are the authoritative implementation of this baseline. Exact route names and component layouts may evolve through approved change control.

**Design baseline status:** complete as a development deliverable. **RAWYOD visual approval:** pending; not implied.
