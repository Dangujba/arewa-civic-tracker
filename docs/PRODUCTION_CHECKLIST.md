# AREWA CIVIC TRACKER
## Production Delivery & Acceptance Checklist
**Project:** RAWYOD VOICES TO VOTES | **Implementation partner:** InnoBytes Lab LTD  
**Contract reference:** RAWYOD/NYFF/VOICES-TO-VOTES/2026/002  
**Staging:** https://arewa.kasua.ng | **Production target:** RAWYOD-owned website/subdomain (subject to hosting assessment)  
**Scope baseline:** RAWYOD EOI, InnoBytes technical proposal, October 2026 Software Development Agreement.  
**Contract milestones:** 2 Nov requirements; 4 Nov interactive presentation; 6 Nov feedback agreement; 13 Nov web/Android/website integration; 16 Nov UAT; 18 Nov deployment and handover.  

> Status rules: **[x] = implemented AND verified with recorded evidence; [ ] = not yet proven complete.** A page, button, repository, or configuration being present is not enough. A production release additionally needs security, accessibility, end-to-end tests and RAWYOD sign-off. Features marked as incomplete must not be described as delivered. Photos on the landing page are documentary context, not RAWYOD-verified monitoring submissions.

### 01. Project governance & scope
- [x] EOI requirements and signed-agreement milestones identified in planning.
- [ ] RAWYOD-approved user requirements specification (URS), workflow and feature acceptance matrix.
- [ ] Approved list of changes to RAWYOD's existing website.
- [ ] Written approval of Android APK delivery mechanism and signing/distribution ownership.
- [ ] Named RAWYOD focal person, approvers and feedback turnaround.
- [ ] Commercial, hosting, support and scope differences confirmed through signed documentation.
- [ ] Project risks, decision register, issue log, change-control and milestone demo records maintained.

### 02. Hosting, environments and source control
- [x] Dedicated VPS staging domain resolves to the assigned server.
- [x] Laravel 13 and PHP-FPM installed in an isolated application directory.
- [x] HTTPS certificate validated; HTTP redirects to HTTPS.
- [x] Dedicated MySQL 8 database identity and schema configured, migrations successful.
- [x] 17 LGAs and 8 initial sectors seeded in MySQL.
- [ ] Private GitHub repository and protected main branch (currently PUBLIC; sanitized application source is published, with runtime credentials excluded).
- [ ] Verified environment variable and secret separation, staging vs production.
- [x] Node dependencies installed and a Vite production asset build completed on staging.
- [ ] Repeatable build/deployment script, release tags and rollback rehearsed.
- [ ] Per-site permissions, PHP-FPM resource limits, rotation and security headers reviewed.
- [ ] RAWYOD cPanel or production-host technical assessment, approved deployment architecture.

### 03. Public landing page & design system
- [x] Original Laravel starter at `/` replaced by a branded civic-monitoring landing page.
- [x] Responsive page styles and secure-staff-portal login styling deployed.
- [x] Working carousel implementation deployed with accessible slide buttons and pause logic in JavaScript.
- [x] Three authentic Yobe infrastructure photos added with visible creator and license attribution.
- [x] Homepage, login, stylesheet, script and main image returned HTTP 200 in server check.
- [x] Puppeteer responsive checks confirm zero horizontal overflow at 390px mobile width; desktop page also renders without script errors.
- [ ] Final visual sign-off across devices, consistent RAWYOD-approved brand identity and official logo.
- [ ] Cross-browser carousel touch/swipe, keyboard, reduced-motion and screen-reader tests.
- [ ] Search engine metadata, favicon, accessible component patterns, no broken navigation or images.
- [ ] Final image-use and licensing approval with RAWYOD; no ambiguous implication of verified reports.
- [ ] Lighthouse/performance scores and low-bandwidth optimization accepted.

### 04. Authentication & user administration
- [x] Administrator-created focal/MEAL users require a new password on first login; protected actions redirect to secure password rotation, verified in integration tests.
- [x] TOTP 2FA opt-in activation, QR provisioning, six-digit login challenge, throttling, one-time recovery codes and secure disable flow tested; mandatory organizational 2FA policy remains to be approved.
- [ ] Role-specific login for RAWYOD administrator, MEAL reviewer and Civic Focal Person.
- [ ] Account activation, deactivation, password reset, session expiry and rate limits tested.
- [ ] Server-enforced permissions in every route, API, export, evidence download and map query.
- [ ] Admin user invitation, account directory and role management tested in browser.
- [ ] User activity audit, failed authentication logging and permission-change history.

### 05. Reporting & public project monitoring
- [x] End-to-end test submission with validation and generated reference passes on MySQL QA database.
- [x] Required type, sector, LGA/community, description, status and observation date validated in submission workflow.
- [ ] Edit/clarification/follow-up workflows including immutable audit trail and version conflicts.
- [ ] Report list pagination, full-text search, filters, sort, detail view and deep linking.
- [x] Duplicate client submission ID returns the existing report; conflicting payload returns HTTP 409 (tested).
- [ ] Independent project progress tracking distinct from RAWYOD verification status.
- [ ] Data consistency checks for out-of-range dates, absent GPS and duplicate project records.

### 06. Evidence and protected files
- [x] Authenticated image upload with MIME/size validation and persisted file verified in isolated integration test.
- [x] Initial report form supports five JPEG/PNG/WebP/PDF evidence files, local JPEG/WebP compression and preview; validated in Laravel tests.
- [ ] Dedicated native Android camera UX and multi-image capture tested on physical devices.
- [x] Evidence saved to private Laravel storage and fetched via an authorization-checked endpoint; further penetration testing remains pending.
- [ ] Authorized preview/download, controlled deletion and evidence audit trail.
- [ ] Storage quotas, integrity checks and upload-failure recovery tested.

### 07. Review, approval and follow-up
- [x] MEAL review queue and role-specific server-authorized status options implemented and verified via authenticated feature tests.
- [ ] MEAL review queue with reviewer assignment, filtering and comments.
- [ ] Request clarification, field response, verify, approve, reject, close, reopen transition rules.
- [ ] All transitions authorized, timestamped, attributed and auditable.
- [ ] Rejected and unverified records excluded from approved statistics and public outputs by default.
- [ ] Notifications and deadlines work under both normal and degraded connections.

- [x] Admin, MEAL and focal roles have responsive role-specific sidebar with collapsing desktop navigation and mobile drawer; server permissions tested.\n\n### 08. GIS mapping and geography
- [x] Real GeoJSON polygons for all 17 LGAs extracted and name-validated from geoBoundaries NGA ADM2 (CC BY 4.0).
- [x] Approved-status GIS feed returns the approved report with its stored GPS and reference in MySQL integration test; broader map disclosure review is pending.
- [x] Interactive 17-LGA boundaries, selection, report list, basemap themes, marker clustering, density heatmap, distance measurement, fullscreen and exports deployed on staging. Browser QA used synthetic feed data.
- [ ] Full GIS browser acceptance on real authorized sessions, marker clustering with large datasets, map accuracy and performance still required.
- [x] GIS explorer implements filters by sector, LGA, date, report type, search and verification/progress status; Laravel access and parameter tests pass.
- [ ] Consistent disclosure rules and map location privacy for sensitive records.
- [ ] Mobile/low-bandwidth map rendering and tile-provider usage/licence acceptance still required. Synthetic browser QA verified desktop/mobile map rendering without horizontal overflow.

### 09. Dashboards, search and exports
- [x] Dashboard template reads the current database-derived KPI and grouping variables (render tested).
- [ ] Dashboard chart interactivity, KPI edge cases and full source-data reconciliation.
- [ ] Time trends, charts, sector distribution and drill-down tables.
- [x] Authenticated management reports with combined filters, CSV compatibility and native XLSX exports tested.
- [x] Role-restricted management PDF summary generator tested with selected filters and aggregate totals; final RAWYOD report layout approval pending.
- [x] XLSX writes text cells explicitly, exports are role restricted and downloads have bounded row counts; further data-volume testing pending.
- [ ] No placeholder numbers or fabricated metrics in live operational dashboards.

### 10. Offline PWA and sync
- [x] 192px and 512px PWA application icons generated and served successfully.
- [ ] Installable manifest and coherent authenticated offline shell tested in real browsers.
- [x] IndexedDB stores draft fields and file blobs on the same browser/device; synthetic browser test confirmed retained evidence in a queued submission.
- [ ] Offline outbox with exponential retry, unique submission IDs and conflict resolution.
- [x] Online/offline/sync/error feedback implemented for queued reports, including preserved file blobs.
- [ ] Physical-device flight-mode/reconnect E2E remains pending. Synthetic browser QA verified offline file queue, multipart upload replay and queue clearing; isolated Laravel integration verified idempotent case/evidence persistence.
- [ ] Logout/account-switch clearing local sensitive information tested.

### 11. Android APK
- [ ] Choose packaging approach (Capacitor/other) and document web-to-Android technical architecture.
- [ ] Android installable signed APK with package ID and version strategy.
- [ ] Permissions for camera, GPS, uploads and offline storage tested on real Android devices.
- [ ] Android app authenticates against same controlled API and database as the web system.
- [ ] Offline report creation and successful sync tested after relaunch and lost connectivity.
- [ ] Distribution, signing-key custody, updates and installation instructions handed to RAWYOD.

### 12. Asynchronous interactions & usability
- [x] Global JavaScript async form handler and loading/error feedback code deployed.
- [x] Login invalid-credentials AJAX feedback and button-reset behavior verified with Puppeteer.
- [ ] Every submission, file upload, review action, export and navigation tested for loading state.
- [ ] AJAX validation errors displayed by field with accessible focus/error summaries.
- [ ] Disable repeated submissions, maintain idempotency on retries and restore button state.
- [ ] Background status indicators distinguish saving, pending, syncing, failed and completed.
- [ ] Browser interruption, server error, expired session, authorization error and slow network simulated.
- [ ] Accessible mobile forms, responsive tables, keyboard navigation and touch target audit.

### 13. Security, privacy and reliability
- [ ] Risk assessment including civic evidence/geolocation confidentiality.
- [ ] CSRF, XSS, SQL injection, IDOR, mass assignment and file path tests.
- [ ] Password policy, rate limits, session controls and audit trail integrity.
- [ ] TLS, restrictive CORS, headers, dependency review and production error-handling audit.
- [ ] Database and evidence recovery procedure tested and documented.
- [ ] Data minimization, retention, incident communication and RAWYOD ownership controls approved.
- [ ] Production monitoring, error reporting and uptime/performance baseline.

### 14. Integrate RAWYOD website
- [ ] Obtain authorized cPanel access and hosting-upgrade specifications.
- [ ] Record current site framework and approved updates without touching unrelated content.
- [ ] Configure RAWYOD-controlled Tracker URL and accessible website entry point.
- [ ] SSL, redirects, private/public boundaries and mobile site navigation tested.
- [ ] No cross-domain authentication or data sharing without explicit approval.

### 15. Automated testing, UAT and release
- [x] Current automated suite passes: 28 tests, 239 assertions, in the default environment and dedicated MySQL 8 QA database.
- [ ] Expanded release-wide test coverage including all contracted modules.
- [x] Isolated MySQL 8 integration test database: migrations, password rotation, report submission, uploaded evidence, MEAL review, administrator approval, GIS feed and dashboard, 28 tests pass.
- [ ] Browser E2E tests for three roles and entire submit→verify→dashboard/map workflow.
- [ ] Performance tests at expected initial load, including upload and exports.
- [ ] Android acceptance testing with representative supported devices.
- [ ] RAWYOD staff sign-off on all critical scenarios and defects resolved.
- [ ] Final build repeatable, release tagged, rollback and migration validated.

### 16. Documentation, training, handover and support
- [ ] Approved URS, architecture, deployment manual, database schema and data dictionary.
- [ ] Administrator and focal-person illustrated user guides.
- [ ] Training/orientation completed and documented.
- [ ] Project source, repository access, UI assets, licence notices, deployment credentials and configuration transferred securely.
- [ ] Signed acceptance/handover record and outstanding issues register.
- [ ] Contractually agreed stabilization/support period confirmed and documented.


## Evidence log
| Verification | Evidence | Result |
|---|---|---|
| Initial HTTP/HTTPS | Nginx + curl tests | HTTPS 200, HTTP → HTTPS |
| Database conversion | Laravel `db:show --counts` on staging | MySQL 8; 10 tables, 17 LGAs, 8 sectors, one existing administrator account (credentials unchanged) |
| Landing, login, CSS, JS, photo | HTTPS responses | All HTTP 200 |
| Authentication protection | Unauthenticated HTTP requests | Dashboard and map redirect to login |
| JS parser | `node --check public/js/act-interactions.js` | Passed |
| Automated tests after landing changes and GIS addition | Laravel tests | 16 passed; 123 assertions |
| Browser responsiveness | Puppeteer desktop 1440px and mobile 390px | No page errors; mobile horizontal overflow corrected |
| Frontend pipeline | npm + Vite build | Asset build completed |
| GIS boundaries | geoBoundaries NGA ADM2 | 17 unique Yobe LGAs, 122,897-byte GeoJSON; HTTP 200 |
| Login async error path | Puppeteer invalid-credentials test | Feedback shown; login remains protected; submit button reset |
| End-to-end operational workflow | Laravel HTTP feature test using isolated MySQL 8 database | 28 tests, 239 assertions; Focal submit, evidence, MEAL review, admin approval, GIS marker, dashboard and CSV confirmed |
| Secure onboarding | Password rotation and account policy tests | First login requires password change; wrong current password rejected |
| JavaScript offline/AJAX coordination | Handler review + Node syntax check | AJAX form handler skips IndexedDB-managed reporting form; successful sync can open the created report |

**Next verification gates:** real-browser three-role acceptance on isolated QA data, permission audit for all file/actions, mobile/offline evidence synchronization, PDF/Excel export, Android APK, and RAWYOD acceptance. No synthetic reports are stored in the staging production database.

### Release verification — 28 October 2026

- GitHub `main`: merged GIS/sidebar release `fbb4007` (pull request #1).
- Laravel tests: 24 passing, 171 assertions (default and isolated MySQL QA).
- Headless browser with synthetic report feed: 17 authentic LGA polygons, dashboard/navigation shell, filter updates, heat layer, sidebar desktop collapse and mobile drawer; no client-side errors or horizontal overflow at tested widths.
- Clean clone from `main`: `npm ci`, `npm run build`, generated Font Awesome/Lucide/Leaflet assets verified; Composer manifest validated.
- Staging: landing page, login, local mapping assets and GeoJSON respond HTTP 200. One real administrator account preserved; zero synthetic production case records.
- Remaining: real authenticated browser UAT, live-user training, large-data GIS performance, complete offline synchronization, Android APK and final security/RAWYOD acceptance. GitHub Actions CI status requires separate investigation.

### Proposal gap closure — 28 October 2026

- **Initial evidence submission:** supported, with up to five privately stored validated image/PDF attachments, multi-file previews and idempotent retries.
- **Automatic GPS:** browser permission prompt, accuracy feedback, visible editable latitude and longitude, manual fallback; synthetic browser verification completed.
- **Management reporting:** new role-restricted Reports & Analytics workspace, 12-month trend, verification distribution, LGA and sector breakdown, filterable register, native XLSX and PDF downloads.
- **Two-factor authentication:** opt-in TOTP QR activation, encrypted secret/recovery hashes, verification challenge, recovery-code consumption and password/code-verified removal. Existing administrator not automatically enrolled.
- **Offline evidence:** file blobs persisted in IndexedDB and synchronized through multipart upload; synthetic browser QA confirmed one image and real coordinates with zero JavaScript errors. End-to-end field Android acceptance still required.
- **Environment:** Arewa-specific PHP-FPM upload limit increased to 6MB per file and 35MB per request; Arewa Nginx max request set to 35MB; configurations tested and reloaded. Other domains were not edited.
- **Tests:** 28 passed, 239 assertions on SQLite and isolated MySQL, plus clean-clone composer/npm install and frontend build. No synthetic record inserted into the staging database.
- **Outstanding:** mobile APK, organization-wide 2FA policy, offline evidence encryption on shared devices, actual staff UAT, security review, RAWYOD acceptance and broader performance testing.
