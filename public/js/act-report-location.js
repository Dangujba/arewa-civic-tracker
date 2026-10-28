(()=>{
'use strict';
const form=document.querySelector('form[data-user-id]');if(!form)return;
const lat=form.querySelector('[name="latitude"]'),lng=form.querySelector('[name="longitude"]');
const status=document.getElementById('gps-status'),button=document.getElementById('gps-capture');
const files=document.getElementById('evidence-files'),preview=document.getElementById('evidence-preview'),drop=document.getElementById('evidence-dropzone');
const notify=(message,kind='error')=>{if(window.ACTToast)window.ACTToast(message,kind)};
const show=(v,bad=false)=>{if(status){status.textContent=v;status.style.color=bad?'#aa4740':'#316e48'}};
function capture(){
 if(!navigator.geolocation){show('GPS is not supported here. You may enter coordinates manually.',true);return}
 if(!window.isSecureContext){show('GPS requires HTTPS. You may enter coordinates manually.',true);return}
 button.disabled=true;button.innerHTML=(window.ACTSpinnerMarkup||'')+' Detecting…';
 show('Requesting location permission…');
 navigator.geolocation.getCurrentPosition(pos=>{
  if(!lat.dataset.edited&&!lng.dataset.edited){
   lat.value=pos.coords.latitude.toFixed(7);lng.value=pos.coords.longitude.toFixed(7);
  }
  show('GPS detected (accuracy approximately '+Math.round(pos.coords.accuracy)+' m). Coordinates remain editable.');
  button.disabled=false;button.innerHTML='<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Detect location again';
 },err=>{
  show(err.code===1?'Location permission denied. Enter coordinates if available.':'Location unavailable. Try again or edit coordinates manually.',true);
  button.disabled=false;button.innerHTML='<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Retry GPS';
 },{enableHighAccuracy:true,timeout:13000,maximumAge:60000});
}
for(const inp of [lat,lng])inp?.addEventListener('input',()=>inp.dataset.edited='1');
button?.addEventListener('click',()=>{lat.dataset.edited='';lng.dataset.edited='';capture()});
let urls=[];
function renderPreview(){
 for(const url of urls)URL.revokeObjectURL(url);urls=[];
 preview.replaceChildren();
 const chosen=[...files.files];
 if(chosen.length>5){const msg='Choose no more than five evidence files.';preview.textContent=msg;notify(msg);return}
 const allowed=new Set(['image/jpeg','image/png','image/webp','application/pdf']);
 for(const file of chosen){
  if(file.size>5*1024*1024||!allowed.has(file.type)){notify('Unsupported or oversized evidence file: '+file.name);continue}
  const line=document.createElement('div');line.className='evidence-file-line';
  const name=document.createElement('span');name.textContent=file.name+' ('+(file.size/1048576).toFixed(1)+' MB)';
  line.append(name);
  if(file.type.startsWith('image/')){
   const img=document.createElement('img');img.alt='Selected evidence preview';img.width=70;img.height=60;
   const url=URL.createObjectURL(file);urls.push(url);img.src=url;line.prepend(img);
  }else{
   const i=document.createElement('i');i.className='fa-solid fa-file-pdf';i.setAttribute('aria-hidden','true');line.prepend(i);
  }
  preview.append(line);
 }
 if(chosen.length)notify(chosen.length+' evidence file'+(chosen.length===1?'':'s')+' selected.','success');
}
files?.addEventListener('change',renderPreview);
if(drop&&files){
 drop.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();files.click()}});
 for(const type of ['dragenter','dragover'])drop.addEventListener(type,e=>{e.preventDefault();e.stopPropagation();drop.classList.add('is-dragging')});
 for(const type of ['dragleave','dragend'])drop.addEventListener(type,e=>{e.preventDefault();drop.classList.remove('is-dragging')});
 drop.addEventListener('drop',e=>{
  e.preventDefault();e.stopPropagation();drop.classList.remove('is-dragging');
  const chosen=[...(e.dataTransfer?.files||[])];
  const permitted=new Set(['image/jpeg','image/png','image/webp','application/pdf']);
  if(!chosen.length)return;
  if(chosen.length>5||chosen.some(f=>f.size>5*1024*1024||!permitted.has(f.type))){notify('Use up to five JPG, PNG, WebP or PDF files, maximum 5 MB each.');return}
  const dt=new DataTransfer();
  for(const file of chosen)dt.items.add(file);
  files.files=dt.files;
  files.dispatchEvent(new Event('change',{bubbles:true}));
 });
}
window.addEventListener('pagehide',()=>{for(const url of urls)URL.revokeObjectURL(url)});
if(navigator.geolocation&&window.isSecureContext)capture();
})();
