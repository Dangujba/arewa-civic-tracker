@extends('layouts.app')
@section('title','GIS Intelligence Map | Arewa Civic Tracker')
@section('section_heading','GIS Intelligence')
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyo4CCd8L7f0myA=" crossorigin="">
<link rel="stylesheet" href="{{ asset('css/act-map.css') }}">
@endpush
@section('content')
<section class="geo-heading">
  <div><span class="eyebrow">GEOSPATIAL INTELLIGENCE</span><h1>Yobe Monitoring Intelligence Map</h1><p class="muted">Explore administrative boundaries and location-based civic observations, with records restricted to your assigned role.</p></div>
  <div class="geo-heading-actions"><a href="{{ route('cases.index') }}" class="geo-btn geo-light"><i class="fa-solid fa-list" aria-hidden="true"></i> Reports</a>@can('create',App\Models\CivicCase::class)<a href="{{ route('cases.create') }}" class="geo-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> New report</a>@endcan</div>
</section>
<div class="geo-summary" aria-label="Map monitoring summary">
  <div><span>Geographic coverage</span><strong>17 <small>LGAs</small></strong><i class="fa-solid fa-map" aria-hidden="true"></i></div>
  <div><span>Matched locations</span><strong id="geo-matched">0</strong><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
  <div><span>Approved records</span><strong id="geo-approved">0</strong><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
  <div><span>Records within map</span><strong id="geo-visible">0</strong><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div>
</div>
<section class="geo-filter-panel" aria-label="GIS filters">
  <div class="geo-filter-heading"><strong><i class="fa-solid fa-sliders" aria-hidden="true"></i> Explore and filter</strong><button id="geo-clear" type="button" class="geo-link"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset filters</button></div>
  <form id="geo-filters">
    <label><span>Search case or community</span><input id="geo-search" name="q" maxlength="120" placeholder="Reference, report, community"></label>
    <label><span>Local government</span><select id="geo-lga" name="lga_id"><option value="">All 17 LGAs</option>@foreach($lgas as $lga)<option value="{{ $lga->id }}">{{ $lga->name }}</option>@endforeach</select></label>
    <label><span>Sector</span><select id="geo-sector" name="sector_id"><option value="">All sectors</option>@foreach($sectors as $sector)<option value="{{ $sector->id }}">{{ $sector->name }}</option>@endforeach</select></label>
    <label><span>Verification</span><select id="geo-status" name="verification_status"><option value="">All status levels</option>@foreach(['submitted','under_review','clarification_requested','verified','approved','rejected'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></label>
    <label><span>Report type</span><select id="geo-type" name="type"><option value="">All types</option><option value="public_project">Public project</option><option value="governance_issue">Governance issue</option></select></label>
    <label><span>Project progress</span><select id="geo-progress" name="progress_status"><option value="">All progress states</option>@foreach(['observed','not_started','ongoing','stalled','completed','resolved','other'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></label>
    <label><span>Date from</span><input id="geo-from" name="date_from" type="date" max="{{ now()->toDateString() }}"></label>
    <label><span>Date to</span><input id="geo-to" name="date_to" type="date" max="{{ now()->toDateString() }}"></label>
    <button class="geo-btn" type="submit" id="geo-apply"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply filters</button>
  </form>
</section>
<section class="geo-workspace" aria-label="Interactive map explorer">
  <aside class="geo-explorer">
    <header><small>MAP EXPLORER</small><strong>Spatial intelligence</strong></header>
    <nav class="geo-tabs" role="tablist" aria-label="Explorer tabs">
      <button type="button" role="tab" aria-selected="true" class="active" data-geo-tab="reports"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> Reports</button>
      <button type="button" role="tab" aria-selected="false" data-geo-tab="lgas"><i class="fa-solid fa-map" aria-hidden="true"></i> LGAs</button>
      <button type="button" role="tab" aria-selected="false" data-geo-tab="layers"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Layers</button>
    </nav>
    <div class="geo-tab-panel" data-geo-panel="reports"><h3>Filtered records <small id="geo-count">0</small></h3><div id="geo-report-list" role="list" class="geo-scroll"><p class="geo-empty">Loading authorized monitoring records…</p></div></div>
    <div class="geo-tab-panel" data-geo-panel="lgas" hidden><h3>Local government areas <small>17</small></h3><div id="geo-lga-list" role="list" class="geo-scroll"><p class="geo-empty">Loading boundaries…</p></div></div>
    <div class="geo-tab-panel" data-geo-panel="layers" hidden><h3>Map visualization</h3>
      <label class="geo-check"><input type="checkbox" id="geo-show-boundaries" checked><span>Administrative boundaries<small>Real 17 LGA polygons</small></span></label>
      <label class="geo-check"><input type="checkbox" id="geo-show-labels" checked><span>LGA names<small>Visible when zoomed in</small></span></label>
      <label class="geo-check"><input type="checkbox" id="geo-group-pins" checked><span>Cluster nearby reports<small>Group overlapping observations</small></span></label>
      <label class="geo-check"><input type="checkbox" id="geo-heat"><span>Location density<small>Relative concentration of authorized reports</small></span></label>
      <label class="geo-basemap"><span>Base map</span><select id="geo-base"><option value="street">OpenStreetMap streets</option><option value="light">CARTO light</option><option value="dark">CARTO dark</option></select></label>
      <p class="geo-tip">Map data can include sensitive location information. Export only when authorized.</p>
    </div>
    <footer><i class="fa-solid fa-lock" aria-hidden="true"></i> Role-restricted reports</footer>
  </aside>
  <div class="geo-map-holder" id="geo-map-holder">
    <div id="map" data-feed="{{ route('map.data') }}" data-boundaries="{{ asset('geo/yobe-lgas.geojson') }}" role="application" aria-label="Yobe civic monitoring interactive map"></div>
    <div class="geo-toolbar" role="toolbar" aria-label="Map tools">
      <button type="button" id="geo-home" title="Fit all LGAs" aria-label="Fit all local governments"><i class="fa-solid fa-expand" aria-hidden="true"></i></button>
      <button type="button" id="geo-locate" title="Locate me" aria-label="Locate my device"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></button>
      <button type="button" id="geo-measure" title="Distance measure" aria-pressed="false" aria-label="Measure distance"><i class="fa-solid fa-ruler" aria-hidden="true"></i></button>
      <button type="button" id="geo-fullscreen" title="Fullscreen" aria-label="Fullscreen map"><i class="fa-solid fa-up-right-and-down-left-from-center" aria-hidden="true"></i></button>
      <button type="button" id="geo-share" title="Copy filtered link" aria-label="Copy filtered map link"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
      <button type="button" id="geo-csv" title="Export authorized CSV" aria-label="Export authorized CSV"><i class="fa-solid fa-file-csv" aria-hidden="true"></i></button>
      <button type="button" id="geo-geojson" title="Export authorized GeoJSON" aria-label="Export authorized GeoJSON"><i class="fa-solid fa-file-code" aria-hidden="true"></i></button>
    </div>
    <div class="geo-legend"><b>VERIFICATION</b><span><i style="--status:#c19a51"></i>Submitted</span><span><i style="--status:#3387a8"></i>In review</span><span><i style="--status:#207954"></i>Approved</span><span><i style="--status:#ba5956"></i>Needs attention</span></div>
    <div class="geo-message"><span id="geo-message" role="status" aria-live="polite">Preparing map…</span><span id="geo-coords">Yobe State</span></div>
  </div>
</section>
<p class="geo-attribution">Boundaries: <a href="https://www.geoboundaries.org/" target="_blank" rel="noopener noreferrer">geoBoundaries / William & Mary</a>, CC BY 4.0. Tiles: OpenStreetMap contributors and CARTO. No synthetic report locations are shown. Device location is permission-based and is not stored by these map tools.</p>
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script defer src="{{ asset('js/act-map-explorer.js') }}"></script>
@endpush
@endsection
