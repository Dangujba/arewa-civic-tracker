@extends('layouts.app')
@section('title','Field Officer Dashboard | Arewa Civic Tracker')
@section('section_heading','Field Monitoring Workspace')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">CIVIC INTELLIGENCE FIELD WORKSPACE</span><h1>My field dashboard</h1><p class="muted">Track your submissions, respond to MEAL clarification requests and manage field evidence.</p></div><a class="btn" href="{{ route('cases.create') }}">New field report</a></section>
<div class="dash-metrics">
@foreach([['My submissions',$stats->total,'fa-regular fa-file-lines'],['Approved reports',$stats->approved,'fa-solid fa-check'],['Clarification required',$stats->clarification,'fa-regular fa-comment-dots'],['In progress',$stats->pending,'fa-regular fa-clock']] as $metric)
<article class="dash-metric"><div class="dash-metric-head">{{ $metric[0] }} <i class="{{ $metric[2] }}" aria-hidden="true"></i></div><strong>{{ number_format((int)$metric[1]) }}</strong></article>
@endforeach
</div>
<div class="dash-panels"><section class="dash-panel"><div class="dash-panel-heading"><h2>My assigned LGAs</h2></div>
@if($assignedLgas->isEmpty())<p class="muted">No specific LGA assignment has been recorded yet. Ask a RAWYOD Administrator to configure your coverage.</p>
@else <p class="muted">Your field submissions must use one of these assigned LGAs.</p>
@foreach($assignedLgas as $lga)<span class="tag">{{ $lga->name }}</span> @endforeach
@endif
</section><section class="dash-panel"><div class="dash-panel-heading"><h2>Connectivity and evidence</h2></div>
<p class="muted">Online reports submit immediately. If connectivity is interrupted, the offline outbox can preserve drafts and evidence on this device until synchronization.</p>
<a href="{{ route('cases.create') }}" class="btn-outline">Open report form and offline outbox</a>
</section></div>
<section class="dash-panel dash-recent"><div class="dash-panel-heading"><h2>My recent field reports</h2><a href="{{ route('cases.index') }}" class="dash-text-link">All my submissions</a></div>
<div class="dash-table-wrap"><table><thead><tr><th>Reference</th><th>Report</th><th>Location</th><th>Status</th><th>Next step</th></tr></thead><tbody>
@forelse($recent as $case)<tr><td><a href="{{ route('cases.show',$case) }}">{{ $case->reference }}</a></td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ \App\Models\WorkflowStatusLabel::label('verification',$case->verification_status) }}</td><td>@if($case->verification_status==='clarification_requested')<a href="{{ route('cases.show',$case) }}">Respond to clarification</a>@else<a href="{{ route('cases.show',$case) }}">View</a>@endif</td></tr>@empty<tr><td colspan="5">No reports submitted yet.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
