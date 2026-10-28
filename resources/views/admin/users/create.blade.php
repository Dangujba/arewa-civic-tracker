@extends('layouts.app')
@section('title','Add Staff Member | Arewa Civic Tracker')
@section('section_heading','Create Staff Account')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">RAWYOD ADMINISTRATION</span><h1>Create staff account</h1><p class="muted">Initial passwords must be changed when new staff sign in.</p></div></section>
<section class="card"><form method="POST" action="{{ route('admin.users.store') }}">@csrf
<div class="grid"><div class="field"><label>Name<input name="name" required maxlength="150" value="{{ old('name') }}"></label></div>
<div class="field"><label>Email<input name="email" type="email" required value="{{ old('email') }}"></label></div>
<div class="field"><label>Role<select name="role" required><option value="focal" @selected(old('role')==='focal')>Civic Intelligence Focal Person</option><option value="meal" @selected(old('role')==='meal')>MEAL Reviewer</option><option value="admin" @selected(old('role')==='admin')>RAWYOD Administrator</option></select></label></div>
<div class="field"><label>Assigned LGAs (focal persons only)<select name="lga_ids[]" multiple size="5" data-no-search aria-label="Local governments assigned to focal person">@foreach($lgas as $lga)<option value="{{ $lga->id }}" @selected(in_array($lga->id,old('lga_ids',[])))>{{ $lga->name }}</option>@endforeach</select></label><small class="muted">Hold Ctrl or Command to select multiple areas. Leave blank for MEAL and admin roles.</small></div>
<div class="field"><label>Initial password<input type="password" name="password" required minlength="12" autocomplete="new-password"></label></div>
<div class="field"><label>Confirm password<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label></div></div>
<button type="submit">Create account</button> <a href="{{ route('admin.users.index') }}" class="btn-outline">Back to users</a>
</form></section>
@endsection
