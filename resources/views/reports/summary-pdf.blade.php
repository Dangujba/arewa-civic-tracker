<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:32px}body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;color:#203529}h1{font-size:22px;color:#17553d;margin-bottom:2px}.meta{color:#65776a;font-size:10px}.summary{padding:12px;border:1px solid #c9d9cf;margin:18px 0}.summary b{margin-right:12px}table{width:100%;border-collapse:collapse}th,td{padding:7px 6px;border-bottom:1px solid #d7e4dc;text-align:left;vertical-align:top;word-wrap:break-word}th{color:#fff;background:#17543c;font-size:9px}.footer{margin-top:17px;font-size:9px;color:#789083}
</style></head><body>
<h1>Arewa Civic Tracker</h1><div class="meta">RAWYOD · VOICES TO VOTES · Management monitoring summary · Generated {{ now()->format('d M Y H:i') }}</div>
<div class="summary"><b>Matching reports: {{ $total }}</b> @foreach($summary as $status=>$count)<b>{{ ucwords(str_replace('_',' ',$status)) }}: {{ $count }}</b>@endforeach</div>
<table><thead><tr><th>Reference</th><th>Title</th><th>LGA</th><th>Sector</th><th>Type</th><th>Review status</th><th>Observed</th></tr></thead><tbody>
@forelse($rows as $case)<tr><td>{{ $case->reference }}</td><td>{{ $case->title }}</td><td>{{ $case->lga?->name }}</td><td>{{ $case->sector?->name }}</td><td>{{ $case->type }}</td><td>{{ str_replace('_',' ',$case->verification_status) }}</td><td>{{ $case->observation_date?->format('Y-m-d') }}</td></tr>
@empty<tr><td colspan="7">No records match the selected criteria.</td></tr>@endforelse
</tbody></table>
<div class="footer">Confidential management report. Only the first {{ $rows->count() }} matching records are listed (maximum 150). Reports with supporting evidence should be verified in the authenticated Tracker.</div>
</body></html>
