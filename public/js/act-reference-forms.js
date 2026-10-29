(()=>{
 const start=()=>{
  const type=document.querySelector('select[name=type]');
  const cat=document.querySelector('select[name=report_category_id]');
  const lga=document.querySelector('select[name=lga_id]');
  const community=document.querySelector('input[name=community]');
  const all=document.querySelector('datalist#community-suggestions');
  const suggestions=all?[...all.options].map(o=>({value:o.value,lga:o.dataset.lga})):[];
  function update(){
   if(cat && type){
    let valid=false;
    for(const option of cat.options){
     if(!option.value)continue;
     option.hidden=option.dataset.type!==type.value;
     option.disabled=option.hidden;
     if(option.selected&&!option.hidden)valid=true;
    }
    if(cat.value&&!valid)cat.value='';
   }
   if(lga&&all&&community){
    all.replaceChildren();
    for(const item of suggestions.filter(o=>o.lga===lga.value)){
      const option=document.createElement('option');option.value=item.value;all.append(option);
    }
   }
  }
  type?.addEventListener('change',update);
  lga?.addEventListener('change',update);
  update();
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
