@extends('layouts.app')
@section('title','Account security | Arewa Civic Tracker')
@section('content')
<div class="account-page">
    <div class="account-heading"><span class="eyebrow">SECURE ACCOUNT ACCESS</span>
      <h1>{{ $mustChange ? 'Set your own password' : 'Account security' }}</h1>
      <p class="muted">{{ $mustChange ? 'Replace your temporary password before accessing your reporting workspace.' : 'Update your account password to protect your access.' }}</p>
    </div>
    <section class="card account-form">
      <form action="{{ route('account.password.update') }}" method="POST">@csrf
      <div class="field"><label for="current-password">Current password</label><input id="current-password" type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="field"><label for="new-password">New password</label><input id="new-password" type="password" name="password" required minlength="12" autocomplete="new-password"></div>
      <div class="field"><label for="password-confirmation">Confirm new password</label><input id="password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></div>
      <p class="muted">Minimum 12 characters including uppercase, lowercase, a number and a symbol.</p>
      <p data-form-status role="status" hidden></p>
      <button type="submit" class="btn"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Save secure password</button>
      </form>
    </section>
</div>
@endsection
