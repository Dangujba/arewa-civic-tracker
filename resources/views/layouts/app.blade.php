<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#164f3b">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/webp" href="{{ asset('icons/arewa-handdrawn-map.webp') }}?v=4">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}?v=4">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=4">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}?v=4">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-design.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-portal.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/ui/tom-select.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/ui/sweetalert2.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-form-polish.css') }}?v=20261029-login1">
<title>@yield('title','Arewa Civic Tracker')</title>@stack('head')
<script defer src="{{ asset('vendor/ui/tom-select.js') }}"></script>
<script defer src="{{ asset('vendor/ui/sweetalert2.js') }}"></script>
<script defer src="{{ asset('js/act-select-dialogs.js') }}?v=20261029"></script>
<script defer src="{{ asset('js/act-interactions.js') }}?v=20261029-login1"></script>
<script defer src="{{ asset('js/act-sidebar.js') }}"></script>
</head>
<body class="@auth has-portal-sidebar @else guest-portal @endauth">
@auth
<div class="portal-shell" id="portal-shell">
  <aside class="portal-sidebar" id="portal-sidebar" aria-label="Application sidebar">
    <a class="portal-logo" href="{{ route('dashboard') }}">
      <img class="portal-logo-image" src="{{ asset('icons/arewa-handdrawn-map.webp') }}" alt="" width="38" height="38">
      <span class="portal-logo-text"><strong>arewa.</strong><small>Civic Tracker</small></span>
    </a>
    <div class="sidebar-organization"><span class="portal-org-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span><span class="sidebar-label"><strong>RAWYOD</strong><small>VOICES TO VOTES</small></span></div>
    <nav class="portal-nav" aria-label="Main application navigation">
      <span class="portal-nav-section">Workspace</span>
      <a href="{{ route('dashboard') }}" class="portal-nav-link {{ request()->routeIs('dashboard')?'active':'' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span class="sidebar-label">Dashboard</span></a>
      <a href="{{ route('cases.index') }}" class="portal-nav-link {{ request()->routeIs('cases.index','cases.show')?'active':'' }}" @if(request()->routeIs('cases.index','cases.show')) aria-current="page" @endif><i class="fa-regular fa-file-lines" aria-hidden="true"></i><span class="sidebar-label">Monitoring reports</span></a>
      @can('create', App\Models\CivicCase::class)
      <a href="{{ route('cases.create') }}" class="portal-nav-link {{ request()->routeIs('cases.create')?'active':'' }}" @if(request()->routeIs('cases.create')) aria-current="page" @endif><i class="fa-solid fa-square-plus" aria-hidden="true"></i><span class="sidebar-label">New field report</span></a>
      @endcan
      @if(auth()->user()->canReview())
      <a href="{{ route('cases.queue') }}" class="portal-nav-link {{ request()->routeIs('cases.queue')?'active':'' }}" @if(request()->routeIs('cases.queue')) aria-current="page" @endif><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span class="sidebar-label">Verification queue</span></a>
      @endif
      @if(auth()->user()->canReview())
      <a href="{{ route('reports.index') }}" class="portal-nav-link {{ request()->routeIs('reports.*')?'active':'' }}" @if(request()->routeIs('reports.*'))aria-current="page"@endif><i class="fa-solid fa-chart-column" aria-hidden="true"></i><span class="sidebar-label">Reports & Analytics</span></a>
      @endif
      <span class="portal-nav-section">Geographic intelligence</span>
      <a href="{{ route('map.index') }}" class="portal-nav-link {{ request()->routeIs('map.index')?'active':'' }}" @if(request()->routeIs('map.index')) aria-current="page" @endif><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><span class="sidebar-label">Interactive GIS map</span></a>
      @if(auth()->user()->isAdmin())
      <span class="portal-nav-section">Administration</span>
      <a href="{{ route('admin.users.index') }}" class="portal-nav-link {{ request()->routeIs('admin.users.*')?'active':'' }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><i class="fa-solid fa-users-gear" aria-hidden="true"></i><span class="sidebar-label">Users & roles</span></a>
      <a href="{{ route('admin.operations.index') }}" class="portal-nav-link {{ request()->routeIs('admin.operations.index')?'active':'' }}"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span class="sidebar-label">Field operations</span></a>
      <a href="{{ route('admin.operations.audit') }}" class="portal-nav-link {{ request()->routeIs('admin.operations.audit')?'active':'' }}"><i class="fa-solid fa-list-check" aria-hidden="true"></i><span class="sidebar-label">Activity audit</span></a>
      <a href="{{ route('admin.sectors.index') }}" class="portal-nav-link {{ request()->routeIs('admin.sectors.*')?'active':'' }}" @if(request()->routeIs('admin.sectors.*')) aria-current="page" @endif><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="sidebar-label">Monitoring sectors</span></a>
      @endif
      <span class="portal-nav-section">Account</span>
      <a href="{{ route('notifications.index') }}" class="portal-nav-link {{ request()->routeIs('notifications.*')?'active':'' }}"><i class="fa-regular fa-bell" aria-hidden="true"></i><span class="sidebar-label">Notifications @if(auth()->user()->unreadNotifications()->exists())<strong>({{ auth()->user()->unreadNotifications()->count() }})</strong>@endif</span></a>
      <a href="{{ route('account.password.form') }}" class="portal-nav-link {{ request()->routeIs('account.*')?'active':'' }}" @if(request()->routeIs('account.*')) aria-current="page" @endif><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span class="sidebar-label">Security settings</span></a>
      <a href="{{ route('home') }}" class="portal-nav-link"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span class="sidebar-label">Public website</span></a>
    </nav>
    <div class="portal-sidebar-bottom">
      <div class="sidebar-account"><span class="sidebar-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="sidebar-label"><strong>{{ auth()->user()->name }}</strong><small>{{ strtoupper(auth()->user()->role) }}</small></span></div>
      <form action="{{ route('logout') }}" method="POST" data-no-ajax data-logout-confirm>@csrf<button type="submit" class="sidebar-signout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span class="sidebar-label">Sign out</span></button></form>
    </div>
  </aside>
  <button class="portal-sidebar-backdrop" id="sidebar-backdrop" type="button" tabindex="-1" aria-label="Close sidebar" hidden></button>
  <div class="portal-page">
    <header class="portal-topbar">
      <div class="portal-topbar-leading">
        <button type="button" id="sidebar-toggle" class="portal-icon-button" aria-label="Toggle sidebar" aria-controls="portal-sidebar" aria-expanded="true"><i class="fa-solid fa-bars-staggered" aria-hidden="true"></i></button>
        <div class="portal-breadcrumb"><small>AREWA CIVIC TRACKER</small><strong>@yield('section_heading','Monitoring workspace')</strong></div>
      </div>
      <div class="portal-topbar-actions">
        <a class="portal-header-action" href="{{ route('map.index') }}" title="GIS map"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><span>GIS</span></a>
        @can('create', App\Models\CivicCase::class)<a class="portal-header-primary" href="{{ route('cases.create') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>New report</span></a>@endcan
        <span class="portal-topbar-user" title="{{ auth()->user()->name }}">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
      </div>
    </header>
    <main class="app-main portal-main"><div class="container">
      @if(session('message'))<div class="notice" role="status">{{ session('message') }}</div>@endif
      @if($errors->any())<div class="card error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>@endif
      @yield('content')
    </div></main>
    <footer class="portal-footer"><span>Arewa Civic Tracker · RAWYOD</span><span>VOICES TO VOTES</span></footer>
  </div>
</div>
@else
<header class="app-header"><div class="container">
<a class="brand" href="{{ route('home') }}"><img class="portal-logo-image" src="{{ asset('icons/arewa-handdrawn-map.webp') }}" alt="" width="34" height="34"><span class="act-portal-wordmark">arewa.<small>Civic Tracker</small></span></a>
<nav class="app-nav"><a href="{{ route('home') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to website</a></nav>
</div></header>
<main class="app-main guest-main"><div class="container">
@if(session('message'))<div class="notice" role="status">{{ session('message') }}</div>@endif
@if($errors->any())<div class="card error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>@endif
@yield('content')
</div></main>
<footer class="site-footer"><div class="container footer-bottom" style="margin-top:0"><span>Arewa Civic Tracker · RAWYOD</span><span>Secure civic monitoring platform</span></div></footer>
@endauth
<div class="toast-stack" id="toast-stack" aria-live="polite"></div>@stack('scripts')
</body></html>
