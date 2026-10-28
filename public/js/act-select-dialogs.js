(()=>{
'use strict';
const dialog=window.Swal;
window.ACTSpinnerMarkup="<span class=\"act-dot-spinner\" aria-hidden=\"true\"><span style=\"--i:0\"></span><span style=\"--i:1\"></span><span style=\"--i:2\"></span><span style=\"--i:3\"></span><span style=\"--i:4\"></span><span style=\"--i:5\"></span><span style=\"--i:6\"></span><span style=\"--i:7\"></span></span>";
const showToast=(message,kind='success')=>{
 const type=['success','error','info','warning'].includes(kind)?kind:'info';
 if(window.Swal){
  const compact=window.Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2800,timerProgressBar:true,width:310,padding:'0.5rem 0.75rem',iconColor:type==='success'?'#216943':undefined,customClass:{popup:'act-compact-toast'},didOpen:popup=>{popup.addEventListener('mouseenter',window.Swal.stopTimer);popup.addEventListener('mouseleave',window.Swal.resumeTimer)}});
  compact.fire({icon:type,title:String(message)});return;
 }
 const holder=document.getElementById('toast-stack')||document.body;
 const e=document.createElement('div');e.className='toast act-fallback-toast'+(type==='error'?' error':'');e.textContent=String(message);holder.append(e);setTimeout(()=>e.remove(),2800);
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
  if(choice.isConfirmed){logoutForm.dataset.confirmed='1';const btn=logoutForm.querySelector('button[type=submit]');if(btn){btn.disabled=true;btn.innerHTML=''+(window.ACTSpinnerMarkup||'')+'<span class="sidebar-label">Signing out…</span>'}HTMLFormElement.prototype.submit.call(logoutForm)}
 });
 document.querySelectorAll('.notice[role="status"]').forEach(item=>{const msg=item.textContent.trim();if(msg){showToast(msg,'success');item.hidden=true}});
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready,{once:true});else ready();
})();
