(()=>{
'use strict';
const dialog=window.Swal;
const tone={success:'#16563b',error:'#a73532',warning:'#9e6e2b',info:'#236b85'};
const showToast=(message,kind='success')=>{
 if(dialog){dialog.fire({toast:true,position:'top-end',icon:kind==='error'?'error':kind==='warning'?'warning':kind==='info'?'info':'success',title:String(message),showConfirmButton:false,timer:3600,timerProgressBar:true,showClass:{popup:'swal2-show'},hideClass:{popup:'swal2-hide'}});return}
 const holder=document.getElementById('toast-stack')||document.body;const e=document.createElement('div');e.className='toast'+(kind==='error'?' error':'');e.textContent=message;holder.append(e);setTimeout(()=>e.remove(),3600);
};
window.ACTToast=showToast;
window.ACTConfirm=async (title,message)=>{
 if(!window.Swal)return window.confirm(message||title);
 const result=await window.Swal.fire({
  title,text:message||'',icon:'question',showCancelButton:true,
  confirmButtonText:'Continue',cancelButtonText:'Cancel',
  confirmButtonColor:'#17563e',cancelButtonColor:'#65776b',reverseButtons:true,focusCancel:true,
  customClass:{popup:'act-confirm-dialog'}
 });
 return result.isConfirmed;
};

const ready=()=>{
 document.querySelectorAll('select:not([data-no-search])').forEach(select=>{
  if(select.tomselect||!window.TomSelect||select.multiple)return;
  const count=select.options.length;
  try{
   new window.TomSelect(select,{
    create:false,sortField:[{field:'$order',direction:'asc'}],
    maxOptions:Math.max(count,40),maxItems:1,searchField:['text'],
    openOnFocus:true,hidePlaceholder:true,
    plugins:[],render:{no_results:(data,escape)=>'<div class="no-results">No matching options</div>'}
   });
  }catch(e){console.error('Select enhancement failed',e)}
 });
 const logoutForm=document.querySelector('[data-logout-confirm]');
 if(logoutForm)logoutForm.addEventListener('submit',async e=>{
  if(logoutForm.dataset.confirmed==='1')return;
  e.preventDefault();
  if(!dialog){if(window.confirm('Sign out of Arewa Civic Tracker?')){logoutForm.dataset.confirmed='1';logoutForm.requestSubmit()}return}
  const choice=await dialog.fire({
   title:'Sign out?',text:'You will need to sign in again to access your workspace.',
   icon:'question',showCancelButton:true,confirmButtonText:'Yes, sign out',cancelButtonText:'Stay signed in',
   confirmButtonColor:'#17563e',cancelButtonColor:'#68796d',reverseButtons:true,focusCancel:true,
   customClass:{popup:'act-confirm-dialog'},showLoaderOnConfirm:false
  });
  if(choice.isConfirmed){logoutForm.dataset.confirmed='1';const btn=logoutForm.querySelector('button[type=submit]');if(btn){btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span class="sidebar-label">Signing out…</span>'}HTMLFormElement.prototype.submit.call(logoutForm)}
 });
 document.querySelectorAll('.notice[role="status"]').forEach(item=>{const msg=item.textContent.trim();if(msg){showToast(msg,'success');item.hidden=true}});
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready,{once:true});else ready();
})();
