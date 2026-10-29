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

## B. Development completion update (29 October 2026, after Android v0.3.0 and VPS rollout)

The following status table supersedes the earlier pre-implementation list. **R2 activation is explicitly deferred at the project owner's request**, and formal UAT/release signing are separate later stages.

| ID | Feature | Current software status | Evidence and remaining boundary |
|---|---|---|---|
| P01 | Native Android and offline field reporting | Core implementation completed, further parity work possible | Native Kotlin v0.3.1 debug APK compiled and privately deployed; authenticated API, camera/GPS, encrypted drafts/evidence, photo compression, edited offline drafts, resilient retry, online similarity checks, and **direct edits of eligible submitted reports using the same versioned audit trail as web**. Physical-device UAT remains a separate later phase. Physical-phone UAT and production signing are later activities. |
| P02 | Formal case resolution, closure and reopening | **Implemented and staging-deployed** | Admin-only closure for approved and completed/resolved cases; reason, actor and event history; reopening with reason; closed-case changes restricted. |
| P03 | Configurable reference data | **Implemented and staging-deployed** | Admin-managed sectors, LGA-scoped communities, project/issue categories, and display labels for status codes. Approval state transitions remain fixed for safety. |
| P04 | Comprehensive activity/change audit | Substantially implemented | Versioned report edits show field changes; review, closure, reference management and key admin operations recorded. Broader consistency audit and log-retention policy remain for final review. |
| P05 | Cloudflare R2 | **Deferred, excluded from active development scope** | Existing private local evidence storage continues. Do not activate or migrate until instructed. |
| P06 | Encrypted backup/recovery | Backup automation implemented; restoration operations partial | AES-256 GPG snapshot command, database + private evidence archive, independent hash validation, scheduled 02:15 WAT, verified first live snapshot. An automated isolated restore procedure and **off-server copies** are not in place. Same-VPS backups cannot protect against total VPS loss. |
| P07 | Data-protection lifecycle | Conservative controls implemented; policy-dependent features pending | Expired device tokens and web sessions older than 30 days are scheduled for cleanup. Reports, evidence, users and audit records are **never auto-deleted**. RAWYOD-approved retention periods and erasure authorizations must precede destructive features. |
| P08 | UI/UX | Substantially implemented | Responsive web and flat admin tables, managed reference lists, revision history and native field interface. Cross-device accessibility and field-persona acceptance remain in QA. |
| M01 | Authorized post-submission report editing with version history | **Implemented on the web** | Versioned, permission-restricted changes, reasons, old/new values and conflict protection. Native post-submission editing would be an additional parity improvement. |
| M02 | Similar-report warning | **Implemented on web and native Android** | LGA-scoped, title/community/sector/date matching; non-blocking suggestions; unauthorized report details masked. Native similarity requires online access and does not prevent offline saving. |

### Verified rollout

- Source: GitHub main development commits `72bf525d81e06fcf1667f16619a8f95268b34fcd` and `d045cda14c3cc771d726e5f58251d90ce70846d2`.
- Web: `https://arewa.kasua.ng` staging is online. Migrations `2026_10_29_000009_add_case_closure` and `2026_10_29_000010_create_reference_data` have been applied.
- Automated Laravel checks: **92 passing, 699 assertions**.
- Android debug version: **v0.3.1**, checksum `a7fb9fb0f4cb129fa53214b857098e4f52f8c7b7393271656de85ebb63cdbb7d`, 11,640,199 bytes, Android v2 debug signature verified. Admin-only route: `/admin/native/android-debug`.
- First AES-256 encrypted private backup generated and verified successfully on 29 October 2026. Live cron invokes the Laravel scheduler every minute.
- No production signing, physical-device acceptance, external backup redundancy, RAWYOD training or formal handover is being claimed.

## C. Remaining actionable software work, excluding R2 and later delivery phases

1. **Native Android parity:** completed for submitted-report revisions in v0.3.1. Remaining device-specific behavior belongs to formal UAT and release signing.
2. **Recovery operations:** implement a strictly isolated restoration flow and validate it using independent recovery storage. Obtain an approved off-server backup destination to protect against VPS loss.
3. **Privacy controls:** finalize RAWYOD-approved record retention/erasure rules; any deletion must be permission-gated, auditable and reversible where lawful.
4. **UI consistency/accessibility:** finish remaining responsive navigation, searchable controls, error states and keyboard/touch accessibility. Formal user/device testing remains a later phase.
5. **Technical design baselines:** complete URS, architecture/API/offline designs and UI/UX baseline as design-stage outputs, not runtime bugs.

The web core, similar warnings, closure and reference administration are no longer missing software modules. **Do not include R2 activation, formal UAT, production signing, manuals, training, hosting handover or support in unfinished development counts.**

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

**Decision (updated):** Core web features, versioned web report editing, similarity warnings, case closure, reference administration and native Android v0.3.0 are implemented in staging. Remaining software work concerns robust recovery tooling, policy-dependent privacy controls and final interface refinements. Native report revisions are available in v0.3.1. Cloudflare R2 activation and later contractual delivery phases are excluded.
