(()=>{
'use strict';
const $=id=>document.getElementById(id);
const root=$('map'),message=$('geo-message');
if(!root||!message)return;
const inform=(text,error=false)=>{message.textContent=text;message.style.color=error?'#9a302c':'#336d4a'};
if(!window.L){inform('The map engine is unavailable. Reconnect and refresh.',true);return}
const map=L.map(root,{preferCanvas:true,scrollWheelZoom:false,zoomControl:false}).setView([12,11.45],7);
L.control.zoom({position:'bottomright'}).addTo(map);
L.control.scale({metric:true,imperial:false,position:'bottomright'}).addTo(map);
const bases={
  street:['https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png','© OpenStreetMap contributors'],
  light:['https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png','© OpenStreetMap contributors · © CARTO'],
  dark:['https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png','© OpenStreetMap contributors · © CARTO']
};
let tiles=null;
function useBase(name){if(tiles)map.removeLayer(tiles);const def=bases[name]||bases.street;tiles=L.tileLayer(def[0],{attribution:def[1],maxZoom:18}).addTo(map);tiles.bringToBack()}
useBase('street');
const colors={submitted:'#c19a51',under_review:'#3387a8',clarification_requested:'#ba5956',verified:'#36957e',approved:'#207954',rejected:'#b84e52'};
const filters={q:$('geo-search'),lga_id:$('geo-lga'),sector_id:$('geo-sector'),verification_status:$('geo-status'),type:$('geo-type'),progress_status:$('geo-progress'),date_from:$('geo-from'),date_to:$('geo-to')};
const cases=L.layerGroup().addTo(map),boundariesLayer=L.layerGroup().addTo(map),labelsLayer=L.layerGroup().addTo(map),heatLayer=L.layerGroup().addTo(map);
let polygonGroup=null,geometry=null,features=[],abort=null,sequence=0,markerMap=new Map(),measuring=false,measurePoints=[],measureLine=null,meMarker=null,searchTimer=null,shownClusters=[];
const label=s=>String(s||'').replaceAll('_',' ').replace(/\b\w/g,m=>m.toUpperCase());
const node=(tag,cls,text)=>{const e=document.createElement(tag);if(cls)e.className=cls;if(text!==undefined)e.textContent=text??'';return e};
const selectedLga=()=>filters.lga_id.value?filters.lga_id.selectedOptions[0]?.textContent?.trim()||'':null;
const currentParams=()=>{const params=new URLSearchParams();for(const [k,el]of Object.entries(filters))if(el.value.trim())params.set(k,el.value.trim());return params};
function readPermalink(){const params=new URLSearchParams(location.search);for(const [k,field]of Object.entries(filters)){const v=params.get(k);if(!v)continue;if(field.tagName==='SELECT'){if([...field.options].some(o=>o.value===v))field.value=v}else field.value=v}}
readPermalink();
const iconFor=color=>L.divIcon({className:'',html:'<div class="geo-map-dot" style="--status:'+color+'"></div>',iconSize:[20,20],iconAnchor:[10,10]});
function popup(feature){
  const props=feature.properties||{},wrap=node('div');
  wrap.append(node('div','geo-popup-title',props.title||'Field report'),node('div','geo-popup-meta',[props.reference,props.lga,props.sector,label(props.verification_status)].filter(Boolean).join(' · ')));
  const a=node('a','geo-popup-link','View report');a.href=props.url||'#';wrap.append(a);return wrap
}
function recordMarker(feature){
  const [lng,lat]=feature.geometry.coordinates,p=feature.properties||{};
  const marker=L.marker([lat,lng],{icon:iconFor(colors[p.verification_status]||'#a48b5d')});
  marker.bindPopup(popup(feature));
  marker.on('click',()=>highlightReport(p.id));
  return marker
}
function highlightReport(id){for(const r of $('geo-report-list').querySelectorAll('[data-case-id]'))r.classList.toggle('active',Number(r.dataset.caseId)===Number(id))}
function pointGroups(){
  const groupByZoom=$('geo-group-pins').checked&&map.getZoom()<=11;
  if(!groupByZoom)return features.map(f=>({items:[f]}));
  const size=map.getZoom()<=7?.37:map.getZoom()<=9?.13:.035;
  const buckets=new Map();
  for(const f of features){const [lng,lat]=f.geometry.coordinates;const k=Math.round(lng/size)+':'+Math.round(lat/size);if(!buckets.has(k))buckets.set(k,[]);buckets.get(k).push(f)}
  return [...buckets.values()].map(items=>({items}))
}
function renderCases(){
  cases.clearLayers();markerMap.clear();shownClusters=[];
  for(const group of pointGroups()){
    if(group.items.length===1){const f=group.items[0],m=recordMarker(f);m.addTo(cases);markerMap.set(Number(f.properties.id),m);continue}
    const lat=group.items.reduce((s,f)=>s+f.geometry.coordinates[1],0)/group.items.length;
    const lng=group.items.reduce((s,f)=>s+f.geometry.coordinates[0],0)/group.items.length;
    const count=group.items.length,html='<span class="geo-cluster">'+count+'</span>';
    const marker=L.marker([lat,lng],{icon:L.divIcon({className:'',html,iconSize:[37,37],iconAnchor:[18,18]})}).addTo(cases);
    marker.bindTooltip(count+' authorized report locations');
    marker.on('click',()=>map.setView([lat,lng],Math.min(17,map.getZoom()+2)));
    shownClusters.push(marker);
  }
  drawHeat();countVisible()
}
function drawHeat(){
  heatLayer.clearLayers();if(!$('geo-heat').checked||!features.length)return;
  const points=features.map(f=>[f.geometry.coordinates[1],f.geometry.coordinates[0],0.75]);
  if(typeof L.heatLayer==='function'){
    L.heatLayer(points,{radius:27,blur:19,maxZoom:16,minOpacity:0.22,gradient:{0.3:'#76c09a',0.6:'#f0b958',1:'#b54a42'}}).addTo(heatLayer);
  }else{
    for(const [lat,lng] of points){L.circle([lat,lng],{radius:map.getZoom()<=8?9000:2000,stroke:false,fillColor:'#3d9362',fillOpacity:.13,interactive:false}).addTo(heatLayer)}
  }
}
function fitFeature(id){
  const f=features.find(f=>Number(f.properties.id)===Number(id));if(!f)return;
  const [lng,lat]=f.geometry.coordinates;map.flyTo([lat,lng],Math.max(map.getZoom(),13),{duration:.4});
  setTimeout(()=>{const m=markerMap.get(Number(id));if(m)m.openPopup()},500);highlightReport(id)
}
function renderList(){
  const list=$('geo-report-list');list.replaceChildren();
  $('geo-count').textContent=features.length;$('geo-matched').textContent=features.length;
  $('geo-approved').textContent=features.filter(f=>f.properties?.verification_status==='approved').length;
  if(!features.length){list.append(node('p','geo-empty','No geotagged reports match these filters. You can still explore the LGA boundaries.'));return}
  for(const f of features){const p=f.properties||{},button=node('button','geo-case');button.type='button';button.dataset.caseId=p.id;button.setAttribute('role','listitem');
    button.append(node('i',p.type==='public_project'?'fa-solid fa-building':'fa-regular fa-file-lines'));
    const text=node('span','geo-case-text');text.append(node('strong','',p.title),node('small','',[p.reference,p.lga,p.community].filter(Boolean).join(' · ')),node('small','',label(p.verification_status)+' / '+label(p.progress_status)));
    const dot=node('span','geo-case-dot');dot.style.setProperty('--status',colors[p.verification_status]||'#b49a66');
    button.append(text,dot);button.addEventListener('click',()=>fitFeature(p.id));list.append(button)
  }
}
function boundaryColor(f){return String(f.properties?.name||'').toLowerCase()===String(selectedLga()||'').toLowerCase()&&!!selectedLga()?'#c8a366':'#4b9769'}
function boundaryStyle(f){const selected=boundaryColor(f)==='#c8a366';return{color:selected?'#1d6744':'#26704c',weight:selected?2.8:1.4,fillColor:boundaryColor(f),fillOpacity:selected?.28:.12}}
function updateBoundaryStyles(){if(polygonGroup)polygonGroup.eachLayer(layer=>layer.setStyle(boundaryStyle(layer.feature)));drawLabels()}
function drawLabels(){labelsLayer.clearLayers();if(!$('geo-show-labels').checked||map.getZoom()<8||!polygonGroup)return;polygonGroup.eachLayer(layer=>{
 const center=layer.getBounds().getCenter();const name=layer.feature.properties.name;
 L.marker(center,{interactive:false,icon:L.divIcon({className:'',html:'<span class="geo-lga-label">'+String(name).replace(/[&<>"']/g,'')+'</span>',iconSize:[100,20],iconAnchor:[50,10]})}).addTo(labelsLayer)
})}
function focusLga(name){if(!polygonGroup)return false;let found=false;polygonGroup.eachLayer(layer=>{if(layer.feature.properties.name.toLowerCase()===String(name).toLowerCase()){map.fitBounds(layer.getBounds(),{padding:[35,35],maxZoom:11});found=true}});return found}
function lgaList(){const list=$('geo-lga-list');list.replaceChildren();if(!geometry)return;const counts=new Map();for(const f of features){const name=String(f.properties?.lga||'').toLowerCase();counts.set(name,(counts.get(name)||0)+1)}
  const names=geometry.features.map(f=>f.properties.name).sort((a,b)=>a.localeCompare(b));for(const name of names){
    const button=node('button','geo-lga-choice');button.type='button';button.append(node('span','',name),node('small','',(counts.get(name.toLowerCase())||0)+' reports'));button.classList.toggle('active',selectedLga()?.toLowerCase()===name.toLowerCase());
    button.addEventListener('click',()=>{const option=[...filters.lga_id.options].find(o=>o.textContent.toLowerCase()===name.toLowerCase());if(option){filters.lga_id.value=option.value;loadReports(true)}});
    list.append(button)
  }
}
function countVisible(){const bounds=map.getBounds();$('geo-visible').textContent=features.filter(f=>{const [lng,lat]=f.geometry.coordinates;return bounds.contains([lat,lng])}).length}
async function loadReports(reframe=true){
  if(abort)abort.abort();abort=new AbortController();const request=++sequence;
  const params=currentParams(),url=new URL(root.dataset.feed,location.href);
  for(const [key,val]of params)url.searchParams.set(key,val);
  const browserUrl=new URL(location.href);for(const key of Object.keys(filters))browserUrl.searchParams.delete(key);for(const [key,val]of params)browserUrl.searchParams.set(key,val);history.replaceState(null,'',browserUrl);
  const btn=$('geo-apply');btn.disabled=true;inform('Loading authorized report locations…');
  try{
    const response=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'},signal:abort.signal,cache:'no-store'});
    if(!response.ok){const b=await response.json().catch(()=>({}));throw Error(b.message||'GIS data unavailable ('+response.status+').')}
    const data=await response.json();if(request!==sequence)return;
    if(!Array.isArray(data.features))throw Error('Unexpected GIS response.');
    features=data.features.filter(f=>f?.geometry?.type==='Point'&&f.geometry.coordinates?.length===2&&f.geometry.coordinates.every(Number.isFinite));
    renderCases();renderList();lgaList();updateBoundaryStyles();
    if(reframe){const lga=selectedLga();if(lga&&focusLga(lga)){}else if(features.length)map.fitBounds(L.latLngBounds(features.map(f=>[f.geometry.coordinates[1],f.geometry.coordinates[0]])),{padding:[36,36],maxZoom:11});else if(polygonGroup)map.fitBounds(polygonGroup.getBounds(),{padding:[25,25],maxZoom:9})}
    inform(features.length+' authorized location'+(features.length===1?'':'s')+(data.meta?.truncated?' (first 1,000; narrow filters)':'')+' available.');
  }catch(e){if(e.name!=='AbortError')inform(e.message||'GIS loading error.',true)}
  finally{if(request===sequence)btn.disabled=false}
}
async function loadGeometry(){
 try{
  const res=await fetch(root.dataset.boundaries,{cache:'force-cache'});if(!res.ok)throw Error('LGA boundaries unavailable.');
  const data=await res.json();if(data.type!=='FeatureCollection'||!Array.isArray(data.features))throw Error('Invalid boundary data.');
  geometry=data;polygonGroup=L.geoJSON(data,{style:boundaryStyle,onEachFeature:(f,layer)=>{
    const name=f.properties.name,box=node('div');box.append(node('div','geo-popup-title',name+' LGA'));
    const button=node('button','geo-btn','Explore LGA');button.type='button';button.addEventListener('click',()=>{const option=[...filters.lga_id.options].find(o=>o.textContent.toLowerCase()===String(name).toLowerCase());if(option){filters.lga_id.value=option.value;loadReports(true)}});box.append(button);layer.bindPopup(box);
    layer.on('mouseover',()=>layer.setStyle({weight:2.5,fillOpacity:.27}));layer.on('mouseout',updateBoundaryStyles)
  }}).addTo(boundariesLayer);
  map.fitBounds(polygonGroup.getBounds(),{padding:[20,20],maxZoom:9});lgaList();drawLabels();
 }catch(e){inform(e.message,true)}
}
$('geo-filters').addEventListener('submit',e=>{e.preventDefault();loadReports(true)});
$('geo-clear').addEventListener('click',()=>{for(const field of Object.values(filters))field.value='';loadReports(true)});
for(const [name,field]of Object.entries(filters)){if(field.tagName==='SELECT')field.addEventListener('change',()=>loadReports(true));else if(name==='q')field.addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>loadReports(false),450)})}
for(const button of document.querySelectorAll('[data-geo-tab]'))button.addEventListener('click',()=>{for(const other of document.querySelectorAll('[data-geo-tab]')){other.classList.toggle('active',other===button);other.setAttribute('aria-selected',String(other===button))}for(const panel of document.querySelectorAll('[data-geo-panel]'))panel.hidden=panel.dataset.geoPanel!==button.dataset.geoTab});
$('geo-show-boundaries').addEventListener('change',e=>{if(e.target.checked)boundariesLayer.addTo(map);else map.removeLayer(boundariesLayer)});
$('geo-show-labels').addEventListener('change',e=>{if(e.target.checked){labelsLayer.addTo(map);drawLabels()}else map.removeLayer(labelsLayer)});
$('geo-group-pins').addEventListener('change',renderCases);
$('geo-heat').addEventListener('change',drawHeat);
$('geo-base').addEventListener('change',e=>useBase(e.target.value));
$('geo-home').addEventListener('click',()=>{if(polygonGroup)map.fitBounds(polygonGroup.getBounds(),{padding:[20,20],maxZoom:9})});
$('geo-locate').addEventListener('click',()=>{if(!navigator.geolocation)return inform('Geolocation is not supported by this browser.',true);inform('Requesting device location…');navigator.geolocation.getCurrentPosition(pos=>{const latlng=[pos.coords.latitude,pos.coords.longitude];if(meMarker)map.removeLayer(meMarker);meMarker=L.circleMarker(latlng,{radius:8,color:'#fff',weight:3,fillColor:'#1b754d',fillOpacity:.95}).addTo(map);meMarker.bindPopup('Your device location. Not stored by Arewa Civic Tracker.').openPopup();map.setView(latlng,12);inform('Device location shown locally. It was not stored.')},()=>inform('Location permission was denied or unavailable.',true),{timeout:10000,maximumAge:60000})});
$('geo-measure').addEventListener('click',()=>{measuring=!measuring;measurePoints=[];if(measureLine)map.removeLayer(measureLine);measureLine=null;$('geo-measure').setAttribute('aria-pressed',String(measuring));inform(measuring?'Click two map positions to measure straight-line distance.':'Measurement cleared.')});
map.on('click',e=>{if(!measuring)return;measurePoints.push(e.latlng);if(measurePoints.length===2){measureLine=L.polyline(measurePoints,{color:'#ad8c4c',weight:3,dashArray:'6,6'}).addTo(map);const meters=measurePoints[0].distanceTo(measurePoints[1]);const result=meters>=1000?(meters/1000).toFixed(2)+' km':Math.round(meters)+' m';measureLine.bindPopup('Straight-line distance: '+result).openPopup();inform('Straight-line distance: '+result);measuring=false;$('geo-measure').setAttribute('aria-pressed','false')}});
$('geo-fullscreen').addEventListener('click',async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else await $('geo-map-holder').requestFullscreen();setTimeout(()=>map.invalidateSize(),200)}catch{inform('Fullscreen not available.',true)}});
document.addEventListener('fullscreenchange',()=>setTimeout(()=>map.invalidateSize(),200));
$('geo-share').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(location.href);inform('Filtered link copied. Viewing still requires authorization.')}catch{inform('Copy the address from your browser.',true)}});
function download(filename,data,type){const blob=new Blob([data],{type}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=filename;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),3000)}
function cell(s){let v=String(s??'');if(/^[\s]*[=+\-@]/.test(v))v="'"+v;return '"'+v.replaceAll('"','""')+'"'}
$('geo-csv').addEventListener('click',()=>{if(!features.length)return inform('No locations to export.');const keys=['reference','title','type','lga','sector','community','verification_status','progress_status','observation_date'];const rows=[keys.concat('latitude','longitude').join(',')];for(const f of features){rows.push(keys.map(k=>cell(f.properties?.[k])).concat(cell(f.geometry.coordinates[1]),cell(f.geometry.coordinates[0])).join(','))}download('arewa-authorized-map.csv','\ufeff'+rows.join('\r\n'),'text/csv;charset=utf-8');inform('Authorized CSV exported. Handle private coordinates carefully.')});
$('geo-geojson').addEventListener('click',()=>{if(!features.length)return inform('No locations to export.');download('arewa-authorized-map.geojson',JSON.stringify({type:'FeatureCollection',features},null,2),'application/geo+json');inform('Role-restricted GeoJSON exported. Do not publish without approval.')});
map.on('moveend',countVisible);
map.on('zoomend',()=>{renderCases();drawLabels()});
map.on('mousemove',e=>{$('geo-coords').textContent=e.latlng.lat.toFixed(4)+', '+e.latlng.lng.toFixed(4)});
Promise.all([loadGeometry(),loadReports(false)]).catch(e=>inform('Map initialization error: '+e.message,true));
})();
