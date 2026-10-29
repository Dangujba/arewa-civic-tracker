# Arewa Civic Tracker: EOI Coverage and Development Status

> **Historical assessment only, superseded by the 29 October 2026 development closing batch.** The subsequent code completed report revisions, native Android v0.3.1, similarity warnings, closure, reference data, community analytics, LGA administration, status labels, data-governance and backup utilities. See [Final Development Closure](DEVELOPMENT_CLOSURE_2026-10-29.md) and the three current URS/architecture/UI-UX baselines. Outdated "missing" or "partial" rows below record the earlier inspection date; they are not live backlog items. Formal acceptance and later contractual phases are separate.

**Assessment date:** 29 October 2026  
**Source:** RAWYOD, *Call for Expression of Interest – Development of the Arewa Civic Tracker – Digital Civic Monitoring and Governance Reporting Platform*, 7 September 2026, reference RAWYOD/NYFF/VOICES-TO-VOTES/2026/001 (12 pages).  
**Implementation:** InnoBytes Lab LTD, Laravel web platform at https://arewa.kasua.ng and native Android Kotlin/Jetpack Compose source under `android-native/`.

## How this assessment separates project phases
The EOI lists **20 expected contractual deliverables**, but those are **not 20 software development tasks**. For project tracking we use separate phases:

- **Pre-development/design and software development:** EOI deliverables 1–13. Assess the functional features and technical implementation here.
- **Formal testing and acceptance:** Deliverable 14. Developer unit tests may run throughout implementation, but formal UAT and release acceptance follow functional completion.
- **Post-development delivery and operations:** Deliverables 15–20, including final deployment, hosting handover, manuals, training, support and complete source-code handover. These remain obligations under RAWYOD's EOI, but must **not** be counted as unfinished software development.

Statuses **Implemented**, **Partial**, and **Not Implemented** apply to software/design scope. Later-stage activities are labelled **Post-Development** rather than described as incomplete coding.

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

## Software development and design deliverables (EOI items 1–13; pages 5–6)

**Development-only breakdown: 7 implemented and 6 partial.** This tracks source and UI completion, not external deployment, training or formal acceptance.

| # | Required development or design output | Development status | Remaining software/design work |
|---|---|---|---|
| 1 | User Requirement Specification (URS) | Partial | Consolidate the approved functional scope into a coherent URS; RAWYOD sign-off is a separate approval gate. |
| 2 | System architecture and technical design | Partial | Consolidate architecture, API, data design, mobile/offline flow and security diagrams. |
| 3 | UI/UX design and prototype | Partial | Finalize the interface and a reviewable design baseline. Formal stakeholder approval is separate. |
| 4 | Fully functional Arewa Civic Tracker | Partial | Complete full report editing/version history and outstanding mobile functional flows. |
| 5 | Web administration/dashboard interface | Implemented | Features exist; final acceptance is tracked later. |
| 6 | Mobile-responsive field interface or appropriate mobile solution | Partial | Responsive web is implemented. Finish native Kotlin Android release features and verify installable build. |
| 7 | User roles and access management | Implemented | Administrator, Focal Person and MEAL access control implemented. |
| 8 | Civic issue and public project reporting | Implemented | Reporting module exists; full post-submission editing remains an identified cross-module gap. |
| 9 | Data review, verification and management | Implemented | Review, clarification, approval and audit events implemented. |
| 10 | Dashboard and data visualisation | Implemented | Database-driven KPI and charts implemented. |
| 11 | Search, filtering and reporting | Implemented | Combined filters, search and reporting screens implemented. |
| 12 | Data export | Implemented | CSV, XLSX and PDF reporting exports implemented. |
| 13 | Database and data-security architecture | Partial | Private data architecture exists; finish security controls, recovery automation and relevant integrations. |

## Separate quality-assurance stage (EOI item 14)

| # | Deliverable | Phase | Status |
|---|---|---|---|
| 14 | System testing and bug fixing | Formal testing and acceptance | Developer automated tests are running throughout construction; the complete release-wide, device, performance, security and RAWYOD acceptance phase remains ahead. |

## After development: deployment, handover and support (EOI items 15–20)

These are still required by the EOI, but they are **not indicators of whether the application features have been developed**. The staging environment exists for development and review; it is not equivalent to formal production deployment and handover.

| # | Deliverable | Project phase |
|---|---|---|
| 15 | Deployment and configuration | Final deployment after development and approval |
| 16 | Hosting setup and technical documentation | Production setup and delivery documentation; engineering notes can be prepared earlier |
| 17 | Administrator and user manuals | Delivery preparation and user onboarding after features stabilize |
| 18 | Training/orientation | Post-development rollout |
| 19 | Initial post-deployment technical support and maintenance | Post-launch support |
| 20 | Full source-code and system handover | Final contractual handover |

## Verified current live state
- Website HTTPS responds, and protected pages redirect unauthenticated requests.
- 17 LGAs in the database.
- 2 staff accounts (1 Administrator, 1 Focal Person, 0 MEAL Reviewers).
- 0 field reports and 0 evidence files at the time checked.
- Evidence Storage dashboard, private R2 connection check and SHA-256 audit/migration commands deployed. No actual R2 credentials/remote bucket configured, so all new evidence still defaults to local private storage.
- Most recent isolated Laravel test suite: **73 passed, 613 assertions**.
- Native Android source code exists but has not been verified as a signed, installable APK or field-tested on a physical Android device.

## Next: complete development before formal testing

1. Complete authorized editing of previously submitted report details, with revision history and an immutable audit trail.
2. Finish native Android functional flows, offline report/evidence handling, sync retries and an installable release build.
3. Finish security and data-protection software requirements, including private evidence handling and automated recovery functionality. Cloudflare R2 activation additionally depends on approved credentials.
4. Consolidate the technical design and final user-facing interface, including remaining RAWYOD-agreed website integration **code**.

Once software development is complete, proceed in order to **formal QA/UAT (item 14)**, then **production deployment, manuals, training, support and handover (items 15–20)**.

## Important scope distinction
The EOI says implementation should be completed in an agreed period following contract signing and does not itself specify an 18 November deadline. Any exact dates from the separate signed agreement must be checked against that agreement, not attributed to the EOI.

**Conclusion:** The core digital platform is substantially implemented. For development tracking, use **7 implemented and 6 partial across items 1–13**, without treating items 15–20 as development gaps. Formal testing (item 14) and post-development delivery (items 15–20) are separate project phases, with their own completion and acceptance criteria.
