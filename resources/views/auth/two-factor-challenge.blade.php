@extends('layouts.app')
@section('title','Two-factor verification | Arewa Civic Tracker')
@section('content')
<div class="account-page">
 <div class="account-heading"><span class="eyebrow">SECURE SIGN-IN</span><h1>Two-factor verification</h1><p class="muted">Open your authenticator app and enter the current 6-digit code. You may also use one of your unused recovery codes.</p></div>
 <section class="card account-form">
 <form method="POST" action="{{ route('two-factor.challenge.submit') }}">@csrf
 <div class="field"><label for="totp-code">Authentication or recovery code</label><input id="totp-code" type="text" name="code" required autocomplete="one-time-code" inputmode="text" maxlength="40" autofocus></div>
 <button type="submit" class="btn"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true" hidden></i> Verify & sign in</button>
 </form>
 </section>
</div>
@endsection
