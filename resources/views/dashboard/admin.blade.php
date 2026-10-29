@extends('layouts.app')
@section('title','Administrator Dashboard | Arewa Civic Tracker')
@section('section_heading','RAWYOD Administration')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">ORGANIZATION-WIDE MONITORING</span><h1>Administrator dashboard</h1><p class="muted">Oversight of reporting activity, geographic coverage, reviewer workload and pending approvals.</p></div><div class="dash-actions"><a class="btn-outline" href="{{ route('admin.operations.index') }}">Field operations</a><a class="btn" href="{{ route('reports.index') }}">Management reports</a></div></section>
<div class="dash-metrics">
@foreach([['Reports received',$stats->total,'fa-regular fa-file-lines'],['Approved records',$stats->approved,'fa-solid fa-check'],['Awaiting review',$stats->pending,'fa-regular fa-clock'],['Overdue reviews',$overdue,'fa-solid fa-triangle-exclamation']] as $metric)
<article class="dash-metric"><div class="dash-metric-head">{{ $metric[0] }} <i class="{{ $metric[2] }}" aria-hidden="true"></i></div><strong>{{ number_format((int)$metric[1]) }}</strong></article>
@endforeach
</div>
<div class="dash-panels">
<section class="dash-panel"><div class="dash-panel-heading"><h2>Field coverage</h2><a class="dash-text-link" href="{{ route('admin.users.index') }}">Manage assignments</a></div>
<p><strong>{{ $assignedFocals }}</strong> of {{ $focalCount }} active focal persons have an assigned LGA.</p>
<p class="muted"><strong>{{ $lgaCoverage }}</strong> of 17 LGAs have at least one assigned active focal person.</p>
@if($assignedFocals < $focalCount)<p class="muted">Assign monitoring areas to unassigned officers from Users & Roles.</p>@endif
</section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>Priority actions</h2><a class="dash-text-link" href="{{ route('admin.operations.index') }}">Operations overview</a></div>
<p>{{ number_format((int)$stats->pending) }} reports await review or clarification.</p>
<p>{{ number_format($overdue) }} reviews have passed their assigned due dates.</p>
<p class="muted">Actions remain available in the verification queue and operations overview.</p>
<a href="{{ route('cases.queue') }}" class="btn-outline">Open verification queue</a>
</section></div>
<div class="dash-panels">
<section class="dash-panel"><div class="dash-panel-heading"><h2>Reports by LGA</h2><a href="{{ route('map.index') }}" class="dash-text-link">Open GIS map</a></div>
@php($maxLga=max(1,(int)$byLga->max('total')))
@forelse($byLga as $row)<div class="dash-bar-row"><div><b>{{ $row->lga?->name??'Unassigned' }}</b><span>{{ $row->total }}</span></div><div class="dash-bar-track"><div style="width:{{ round($row->total/$maxLga*100) }}%"></div></div></div>@empty<p class="muted">No reports submitted yet.</p>@endforelse
</section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>Reporting by sector</h2></div>
@php($maxSector=max(1,(int)$bySector->max('total')))
@forelse($bySector as $row)<div class="dash-bar-row"><div><b>{{ $row->sector?->name??'Other' }}</b><span>{{ $row->total }}</span></div><div class="dash-bar-track gold"><div style="width:{{ round($row->total/$maxSector*100) }}%"></div></div></div>@empty<p class="muted">No sector reports yet.</p>@endforelse
</section></div>
<section class="dash-panel dash-recent"><div class="dash-panel-heading"><h2>Latest field reports</h2><a href="{{ route('cases.index') }}" class="dash-text-link">View all reports</a></div>
<div class="dash-table-wrap"><table><thead><tr><th>Reference</th><th>Report</th><th>LGA</th><th>Status</th></tr></thead><tbody>
@forelse($recent as $case)<tr><td><a href="{{ route('cases.show',$case) }}">{{ $case->reference }}</a></td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ \App\Models\WorkflowStatusLabel::label('verification',$case->verification_status) }}</td></tr>@empty<tr><td colspan="4">No reports received yet.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
