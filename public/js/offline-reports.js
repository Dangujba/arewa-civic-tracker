// Increment 1 offline field reporting: local draft + idempotent outbox.
// This is NOT a claim of full offline image/evidence support or encrypted storage.
(() => {
  'use strict';
  const form = document.querySelector('form[data-user-id]');
  if (!form) return;
  const userId = form.dataset.userId;
  const info = document.getElementById('offline-status');
  const idInput = document.getElementById('submission_id');
  const dbName = 'arewa-civic-tracker-v1';
  const dbVersion = 1;
  let db;
  const report = message => { info.textContent = message; };
  if (!globalThis.crypto?.randomUUID || !globalThis.indexedDB) {
    report('Offline saving is unavailable in this browser. Use an updated browser with HTTPS.');
    return;
  }
  if (!idInput.value) idInput.value = crypto.randomUUID();
  const request = indexedDB.open(dbName, dbVersion);
  request.onupgradeneeded = () => {
    const local = request.result;
    if (!local.objectStoreNames.contains('drafts')) local.createObjectStore('drafts', { keyPath: 'userId' });
    if (!local.objectStoreNames.contains('outbox')) local.createObjectStore('outbox', { keyPath: 'key' });
  };
  request.onerror = () => report('Cannot access local drafts. Check private browsing / device storage settings.');
  request.onsuccess = () => { db = request.result; form.dataset.offlineManaged='1'; recoverDraft(); syncPending(); };
  function tx(store,mode,operation) {
    return new Promise((resolve,reject) => {
      if (!db) return reject(Error('Local database unavailable'));
      const transaction = db.transaction(store,mode);
      const output = operation(transaction.objectStore(store));
      transaction.oncomplete = () => resolve(output.result);
      transaction.onerror = () => reject(transaction.error || Error('Device storage failed'));
      transaction.onabort = () => reject(transaction.error || Error('Device storage aborted'));
    });
  }
  function payload() {
    const obj=Object.fromEntries(new FormData(form).entries());
    delete obj._token;
    if (!obj.client_submission_id) { obj.client_submission_id=crypto.randomUUID(); idInput.value=obj.client_submission_id; }
    return obj;
  }
  function load(obj) {
    for (const [key,value] of Object.entries(obj || {})) {
      const input = form.elements.namedItem(key);
      if (input && typeof value === 'string') input.value=value;
    }
  }
  async function recoverDraft() {
    try {
      const old=await tx('drafts','readonly',s=>s.get(userId));
      if (old?.payload) { load(old.payload); report('Unsynced draft recovered from this device.'); }
      else report('Online field reporting ready. You can also save a draft locally.');
    } catch(e) { report(e.message); }
  }
  async function saveDraft() {
    try { await tx('drafts','readwrite',s=>s.put({userId,payload:payload(),savedAt:new Date().toISOString()})); report('Draft saved on this device. It has not been submitted to RAWYOD.'); }
    catch(e) { report(e.message); }
  }
  async function clearDraft() { await tx('drafts','readwrite',s=>s.delete(userId)); }
  async function enqueue(data) {
    const key=`${userId}:${data.client_submission_id}`;
    const existing=await tx('outbox','readonly',s=>s.get(key));
    if (existing && JSON.stringify(existing.payload) !== JSON.stringify(data)) throw Error('This submission is already queued with different data. Sync or discard the existing report first.');
    if (!existing) await tx('outbox','readwrite',s=>s.put({key,userId,payload:data,savedAt:Date.now()}));
  }
  async function syncPending(redirectId=null) {
    if (!db) return;
    if (!navigator.onLine) return report('Offline: pending reports remain safely stored on this device until you reconnect.');
    try {
      const items=await tx('outbox','readonly',s=>s.getAll());
      const mine=items.filter(x=>x.userId===userId);
      if (!mine.length) return;
      report(`Synchronizing ${mine.length} pending report(s)...`);
      for (const item of mine) {
        let r;
        try {r=await fetch(form.action,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(item.payload)});}
        catch(e) {report('Network unavailable. Submission retained on device.');return;}
        if (r.status === 401 || r.status===419 || r.redirected) return report('Session expired. Sign in again, then synchronize pending reports.');
        if (r.status === 409) return report('Submission conflict. Keep the draft and contact an administrator.');
        if (!r.ok) {const body=await r.json().catch(()=>({}));return report(`Sync paused: ${body.message || 'server rejected a report'} (${r.status}). The report is still on this device.`);}
        await tx('outbox','readwrite',s=>s.delete(item.key));
        if(idInput.value===item.payload.client_submission_id)await clearDraft();
        const body=await r.json();
        report(`Submitted ${body.reference}. Pending reports are synchronized.`);
        idInput.value=crypto.randomUUID();
        if(redirectId===item.payload.client_submission_id && body.redirect){window.location.assign(body.redirect);return;}
      }
    } catch(e) {report(`Could not synchronize: ${e.message}`);}
  }
  document.getElementById('new-report').addEventListener('click',async () => {
    if (!confirm('Start a new report? The current form fields will be cleared. Already queued reports will remain saved.')) return;
    form.reset(); idInput.value=crypto.randomUUID();
    await clearDraft(); report('New blank report ready. Previously queued reports remain on this device.');
  });
  document.getElementById('save-draft').addEventListener('click',saveDraft);
  document.getElementById('sync-outbox').addEventListener('click',syncPending);
  form.addEventListener('submit',async e=> {
    if (!db) return;
    e.preventDefault();
    if (!form.reportValidity()) return;
    const data=payload();
    try {await enqueue(data);await clearDraft();report('Report queued on this device. Attempting secure synchronization...');await syncPending(data.client_submission_id);}
    catch(err) {report(`Could not queue report: ${err.message}`);}
  });
  window.addEventListener('online',syncPending);
  window.addEventListener('pagehide',()=>{ /* Explicit Save prevents surprising persistence of partially typed sensitive records. */ });
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(()=>{});
})();
