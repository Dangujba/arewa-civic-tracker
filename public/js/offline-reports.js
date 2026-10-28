// Field draft and outbox, including evidence blobs, scoped to the signed-in account.
// IndexedDB is not encrypted. Use only personal devices for sensitive evidence.
(()=>{
'use strict';
const form=document.querySelector('form[data-user-id]');if(!form)return;
const userId=form.dataset.userId,info=document.getElementById('offline-status'),idInput=document.getElementById('submission_id');
const filesInput=document.getElementById('evidence-files'),submit=document.getElementById('submit-btn');
const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
let db=null,syncing=false,draftFiles=[];
const report=message=>{if(info)info.textContent=message};
const toast=(message,type='success')=>window.ACTToast?.(message,type);
function setBusy(on){if(!submit)return;submit.disabled=on;submit.setAttribute('aria-busy',String(on));submit.innerHTML=on?(window.ACTSpinnerMarkup||'')+' Saving & synchronizing…':'Submit report'}
if(!globalThis.indexedDB||!globalThis.crypto?.randomUUID){report('Offline storage unavailable; online upload is still supported.');return}
if(!idInput.value)idInput.value=crypto.randomUUID();
const request=indexedDB.open('arewa-civic-tracker-v1',1);
request.onupgradeneeded=()=>{const d=request.result;if(!d.objectStoreNames.contains('drafts'))d.createObjectStore('drafts',{keyPath:'userId'});if(!d.objectStoreNames.contains('outbox'))d.createObjectStore('outbox',{keyPath:'key'})};
request.onerror=()=>report('Local offline storage could not be opened. Check browser storage settings.');
request.onsuccess=()=>{db=request.result;form.dataset.offlineManaged='1';recoverDraft();syncPending()};
function tx(store,mode,fn){return new Promise((resolve,reject)=>{if(!db)return reject(Error('Offline storage unavailable'));const t=db.transaction(store,mode),r=fn(t.objectStore(store));t.oncomplete=()=>resolve(r.result);t.onerror=()=>reject(t.error||Error('Device storage unavailable'));t.onabort=()=>reject(t.error||Error('Device storage aborted'))})}
function fields(){const f=new FormData(form),out={};for(const [key,value] of f.entries())if(key!=='_token'&&key!=='evidence[]'&&typeof value==='string')out[key]=value;if(!out.client_submission_id){out.client_submission_id=crypto.randomUUID();idInput.value=out.client_submission_id}return out}
function attachments(){const live=filesInput?[...filesInput.files].filter(f=>f.size>0):[];return live.length?live:draftFiles}
function validateFiles(files){if(files.length>5)throw Error('Choose up to five evidence files.');for(const f of files){if(f.size>5*1024*1024)throw Error(f.name+' is larger than 5MB.');if(!['image/jpeg','image/png','image/webp','application/pdf'].includes(f.type))throw Error('Unsupported evidence format: '+f.name)}}
async function optimizeImage(file){
 if(!['image/jpeg','image/webp'].includes(file.type)||file.size<750000||!globalThis.createImageBitmap)return file;
 let bitmap;
 try{
  bitmap=await createImageBitmap(file);
  const max=1600,scale=Math.min(1,max/Math.max(bitmap.width,bitmap.height));
  const canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.round(bitmap.width*scale));canvas.height=Math.max(1,Math.round(bitmap.height*scale));
  canvas.getContext('2d').drawImage(bitmap,0,0,canvas.width,canvas.height);
  const blob=await new Promise(resolve=>canvas.toBlob(resolve,file.type,.79));
  if(!blob||blob.size>=file.size)return file;
  return new File([blob],file.name,{type:blob.type,lastModified:file.lastModified});
 }catch{return file}
 finally{bitmap?.close?.()}
}
async function optimizedAttachments(){
 const original=attachments();validateFiles(original);
 const optimized=await Promise.all(original.map(optimizeImage));validateFiles(optimized);
 return optimized;
}
function loadFields(data){for(const [key,value] of Object.entries(data||{})){const input=form.elements.namedItem(key);if(input&&typeof value==='string')input.value=value}}
async function recoverDraft(){try{const saved=await tx('drafts','readonly',s=>s.get(userId));if(saved?.payload){loadFields(saved.payload);draftFiles=saved.files||[];report(saved.files?.length?'Recovered draft with '+saved.files.length+' stored evidence file(s). Save or submit to synchronize.':'Recovered your local draft.')}else report('Field reporting ready. Reports and evidence can be queued for synchronization.')}catch(e){report(e.message)}}
async function saveDraft(){try{const files=await optimizedAttachments();await tx('drafts','readwrite',s=>s.put({userId,payload:fields(),files,savedAt:Date.now()}));report('Draft and evidence saved on this device.');toast('Draft saved successfully')}catch(e){report('Draft saving failed: '+e.message)}}
async function clearDraft(){draftFiles=[];return tx('drafts','readwrite',s=>s.delete(userId))}
async function enqueue(data,files){validateFiles(files);const key=userId+':'+data.client_submission_id;const old=await tx('outbox','readonly',s=>s.get(key));if(old){if(JSON.stringify(old.payload)!==JSON.stringify(data))throw Error('A different report is already queued under this reference.');return}await tx('outbox','readwrite',s=>s.put({key,userId,payload:data,files,savedAt:Date.now()}))}
async function syncPending(redirectId=null){
 if(!db||syncing)return;
 if(!navigator.onLine){report('Offline: queued reports and evidence remain on this device until connection returns.');return}
 syncing=true;setBusy(true);
 try{
  const items=(await tx('outbox','readonly',s=>s.getAll())).filter(x=>x.userId===userId);
  if(!items.length)return;
  report('Synchronizing '+items.length+' pending report(s)…');
  for(const item of items){
   let body,headers={'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'};
   if(item.files?.length){
    body=new FormData();for(const [name,value]of Object.entries(item.payload))body.append(name,value);
    for(const file of item.files)body.append('evidence[]',file,file.name||'field-evidence');
   }else{body=JSON.stringify(item.payload);headers['Content-Type']='application/json'}
   let response;
   try{response=await fetch(form.action,{method:'POST',credentials:'same-origin',headers,body})}
   catch{return report('Network unavailable. The queued report and its evidence remain on this device.')}
   if(response.status===401||response.status===419||response.redirected)return report('Session expired. Sign in again, then retry synchronization.');
   if(response.status===409)return report('Submission conflict. The local report is retained for review.');
   if(!response.ok){const err=await response.json().catch(()=>({}));return report('Sync paused ('+response.status+'): '+(err.message||'server rejected report')+'. Local data retained.')}
   const result=await response.json();
   await tx('outbox','readwrite',s=>s.delete(item.key));
   if(idInput.value===item.payload.client_submission_id)await clearDraft();
   idInput.value=crypto.randomUUID();
   if(redirectId===item.payload.client_submission_id&&result.redirect){location.assign(result.redirect);return}
   report('Submitted '+result.reference+'. Evidence synchronized.');toast('Report submitted successfully')
  }
 }catch(e){report('Synchronization paused: '+e.message)}
 finally{syncing=false;setBusy(false)}
}
filesInput?.addEventListener('change',()=>{draftFiles=[]});
document.getElementById('save-draft')?.addEventListener('click',saveDraft);
document.getElementById('sync-outbox')?.addEventListener('click',()=>syncPending());
document.getElementById('new-report')?.addEventListener('click',async()=>{
 if(!(await (window.ACTConfirm?window.ACTConfirm('Start a new report?','The current draft will be cleared. Any already queued submissions will remain safe.'):Promise.resolve(confirm('Start a new report?')))))return;
 form.reset();idInput.value=crypto.randomUUID();await clearDraft();document.getElementById('evidence-preview')?.replaceChildren();report('New blank report ready.');toast('New report form ready')
});
form.addEventListener('submit',async e=>{
 if(!db)return; e.preventDefault();if(syncing||!form.reportValidity())return;
 setBusy(true);const data=fields();
 try{const files=await optimizedAttachments();await enqueue(data,files);await clearDraft();report('Report and evidence queued. Attempting secure synchronization…')}
 catch(e){report('Cannot queue report: '+e.message);setBusy(false);return}
 setBusy(false);await syncPending(data.client_submission_id);
});
window.addEventListener('online',()=>syncPending());
if('serviceWorker'in navigator)navigator.serviceWorker.register('/sw.js').catch(()=>{});
})();
