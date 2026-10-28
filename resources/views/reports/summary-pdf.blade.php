<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
@page { margin: 27px 33px 32px; }
* { box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #21372a; line-height: 1.45; }
h1,h2,h3,p { margin: 0; }
h1 { font-size: 21px; color: #164b38; }
h2 { font-size: 14px; color: #164b38; margin: 16px 0 5px; }
h3 { font-size: 10px; color: #1d513b; }
.brand-table { width: 100%; border-bottom: 2px solid #164b38; padding-bottom: 12px; }
.brand-table td { border: none; vertical-align: middle; }
.brand-table .logo-cell { width: 54px; }
.brand-table img { width: 44px; height: 44px; }
.eyebrow { color: #a17c3e; letter-spacing: 1.4px; font-weight: bold; font-size: 8px; text-transform: uppercase; }
.subtitle { color: #597163; font-size: 9px; margin-top: 4px; }
.issue { color: #567366; text-align: right; font-size: 8px; }
.intro { padding: 14px 0 8px; }
.intro p { color: #5c7264; margin-top: 3px; }
.scope { border-top: 1px solid #d8e3da; border-bottom: 1px solid #d8e3da; padding: 9px 0; margin: 5px 0 13px; }
.scope b { color: #244d37; }
.scope em { font-style: normal; color: #50685a; margin-right: 15px; }
.metrics { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 7px 0 16px; }
.metrics td { background: #f2f6f1; padding: 11px 13px; border-left: 4px solid #fff; }
.metrics td:first-child { border-left: none; }
.metrics small { display: block; color: #587163; font-size: 8px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
.metrics strong { display: block; color: #164b38; font-size: 19px; line-height: 1; }
.section-note { color: #667e6e; font-size: 8px; margin-bottom: 10px; }
.figures { width: 100%; border-collapse: collapse; table-layout: fixed; }
.figures td { width: 50%; vertical-align: top; padding: 0 7px 0 0; }
.figures td + td { padding: 0 0 0 7px; }
.figure-title { font-size: 10px; font-weight: bold; margin-bottom: 5px; color: #274c39; }
.figure { width: 100%; border: 1px solid #dce6df; padding: 6px 3px; }
.figure img { display: block; width: 100%; }
.figure-caption { margin-top: 4px; color: #667a6c; font-size: 7px; }
.observation { margin-top: 16px; border-left: 3px solid #b79a5b; padding: 8px 10px; background: #faf9f4; }
.observation b { color: #254e36; }
.page-break { page-break-before: always; }
.analytics-summary { width: 100%; margin-top: 16px; border-collapse: collapse; }
.analytics-summary td { vertical-align: top; padding: 7px; border-bottom: 1px solid #e3ebe5; }
.analytics-summary th { text-align: left; padding: 7px; color: #164b38; border-bottom: 1px solid #bed1c3; }
.analytics-summary .number { text-align: right; font-weight: bold; }
.register { width: 100%; border-collapse: collapse; table-layout: fixed; }
.register thead { display: table-header-group; }
.register tr { page-break-inside: avoid; }
.register th { background: #174b37; color: #fff; font-size: 7.6px; text-align: left; padding: 7px 5px; }
.register td { border-bottom: 1px solid #e0e8e2; vertical-align: top; font-size: 7.4px; padding: 7px 5px; overflow-wrap: break-word; }
.register tr:nth-child(even) td { background: #f7faf7; }
.register .ref { color: #15573e; font-weight: bold; }
.register .muted { color: #688072; }
.footnote { margin-top: 11px; padding-top: 8px; border-top: 1px solid #dce5df; color: #6e8172; font-size: 7px; }
</style>
</head>
<body>
<table class="brand-table"><tr>
<td class="logo-cell">@if($logo)<img src="{{ $logo }}" alt="">@endif</td>
<td>
<div class="eyebrow">RAWYOD · VOICES TO VOTES</div>
<h1>Arewa Civic Tracker</h1>
<div class="subtitle">Management intelligence report | Civic monitoring and public project oversight</div>
</td>
<td class="issue">ISSUED<br><b>{{ now('Africa/Lagos')->format('d M Y') }}</b><br>{{ now('Africa/Lagos')->format('H:i') }} WAT</td>
</tr></table>

<div class="intro">
  <h2>Executive overview</h2>
  <p>Verified reporting metrics are drawn from authorized field records matching the selected criteria. Counts represent submissions, not independently confirmed project completion.</p>
</div>
<div class="scope"><b>REPORT SCOPE:</b>
@if(count($applied))
  @foreach($applied as $name => $value)<em>{{ $name }}: {{ $value }}</em>@endforeach
@else
  <em>All accessible records; no filters applied</em>
@endif
</div>

<table class="metrics"><tr>
<td><small>Matching reports</small><strong>{{ number_format($total) }}</strong></td>
<td><small>Project observations</small><strong>{{ number_format((int)($types->projects ?? 0)) }}</strong></td>
<td><small>Approved reports</small><strong>{{ number_format((int)($types->approved ?? 0)) }}</strong></td>
<td><small>Awaiting review</small><strong>{{ number_format((int)($types->pending ?? 0)) }}</strong></td>
</tr></table>

<h2>Reporting activity & review outcomes</h2>
<p class="section-note">The monthly trend uses observation dates over the last 12 months. Status distribution includes every matching report regardless of date.</p>
<table class="figures"><tr>
<td><div class="figure-title">Monthly report observations</div><div class="figure"><img src="{{ $figures['trend'] }}" alt="Monthly observation trend"></div><div class="figure-caption">One point per month, using filtered observation dates</div></td>
<td><div class="figure-title">Verification status distribution</div><div class="figure"><img src="{{ $figures['status'] }}" alt="Verification status doughnut chart"></div><div class="figure-caption">Share of total matching records by review status</div></td>
</tr></table>

<div class="observation"><b>Management reading:</b>
@if($total > 0)
{{ number_format((int)($types->approved ?? 0)) }} of {{ number_format($total) }} reports ({{ number_format(100 * (int)($types->approved ?? 0) / $total, 1) }}%) have approved status. {{ number_format((int)($types->pending ?? 0)) }} report(s) remain in submitted, under-review or clarification stages.
@else
No records match the selected criteria. All figures are shown as empty rather than substituting illustrative data.
@endif
</div>
<p class="footnote">Source: Arewa Civic Tracker, access-controlled reporting database. This document is for authorized RAWYOD review; inspect original records and supporting evidence before public release.</p>

<div class="page-break"></div>
<div class="eyebrow">GEOGRAPHIC & SECTOR ANALYSIS</div>
<h1 style="font-size:18px;margin-top:5px">Where reports are concentrated</h1>
<p class="section-note" style="margin:7px 0 18px">Rankings reflect submitted field observations, not population-adjusted incidence or public-service performance ratings. Top eight categories shown when applicable.</p>
<table class="figures"><tr>
<td><div class="figure-title">Reports by local government</div><div class="figure"><img src="{{ $figures['lga'] }}" alt="Reports by local government horizontal bar chart"></div></td>
<td><div class="figure-title">Reports by monitoring sector</div><div class="figure"><img src="{{ $figures['sector'] }}" alt="Reports by sector horizontal bar chart"></div></td>
</tr></table>

<h2 style="margin-top:22px">Review distribution</h2>
<table class="analytics-summary"><thead><tr><th>Verification category</th><th class="number">Reports</th><th class="number">Share of matching reports</th></tr></thead><tbody>
@forelse($summary as $status=>$count)
<tr><td>{{ ucwords(str_replace('_',' ', $status)) }}</td><td class="number">{{ number_format((int)$count) }}</td><td class="number">{{ $total ? number_format(100 * $count / $total, 1).'%' : '0.0%' }}</td></tr>
@empty<tr><td colspan="3">No matching submissions.</td></tr>@endforelse
</tbody></table>
<p class="footnote">Interpretation: An approved status records workflow review, not proof of completed infrastructure. The charts exclude no matching records and do not infer or fabricate geographic coordinates.</p>

<div class="page-break"></div>
<div class="eyebrow">MANAGEMENT REGISTER</div>
<h1 style="font-size:18px;margin:5px 0">Underlying monitoring records</h1>
<p class="section-note">Showing {{ number_format($rows->count()) }} of {{ number_format($total) }} matching reports, maximum 150 in this PDF. Use the filtered Excel export for a larger detailed register.</p>
<table class="register"><thead><tr>
<th style="width:13%">Reference</th><th style="width:24%">Observation title</th><th style="width:13%">LGA</th><th style="width:15%">Sector</th><th style="width:12%">Type</th><th style="width:13%">Review status</th><th style="width:10%">Observed</th>
</tr></thead><tbody>
@forelse($rows as $case)
<tr>
<td class="ref">{{ $case->reference }}</td>
<td>{{ \Illuminate\Support\Str::limit($case->title, 82) }}</td>
<td>{{ $case->lga?->name ?? 'Unassigned' }}</td>
<td>{{ $case->sector?->name ?? 'Other' }}</td>
<td>{{ ucwords(str_replace('_',' ', $case->type)) }}</td>
<td>{{ ucwords(str_replace('_',' ', $case->verification_status)) }}</td>
<td>{{ $case->observation_date?->format('d M Y') }}</td>
</tr>
@empty<tr><td colspan="7" class="muted">There are no matching monitoring reports for this selection.</td></tr>@endforelse
</tbody></table>
<p class="footnote">Confidential RAWYOD management document. Evidence attachments and personally sensitive content are not reproduced in this export. Record details remain available to authorized users in Arewa Civic Tracker.</p>
</body></html>
