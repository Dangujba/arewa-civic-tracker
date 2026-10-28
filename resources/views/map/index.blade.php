@extends('layouts.app')
@section('title','GIS Intelligence Map | Arewa Civic Tracker')
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyo4CCd8L7f0myA=" crossorigin="">
<style>
.geo-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:25px}.geo-heading h1{margin:4px 0 12px}.geo-heading .muted{max-width:600px;font-size:13px;line-height:1.7}.geo-surface{background:#fff;border:1px solid #dfe8e0;border-radius:16px;overflow:hidden}.geo-tools{padding:24px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;border-bottom:1px solid #e2ece2}.geo-tools label{display:block;font-size:12px;font-weight:800;margin-bottom:8px;color:#38584b}.geo-tools select{width:100%;border:1px solid #cbd9ce;border-radius:10px;padding:11px 13px;background:white;color:#193b2d;outline:none}.geo-tools select:focus{border-color:#2a7750}.geo-map-wrap{position:relative}#map{height:620px;min-height:380px;background:#e5eee6}.geo-status{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:15px 23px;font-size:12px;color:#496255;border-top:1px solid #e2ece2}.geo-legend{display:flex;align-items:center;gap:15px;flex-wrap:wrap}.legend-icon{display:inline-block;width:11px;height:11px;border-radius:3px;background:#1d6a49;margin-right:5px}.legend-icon.marker{border-radius:100%;background:#d0a14f}.map-action{border:0;color:#205d43;background:#e8f3e8;border-radius:8px;padding:10px 12px;font-size:11px;font-weight:800;cursor:pointer}.leaflet-container{font-family:Inter,"Segoe UI",sans-serif}.map-popup-title{font-weight:850;font-size:14px;color:#1d3e2e}.map-popup-meta{margin:6px 0;font-size:12px;color:#4a6657}.map-popup-link{font-size:12px;font-weight:800;color:#20704b}.geo-foot{font-size:11px;color:#748a79;margin-top:13px;line-height:1.6}.geo-foot a{text-decoration:underline}.geo-pill{font-weight:800;color:#1c6646;background:#e9f4e8;border-radius:30px;padding:8px 12px;font-size:12px}
@media(max-width:700px){.geo-heading{display:block}.geo-heading .btn{margin-top:12px}.geo-tools{grid-template-columns:1fr;padding:17px}.geo-status{display:block;line-height:2}.geo-legend{margin-top:6px}#map{height:480px}}
</style>
@endpush
@section('content')
<section class="geo-heading">
  <div><span class="eyebrow">GEOGRAPHIC INTELLIGENCE</span><h1>Yobe State Monitoring Map</h1><p class="muted">Explore local government boundaries and reporting locations. Individual markers appear only for records with authorized GPS coordinates; no locations are invented.</p></div>
  <a class="btn" href="{{ route('cases.create') }}">+ Submit field report</a>
</section>
<div class="geo-surface">
 <div class="geo-tools">
  <div><label for="lga">Local government area</label><select id="lga"><option value="">All 17 LGAs</option>@foreach($lgas as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
  <div><label for="sector">Monitoring sector</label><select id="sector"><option value="">All sectors</option>@foreach($sectors as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
  <div><label for="status">Review status</label><select id="status"><option value="">All verification states</option>@foreach(['submitted','under_review','clarification_requested','verified','approved','rejected'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
 </div>
 <div class="geo-map-wrap"><div id="map" role="region" aria-label="Map of 17 Yobe LGAs and permitted project monitoring locations"></div></div>
 <div class="geo-status"><span id="map-summary" role="status" aria-live="polite"><span class="spin" aria-hidden="true"></span> Loading authorized map layers…</span><span class="geo-legend"><span><i class="legend-icon"></i> LGA boundaries</span><span><i class="legend-icon marker"></i> Field report</span></span></div>
</div>
<p class="geo-foot">Administrative boundaries: <a href="https://www.geoboundaries.org/" target="_blank" rel="noopener noreferrer">geoBoundaries / William & Mary</a>, Nigeria ADM2, CC BY 4.0. Tiles: © OpenStreetMap contributors. Geographic boundaries are reference data; their inclusion is not a RAWYOD project verification.</p>
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(()=>{
  const root=document.getElementById('map'),msg=document.getElementById('map-summary');
  if(!window.L){msg.textContent='Map library could not be loaded. Check your connection.';return}
  const map=L.map(root,{zoomControl:true,scrollWheelZoom:false}).setView([12,11.45],7);
  const basemap=L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap contributors',maxZoom:18}).addTo(map);
  const polygons=L.layerGroup().addTo(map),markers=L.layerGroup().addTo(map);let shapeLayer=null,allBound=null,abortController=null;
  const fields={lga:document.getElementById('lga'),sector:document.getElementById('sector'),status:document.getElementById('status')};
  const selectByName=(name)=>[...fields.lga.options].find(o=>o.textContent.trim().toLowerCase()===String(name).trim().toLowerCase());
  const polygonStyle=f=>({color:'#276347',weight:1.5,opacity:.95,fillColor:'#4d906b',fillOpacity:.12});
  const createLayers=geo=>{
    shapeLayer=L.geoJSON(geo,{style:polygonStyle,onEachFeature:(f,layer)=>{
      const name=f.properties.name;
      const content=document.createElement('div');content.className='map-popup-title';content.textContent=name+' LGA';
      const btn=document.createElement('button');btn.type='button';btn.className='map-action';btn.textContent='Filter this LGA';btn.addEventListener('click',()=>{const option=selectByName(name);if(option){fields.lga.value=option.value;render();map.fitBounds(layer.getBounds(),{padding:[20,20]})}});const wrap=document.createElement('div');wrap.append(content,document.createElement('br'),btn);layer.bindPopup(wrap);
      layer.on('mouseover',()=>layer.setStyle({fillOpacity:.27,weight:2.4}));layer.on('mouseout',()=>shapeLayer.resetStyle(layer));
      layer.on('click',()=>{layer.openPopup()});
    }}).addTo(polygons);
    allBound=shapeLayer.getBounds();if(allBound.isValid())map.fitBounds(allBound,{padding:[13,13],maxZoom:9});
  };
  const markerIcon=L.divIcon({className:'',html:'<span style="display:block;width:15px;height:15px;background:#d4ab60;border:3px solid #fff;border-radius:50%;box-shadow:0 0 0 2px #174c37,0 1px 5px #3455"></span>',iconSize:[17,17],iconAnchor:[8,8]});
  const render=async()=>{
    if(abortController)abortController.abort();abortController=new AbortController();
    const u=new URL(@json(route('map.data')));
    for(const [field,param] of [['lga','lga_id'],['sector','sector_id'],['status','verification_status']])if(fields[field].value)u.searchParams.set(param,fields[field].value);
    msg.innerHTML='<span class="spin" aria-hidden="true"></span> Updating authorized report locations…';
    markers.clearLayers();
    try{
      const r=await fetch(u,{credentials:'same-origin',headers:{Accept:'application/json'},signal:abortController.signal});
      if(!r.ok)throw new Error('Unable to retrieve monitoring records ('+r.status+').');
      const collection=await r.json();const points=[];
      for(const feature of collection.features||[]){
        if(!feature.geometry||feature.geometry.type!=='Point')continue;
        const [lon,lat]=feature.geometry.coordinates;if(!Number.isFinite(lon)||!Number.isFinite(lat))continue;
        const pin=L.marker([lat,lon],{icon:markerIcon});const box=document.createElement('div');
        const title=document.createElement('div');title.className='map-popup-title';title.textContent=feature.properties.title||'Field report';
        const meta=document.createElement('p');meta.className='map-popup-meta';meta.textContent=[feature.properties.reference,feature.properties.lga,feature.properties.verification_status].filter(Boolean).join(' • ');
        const link=document.createElement('a');link.className='map-popup-link';link.textContent='Open report';link.href=feature.properties.url||'#';box.append(title,meta,link);pin.bindPopup(box).addTo(markers);points.push([lat,lon]);
      }
      msg.textContent=points.length+' authorized report location'+(points.length===1?'':'s')+' available. Reports without GPS remain in the reports list.';
      if(points.length&&fields.lga.value==='')map.fitBounds(points,{maxZoom:11,padding:[40,40]});
    }catch(err){if(err.name==='AbortError')return;msg.textContent=err.message||'Map data is temporarily unavailable.'}
  };
  const highlight=()=>{if(!shapeLayer)return;const sel=fields.lga.selectedOptions[0]?.textContent.trim();shapeLayer.eachLayer(layer=>{const active=sel&&fields.lga.value&&layer.feature.properties.name.toLowerCase()===sel.toLowerCase();shapeLayer.resetStyle(layer);if(active){layer.setStyle({fillColor:'#d7af62',fillOpacity:.34,color:'#1b6144',weight:2.5});map.fitBounds(layer.getBounds(),{padding:[30,30]})}});if(!fields.lga.value&&allBound?.isValid())map.fitBounds(allBound,{padding:[15,15]})};
  for(const select of Object.values(fields))select.addEventListener('change',()=>{highlight();render()});
  Promise.all([fetch('/geo/yobe-lgas.geojson',{cache:'force-cache'}).then(r=>{if(!r.ok)throw new Error('LGA layer unavailable');return r.json()}),Promise.resolve()]).then(([geo])=>{createLayers(geo);render()}).catch(err=>{msg.textContent=err.message;render()});
  L.control.layers({'OpenStreetMap':basemap},{'17 LGA boundaries':polygons,'Report locations':markers},{collapsed:true}).addTo(map);
})();
</script>
@endpush
@endsection
