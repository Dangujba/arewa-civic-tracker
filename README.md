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
- Evidence-aware mobile report form with image/PDF attachments, permission-based editable GPS coordinates, local image compression and offline evidence outbox
- Optional authenticator-app two-factor sign-in with secure QR setup, one-time recovery codes and account security controls

**Development status:** Active. Features not yet accepted are tracked in [Production Readiness Checklist](docs/PRODUCTION_CHECKLIST.md). The staging Android v0.3.1 debug APK is publicly downloadable at https://arewa.kasua.ng/download/android without website login. Native app access still requires authorized staff credentials. Production signing, physical-device offline acceptance and final deployment to RAWYOD hosting remain separate work.

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
- Offline drafts and evidence are stored **unencrypted in IndexedDB** on the reporting device. Do not use shared devices for sensitive field data. Device encryption, retention policy and real-device acceptance remain required before release.
- 2FA secrets and recovery-code hashes are stored in encrypted database fields. The initial admin account is not automatically enrolled in 2FA.
- The management XLSX export is limited to 10,000 filtered records and the PDF summary prints up to 150 records alongside an aggregate total. Both require authenticated review permissions.
