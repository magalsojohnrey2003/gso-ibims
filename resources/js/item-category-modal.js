// resources/js/item-category-modal.js
// Category / PPE modal manager (Add / Edit / Delete + server persist + localStorage fallback)

(function () {
  // Configurable keys (no Blade here; Blade partial sets window.__savePpeRoute and window.__serverCategoryPpeMap)
  const STORAGE_KEY = 'inventory.customCategories';
  const SAVE_ENDPOINT = window.__savePpeRoute || '/admin/items/ppe/save';
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // DOM helpers
  const $ = (s, ctx = document) => ctx.querySelector(s);
  const $$ = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));

  // Load initial base map from the server-injected global (set by Blade)
  let baseMap = typeof window.__serverCategoryPpeMap === 'object' && window.__serverCategoryPpeMap !== null
    ? Object.assign({}, window.__serverCategoryPpeMap)
    : {};
  Object.keys(baseMap).forEach(k => baseMap[k] = String(baseMap[k]));

  // Load customMap from localStorage (fallback)
  let customMap = {};
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        parsed.forEach(e => {
          if (e && e.name) customMap[String(e.name)] = String(e.ppe || '');
        });
      } else if (typeof parsed === 'object' && parsed !== null) {
        Object.keys(parsed).forEach(k => customMap[k] = String(parsed[k]));
      }
    }
  } catch (err) {
    console.warn('Failed to read custom categories from localStorage', err);
  }

  function mergedMap() {
    return Object.assign({}, baseMap, customMap);
  }

  function renderTable() {
    const tbody = $('#ppeTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    const map = mergedMap();
    const keys = Object.keys(map).sort((a,b) => a.toLowerCase().localeCompare(b.toLowerCase()));
    if (keys.length === 0) {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td colspan="3" class="px-4 py-6 text-center text-gray-500">No PPE codes</td>';
      tbody.appendChild(tr);
      return;
    }
    keys.forEach(cat => {
      const ppe = String(map[cat] ?? '');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 py-3 align-top">
          <div class="font-medium">${escapeHtml(cat)}</div>
        </td>
        <td class="px-4 py-3 align-top">
          <div class="inline-block px-2 py-1 rounded-md text-xs font-semibold bg-gray-100">${escapeHtml(ppe)}</div>
        </td>
        <td class="px-4 py-3 text-right align-top space-x-2">
          <button type="button" class="text-blue-600 underline text-sm" data-edit="${escapeHtml(cat)}">Edit</button>
          <button type="button" class="text-red-600 underline text-sm" data-delete="${escapeHtml(cat)}">Delete</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  function escapeHtml(s) {
    return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
  }

  function openEdit(categoryName) {
    const map = mergedMap();
    const ppe = map[categoryName] ?? '';
    const modeEl = $('#ppe_mode');
    const keyEl = $('#ppe_edit_key');
    const nameEl = $('#category_name');
    const ppeEl = $('#category_ppe');

    if (!modeEl || !keyEl || !nameEl || !ppeEl) return;

    modeEl.value = 'edit';
    keyEl.value = categoryName;
    nameEl.value = categoryName;
    ppeEl.value = ppe;
    clearErrors();
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-ppe' }));
  }

  function openAdd() {
    const modeEl = $('#ppe_mode');
    const keyEl = $('#ppe_edit_key');
    const nameEl = $('#category_name');
    const ppeEl = $('#category_ppe');
    if (!modeEl || !keyEl || !nameEl || !ppeEl) return;

    modeEl.value = 'add';
    keyEl.value = '';
    nameEl.value = '';
    ppeEl.value = '';
    clearErrors();
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-ppe' }));
  }

  async function deleteCategory(categoryName) {
    if (!confirm(`Delete category "${categoryName}"? This will remove any custom PPE mapping for it.`)) return;

    if (Object.prototype.hasOwnProperty.call(customMap, categoryName)) {
      delete customMap[categoryName];
    } else {
      // If category only exists in baseMap, we simply remove custom override (no-op).
      // To physically remove from baseMap you'd need server-side change; we leave base unchanged.
    }

    const success = await persistCustomMap();
    if (!success) {
      persistToLocalStorage();
    }
    renderTable();
    triggerCategoryRerender();
  }

  async function saveHandler() {
    clearErrors();
    const mode = $('#ppe_mode')?.value || 'add';
    const editKey = $('#ppe_edit_key')?.value || '';
    const name = String($('#category_name')?.value || '').trim();
    let ppe = String($('#category_ppe')?.value || '').trim();

    // validations
    let ok = true;
    if (!name) {
      showError('category_name_error', 'Category name is required.');
      ok = false;
    }
    if (!/^\d{2}$/.test(ppe)) {
      showError('category_ppe_error', 'PPE code must be exactly 2 digits (e.g. 08).');
      ok = false;
    }
    if (!ok) return;

    // duplicate name check
    const map = mergedMap();
    const lower = name.toLowerCase();
    const duplicate = Object.keys(map).some(k => k.toLowerCase() === lower && (mode === 'add' || k !== editKey));
    if (duplicate) {
      showError('category_name_error', 'Category already exists.');
      return;
    }

    // If editing and name changed, remove old custom key if exists
    if (mode === 'edit' && editKey && editKey !== name) {
      if (Object.prototype.hasOwnProperty.call(customMap, editKey)) {
        delete customMap[editKey];
      }
    }

    customMap[name] = ppe;

    const success = await persistCustomMap();
    if (!success) {
      persistToLocalStorage();
      if (typeof window.showToast === 'function') {
        window.showToast('warning', 'Saved locally. Server save failed — changes will not be shared across devices.');
      } else {
        console.warn('Server save failed; persisted to localStorage.');
      }
    } else {
      if (typeof window.showToast === 'function') {
        window.showToast('success', 'Category saved.');
      }
    }

    renderTable();
    triggerCategoryRerender();
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'manage-ppe' }));
  }

  async function persistCustomMap() {
    const payload = Object.assign({}, customMap);
    try {
      const res = await fetch(SAVE_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });

      if (!res.ok) {
        const json = await res.json().catch(() => ({}));
        console.warn('Server save failed', res.status, json);
        return false;
      }

      localStorage.removeItem(STORAGE_KEY);
      return true;
    } catch (err) {
      console.error('Failed to persist PPE codes', err);
      return false;
    }
  }

  function persistToLocalStorage() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(customMap));
    } catch (err) {
      console.warn('Failed to persist to localStorage', err);
    }
  }

  function showError(id, msg) {
    const el = $('#' + id);
    if (el) {
      el.textContent = msg;
      el.classList.remove('hidden');
    }
  }

  function clearErrors() {
    ['category_name_error','category_ppe_error'].forEach(id => {
      const el = $('#' + id);
      if (el) { el.textContent = ''; el.classList.add('hidden'); }
    });
  }

  function triggerCategoryRerender() {
    const map = mergedMap();
    window.__clientCategoryPpeMap = map;
    window.dispatchEvent(new CustomEvent('categories:changed', { detail: map }));
  }

  // DOM event delegation for edit/delete
  document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('[data-edit]');
    const delBtn = e.target.closest('[data-delete]');
    if (editBtn) {
      const cat = editBtn.getAttribute('data-edit');
      openEdit(cat);
      return;
    }
    if (delBtn) {
      const cat = delBtn.getAttribute('data-delete');
      deleteCategory(cat);
      return;
    }
  });

  // wire Save button (guard if element missing)
  const saveBtn = document.getElementById('savePpeBtn');
  if (saveBtn) saveBtn.addEventListener('click', saveHandler);

  // Expose openAdd to global so the floating action can reuse it
  window.openAddPpeCategory = openAdd;

  // Initial render
  document.addEventListener('DOMContentLoaded', () => {
    renderTable();
  });

})();

// categories-renderer.js
// Place at bottom of item-category-modal.js or import from app.js

(function () {
  const SELECTOR = 'select[data-category-select]';
  const STORAGE_KEY = 'inventory.customCategories';

  // helpers
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from((ctx || document).querySelectorAll(sel));
  const escape = s => String(s ?? '');

  // Read localStorage fallback (supports array of {name,ppe} or object map)
  function readLocalStorageMap() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      const map = {};
      if (Array.isArray(parsed)) {
        parsed.forEach(e => { if (e && e.name) map[String(e.name)] = String(e.ppe ?? ''); });
      } else if (typeof parsed === 'object' && parsed !== null) {
        Object.keys(parsed).forEach(k => map[k] = String(parsed[k]));
      }
      return map;
    } catch (err) {
      console.warn('Failed to parse localStorage categories', err);
      return {};
    }
  }

  // Build merged map and ordered names
  function buildMergedMap() {
    const client = (typeof window.__clientCategoryPpeMap === 'object' && window.__clientCategoryPpeMap) ? window.__clientCategoryPpeMap : {};
    const serverMap = (typeof window.__serverCategoryPpeMap === 'object' && window.__serverCategoryPpeMap) ? window.__serverCategoryPpeMap : {};
    const serverCatsArr = Array.isArray(window.__serverCategories) ? window.__serverCategories : [];
    const localMap = readLocalStorageMap();

    // Start with server categories array to capture preferred casing if available
    const resultMap = {};
    const order = [];

    // Add server categories array first (preserve casing if present)
    serverCatsArr.forEach(name => {
      if (!name) return;
      const key = String(name);
      if (!Object.prototype.hasOwnProperty.call(resultMap, key)) {
        resultMap[key] = String(serverMap[key] ?? serverMap[key.toLowerCase()] ?? '');
        order.push(key);
      }
    });

    // Add keys from serverMap (if not present)
    Object.keys(serverMap).forEach(k => {
      // prefer readable casing: if k exists in resultMap keyed by same string already skip
      if (order.some(o => o.toLowerCase() === k.toLowerCase())) return;
      const pretty = k.charAt(0).toUpperCase() + k.slice(1);
      resultMap[pretty] = String(serverMap[k] ?? '');
      order.push(pretty);
    });

    // Add client overrides/entries (these should override server)
    Object.keys(client).forEach(k => {
      // if name exists (case-insensitive) replace value while preserving casing found in order
      const existingIndex = order.findIndex(o => o.toLowerCase() === k.toLowerCase());
      if (existingIndex !== -1) {
        const existingName = order[existingIndex];
        resultMap[existingName] = String(client[k] ?? '');
      } else {
        resultMap[k] = String(client[k] ?? '');
        order.push(k);
      }
    });

    // Add localStorage-only ones (if not already present)
    Object.keys(localMap).forEach(k => {
      const existingIndex = order.findIndex(o => o.toLowerCase() === k.toLowerCase());
      if (existingIndex !== -1) {
        const existingName = order[existingIndex];
        // only set if not already set by client/server
        if (!resultMap[existingName]) resultMap[existingName] = String(localMap[k] ?? '');
      } else {
        resultMap[k] = String(localMap[k] ?? '');
        order.push(k);
      }
    });

    // final: normalize order alphabetically (but keep existing order if you prefer server-first)
    const sorted = [...order].sort((a,b) => a.toLowerCase().localeCompare(b.toLowerCase()));
    // build final map in sorted order but preserve value mapping for casing
    const finalMap = {};
    sorted.forEach(name => { finalMap[name] = resultMap[name] ?? ''; });

    return finalMap;
  }

  // Render all select[data-category-select]
  function renderCategorySelects() {
    const map = buildMergedMap();
    const names = Object.keys(map);

    $$('select[data-category-select]').forEach(select => {
      const current = select.value;
      // Clear existing options
      select.innerHTML = '';

      // placeholder option
      const ph = document.createElement('option');
      ph.value = '';
      ph.textContent = '-- Select --';
      select.appendChild(ph);

      // create options
      names.forEach(name => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name.charAt(0).toUpperCase() + name.slice(1);
        const ppe = map[name] ?? '';
        if (ppe) opt.setAttribute('data-ppe-code', ppe);
        if (String(current) !== '' && current === name) {
          opt.selected = true;
        }
        // attempt case-insensitive match if select had current different case
        if (!opt.selected && String(current) !== '' && current.toLowerCase() === name.toLowerCase()) {
          opt.selected = true;
        }
        select.appendChild(opt);
      });

      // if previous value not found, keep placeholder selected (or attempt to re-select after)
      // dispatch change so any bound listeners update PPE display
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  // initial run + listeners
  document.addEventListener('DOMContentLoaded', () => {
    try { renderCategorySelects(); } catch (err) { console.error('Failed to render category selects', err); }
  });

  // re-render when categories change (our modal emits this event)
  document.addEventListener('categories:changed', (e) => {
    try { renderCategorySelects(); } catch (err) { console.error('Failed to re-render category selects', err); }
  });

  // also re-render if storage changes in another tab (helps fallback localStorage case)
  window.addEventListener('storage', (e) => {
    if (e.key === STORAGE_KEY) {
      try { renderCategorySelects(); } catch (err) { console.error('Failed to re-render category selects from storage event', err); }
    }
  });

  // expose for debugging
  window.renderCategorySelects = renderCategorySelects;
})();

// --- category->PPE binder ---
// Append this to the end of item-category-modal.js (or include in app.js)
// Keeps <select data-category-select> in sync with [data-ppe-display] and hidden [data-property-segment="ppe"]

(function () {
  const SELECTOR = 'select[data-category-select]';

  function readLocalStorageMap() {
    try {
      const raw = localStorage.getItem('inventory.customCategories');
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      const out = {};
      if (Array.isArray(parsed)) {
        parsed.forEach(e => { if (e && e.name) out[String(e.name)] = String(e.ppe ?? ''); });
      } else if (parsed && typeof parsed === 'object') {
        Object.keys(parsed).forEach(k => out[k] = String(parsed[k] ?? ''));
      }
      return out;
    } catch (err) {
      console.warn('readLocalStorageMap error', err);
      return {};
    }
  }

  function buildLookupMap() {
    const client = (typeof window.__clientCategoryPpeMap === 'object' && window.__clientCategoryPpeMap) ? window.__clientCategoryPpeMap : {};
    const server = (typeof window.__serverCategoryPpeMap === 'object' && window.__serverCategoryPpeMap) ? window.__serverCategoryPpeMap : {};
    const local = readLocalStorageMap();

    // Build case-insensitive map: keyLower -> { keyOriginal, ppe }
    const map = {};

    const addToMap = (name, ppe) => {
      if (!name) return;
      const keyLower = String(name).toLowerCase();
      map[keyLower] = { name: String(name), ppe: String(ppe ?? '') };
    };

    // server keys (use server's casing when possible)
    Object.keys(server).forEach(k => addToMap(k, server[k]));
    // client overrides / additions (overwrite)
    Object.keys(client).forEach(k => addToMap(k, client[k]));
    // localStorage fallback (if not present)
    Object.keys(local).forEach(k => {
      const kk = String(k).toLowerCase();
      if (!map[kk]) addToMap(k, local[k]);
    });

    return map; // map by lowercase name
  }

  function resolvePpeForCategory(catName) {
    if (!catName) return '';
    const map = buildLookupMap();
    const rec = map[String(catName).toLowerCase()];
    if (rec && rec.ppe) return String(rec.ppe);
    return '';
  }

  function updatePpeForSelect(select) {
    if (!select) return;
    const chosen = select.value || '';
    const form = select.closest('form') || document;
    const ppeDisplay = form.querySelector('[data-ppe-display]');
    const ppeHidden = form.querySelector('input[data-property-segment="ppe"], input[name="ppe_code"]');

    const ppe = resolvePpeForCategory(chosen);
    if (ppeDisplay) {
      ppeDisplay.value = ppe;
      // dispatch input so preview listeners run
      ppeDisplay.dispatchEvent(new Event('input', { bubbles: true }));
      ppeDisplay.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (ppeHidden) {
      ppeHidden.value = ppe;
      try { ppeHidden.setAttribute('value', ppe); } catch (_) {}
    }
  }

  // Attach listeners to a given select element (idempotent)
  function bindSelect(select) {
    if (!select) return;
    // guard: don't double-bind
    if (select.__ppeBound) return;
    select.__ppeBound = true;
    select.addEventListener('change', () => {
      updatePpeForSelect(select);
    });

    // also update immediately (for initial render)
    updatePpeForSelect(select);
  }

  function bindAllCategorySelects() {
    document.querySelectorAll(SELECTOR).forEach(bindSelect);
  }

  // initial bind on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    try { bindAllCategorySelects(); } catch (err) { console.error('bindAllCategorySelects error', err); }
  });

  // Rebind when categories change (modal emits categories:changed)
  document.addEventListener('categories:changed', () => {
    try { bindAllCategorySelects(); } catch (err) { console.error('categories:changed binder error', err); }
  });

  // Rebind on storage events (fallback cases)
  window.addEventListener('storage', (e) => {
    if (e.key === 'inventory.customCategories') {
      try { bindAllCategorySelects(); } catch (err) { console.error('storage binder error', err); }
    }
  });

  // Expose helper for debugging if needed
  window.updatePpeForSelect = updatePpeForSelect;
})();

function truncateCategoryOptionText(maxChars = 25) {
  document.querySelectorAll('select[data-category-select]').forEach(select => {
    select.querySelectorAll('option').forEach(opt => {
      const txt = opt.textContent || '';
      if (txt.length > maxChars) {
        opt.title = txt; // keep full text on hover
        opt.textContent = txt.slice(0, maxChars - 1).trim() + '…';
      } else {
        opt.title = '';
      }
    });
  });
}
// call after renderCategorySelects()
truncateCategoryOptionText(28);
document.addEventListener('categories:changed', () => truncateCategoryOptionText(28));
