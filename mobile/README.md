# Arewa Civic Tracker: Android Field Application

This is a locally bundled Capacitor 8 Android field client for RAWYOD's Civic Intelligence Focal Persons. It is **not** a remote website wrapper.

## Field capabilities

- Authenticated report submission over the Laravel /api/v1 endpoints, using SHA-256-hashed Sanctum bearer tokens which expire after 30 days.
- No plaintext password or bearer token is written into web storage. Tokens and the AES-GCM encryption key use Android Keystore-backed native secure storage.
- Encrypted IndexedDB outbox for report fields, GPS coordinates and up to five JPEG, PNG, WebP or PDF evidence attachments.
- Native Android camera, GPS and connectivity plugins.
- UUID-based idempotent synchronization after connection recovery, with manual retry and editable pending drafts.
- Individual focal accounts can only submit observations from their assigned LGAs. Clarification responses and submitted report statuses are available in-app.

## Build instructions

Requires Node.js 22+, JDK 21, Android SDK command-line tools/platforms, and Android build tools. These are developer build prerequisites, **not** dependencies on the Arewa VPS for normal app users.

1. Run npm ci from mobile/.
2. Run npm run android:add once to generate the Android project with privacy and GPS manifest settings.
3. After changing the app, run npm run android:sync.
4. For a debug APK, run npm run android:debug, or open android/ in Android Studio.
5. To distribute a signed release, use a RAWYOD-approved signing keystore, distribution plan and the approved production API endpoint.

The generated android/ project is excluded from source control because it can be reproduced from the Capacitor configuration and scripts.

## API URL

The QA API origin is declared in src/config.js. Change it to RAWYOD's authorized production domain before release. Do not embed user accounts, passwords, service keys or signing keys in the repository.

## Security and limitations

The server validates every form and file regardless of client checks. Local offline drafts are AES-GCM encrypted, but the mobile application still needs real-device QA of background execution, camera, storage limits, OS kill/restart and connectivity changes. Synchronization runs on reconnect **while the application is active**, not as an independently installed Android background service. Uninstalling the app removes its drafts. The app intentionally prevents sign-out when unsynchronized drafts remain.

This is source ready for native testing, **not an accepted/signed APK**. GPS permission must be granted by the device user.
