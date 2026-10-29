(() => {
 'use strict';
 const root=document.getElementById('public-map');
 if(!root) return;
 if(!window.L){root.textContent='Map engine unavailable. You can still browse the reports below.';return;}
 root.textContent='';
 const map=L.map(root,{scrollWheelZoom:false}).setView([12.0,11.45],7);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
  maxZoom:17,attribution:'© OpenStreetMap contributors'
 }).addTo(map);
 const note=document.createElement('div');
 note.className='public-map-note';
 note.style.cssText='position:absolute;bottom:8px;left:8px;z-index:440;background:white;padding:6px 9px;font-size:11px;color:#16523a;border:1px solid #dce5df';
 note.textContent='Approximate locations · approved public records only';
 root.append(note);
 const form=document.getElementById('public-filters');
 const params=new URLSearchParams(new FormData(form));
 params.delete('page');
 const endpoint=root.dataset.feed+'?'+params.toString();
 function popupText(feature){
  const props=feature.properties||{};
  const box=document.createElement('div');
  const title=document.createElement('strong');title.textContent=props.title||'Published report';
  box.append(title);
  const small=document.createElement('p');
  small.textContent=[props.reference,props.community,props.lga,props.sector,props.progress].filter(Boolean).join(' · ');
  box.append(small);
  return box;
 }
 Promise.all([
  fetch(root.dataset.boundaries,{credentials:'omit'}).then(r=>{if(!r.ok)throw Error('Boundaries unavailable');return r.json()}),
  fetch(endpoint,{credentials:'omit',headers:{'Accept':'application/json'}}).then(r=>{if(!r.ok)throw Error('Map data unavailable');return r.json()})
 ]).then(([geo,data])=>{
  const selected=form.elements.namedItem('lga_id');
  const name=selected?.value ? selected.selectedOptions[0]?.textContent?.toLowerCase() : null;
  const boundaryLayer=L.geoJSON(geo,{style:f=>{
   const selectedArea=(f.properties?.name||'').toLowerCase()===name;
   return {color:selectedArea?'#a8863f':'#307b53',weight:selectedArea?2.4:1.25,fillColor:selectedArea?'#edca77':'#96c6a0',fillOpacity:selectedArea?.32:.14};
  },onEachFeature:(f,layer)=>{layer.bindTooltip(String(f.properties?.name||'Yobe LGA'))}}).addTo(map);
  if(boundaryLayer.getBounds().isValid())map.fitBounds(boundaryLayer.getBounds(),{padding:[16,16],maxZoom:9});
  (data.features||[]).forEach(f=>{
   const coords=f.geometry?.coordinates;
   if(!Array.isArray(coords)||coords.length!==2||!coords.every(Number.isFinite))return;
   if(Math.abs(coords[1])>90||Math.abs(coords[0])>180)return;
   L.circleMarker([coords[1],coords[0]],{radius:7,color:'#fff',weight:2,fillColor:'#186746',fillOpacity:.92}).addTo(map).bindPopup(popupText(f));
  });
 }).catch(()=>{note.textContent='Map records are temporarily unavailable; browse reports below.'});
})();
