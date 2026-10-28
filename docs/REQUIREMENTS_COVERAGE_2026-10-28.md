# Arewa Civic Tracker - Requirements Coverage and Release Gates

**Review:** 28 October 2026. Grounding: RAWYOD's *Call for Expression of Interest* dated 7 September 2026; InnoBytes' submitted *Arewa Civic Tracker Technical Proposal*; and the subsequent signed implementation agreement effective 29 October 2026.

**Definition of completion:** code present is not the same as field-tested and accepted. Production completion requires end-to-end role testing, data security checks, performance evaluation, documented handover, and RAWYOD's written acceptance.

| Required capability | Current status | Remaining acceptance |
|---|---|---|
| Secure login and role-based access | Implemented | Account recovery/security review |
| Admin, focal, MEAL user roles | Implemented | Role-specific staff UAT |
| Account activation/deactivation | Implemented | Admin acceptance |
| User activity tracking | Partial | Login and administrative audit reporting |
| Public project and governance reporting | Implemented | Workflow UAT |
| LGA, sector, community, observation dates and progress | Implemented | Consistency and duplicate-detection validation |
| Supporting images/PDF evidence at submission | Implemented | Physical-device upload UAT |
| Automatic GPS and editable coordinates | Implemented | Accuracy and permission tests on real devices |
| Updating previously submitted reports | Partial | Approved editing/version-history workflow |
| Follow-ups, clarifications and approval/rejection | Implemented | Staff acceptance |
| Reporting dashboard and trends | Implemented | Data reconciliation and report design sign-off |
| Combined filters and searchable selections | Implemented | Keyboard/accessibility UAT |
| Excel/CSV and PDF summary exports | Implemented | Large-result and authorization tests |
| Interactive 17-LGA GIS map | Implemented | Map precision and large-dataset validation |
| Low-bandwidth image compression | Implemented for JPEG/WebP | Android network tests |
| Offline drafts and evidence outbox | Implemented groundwork | True airplane-mode/reconnect device UAT |
| 34 focal persons at initial scale | Not load tested | Provision/test roles and capacity |
| Mobile responsive reporting | Browser-tested | Real-device testing |
| Native Android deliverable | **Not delivered** | Capacitor standalone offline-capable app and signed APK |
| Installable PWA | Partial | Install/cache/upgrade tests |
| 2FA activation | Optional TOTP implemented | Enrolment policy and admin adoption |
| Private cloud evidence storage | R2 integration prepared, **not active** | RAWYOD approval, account keys, private-bucket tests, migration/backup |
| Backup, restore, security and incident process | Partial | Restore drill, penetration test, privacy approval |
| RAWYOD website changes and integration | Not accepted | Approved change list and implementation |
| URS/architecture and UI/UX prototype | Drafted/developed | RAWYOD approval and sign-off |
| Manuals, training, full handover | Not complete | Administrator/field manuals, training, credentials, handover |
| Acceptance and support | Not started | UAT and contracted post-acceptance support |

## UX corrections
- Report form fields use tighter spacing, responsive rows and searchable locally hosted selects.
- Evidence accepts drag/drop and click selection, supports preview, and validates file formats/count/size.
- GPS detects automatically with permission and remains editable.
- Sign-out uses a SweetAlert2 confirmation dialog; in-app messages use toast feedback and submission loading states.
- Online submission sends promptly; offline queue is a fallback, not a requirement.
- Dependencies are locally hosted and cacheable to reduce repeated low-bandwidth transfer.

## R2 architecture
- **Default remains private local storage**; existing evidence is unchanged.
- Optional `EVIDENCE_DISK=r2` uses a private Cloudflare R2 S3-compatible bucket through Laravel Flysystem.
- All file download links pass through server authorization. Never use a public bucket or embed access keys in the app.
- Switching needs an R2 account, private bucket credentials, approved hosting/data-processing arrangement, successful test upload/download, storage backup/recovery plan and RAWYOD authorization.
- A signed URL workflow for later large-file/poor-network uploads is a potential future optimization, not implemented.

## Android architecture
The original proposal emphasized a mobile-first PWA, while the signed agreement requires a separate Android deliverable. Capacitor is **not yet installed** and no APK has been produced. Plan a separate, locally bundled Capacitor field client and authenticated API. A remote-webview wrapper alone does not meet the agreed offline field requirements.
