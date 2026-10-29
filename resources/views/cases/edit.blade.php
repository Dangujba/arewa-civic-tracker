@extends('layouts.app')
@section('title','Edit Monitoring Report | Arewa Civic Tracker')
@section('section_heading','Edit Monitoring Report')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">REPORT REVISION</span><h1>Edit {{ $case->reference }}</h1><p class="muted">Provide a reason for each revision. Previous values remain recorded and visible in version history.</p></div><a class="btn-outline" href="{{ route('cases.show',$case) }}">Back to Report</a></section>
<form method="POST" action="{{ route('cases.update',$case) }}" class="dash-panel report-form" data-no-ajax>@csrf @method('PUT')
 <input type="hidden" name="version" value="{{ $case->revisions()->max('version')??0 }}">
 <div class="grid">
 <div class="field"><label>Report Type</label><select name="type" required><option value="public_project" @selected(old('type',$case->type)==='public_project')>Public Project</option><option value="governance_issue" @selected(old('type',$case->type)==='governance_issue')>Governance Issue</option></select></div>
 <div class="field"><label>Observation Date</label><input type="date" max="{{ now()->toDateString() }}" name="observation_date" value="{{ old('observation_date',$case->observation_date->format('Y-m-d')) }}" required></div>
 <div class="field"><label>LGA</label><select name="lga_id" required>@foreach($lgas as $l)<option value="{{ $l->id }}" @selected(old('lga_id',$case->lga_id)==$l->id)>{{ $l->name }}</option>@endforeach</select></div>
 <div class="field"><label>Community</label><input name="community" maxlength="150" value="{{ old('community',$case->community) }}" required></div>
 <div class="field"><label>Sector</label><select name="sector_id" required>@foreach($sectors as $s)<option value="{{ $s->id }}" @selected(old('sector_id',$case->sector_id)==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
 </div>
 <div class="field"><label>Report Title</label><input name="title" required maxlength="200" minlength="5" value="{{ old('title',$case->title) }}"></div>
 <div class="field"><label>Description</label><textarea name="description" required minlength="15" maxlength="10000">{{ old('description',$case->description) }}</textarea></div>
 <div class="field"><label>Observations</label><textarea name="observations" maxlength="10000">{{ old('observations',$case->observations) }}</textarea></div>
 <div class="field"><label>Stakeholders / Duty Bearers</label><textarea name="duty_bearers" maxlength="5000">{{ old('duty_bearers',$case->duty_bearers) }}</textarea></div>
 <div class="grid"><div class="field"><label>Latitude</label><input type="number" step="any" min="-90" max="90" name="latitude" value="{{ old('latitude',$case->latitude) }}"></div><div class="field"><label>Longitude</label><input type="number" step="any" min="-180" max="180" name="longitude" value="{{ old('longitude',$case->longitude) }}"></div></div>
 <div class="field"><label for="revision-reason">Reason for Revision <span aria-hidden="true">*</span></label><textarea id="revision-reason" name="reason" required minlength="5" maxlength="2000" placeholder="Explain what changed and why">{{ old('reason') }}</textarea></div>
 <div class="act-user-actions"><a class="btn-outline" href="{{ route('cases.show',$case) }}">Cancel</a><button type="submit" class="btn"><i class="fa-solid fa-check" aria-hidden="true"></i> Save Revision</button></div>
</form>
@endsection
