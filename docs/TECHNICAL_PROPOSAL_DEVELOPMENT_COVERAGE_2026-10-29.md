# Arewa Civic Tracker: Technical Proposal Development Coverage

**Review date:** 29 October 2026  
**Source:** InnoBytes Lab LTD, *Technical Proposal: Development of the Arewa Civic Tracker – Digital Civic Monitoring and Governance Reporting Platform*, prepared September 2026, 19 pages, ref. RAWYOD/NYFF/VOICES-TO-VOTES/2026/001.  
**Implementation examined:** Laravel/PHP on `arewa.kasua.ng`, current database migrations and routes, web frontend, evidence-storage module and native Kotlin Android source.

## Scope interpretation

RAWYOD is receiving a **native Android application** rather than the PWA proposed in September. **Do not count PWA installation, browser service workers, or PWA-specific app-shell caching as unfinished development.** Retain the underlying operational promises: mobile-friendly reporting, offline draft recovery, evidence capture, low-bandwidth transfers, resilient synchronization, and no lost/duplicate submissions.

Keep formal QA, deployment, training, manuals, stabilization support and source handover in their own later delivery stages. These are *not* missing software features.

## Technical proposal module coverage

| Proposal area | Current implementation status | Evidence or remaining work |
|---|---|---|
| Secure login, staff profiles and role-based access (pp. 6, 12) | Implemented | Admin/Focal Person/MEAL roles, session control, TOTP, rate limiting, profile photos, activity events. |
| Structured civic issue and public project reporting (pp. 6–7) | Implemented | Type, LGA, community, sector, description, observations, duty bearers, date, progress, GPS and evidence. |
| Unique report reference and retry safety (p. 7) | Implemented | Unique reference and idempotent client submission IDs. **This is not content-similarity detection.** |
| Duplicate-report warning based on overlapping content (p. 7) | Missing | Add a near-duplicate suggestion before submission, based on relevant LGA/community/date/type/title, while allowing justified distinct submissions. |
| Post-submission report field editing and version history (pp. 5, 7, 9) | Missing | Build editable draft/revision process with role-based permissions, original values, changes, actor, time, reason, and review-state safeguards. Follow-up notes are not a substitute. |
| Offline field drafts, queue, sync and low bandwidth (pp. 2, 7, 13) | Partial | Web queue and encrypted Android offline outbox exist; native camera/GPS/evidence/restore, retries, token expiry and physical-device functionality need finalization. No PWA work required. |
| Client-side image compression (pp. 6–7, 13) | Web implemented; Android completion not established | Web canvas compression confirmed for JPEG/WebP; verify on native Android and for supported upload formats. |
| Review, clarification, verification, approval/rejection (p. 9) | Implemented | State machine and attributed events. |
| Follow-ups and formal closure (pp. 6, 9) | Partial | Follow-up notes and resolved progress exist, but no distinct closed-case state and reopening/final-closure workflow has been established. |
| Dashboards, trends, distribution and role views (p. 10) | Implemented | Report KPIs, visualizations, date/sector/status distribution. |
| Map, marker clustering, LGA boundaries and location filters (p. 10) | Implemented | Leaflet/OpenStreetMap with marker clusters and filtered GIS data. |
| Search/filtering and CSV/XLSX/PDF outputs (p. 10) | Implemented | Searchable register and analytics exports with charts. |
| Administrator-configurable master data (pp. 2, 6) | Partial | Sectors are managed. Report categories, verification/progress statuses and community options remain largely hardcoded or free text; create controlled admin-managed master data where RAWYOD needs customization. |
| Evidence privacy/security (pp. 11–12) | Substantial | Role-restricted downloads, file validation, storage hashes, local private storage, R2 readiness dashboard and verified-copy migration tooling. Live R2 activation awaits credentials. |
| Audit of login, review, report changes (p. 12) | Partial | Login/logout, review, assignment and follow-up actions logged. Full report-edit diff/version ledger is missing. |
| Backup, recovery, retention, breach handling (pp. 11–12) | Partial | Manual backups and technical storage code exist. Complete scheduled encrypted database+evidence backup, restore automation, retention controls and incident-response processes are not established. Some documents/policies are an operational deliverable after development. |
| Mobile/tablet accessibility, filters, pagination (p. 13) | Mostly implemented | Responsive screens and paginated lists. Physical device/accessibility and performance acceptance are separate QA work. |

## Prioritized software development backlog

1. **Report editing/revisions:** secure revisions after submission, change reasons, historical diffs, role rules and review-state behavior.
2. **Native Android:** complete draft/evidence queue, camera, GPS, image processing, retry and background synchronization; produce the release build.
3. **Similar-report warning:** content-based duplicate suggestions distinct from idempotency keys.
4. **Formal closure:** resolved/closed lifecycle and traceable follow-up completion, as agreed with RAWYOD.
5. **Master data administration:** controlled communities/categories and configurable status options where compatible with the review workflow.
6. **Recovery software:** scheduled encrypted backups for database and private evidence, backup monitoring and tested restore utilities.
7. **Data-protection engineering:** retention/deletion options with audit-preserving safeguards and any missing security controls.
8. **R2 activation:** dependent on scoped Cloudflare account credentials; keep local evidence protected until verified.

## Later stages, tracked separately

- **Formal QA and UAT:** functional, permission, device/offline, security, performance, backup-restore and RAWYOD acceptance testing (technical proposal p. 15).
- **Post-development deployment:** production configuration and hosting (p. 16).
- **Materials and training:** administrator and field-user manuals and orientation (p. 16).
- **Post-launch support:** proposed 60-day stabilization support (p. 16).
- **Final handover:** source, data dictionary, architecture, secure credentials, backup procedures and design assets (p. 17).

## Important distinction

The proposal's five-week plan places *testing and UAT in week 4* and *deployment/handover in week 5* (p. 14). The present tracker should not label those later phases as unimplemented application features. Likewise, replacing PWA with native Android changes the delivery technology, not the promised ability to operate offline under weak connectivity.

**Assessment:** The web core is substantially built, but remaining actual software gaps include report revisions, near-duplicate warnings, formal closure, configurable master data, finalized Android flows, and automated recovery.
