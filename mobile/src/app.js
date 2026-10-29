import './style.css';
import { Capacitor } from '@capacitor/core';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Geolocation } from '@capacitor/geolocation';
import { Network } from '@capacitor/network';
import { SecureStorage } from '@aparajita/capacitor-secure-storage';
import { API_ROOT } from './config.js';

const native = Capacitor.isNativePlatform();
const $ = (id) => document.getElementById(id);
const key = (part) => 'arewa.field.' + part;
let token = null, account = null, catalogs = null, files = [], editId = null, busy = false;
const memory = new Map();
const secureGet = async (part) => native ? await SecureStorage.get(key(part)) : memory.get(part) ?? null;
const secureSet = async (part, value) => native ? SecureStorage.set(key(part), value) : memory.set(part, value);
const secureRemove = async (part) => native ? SecureStorage.remove(key(part)) : memory.delete(part);

function flash(text, error = false) {
  const msg = $('message'); msg.hidden = false; msg.classList.toggle('error', error);
  msg.textContent = text;
}
function safeNode(tag, text, className = '') {
  const el = document.createElement(tag); el.textContent = text; if (className) el.className = className; return el;
}
function setBusy(value) {
  busy = value;
  for (const id of ['save-report', 'sync', 'login-submit']) $(id).disabled = value;
}
function errorMessage(body, fallback = 'Request unsuccessful') {
  if (body?.errors) return Object.values(body.errors).flat().join('\n');
  return body?.message || fallback;
}
async function request(path, method = 'GET', data = undefined, bearer = token) {
  const headers = { Accept: 'application/json' };
  if (bearer) headers.Authorization = 'Bearer ' + bearer;
  const options = { method, headers, cache: 'no-store' };
  if (data instanceof FormData) options.body = data;
  else if (data !== undefined) { headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(data); }
  const response = await fetch(API_ROOT + path, options);
  const body = await response.json().catch(() => null);
  if (!response.ok) {
    const ex = new Error(errorMessage(body, 'HTTP ' + response.status));
    ex.status = response.status; throw ex;
  }
  return body;
}
function showView() {
  $('login-view').hidden = !!account;
  $('workspace').hidden = !account;
  $('welcome').textContent = account ? 'Welcome, ' + account.name : '';
  if (account && catalogs) loadCatalogs();
  renderQueue().catch((e) => flash(e.message, true));
}
function loadCatalogs() {
  for (const [id, list, label] of [
    ['lga', catalogs?.lgas || [], 'Select assigned LGA'],
    ['sector', catalogs?.sectors || [], 'Select sector']
  ]) {
    const el = $(id); const previous = el.value;
    el.replaceChildren(new Option(label, ''));
    for (const item of list) el.add(new Option(item.name, String(item.id)));
    if ([...el.options].some((o) => o.value === previous)) el.value = previous;
  }
  if (!(catalogs?.lgas || []).length) flash('No LGA is assigned to your account. Contact the RAWYOD Administrator.', true);
}
async function networkStatus() {
  try { return (await Network.getStatus()).connected; }
  catch { return navigator.onLine; }
}
async function updateConnectivity() {
  const online = await networkStatus();
  $('network').textContent = online ? 'Online' : 'Offline';
  $('network').classList.toggle('offline', !online);
  return online;
}
async function login(event) {
  event.preventDefault(); if (busy) return;
  setBusy(true);
  try {
    const payload = { email: $('email').value.trim(), password: $('password').value,
      otp: $('otp').value.trim() || undefined, device_name: 'Arewa Android field' };
    const data = await request('/login', 'POST', payload, null);
    token = data.token; account = data.user;
    await secureSet('token', token); await secureSet('account', JSON.stringify(account));
    catalogs = await request('/bootstrap');
    await secureSet('catalogs', JSON.stringify(catalogs));
    $('password').value = ''; $('otp').value = '';
    showView(); flash('Signed in. Field reporting is available.');
    await syncPending(false);
  } catch (e) { flash(e.message, true); }
  finally { setBusy(false); }
}
async function restoreSession() {
  try {
    token = await secureGet('token');
    account = JSON.parse((await secureGet('account')) || 'null');
    catalogs = JSON.parse((await secureGet('catalogs')) || 'null');
    if (!token || !account) { token = null; account = null; return; }
    if (await updateConnectivity()) {
      try { catalogs = await request('/bootstrap'); await secureSet('catalogs', JSON.stringify(catalogs)); }
      catch (e) { if (e.status === 401 || e.status === 403) {
        token = null; account = null; await secureRemove('token'); await secureRemove('account');
        flash('Sign in again to synchronize saved reports.', true);
      } else flash('Using locally cached field locations until connectivity returns.'); }
    }
  } catch (e) { flash('Secure storage could not be opened: ' + e.message, true); }
}
async function logout() {
  if ((await queueForCurrentUser()).length) {
    flash('Pending encrypted reports remain on this device. Synchronize them before signing out.', true); return;
  }
  if (await networkStatus()) { try { await request('/logout', 'POST', {}); } catch { /* offline token still expires */ } }
  token = null; account = null; catalogs = null; files = [];
  await Promise.all(['token', 'account', 'catalogs'].map(secureRemove));
  showView(); flash('Signed out successfully.');
}
function openQueueDatabase() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('arewa-field-queue-v1', 1);
    req.onupgradeneeded = () => { if (!req.result.objectStoreNames.contains('pending')) req.result.createObjectStore('pending', { keyPath: 'id' }); };
    req.onerror = () => reject(req.error);
    req.onsuccess = () => resolve(req.result);
  });
}
async function dbAction(mode, action) {
  const db = await openQueueDatabase();
  try {
    return await new Promise((resolve, reject) => {
      const tx = db.transaction('pending', mode);
      const request = action(tx.objectStore('pending'));
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
      tx.onerror = () => reject(tx.error);
    });
  } finally { db.close(); }
}
async function getKey() {
  if (!native) throw new Error('Secure offline evidence requires the native Android application.');
  let base64 = await secureGet('encryption-key');
  if (!base64) {
    const random = crypto.getRandomValues(new Uint8Array(32));
    base64 = btoa(String.fromCharCode(...random));
    await secureSet('encryption-key', base64);
  }
  const bytes = Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
  return crypto.subtle.importKey('raw', bytes, 'AES-GCM', false, ['encrypt', 'decrypt']);
}
async function encrypt(data) {
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const encoded = new TextEncoder().encode(JSON.stringify(data));
  const cipher = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, await getKey(), encoded));
  return { iv: Array.from(iv), cipher: cipher.buffer };
}
async function decrypt(record) {
  const data = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(record.iv) },
    await getKey(), record.cipher);
  return JSON.parse(new TextDecoder().decode(data));
}
async function queueForCurrentUser() {
  if (!account) return [];
  const all = await dbAction('readonly', (store) => store.getAll());
  return all.filter((r) => r.userId === account.id).sort((a, b) => a.savedAt.localeCompare(b.savedAt));
}
async function addDraft(id, report) {
  const encrypted = await encrypt(report);
  await dbAction('readwrite', (store) => store.put({ id, userId: account.id, savedAt: new Date().toISOString(), ...encrypted }));
}
async function deleteDraft(id) { await dbAction('readwrite', (store) => store.delete(id)); }
function displayFiles() {
  $('attachments').replaceChildren(...files.map((file, index) => {
    const item = document.createElement('li');
    item.append(safeNode('span', file.name + ' · ' + Math.round(file.size / 1024) + ' KB'));
    const remove = safeNode('button', 'Remove', 'outline'); remove.type = 'button';
    remove.onclick = () => { files.splice(index, 1); displayFiles(); };
    item.append(remove); return item;
  }));
}
async function addFile(name, type, dataUrl) {
  if (files.length >= 5) throw new Error('Maximum five evidence files per report.');
  if (!['image/jpeg','image/png','image/webp','application/pdf'].includes(type))
    throw new Error('Only JPEG, PNG, WebP and PDF evidence is accepted.');
  const encoded = dataUrl.slice(dataUrl.indexOf(',') + 1);
  const size = Math.floor(encoded.length * 3 / 4);
  if (size > 5242880) throw new Error('Evidence exceeds the 5 MB per-file limit.');
  files.push({ name, type, dataUrl, size }); displayFiles();
}
function asDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(file);
  });
}
async function chooseFiles(event) {
  try {
    for (const file of event.target.files) await addFile(file.name, file.type, await asDataUrl(file));
  } catch (e) { flash(e.message, true); }
  event.target.value = '';
}
async function camera() {
  try {
    const image = await Camera.getPhoto({ quality: 72, resultType: CameraResultType.DataUrl,
      source: CameraSource.Camera, width: 1600, correctOrientation: true });
    if (!image.dataUrl) throw new Error('The camera did not return a photograph.');
    await addFile('field-photo-' + Date.now() + '.jpeg', 'image/jpeg', image.dataUrl);
  } catch (e) { flash('Camera: ' + e.message, true); }
}
async function captureGps() {
  try {
    const coord = (await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 18000 })).coords;
    $('report-form').elements.latitude.value = coord.latitude.toFixed(7);
    $('report-form').elements.longitude.value = coord.longitude.toFixed(7);
    flash('GPS captured. Review the location before submitting.');
  } catch (e) { flash('GPS access was unsuccessful. You may enter coordinates manually. ' + e.message, true); }
}
function reportPayload() {
  const form = $('report-form');
  const values = Object.fromEntries(new FormData(form).entries());
  return { ...values, client_submission_id: editId || crypto.randomUUID(),
    evidence: files.map((f) => ({ ...f })) };
}
async function submitForm(event) {
  event.preventDefault(); if (busy || !account) return;
  const form = $('report-form'); if (!form.reportValidity()) return;
  const report = reportPayload();
  if (!catalogs?.lgas?.some((l) => String(l.id) === report.lga_id)) {
    flash('Choose an LGA assigned to your account.', true); return;
  }
  setBusy(true);
  let queued = false;
  try {
    if (native) {
      await addDraft(report.client_submission_id, report);
      editId = null; files = []; form.reset(); displayFiles();
      form.elements.observation_date.value = new Date().toISOString().slice(0,10);
      flash('Report encrypted and saved. Synchronization will run when connected.');
      await renderQueue(); queued = true;
    } else {
      // Browser development preview has no Android Keystore, so offline queueing is disabled.
      await sendReport(report);
      editId = null; files = []; form.reset(); displayFiles();
      flash('Report submitted online.');
    }
  } catch (e) { flash(e.message, true); }
  finally { setBusy(false); }
}
function formData(report) {
  const form = new FormData();
  for (const [name, value] of Object.entries(report)) {
    if (name !== 'evidence' && value !== '' && value !== undefined) form.append(name, String(value));
  }
  report.evidence.forEach((file) => {
    const binary = atob(file.dataUrl.split(',')[1]);
    const bytes = Uint8Array.from(binary, (c) => c.charCodeAt(0));
    form.append('evidence[]', new Blob([bytes], { type: file.type }), file.name);
  });
  return form;
}
async function sendReport(report) { return request('/reports', 'POST', formData(report)); }
async function syncPending(showNotice = true) {
  if (busy || !account || !token || !native) return;
  if (!(await updateConnectivity())) {
    if (showNotice) flash('Offline. Encrypted reports are safe on this device.'); return;
  }
  setBusy(true);
  let successful = 0, problem = '';
  try {
    for (const record of await queueForCurrentUser()) {
      try {
        const report = await decrypt(record);
        await sendReport(report);
        await deleteDraft(record.id);
        successful++;
      } catch (e) {
        problem = e.message;
        if ([401, 403].includes(e.status)) break;
      }
    }
    await renderQueue();
    if (problem) flash(successful + ' report(s) synchronized. Others remain encrypted: ' + problem, true);
    else if (successful || showNotice) flash(successful + ' pending report(s) synchronized successfully.');
  } finally { setBusy(false); }
}
async function renderQueue() {
  const list = native ? await queueForCurrentUser() : [];
  $('outbox-count').textContent = String(list.length);
  const ul = $('pending'); ul.replaceChildren();
  for (const item of list) {
    const li = document.createElement('li');
    try {
      const data = await decrypt(item);
      li.append(safeNode('strong', data.title || 'Untitled report'));
      li.append(safeNode('small', 'Saved: ' + new Date(item.savedAt).toLocaleString(), 'muted'));
      const edit = safeNode('button','Edit draft','outline');
      edit.onclick = () => restoreDraft(item.id).catch((e) => flash(e.message, true));
      li.append(edit);
    } catch { li.append(safeNode('span','Encrypted draft cannot be read. Do not uninstall the app.')); }
    ul.append(li);
  }
  if (!list.length) ul.append(safeNode('li', 'No offline reports waiting.'));
}
async function restoreDraft(id) {
  const list = await queueForCurrentUser();
  const record = list.find((item) => item.id === id); if (!record) return;
  const draft = await decrypt(record), form = $('report-form');
  for (const [field, value] of Object.entries(draft)) {
    if (field !== 'evidence' && field !== 'client_submission_id' && form.elements[field]) form.elements[field].value = value;
  }
  files = draft.evidence || []; editId = id; displayFiles(); setTab('report');
  flash('Draft loaded for editing. Save again before leaving the form.');
}
function setTab(active) {
  for (const tab of ['report','outbox','history']) {
    $('tab-' + tab).hidden = tab !== active;
    document.querySelector('[data-tab="'+tab+'"]').setAttribute('aria-current',tab===active?'page':'false');
  }
  if (active === 'outbox') renderQueue().catch((e) => flash(e.message, true));
  if (active === 'history') history().catch((e) => flash(e.message, true));
}
async function history() {
  if (!(await networkStatus())) { flash('Connect to the internet to refresh submitted reports. Offline drafts remain available.', true); return; }
  const result = await request('/reports');
  const ul = $('history'); ul.replaceChildren();
  for (const report of result.reports) {
    const li = document.createElement('li');
    li.append(safeNode('strong', report.reference + ' · ' + report.title));
    li.append(safeNode('small', (report.lga?.name || '') + ' · ' + report.verification_status.replaceAll('_',' '), 'muted'));
    const show = safeNode('button','View details','outline');
    show.onclick = () => detail(report.id).catch((e) => flash(e.message,true));
    li.append(show); ul.append(li);
  }
  if (!result.reports.length) ul.append(safeNode('li','No reports submitted yet.'));
}
async function detail(id) {
  const result = await request('/reports/' + id), report = result.report;
  const area = $('report-detail'); area.replaceChildren();
  const div = safeNode('div', '', 'panel');
  div.append(safeNode('h2',report.reference + ' · ' + report.title));
  div.append(safeNode('p',report.description || ''));
  div.append(safeNode('p','Verification: ' + report.verification_status.replaceAll('_',' ')));
  for(const followup of report.followups || []) div.append(safeNode('p','Follow-up: '+followup.note));
  if(report.verification_status === 'clarification_requested') {
    const form=document.createElement('form'), input=document.createElement('textarea');
    input.required=true;input.minLength=5;input.placeholder='Respond to the MEAL clarification request';
    const button=safeNode('button','Submit clarification');
    form.append(input,button);
    form.onsubmit=async(event)=>{event.preventDefault();try{await request('/reports/'+id+'/clarification','POST',{response:input.value});flash('Clarification submitted.');await detail(id);}catch(e){flash(e.message,true);}};
    div.append(form);
  }
  area.append(div);
}
async function boot() {
  $('report-form').elements.observation_date.value = new Date().toISOString().slice(0,10);
  $('login-form').addEventListener('submit',login);
  $('report-form').addEventListener('submit',submitForm);
  $('evidence').addEventListener('change',chooseFiles);
  $('camera').addEventListener('click',camera);
  $('locate').addEventListener('click',captureGps);
  $('signout').addEventListener('click',()=>logout().catch((e)=>flash(e.message,true)));
  $('sync').addEventListener('click',()=>syncPending().catch((e)=>flash(e.message,true)));
  $('refresh-history').addEventListener('click',()=>history().catch((e)=>flash(e.message,true)));
  for(const el of document.querySelectorAll('[data-tab]'))el.addEventListener('click',()=>setTab(el.dataset.tab));
  await updateConnectivity();
  await restoreSession(); showView();
  Network.addListener('networkStatusChange', (status)=>{
    $('network').textContent=status.connected?'Online':'Offline';
    $('network').classList.toggle('offline',!status.connected);
    if(status.connected && account)syncPending(false).catch((e)=>flash(e.message,true));
  });
  if(account)await syncPending(false);
  if (!native) flash('Browser preview mode: online reporting only. Encrypted offline evidence requires the Android build.');
}
boot().catch((e)=>flash(e.message,true));
