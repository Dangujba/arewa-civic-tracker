@extends('layouts.app')
@section('title','Administrative Activity Audit | Arewa Civic Tracker')
@section('section_heading','Administrative Audit')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">ORGANIZATIONAL ACCOUNTABILITY</span><h1>Administrative activity audit</h1><p class="muted">Changes to staff accounts, LGA coverage, reviewer assignments and review deadline settings.</p></div><a href="{{ route('admin.operations.index') }}" class="btn-outline">Field operations</a></section>
<section class="dash-panel"><div class="dash-table-wrap"><table><thead><tr><th>Date</th><th>Actor</th><th>Action</th><th>Record</th><th>Details</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->created_at?->format('d M Y H:i') }}</td><td>{{ $log->actor?->name??'Unknown' }}</td><td>{{ str_replace(['.','_'],' ',$log->action) }}</td><td>{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : 'Organization' }}</td><td><code>{{ \Illuminate\Support\Str::limit(json_encode($log->details,JSON_UNESCAPED_SLASHES),200) }}</code></td></tr>@empty<tr><td colspan="5">No administrative changes recorded yet.</td></tr>@endforelse
</tbody></table></div>{{ $logs->links() }}</section>
@endsection
