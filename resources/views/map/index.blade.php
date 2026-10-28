@extends('layouts.app')
@section('title','Geospatial Intelligence | Arewa Civic Tracker')
@section('section_heading','GIS Intelligence')
@push('head')
<link rel="stylesheet" href="{{ asset('vendor/maps/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/maps/markercluster.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/maps/markercluster-default.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-map.css') }}">
@endpush
@section('content')
<section class="gis-heading">
  <div><span class="eyebrow">AREWA / GEOSPATIAL INTELLIGENCE</span><h1>Monitoring Intelligence Map</h1><p class="muted">Explore Yobe's 17 local government boundaries and the field reports you are authorized to view. Maps are based on real geography and submitted coordinates.</p></div>
  <div class="gis-heading-actions">
    <a href="{{ route('cases.index') }}" class="gis-action gis-action-light"><i class="fa-solid fa-table-list" aria-hidden="true"></i> Reports</a>
    @can('create',App\Models\CivicCase::class)<a href="{{ route('cases.create') }}" class="gis-action"><i class="fa-solid fa-plus" aria-hidden="true"></i> New field report</a>@endcan
  </div>
</section>
<div class="gis-summary-row" aria-label="Current map statistics">
  <div class="gis-summary-item"><div><small>Geographic coverage</small><strong>17 <span>LGAs</span></strong></div><i class="fa-solid fa-map" aria-hidden="true"></i></div>
  <div class="gis-summary-item"><div><small>Matching locations</small><strong id="gis-total">0</strong></div><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
  <div class="gis-summary-item"><div><small>Approved reports</small><strong id="gis-approved">0</strong></div><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
  <div class="gis-summary-item"><div><small>Displayed markers</small><strong id="gis-visible">0</strong></div><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div>
</div>
<section class="gis-filters" aria-label="Map filters">
  <div class="gis-filter-top"><div class="gis-filter-label"><i class="fa-solid fa-sliders" aria-hidden="true"></i><strong>Explore & filter</strong><span>Combine multiple criteria to focus your analysis</span></div>
    <button class="gis-text-button" type="button" id="gis-reset"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Clear all filters</button></div>
  <form id="gis-filter-form" class="gis-filter-grid" autocomplete="off">
    <label class="gis-field gis-field-search"><span>Search report / community</span><div class="gis-input-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="gis-search" name="q" maxlength="120" placeholder="Search reference, report or community…"></div></label>
    <label class="gis-field"><span>Local government</span><select id="gis-lga" name="lga_id"><option value="">All 17 LGAs</option>@foreach($lgas as $l)<option value="{{ $l->id }}" data-name="{{ $l->name }}">{{ $l->name }}</option>@endforeach</select></label>
    <label class="gis-field"><span>Sector</span><select id="gis-sector" name="sector_id"><option value="">All monitoring sectors</option>@foreach($sectors as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></label>
    <label class="gis-field"><span>Verification status</span><select id="gis-status" name="verification_status"><option value="">All status levels</option>@foreach(['submitted','under_review','clarification_requested','verified','approved','rejected'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></label>
    <label class="gis-field"><span>Observation type</span><select id="gis-type" name="type"><option value="">All report types</option><option value="public_project">Public project</option><option value="governance_issue">Governance issue</option></select></label>
    <label class="gis-field"><span>Project progress</span><select id="gis-progress" name="progress_status"><option value="">All progress states</option>@foreach(['observed','not_started','ongoing','stalled','completed','resolved','other'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></label>
    <label class="gis-field"><span>From date</span><input id="gis-date-from" name="date_from" type="date" max="{{ now()->toDateString() }}"></label>
    <label class="gis-field"><span>To date</span><input id="gis-date-to" name="date_to" type="date" max="{{ now()->toDateString() }}"></label>
    <div class="gis-field gis-filter-submit"><span>Apply selection</span><button type="submit" class="gis-action" id="gis-apply"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply filters</button></div>
  </form>
</section>
<section class="gis-workspace" aria-label="Interactive GIS workspace">
  <aside class="gis-results-panel" aria-label="Map results and layers">
    <div class="gis-panel-title"><div><small>MAP EXPLORER</small><strong>Explore the data</strong></div><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></div>
    <div class="gis-panel-tabs" role="tablist" aria-label="Explorer content"><button class="active" type="button" data-panel="reports" role="tab" aria-selected="true"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> Reports</button><button type="button" data-panel="lgas" role="tab" aria-selected="false"><i class="fa-solid fa-map" aria-hidden="true"></i> LGAs</button><button type="button" data-panel="layers" role="tab" aria-selected="false"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Layers</button></div>
    <div class="gis-panel-content" data-panel-content="reports"><div class="gis-panel-section-head"><strong>Matching reports</strong><span id="gis-results-count">0 records</span></div><div id="gis-report-results" class="gis-report-results" role="list"><div class="gis-empty"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><p>Loading authorized field reports…</p></div></div></div>
    <div class="gis-panel-content" data-panel-content="lgas" hidden><div class="gis-panel-section-head"><strong>Yobe LGAs</strong><span>17 areas</span></div><div id="gis-lga-results" class="gis-lga-results" role="list"><p class="gis-empty">Loading administrative geography…</p></div></div>
    <div class="gis-panel-content" data-panel-content="layers" hidden>
      <div class="gis-panel-section-head"><strong>Map visualization</strong></div>
      <label class="gis-layer-setting"><input type="checkbox" id="gis-boundaries" checked><span><b>LGA boundaries</b><small>17 real administrative polygons</small></span></label>
      <label class="gis-layer-setting"><input type="checkbox" id="gis-clustering" checked><span><b>Marker clustering</b><small>Group dense report locations</small></span></label>
      <label class="gis-layer-setting"><input type="checkbox" id="gis-heat"><span><b>Density heatmap</b><small>Visualize patterns in authorized reports</small></span></label>
      <label class="gis-layer-setting"><input type="checkbox" id="gis-labels" checked><span><b>LGA names</b><small>Labels at regional zoom levels</small></span></label>
      <div class="gis-layer-section"><label for="gis-basemap"><b>Base map</b></label><select id="gis-basemap"><option value="street">OpenStreetMap streets</option><option value="light">Light vector-style tiles</option><option value="dark">Dark terrain-style tiles</option></select></div>
      <p class="gis-layer-note">Visibility is restricted to your account permissions. Exported geographic records may contain sensitive coordinates.</p>
    </div>
    <div class="gis-panel-footer"><span><i class="fa-solid fa-lock" aria-hidden="true"></i> Role-restricted intelligence</span></div>
  </aside>
  <div class="gis-map-wrapper" id="gis-map-wrapper">
    <div class="gis-toolbar" role="toolbar" aria-label="Map tools">
      <button type="button" id="gis-home" aria-label="Fit all Yobe LGAs" title="Fit all LGAs"><i class="fa-solid fa-expand" aria-hidden="true"></i></button>
      <button type="button" id="gis-my-location" aria-label="Locate my device" title="Locate my device"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></button>
      <button type="button" id="gis-measure" aria-pressed="false" aria-label="Measure distance between points" title="Measure distance"><i class="fa-solid fa-ruler" aria-hidden="true"></i></button>
      <button type="button" id="gis-fullscreen" aria-label="Fullscreen map" title="Fullscreen map"><i class="fa-solid fa-up-right-and-down-left-from-center" aria-hidden="true"></i></button>
      <button type="button" id="gis-copy-link" aria-label="Copy filtered map link" title="Copy filtered map link"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
      <div class="gis-toolbar-divider"></div>
      <button type="button" id="gis-export-csv" aria-label="Export visible report CSV" title="Download role-restricted CSV"><i class="fa-solid fa-file-csv" aria-hidden="true"></i></button>
      <button type="button" id="gis-export-geojson" aria-label="Export GeoJSON" title="Download role-restricted GeoJSON"><i class="fa-solid fa-file-code" aria-hidden="true"></i></button>
    </div>
    <div id="map" data-feed="{{ route('map.data') }}" data-boundaries="{{ asset('geo/yobe-lgas.geojson') }}" role="application" aria-label="Interactive map of Yobe administrative boundaries and authorized field reports"></div>
    <div class="gis-map-legend" aria-label="Verification legend"><strong>VERIFICATION STATUS</strong><span><i class="gis-dot" style="--dot:#c09c55"></i>Submitted</span><span><i class="gis-dot" style="--dot:#3083ae"></i>In review</span><span><i class="gis-dot" style="--dot:#1e7852"></i>Approved</span><span><i class="gis-dot" style="--dot:#b85455"></i>Needs attention</span></div>
    <div class="gis-map-bottom"><div id="gis-message" role="status" aria-live="polite"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Loading map…</div><span id="gis-coordinate">Yobe State, Nigeria</span></div>
  </div>
</section>
<p class="gis-attribution">The map represents actual LGA polygons and authorized records, not invented report locations. Boundary data: <a href="https://www.geoboundaries.org/" target="_blank" rel="noopener noreferrer">geoBoundaries / William & Mary</a> (CC BY 4.0). Map tiles: OpenStreetMap contributors and CARTO. GPS use requires explicit device permission. Exports must be handled as confidential operational records.</p>
@push('scripts')
<script src="{{ asset('vendor/maps/leaflet.js') }}"></script>
<script src="{{ asset('vendor/maps/markercluster.js') }}"></script>
<script src="{{ asset('vendor/maps/heat.js') }}"></script>
<script defer src="{{ asset('js/act-map.js') }}"></script>
@endpush
@endsection
