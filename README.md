# Arewa Civic Tracker

Civic monitoring, public project reporting and evidence verification for **Roadmap for Women and Youth Development (RAWYOD)** under the **VOICES TO VOTES** project, implemented by **InnoBytes Lab LTD**.

**Staging application:** https://arewa.kasua.ng

## Platform

- Public landing page featuring real images and an interactive carousel
- Role-based workspaces for RAWYOD administrators, Civic Intelligence Focal Persons and MEAL reviewers
- Structured issue and public project reporting, field evidence and observation follow-ups
- Verification, clarification, approval and auditable activity history
- Interactive Leaflet map with official-reference administrative polygons for all 17 Yobe LGAs
- Monitoring dashboard, geographic and sector views, filtering and CSV exports
- Mobile-friendly interface with local offline reporting groundwork

**Development status:** Active. Features not yet accepted are tracked in [Production Readiness Checklist](docs/PRODUCTION_CHECKLIST.md). The Android APK, full offline synchronization and final deployment to RAWYOD hosting require further integration and testing.

## Technology

Laravel 13, PHP 8.3+, Blade, JavaScript, MySQL 8+, Leaflet, Tailwind CSS/Vite. The production database target is MySQL, not SQLite.

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

Never commit the real `.env`, credentials, test evidence containing personal data, uploads or database dumps.

## Image and boundary attribution

The site uses photographs from Wikimedia Commons, credited in the interface. Administrative LGA geometry is from the geoBoundaries open Nigeria ADM2 dataset and is credited in the map. Refer to original source pages for applicable reuse terms. These images are **illustrative geographic context**, not submitted or verified RAWYOD cases.

## Governance

RAWYOD acceptance, ownership and handover requirements are governed by the signed project agreement. Do not use this public repository to store confidential project records.
