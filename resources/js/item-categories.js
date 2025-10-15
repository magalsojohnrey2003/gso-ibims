// resources/js/item-categories.js
(function () {
  'use strict';

  // server should set window.__serverCategories (array of category strings)
  // and window.__serverCategoryPpeMap (object mapping category->ppe)
  const categories = Array.isArray(window.__serverCategories) ? window.__serverCategories : [];
  const ppeMap = (window.__serverCategoryPpeMap && typeof window.__serverCategoryPpeMap === 'object') ? window.__serverCategoryPpeMap : {};

  function normalizeKey(k) {
    return String(k ?? '').trim().toLowerCase();
  }

  function resolvePpeFor(category) {
    if (!category) return '';
    const key = normalizeKey(category);
    // try exact match from map (map keys might be mixed case)
    for (const mk of Object.keys(ppeMap)) {
      if (normalizeKey(mk) === key) return String(ppeMap[mk] ?? '').padStart(2, '0');
    }
    return '';
  }

  function populateSelect(sel) {
    if (!sel) return;
    // preserve current selection
    const current = String(sel.getAttribute('value') ?? sel.value ?? '').trim();
    // clear
    sel.innerHTML = '';
    // add an empty placeholder
    const emptyOpt = document.createElement('option');
    emptyOpt.value = '';
    emptyOpt.textContent = '-- Select category --';
    sel.appendChild(emptyOpt);

    categories.forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat;
      opt.textContent = typeof cat === 'string' ? cat : String(cat);
      sel.appendChild(opt);
    });

    // restore current selection if present
    if (current) {
      sel.value = current;
    }
  }

  function updatePpeForSelect(sel) {
    if (!sel) return;
    const form = sel.closest('form') || document;
    const selected = sel.value || '';
    const ppe = resolvePpeFor(selected) || '';

    // display input (readonly) within same form
    const display = form.querySelector('[data-ppe-display]');
    if (display) display.value = ppe;

    // hidden input for submit (named ppe_code or data-property-segment="ppe")
    const hidden = form.querySelector('input[name="ppe_code"], input[data-property-segment="ppe"]');
    if (hidden) hidden.value = ppe;

    // also update manual rows (if present) - set inputs with data-part="ppe"
    form.querySelectorAll('input[data-part="ppe"]').forEach(inp => {
      inp.value = ppe;
      inp.dispatchEvent(new Event('input', { bubbles: true }));
    });
  }

  function initAll() {
    const sels = Array.from(document.querySelectorAll('select[data-category-select]'));
    sels.forEach(sel => {
      populateSelect(sel);
      // if server placed value in attribute, ensure change handler sees it
      updatePpeForSelect(sel);

      // attach listener
      sel.removeEventListener('change', categoryChangeHandler); // safe
      sel.addEventListener('change', categoryChangeHandler);
    });
  }

  function categoryChangeHandler(e) {
    const sel = e.target;
    updatePpeForSelect(sel);
  }

  // Re-run when modal opens (if your modals dispatch open-modal)
  window.addEventListener('open-modal', (ev) => {
    // small defer so DOM has been adjusted
    setTimeout(initAll, 10);
  });

  document.addEventListener('DOMContentLoaded', initAll);
})();
