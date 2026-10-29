# Arewa Civic Tracker

Civic monitoring, public project reporting and evidence verification for **Roadmap for Women and Youth Development (RAWYOD)** under the **VOICES TO VOTES** project, implemented by **InnoBytes Lab LTD**.

**Staging application:** https://arewa.kasua.ng

## Platform

- Public landing page featuring real images and an interactive carousel
- Role-based workspaces for RAWYOD administrators, Civic Intelligence Focal Persons and MEAL reviewers
- Structured issue and public project reporting, field evidence and observation follow-ups
- Verification, clarification, approval and auditable activity history
- Interactive Leaflet map with official-reference administrative polygons for all 17 Yobe LGAs
- Monitoring dashboard, interactive GIS, administrative Reports & Analytics including filtered CSV, native Excel (XLSX) and PDF management exports
- Evidence-aware mobile report form with image/PDF attachments, permission-based editable GPS coordinates, unchanged original evidence, private optimized previews, and offline evidence outbox
- Optional authenticator-app two-factor sign-in with secure QR setup, one-time recovery codes and account security controls

**Development status (29 October 2026):** The EOI and technical-proposal development baseline is implemented, including the final six closing items. The isolated Laravel suite passed **116 tests, 859 assertions**. This is not RAWYOD acceptance. The staging Android v0.3.7 debug APK is publicly downloadable at https://arewa.kasua.ng/download/android without website login. Native app access still requires authorized staff credentials. Production signing, physical-device offline acceptance and final deployment remain separate work.

## Development deliverables

- [Development closure and EOI compliance](docs/DEVELOPMENT_CLOSURE_2026-10-29.md)
- [User Requirements Specification (URS v1.0)](docs/URS_AREWA_CIVIC_TRACKER_V1.md)
- [System Architecture and Technical Design (v1.0)](docs/ARCHITECTURE_AND_TECHNICAL_DESIGN_V1.md)
- [UI/UX Design and Screen Baseline (v1.0)](docs/UI_UX_DESIGN_BASELINE_V1.md)

Formal testing, deployment, onboarding and handover remain tracked separately in the [Production Readiness Checklist](docs/PRODUCTION_CHECKLIST.md).

## Technology

Laravel 13, PHP 8.3+, Blade, JavaScript, MySQL 8+, Leaflet, Chart.js, PhpSpreadsheet, Dompdf, TOTP/QR libraries and Tailwind CSS/Vite. The production database target is MySQL, not SQLite.

## Setup

1. Install PHP 8.3+, Composer, Node.js and MySQL.
2. Create a dedicated MySQL database and user.
3. Run `cp .env.example .env`, then provide database credentials in your local `.env`.
4. Run `composer install` and `php artisan key:generate`.
5. Run `php artisan migrate --seed`.
6. Run `npm ci && npm run build`.
7. Use `php artisan serve` locally or configure a web server to serve only the `public` directory.
8. Run `php artisan test`.
9. Provision the first administrator with `php artisan act:create-admin admin@example.org`; the command prompts for the account name and password.
10. After login, open **Account → Security settings** and activate TOTP 2FA using an authenticator app. Save the one-time recovery codes securely.
11. Administrators and MEAL reviewers can open **Reports & Analytics** for filtered trends, `.xlsx` spreadsheets and PDF summaries. Public visitors cannot access exports.
12. For on-site uploads configure the **site-specific** PHP-FPM pool to accept 6MB files and 35MB form bodies, and Nginx `client_max_body_size 35M` (5 evidence files × 5MB plus multipart overhead). Never modify unrelated websites to raise Arewa limits.

Never commit the real `.env`, credentials, test evidence containing personal data, uploads or database dumps.

## Image and boundary attribution

The site uses photographs from Wikimedia Commons, credited in the interface. Administrative LGA geometry is from the geoBoundaries open Nigeria ADM2 dataset and is credited in the map. Refer to original source pages for applicable reuse terms. These images are **illustrative geographic context**, not submitted or verified RAWYOD cases.

## Governance

RAWYOD acceptance, ownership and handover requirements are governed by the signed project agreement. Do not use this public repository to store confidential project records.

## Operational safeguards

- Uploaded evidence lives in non-public Laravel storage, and downloads require report access permissions. File types are restricted to JPEG, PNG, WebP and PDF, up to five files of 5MB each.
- Camera/GPS capture depends on browser permission and device support. Manual location entry remains possible when unavailable.
- The responsive browser's optional offline evidence queue can retain data in local browser storage; avoid sensitive field drafts on shared devices. The separate **native Android app** encrypts local report drafts and evidence using Android Keystore AES-GCM, with server authentication required before upload. Device lifecycle, retention and real-device acceptance must still be confirmed before production.
- 2FA secrets and recovery-code hashes are stored in encrypted database fields. The initial admin account is not automatically enrolled in 2FA.
- The management XLSX export is limited to 10,000 filtered records and the PDF summary prints up to 150 records alongside an aggregate total. Both require authenticated review permissions.

### Public transparency view

The read-only `/transparency` page is open to everyone, with no registration. Staff administrators explicitly publish approved report metadata using the case-detail publication control. Unpublished and unapproved reports stay private, while edits to public fields withdraw visibility until republished. The map uses approximate coordinates, with private evidence and staff identities excluded. The Android v0.3.3 login screen offers **Browse public reports** for anonymous use of the same view.

### Original-quality photographic evidence (Android v0.3.4)

Uploaded JPEG/PNG/WebP photos are retained byte-for-byte in private evidence storage, with a SHA-256 checksum. The server separately generates a metadata-stripped, high-quality WebP preview for authorized staff. The preview is displayed within the restricted case detail screen, while **Download original** serves the unmodified file. A preview-generation failure does not change the original. The current cap is 10 MB/photo, 5 MB/PDF, five files per submission. Web reporting also uploads originals instead of lossy precompressed copies; the Android offline outbox encrypts its staged originals. This feature does not publish private evidence in the public transparency view. RAWYOD production signing and device UAT remain separate.

### Android v0.3.5 field application UX and offline access

- Safe system-bar layout, actual material navigation/action icons, snackbar notices instead of blocking inline error banners, native observation-date picker, form state preservation during tab navigation and external pickers, and explicit sign-out confirmation.
- Profile page shows the signed-in focal person's cached account details. Settings provides fingerprint lock, optional locally secured offline PIN, authenticated password changes, and an end-to-end TOTP enable/disable flow via field-only bearer-protected APIs. Save displayed one-time recovery codes securely.
- A *previously paired* device may be unlocked with its enrolled fingerprint or preconfigured offline PIN; saved assigned LGAs/categories, encrypted queued drafts/evidence, cached profile and previously loaded report listings can be viewed offline. First account enrollment, changes to password/2FA, reports not yet cached, live public web content and server synchronization necessarily require internet; never imply otherwise.
- Network failures present short actionable messages rather than raw socket diagnostics; legacy hard-coded IP resolution fallback was removed. GPS and camera/file-picking callbacks fail safely and preserve form state; runtime behavior on the affected physical device remains pending acceptance.
- Staff profile/password/2FA API regression tests passed; a native debug APK was compiled and tested, pending physical-device crash reproduction and acceptance.

### Yobe State Ward support (29 October 2026)

- The browser and Android field forms now capture **Ward separately from Community**, using the currently chosen LGA to filter choices. Search wards or choose **Ward not listed? Enter manually**. Existing reports and queued historical mobile payloads without a Ward remain valid.
- The Yobe-only reference table contains 178 wards across 17 LGAs, derived from the [public Nigerian state/LGA/ward repository](https://github.com/afeibukun/nigerian-state-lgas-wards-polling-units). The original slugs are retained in `database/data/yobe_wards.json`; normalized labels are displayed to users. Reference data is treated as editable-local-input assistance, not an official legal boundary authority.
- The source dataset is bundled locally so offline Android reference data is available after the initial bootstrap. New wards are not automatically added to the shared roster when typed manually; the report retains the typed ward as submitted.
- Report revisions, staff analytics, spreadsheet export, published report metadata, and API synchronization also support Ward. No public registration or write access was introduced.

### Android v0.3.7 capture/permissions repair

Camera, GPS and Add Evidence launchers are now registered on the MainActivity rather than its conditional Compose form; camera/GPS permissions are requested where appropriate, Camera receives explicit URI grants, and file-backed original photos are encrypted safely. Users see a status for every action, including cancellation, permission denial and GPS timeout. Android's SAF picker intentionally requires no broad storage permission.
