(()=>{
'use strict';
const start=()=>{
 const form=document.querySelector('form[data-similar-url]');
 const panel=document.getElementById('act-similar-panel');
 const results=document.getElementById('act-similar-results');
 if(!form||!panel||!results)return;
 let timer,controller,sequence=0;
 const check=async()=>{
  controller?.abort();
  const title=form.elements.namedItem('title')?.value.trim()||'';
  const lga=form.elements.namedItem('lga_id')?.value;
  if(title.length<5||!lga){panel.hidden=true;results.replaceChildren();return}
  const index=++sequence;
  controller=new AbortController();
  const body=new FormData();
  for(const key of ['title','lga_id','community','sector_id','type','observation_date']){
   const value=form.elements.namedItem(key)?.value;
   if(value)body.append(key,value);
  }
  try{
   const response=await fetch(form.dataset.similarUrl,{
    method:'POST',credentials:'same-origin',
    headers:{'Accept':'application/json','X-CSRF-TOKEN':form.querySelector('input[name=_token]').value},
    body,signal:controller.signal
   });
   if(!response.ok)throw Error('Unable to check similar reports');
   const json=await response.json();
   if(index!==sequence)return;
   results.replaceChildren();
   panel.hidden=!json.matches?.length;
   for(const item of json.matches||[]){
    const entry=document.createElement('div');
    entry.className='act-similar-item';
    const titleNode=item.url?document.createElement('a'):document.createElement('strong');
    titleNode.textContent=item.title;
    if(item.url){titleNode.href=item.url;titleNode.target='_blank';titleNode.rel='noopener'}
    const detail=document.createElement('small');
    detail.textContent=[item.reference,item.community,item.observation_date].filter(Boolean).join(' · ');
    entry.append(titleNode,detail);results.append(entry);
   }
  }catch(e){if(e.name!=='AbortError')panel.hidden=true}
 };
 form.addEventListener('input',e=>{
  if(!['title','community'].includes(e.target.name))return;
  clearTimeout(timer);timer=setTimeout(check,650);
 });
 form.addEventListener('change',e=>{
  if(!['lga_id','sector_id','type','observation_date'].includes(e.target.name))return;
  clearTimeout(timer);timer=setTimeout(check,300);
 });
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
