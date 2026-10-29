@extends('layouts.app')
@section('title','My Profile | Arewa Civic Tracker')
@push('head')
<link rel="stylesheet" href="{{ asset('css/act-profile.css') }}?v=20261029-profile1">
@endpush
@section('section_heading','My Profile')
@section('content')
<section class="dash-heading act-profile-heading">
 <div><span class="eyebrow">YOUR ACCOUNT</span><h1>My Profile</h1><p class="muted">Manage your photo and view your account details.</p></div>
</section>
<div class="act-profile-layout">
 <section class="dash-panel act-profile-panel">
  <div class="act-profile-panel-heading"><h2>Profile Photo</h2></div>
  <div class="act-profile-content">
   <div class="act-profile-preview" id="act-profile-preview" aria-label="Current profile photo">
    @if($user->profile_photo_path)
     <img id="act-profile-image" src="{{ route('account.profile.photo') }}?v={{ $user->updated_at?->timestamp??0 }}" alt="Your current profile photo" width="100" height="100">
    @else
     <span id="act-profile-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</span>
    @endif
   </div>
   <div class="act-profile-controls">
    <p>Upload a clear photo of yourself. JPEG, PNG and WebP images are supported, up to 2 MB.</p>
    <form method="POST" action="{{ route('account.profile.photo.upload') }}" enctype="multipart/form-data" data-no-ajax id="act-profile-upload-form">
     @csrf
     <label for="act-profile-file" class="act-profile-file-label">Choose Photo</label>
     <input id="act-profile-file" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required aria-describedby="act-profile-file-name">
     <small id="act-profile-file-name" aria-live="polite">No photo selected</small>
     <button type="submit" class="btn" id="act-profile-upload-button"><i class="fa-solid fa-upload" aria-hidden="true"></i> Upload Photo</button>
    </form>
    @if($user->profile_photo_path)
    <form method="POST" action="{{ route('account.profile.photo.remove') }}" data-no-ajax data-profile-remove>
     @csrf @method('DELETE')
     <button type="submit" class="btn-outline act-profile-remove"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Remove Photo</button>
    </form>
    @endif
   </div>
  </div>
 </section>
 <section class="dash-panel act-profile-panel">
  <div class="act-profile-panel-heading"><h2>Account Details</h2></div>
  <dl class="act-profile-detail-list">
   <div><dt>Full Name</dt><dd>{{ $user->name }}</dd></div>
   <div><dt>Email Address</dt><dd>{{ $user->email }}</dd></div>
   <div><dt>Role</dt><dd>{{ ['admin'=>'Administrator','meal'=>'MEAL Reviewer','focal'=>'Civic Intelligence Focal Person'][$user->role]??ucfirst($user->role) }}</dd></div>
   <div><dt>Account Status</dt><dd>{{ $user->is_active?'Active':'Inactive' }}</dd></div>
  </dl>
  <div class="act-profile-security"><a href="{{ route('account.password.form') }}" class="btn-outline"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Security Settings</a></div>
 </section>
</div>
@endsection
@push('scripts')
<script defer src="{{ asset('js/act-profile.js') }}?v=20261029-profile1"></script>
@endpush
