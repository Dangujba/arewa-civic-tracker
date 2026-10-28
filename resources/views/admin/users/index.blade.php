@extends('layouts.app')
@section('title','Users and LGA Assignments | Arewa Civic Tracker')
@section('section_heading','Users & Roles')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">RAWYOD ADMINISTRATION</span><h1>Users and field assignments</h1><p class="muted">Manage administrators, MEAL reviewers and focal persons. Assign officers to one or more LGAs.</p></div><a class="btn" href="{{ route('admin.users.create') }}">Add user</a></section>
<form method="GET" action="{{ route('admin.users.index') }}" class="card"><div class="field"><label for="user-search">Search staff by name or email</label><input id="user-search" type="search" name="q" value="{{ request('q') }}" maxlength="150"></div><button type="submit">Search</button> <a class="btn-outline" href="{{ route('admin.users.index') }}">Reset</a></form>
<section class="dash-panel"><div class="dash-table-wrap"><table><thead><tr><th>Staff member</th><th>Role</th><th>LGA assignments</th><th>Access</th><th>Save</th></tr></thead><tbody>
@forelse($users as $account)<tr><td><strong>{{ $account->name }}</strong><div class="muted">{{ $account->email }}</div></td>
<td colspan="4"><form method="POST" action="{{ route('admin.users.update',$account) }}">@csrf @method('PUT')
<input type="hidden" name="name" value="{{ $account->name }}"><input type="hidden" name="email" value="{{ $account->email }}"><input type="hidden" name="assignments_present" value="1">
<div class="act-user-editor"><select name="role" aria-label="Role for {{ $account->name }}">@foreach(['admin'=>'Administrator','meal'=>'MEAL reviewer','focal'=>'Focal person'] as $role=>$label)<option value="{{ $role }}" @selected($account->role===$role)>{{ $label }}</option>@endforeach</select>
<select name="lga_ids[]" multiple size="3" aria-label="Assigned LGAs for {{ $account->name }}" class="act-lga-multi">
@foreach($lgas as $lga)<option value="{{ $lga->id }}" @selected($account->assignedLgas->contains('id',$lga->id))>{{ $lga->name }}</option>@endforeach
</select><select name="is_active" aria-label="Account access for {{ $account->name }}"><option value="1" @selected($account->is_active)>Active</option><option value="0" @selected(!$account->is_active)>Inactive</option></select>
<button type="submit">Save</button></div>
<small class="muted">Hold Ctrl or Command to select multiple LGAs. LGA assignments apply to focal persons only.</small></form></td></tr>
@empty<tr><td colspan="5">No matching users.</td></tr>@endforelse
</tbody></table></div>{{ $users->links() }}</section>
<style>.act-user-editor{display:grid;grid-template-columns:minmax(125px,1fr) minmax(150px,2fr) minmax(100px,1fr) auto;gap:8px;align-items:start}.act-user-editor button{white-space:nowrap}.act-lga-multi{min-height:90px}@media(max-width:850px){.act-user-editor{grid-template-columns:1fr 1fr}.act-user-editor button{grid-column:span 2}}.dash-panel td{vertical-align:top}</style>
@endsection
