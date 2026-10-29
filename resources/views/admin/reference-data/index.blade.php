@extends('layouts.app')
@section('title','Reference Data | Arewa Civic Tracker')
@section('section_heading','Reference Data')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">ADMINISTRATION</span><h1>Reference Data</h1><p class="muted">Manage LGAs, report categories, communities and display labels without changing review permissions.</p></div></section>
<section class="dash-panel act-structured-panel" style="margin-bottom:20px">
<div class="dash-panel-heading"><h2>Local governments</h2><small class="muted">{{ $lgas->count() }} records. Renaming an LGA must preserve its official geography.</small></div>
<p class="muted">Existing report and focal-person assignments stay linked by LGA ID. If an official name changes, review the associated map-boundary labels.</p>
<form class="act-reference-form" method="POST" action="{{ route('admin.reference.lgas.create') }}" data-no-ajax>@csrf
<div class="field"><label for="new-lga-name">LGA Name</label><input id="new-lga-name" name="name" required maxlength="100" placeholder="Official LGA name"></div>
<div class="field"><label for="new-lga-code">Unique code</label><input id="new-lga-code" name="code" required maxlength="10" placeholder="e.g. DAM"></div>
<button class="btn" type="submit">Add LGA</button></form>
<div class="dash-table-wrap"><table class="act-compact-table"><thead><tr><th>Local government</th><th>Code</th><th>Action</th></tr></thead><tbody>
@foreach($lgas as $l)
<tr><td colspan="3"><form method="POST" action="{{ route('admin.reference.lgas.update',$l) }}" class="act-reference-editor" data-no-ajax>@csrf @method('PUT')
<input name="name" required maxlength="100" value="{{ $l->name }}" aria-label="LGA name for {{ $l->name }}">
<input name="code" required maxlength="10" value="{{ $l->code }}" aria-label="LGA code for {{ $l->name }}">
<button class="act-table-icon-link" title="Save LGA" aria-label="Save {{ $l->name }}"><i class="fa-solid fa-check"></i></button></form></td></tr>
@endforeach
</tbody></table></div></section>
<section class="dash-panel act-structured-panel" style="margin-bottom:20px">
<div class="dash-panel-heading"><h2>Communities</h2><small class="muted">{{ $communities->total() }} records</small></div>
<form class="act-reference-form" method="POST" action="{{ route('admin.reference.communities.create') }}" data-no-ajax>@csrf
<div class="field"><label>LGA</label><select name="lga_id" required><option value="">Select LGA</option>@foreach($lgas as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
<div class="field"><label>Community Name</label><input name="name" required maxlength="150" placeholder="Community name"></div><button class="btn">Add Community</button></form>
<div class="dash-table-wrap"><table class="act-compact-table"><thead><tr><th>Community</th><th>LGA</th><th>Actions</th></tr></thead><tbody>
@forelse($communities as $c)<tr><td>{{ $c->name }}</td><td>{{ $c->lga?->name }}</td><td>
<form method="POST" action="{{ route('admin.reference.communities.update',$c) }}" class="act-reference-editor" data-no-ajax>@csrf @method('PUT')
<input name="name" value="{{ $c->name }}" maxlength="150" required aria-label="Community name">
<select name="is_active" aria-label="Community status"><option value="1" @selected($c->is_active)>Active</option><option value="0" @selected(!$c->is_active)>Inactive</option></select>
<button class="act-table-icon-link" aria-label="Save community" title="Save"><i class="fa-solid fa-check"></i></button></form></td></tr>
@empty<tr><td colspan="3" class="act-table-empty">No communities yet. Free-text entry remains available.</td></tr>@endforelse
</tbody></table></div><div class="act-pagination-bar"><span>Showing {{ $communities->firstItem()??0 }}–{{ $communities->lastItem()??0 }} of {{ $communities->total() }}</span>@if($communities->hasPages()){{ $communities->withQueryString()->links() }}@endif</div></section>
<section class="dash-panel act-structured-panel" style="margin-bottom:20px">
<div class="dash-panel-heading"><h2>Report Categories</h2></div>
<form class="act-reference-form" method="POST" action="{{ route('admin.reference.categories.create') }}" data-no-ajax>@csrf
<div class="field"><label>Report Type</label><select name="type"><option value="public_project">Public Project</option><option value="governance_issue">Governance Issue</option></select></div>
<div class="field"><label>Category Name</label><input name="name" required maxlength="120" placeholder="Category name"></div><button class="btn">Add Category</button></form>
<div class="dash-table-wrap"><table class="act-compact-table"><thead><tr><th>Type</th><th>Category</th><th>Actions</th></tr></thead><tbody>
@forelse($categories as $c)<tr><td>{{ ucwords(str_replace('_',' ',$c->type)) }}</td><td>{{ $c->name }}</td><td>
<form method="POST" action="{{ route('admin.reference.categories.update',$c) }}" class="act-reference-editor" data-no-ajax>@csrf @method('PUT')
<input name="name" maxlength="120" value="{{ $c->name }}" required aria-label="Category name">
<select name="is_active" aria-label="Category status"><option value="1" @selected($c->is_active)>Active</option><option value="0" @selected(!$c->is_active)>Inactive</option></select>
<button class="act-table-icon-link" aria-label="Save category" title="Save"><i class="fa-solid fa-check"></i></button></form></td></tr>
@empty<tr><td colspan="3" class="act-table-empty">No categories yet.</td></tr>@endforelse
</tbody></table></div></section>
<section class="dash-panel act-structured-panel">
<div class="dash-panel-heading"><h2>Workflow Status Labels</h2><small class="muted">The underlying status codes and allowed transitions cannot be changed.</small></div>
<div class="dash-table-wrap"><table class="act-compact-table"><thead><tr><th>Type</th><th>Fixed Code</th><th>Display Label</th></tr></thead><tbody>
@foreach($statuses as $status)<tr><td>{{ ucfirst($status->kind) }}</td><td><code>{{ $status->code }}</code></td><td><form method="POST" action="{{ route('admin.reference.statuses.update',$status) }}" class="act-reference-editor" data-no-ajax>@csrf @method('PUT')
<input name="label" required maxlength="100" aria-label="Label for {{ $status->code }}" value="{{ $status->label }}"><button class="act-table-icon-link" title="Save" aria-label="Save status label"><i class="fa-solid fa-check"></i></button>
</form></td></tr>@endforeach
</tbody></table></div></section>
@endsection
