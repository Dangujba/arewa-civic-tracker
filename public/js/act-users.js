(()=>{
'use strict';
const ready=()=>{
 const initTags=(select)=>{
  if(!select||select.tomselect||!window.TomSelect)return;
  let controller;
  try { controller=new window.TomSelect(select,{
   plugins:{remove_button:{title:'Remove this LGA',label:'×',tabindex:0}},
   create:false,maxItems:null,closeAfterSelect:false,hideSelected:true,
   persist:false,maxOptions:50,searchField:['text'],
   placeholder:'Search and select LGAs',
   render:{no_results:()=>'<div class="no-results">No matching LGAs</div>'}
  }); }catch(error) {
   console.error('LGA selector unavailable; using native multi-select:',error);
   return;
  }
  // Make each tag's remove control usable by keyboard as well as pointer.
  controller.control.addEventListener('keydown',event=>{
   if((event.key==='Enter'||event.key===' ')&&event.target.closest('.remove')){
    event.preventDefault();
    event.target.click();
   }
  });
 };
 const toggleRole=(form,clear=true)=>{
  const role=form.querySelector('[data-user-role]');
  const field=form.querySelector('[data-lga-field]');
  if(!role||!field)return;
  const select=field.querySelector('select');
  const enabled=role.value==='focal';
  field.hidden=!enabled;
  if(!enabled&&clear){
   if(select.tomselect)select.tomselect.clear(true);
   else Array.from(select.options).forEach(o=>o.selected=false);
  }
  if(select.tomselect){
   if(enabled) select.tomselect.enable();
   else select.tomselect.disable();
  }else select.disabled=!enabled;
 };
 document.querySelectorAll('[data-user-lga-tags]').forEach(initTags);
 document.querySelectorAll('[data-user-role]').forEach(role=>{
  const form=role.closest('form');
  if(!form)return;
  role.addEventListener('change',()=>toggleRole(form));
  toggleRole(form,false);
 });
 const dialog=document.getElementById('act-user-edit-dialog');
 const editForm=document.getElementById('act-user-edit-form');
 if(dialog&&editForm){
  document.querySelectorAll('[data-user-edit]').forEach(button=>button.addEventListener('click',()=>{
   editForm.action=button.dataset.action;
   editForm.querySelector('[name=name]').value=button.dataset.name||'';
   editForm.querySelector('[name=email]').value=button.dataset.email||'';
   editForm.querySelector('[name=role]').value=button.dataset.role||'focal';
   editForm.querySelector('[name=is_active]').value=button.dataset.active||'1';
   const select=editForm.querySelector('[data-user-lga-tags]');
   const ids=(button.dataset.lgas||'').split(',').filter(Boolean);
   if(select.tomselect)select.tomselect.setValue(ids,true);
   else Array.from(select.options).forEach(o=>o.selected=ids.includes(o.value));
   toggleRole(editForm,false);
   dialog.showModal();
   if(select.tomselect)select.tomselect.refreshOptions(false);
  }));
  dialog.querySelectorAll('[data-user-close]').forEach(btn=>btn.addEventListener('click',()=>dialog.close()));
  dialog.addEventListener('click',event=>{
   if(event.target===dialog)dialog.close();
  });
 }
 document.querySelectorAll('form[data-user-delete]').forEach(form=>form.addEventListener('submit',async(event)=>{
  if(form.dataset.confirmed==='1')return;
  event.preventDefault();
  const name=form.dataset.name||'this staff member';
  let confirmed=false;
  if(window.Swal){
   const answer=await window.Swal.fire({
    title:'Delete staff account?',
    text:'Delete '+name+'? Accounts with field reports or audit history cannot be deleted. Deactivate them instead.',
    icon:'warning',showCancelButton:true,
    confirmButtonText:'Delete account',cancelButtonText:'Cancel',
    confirmButtonColor:'#a43832',cancelButtonColor:'#5e7566',
    reverseButtons:true,focusCancel:true,
    customClass:{popup:'act-confirm-dialog'}
   });
   confirmed=answer.isConfirmed;
  }else confirmed=window.confirm('Delete '+name+'? This cannot be undone.');
  if(confirmed){
   form.dataset.confirmed='1';
   HTMLFormElement.prototype.submit.call(form);
  }
 }));
};
if(document.readyState==='complete')ready();else document.addEventListener('DOMContentLoaded',ready,{once:true});
})();
