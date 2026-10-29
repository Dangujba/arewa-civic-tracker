# Arewa Civic Tracker: Field Security and Android Development Release (29 October 2026)

## Completed in the source release
- Enforce focal LGA restrictions for all field accounts, including those with zero assignments.
- Restrict MEAL access to unassigned submissions and submissions assigned to the requesting reviewer. Apply the same authorization to case details, evidence, progress changes, the GIS feed, and analytics.
- Provide in-app notifications for reviewer assignment, clarification requests and responses, and scheduled deadline reminders. Reminders are idempotent per calendar day and only generated for active assigned reviewers.
- Add authenticated Android API v1 endpoints for login, logout, assigned locations/sectors, submit/list/view field reports, evidence download and clarification response.
- Native mobile source is available at mobile/: locally bundled Capacitor 8 client, secure token storage, encrypted offline evidence outbox, GPS, camera and online synchronization.
- Automated unit/feature tests and Vite mobile bundle build pass in an isolated QA checkout.

## Production gates still open
- Android debug/signed APK build on a machine with JDK and Android SDK. Neither is installed on the staging VPS. Signing keys must remain under RAWYOD's control.
- Real Android camera, GPS, low-bandwidth, permission and airplane-mode tests, including application restart and drafts during authentication expiry.
- RAWYOD approval of the production API origin and mobile distribution.
- Cloudflare R2 activation after approved private storage credentials and backup tests.
- Email delivery and escalation of notifications: the implemented reminders are in-app, not outbound email.
- Secure account recovery, complete audit coverage, restoration drill and full penetration/load tests.
- RAWYOD website integration and formal acceptance/handover.
- GitHub Actions runners: repository jobs currently fail before executing any steps, with runner_id=0. The root cause is not yet confirmed, so do not claim green CI.

## Safety
The live Laravel environment and database credentials must never be copied into the public GitHub repository.
The new mobile API uses revocable 30-day hashed Sanctum bearer tokens. User password rotation and existing 2FA are respected.
Capacitor's android/ directory is regenerated from tracked source. An Android signing key is never committed.
