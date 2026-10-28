<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#164f3b">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-design.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-portal.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-sidebar.css') }}">
<title>@yield('title','Arewa Civic Tracker')</title>@stack('head')
<script defer src="{{ asset('js/act-interactions.js') }}"></script>
<script defer src="{{ asset('js/act-sidebar.js') }}"></script>
</head>
<body class="@auth portal-authenticated @else portal-guest @endauth">
@auth
<div class="act-shell" id="act-shell">
<aside class="act-sidebar" id="act-sidebar" aria-label="Application navigation">
  <a class="act-sidebar-logo" href="{{ route('dashboard') }}">
    <span class="act-sidebar-logo-icon"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></span>
    <span class="act-sidebar-copy"><strong>arewa.</strong><small>Civic Tracker</small></span>
  </a>
  <div class="act-sidebar-org"><i class="fa-solid fa-building-columns" aria-hidden="true"></i><span class="act-sidebar-copy"><b>RAWYOD</b><small>VOICES TO VOTES</small></span></div>
  <nav class="act-sidebar-links">
    <small class="act-sidebar-section">Workspace</small>
    <a href="{{ route('dashboard') }}" class="act-sidebar-link {{ request()->routeIs('dashboard')?'active':'' }}" @if(request()->routeIs('dashboard'))aria-current="page"@endif><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span class="act-sidebar-copy">Dashboard</span></a>
    <a href="{{ route('cases.index') }}" class="act-sidebar-link {{ request()->routeIs('cases.index','cases.show')?'active':'' }}" @if(request()->routeIs('cases.index','cases.show'))aria-current="page"@endif><i class="fa-regular fa-file-lines" aria-hidden="true"></i><span class="act-sidebar-copy">Monitoring reports</span></a>
    @can('create', App\Models\CivicCase::class)
    <a href="{{ route('cases.create') }}" class="act-sidebar-link {{ request()->routeIs('cases.create')?'active':'' }}" @if(request()->routeIs('cases.create'))aria-current="page"@endif><i class="fa-solid fa-square-plus" aria-hidden="true"></i><span class="act-sidebar-copy">New field report</span></a>
    @endcan
    @if(auth()->user()->canReview())
    <a href="{{ route('cases.queue') }}" class="act-sidebar-link {{ request()->routeIs('cases.queue')?'active':'' }}" @if(request()->routeIs('cases.queue'))aria-current="page"@endif><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span class="act-sidebar-copy">Verification queue</span></a>
    @endif
    <small class="act-sidebar-section">Geographic intelligence</small>
    <a href="{{ route('map.index') }}" class="act-sidebar-link {{ request()->routeIs('map.index')?'active':'' }}" @if(request()->routeIs('map.index'))aria-current="page"@endif><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><span class="act-sidebar-copy">GIS intelligence map</span></a>
    @if(auth()->user()->isAdmin())
    <small class="act-sidebar-section">Administration</small>
    <a href="{{ route('admin.users.index') }}" class="act-sidebar-link {{ request()->routeIs('admin.users.*')?'active':'' }}" @if(request()->routeIs('admin.users.*'))aria-current="page"@endif><i class="fa-solid fa-users-gear" aria-hidden="true"></i><span class="act-sidebar-copy">Users & roles</span></a>
    <a href="{{ route('admin.sectors.index') }}" class="act-sidebar-link {{ request()->routeIs('admin.sectors.*')?'active':'' }}" @if(request()->routeIs('admin.sectors.*'))aria-current="page"@endif><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="act-sidebar-copy">Monitoring sectors</span></a>
    @endif
    <small class="act-sidebar-section">Account</small>
    <a href="{{ route('account.password.form') }}" class="act-sidebar-link {{ request()->routeIs('account.*')?'active':'' }}" @if(request()->routeIs('account.*'))aria-current="page"@endif><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span class="act-sidebar-copy">Security settings</span></a>
    <a href="{{ route('home') }}" class="act-sidebar-link"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span class="act-sidebar-copy">Public website</span></a>
  </nav>
  <div class="act-sidebar-bottom">
    <div class="act-sidebar-user"><span class="act-sidebar-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="act-sidebar-copy"><b>{{ auth()->user()->name }}</b><small>{{ strtoupper(auth()->user()->role) }}</small></span></div>
    <form action="{{ route('logout') }}" method="post" data-no-ajax>@csrf<button type="submit" class="act-sidebar-logout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span class="act-sidebar-copy">Sign out</span></button></form>
  </div>
</aside>
<button type="button" class="act-sidebar-backdrop" id="act-sidebar-backdrop" aria-label="Close menu" hidden></button>
<div class="act-portal-page">
  <header class="act-portal-toolbar">
    <div class="act-toolbar-left"><button id="act-sidebar-toggle" class="act-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-controls="act-sidebar" aria-expanded="true"><i class="fa-solid fa-bars-staggered" aria-hidden="true"></i></button><span class="act-toolbar-caption"><small>AREWA CIVIC TRACKER</small><strong>@yield('section_heading','Monitoring workspace')</strong></span></div>
    <div class="act-toolbar-right"><a href="{{ route('map.index') }}" class="act-toolbar-link"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> GIS</a>@can('create', App\Models\CivicCase::class)<a href="{{ route('cases.create') }}" class="act-toolbar-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>New report</span></a>@endcan<span class="act-sidebar-avatar" title="{{ auth()->user()->name }}">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span></div>
  </header>
  <main class="app-main act-portal-main"><div class="container">
  @if(session('message'))<div class="notice" role="status">{{ session('message') }}</div>@endif
  @if($errors->any())<div class="card error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>@endif
  @yield('content')
  </div></main>
  <footer class="act-portal-footer"><span>Arewa Civic Tracker · RAWYOD</span><span>VOICES TO VOTES</span></footer>
</div>
</div>
@else
<header class="app-header"><div class="container">
<a class="brand" href="{{ route('home') }}"><span class="act-portal-wordmark">arewa.<small>Civic Tracker</small></span></a>
<nav class="app-nav"><a href="{{ route('home') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to website</a></nav>
</div></header>
<main class="app-main"><div class="container">
@if(session('message'))<div class="notice" role="status">{{ session('message') }}</div>@endif
@if($errors->any())<div class="card error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>@endif
@yield('content')
</div></main>
<footer class="site-footer"><div class="container footer-bottom" style="margin-top:0"><span>Arewa Civic Tracker · RAWYOD</span><span>Secure civic monitoring platform</span></div></footer>
@endauth
<div class="toast-stack" id="toast-stack" aria-live="polite"></div>@stack('scripts')
</body></html>
