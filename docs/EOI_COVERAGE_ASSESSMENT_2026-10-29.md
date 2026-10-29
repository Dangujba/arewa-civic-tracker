# Arewa Civic Tracker: EOI Coverage and Development Status
**Assessment date:** 29 October 2026  
**Source:** RAWYOD, *Call for Expression of Interest – Development of the Arewa Civic Tracker – Digital Civic Monitoring and Governance Reporting Platform*, 7 September 2026, reference RAWYOD/NYFF/VOICES-TO-VOTES/2026/001 (12 pages).  
**Implementation:** InnoBytes Lab LTD, Laravel web platform at https://arewa.kasua.ng and native Android Kotlin/Jetpack Compose source under `android-native/`.

## Status definitions
- **Implemented:** feature exists in the deployed web application or reviewed code, but does not alone imply RAWYOD acceptance.
- **Partial:** some meaningful work exists; an EOI requirement is not fully satisfied or signed off.
- **Not delivered:** no verified completed deliverable at the date of assessment.

## EOI functional requirements (pages 2–5)
| EOI clause | Status | Current evidence and outstanding work |
|---|---|---|
| A. Registration, login and access levels | Implemented | Administrator-managed accounts; Administrator, Civic Intelligence Focal Person and MEAL roles; enable/disable, forced initial-password replacement, optional TOTP and scoped role authorization. Self-registration is not exposed; user activity audit is partial. |
| B. Civic issues and public projects | Implemented | Public-project and governance-issue categories, LGA, community, sector, dates, progress, description, observations, duty bearers, GPS, photographs/PDF evidence and follow-up records. |
| C. Field submission and later updates | Partial | Web and native API submission, attachments, status tracking, follow-ups, progress updates and clarification responses. Full editing/version history of previously submitted report fields is not established. |
| D. Review and verification | Implemented with acceptance pending | Reviewer queue, assignments, clarification, verification, approval/rejection, attributed events and notifications. Formal multi-role UAT remains open. |
| E. Dashboards and visualisation | Implemented with acceptance pending | Role-based dashboards, report counts, LGA/sector/status distribution, progress and trends; data-reconciliation UAT remains open. |
| F. Search, filters, summary reports, exports | Implemented with acceptance pending | Search and combined LGA/sector/date/status/type filters, CSV, XLSX, chart-rich PDF. Large-volume/performance and stakeholder layout approval pending. |
| G. Geographic mapping | Implemented with acceptance pending | Location data and 17-LGA GIS boundaries, map filters/markers and geographic overlays. Field accuracy, large data and privacy evaluation remain open. |
| H. Mobile access and low bandwidth | Partial | Responsive web interface, offline browser queue, encrypted offline native outbox and API. A verified installable native Android APK, physical-device offline testing and performance acceptance are not delivered. |

## EOI Expected Deliverables: 20-point checklist (pages 5–6)
| # | Expected deliverable | Status | Notes |
|---|---|---|---|
| 1 | User Requirement Specification (URS) | Partial | Requirements coverage matrix exists; no evidence of formal RAWYOD-approved URS. |
| 2 | System architecture and technical design | Partial | Technical code, source documentation, hosting design and storage procedures exist; final approved architecture package is pending. |
| 3 | UI/UX design and approved prototype | Partial | Functional web UI/prototype deployed; no formal RAWYOD design approval confirmed. |
| 4 | Fully functional Arewa Civic Tracker | Partial | Core web features exist; editing, native release and final field-readiness gaps remain. |
| 5 | Web administration and dashboard | Implemented | Admin, focal, MEAL dashboards and administrative management. |
| 6 | Mobile-responsive field interface or suitable mobile solution | Partial | Responsive web works; native Kotlin source exists but no verified APK/field trial. |
| 7 | User roles and access management | Implemented | Three user types, activation/deactivation, initial password rotation and restricted routes. |
| 8 | Civic issue and public project reporting | Implemented | Structured form and API; later full record editing still partial. |
| 9 | Review, verification and case management | Implemented | Reviewer assignment, status progression, clarification and audit events. |
| 10 | Dashboard and data visualisation | Implemented | KPI panels and charts; RAWYOD data accuracy sign-off pending. |
| 11 | Search, filtering and reporting | Implemented | Advanced filters and analytics. |
| 12 | Data export | Implemented | CSV, XLSX, PDF with embedded charts. |
| 13 | Database and data-security architecture | Partial | Private evidence, HTTPS, permissions, CSRF, TOTP and audit features; scheduled backup/restore drill, privacy and penetration review pending. R2 dashboard and verified migration tooling deployed, credentials absent. |
| 14 | System testing and bug fixing | Partial | 73 Laravel tests and 613 assertions passed in an isolated checkout on 29 Oct; final browser, device, load, security and UAT not performed. |
| 15 | Deployment and configuration | Partial | Web staging is live at arewa.kasua.ng; native release and RAWYOD final production destination/handover remain open. |
| 16 | Hosting setup and technical documentation | Partial | Staging hosting and server docs exist; finalized hosting configuration/support plan not signed off. |
| 17 | Administrator and user manuals | Not delivered | Dedicated complete illustrated manuals not verified. |
| 18 | Staff training/orientation | Not delivered | No evidence of completed RAWYOD training, attendance or participant sign-off. |
| 19 | Initial post-deployment support and maintenance | Not delivered | Contractual support period cannot start until acceptance/handover. |
| 20 | Full source-code and technical handover | Not delivered | Source in GitHub; RAWYOD-administered ownership, signed handover, documentation and secure credentials transfer pending. |

## Verified current live state
- Website HTTPS responds, and protected pages redirect unauthenticated requests.
- 17 LGAs in the database.
- 2 staff accounts (1 Administrator, 1 Focal Person, 0 MEAL Reviewers).
- 0 field reports and 0 evidence files at the time checked.
- Evidence Storage dashboard, private R2 connection check and SHA-256 audit/migration commands deployed. No actual R2 credentials/remote bucket configured, so all new evidence still defaults to local private storage.
- Most recent isolated Laravel test suite: **73 passed, 613 assertions**.
- Native Android source code exists but has not been verified as a signed, installable APK or field-tested on a physical Android device.

## Priority order before formal acceptance
1. Finish genuine editing/revision workflow for previously submitted reports with complete audit history and role checks.
2. Finalize native Kotlin Android application, produce the APK, and complete camera, GPS, permission, airplane-mode, resume, syncing, token-expiration and retry tests.
3. Finalize privacy/security requirements, complete authenticated evidence controls and R2 activation when authorized credentials are available.
4. Finish RAWYOD website integration and approved production hosting URL without touching unrelated RAWYOD pages.
5. Prepare approved URS/architecture diagrams, administrator and focal-person guides, maintenance/runbook and automated backup/restore procedures.
6. Complete role-by-role UAT, security review, data reconciliation and realistic 34-field-person load tests.
7. Orient/train RAWYOD administrators, MEAL reviewers and 34 focal persons, then formally hand over source, hosting credentials, data, designs and manuals with RAWYOD sign-off.

## Important scope distinction
The EOI says implementation should be completed in an agreed period following contract signing and does not itself specify an 18 November deadline. Any exact dates from the separate signed agreement must be checked against that agreement, not attributed to the EOI.

**Conclusion:** The core digital platform is substantially implemented. The 20 requested outputs must not be treated as 20 accepted deliverables yet. Full release/acceptance depends on native Android delivery, report revisions, approved documentation, security/backup validation, training, RAWYOD website integration and complete handover.
