(() => {
  'use strict';
  const form=document.querySelector('.report-form[data-no-ajax],.report-form[data-user-id]');
  if(!form)return;
  const lga=form.querySelector('select[name="lga_id"]');
  const box=form.querySelector('[data-ward-picker]');
  if(!lga||!box)return;
  const select=box.querySelector('[data-ward-select]');
  const manual=box.querySelector('[data-ward-manual]');
  const search=box.querySelector('[data-ward-search]');
  const toggle=box.querySelector('[data-ward-switch]');
  const options=[...select.options].filter(option=>option.dataset.lga);
  let manualMode=false;
  const norm=s=>String(s||'').trim().toLocaleLowerCase('en');
  function switchMode(enabled,value=''){
    manualMode=enabled;
    select.disabled=enabled;select.hidden=enabled;
    search.hidden=enabled;search.disabled=enabled;
    manual.disabled=!enabled;manual.hidden=!enabled;
    select.required=!enabled;manual.required=enabled;
    if(enabled)manual.value=value;
    toggle.setAttribute('aria-pressed',String(enabled));
    toggle.textContent=enabled?'Choose from listed wards':'Ward not listed? Enter manually';
  }
  function populate(reset=false){
    if(reset)search.value='';
    const selectedLga=lga.value;
    const q=norm(search.value);
    const current=reset?'':select.value;
    for(const opt of options){
      const allowed=opt.dataset.lga===selectedLga;
      const match=!q||norm(opt.textContent).includes(q);
      opt.hidden=!allowed||!match;
      opt.disabled=!allowed||!match;
    }
    select.options[0].textContent=selectedLga?'Select ward':'Select LGA first';
    const matched=options.find(o=>o.value===current && !o.disabled);
    select.value=matched?matched.value:'';
    if(reset){manual.value='';search.value='';switchMode(false);}
  }
  function restore(value){
    const initial=String(value??'').trim();
    search.value='';
    populate(true);
    if(!initial)return;
    const found=options.find(opt=>opt.dataset.lga===lga.value&&norm(opt.value)===norm(initial));
    if(found){select.value=found.value;switchMode(false);}
    else switchMode(true,initial);
  }
  lga.addEventListener('change',()=>populate(true));
  search.addEventListener('input',()=>populate());
  toggle.addEventListener('click',()=>{
    if(manualMode){switchMode(false);populate();select.focus();}
    else {switchMode(true,select.value);manual.focus();}
  });
  window.addEventListener('act:ward-restore',event=>restore(event.detail?.ward));
  restore(box.dataset.initialWard);
})();
