@extends('layouts.app')
@section('title','Secure staff sign-in | Arewa Civic Tracker')
@section('content')
<div class="auth-wrap">
  <div class="auth-art auth-art-image"><img src="{{ asset('images/arewa-fieldwork-hero.webp') }}?v=1" alt="Illustration of a northern Nigerian field officer recording civic evidence with a tablet and a map of monitored communities" width="300" height="360" fetchpriority="high" decoding="async"></div>
  <div class="auth-card"><span class="eyebrow">Welcome back</span><h1>Sign in to the Tracker</h1>
    <p id="login-feedback" class="auth-login-feedback" role="alert" aria-live="polite" aria-atomic="true" hidden></p>
    <form method="post" action="{{ route('login') }}" id="login-form">@csrf
      <label for="email">Email address</label><input id="email" type="email" name="email" required autocomplete="username" value="{{ old('email') }}" placeholder="you@organization.org">
      <label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
      <button type="submit" class="btn">Sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
    </form>
    <p style="font-size:12px;color:#738377;margin-top:23px">Having trouble signing in? Contact your authorized RAWYOD administrator.</p>
  </div>
</div>
@endsection
