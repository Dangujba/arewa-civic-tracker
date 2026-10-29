@extends('layouts.app')
@section('title','Users & Roles | Arewa Civic Tracker')
@section('section_heading','Users & Roles')
@push('head')
<link rel="stylesheet" href="{{ asset('css/act-users.css') }}?v=20261029-staff2">
<script defer src="{{ asset('js/act-users.js') }}?v=20261029-staff2"></script>
@endpush
@section('content')
<section class="dash-heading act-staff-heading">
  <div><span class="eyebrow">RAWYOD ADMINISTRATION</span><h1>Staff accounts</h1><p class="muted">Manage staff access and focal person assignments.</p></div>
  <a class="btn" href="{{ route('admin.users.create') }}"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Add user</a>
</section>
<div class="act-staff-toolbar">
  <form method="GET" action="{{ route('admin.users.index') }}" class="act-staff-search">
    <label class="act-visually-hidden" for="staff-search">Search staff accounts</label>
    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
    <input id="staff-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search staff by name or email" maxlength="150">
    <button type="submit" aria-label="Search staff">Search</button>
    @if(request()->filled('q'))<a class="act-staff-reset" href="{{ route('admin.users.index') }}">Clear</a>@endif
  </form>
  <span class="act-staff-count">{{ $users->total() }} {{ $users->total()===1?'staff member':'staff members' }}</span>
</div>
<section class="dash-panel act-staff-panel" aria-label="Staff accounts table">
  <div class="dash-table-wrap">
    <table class="act-staff-table">
      <thead><tr><th scope="col">Staff member</th><th scope="col">Role</th><th scope="col">Assigned LGAs</th><th scope="col">Access</th><th scope="col" class="act-actions-head">Actions</th></tr></thead>
      <tbody>
      @forelse($users as $account)
      <tr>
        <td><div class="act-staff-person"><span class="act-staff-avatar" aria-hidden="true">{{ strtoupper(mb_substr($account->name,0,1)) }}</span><div><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></div></div></td>
        <td><span class="act-staff-role">{{ ['admin'=>'Administrator','meal'=>'MEAL reviewer','focal'=>'Focal person'][$account->role]??ucfirst($account->role) }}</span></td>
        <td class="act-staff-lga-cell">
          @if($account->role==='focal' && $account->assignedLgas->isNotEmpty())
            <div class="act-staff-tags">
              @foreach($account->assignedLgas->take(3) as $lga)<span class="act-staff-chip">{{ $lga->name }}</span>@endforeach
              @if($account->assignedLgas->count()>3)<span class="act-staff-more" title="{{ $account->assignedLgas->pluck('name')->implode(', ') }}">+{{ $account->assignedLgas->count()-3 }} more</span>@endif
            </div>
          @else <span class="act-staff-empty">{{ $account->role==='focal'?'Not assigned':'Not applicable' }}</span> @endif
        </td>
        <td><span class="act-staff-access {{ $account->is_active?'is-active':'is-inactive' }}"><i class="fa-solid fa-circle" aria-hidden="true"></i> {{ $account->is_active?'Active':'Inactive' }}</span></td>
        <td>
          <div class="act-staff-actions">
            <button type="button" class="act-staff-icon act-staff-edit" aria-label="Edit {{ $account->name }}" title="Edit account"
              data-user-edit data-action="{{ route('admin.users.update',$account) }}" data-name="{{ $account->name }}" data-email="{{ $account->email }}" data-role="{{ $account->role }}" data-active="{{ $account->is_active?'1':'0' }}" data-lgas="{{ $account->assignedLgas->pluck('id')->implode(',') }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
            <form method="POST" action="{{ route('admin.users.destroy',$account) }}" data-user-delete data-no-ajax data-name="{{ $account->name }}">
              @csrf @method('DELETE')
              <button type="submit" class="act-staff-icon act-staff-delete" aria-label="Delete {{ $account->name }}" title="{{ $account->id===auth()->id()?'You cannot delete your own account':'Delete account' }}" @disabled($account->id===auth()->id())><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="act-staff-no-results">No staff accounts match your search.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="act-staff-pagination act-pagination-bar"><span>Showing {{ $users->firstItem()??0 }}–{{ $users->lastItem()??0 }} of {{ number_format($users->total()) }} staff</span>@if($users->hasPages()){{ $users->links() }}@endif</div>
</section>

<dialog class="act-user-dialog" id="act-user-edit-dialog" aria-labelledby="act-user-edit-heading">
  <div class="act-user-dialog-head"><div><span class="eyebrow">STAFF MANAGEMENT</span><h2 id="act-user-edit-heading">Edit account</h2></div><button type="button" class="act-user-dialog-close" data-user-close aria-label="Close editor"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
  <form id="act-user-edit-form" method="POST" data-no-ajax>
    @csrf @method('PUT')
    <input type="hidden" name="assignments_present" value="1">
    <div class="act-user-fields">
      <div class="act-user-field"><label for="act-edit-name">Full name</label><input id="act-edit-name" type="text" name="name" maxlength="150" required></div>
      <div class="act-user-field"><label for="act-edit-email">Email address</label><input id="act-edit-email" type="email" name="email" maxlength="255" required></div>
      <div class="act-user-field"><label for="act-edit-role">Role</label><select id="act-edit-role" name="role" required data-user-role>
        <option value="admin">Administrator</option><option value="meal">MEAL reviewer</option><option value="focal">Civic Intelligence Focal Person</option>
      </select></div>
      <div class="act-user-field"><label for="act-edit-access">Account access</label><select id="act-edit-access" name="is_active" required><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="act-user-field act-user-field-wide" data-lga-field>
        <label for="act-edit-lgas">Assigned LGAs</label>
        <select id="act-edit-lgas" name="lga_ids[]" multiple size="4" data-user-lga-tags aria-label="Search and select assigned LGAs">
          @foreach($lgas as $lga)<option value="{{ $lga->id }}">{{ $lga->name }}</option>@endforeach
        </select>
        <small>Search LGAs and remove any selected tag to unassign it.</small>
      </div>
    </div>
    <div class="act-user-actions"><button type="button" class="btn-outline" data-user-close>Cancel</button><button type="submit" class="btn"><i class="fa-solid fa-check" aria-hidden="true"></i> Save changes</button></div>
  </form>
</dialog>
@endsection
