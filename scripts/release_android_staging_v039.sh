#!/usr/bin/env bash
# Safe, auditable v0.3.9 staging APK build and deployment on the authorized Arewa VPS.
set -Eeuo pipefail
VERSION=0.3.9
SOURCE=/tmp/arewa-six-20261029
SITE=/var/www/arewa.kasua.ng
SDK=/tmp/arewa-native-android-sdk
BT="$SDK/build-tools/35.0.0"
RELEASE_DIR="$SITE/storage/app/private/native-release"
NAME="arewa-civic-tracker-v0.3.9-debug.apk"
APK="$SOURCE/android-native/app/build/outputs/apk/debug/app-debug.apk"
CONFIG="$SITE/config/native.php"

test "$EUID" -eq 0 || { echo "Requires authorized staging administrator"; exit 2; }
test -d "$SOURCE/.git" && test -f "$BT/aapt" && test -f "$BT/apksigner"
test -d "$RELEASE_DIR" && test -f "$CONFIG"
cd "$SOURCE"
git pull --ff-only
echo "Building $(git rev-parse --short HEAD)"
(
  cd android-native
  ANDROID_HOME="$SDK" ANDROID_SDK_ROOT="$SDK" \
    ./gradlew --no-daemon --console=plain testDebugUnitTest assembleDebug
)
test -s "$APK"

# Verify package ID and version, the actual embedded Yobe ward data,
# and the update-compatible signing certificate BEFORE changing public config.
PACKAGE="$("$BT/aapt" dump badging "$APK" | grep '^package:')"
grep -F "name='ng.org.rawyod.arewa'" <<<"$PACKAGE" >/dev/null
grep -F "versionName='0.3.9'" <<<"$PACKAGE" >/dev/null
unzip -Z1 "$APK" | grep -Fx 'assets/yobe_wards.json' >/dev/null
unzip -p "$APK" assets/yobe_wards.json | python3 -c '
import json,sys
x=json.load(sys.stdin)
assert len(x["lgas"])==17
assert sum(len(l["wards"]) for l in x["lgas"])==178
assert any(l["lga"]=="damaturu" and len(l["wards"])==11 for l in x["lgas"])
print("Validated: all 178 wards and 17 Yobe LGAs embedded in APK")
'
"$BT/apksigner" verify --print-certs "$APK" >/dev/null
PREV_NAME="$(php -r 'function storage_path($p){return $p;} $c=require $argv[1];echo $c["android_apk_name"];' "$CONFIG")"
PREV="$RELEASE_DIR/$PREV_NAME"
test -s "$PREV"
NEW_CERT="$("$BT/apksigner" verify --print-certs "$APK" | sed -n 's/^Signer #1 certificate SHA-256 digest: //p')"
OLD_CERT="$("$BT/apksigner" verify --print-certs "$PREV" | sed -n 's/^Signer #1 certificate SHA-256 digest: //p')"
test -n "$NEW_CERT" && test "$NEW_CERT" = "$OLD_CERT" || {
  echo "Signing mismatch: refusing to replace installable app"; exit 3;
}
echo "Verified signing matches $PREV_NAME"
TMP="$RELEASE_DIR/.pending-v039-$$"
trap 'rm -f "$TMP"' EXIT
install -o actsite -g www-data -m 0640 "$APK" "$TMP"
mv "$TMP" "$RELEASE_DIR/$NAME"
sha256sum "$RELEASE_DIR/$NAME"

BACKUP="$CONFIG.before-v039-$(date -u +%Y%m%d%H%M%S)"
cp -a "$CONFIG" "$BACKUP"
TMP_CONFIG="$SITE/config/.native-v039-$$"
trap 'rm -f "$TMP" "$TMP_CONFIG"' EXIT
cat > "$TMP_CONFIG" <<'PHP'
<?php
return [
    'android_apk_path' => storage_path('app/private/native-release/arewa-civic-tracker-v0.3.9-debug.apk'),
    'android_apk_name' => 'arewa-civic-tracker-v0.3.9-debug.apk',
];
PHP
chown actsite:www-data "$TMP_CONFIG"
chmod 0644 "$TMP_CONFIG"
mv "$TMP_CONFIG" "$CONFIG"
cd "$SITE"
if ! runuser -u actsite -- php artisan optimize:clear >/dev/null ||
   ! runuser -u actsite -- php artisan config:cache >/dev/null ||
   ! runuser -u actsite -- php artisan route:cache >/dev/null ||
   ! runuser -u actsite -- php artisan view:cache >/dev/null; then
  cp -a "$BACKUP" "$CONFIG"
  runuser -u actsite -- php artisan optimize:clear >/dev/null || true
  echo "Cache failure; restored config from $BACKUP"; exit 4
fi
HEADERS="$(curl -fsSI --connect-timeout 10 --max-time 30 https://arewa.kasua.ng/download/android)"
if ! grep -E '^HTTP/.* 200' <<<"$HEADERS" >/dev/null ||
   ! grep -F "$NAME" <<<"$HEADERS" >/dev/null; then
  cp -a "$BACKUP" "$CONFIG"
  runuser -u actsite -- php artisan optimize:clear >/dev/null || true
  echo "Download verification failed; restored config"; exit 5
fi
echo "Published verified Android v$VERSION at https://arewa.kasua.ng/download/android"
