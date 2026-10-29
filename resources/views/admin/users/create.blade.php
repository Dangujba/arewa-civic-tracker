@extends('layouts.app')
@section('title','Add Staff Member | Arewa Civic Tracker')
@section('section_heading','Create Staff Account')
@push('head')
<link rel="stylesheet" href="{{ asset('css/act-users.css') }}?v=20261029-staff2">
<script defer src="{{ asset('js/act-users.js') }}?v=20261029-staff2"></script>
@endpush
@section('content')
<section class="dash-heading act-staff-heading">
 <div><span class="eyebrow">RAWYOD ADMINISTRATION</span><h1>Add staff member</h1><p class="muted">Create an account and assign field coverage where applicable.</p></div>
 <a class="btn-outline" href="{{ route('admin.users.index') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to staff</a>
</section>
<section class="dash-panel act-user-create-panel">
 <form action="{{ route('admin.users.store') }}" method="POST" data-no-ajax id="act-user-create-form">@csrf
  <div class="act-user-section-heading"><h2>Account details</h2><span>All fields marked * are required</span></div>
  <div class="act-user-fields">
    <div class="act-user-field"><label for="create-user-name">Full name <span aria-hidden="true">*</span></label><input id="create-user-name" type="text" name="name" value="{{ old('name') }}" maxlength="150" autocomplete="name" required placeholder="Enter staff member's name"></div>
    <div class="act-user-field"><label for="create-user-email">Email address <span aria-hidden="true">*</span></label><input id="create-user-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required placeholder="name@example.org"></div>
    <div class="act-user-field"><label for="create-user-role">Role <span aria-hidden="true">*</span></label><select id="create-user-role" name="role" data-user-role required>
       <option value="focal" @selected(old('role','focal')==='focal')>Civic Intelligence Focal Person</option>
       <option value="meal" @selected(old('role')==='meal')>MEAL reviewer</option>
       <option value="admin" @selected(old('role')==='admin')>Administrator</option>
    </select></div>
    <div class="act-user-field" data-lga-field @if(old('role','focal')!=='focal') hidden @endif>
      <label for="create-user-lgas">Assigned LGAs</label>
      <select id="create-user-lgas" name="lga_ids[]" multiple size="4" data-user-lga-tags aria-label="Search and select assigned LGAs">
        @foreach($lgas as $lga)<option value="{{ $lga->id }}" @selected(in_array($lga->id,array_map('intval',old('lga_ids',[]))))>{{ $lga->name }}</option>@endforeach
      </select><small>Select one or more LGAs using searchable removable tags.</small>
    </div>
  </div>
  <div class="act-user-section-heading act-user-password-heading"><h2>Initial password</h2><span>At least 12 characters. The staff member must change it on first sign-in.</span></div>
  <div class="act-user-fields">
    <div class="act-user-field"><label for="create-user-password">Password <span aria-hidden="true">*</span></label><input id="create-user-password" type="password" name="password" minlength="12" autocomplete="new-password" required placeholder="Minimum 12 characters"></div>
    <div class="act-user-field"><label for="create-user-confirm">Confirm password <span aria-hidden="true">*</span></label><input id="create-user-confirm" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required placeholder="Re-enter password"></div>
  </div>
  <div class="act-user-actions"><a class="btn-outline" href="{{ route('admin.users.index') }}">Cancel</a><button type="submit" class="btn"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create account</button></div>
 </form>
</section>
@endsection
