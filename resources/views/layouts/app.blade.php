<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#164f3b">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-design.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-portal.css') }}">
<title>@yield('title','Arewa Civic Tracker')</title>@stack('head')
<script defer src="{{ asset('js/act-interactions.js') }}"></script>
</head><body>
<header class="app-header"><div class="container">
  <a class="brand" href="{{ route('home') }}"><span class="act-portal-wordmark">arewa.<small>Civic Tracker</small></span></a>
  @auth
  <nav class="app-nav" aria-label="Portal navigation">
    <a href="{{ route('dashboard') }}"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Dashboard</a>
    <a href="{{ route('cases.index') }}"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> Reports</a>
    <a href="{{ route('map.index') }}"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> GIS map</a>
    <a href="{{ route('cases.create') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> New report</a>
    @if(auth()->user()->isAdmin())
      <a href="{{ route('admin.users.index') }}"><i class="fa-solid fa-users" aria-hidden="true"></i> Users</a>
      <a href="{{ route('admin.sectors.index') }}"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Sectors</a>
    @endif
    <form action="{{ route('logout') }}" method="post">@csrf<button class="btn-outline" type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sign out</button></form>
  </nav>
  @else
  <nav class="app-nav"><a href="{{ route('home') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to website</a></nav>
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
