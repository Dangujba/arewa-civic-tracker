@extends('layouts.app')
@section('title','Field Operations | Arewa Civic Tracker')
@section('section_heading','Field Operations')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">ADMINISTRATIVE OVERSIGHT</span><h1>Field operations</h1><p class="muted">Actual monitoring coverage, focal activity, reviewer workload and overdue assignments.</p></div><a class="btn-outline" href="{{ route('admin.operations.audit') }}">Activity audit</a></section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>LGA coverage</h2><a href="{{ route('admin.users.index') }}" class="dash-text-link">Manage assignments</a></div>
<div class="dash-table-wrap"><table><thead><tr><th>Local government</th><th>Active focal persons</th><th>Coverage</th></tr></thead><tbody>
@foreach($lgas as $lga)<tr><td>{{ $lga->name }}</td><td>{{ $lga->focal_count }}</td><td>{{ $lga->focal_count>0?'Assigned':'Unassigned' }}</td></tr>@endforeach
</tbody></table></div></section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>Focal person performance</h2></div>
<div class="dash-table-wrap"><table><thead><tr><th>Officer</th><th>Assigned LGAs</th><th>Reports</th><th>Approved</th><th>Clarifications</th></tr></thead><tbody>
@forelse($focals as $officer)<tr><td>{{ $officer->name }} @unless($officer->is_active)<span class="muted">(Inactive)</span>@endunless</td><td>{{ $officer->assignedLgas->pluck('name')->implode(', ') ?: 'Not assigned' }}</td><td>{{ $officer->reports_count }}</td><td>{{ $officer->approved_reports_count }}</td><td>{{ $officer->clarification_reports_count }}</td></tr>@empty<tr><td colspan="5">No focal persons yet.</td></tr>@endforelse
</tbody></table></div></section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>MEAL reviewer workload</h2></div>
<div class="dash-table-wrap"><table><thead><tr><th>Reviewer</th><th>Assigned total</th><th>Active</th><th>Overdue</th></tr></thead><tbody>
@forelse($reviewers as $reviewer)<tr><td>{{ $reviewer->name }} @unless($reviewer->is_active)<span class="muted">(Inactive)</span>@endunless</td><td>{{ $reviewer->assigned_reviews_count }}</td><td>{{ $reviewer->pending_reviews_count }}</td><td>{{ $reviewer->overdue_reviews_count }}</td></tr>@empty<tr><td colspan="4">No MEAL officers yet.</td></tr>@endforelse
</tbody></table></div></section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>Overdue reviews</h2><a href="{{ route('cases.queue') }}" class="dash-text-link">Verification queue</a></div>
<div class="dash-table-wrap"><table><thead><tr><th>Reference</th><th>LGA</th><th>Reviewer</th><th>Due</th></tr></thead><tbody>
@forelse($overdue as $report)<tr><td><a href="{{ route('cases.show',$report) }}">{{ $report->reference }}</a></td><td>{{ $report->lga?->name }}</td><td>{{ $report->reviewer?->name??'Unassigned' }}</td><td>{{ $report->review_due_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="4">No overdue assigned reviews.</td></tr>@endforelse
</tbody></table></div></section>
<section class="dash-panel"><div class="dash-panel-heading"><h2>Review deadline policy</h2></div><p class="muted">The default applies only to newly assigned reviewers, without altering previous deadlines.</p>
<form method="POST" action="{{ route('admin.operations.settings') }}">@csrf
<div class="field"><label for="review-sla">Default review period (days)</label><input id="review-sla" name="review_sla_days" type="number" min="1" max="30" required value="{{ old('review_sla_days',$settings?->review_sla_days??7) }}"></div>
<div class="field"><label><input type="hidden" name="require_mfa_admin" value="0"><input type="checkbox" name="require_mfa_admin" value="1" @checked(old('require_mfa_admin',$settings?->require_mfa_admin??false))> Require 2FA for RAWYOD Administrators</label></div>
<div class="field"><label><input type="hidden" name="require_mfa_meal" value="0"><input type="checkbox" name="require_mfa_meal" value="1" @checked(old('require_mfa_meal',$settings?->require_mfa_meal??false))> Require 2FA for MEAL Reviewers</label></div>
<p class="muted">When enabled, unenrolled users must activate their authenticator in Security Settings before opening their workspaces. At least one administrator must be enrolled before administrator enforcement is enabled.</p>
<button type="submit">Save organizational policy</button></form></section>
@endsection
