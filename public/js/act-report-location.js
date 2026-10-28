(()=>{
'use strict';
const form=document.querySelector('form[data-user-id]');if(!form)return;
const lat=form.querySelector('[name="latitude"]'),lng=form.querySelector('[name="longitude"]');
const status=document.getElementById('gps-status'),button=document.getElementById('gps-capture');
const files=document.getElementById('evidence-files'),preview=document.getElementById('evidence-preview');
const show=(v,bad=false)=>{if(status){status.textContent=v;status.style.color=bad?'#aa4740':'#316e48'}};
const capture=()=>{
 if(!navigator.geolocation){show('GPS is not supported here. You may enter coordinates manually.',true);return}
 if(!window.isSecureContext){show('GPS requires HTTPS. You may enter coordinates manually.',true);return}
 button.disabled=true;button.innerHTML='<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Detecting…';
 show('Requesting location permission…');
 navigator.geolocation.getCurrentPosition(pos=>{
  // Do not overwrite coordinates a user has deliberately edited.
  if(!lat.dataset.edited&&!lng.dataset.edited){
   lat.value=pos.coords.latitude.toFixed(7);lng.value=pos.coords.longitude.toFixed(7);
  }
  show('GPS detected (accuracy about '+Math.round(pos.coords.accuracy)+' metres). You can edit both values.');
  button.disabled=false;button.innerHTML='<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Detect location again';
 },err=>{
  show(err.code===1?'Location permission denied. Enter coordinates if available.':'Location unavailable. Try again or edit coordinates manually.',true);
  button.disabled=false;button.innerHTML='<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Retry GPS';
 },{enableHighAccuracy:true,timeout:13000,maximumAge:60000});
};
for(const inp of [lat,lng])inp?.addEventListener('input',()=>inp.dataset.edited='1');
button?.addEventListener('click',()=>{lat.dataset.edited='';lng.dataset.edited='';capture()});
if(files){
 files.addEventListener('change',()=>{
  preview.replaceChildren();
  const chosen=[...files.files];
  if(chosen.length>5){preview.textContent='Choose up to 5 files.';return}
  for(const file of chosen){
   const line=document.createElement('div');line.className='evidence-file-line';
   const name=document.createElement('span');name.textContent=file.name+' ('+(file.size/1048576).toFixed(1)+' MB)';
   line.append(name);
   if(file.type.startsWith('image/')){
    const img=document.createElement('img');img.alt='Selected evidence preview';img.width=70;img.height=60;
    img.src=URL.createObjectURL(file);img.addEventListener('load',()=>URL.revokeObjectURL(img.src),{once:true});
    line.prepend(img);
   }
   preview.append(line);
  }
 });
}
if(navigator.geolocation && window.isSecureContext)capture();
})();
