@extends('layouts.app')
@section('title','Monitoring Reports | Arewa Civic Tracker')
@section('section_heading','Monitoring Reports')
@section('content')
<section class="dash-heading act-page-heading">
 <div><span class="eyebrow">FIELD OBSERVATIONS</span><h1>Monitoring reports</h1><p class="muted">{{ number_format($cases->total()) }} accessible reports</p></div>
 @can('create',App\Models\CivicCase::class)
  <a class="btn" href="{{ route('cases.create') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> New report</a>
 @endcan
</section>
<section class="card act-report-filter-card">
 <form method="GET" action="{{ route('cases.index') }}" class="act-inline-filter">
  <div class="field"><label for="report-filter-lga">Local government</label><select id="report-filter-lga" name="lga_id"><option value="">All LGAs</option>@foreach($lgas as $lga)<option value="{{ $lga->id }}" @selected(request('lga_id')==$lga->id)>{{ $lga->name }}</option>@endforeach</select></div>
  <div class="field"><label for="report-filter-status">Verification status</label><select id="report-filter-status" name="verification_status"><option value="">All statuses</option>@foreach(['submitted','under_review','clarification_requested','verified','approved','rejected'] as $status)<option value="{{ $status }}" @selected(request('verification_status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
  <div class="act-filter-actions"><button type="submit" class="btn"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn-outline" href="{{ route('cases.index') }}">Clear</a></div>
 </form>
</section>
<section class="dash-panel act-structured-panel">
 <div class="dash-panel-heading"><h2>Report register</h2><small class="muted">{{ number_format($cases->total()) }} records matching selection</small></div>
 <div class="dash-table-wrap"><table class="act-compact-table"><thead><tr><th>Reference</th><th>Report</th><th>LGA</th><th>Sector</th><th>Verification</th><th>Action</th></tr></thead><tbody>
 @forelse($cases as $case)<tr>
  <td><strong>{{ $case->reference }}</strong></td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ $case->sector?->name }}</td>
  <td><span class="act-state-chip">{{ ucwords(str_replace('_',' ',$case->verification_status)) }}</span></td>
  <td><a class="act-table-icon-link" href="{{ route('cases.show',$case) }}" aria-label="View {{ $case->reference }}" title="View report"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td>
 </tr>@empty<tr><td colspan="6" class="act-table-empty">No reports match your filters.</td></tr>@endforelse
 </tbody></table></div>
 <div class="act-panel-pagination act-pagination-bar"><span>Showing {{ $cases->firstItem()??0 }}–{{ $cases->lastItem()??0 }} of {{ number_format($cases->total()) }} reports</span>@if($cases->hasPages()){{ $cases->withQueryString()->links() }}@endif</div>
</section>
@endsection
