(() => {
  'use strict';

  // Quill is a third-party editor. Keep its native Snow theme and toolbar.
  // This adapter only synchronizes standard HTML with the existing report form.
  const editorSource = document.querySelector('textarea[name="description"][data-act-richtext]');
  if (!editorSource || typeof window.Quill !== 'function') return;

  const form = editorSource.closest('form');
  const host = document.createElement('div');
  host.className = 'act-quill-host';
  host.setAttribute('aria-label', 'Description editor');
  editorSource.before(host);

  const notice = document.createElement('p');
  notice.className = 'act-richtext-hint';
  notice.setAttribute('role', 'status');
  host.after(notice);

  const quill = new window.Quill(host, {
    theme: 'snow',
    placeholder: 'Describe the project, observations, location and supporting facts...',
    modules: {
      toolbar: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'link'],
        ['clean']
      ]
    },
    formats: ['header', 'bold', 'italic', 'underline', 'list', 'blockquote', 'link']
  });

  const isMarkup = value => /<\/?(?:p|h2|h3|strong|b|em|i|ul|ol|li|blockquote|a|u|br)\b/i.test(value);
  function initialize(value) {
    if (isMarkup(value)) quill.clipboard.dangerouslyPasteHTML(value, 'silent');
    else quill.setText(value || '', 'silent');
    update();
  }
  function update() {
    const text = quill.getText().trim();
    editorSource.value = text ? quill.getSemanticHTML() : '';
    if (text.length >= 15) notice.textContent = '';
  }
  initialize(editorSource.value);

  // Non-visual backing field is synchronized on text changes and before
  // IndexedDB FormData capture; HTML5 required on hidden fields would block.
  editorSource.required = false;
  editorSource.hidden = true;
  editorSource.dataset.editorReady = '1';
  quill.on('text-change', update);

  form?.addEventListener('submit', event => {
    update();
    const length = quill.getText().trim().length;
    if (length < 15) {
      event.preventDefault();
      event.stopImmediatePropagation();
      notice.textContent = 'Description must contain at least 15 characters of text.';
      quill.focus();
    } else if (editorSource.value.length > 10000) {
      event.preventDefault();
      event.stopImmediatePropagation();
      notice.textContent = 'Description exceeds 10,000 characters including formatting.';
      quill.focus();
    }
  }, true);

  // The existing offline draft recovery sets textarea.value programmatically.
  window.addEventListener('act:richtext-restore', () => initialize(editorSource.value));
  form?.addEventListener('reset', () => queueMicrotask(() => initialize(editorSource.value)));
})();
