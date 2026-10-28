// Only cache public static resources. Never cache authenticated HTML, API data or evidence.
const CACHE='arewa-public-v1';
const ASSETS=['/js/offline-reports.js','/manifest.webmanifest'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(ASSETS))));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k))))));
self.addEventListener('fetch',event=>{
  if(event.request.method!=='GET')return;
  const url=new URL(event.request.url);
  if(url.origin!==self.location.origin||!ASSETS.includes(url.pathname))return;
  event.respondWith(caches.match(event.request).then(cache=>cache||fetch(event.request)));
});
