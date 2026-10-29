@extends('layouts.app')
@section('title','Evidence Storage | Arewa Civic Tracker')
@section('section_heading','Evidence Storage')
@push('head')
<link rel="stylesheet" href="{{ asset('css/act-evidence-storage.css') }}?v=20261029-evidence1">
@endpush
@section('content')
<section class="dash-heading act-storage-heading">
 <div><span class="eyebrow">SECURE FIELD EVIDENCE</span><h1>Evidence Storage</h1><p class="muted">Private storage for report photographs and PDF documents.</p></div>
 <a href="{{ route('admin.operations.index') }}" class="btn-outline"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Field Operations</a>
</section>
<div class="act-storage-stats">
 <div><span>Stored Evidence</span><strong>{{ number_format($overview['total_files']) }}</strong><small>Attached files recorded</small></div>
 <div><span>Total File Size</span><strong>{{ number_format($overview['total_bytes']/1048576,2) }} MB</strong><small>Recorded uploaded size</small></div>
 <div><span>Active Storage</span><strong>{{ $overview['active_disk']==='r2'?'Cloudflare R2':'Private Local' }}</strong><small>Destination for new evidence</small></div>
</div>
<section class="dash-panel act-storage-panel" aria-labelledby="act-storage-breakdown">
 <div class="act-storage-head"><div><h2 id="act-storage-breakdown">Storage Distribution</h2><p>Every evidence record retains its storage location and a SHA-256 fingerprint.</p></div></div>
 <div class="dash-table-wrap"><table class="act-storage-table">
  <thead><tr><th scope="col">Storage Location</th><th scope="col">Files</th><th scope="col">Recorded Size</th><th scope="col">Access</th></tr></thead>
  <tbody>
   @forelse($overview['by_disk'] as $disk)
    <tr><td><strong>{{ $disk->disk==='r2'?'Cloudflare R2':($disk->disk==='local'?'Private Local':ucfirst($disk->disk)) }}</strong></td>
    <td>{{ number_format($disk->file_count) }}</td><td>{{ number_format($disk->bytes/1048576,2) }} MB</td><td><span class="act-storage-private"><i class="fa-solid fa-lock" aria-hidden="true"></i> Authenticated</span></td></tr>
   @empty
    <tr><td colspan="4" class="act-table-empty">No field evidence has been uploaded yet.</td></tr>
   @endforelse
  </tbody>
 </table></div>
</section>
<section class="dash-panel act-storage-panel" aria-labelledby="act-storage-r2-title">
 <div class="act-storage-head"><div><h2 id="act-storage-r2-title">Cloudflare R2</h2><p>Private object storage for long-term evidence preservation.</p></div><span class="act-storage-state {{ $overview['r2_configured']?'is-ready':'is-pending' }}">{{ $overview['r2_configured']?'Configured':'Awaiting Configuration' }}</span></div>
 <div class="act-storage-body">
  @if($overview['r2_configured'])
   <p>R2 credentials and the HTTPS storage endpoint are configured on this server. Use the connection check before switching uploads to R2.</p>
  @else
   <p>R2 credentials have not yet been added to the server. New evidence remains on private local storage. No existing attachments are moved or deleted.</p>
  @endif
  <p class="act-storage-hint">To enable R2, configure the account endpoint, bucket name, and scoped API credentials in the server environment. Credentials are never displayed in the browser.</p>
  <form method="POST" action="{{ route('admin.operations.storage.check') }}" data-no-ajax>@csrf
   <button type="submit" class="btn" @disabled(!$overview['r2_configured'])><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Verify Private R2 Connection</button>
  </form>
 </div>
</section>
<section class="dash-panel act-storage-panel" aria-labelledby="act-storage-integrity">
 <div class="act-storage-head"><div><h2 id="act-storage-integrity">Integrity and Migration</h2><p>Operations available to the system administrator.</p></div></div>
 <div class="act-storage-body">
  <p>Each file can be checked against its recorded SHA-256 hash and expected size. Migration copies a file to R2, verifies the remote copy and only then updates its database location. Original local files are retained until independently approved for removal.</p>
  <div class="act-storage-commands">
    <div><span>Audit evidence</span><code>php artisan act:evidence-storage --audit</code></div>
    <div><span>Preview migration</span><code>php artisan act:evidence-storage --migrate-r2</code></div>
    <div><span>Verified transfer</span><code>php artisan act:evidence-storage --migrate-r2 --execute</code></div>
  </div>
  <small>These commands run on the server. A browser action cannot silently migrate or delete evidence.</small>
 </div>
</section>
@endsection
