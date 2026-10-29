@extends('layouts.app')
@section('title','Reporting Sectors | Arewa Civic Tracker')
@section('section_heading','Reporting Sectors')
@section('content')
<section class="dash-heading act-sector-heading"><div><span class="eyebrow">RAWYOD ADMINISTRATION</span><h1>Reporting sectors</h1><p class="muted">Manage the categories used to classify field reports.</p></div></section>
<section class="card act-sector-create"><form method="POST" action="{{ route('admin.sectors.store') }}">@csrf
 <div class="field"><label for="new-sector-name">Sector name</label><input id="new-sector-name" type="text" name="name" maxlength="120" placeholder="e.g. Water and Sanitation" required></div>
 <button type="submit" class="btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add sector</button>
</form></section>
<section class="dash-panel act-structured-panel">
 <div class="dash-panel-heading"><h2>Monitoring sectors</h2></div>
 <div class="dash-table-wrap"><table class="act-compact-table act-sector-table"><thead><tr><th>Sector</th><th>Manage</th></tr></thead><tbody>
 @forelse($sectors as $sector)<tr><td><strong>{{ $sector->name }}</strong></td><td>
  <form method="POST" action="{{ route('admin.sectors.update',$sector) }}" class="act-sector-editor" data-no-ajax>@csrf @method('PUT')
    <input name="name" aria-label="Sector name: {{ $sector->name }}" value="{{ $sector->name }}" maxlength="120" required>
    <select name="is_active" aria-label="Status for {{ $sector->name }}"><option value="1" @selected($sector->is_active)>Active</option><option value="0" @selected(!$sector->is_active)>Inactive</option></select>
    <button type="submit" class="btn">Save</button>
  </form>
 </td></tr>
 @empty<tr><td colspan="2" class="act-table-empty">No sectors created.</td></tr>@endforelse
 </tbody></table></div>
</section>
@endsection
