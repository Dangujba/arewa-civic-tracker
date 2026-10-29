# Arewa Civic Tracker — Native Android field app

This is the **native Kotlin / Jetpack Compose** Android application for RAWYOD. It uses no Capacitor, WebView, JavaScript or IndexedDB. It connects to the existing Laravel field API at `/api/v1`, without modifying the web administration platform.

## Stack
- Kotlin 2.2.21, Jetpack Compose, AndroidX Room 2.8.5, WorkManager 2.12.0 and OkHttp.
- Room persists an offline queue; report content is AES-256-GCM encrypted with a non-exportable Android Keystore key.
- Evidence is encrypted into private app files (never external/public storage). The data is decrypted only into memory for HTTPS transmission.
- WorkManager schedules network-constrained retries; report UUIDs remain stable for server idempotency.
- Native photo capture uses Android Activity Result API with a private FileProvider, not a WebView.
- GPS uses Android LocationManager with runtime permissions.

## v0.3.2 branded launcher and fingerprint setting

**Build and staging distribution:** v0.3.2 compiled successfully on the connected Arewa VPS (Android SDK 36, JDK 17). Android unit tests and debug APK assembly succeeded. Its signer SHA-256 matched the existing v0.3.1 debug build, enabling in-place update. The public download now serves v0.3.2. Fingerprint behavior still requires physical-device acceptance before a production release.


- The launcher uses the same approved hand-drawn Arewa map-and-pin WebP mark as the website, reused from the repository's original asset blob. The adaptive icon scales across launchers.
- The native **Settings** tab provides an opt-in **fingerprint lock** for a previously authenticated focal person's active session.
- Android's BiometricPrompt requires an enrolled **strong biometric** to enable, disable and unlock the local app. It is not a substitute for the server login or TOTP.
- After backgrounding the app, field screens remain hidden until fingerprint verification. Users can explicitly choose account-password sign-in instead; doing so requires internet but preserves queued drafts.
- Fingerprint enablement is scoped to the authenticated account and cleared on sign-out. On phones without enrolled strong biometrics the setting remains unavailable.
- This is a local UI session lock, not cryptographic authentication of a server request or a way to recover a revoked or expired token.

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

## Login and DNS recovery

The native app authenticates email and password first. Only a verified account with enabled 2FA receives a short-lived challenge and then sees the authenticator screen. Tokens are issued only after verification. All other accounts sign in directly. Native input fields and buttons use square corners.

For the approved staging API hostname, Android DNS is tried first. A fallback to the verified IPv4 address is attempted only if that hostname cannot be resolved. HTTPS certificate validation and SNI remain active. This fallback must be updated if the API hosting IP changes. Internet access is still required to sign in.

## v0.2.0 native completion

- Encrypted offline drafts can be restored, edited, and resubmitted under their original idempotency ID.
- WorkManager claims drafts before uploading; interrupted uploads can resume safely.
- Expired API token reauthentication preserves the original account's encrypted draft data.
- JPEG, PNG, WebP photographs can be downsampled and encoded as JPEG, targeting 2 MB and enforcing the backend 5 MB limit. PDFs remain unchanged.
- Native form validation covers future dates, statuses and paired GPS coordinates.
- Flat, square native action controls.

The debug APK is for controlled testing, not production distribution. It uses Android debug signing and the staging API. RAWYOD must sign and verify a separate release artifact for production.

## v0.3.0 online similarity and reference data
- Report form includes an optional online check for potentially related field reports. It never blocks offline submission.
- Enrolled officers can select published categories and communities for their assigned LGAs while retaining manual community entry.
- Category IDs are transmitted only when a valid category for the selected report type has been chosen.
- The staging v0.3.2 debug APK is available to all visitors at https://arewa.kasua.ng/download/android without signing in to the web portal. Staff authentication inside the app remains required to access monitoring data. This is not production-signed.

## v0.3.1 submitted report amendments

Field officers can open their submitted reports and edit eligible records online. The application fetches the latest server-side version, supports correction of report details with a mandatory reason, and preserves the shared web/API version history. Approved, rejected or closed reports are locked. Offline draft editing remains available separately. This build remains debug-signed for development verification.

## v0.3.3 anonymous public viewing in Android

- The first-run and reauthentication sign-in screens offer **Browse public reports**, which opens the same read-only transparency view inside the native app.
- Public users do not create accounts, provide passwords, or upload reports. RAWYOD staff continue using existing authenticated workflows.
- WebView browsing is restricted to HTTPS Arewa transparency pages; file access, content access, third-party cookies and mixed content are disabled.
- Public data remains on the same server publication allowlist, with approximate map coordinates and no private evidence.
- v0.3.3 requires a fresh successful APK build, and device-level tests of the embedded map remain part of release QA.
