@php($initialWard = (string) ($wardValue ?? ''))
<div class="field act-ward-field" data-ward-picker data-initial-ward="{{ $initialWard }}">
  <label for="report-ward-select">Ward <span aria-hidden="true">*</span></label>
  <input type="search" class="act-ward-search" data-ward-search aria-label="Search wards in selected LGA" placeholder="Search wards..." autocomplete="off">
  <select id="report-ward-select" data-ward-select name="ward" required>
    <option value="">Select LGA first</option>
    @foreach($wards as $ward)
      <option value="{{ $ward->name }}" data-lga="{{ $ward->lga_id }}">{{ $ward->name }}</option>
    @endforeach
  </select>
  <input class="act-ward-manual" type="text" name="ward" data-ward-manual maxlength="150" placeholder="Enter ward name" autocomplete="off" disabled hidden>
  <button class="act-ward-switch" type="button" data-ward-switch aria-pressed="false">Ward not listed? Enter manually</button>
  <small class="muted">Ward is recorded separately from the community.</small>
</div>
