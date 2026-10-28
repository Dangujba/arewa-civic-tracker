(()=>{
'use strict';
const $=id=>document.getElementById(id);
const root=$('map'), message=$('gis-message');
if(!root||!message)return;
const status=(text,error=false)=>{message.textContent=text;message.style.color=error?'#a23630':'#316849'};
if(!window.L){status('Map engine unavailable. Refresh to retry.',true);return}
const map=L.map(root,{zoomControl:false,scrollWheelZoom:false,preferCanvas:true}).setView([12.0,11.45],7);
L.control.zoom({position:'bottomright'}).addTo(map);
L.control.scale({metric:true,imperial:false,position:'bottomright'}).addTo(map);
const tileDef={
 street:{url:'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',attrs:{attribution:'© OpenStreetMap contributors',maxZoom:18}},
 light:{url:'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',attrs:{attribution:'© OpenStreetMap contributors, © CARTO',maxZoom:18}},
 dark:{url:'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',attrs:{attribution:'© OpenStreetMap contributors, © CARTO',maxZoom:18}}
};
let tile=null;
const changeBasemap=name=>{if(tile)map.removeLayer(tile);const def=tileDef[name]||tileDef.street;tile=L.tileLayer(def.url,def.attrs).addTo(map);tile.bringToBack()};
changeBasemap('street');
const groups={clusters:L.markerClusterGroup({showCoverageOnHover:false,maxClusterRadius:45,spiderfyOnMaxZoom:true}),direct:L.layerGroup(),boundaries:L.layerGroup().addTo(map),heat:null,labels:L.layerGroup().addTo(map)};
const colors={submitted:'#bf9b51',under_review:'#3083ae',clarification_requested:'#b85a55',verified:'#39967b',approved:'#1f8256',rejected:'#b34b50'};
const selects={lga:$('gis-lga'),sector:$('gis-sector'),status:$('gis-status'),type:$('gis-type'),progress:$('gis-progress'),dateFrom:$('gis-date-from'),dateTo:$('gis-date-to'),search:$('gis-search')};
const filterKeys={lga:'lga_id',sector:'sector_id',status:'verification_status',type:'type',progress:'progress_status',dateFrom:'date_from',dateTo:'date_to',search:'q'};
let features=[], boundaries=null, boundaryByName=new Map(), markers=new Map(), loaded=false;
let activeRequest=null, measureActive=false, measurePoints=[], measureLine=null, locationMarker=null, highlighted=null;
const statusLabel=s=>String(s||'').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase());
const selectedLgaName=()=>selects.lga.value?selects.lga.selectedOptions[0]?.textContent.trim():null;
const getFilters=()=>{const values={};for(const [k,param] of Object.entries(filterKeys)){const v=selects[k].value.trim();if(v)values[param]=v}return values};
const readUrl=()=>{const p=new URLSearchParams(location.search);for(const [k,param] of Object.entries(filterKeys)){const v=p.get(param);if(v&&[...selects[k].options||[]].some(o=>o.value===v)){selects[k].value=v}else if(v&&selects[k].tagName==='INPUT'){selects[k].value=v}}};
readUrl();
function syncUrl(){const u=new URL(location.href);for(const v of Object.values(filterKeys))u.searchParams.delete(v);for(const [k,v] of Object.entries(getFilters()))u.searchParams.set(k,v);history.replaceState({},'',u)}
function safeText(el,content){el.textContent=content??'';return el}
const el=(tag,cls,text)=>{const node=document.createElement(tag);if(cls)node.className=cls;if(text!==undefined)safeText(node,text);return node};
const dotColor=state=>colors[state]||'#af956c';
const addPinFeature=feature=>{
  const [lng,lat]=feature.geometry.coordinates;
  if(!Number.isFinite(lat)||!Number.isFinite(lng)||Math.abs(lat)>90||Math.abs(lng)>180)return;
  const props=feature.properties||{}, id=Number(props.id),color=dotColor(props.verification_status);
  const marker=L.marker([lat,lng],{icon:L.divIcon({className:'',html:'<span class="gis-map-pin" style="--marker-color:'+color+'"></span>',iconSize:[21,21],iconAnchor:[10,10]})});
  const wrap=el('div'),heading=el('div','gis-popup-title',props.title||'Field report'),metadata=el('div','gis-popup-meta',[props.reference,props.lga,props.sector, statusLabel(props.verification_status)].filter(Boolean).join(' • '));
  const link=el('a','gis-popup-link','Open record');
  link.href=props.url||'#';link.rel='noopener';wrap.append(heading,metadata,link);marker.bindPopup(wrap);
  marker.on('click',()=>selectReportInList(id));
  markers.set(id,marker);groups.clusters.addLayer(marker);groups.direct.addLayer(marker);
};
function addBoundaryLayer(data){
  if(!data||data.type!=='FeatureCollection'||!Array.isArray(data.features))throw Error('Boundary data is not valid GeoJSON.');
  boundaries=L.geoJSON(data,{style:f=>boundaryStyle(f),onEachFeature:(feature,layer)=>{
    const name=String(feature.properties?.name||'Unknown');boundaryByName.set(name.toLowerCase(),layer);
    const container=el('div'),title=el('div','gis-popup-title',name+' LGA');
    const button=el('button','gis-action','Explore this LGA');button.type='button';
    button.addEventListener('click',()=>{const option=[...selects.lga.options].find(o=>o.textContent.toLowerCase()===name.toLowerCase());if(option){selects.lga.value=option.value;applyFilters()}});
    container.append(title,button);layer.bindPopup(container);layer.bindTooltip(name,{sticky:true,opacity:.92});
    layer.on('mouseover',()=>{layer.setStyle({weight:2.7,fillOpacity:.28})});
    layer.on('mouseout',()=>styleBoundaries());
  }});
  groups.boundaries.addLayer(boundaries);
  const bounds=boundaries.getBounds();if(bounds.isValid())map.fitBounds(bounds,{padding:[20,20],maxZoom:9});
  renderLgaList();styleBoundaries();
}
function countsByLga(){const counts=new Map();for(const f of features){const key=String(f.properties.lga||'').toLowerCase();counts.set(key,(counts.get(key)||0)+1)}return counts}
function boundaryStyle(feature){
 const name=String(feature.properties?.name||'').toLowerCase();const chosen=selectedLgaName()?.toLowerCase();const count=countsByLga().get(name)||0;
 return {color:name===chosen?'#185c3d':'#26714e',weight:name===chosen?2.7:1.35,fillColor:name===chosen?'#d6ae69':'#479c6d',fillOpacity:name===chosen?.toLowerCase()?0.34:Math.min(.29,.09+count*.03),opacity:1};
}
function styleBoundaries(){if(!boundaries)return;boundaries.eachLayer(layer=>layer.setStyle(boundaryStyle(layer.feature)));renderLgaLabels()}
function renderLgaLabels(){groups.labels.clearLayers();if(!$('gis-labels').checked||map.getZoom()<8||!boundaries)return;boundaries.eachLayer(layer=>{const name=String(layer.feature.properties.name);const center=layer.getBounds().getCenter();const icon=L.divIcon({className:'',html:'<span class="gis-lga-label">'+name.replace(/[&<>"']/g,'')+'</span>',iconSize:[90,22],iconAnchor:[45,11]});const marker=L.marker(center,{icon,interactive:false});marker.addTo(groups.labels);const span=marker.getElement()?.querySelector('span');if(span)span.textContent=name})}
function fitLga(name){const shape=boundaryByName.get(String(name).toLowerCase());if(shape){map.fitBounds(shape.getBounds(),{padding:[32,32],maxZoom:11});return true}return false}
function renderLgaList(){
 const wrapper=$('gis-lga-results');wrapper.replaceChildren();const counts=countsByLga();const names=[...boundaryByName.keys()].sort((a,b)=>a.localeCompare(b));
 if(!names.length)return wrapper.append(el('p','gis-empty','No geographic boundaries are currently available.'));
 for(const name of names){const shape=boundaryByName.get(name);const display=String(shape.feature.properties.name);
  const btn=el('button','gis-lga-button');btn.type='button';btn.classList.toggle('selected',selectedLgaName()?.toLowerCase()===name);
  btn.append(el('span','',display),el('span','gis-lga-count',String(counts.get(name)||0)+' records'));
  btn.addEventListener('click',()=>{const option=[...selects.lga.options].find(o=>o.textContent.trim().toLowerCase()===name);if(option){selects.lga.value=option.value;applyFilters()}});
  wrapper.append(btn);
 }
}
function clearReportLayers(){groups.clusters.clearLayers();groups.direct.clearLayers();markers.clear();if(groups.heat){map.removeLayer(groups.heat);groups.heat=null}}
function attachReportLayer(){if(map.hasLayer(groups.clusters))map.removeLayer(groups.clusters);if(map.hasLayer(groups.direct))map.removeLayer(groups.direct);map.addLayer($('gis-clustering').checked?groups.clusters:groups.direct)}
function refreshHeat(){if(groups.heat){map.removeLayer(groups.heat);groups.heat=null}if($('gis-heat').checked&&features.length&&L.heatLayer){const points=features.map(f=>{const [lng,lat]=f.geometry.coordinates;return [lat,lng,.7]});groups.heat=L.heatLayer(points,{radius:26,blur:19,maxZoom:16,minOpacity:.22}).addTo(map)}}
function focusReport(id){
 const marker=markers.get(id);if(!marker)return;
 const action=()=>{map.flyTo(marker.getLatLng(),Math.max(map.getZoom(),12),{duration:.55});setTimeout(()=>marker.openPopup(),450)};
 if($('gis-clustering').checked&&groups.clusters.zoomToShowLayer)groups.clusters.zoomToShowLayer(marker,action);else action();
 selectReportInList(id);
}
function selectReportInList(id){for(const row of $('gis-report-results').querySelectorAll('[data-report-id]'))row.classList.toggle('active',Number(row.dataset.reportId)===id)}
function renderReports(){
 const target=$('gis-report-results');target.replaceChildren();
 $('gis-results-count').textContent=features.length+' records';$('gis-total').textContent=String(features.length);
 $('gis-approved').textContent=String(features.filter(f=>f.properties.verification_status==='approved').length);
 if(!features.length){const empty=el('div','gis-empty');empty.append(el('i','fa-regular fa-map',''),el('p','','No matching geotagged reports. Try another filter or explore the LGA boundaries.'));target.append(empty);return}
 for(const f of features){const p=f.properties;const row=el('button','gis-result');row.type='button';row.dataset.reportId=String(p.id);
  const icon=el('span','gis-result-icon');icon.append(el('i',p.type==='public_project'?'fa-solid fa-building':'fa-solid fa-file-circle-exclamation'));
  const body=el('span','gis-result-body');body.append(el('strong','',p.title),el('small','',[p.reference,p.lga,p.community].filter(Boolean).join(' · ')),el('small','',statusLabel(p.verification_status)+' / '+statusLabel(p.progress_status)));
  const dot=el('span','gis-status-indicator');dot.style.setProperty('--marker-color',dotColor(p.verification_status));row.append(icon,body,dot);row.addEventListener('click',()=>focusReport(p.id));target.append(row);
 }
}
function countVisible(){const bounds=map.getBounds();$('gis-visible').textContent=String(features.filter(f=>{const [lng,lat]=f.geometry.coordinates;return bounds.contains([lat,lng])}).length)}
let requestSeq=0;
async function loadReports({zoom=true}={}){
 if(activeRequest)activeRequest.abort();activeRequest=new AbortController();
 const current=++requestSeq;syncUrl();const u=new URL(root.dataset.feed,location.href);
 for(const [key,value] of Object.entries(getFilters()))u.searchParams.set(key,value);
 $('gis-apply').disabled=true;status('Loading authorized report locations…');
 try{
  const res=await fetch(u,{credentials:'same-origin',headers:{Accept:'application/json'},signal:activeRequest.signal,cache:'no-store'});
  if(res.status===401||res.status===302){status('Your session has expired. Please sign in again.',true);return}
  if(!res.ok){const body=await res.json().catch(()=>({}));throw Error(body.message||'Map feed could not be loaded ('+res.status+').')}
  const data=await res.json();if(current!==requestSeq)return;
  if(!Array.isArray(data.features))throw Error('Unexpected map response.');
  features=data.features.filter(f=>f?.geometry?.type==='Point'&&f.geometry.coordinates?.length===2);
  clearReportLayers();for(const f of features)addPinFeature(f);attachReportLayer();refreshHeat();renderReports();styleBoundaries();renderLgaList();
  const meta=data.meta||{},trunc=!!meta.truncated;
  status(features.length+' authorized geotagged report'+(features.length===1?'':'s')+(trunc?' (first 1,000 shown; refine filters)':'')+'.');
  if(zoom){const selected=selectedLgaName();if(selected&&fitLga(selected)){}else if(features.length){const bounds=L.latLngBounds(features.map(f=>[f.geometry.coordinates[1],f.geometry.coordinates[0]]));map.fitBounds(bounds,{maxZoom:11,padding:[35,35]})}else if(boundaries)map.fitBounds(boundaries.getBounds(),{padding:[25,25],maxZoom:9})}
  countVisible();
 }catch(e){if(e.name!=='AbortError')status(e.message||'Map loading failed. Retry the filters.',true)}
 finally{if(current===requestSeq)$('gis-apply').disabled=false}
}
function applyFilters(){styleBoundaries();renderLgaList();loadReports()}
$('gis-filter-form').addEventListener('submit',e=>{e.preventDefault();applyFilters()});
$('gis-reset').addEventListener('click',()=>{for(const field of Object.values(selects))field.value='';applyFilters()});
for(const field of Object.values(selects)){if(field.tagName==='SELECT')field.addEventListener('change',applyFilters)}
document.querySelectorAll('[data-panel]').forEach(button=>button.addEventListener('click',()=>{for(const b of document.querySelectorAll('[data-panel]')){const chosen=b===button;b.classList.toggle('active',chosen);b.setAttribute('aria-selected',String(chosen))}for(const panel of document.querySelectorAll('[data-panel-content]'))panel.hidden=panel.dataset.panelContent!==button.dataset.panel}));
$('gis-boundaries').addEventListener('change',()=>{if($('gis-boundaries').checked)map.addLayer(groups.boundaries);else map.removeLayer(groups.boundaries)});
$('gis-clustering').addEventListener('change',attachReportLayer);
$('gis-heat').addEventListener('change',refreshHeat);
$('gis-labels').addEventListener('change',()=>{if($('gis-labels').checked){groups.labels.addTo(map);renderLgaLabels()}else{map.removeLayer(groups.labels)}});
$('gis-basemap').addEventListener('change',e=>changeBasemap(e.target.value));
$('gis-home').addEventListener('click',()=>{if(boundaries)map.fitBounds(boundaries.getBounds(),{padding:[25,25],maxZoom:9})});
$('gis-my-location').addEventListener('click',()=>{if(!navigator.geolocation)return status('Location is unavailable in this browser.',true);status('Requesting your device location (not stored)…');navigator.geolocation.getCurrentPosition(pos=>{const point=[pos.coords.latitude,pos.coords.longitude];if(locationMarker)map.removeLayer(locationMarker);locationMarker=L.circleMarker(point,{radius:9,weight:2,color:'#fff',fillColor:'#176847',fillOpacity:.85}).addTo(map);locationMarker.bindPopup('Your approximate device location (not saved to the server).');map.setView(point,12);locationMarker.openPopup();status('Device location shown locally. It was not sent to the Tracker.')},()=>status('Location access denied or unavailable.',true),{enableHighAccuracy:false,timeout:10000,maximumAge:60000})});
const resetMeasure=()=>{measurePoints=[];if(measureLine){map.removeLayer(measureLine);measureLine=null}};
$('gis-measure').addEventListener('click',()=>{measureActive=!measureActive;resetMeasure();$('gis-measure').setAttribute('aria-pressed',String(measureActive));root.style.cursor=measureActive?'crosshair':'';status(measureActive?'Measurement mode: select two points on the map.':'Measurement mode ended.')});
map.on('click',e=>{if(!measureActive)return;measurePoints.push(e.latlng);if(measurePoints.length===2){const meters=measurePoints[0].distanceTo(measurePoints[1]);measureLine=L.polyline(measurePoints,{color:'#9b753c',weight:3,dashArray:'7,5'}).addTo(map);measureLine.bindPopup('Straight-line distance: '+(meters>=1000?(meters/1000).toFixed(2)+' km':Math.round(meters)+' m')).openPopup();status('Straight-line distance: '+(meters>=1000?(meters/1000).toFixed(2)+' km':Math.round(meters)+' m')+'. Click ruler to clear.');measureActive=false;$('gis-measure').setAttribute('aria-pressed','false');root.style.cursor=''}});
$('gis-fullscreen').addEventListener('click',async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else await $('gis-map-wrapper').requestFullscreen();setTimeout(()=>map.invalidateSize(),150)}catch{status('Fullscreen is not available in this browser.',true)}});
document.addEventListener('fullscreenchange',()=>setTimeout(()=>map.invalidateSize(),180));
$('gis-copy-link').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(location.href);status('Filtered map link copied. Recipients must have authorized access.')}catch{status('Unable to copy. Copy the URL from your browser address bar.',true)}});
function download(name,content,mime){const obj=new Blob([content],{type:mime});const url=URL.createObjectURL(obj);const a=document.createElement('a');a.href=url;a.download=name;document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),4000)}
function csvCell(value){const v=String(value??'');const safe=/^[\s]*[=+\-@]/.test(v)?"'"+v:v;return '"'+safe.replaceAll('"','""')+'"'}
$('gis-export-csv').addEventListener('click',()=>{if(!features.length)return status('No report locations to export.');const keys=['reference','title','lga','community','sector','type','verification_status','progress_status','observation_date'];const rows=[keys.join(',')];for(const f of features){rows.push([...keys.map(key=>csvCell(f.properties?.[key])),csvCell(f.geometry.coordinates[1]),csvCell(f.geometry.coordinates[0])].join(','))}rows[0]+=',latitude,longitude';download('arewa-map-authorized-'+new Date().toISOString().slice(0,10)+'.csv','\ufeff'+rows.join('\r\n'),'text/csv;charset=utf-8');status('Downloaded authorized CSV. Handle private location data securely.')});
$('gis-export-geojson').addEventListener('click',()=>{if(!features.length)return status('No report locations to export.');download('arewa-map-authorized-'+new Date().toISOString().slice(0,10)+'.geojson',JSON.stringify({type:'FeatureCollection',features},null,2),'application/geo+json');status('Downloaded role-restricted GeoJSON. Do not publish without approval.')});
map.on('moveend',countVisible);map.on('zoomend',renderLgaLabels);
map.on('mousemove',e=>{$('gis-coordinate').textContent=e.latlng.lat.toFixed(4)+', '+e.latlng.lng.toFixed(4)});
(async()=>{try{const res=await fetch(root.dataset.boundaries,{cache:'force-cache'});if(!res.ok)throw Error('LGA boundaries unavailable.');addBoundaryLayer(await res.json())}catch(e){status(e.message,true)}finally{loaded=true;loadReports({zoom:false})}})();
})();
