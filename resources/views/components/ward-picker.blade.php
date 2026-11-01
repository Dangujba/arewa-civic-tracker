@php($initialWard = (string) ($wardValue ?? ''))
<div class="field act-ward-field" data-ward-picker data-initial-ward="{{ $initialWard }}">
  <label for="report-ward-select">Ward <span aria-hidden="true">*</span></label>
  <select id="report-ward-select" data-ward-select data-no-search name="ward" required aria-label="Select or search Ward" @disabled(false)>
    <option value="">Select LGA first</option>
    @foreach($wards as $ward)
      <option value="{{ $ward->name }}" data-lga="{{ $ward->lga_id }}">{{ $ward->name }}</option>
    @endforeach
  </select>
  <input class="act-ward-manual" type="text" name="ward" data-ward-manual maxlength="150" placeholder="Enter ward name" autocomplete="off" disabled hidden>
</div>
