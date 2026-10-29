import fs from 'node:fs';
const path = 'android/app/src/main/AndroidManifest.xml';
if (!fs.existsSync(path)) {
  console.error('Native Android platform is not yet generated. Run npm run android:add first.');
  process.exit(1);
}
let xml = fs.readFileSync(path,'utf8');
xml = xml.replace('android:allowBackup="true"', 'android:allowBackup="false"');
const permissions = [
  'android.permission.ACCESS_COARSE_LOCATION',
  'android.permission.ACCESS_FINE_LOCATION',
  'android.permission.ACCESS_NETWORK_STATE'
];
for(const permission of permissions) {
  if (!xml.includes('android:name="'+permission+'"')) {
    xml=xml.replace('</manifest>', '    <uses-permission android:name="'+permission+'" />\n</manifest>');
  }
}
fs.writeFileSync(path,xml);
console.log('Android privacy, GPS and network permissions verified.');
