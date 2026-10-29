# Arewa Civic Tracker — Native Android field app

This is the **native Kotlin / Jetpack Compose** Android application for RAWYOD. It uses no Capacitor, WebView, JavaScript or IndexedDB. It connects to the existing Laravel field API at `/api/v1`, without modifying the web administration platform.

## Stack
- Kotlin 2.2.21, Jetpack Compose, AndroidX Room 2.8.5, WorkManager 2.12.0 and OkHttp.
- Room persists an offline queue; report content is AES-256-GCM encrypted with a non-exportable Android Keystore key.
- Evidence is encrypted into private app files (never external/public storage). The data is decrypted only into memory for HTTPS transmission.
- WorkManager schedules network-constrained retries; report UUIDs remain stable for server idempotency.
- Native photo capture uses Android Activity Result API with a private FileProvider, not a WebView.
- GPS uses Android LocationManager with runtime permissions.

## Build / QA
From `android-native/`, with Android SDK Platform 36, Build Tools 35.0.0, JDK 17:
`./gradlew testDebugUnitTest assembleDebug`

Installable debug artifact: `app/build/outputs/apk/debug/app-debug.apk`.
A production signed release must be signed by RAWYOD using its own keystore. Do not commit signing credentials.

## Offline handling
An officer must sign in online once, and receive an LGA/sector list. Later reports and evidence may be collected offline and stored until synchronization. Token expiry does **not** clear drafts. Uninstalling, manually clearing app data, damage to the device or Keystore invalidation can still cause data loss. WorkManager is best-effort and may be delayed by Android battery policies. Do not represent it as instantaneous or as a guaranteed permanent archive.

User-scoped draft ownership blocks a different account from seeing previous reports. Report fields, locally cached account/assigned-LGA data, tokens and evidence contents are encrypted. Existing drafts prevent signing out until synchronized. The backend rechecks all permissions, so assignments removed by an administrator may cause a queued report to be rejected. Rejected reports remain available in the outbox for follow-up.

## Release acceptance
Real-device acceptance still needs airplane-mode reporting and multi-day storage tests, GPS permission refusal, photographing, PDF selection, phone restart, network flapping, 5MB limits, session expiry, app upgrades, backup/restore policies and the final signed APK. The API origin in `app/build.gradle.kts` is staging and must be changed to an approved domain for distribution.
