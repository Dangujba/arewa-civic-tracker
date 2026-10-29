@extends('layouts.app')
@section('title','Intelligence Dashboard | Arewa Civic Tracker')
@section('content')
<section class="dash-heading">
  <div>
    <span class="eyebrow">LIVE MONITORING WORKSPACE</span>
    <h1>Monitoring overview</h1>
    <p class="muted">Evidence, project observations and progress across the communities assigned to your account.</p>
  </div>
  <div class="dash-actions">
    @if(auth()->user()->canReview())<a href="{{ route('reports.index') }}" class="btn-outline"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Reports & Analytics</a>@endif
    <a href="{{ route('map.index') }}" class="btn-outline">Explore GIS map <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
    @if(auth()->user()->canReview())<a href="{{ route('cases.queue') }}" class="btn"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Verification queue</a>@else<a href="{{ route('cases.create') }}" class="btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> New field report</a>@endif
  </div>
</section>
<div class="dash-metrics" aria-label="Report statistics">
  @foreach([
    ['label'=>'Reports received','value'=>(int)$stats->total,'symbol'=>'fa-regular fa-file-lines','hint'=>'All visible submissions'],
    ['label'=>'Projects monitored','value'=>(int)$stats->projects,'symbol'=>'fa-solid fa-diagram-project','hint'=>'Public project observations'],
    ['label'=>'Approved records','value'=>(int)$stats->approved,'symbol'=>'fa-solid fa-check-circle','hint'=>'Approved for monitoring use'],
    ['label'=>'Awaiting review','value'=>(int)$stats->pending,'symbol'=>'fa-regular fa-clock','hint'=>'Require reviewer attention']
  ] as $metric)
  <article class="dash-metric">
    <div class="dash-metric-head"><span>{{ $metric['label'] }}</span><span class="dash-metric-icon" aria-hidden="true"><i class="{{ $metric['symbol'] }}" aria-hidden="true"></i></span></div>
    <strong>{{ number_format($metric['value']) }}</strong>
    <small>{{ $metric['hint'] }}</small>
  </article>
  @endforeach
</div>
<div class="dash-panels">
  <section class="dash-panel">
    <div class="dash-panel-heading"><div><span class="eyebrow">GEOGRAPHIC ACTIVITY</span><h2>Reports by LGA</h2></div><a href="{{ route('map.index') }}" class="dash-text-link">View map <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
    @php($maxLga = max(1,(int) $byLga->max('total')))
    @forelse($byLga as $row)
    <div class="dash-bar-row"><div><b>{{ $row->lga?->name ?? 'Unassigned' }}</b><span>{{ number_format($row->total) }}</span></div>
      <div class="dash-bar-track"><div style="width:{{ max(3,round(((int)$row->total/$maxLga)*100)) }}%"></div></div></div>
    @empty
    <div class="dash-empty"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><b>No geographic observations yet</b><p>When field reports are submitted, distribution by LGA will appear here automatically.</p></div>
    @endforelse
  </section>
  <section class="dash-panel">
    <div class="dash-panel-heading"><div><span class="eyebrow">SECTOR INTELLIGENCE</span><h2>Reporting by sector</h2></div><a href="{{ route('cases.index') }}" class="dash-text-link">Browse reports <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
    @php($maxSector = max(1,(int) $bySector->max('total')))
    @forelse($bySector as $row)
    <div class="dash-bar-row"><div><b>{{ $row->sector?->name ?? 'Other' }}</b><span>{{ number_format($row->total) }}</span></div>
      <div class="dash-bar-track gold"><div style="width:{{ max(3,round(((int)$row->total/$maxSector)*100)) }}%"></div></div></div>
    @empty
    <div class="dash-empty"><i class="fa-solid fa-chart-column" aria-hidden="true"></i><b>No sector observations yet</b><p>Health, education, livelihoods and other sector indicators will be generated from report records.</p></div>
    @endforelse
  </section>
</div>
<section class="dash-panel dash-recent">
  <div class="dash-panel-heading"><div><span class="eyebrow">ACTIVITY FEED</span><h2>Recent submissions</h2></div><a href="{{ route('cases.index') }}" class="dash-text-link">View all reports <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
  <div class="dash-table-wrap"><table><thead><tr><th>Reference</th><th>Report title</th><th>Location</th><th>Review status</th></tr></thead>
    <tbody>@forelse($recent as $c)<tr>
      <td><a href="{{ route('cases.show',$c) }}" class="dash-text-link">{{ $c->reference }}</a></td><td>{{ $c->title }}</td><td>{{ $c->lga?->name ?? 'Not specified' }}</td><td><span class="dash-status">{{ \App\Models\WorkflowStatusLabel::label('verification',$c->verification_status) }}</span></td>
    </tr>@empty<tr><td colspan="4"><div class="dash-empty"><i class="fa-regular fa-file-lines" aria-hidden="true"></i><b>There are no reports yet</b><p>New submissions will populate this activity feed automatically. No example statistics are presented as real reports.</p></div></td></tr>@endforelse</tbody></table></div>
</section>
@endsection
