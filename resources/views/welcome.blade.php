<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Arewa Civic Tracker supports RAWYOD's evidence-led monitoring of civic issues and public projects across the 17 LGAs of Yobe State.">
<meta name="theme-color" content="#164f3b"><meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/webp" href="{{ asset('icons/arewa-handdrawn-map.webp') }}?v=4">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}?v=4">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=4">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}?v=4">
<title>Arewa Civic Tracker | Evidence for Better Communities</title>
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/act-structure.css') }}?v=20261029-ring1">
<script defer src="{{ asset('js/act-interactions.js') }}?v=20261029-ring1"></script>
</head><body>
<a class="skip" href="#main-content">Skip to content</a>
<div class="topline"><div class="shell"><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Yobe State, Nigeria</span><span>RAWYOD · VOICES TO VOTES</span></div></div>
<header class="site-header"><div class="shell header-inner">
<a class="wordmark" href="{{ route('home') }}" aria-label="Arewa Civic Tracker, home"><img src="{{ asset('icons/arewa-handdrawn-map.webp') }}" class="site-brand-icon" width="37" height="37" alt=""><strong>arewa.</strong><small>Civic Tracker</small></a>
<nav class="main-nav" id="site-navigation" aria-label="Primary navigation">
 <a href="#about">About</a><a href="#capabilities">Features</a><a href="#how-it-works">How it works</a><a href="#yobe">Yobe in focus</a><a class="mobile-download-link" href="{{ route('downloads.android') }}" download>Download Android app</a>
</nav>
<button class="menu-button" type="button" aria-controls="site-navigation" aria-expanded="false" data-mobile-nav aria-label="Toggle menu"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
<div class="header-actions"><a class="btn download-header" href="{{ route('downloads.android') }}" download><i class="fa-solid fa-download" aria-hidden="true"></i> Download app</a><a class="btn" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Staff portal</a></div>
</div></header>
<main id="main-content">
<section class="hero"><div class="shell hero-grid">
 <div class="hero-copy">
  <span class="eyebrow">Civic monitoring • Project accountability</span>
  <h1>Evidence for <span>better communities.</span></h1>
  <p>Every community observation matters. Arewa Civic Tracker brings together public project monitoring, geographic intelligence and verified civic reports in one secure platform designed for RAWYOD's VOICES TO VOTES initiative.</p>
  <div class="hero-actions"><a class="btn" href="#about">Explore the platform <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><a class="btn outline" href="{{ route('login') }}">Access reporting portal <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a><a class="btn" href="{{ route('downloads.android') }}" download><i class="fa-solid fa-download" aria-hidden="true"></i> Download Android app</a></div>
  <p class="download-note"><i class="fa-brands fa-android" aria-hidden="true"></i> Android preview build for testing. Download without signing in; staff credentials are required to use the app.</p>
  <div class="hero-support"><img class="sicon" src="{{ asset('vendor/lucide/shield-check.svg') }}" alt="" aria-hidden="true"><p>Secure role-based workspaces for field focal persons, reviewers and administrators.</p></div>
 </div>
 <div class="hero-visual" data-carousel aria-roledescription="carousel" aria-label="Photographs of Yobe State public infrastructure">
  <div class="visual-top"><strong><i class="fa-regular fa-images" aria-hidden="true"></i> Yobe in pictures</strong></div>
  <div class="carousel">
   <article class="carousel-slide active" aria-hidden="false"><img src="{{ asset('media/yobe-bridge.jpg') }}" alt="Garin Maje bridge in Potiskum, Yobe State" width="720" height="540" fetchpriority="high"><div class="slide-caption"><small>Infrastructure · Potiskum</small><strong>Connecting communities</strong><span>Garin Maje bridge, Yobe State</span></div></article>
   <article class="carousel-slide" aria-hidden="true"><img src="{{ asset('media/yobe-library.jpg') }}" alt="Yobe State Library Board Complex in Damaturu" width="1012" height="770" loading="lazy"><div class="slide-caption"><small>Public facilities · Damaturu</small><strong>Spaces for learning</strong><span>Yobe State Library Complex</span></div></article>
   <article class="carousel-slide" aria-hidden="true"><img src="{{ asset('media/yobe-education.jpg') }}" alt="Entrance of Yobe State University" width="1080" height="810" loading="lazy"><div class="slide-caption"><small>Education · Damaturu</small><strong>Investing in opportunity</strong><span>Yobe State University</span></div></article>
  </div>
  <div class="carousel-bottom">
   <div class="carousel-dots" role="tablist" aria-label="Select infrastructure photo"><button class="carousel-dot active" type="button" data-slide="0" aria-label="Garin Maje bridge" aria-selected="true"></button><button class="carousel-dot" type="button" data-slide="1" aria-label="Yobe State Library" aria-selected="false"></button><button class="carousel-dot" type="button" data-slide="2" aria-label="Yobe State University" aria-selected="false"></button></div>
   <div class="carousel-buttons"><button type="button" data-slide-prev aria-label="Previous photo"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button><button type="button" data-slide-next aria-label="Next photo"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button></div>
  </div>
 </div>
</div></section>
<section class="scope" aria-label="Platform scope"><div class="shell scope-grid">
 <div class="scope-stat"><span class="symbol"><img class="sicon" src="{{ asset('vendor/lucide/map-pinned.svg') }}" alt="" aria-hidden="true"></span><div><b>17</b><small>Local government areas</small></div></div>
 <div class="scope-stat"><span class="symbol"><img class="sicon" src="{{ asset('vendor/lucide/users-round.svg') }}" alt="" aria-hidden="true"></span><div><b>34</b><small>Planned focal persons</small></div></div>
 <div class="scope-stat"><span class="symbol"><img class="sicon" src="{{ asset('vendor/lucide/clipboard-check.svg') }}" alt="" aria-hidden="true"></span><div><b>08</b><small>Initial monitoring sectors</small></div></div>
 <div class="scope-stat"><span class="symbol"><img class="sicon" src="{{ asset('vendor/lucide/shield-check.svg') }}" alt="" aria-hidden="true"></span><div><b>03</b><small>Operational user roles</small></div></div>
</div></section>
<section class="section" id="about"><div class="shell focus-grid">
 <div class="focus-content">
  <span class="eyebrow">Purpose-built for accountability</span>
  <h2 class="section-title">From observations to informed action.</h2>
  <p>Arewa Civic Tracker creates a consistent process for documenting community observations and public projects, reviewing supporting evidence, and producing reliable monitoring insights.</p>
  <p>Field and monitoring teams can work from a common system, while authorized reviewers control which observations are accepted and how follow-up activity is recorded.</p>
  <a class="link" href="#how-it-works">See the reporting process <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
 </div>
 <div class="focus-cards" id="capabilities">
  <article class="focus-card"><div class="focus-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/notebook-pen.svg') }}" alt="" aria-hidden="true"></div><h3>Structured reporting</h3><p>Consistent forms for community observations and project progress.</p></article>
  <article class="focus-card"><div class="focus-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/map-pin.svg') }}" alt="" aria-hidden="true"></div><h3>Geographic intelligence</h3><p>Place reports on the map using real submitted coordinates.</p></article>
  <article class="focus-card"><div class="focus-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/file-check-2.svg') }}" alt="" aria-hidden="true"></div><h3>Evidence verification</h3><p>Review, clarify, approve and track each submitted report.</p></article>
  <article class="focus-card"><div class="focus-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/chart-no-axes-combined.svg') }}" alt="" aria-hidden="true"></div><h3>Monitoring insights</h3><p>See trends, sector summaries and follow-up priorities.</p></article>
 </div>
</div></section>
<section class="feature-band" id="coverage"><div class="shell feature-band-grid">
 <div><span class="eyebrow">Real geographic context</span><h2 class="section-title">One state. Seventeen local government areas.</h2><p>The platform uses open administrative boundary data to support location-aware civic monitoring throughout Yobe. Reports are mapped only when their authorized records include verified-access coordinates.</p><a class="btn white" href="{{ route('map.index') }}">Explore the GIS workspace <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
 <div class="feature-map" aria-label="Geographic monitoring features">
  <div class="map-tile"><img class="sicon sicon-lg sicon-white" src="{{ asset('vendor/lucide/map-pinned.svg') }}" alt="" aria-hidden="true"><b>17 LGAs</b><small>Administrative boundary layer</small></div>
  <div class="map-tile"><img class="sicon sicon-lg sicon-white" src="{{ asset('vendor/lucide/route.svg') }}" alt="" aria-hidden="true"><b>GPS</b><small>Location-based observations</small></div>
  <div class="map-tile"><img class="sicon sicon-lg sicon-white" src="{{ asset('vendor/lucide/chart-column.svg') }}" alt="" aria-hidden="true"><b>Trends</b><small>Area and sector breakdowns</small></div>
  <div class="map-tile"><img class="sicon sicon-lg sicon-white" src="{{ asset('vendor/lucide/shield-check.svg') }}" alt="" aria-hidden="true"><b>Access</b><small>Authorized record visibility</small></div>
 </div>
</div></section>
<section class="section steps" id="how-it-works"><div class="shell">
 <div class="section-header"><div><span class="eyebrow">Simple, accountable workflow</span><h2 class="section-title">How reporting works</h2></div><p class="section-lead">From the first observation to a documented outcome, every stage has an identifiable owner and a traceable record.</p></div>
 <div class="steps-grid">
  <article class="step"><div class="step-top"><span>STEP 01</span><img class="sicon" src="{{ asset('vendor/lucide/camera.svg') }}" alt="" aria-hidden="true"></div><h3>Document</h3><p>A focal person records the issue or project, its location, description and supporting evidence.</p></article>
  <article class="step"><div class="step-top"><span>STEP 02</span><img class="sicon" src="{{ asset('vendor/lucide/clipboard-check.svg') }}" alt="" aria-hidden="true"></div><h3>Submit</h3><p>The system creates a reference and maintains a record of the initial submission.</p></article>
  <article class="step"><div class="step-top"><span>STEP 03</span><img class="sicon" src="{{ asset('vendor/lucide/file-search.svg') }}" alt="" aria-hidden="true"></div><h3>Verify</h3><p>Authorized reviewers examine the evidence and record decisions or clarification requests.</p></article>
  <article class="step"><div class="step-top"><span>STEP 04</span><img class="sicon" src="{{ asset('vendor/lucide/chart-column.svg') }}" alt="" aria-hidden="true"></div><h3>Follow up</h3><p>Monitoring teams track progress through dashboards and documented updates.</p></article>
 </div>
</div></section>
<section class="section" id="yobe"><div class="shell">
 <div class="section-header"><div><span class="eyebrow">Local context</span><h2 class="section-title">Yobe in focus</h2></div><p class="section-lead">These authentic photographs are geographical illustrations. They are not RAWYOD-submitted cases or verified assessments of the pictured projects.</p></div>
 <div class="photos-grid">
  <article class="photo-card"><img loading="lazy" src="{{ asset('media/yobe-bridge.jpg') }}" alt="Garin Maje bridge in Potiskum" width="720" height="540"><div class="photo-card-body"><span class="eyebrow">Infrastructure</span><h3>Garin Maje bridge</h3><p>Infrastructure and connectivity are important dimensions of public-service monitoring.</p><small>Photo: <a href="https://commons.wikimedia.org/wiki/File:Garin_maje_New_bridge.jpg" target="_blank" rel="noopener noreferrer">Mamman AA / Wikimedia Commons</a>, CC BY-SA 4.0.</small></div></article>
  <article class="photo-card"><img loading="lazy" src="{{ asset('media/yobe-library.jpg') }}" alt="Yobe State Library Board Complex" width="1012" height="770"><div class="photo-card-body"><span class="eyebrow">Education</span><h3>Yobe State Library Complex</h3><p>Public learning spaces illustrate institutions and services across the state.</p><small>Photo: <a href="https://commons.wikimedia.org/wiki/File:Yobe_state_library_Board_Complex.jpg" target="_blank" rel="noopener noreferrer">Useendk / Wikimedia Commons</a>, CC BY-SA 4.0.</small></div></article>
  <article class="photo-card"><img loading="lazy" src="{{ asset('media/yobe-education.jpg') }}" alt="Yobe State University campus entrance" width="1080" height="810"><div class="photo-card-body"><span class="eyebrow">Higher education</span><h3>Yobe State University</h3><p>Regional institutions are part of the geographical landscape captured by civic monitoring.</p><small>Photo: <a href="https://commons.wikimedia.org/wiki/File:Yobe_State_University.jpg" target="_blank" rel="noopener noreferrer">Musaddam Idriss / Wikimedia Commons</a>, CC BY-SA.</small></div></article>
 </div>
</div></section>
<section class="section roles" id="workspaces"><div class="shell">
 <div class="section-header"><div><span class="eyebrow">Purpose-built workspaces</span><h2 class="section-title">Built around the people using it</h2></div><p class="section-lead">Dedicated access for approved field focal persons, MEAL reviewers and administrators. Individual permissions determine which reports and actions are available.</p></div>
 <div class="roles-grid">
  <article class="role"><div class="role-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/users-round.svg') }}" alt="" aria-hidden="true"></div><h3>Field focal persons</h3><p>Capture observations, manage submissions and respond to requests for clarification.</p><a class="link" href="{{ route('cases.create') }}">Field workspace <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article>
  <article class="role"><div class="role-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/file-search.svg') }}" alt="" aria-hidden="true"></div><h3>Review and MEAL teams</h3><p>Assess report quality, evidence and progress through a documented review process.</p><a class="link" href="{{ route('cases.index') }}">Review reports <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article>
  <article class="role"><div class="role-icon"><img class="sicon sicon-lg" src="{{ asset('vendor/lucide/chart-no-axes-combined.svg') }}" alt="" aria-hidden="true"></div><h3>Administrators</h3><p>Manage users, monitor organizational activity and explore reports through data-driven summaries.</p><a class="link" href="{{ route('dashboard') }}">Monitoring dashboard <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article>
 </div>
</div></section>
<section class="cta"><div class="shell cta-inner"><div><span class="eyebrow">Arewa Civic Tracker</span><h2>Turn field observations into clearer decisions.</h2><p>Sign in to your authorized workspace to document issues, review evidence and monitor activities across Yobe State.</p></div><a class="btn white" href="{{ route('login') }}">Open staff portal <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div></section>
</main>
<footer class="footer"><div class="shell">
 <div class="footer-top"><div><a class="wordmark" href="{{ route('home') }}"><strong>arewa.</strong><small>Civic Tracker</small></a><p class="footer-about">A civic monitoring initiative for Roadmap for Women and Youth Development (RAWYOD), under VOICES TO VOTES. Implemented by InnoBytes Lab LTD.</p></div><nav class="footer-links" aria-label="Footer"><a href="#about">About</a><a href="#capabilities">Features</a><a href="#how-it-works">Reporting process</a><a href="#yobe">Yobe in focus</a><a href="{{ route('downloads.android') }}" download>Download Android app</a><a href="{{ route('login') }}">Staff portal</a></nav></div>
 <div class="footer-bottom"><span>© {{ date('Y') }} Arewa Civic Tracker. All rights reserved.</span><span>Infrastructure photography: Wikimedia Commons creators · Boundary data: geoBoundaries.</span></div>
</div></footer>
<div class="toast-stack" id="toast-stack" aria-live="polite"></div>
</body></html>
