@extends('layouts.app')
@section('title','Data Governance & Backups | Arewa Civic Tracker')
@section('section_heading','Data Governance & Backups')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">ADMINISTRATION</span><h1>Data Governance & Backups</h1><p class="muted">Retention policies, legal holds and encrypted backup readiness. Reports and evidence are never automatically deleted.</p></div></section>
<div class="act-governance-overview">
<section class="dash-panel"><h2>Retention Policy</h2><strong>{{ $policy?->state==='approved'?'Approved':'Draft / Unapproved' }}</strong><p class="muted">{{ $policy?->approval_reference??'Policy approval reference has not been recorded.' }}</p></section>
<section class="dash-panel"><h2>Encrypted Backup</h2><strong>{{ $backup['exists']?'Available':'Not Found' }}</strong><p class="muted">@if($backup['last_at'])Latest: {{ $backup['last_at']->timezone('Africa/Lagos')->format('d M Y H:i') }} WAT · {{ $backup['count'] }} snapshot(s)
@else
No snapshot available
@endif</p></section>
<section class="dash-panel"><h2>Off-server Backup</h2><strong>{{ $offsite_configured?'Destination Set':'Not Configured' }}</strong><p class="muted">An independent mounted backup location must be supplied by an authorized operator.</p></section>
<section class="dash-panel"><h2>Legal Holds</h2><strong>{{ $holds }} reports</strong><p class="muted">Hold status is managed from each report's details.</p></section>
</div>
<section class="dash-panel act-governance-panel">
<h2>Proposed Retention Periods</h2><p class="muted">Specify periods in days; empty means not yet defined. These settings support policy review, not automatic deletion.</p>
<form method="POST" action="{{ route('admin.governance.save') }}" data-no-ajax>@csrf
<div class="act-governance-fields">
@foreach(['case_retention_days'=>'Monitoring Reports','evidence_retention_days'=>'Evidence Files','audit_retention_days'=>'Audit History'] as $key=>$label)
<div class="field"><label for="{{ $key }}">{{ $label }} (days)</label><input id="{{ $key }}" name="{{ $key }}" type="number" min="365" max="36500" placeholder="Not defined" value="{{ old($key,$policy?->$key) }}"></div>
@endforeach
</div>
<div class="field"><label for="policy_notes">Legal Basis / Policy Notes</label><textarea name="notes" id="policy_notes" maxlength="5000" rows="3">{{ old('notes',$policy?->notes) }}</textarea></div>
<div class="act-governance-actions"><button class="btn" type="submit">Save Draft Policy</button><span class="muted">Editing an approved policy returns it to Draft.</span></div>
</form>
@if($policy && $policy->state!=='approved')
<form method="POST" action="{{ route('admin.governance.approve') }}" data-no-ajax class="act-governance-approval">@csrf
<h3>Record RAWYOD Approval</h3>
<div class="field"><label for="approval_reference">Authorized Approval Reference</label><input id="approval_reference" name="approval_reference" required minlength="6" maxlength="160" placeholder="Document / decision reference"></div>
<label class="act-governance-confirm"><input type="checkbox" required name="confirm_no_purge" value="1"> I confirm this policy is authorized and understand that approval never deletes records automatically.</label>
<button class="btn-outline" type="submit">Record Approval</button></form>
@endif
</section>
<section class="dash-panel act-governance-panel"><h2>Retention Review Preview</h2><p class="muted">Older records outside legal holds, shown for governance review only. No deletion operation is attached.</p>
<div class="dash-table-wrap"><table><thead><tr><th>Dataset</th><th>Potentially Due</th><th>Current Action</th></tr></thead><tbody>
<tr><td>Monitoring Reports</td><td>{{ $preview['cases']??'Not defined' }}</td><td>Review only</td></tr>
<tr><td>Evidence Files</td><td>{{ $preview['evidence']??'Not defined' }}</td><td>Review only</td></tr>
<tr><td>Audit Events</td><td>{{ $preview['audit']??'Not defined' }}</td><td>Retain history</td></tr>
</tbody></table></div></section>
<section class="dash-panel act-governance-panel"><h2>Backup and Recovery</h2><p>Encrypted backups are scheduled daily at 2:15 AM WAT. Isolated recovery is available through the secure command line. Off-server copying remains disabled without a separately configured destination.</p><p class="muted">Cloudflare R2 has not been activated or changed.</p></section>
@endsection
