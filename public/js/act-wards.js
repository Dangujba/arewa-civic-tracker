(() => {
  'use strict';
  const form = document.querySelector('.report-form[data-no-ajax],.report-form[data-user-id]');
  if (!form) return;

  const lga = form.querySelector('select[name="lga_id"]');
  const box = form.querySelector('[data-ward-picker]');
  if (!lga || !box) return;

  const select = box.querySelector('[data-ward-select]');
  const manual = box.querySelector('[data-ward-manual]');
  const catalog = [...select.options]
    .filter(option => option.dataset.lga)
    .map(option => ({ value: option.value, text: option.textContent.trim(), lga: option.dataset.lga }));
  const norm = value => String(value ?? '').trim().toLocaleLowerCase('en');
  const selectedLga = () => String(lga.value || '');

  // Dedicated Tom Select instance, instead of a duplicate search input.
  // The global selector skips the Ward field via data-no-search.
  // Tom Select handles filtering, keyboard selection, and manual creation.
  if (typeof window.TomSelect === 'function') {
    const initialOptions = [...select.options];
    const cachedValue = box.dataset.initialWard || '';
    // Do not start with wards of other LGAs in the dropdown.
    select.replaceChildren(initialOptions[0]);

    const control = new window.TomSelect(select, {
      create: typed => {
        const value = String(typed ?? '').trim();
        if (!selectedLga() || !value || value.length > 150) return false;
        return { value, text: value };
      },
      createOnBlur: false,
      persist: false,
      maxItems: 1,
      maxOptions: 120,
      closeAfterSelect: true,
      openOnFocus: true,
      hidePlaceholder: true,
      searchField: ['text'],
      sortField: [{ field: 'text', direction: 'asc' }],
      placeholder: 'Select LGA first',
      render: {
        option_create: (data, escape) =>
          '<div class="create">Ward not listed? Add <strong>' + escape(data.input) + '</strong></div>',
        no_results: () => '<div class="no-results">No matching ward. Type its full name to add.</div>'
      }
    });
    box.dataset.enhanced = '1';

    function refresh(lgaChanged = false, retained = '') {
      const location = selectedLga();
      const previous = lgaChanged ? '' : (retained || control.getValue());
      control.clear(true);
      control.clearOptions();
      if (location) {
        control.addOptions(catalog.filter(item => item.lga === location)
          .map(({value,text}) => ({value,text})));
      }
      control.settings.placeholder = location ? 'Search or select ward' : 'Select LGA first';
      control.settings.create = location
        ? (typed => {
            const value = String(typed ?? '').trim();
            return value && value.length <= 150 ? {value,text:value} : false;
          })
        : false;
      if (previous && location) {
        const found = catalog.find(item => item.lga === location && norm(item.value) === norm(previous));
        if (!found) control.addOption({value:previous,text:previous});
        control.setValue(found ? found.value : previous, true);
      }
      control.refreshOptions(false);
      control.updatePlaceholder();
      control.refreshState();
    }
    lga.addEventListener('change', () => refresh(true));
    window.addEventListener('act:ward-restore', event => refresh(false, String(event.detail?.ward ?? '')));
    form.addEventListener('reset', () => queueMicrotask(() => refresh(true)));
    refresh(false, cachedValue);
    return;
  }

  // Native-select fallback where Tom Select could not load.
  const extra = new Option('Ward not listed? Enter manually', '__manual__');
  function nativeFilter(reset = false, prefill = '') {
    const location = selectedLga();
    if (reset) {select.value = '';manual.value = ''}
    for (const option of select.options) {
      if (!option.dataset.lga) continue;
      const show = option.dataset.lga === location;
      option.hidden = !show;
      option.disabled = !show;
    }
    if (!extra.isConnected) select.add(extra);
    extra.disabled = !location;
    select.options[0].textContent = location ? 'Select ward' : 'Select LGA first';
    if (prefill) {
      const exists = catalog.find(item => item.lga === location && norm(item.value) === norm(prefill));
      select.value = exists ? exists.value : '__manual__';
      manual.value = exists ? '' : prefill;
    }
    const manualMode = select.value === '__manual__' && Boolean(location);
    select.disabled = manualMode;
    manual.disabled = !manualMode;
    manual.hidden = !manualMode;
    select.required = !manualMode;
    manual.required = manualMode;
  }
  select.addEventListener('change', () => nativeFilter(false));
  lga.addEventListener('change', () => nativeFilter(true));
  window.addEventListener('act:ward-restore', event => nativeFilter(true, String(event.detail?.ward ?? '')));
  form.addEventListener('reset', () => queueMicrotask(() => nativeFilter(true)));
  nativeFilter(false, box.dataset.initialWard || '');
})();
