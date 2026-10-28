<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Arewa Civic Tracker | Stakeholder Monitoring Report</title>
<style>
@page{margin:28px 34px 32px}
*{box-sizing:border-box}
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9px;color:#253e30;line-height:1.55}
h1,h2,h3,p{margin:0}
h1{font-size:23px;line-height:1.2;letter-spacing:-.5px;color:#124b35}
h2{font-size:14px;color:#174e37;margin-bottom:8px}
h3{font-size:10px;color:#184f38;margin-bottom:7px}
.topline{height:7px;background:#15543a;border-bottom:2px solid #c6a052;margin:-28px -34px 20px}
.header-table{width:100%;border-collapse:collapse;margin-bottom:14px}
.header-table td{border:0;vertical-align:middle}
.header-table .brand{width:54px}
.brand img{width:44px;height:44px}
.eyebrow{color:#97752d;font-size:8px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px}
.muted{color:#6d8072}
.subtitle{margin-top:7px;color:#698073;font-size:10px}
.issue{font-size:9px;text-align:right;color:#557462}
.section{margin-top:17px}
.section-heading{border-bottom:1px solid #cedbd1;padding-bottom:7px;margin-bottom:10px}
.lead{font-size:10px;color:#364f3f;line-height:1.7}
.metric{width:100%;border-collapse:separate;border-spacing:8px 0;margin:14px -8px 15px}
.metric td{width:25%;border:1px solid #dce8de;background:#f6f9f6;padding:12px 13px}
.metric .value{font-size:22px;color:#174f38;font-weight:bold;line-height:1.2}
.metric .label{font-size:9px;color:#69816f;margin-top:6px}
.scope{background:#f4f8f4;border-left:4px solid #c59d4f;padding:11px 14px;line-height:1.8}
.scope strong{color:#1d563c}
.note{background:#fbf9f2;border:1px solid #e5dbc0;padding:10px 12px;color:#6c6145;margin:10px 0}
.insight-table{border-collapse:collapse;width:100%;margin:10px 0}
.insight-table td{border:1px solid #dce7df;width:33%;vertical-align:top;padding:12px}
.insight-table strong{display:block;color:#1b5c3d;font-size:12px;margin-bottom:4px}
.figure{border:1px solid #dce8df;padding:10px 12px;margin-bottom:12px;page-break-inside:avoid}
.figure img{width:100%;height:auto;display:block}
.figure .caption{margin-top:7px;color:#6c8170;font-size:8px}
.figure-title{font-size:10px;color:#1a533a;font-weight:bold;border-bottom:1px solid #eef2ee;padding-bottom:6px;margin-bottom:8px}
.figure-small{width:48%;display:inline-block;vertical-align:top}
.figure-small img{width:100%}
.split td{vertical-align:top;width:50%;border:0;padding:5px}
.pagebreak{page-break-before:always}
.report-table{border-collapse:collapse;width:100%;table-layout:fixed;font-size:8px}
.report-table th{background:#18533b;color:white;padding:8px 5px;text-align:left;border:0}
.report-table td{border-bottom:1px solid #dbe6dd;padding:7px 5px;word-wrap:break-word;vertical-align:top}
.report-table tr:nth-child(even) td{background:#f8faf8}
.report-table thead{display:table-header-group}
.report-table tr{page-break-inside:avoid}
.footer{border-top:1px solid #d3e1d7;margin-top:16px;padding-top:8px;color:#718174;font-size:8px}
.no-data{padding:24px;background:#f7faf8;color:#577164;border:1px solid #dce6df;font-size:11px}
.cover-separator{height:2px;background:#d4b269;width:110px;margin-top:13px}
</style>
</head>
<body>
<div class="topline"></div>
<table class="header-table"><tr>
<td class="brand">@if($brand)<img src="{{ $brand }}" alt="Arewa Civic Tracker">@endif</td>
<td><div class="eyebrow">RAWYOD · Voices to Votes</div><div style="font-size:14px;font-weight:bold;color:#17563e">AREWA CIVIC TRACKER</div><div class="muted">Field Monitoring & Civic Accountability</div></td>
<td class="issue"><strong>Management Report</strong><div>Generated {{ $generated->format('d M Y, H:i') }}</div><div>Restricted circulation</div></td>
</tr></table>

<div class="eyebrow">Stakeholder monitoring brief</div>
<h1>Field Monitoring & Civic Accountability Report</h1>
<div class="subtitle">Evidence-based overview of the observations visible to the authorized reporting account.</div>
<div class="cover-separator"></div>

<table class="metric"><tr>
<td><div class="value">{{ number_format($total) }}</div><div class="label">Matching Observations</div></td>
<td><div class="value">{{ number_format($projects) }}</div><div class="label">Public Project Reports</div></td>
<td><div class="value">{{ number_format($approved) }}</div><div class="label">Approved Reports</div></td>
<td><div class="value">{{ number_format($pending) }}</div><div class="label">Awaiting Review / Clarification</div></td>
</tr></table>

<div class="scope"><strong>Report scope:</strong>
@if($scope['from'] || $scope['to']) Observed {{ $scope['from'] ?: 'earliest' }} to {{ $scope['to'] ?: 'latest' }}. @else All observation dates. @endif
<strong>LGA:</strong> {{ $scope['lga'] ?: 'All accessible LGAs' }}.
<strong>Sector:</strong> {{ $scope['sector'] ?: 'All sectors' }}.
<strong>Review status:</strong> {{ $scope['status'] ? ucwords(str_replace('_',' ',$scope['status'])) : 'All' }}.
<strong>Type:</strong> {{ $scope['type'] ? ucwords(str_replace('_',' ',$scope['type'])) : 'All' }}.
@if($scope['search']) <strong>Search:</strong> {{ $scope['search'] }}. @endif
</div>

<div class="section">
<h2 class="section-heading">Executive Summary</h2>
@if($total > 0)
<p class="lead">The selected dataset contains <strong>{{ number_format($total) }} field observations</strong>,
including {{ number_format($projects) }} public project reports and {{ number_format($issues) }} governance issue reports.
{{ number_format($approved) }} reports are marked approved ({{ number_format(100*$approved/max($total,1),1) }}%),
while {{ number_format($pending) }} remain submitted, under review or awaiting clarification.
These are record statuses, not independent assessments of project delivery.</p>
<table class="insight-table"><tr>
<td><strong>{{ count($lgaCounts) }} LGAs</strong>Represented among the filtered observations</td>
<td><strong>{{ array_key_first($lgaCounts) ?: 'No LGA reported' }}</strong>Highest recorded LGA count@if($lgaCounts) {{ number_format((int) reset($lgaCounts)) }}@endif</td>
<td><strong>{{ array_key_first($sectorCounts) ?: 'No sector reported' }}</strong>Highest recorded sector count@if($sectorCounts) {{ number_format((int) reset($sectorCounts)) }}@endif</td>
</tr></table>
@else
<div class="no-data">No field observations match the selected filters. The figures below are intentionally empty. No locations, activities or outcomes have been inferred.</div>
@endif
</div>

@if($total > 0)
<div class="section">
<h2 class="section-heading">Reporting Activity & Verification</h2>
@if(!empty($images['trend']))
<div class="figure"><div class="figure-title">Figure 1 · Monthly Observations (Last 12 Months)</div>
<img src="{{ $images['trend'] }}" alt="Monthly reporting volume">
<div class="caption">Count of observations by recorded observation date. Figures reflect the selected filters.</div></div>
@endif
@if(!empty($images['verification']))
<div class="figure"><div class="figure-title">Figure 2 · Verification Outcomes</div>
<img src="{{ $images['verification'] }}" alt="Reports by verification status">
<div class="caption">Submitted and under-review records must not be represented as verified findings.</div></div>
@endif
</div>
@endif

<div class="pagebreak"></div>
<div class="eyebrow">Area and sector intelligence</div><h1>Geographic & Sector Distribution</h1>
<p class="subtitle">Comparative distribution across the accessible records and selected filters.</p>
<div class="section">
@if($total > 0)
@if(!empty($images['lga']))
<div class="figure"><div class="figure-title">Figure 3 · Local Government Area Comparisons (Top 8)</div>
<img src="{{ $images['lga'] }}" alt="Reports by LGA"><div class="caption">Counts represent recorded observations, not population-adjusted incidence.</div></div>
@endif
@if(!empty($images['sector']))
<div class="figure"><div class="figure-title">Figure 4 · Sector Comparisons (Top 8)</div>
<img src="{{ $images['sector'] }}" alt="Reports by monitoring sector"><div class="caption">Other sectors may exist outside the eight largest categories shown.</div></div>
@endif
@else <div class="no-data">No geographic or sector comparisons are available for the selected dataset.</div> @endif
</div>

<div class="pagebreak"></div>
<div class="eyebrow">Monitoring status</div><h1>Progress & Follow-up Context</h1>
<p class="subtitle">Observed progress values recorded by field reporters; validation still depends on the verification workflow.</p>
<div class="section">
@if($total>0 && !empty($images['progress']))
<div class="figure"><div class="figure-title">Figure 5 · Reported Project / Issue Progress</div>
<img src="{{ $images['progress'] }}" alt="Observation progress distribution"><div class="caption">Progress categories are not independently verified physical-completion percentages.</div></div>
@else <div class="no-data">No progress observations are available in this selection.</div> @endif
<div class="note"><strong>Interpretation:</strong> This report summarizes submitted monitoring records. Approval status indicates the application's review state, not certification of expenditure, procurement compliance or completed infrastructure. For evidence photographs and attachments, open the authenticated case record.</div>
</div>

<div class="pagebreak"></div>
<div class="eyebrow">Supporting record register</div><h1>Selected Field Observations</h1>
<p class="subtitle">Showing {{ number_format($rows->count()) }} of {{ number_format($total) }} matching records (PDF appendix limited to 150). Use Excel export for the complete filtered register where within the export limit.</p>
<div class="section">
<table class="report-table"><thead><tr>
<th style="width:12%">Reference</th><th style="width:26%">Observation</th><th style="width:12%">LGA</th><th style="width:12%">Sector</th><th style="width:14%">Type</th><th style="width:14%">Verification</th><th style="width:10%">Date</th>
</tr></thead><tbody>
@forelse($rows as $case)
<tr><td>{{ $case->reference }}</td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ $case->sector?->name }}</td><td>{{ ucwords(str_replace('_',' ',$case->type)) }}</td><td>{{ ucwords(str_replace('_',' ',$case->verification_status)) }}</td><td>{{ $case->observation_date?->format('d M Y') }}</td></tr>
@empty<tr><td colspan="7">No records match the selected criteria.</td></tr>
@endforelse
</tbody></table>
</div>
<div class="footer">Generated by Arewa Civic Tracker for RAWYOD · Restricted management distribution · Filtered access is enforced server-side · Source: authorized field monitoring records</div>
</body></html>
