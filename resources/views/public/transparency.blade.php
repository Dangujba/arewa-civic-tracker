<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Explore published and approved public project monitoring reports in Yobe State without signing in.">
<meta name="theme-color" content="#164f3b">
<link rel="icon" type="image/webp" href="{{ asset('icons/arewa-handdrawn-map.webp') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/maps/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-public.css') }}?v=20261029-1">
<title>Public Transparency | Arewa Civic Tracker</title>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="public-header">
 <div class="public-shell public-nav">
  <a href="{{ route('home') }}" class="brand"><img src="{{ asset('icons/arewa-handdrawn-map.webp') }}" alt="" width="40" height="40"><span><strong>arewa.</strong><small>CIVIC TRACKER</small></span></a>
  <nav aria-label="Page navigation"><a href="{{ route('home') }}">Home</a><a href="{{ route('public.transparency') }}" aria-current="page">Public transparency</a><a class="staff-link" href="{{ route('login') }}">Staff portal</a></nav>
 </div>
</header>
<main id="main">
 <div class="public-shell">
 <section class="intro">
   <div><span class="eyebrow">RAWYOD · VOICES TO VOTES · Yobe State</span>
    <h1>Explore civic monitoring in your community.</h1>
    <p>Browse reports that RAWYOD has approved and deliberately published for public viewing. No account or registration required.</p>
   </div>
   <div class="intro-aside"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><strong>Public information only</strong><span>Pending reports, private evidence, staff details and confidential notes are not shown.</span></div>
 </section>
 <section class="metrics" aria-label="Published monitoring statistics">
   <div><span>Published reports</span><strong>{{ number_format($totals->total ?? 0) }}</strong></div>
   <div><span>Public projects</span><strong>{{ number_format($totals->projects ?? 0) }}</strong></div>
   <div><span>Governance issues</span><strong>{{ number_format($totals->issues ?? 0) }}</strong></div>
   <div><span>LGAs represented</span><strong>{{ number_format($totals->lgas ?? 0) }} <small>/ 17</small></strong></div>
 </section>
 <section class="filters" aria-label="Filter published reports">
 <div class="section-heading"><div><span class="eyebrow">Explore the evidence</span><h2>Find published reports</h2></div><a href="{{ route('public.transparency') }}" class="reset">Clear filters</a></div>
 <form method="GET" action="{{ route('public.transparency') }}" id="public-filters">
  <label class="search-field">Search title, reference or community<input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Search public projects or places..."></label>
  <label>LGA<select name="lga_id"><option value="">All LGAs</option>@foreach($lgas as $lga)<option value="{{ $lga->id }}" @selected(request('lga_id')==(string)$lga->id)>{{ $lga->name }}</option>@endforeach</select></label>
  <label>Ward<input type="search" name="ward" maxlength="150" value="{{ request('ward') }}" placeholder="Filter by ward"></label>
  <label>Sector<select name="sector_id"><option value="">All sectors</option>@foreach($sectors as $sector)<option value="{{ $sector->id }}" @selected(request('sector_id')==(string)$sector->id)>{{ $sector->name }}</option>@endforeach</select></label>
  <label>Type<select name="type"><option value="">All types</option><option value="public_project" @selected(request('type')==='public_project')>Public projects</option><option value="governance_issue" @selected(request('type')==='governance_issue')>Governance issues</option></select></label>
  <label>Progress<select name="progress_status"><option value="">All progress states</option>@foreach($progressLabels as $code=>$label)<option value="{{ $code }}" @selected(request('progress_status')===$code)>{{ $label }}</option>@endforeach</select></label>
  <button type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Apply filters</button>
 </form>
 </section>
 <section class="map-section" aria-label="Public monitoring map">
  <div class="section-heading"><div><span class="eyebrow">Geographic distribution</span><h2>Public monitoring map</h2><p>Location pins are approximate to protect field evidence and personal information.</p></div><span class="read-only"><i class="fa-regular fa-eye" aria-hidden="true"></i> View only</span></div>
  <div class="map-grid">
   <div id="public-map" data-feed="{{ route('public.transparency.map') }}" data-boundaries="{{ asset('geo/yobe-lgas.geojson') }}" role="region" aria-label="Map of publicly published projects in Yobe State"><p>Loading map…</p></div>
   <aside class="lga-panel"><h3>LGAs by published reports</h3>
    @forelse($byLga as $item)
      <a href="{{ route('public.transparency',array_merge(request()->except(['page','lga_id']),['lga_id'=>$item->lga_id])) }}"><span>{{ $item->lga?->name ?? 'Other LGA' }}</span><strong>{{ $item->total }}</strong></a>
    @empty
      <p>No public reports match these filters yet.</p>
    @endforelse
   </aside>
  </div>
 </section>
 <section class="report-section" aria-label="Public monitoring records">
  <div class="section-heading"><div><span class="eyebrow">Published monitoring records</span><h2>Projects and issues</h2></div><p>{{ $reports->total() }} matching record(s)</p></div>
  <div class="report-grid">
  @forelse($reports as $report)
   <article class="report">
    <div class="report-top"><span>{{ $report->type==='public_project'?'Public project':'Governance issue' }}</span><span class="approved"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approved & published</span></div>
    <h3>{{ $report->title }}</h3>
    <dl><div><dt>Reference</dt><dd>{{ $report->reference }}</dd></div>
        <div><dt>LGA</dt><dd>{{ $report->lga?->name }}</dd></div>
        <div><dt>Ward</dt><dd>{{ $report->ward ?: 'Not specified' }}</dd></div><div><dt>Community</dt><dd>{{ $report->community }}</dd></div>
        <div><dt>Sector</dt><dd>{{ $report->sector?->name }}</dd></div>
        <div><dt>Progress</dt><dd>{{ $progressLabels[$report->progress_status]??ucwords(str_replace('_',' ',$report->progress_status)) }}</dd></div>
        <div><dt>Observed</dt><dd>{{ $report->observation_date?->format('d M Y') }}</dd></div></dl>
   </article>
  @empty
   <div class="empty"><i class="fa-regular fa-folder-open" aria-hidden="true"></i><h3>No published reports found</h3><p>RAWYOD releases approved information for public viewing. Try another filter or check again later.</p></div>
  @endforelse
  </div>
  @if($reports->hasPages())<nav class="pages" aria-label="Results pages">{{ $reports->links() }}</nav>@endif
 </section>
 <p class="privacy">Data displayed here is approved for public communication. Only selected, non-confidential report fields are shown. Map pins have reduced precision and may not represent an exact site. Mapping: © OpenStreetMap contributors, geoBoundaries / William & Mary.</p>
 </div>
</main>
<footer><div class="public-shell"><span>© {{ now()->year }} Arewa Civic Tracker · RAWYOD · VOICES TO VOTES</span><a href="{{ route('home') }}">Back to home</a></div></footer>
<script src="{{ asset('vendor/maps/leaflet.js') }}"></script>
<script defer src="{{ asset('js/act-public.js') }}?v=20261029-1"></script>
</body>
</html>
