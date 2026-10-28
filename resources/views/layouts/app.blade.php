<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#173f33">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="stylesheet" href="{{ asset('css/act-design.css') }}">
<title>@yield('title','Arewa Civic Tracker')</title>@stack('head')
<script defer src="{{ asset('js/act-interactions.js') }}"></script>
</head><body>
<header class="app-header"><div class="container">
  <a class="brand" href="{{ route('home') }}"><span class="brand-mark">A</span><span>Arewa Civic Tracker<small>RAWYOD · VOICES TO VOTES</small></span></a>
  @auth
  <nav class="app-nav" aria-label="Portal navigation">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('cases.index') }}">Reports</a>
    <a href="{{ route('map.index') }}">GIS map</a>
    <a href="{{ route('cases.create') }}">New report</a>
    @if(auth()->user()->isAdmin())
      <a href="{{ route('admin.users.index') }}">Users</a>
      <a href="{{ route('admin.sectors.index') }}">Sectors</a>
    @endif
    <form action="{{ route('logout') }}" method="post">@csrf<button class="btn-outline" type="submit">Sign out</button></form>
  </nav>
  @else
  <nav class="app-nav"><a href="{{ route('home') }}">← Back to site</a></nav>
  @endauth
</div></header>
<main class="app-main"><div class="container">
@if(session('message'))<div class="notice" role="status">{{ session('message') }}</div>@endif
@if($errors->any())<div class="card error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>@endif
@yield('content')
</div></main>
<footer class="site-footer"><div class="container footer-bottom" style="margin-top:0"><span>Arewa Civic Tracker · RAWYOD</span><span>Secure civic monitoring platform</span></div></footer>
<div class="toast-stack" id="toast-stack" aria-live="polite"></div>@stack('scripts')
</body></html>
