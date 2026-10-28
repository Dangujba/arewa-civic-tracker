@extends('layouts.app')
@section('title','Management Reports | Arewa Civic Tracker')
@section('section_heading','Reports & Analytics')
@section('content')
<section class="dash-heading">
 <div><span class="eyebrow">MANAGEMENT INTELLIGENCE</span><h1>Reports & Analytics</h1>
 <p class="muted">Filter field observations, review sector and LGA trends, then export an Excel register or a presentation-ready PDF with verified charts and context.</p></div>
 <div class="dash-actions">
 <a class="btn-outline" href="{{ route('reports.excel',request()->query()) }}"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Export Excel</a>
 <a class="btn" href="{{ route('reports.pdf',request()->query()) }}"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i aria-label="Export PDF stakeholder report"> Export PDF Report</a>
 </div>
</section>
<form method="GET" action="{{ route('reports.index') }}" class="card reporting-filters">
 <div class="grid">
  <div class="field"><label>Search reports</label><input type="search" name="q" maxlength="150" value="{{ request('q') }}" placeholder="Reference, title, community"></div>
  <div class="field"><label>Local government</label><select name="lga_id"><option value="">All LGAs</option>@foreach($lgas as $lga)<option value="{{ $lga->id }}" @selected(request('lga_id')==$lga->id)>{{ $lga->name }}</option>@endforeach</select></div>
  <div class="field"><label>Sector</label><select name="sector_id"><option value="">All sectors</option>@foreach($sectors as $sector)<option value="{{ $sector->id }}" @selected(request('sector_id')==$sector->id)>{{ $sector->name }}</option>@endforeach</select></div>
  <div class="field"><label>Status</label><select name="verification_status"><option value="">All statuses</option>@foreach(['submitted','under_review','clarification_requested','verified','approved','rejected'] as $status)<option value="{{ $status }}" @selected(request('verification_status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
  <div class="field"><label>Report type</label><select name="type"><option value="">All types</option><option value="public_project" @selected(request('type')==='public_project')>Public project</option><option value="governance_issue" @selected(request('type')==='governance_issue')>Governance issue</option></select></div>
  <div class="field"><label>From date</label><input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ now()->format('Y-m-d') }}"></div>
  <div class="field"><label>To date</label><input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ now()->format('Y-m-d') }}"></div>
 </div>
 <button type="submit" class="btn"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply filters</button>
 <a class="btn-outline" href="{{ route('reports.index') }}">Clear filters</a>
</form>
<div class="dash-metrics">
 @foreach([
 ['label'=>'Matching reports','value'=>$totals->total,'symbol'=>'fa-regular fa-file-lines'],
 ['label'=>'Project observations','value'=>$totals->projects,'symbol'=>'fa-solid fa-building'],
 ['label'=>'Approved reports','value'=>$totals->approved,'symbol'=>'fa-solid fa-circle-check'],
 ['label'=>'Awaiting review','value'=>$totals->pending,'symbol'=>'fa-regular fa-clock']
 ] as $stat)
 <article class="dash-metric"><div class="dash-metric-head">{{ $stat['label'] }}<i class="{{ $stat['symbol'] }}" aria-hidden="true"></i></div>
 <strong>{{ number_format((int)$stat['value']) }}</strong><small>Current filter selection</small></article>
 @endforeach
</div>

<section class="reports-charts" aria-label="Reporting trends and verification statistics">
 <div class="dash-panel reports-trend-panel">
  <div class="dash-panel-heading"><div><span class="eyebrow">REPORTING ACTIVITY</span><h2>Monthly submissions</h2></div><small class="muted">Last 12 months · filtered records</small></div>
  <div class="reports-chart-area"><canvas id="reports-trend" aria-label="Monthly report submissions in the last 12 months" role="img"></canvas></div>
 </div>
 <div class="dash-panel reports-status-panel">
  <div class="dash-panel-heading"><div><span class="eyebrow">REVIEW OUTCOMES</span><h2>Verification status</h2></div></div>
  <div class="reports-chart-status"><canvas id="reports-status" aria-label="Verification status distribution" role="img"></canvas></div>
 </div>
</section>
<div class="dash-panel reports-progress-panel">
 <div class="dash-panel-heading"><div><span class="eyebrow">PROJECT MONITORING</span><h2>Progress breakdown</h2></div></div>
 <div class="reports-status-grid">
 @forelse($byProgress as $row)<div class="reports-status-pill"><span>{{ ucwords(str_replace('_',' ',$row->progress_status)) }}</span><strong>{{ number_format($row->total) }}</strong></div>
 @empty<p class="muted">Progress breakdown appears after reports are submitted.</p>
 @endforelse
 </div>
</div>
<div class="dash-panels">
 <section class="dash-panel"><div class="dash-panel-heading"><h2>By local government</h2></div>
 @php($maxLga=max(1,(int)$byLga->max('total')))
 @forelse($byLga as $row)<div class="dash-bar-row"><div><b>{{ $row->lga?->name??'Unassigned' }}</b><span>{{ $row->total }}</span></div><div class="dash-bar-track"><div style="width:{{ round($row->total/$maxLga*100) }}%"></div></div></div>@empty<p class="muted">No matching reports.</p>@endforelse
 </section>
 <section class="dash-panel"><div class="dash-panel-heading"><h2>By sector</h2></div>
 @php($maxSector=max(1,(int)$bySector->max('total')))
 @forelse($bySector as $row)<div class="dash-bar-row"><div><b>{{ $row->sector?->name??'Other' }}</b><span>{{ $row->total }}</span></div><div class="dash-bar-track gold"><div style="width:{{ round($row->total/$maxSector*100) }}%"></div></div></div>@empty<p class="muted">No matching reports.</p>@endforelse
 </section>
</div>
<section class="dash-panel dash-recent">
 <div class="dash-panel-heading"><h2>Report register</h2><small class="muted">Includes only records accessible to your account</small></div>
 <div class="dash-table-wrap"><table><thead><tr><th>Reference</th><th>Report</th><th>LGA</th><th>Sector</th><th>Status</th><th>Observed</th></tr></thead><tbody>
 @forelse($recent as $case)<tr><td><a href="{{ route('cases.show',$case) }}">{{ $case->reference }}</a></td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ $case->sector?->name }}</td><td>{{ ucwords(str_replace('_',' ',$case->verification_status)) }}</td><td>{{ $case->observation_date?->format('d M Y') }}</td></tr>
 @empty<tr><td colspan="6">No reports match the selected filters.</td></tr>@endforelse
 </tbody></table></div>{{ $recent->links() }}
</section>
@endsection

@push('scripts')
<script src="{{ asset('vendor/charts/chart.umd.js') }}"></script>
<script>
(()=>{
 'use strict';
 if(!window.Chart)return;
 const labels=@json($monthLabels),series=@json($monthlyCounts);
 const statuses=@json($byStatus->pluck('verification_status')->values());
 const statusCounts=@json($byStatus->pluck('total')->map(fn($value)=>(int)$value)->values());
 Chart.defaults.font.family='Inter, Segoe UI, Arial, sans-serif';
 Chart.defaults.color='#637769';
 const line=document.getElementById('reports-trend');
 if(line)new Chart(line,{type:'line',data:{labels,datasets:[{label:'Submitted reports',data:series,borderColor:'#206b49',backgroundColor:'rgba(39,124,78,.09)',fill:true,tension:.25,borderWidth:2.5,pointRadius:3,pointHoverRadius:6}]},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#ecf1eb'}},x:{grid:{display:false}}}}});
 const pie=document.getElementById('reports-status');
 if(pie&&statusCounts.length&&statusCounts.some(v=>v>0))new Chart(pie,{type:'doughnut',data:{labels:statuses.map(s=>s.replaceAll('_',' ')),datasets:[{data:statusCounts,backgroundColor:['#b79a57','#418ab0','#c47b49','#31876f','#205f43','#ba605a'],borderColor:'#ffffff',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{boxWidth:9,padding:13,font:{size:10}}}}}});
 else if(pie){const hint=document.createElement('p');hint.className='muted';hint.textContent='No status data available for the selected filters.';pie.parentNode.replaceChildren(hint)}
})();
</script>
@endpush
