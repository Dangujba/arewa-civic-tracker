@extends('layouts.app')
@section('title','New report | Arewa Civic Tracker')
@section('content')
<div class="report-form-heading"><h1>New field report</h1><p class="muted">Document a public project or governance issue with location and supporting evidence.</p></div>
@if(auth()->user()->role==='focal' && $lgas->isEmpty())<p class="notice" role="alert">You have not been assigned an LGA. Ask your RAWYOD Administrator to assign your monitoring area before submitting reports.</p>@endif
<form method="post" action="{{ route('cases.store') }}" class="card report-form" data-similar-url="{{ route('cases.similar') }}" enctype="multipart/form-data" data-user-id="{{ auth()->id() }}">@csrf<input type="hidden" name="client_submission_id" id="submission_id" value="{{ old('client_submission_id') }}"><div class="grid"><div class="field"><label>Report type</label><select required name="type"><option value="public_project">Public project</option><option value="governance_issue">Governance / service delivery issue</option></select></div><div class="field"><label>Observation date</label><input type="date" name="observation_date" max="{{ now()->toDateString() }}" value="{{ old('observation_date',now()->toDateString()) }}" required></div><div class="field"><label>LGA</label><select id="case-lga" name="lga_id" required><option value="">Select LGA</option>@foreach($lgas as $l)<option value="{{ $l->id }}" @selected(old('lga_id')==$l->id)>{{ $l->name }}</option>@endforeach</select></div>@include('components.ward-picker',['wardValue'=>old('ward')])<div class="field"><label>Community</label><input list="community-suggestions" name="community" required maxlength="150" value="{{ old('community') }}"></div><div class="field"><label>Report Category (Optional)</label><select name="report_category_id"><option value="">No category</option>@foreach($categories as $c)<option value="{{ $c->id }}" data-type="{{ $c->type }}" @selected(old('report_category_id',isset($case)?$case->report_category_id:null)==$c->id)>{{ $c->name }}</option>@endforeach</select></div><datalist id="community-suggestions">@foreach($communities as $place)<option value="{{ $place->name }}" data-lga="{{ $place->lga_id }}"></option>@endforeach</datalist><div class="field"><label>Sector</label><select name="sector_id" required><option value="">Select sector</option>@foreach($sectors as $s)<option value="{{ $s->id }}" @selected(old('sector_id')==$s->id)>{{ $s->name }}</option>@endforeach</select></div><div class="field"><label>Observed progress</label><select name="progress_status" required>@foreach(['observed','not_started','ongoing','stalled','completed','resolved','other'] as $s)<option value="{{ $s }}" @selected(old('progress_status')===$s)>{{ \App\Models\WorkflowStatusLabel::label('progress',$s) }}</option>@endforeach</select></div></div>
<div class="field"><label>Report title</label><input name="title" minlength="5" maxlength="200" required value="{{ old('title') }}"></div><div class="field"><label>Description</label><textarea name="description" minlength="15" required>{{ old('description') }}</textarea></div><div class="field"><label>Observations</label><textarea name="observations">{{ old('observations') }}</textarea></div><div class="field"><label>Stakeholders / duty bearers</label><textarea name="duty_bearers">{{ old('duty_bearers') }}</textarea></div><section class="report-evidence-section">
<h2><i class="fa-solid fa-camera" aria-hidden="true"></i> Photographs & supporting evidence</h2>
<p class="muted">Attach up to five photographs or PDF documents. Photographs: maximum 10MB each, originals preserved privately; optimized previews generated on the server. PDFs: maximum 5MB. Select a photo from your device or use the camera.</p>
<label for="evidence-files" class="evidence-dropzone" id="evidence-dropzone" tabindex="0" role="button" aria-label="Choose or drop supporting evidence files">
<i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
<strong>Drag and drop your evidence here</strong>
<span>or <b>browse files</b> · JPG, PNG, WebP up to 10 MB · PDF up to 5 MB</span>
</label>
<input id="evidence-files" class="evidence-file-input" type="file" name="evidence[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple>
<div id="evidence-preview" aria-live="polite"></div>
<p class="report-upload-hint"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Uploads immediately with your report when connected. Offline saving is optional.</p>
</section>
<section class="report-location-section">
<h2><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Report location</h2>
<p class="muted">The browser will request GPS permission. Coordinates are optional, visible below, and may be edited manually at any time.</p>
<button type="button" class="secondary" id="gps-capture"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Detect my location</button>
<p id="gps-status" class="muted" role="status">Checking GPS permission…</p>
<div class="grid"><div class="field"><label for="latitude-field">Latitude (optional)</label><input id="latitude-field" type="number" step="any" min="-90" max="90" name="latitude" value="{{ old('latitude') }}"></div><div class="field"><label for="longitude-field">Longitude (optional)</label><input id="longitude-field" type="number" step="any" min="-180" max="180" name="longitude" value="{{ old('longitude') }}"></div></div></section>
<section id="act-similar-panel" class="act-similar-panel" hidden aria-live="polite">
 <div class="act-similar-heading"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
 <strong>Possible Similar Reports</strong></div>
 <p>Review these potentially related observations before submitting. You may still submit a distinct report.</p>
 <div id="act-similar-results"></div>
</section>
<div style="display:flex;gap:10px;flex-wrap:wrap"><button type="submit" id="submit-btn">Submit report</button><button type="button" class="secondary" id="save-draft">Save on this device</button><button type="button" class="secondary" id="sync-outbox">Sync pending reports</button><button type="button" class="secondary" id="new-report">Start another report</button></div><p class="muted" id="offline-status" role="status">Ready to submit online. Offline drafts are available if needed.</p></form>
@push('scripts')<script defer src="{{ asset('js/act-similar-reports.js') }}?v=20261029-similar1"></script><script defer src="{{ asset('js/act-report-location.js') }}?v=20261029-ring1"></script><script defer src="{{ asset('js/offline-reports.js') }}?v=20261029-wards1"></script><script defer src="{{ asset('js/act-wards.js') }}?v=20261029-wards1"></script>@endpush
@endsection

@push('scripts')<script defer src="{{ asset('js/act-reference-forms.js') }}?v=20261029-master1"></script>@endpush
