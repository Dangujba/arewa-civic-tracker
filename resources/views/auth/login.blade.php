@extends('layouts.app')
@section('title','Secure staff sign-in | Arewa Civic Tracker')
@section('content')
<div class="auth-wrap">
  <div class="auth-art"><div><span class="eyebrow" style="color:#e1c487">AUTHORIZED ACCESS</span><h2>Evidence. Trust.<br>Accountability.</h2><p>Your reporting and monitoring workspace brings every field observation, verification decision and follow-up into one secure system.</p></div><div style="font-size:12px;color:#d6e6db">RAWYOD · VOICES TO VOTES<br>Yobe State, Nigeria</div></div>
  <div class="auth-card"><span class="eyebrow">Welcome back</span><h1>Sign in to the Tracker</h1><p class="sub">Use the credentials provided by your RAWYOD administrator. Access is restricted to authorized personnel.</p>
    <form method="post" action="{{ route('login') }}" id="login-form">@csrf
      <label for="email">Email address</label><input id="email" type="email" name="email" required autocomplete="username" value="{{ old('email') }}" placeholder="you@organization.org">
      <label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
      <p data-form-status hidden role="status" style="font-size:12px;color:#577564"></p>
      <button type="submit" class="btn">Secure sign in <span aria-hidden="true">→</span></button>
    </form>
    <p style="font-size:12px;color:#738377;margin-top:23px">Having trouble signing in? Contact your authorized RAWYOD administrator.</p>
  </div>
</div>
@endsection
