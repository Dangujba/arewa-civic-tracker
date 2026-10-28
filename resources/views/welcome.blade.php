<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Arewa Civic Tracker: structured civic monitoring, public project reporting, evidence review and governance accountability across Yobe State.">
<meta name="theme-color" content="#173f33"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>Arewa Civic Tracker | Civic Monitoring & Public Project Accountability</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="{{ asset('css/act-design.css') }}">
<script src="{{ asset('js/act-interactions.js') }}" defer></script>
</head>
<body>
<div class="topline"><div class="container"><span>VOICES TO VOTES • Digital civic monitoring and community accountability</span><span>RAWYOD • Yobe State, Nigeria</span></div></div>
<header class="site-header"><div class="container">
  <a href="{{ url('/') }}" class="brand" aria-label="Arewa Civic Tracker home"><span class="brand-mark">A</span><span>Arewa Civic Tracker<small>Powered by community intelligence</small></span></a>
  <button class="mobile-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" data-mobile-nav>☰ <span class="sr-only">Toggle navigation</span></button>
  <nav class="site-nav" id="site-navigation" aria-label="Main navigation">
    <a href="#about">About the tracker</a><a href="#features">Platform features</a><a href="#how-it-works">How it works</a><a href="#infrastructure">Yobe in focus</a>
  </nav>
  <div class="header-actions"><a href="{{ route('login') }}" class="btn">Staff portal <span aria-hidden="true">↗</span></a></div>
</div></header>
<main>
<section class="hero" id="about"><div class="container hero-grid">
  <div>
    <span class="eyebrow">Transparency begins with evidence</span>
    <h1>Stronger communities.<br><em>Accountable governance.</em></h1>
    <p>A secure digital platform helping trained civic focal persons document public projects, report service-delivery issues and turn verified community observations into actionable insights across Yobe State.</p>
    <div class="hero-buttons">
      <a href="{{ route('login') }}" class="btn btn-lg">Access reporting portal <span aria-hidden="true">↗</span></a>
      <a href="#how-it-works" class="btn-outline btn-lg">Discover how it works <span aria-hidden="true">↓</span></a>
    </div>
  </div>
  <div class="hero-visual">
    <div class="photo-flag">REAL PLACES • YOBE STATE</div>
    <div class="slide-window" data-carousel aria-roledescription="carousel" aria-label="Yobe State public infrastructure photographs">
      <article class="carousel-slide active" aria-hidden="false">
        <img src="{{ asset('media/yobe-bridge.jpg') }}" alt="A bridge at Garin Maje in Potiskum, Yobe State" width="720" height="540" fetchpriority="high">
        <div class="slide-info"><span>Infrastructure • Potiskum</span><strong>Connecting communities through public infrastructure</strong><small>Photograph: Garin Maje bridge. Mamman AA / Wikimedia Commons, CC BY-SA 4.0.</small></div>
      </article>
      <article class="carousel-slide" aria-hidden="true">
        <img src="{{ asset('media/yobe-library.jpg') }}" alt="Yobe State Library Board Complex in Damaturu" width="1012" height="770" loading="lazy">
        <div class="slide-info"><span>Education & community infrastructure</span><strong>Spaces that support knowledge, learning and opportunity</strong><small>Photograph: Yobe State Library Board Complex. Useendk / Wikimedia Commons, CC BY-SA 4.0.</small></div>
      </article>
      <article class="carousel-slide" aria-hidden="true">
        <img src="{{ asset('media/yobe-education.jpg') }}" alt="Entrance gate at Yobe State University, Damaturu" width="1080" height="810" loading="lazy">
        <div class="slide-info"><span>Education • Damaturu</span><strong>Documenting the places shaping the future</strong><small>Photograph: Yobe State University entrance. Musaddam Idriss / Wikimedia Commons, CC BY-SA.</small></div>
      </article>
      <div class="carousel-controls"><div class="carousel-dots" role="tablist" aria-label="Choose photograph">
        <button class="carousel-dot active" type="button" aria-label="Photo 1: Garin Maje bridge" aria-selected="true" data-slide="0"></button>
        <button class="carousel-dot" type="button" aria-label="Photo 2: Yobe State Library" aria-selected="false" data-slide="1"></button>
        <button class="carousel-dot" type="button" aria-label="Photo 3: Yobe State University" aria-selected="false" data-slide="2"></button>
      </div><div class="carousel-arrows"><button class="carousel-arrow" type="button" data-slide-prev aria-label="Previous photograph">‹</button><button class="carousel-arrow" type="button" data-slide-next aria-label="Next photograph">›</button></div></div>
    </div>
  </div>
</div></section>
<section class="stat-strip" aria-label="Initial platform coverage"><div class="container stat-grid">
  <div class="stat"><b>17</b><span>Yobe local government areas</span></div>
  <div class="stat"><b>34</b><span>Planned civic intelligence focal persons</span></div>
  <div class="stat"><b>3</b><span>Role-based user workspaces</span></div>
</div></section>
<section class="section" id="features"><div class="container">
  <span class="eyebrow">One platform. Meaningful action.</span><h2>From observations to<br>verified civic intelligence.</h2>
  <p class="section-sub">A complete reporting, monitoring and accountability workspace designed around the realities of community fieldwork, independent verification and informed decision-making.</p>
  <div class="feature-grid">
    <article class="feature"><span class="feature-icon" aria-hidden="true">▤</span><h3>Structured field reporting</h3><p>Capture public projects and civic issues with location, sector, observations, photographs and follow-up actions in one guided workflow.</p></article>
    <article class="feature"><span class="feature-icon" aria-hidden="true">◎</span><h3>Interactive GIS mapping</h3><p>Explore verified geographic reports by LGA, community, sector, observation date and project status.</p></article>
    <article class="feature"><span class="feature-icon" aria-hidden="true">✓</span><h3>Evidence-led verification</h3><p>Review submissions, request clarification, approve or reject reports and preserve a traceable decision history.</p></article>
    <article class="feature"><span class="feature-icon" aria-hidden="true">↻</span><h3>Offline-capable fieldwork</h3><p>Save drafts on mobile devices, queue submissions when connectivity is poor, and synchronize after reconnection.</p></article>
    <article class="feature"><span class="feature-icon" aria-hidden="true">▥</span><h3>Insights and dashboards</h3><p>Compare reporting trends across 17 LGAs with configurable filters, sector indicators and exportable summaries.</p></article>
    <article class="feature"><span class="feature-icon" aria-hidden="true">♧</span><h3>Protected organizational data</h3><p>Role-based access, secure evidence storage and auditability help keep operational information with authorized RAWYOD users.</p></article>
  </div>
</div></section>
<section class="section dark-section" id="how-it-works"><div class="container">
  <span class="eyebrow">The monitoring journey</span><h2>Every report follows<br>a clear path to accountability.</h2>
  <p class="section-sub">A transparent workflow connects community observations with responsible verification, oversight and documented follow-up.</p>
  <div class="steps">
    <article class="step"><b>01</b><h3>Observe & document</h3><p>Authorized focal persons record project information or service-delivery issues, including supporting evidence.</p></article>
    <article class="step"><b>02</b><h3>Submit securely</h3><p>Reports are saved, submitted and tracked with a unique reference, even after periods of poor connectivity.</p></article>
    <article class="step"><b>03</b><h3>Verify the evidence</h3><p>RAWYOD reviewers validate submitted information and request clarification whenever necessary.</p></article>
    <article class="step"><b>04</b><h3>Track & act</h3><p>Approved information supports dashboards, geographic monitoring, structured follow-ups and reporting.</p></article>
  </div>
</div></section>
<section class="section" id="infrastructure"><div class="container">
  <span class="eyebrow">Places and infrastructure in Yobe</span><h2>Understand the communities<br>behind the data.</h2>
  <p class="section-sub">These authentic photos are included for geographic and infrastructure context only. They are not Arewa Civic Tracker submissions, investigation results or RAWYOD-verified project assessments.</p>
  <div class="project-grid">
    <article class="project"><img src="{{ asset('media/yobe-bridge.jpg') }}" alt="Garin Maje bridge near Potiskum" loading="lazy"><div class="body"><span class="label">Transport infrastructure</span><h3>Garin Maje bridge</h3><p>Real location in the Potiskum area, illustrating the infrastructure categories civic focal persons may document.</p><span class="credit"><a href="https://commons.wikimedia.org/wiki/File:Garin_maje_New_bridge.jpg" target="_blank" rel="noopener">Mamman AA · Wikimedia Commons · CC BY-SA 4.0</a></span></div></article>
    <article class="project"><img src="{{ asset('media/yobe-library.jpg') }}" alt="Yobe State Library Board building" loading="lazy"><div class="body"><span class="label">Educational infrastructure</span><h3>Yobe State Library Complex</h3><p>A photographed public institution in Damaturu, featured as an example of infrastructure and service access.</p><span class="credit"><a href="https://commons.wikimedia.org/wiki/File:Yobe_state_library_Board_Complex.jpg" target="_blank" rel="noopener">Useendk · Wikimedia Commons · CC BY-SA 4.0</a></span></div></article>
    <article class="project"><img src="{{ asset('media/yobe-education.jpg') }}" alt="Yobe State University entrance" loading="lazy"><div class="body"><span class="label">Higher education</span><h3>Yobe State University</h3><p>Damaturu campus entrance, highlighting how geographic reporting can place institutions within their communities.</p><span class="credit"><a href="https://commons.wikimedia.org/wiki/File:Yobe_State_University.jpg" target="_blank" rel="noopener">Musaddam Idriss · Wikimedia Commons · CC BY-SA</a></span></div></article>
  </div>
</div></section>
<section class="section"><div class="container"><div class="cta-panel">
  <div><span class="eyebrow">For authorized field and monitoring personnel</span><h2>Ready to document what matters?</h2><p>Access your reporting workspace to submit observations, review evidence, follow projects or explore the monitoring map.</p></div>
  <div><a href="{{ route('login') }}" class="btn btn-lg">Open secure portal <span aria-hidden="true">↗</span></a></div>
</div></div></section>
</main>
<footer class="site-footer"><div class="container">
  <div class="footer-grid"><div><a href="{{ url('/') }}" class="brand"><span class="brand-mark">A</span><span>Arewa Civic Tracker<small>Civic monitoring for stronger communities</small></span></a><p>An initiative of Roadmap for Women and Youth Development (RAWYOD), under the VOICES TO VOTES project, supported by the Nigeria Youth Futures Fund.</p></div><div class="footer-links"><a href="#features">Features</a><a href="#how-it-works">How it works</a><a href="{{ route('login') }}">Staff portal</a></div></div>
  <div class="footer-bottom"><span>© {{ date('Y') }} Arewa Civic Tracker. All rights reserved.</span><span>Real infrastructure photos are credited to their Wikimedia Commons creators.</span></div>
</div></footer>
<div class="toast-stack" aria-live="polite" id="toast-stack"></div>
</body></html>
