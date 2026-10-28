@extends('layouts.app')
@section('title','MEAL Review Dashboard | Arewa Civic Tracker')
@section('section_heading','MEAL Review Workspace')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">MONITORING · EVALUATION · ACCOUNTABILITY · LEARNING</span><h1>MEAL review dashboard</h1><p class="muted">Review reports assigned to you and submissions currently awaiting assignment.</p></div><a class="btn" href="{{ route('cases.queue') }}">Open verification queue</a></section>
<div class="dash-metrics">
@foreach([['Accessible queue',$stats->total,'fa-regular fa-file-lines'],['My active assignments',$mine,'fa-solid fa-user-check'],['Waiting for clarification',$stats->clarification,'fa-regular fa-comment-dots'],['Overdue reviews',$overdue,'fa-regular fa-clock']] as $metric)
<article class="dash-metric"><div class="dash-metric-head">{{ $metric[0] }} <i class="{{ $metric[2] }}" aria-hidden="true"></i></div><strong>{{ number_format((int)$metric[1]) }}</strong></article>
@endforeach
</div>
<section class="dash-panel dash-recent"><div class="dash-panel-heading"><h2>Review priorities</h2><a href="{{ route('cases.queue') }}" class="dash-text-link">Full queue</a></div>
<p class="muted">You may review unassigned submissions and those explicitly assigned to your account. Administrator approval is required after verification.</p>
<div class="dash-table-wrap"><table><thead><tr><th>Reference</th><th>Field report</th><th>LGA</th><th>Assigned reviewer</th><th>Deadline</th><th>Status</th></tr></thead><tbody>
@forelse($recent as $case)<tr><td><a href="{{ route('cases.show',$case) }}">{{ $case->reference }}</a></td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ $case->reviewer?->name ?? 'Unassigned' }}</td><td>{{ $case->review_due_at?->format('d M Y') ?? 'Not set' }}</td><td>{{ ucwords(str_replace('_',' ',$case->verification_status)) }}</td></tr>@empty<tr><td colspan="6">No submissions require your review.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
