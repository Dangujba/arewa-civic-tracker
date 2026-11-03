<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $case->reference }} · Arewa Civic Tracker</title>
<style>
@page{margin:15mm 17mm 17mm}
*{box-sizing:border-box}
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9.5px;color:#171717;line-height:1.5;background:white;margin:0}
.top-border{height:7px;background:#9E5D37}
.letterhead{width:100%;border-bottom:2px solid #9E5D37;padding:10px 0 9px;table-layout:fixed}
.letterhead td{vertical-align:middle}
.logo{width:61px;height:61px}
.org{font-weight:800;font-size:16px;color:#9E5D37;letter-spacing:.2px}
.tagline{font-size:8px;color:#7d855e;letter-spacing:1.5px}
.doc-ref{font-size:9px;color:#647569;text-align:right}
.report-title{font-size:19px;line-height:1.3;color:#171717;margin:12px 0 4px}
.subtitle{font-size:10px;color:#6f7c72;margin-bottom:9px}
.badges span{display:inline-block;border:1px solid #E0C8B7;padding:5px 9px;margin-right:8px;background:#F6EBE3;color:#773F24;font-weight:bold;font-size:9px}
.summary{border-collapse:collapse;width:100%;margin:10px 0 11px}
.summary td{padding:7px;border:1px solid #d7e2d9;vertical-align:top;width:50%}
.summary label{display:block;font-size:8px;color:#708274;margin-bottom:3px;text-transform:uppercase}
.summary strong{font-size:10px;font-weight:700}
.block{margin:10px 0;page-break-inside:avoid}
.block h2{font-size:12px;color:#773F24;margin:0 0 8px;padding:0 0 6px;border-bottom:1px solid #ccd9cf}
.rich p{margin:0 0 8px}.rich ul,.rich ol{padding-left:20px}
.muted{color:#7b8e7e}
.panel{padding:8px;border:1px solid #dce4dd;white-space:pre-line}
.evidence-grid{width:100%;border-collapse:separate;border-spacing:8px}
.evidence-grid td{border:1px solid #dce4dd;width:50%;padding:9px;vertical-align:top}
.evidence-grid img{max-width:190px;max-height:135px;display:block;margin-bottom:8px}
.small{font-size:8px;color:#768579;overflow-wrap:anywhere}
.footer{border-top:1px solid #afc2b3;padding-top:9px;font-size:8px;color:#667c6d;margin-top:12px}
.private-note{font-size:8px;color:#63796a}
@media screen{
body{max-width:850px;margin:22px auto;padding:0 25px;box-shadow:0 0 0 1px #dce4dc}
.toolbar{display:flex;gap:10px;padding:15px 0;background:white}
.toolbar button,.toolbar a{border:1px solid #9E5D37;background:#9E5D37;color:white;padding:10px 17px;cursor:pointer;text-decoration:none;font:700 12px Arial}
.toolbar a.secondary{background:white;color:#9E5D37}
}
@media print{.toolbar{display:none!important};body{margin:0;box-shadow:none;padding:0}}
</style>
</head>
<body>
@if($printMode)
<div class="toolbar"><button type="button" onclick="window.print()">Print Report</button>
<a href="{{ route('cases.pdf',$case) }}">Export PDF</a>
<a class="secondary" href="{{ route('cases.show',$case) }}">Back to Report</a></div>
@endif
<div class="top-border"></div>
<table class="letterhead"><tr>
<td style="width:70px">@if($logo)<img class="logo" src="{{ $logo }}" alt="">@endif</td>
<td><div class="org">AREWA CIVIC TRACKER</div>
<div>RAWYOD · VOICES TO VOTES</div><div class="tagline">CIVIC MONITORING &amp; ACCOUNTABILITY</div></td>
<td class="doc-ref" style="width:180px"><strong>MONITORING REPORT</strong><br>{{ $case->reference }}<br>Generated {{ $generatedAt->format('d M Y, h:i A') }} WAT</td>
</tr></table>
<h1 class="report-title">{{ $case->title }}</h1>
<div class="subtitle">{{ $case->lga?->name }} · {{ $case->ward ?: 'Ward not recorded' }} · {{ $case->community }}</div>
<div class="badges"><span>{{ \App\Models\WorkflowStatusLabel::label('verification',$case->verification_status) }}</span>
<span>{{ \App\Models\WorkflowStatusLabel::label('progress',$case->progress_status) }}</span>
<span>{{ $case->type==='public_project'?'Public Project':'Governance Issue' }}</span></div>
<table class="summary">
<tr>
<td><label>Sector</label><strong>{{ $case->sector?->name ?? 'Not specified' }}</strong></td>
<td><label>Category</label><strong>{{ $case->category?->name ?? 'Not specified' }}</strong></td>
</tr>
<tr>
<td><label>Date and time observed</label><strong>{{ $case->observation_date?->format('d M Y') ?? 'Not recorded' }}
· {{ $case->observation_time ? \Illuminate\Support\Carbon::parse($case->observation_time)->format('h:i A') : 'Time not recorded' }}</strong></td>
<td><label>Last updated</label><strong>{{ $case->updated_at?->format('d M Y, h:i A') }} WAT</strong></td>
</tr>
<tr>
<td><label>Local government / Ward</label><strong>{{ $case->lga?->name }} / {{ $case->ward ?: 'Not specified' }}</strong></td>
<td><label>Community</label><strong>{{ $case->community }}</strong></td>
</tr>
<tr>
<td><label>Reported by</label><strong>{{ $case->reporter?->name ?? 'Not available' }}</strong></td>
<td><label>Assigned MEAL reviewer</label><strong>{{ $case->reviewer?->name ?? 'Unassigned' }}</strong></td>
</tr>
</table>
<section class="block"><h2>Description</h2><div class="rich">{!! $renderedDescription !!}</div></section>
<section class="block"><h2>Field Observations</h2><div class="panel">{{ $case->observations ?: 'None recorded' }}</div></section>
<section class="block"><h2>Stakeholders / Duty Bearers</h2><div class="panel">{{ $case->duty_bearers ?: 'None recorded' }}</div></section>
<section class="block"><h2>Supporting Evidence ({{ count($attachments) }})</h2>
@if(count($attachments))
<table class="evidence-grid">
@foreach(array_chunk($attachments,2) as $group)
<tr>@foreach($group as $item)<td>
@if($item['image'])<img src="{{ $item['image'] }}" alt="">@endif
<strong>{{ $item['name'] }}</strong><div class="small">{{ number_format($item['size']/1024) }} KB · {{ $item['mime'] }}</div>
</td>@endforeach
@if(count($group)===1)<td></td>@endif</tr>
@endforeach
</table>
@else<p class="muted">No evidence uploaded.</p>@endif
</section>
@if($case->latitude!==null && $case->longitude!==null)
<section class="block"><h2>Location Information (restricted)</h2>
<div class="panel">Coordinates: {{ $case->latitude }}, {{ $case->longitude }}</div></section>
@endif
<section class="block"><h2>Review and Audit Timeline</h2>
@forelse($case->events->take(16) as $event)
<div><strong>{{ ucwords(str_replace('_',' ',$event->event_type)) }}</strong> ·
{{ $event->created_at?->format('d M Y, h:i A') }} · {{ $event->actor?->name ?? 'System' }}
@if($event->to_status) · {{ $event->to_status }}@endif</div>
@empty<p class="muted">No recorded review actions.</p>@endforelse
</section>
<div class="footer"><strong>RAWYOD — Arewa Civic Tracker</strong>
<div class="private-note">Restricted document for authorized project monitoring. Evidence and staff information must not be distributed through public transparency without separate review and approval.</div></div>
</body></html>