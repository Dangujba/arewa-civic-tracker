@extends('layouts.app')
@section('title','Verification queue | Arewa Civic Tracker')
@section('content')
<section class="queue-head">
  <div><span class="eyebrow">MEAL & ADMIN REVIEW</span><h1>Verification queue</h1><p class="muted">Review field reports, document the evidence and move each report through the authorized decision process.</p></div>
  <a href="{{ route('cases.index') }}" class="btn-outline"><i class="fa-solid fa-table-list" aria-hidden="true"></i> All reports</a>
</section>
<div class="dash-metrics">
  <article class="dash-metric"><div class="dash-metric-head">Awaiting review <i class="fa-regular fa-clock"></i></div><strong>{{ $counts['submitted'] }}</strong><small>New submissions</small></article>
  <article class="dash-metric"><div class="dash-metric-head">In review <i class="fa-solid fa-magnifying-glass"></i></div><strong>{{ $counts['under_review'] }}</strong><small>Evidence being assessed</small></article>
  <article class="dash-metric"><div class="dash-metric-head">Clarification <i class="fa-solid fa-comment-dots"></i></div><strong>{{ $counts['clarification_requested'] }}</strong><small>Waiting on focal person</small></article>
  <article class="dash-metric"><div class="dash-metric-head">Verified <i class="fa-solid fa-circle-check"></i></div><strong>{{ $counts['verified'] }}</strong><small>Pending administrator approval</small></article>
</div>
<section class="card">
 <form method="GET" class="queue-filter" action="{{ route('cases.queue') }}">
 <label for="queue-status">Review status</label><select id="queue-status" name="status">
 <option value="">All active queue items</option>
 @foreach(['submitted','under_review','clarification_requested','verified'] as $value)<option value="{{ $value }}" @selected(request('status')===$value)>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach
 </select><button type="submit" class="btn"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
 </form>
 <div style="overflow-x:auto"><table><thead><tr><th>Reference</th><th>Field report</th><th>LGA</th><th>Current state</th><th>Reviewer</th><th>Due</th><th>Action</th></tr></thead><tbody>
 @forelse($cases as $case)
 <tr><td>{{ $case->reference }}</td><td><strong>{{ $case->title }}</strong><div class="muted">{{ $case->sector?->name }} · {{ $case->created_at->format('d M Y') }}</div></td><td>{{ $case->lga?->name }}</td><td><span class="tag">{{ ucwords(str_replace('_',' ',$case->verification_status)) }}</span></td><td>{{ $case->reviewer?->name??'Unassigned' }}</td><td>{{ $case->review_due_at?->format('d M Y')??'Not set' }}</td><td><a href="{{ route('cases.show',$case) }}" class="link">Open <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td></tr>
 @empty<tr><td colspan="7">No reports currently match this verification queue.</td></tr>@endforelse
 </tbody></table></div>{{ $cases->withQueryString()->links() }}
</section>
@endsection
