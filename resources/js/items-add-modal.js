import { normalizeSegment, resolveCategoryCode } from './property-number';

const SELECTOR = '[data-add-items-form]';
const FEEDBACK_SELECTOR = '[data-add-feedback]';
const ERROR_SELECTOR = '[data-add-error]';
const PREVIEW_WRAP_SELECTOR = '[data-add-preview]';
const PREVIEW_LIST_SELECTOR = '[data-add-preview-list]';
const SUBMIT_SELECTOR = '[data-add-submit]';
const CANCEL_SELECTOR = '[data-add-cancel]';
const OFFICE_ERROR_SELECTOR = '[data-office-error]';
const SERIAL_FEEDBACK_SELECTOR = '[data-serial-feedback]';
const SERIAL_CHECK_ENDPOINT = '/admin/items/check-serial';
const SERIAL_CHECK_DELAY = 300;
const SERIAL_ERROR_CLASSES = ['ring-2', 'ring-red-300', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-300'];
const SERIAL_OK_CLASSES = ['ring-2', 'ring-green-300', 'border-green-500', 'focus:border-green-500', 'focus:ring-green-300'];

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
  ? window.showToast.bind(window)
  : (type, message) => {
      if (type === 'error') console.error(message);
      else console.log(message);
    };

function padSerial(n, width) {
  const w = Math.max(1, width || 1);
  const val = Number.isFinite(n) ? Math.max(0, n) : 0;
  return String(val).padStart(w, '0');
}

function formatSerialConflictMessage(conflicts = []) {
  if (!Array.isArray(conflicts) || conflicts.length === 0) {
    return 'Some serials are already in use. Adjust the starting serial or quantity.';
  }
  const preview = conflicts.slice(0, 5).map((s) => `#${String(s)}`);
  const remaining = conflicts.length - preview.length;
  const suffix = remaining > 0 ? ` and ${remaining} more` : '';
  const label = conflicts.length === 1 ? 'Serial' : 'Serials';
  return `${label} ${preview.join(', ')}${suffix} already in use. Adjust the starting serial or quantity.`;
}

function showMessage(el, message) {
  if (!el) return;
  el.textContent = message;
  el.classList.remove('hidden');
}

function hideMessage(el) {
  if (!el) return;
  el.textContent = '';
  el.classList.add('hidden');
}

function collectBase(form) {
  const fields = {
    year: form.querySelector('[data-add-field="year"]'),
    categoryEl: form.querySelector('[data-category-select]'),
    categoryHidden: form.querySelector('[data-property-segment="category"], input[name="category_code"]'),
    gla: form.querySelector('[data-add-field="gla"], input[name="gla"]'),
    office: form.querySelector('[data-add-field="office"]'),
    serial: form.querySelector('[data-add-field="serial"]'),
    quantity: form.querySelector('[data-add-field="quantity"]'),
  };

  const year = (fields.year?.value || '').toString().trim();
  const categoryValue = (fields.categoryEl?.value ?? '').trim();

  let categoryCodeValue = fields.categoryHidden?.value ?? '';
  if (!categoryCodeValue && categoryValue) {
    categoryCodeValue = resolveCategoryCode(categoryValue) || '';
  }

  const categoryCode = normalizeSegment(categoryCodeValue, 4);
  const glaVal = (fields.gla?.value || '').replace(/\D/g, '').slice(0,6);
  const office = (fields.office?.value || '').toString().trim();
  const rawSerial = (fields.serial?.value || '').replace(/[^0-9]/g, '');
  const serialWidth = Math.max(4, rawSerial.length || 0);
  const serialStart = rawSerial ? parseInt(rawSerial, 10) : Number.NaN;

  let quantity = parseInt(fields.quantity?.value ?? '1', 10);
  if (!Number.isFinite(quantity) || quantity < 1) quantity = 1;
  if (quantity > 500) quantity = 500;

  const ready = Boolean(year.length === 4 && categoryCode && office && !Number.isNaN(serialStart));
  const signature = [year, categoryCode, glaVal, office, serialWidth, serialStart, quantity].join('|');

  return {
    ready,
    year,
    category: categoryValue,
    category_code: categoryCode,
    gla: glaVal,
    office,
    serialWidth,
    serialStart,
    quantity,
    firstSerial: rawSerial ? padSerial(parseInt(rawSerial, 10), serialWidth) : '',
    signature,
  };
}

function buildPreviewData(base) {
  if (!base.ready) return null;
  const count = base.quantity;
  const firstSerialInt = base.serialStart;
  const lastSerialInt = firstSerialInt + (count - 1);
  const firstSerial = padSerial(firstSerialInt, base.serialWidth);
  const lastSerial = padSerial(lastSerialInt, base.serialWidth);

  const firstPn = `${base.year}-${base.category_code}-${(base.gla||'')}-${firstSerial}-${base.office}`;
  const lastPn = `${base.year}-${base.category_code}-${(base.gla||'')}-${lastSerial}-${base.office}`;

  return {
    count,
    firstSerial,
    lastSerial,
    firstPn,
    lastPn,
    isBulk: count > 1,
  };
}

function applySerialState(elements, state, message = '') {
  const input = elements.serialInput;
  const feedback = elements.serialFeedback;
  if (!input) return;

  input.classList.remove(...SERIAL_ERROR_CLASSES, ...SERIAL_OK_CLASSES);
  if (feedback) {
    feedback.classList.add('hidden');
    feedback.classList.remove('text-red-600', 'text-green-600');
    feedback.textContent = '';
  }

  if (state === 'error') {
    input.classList.add(...SERIAL_ERROR_CLASSES);
    input.setCustomValidity('Serial numbers are already in use.');
    if (feedback) {
      feedback.textContent = message || 'Some serials are already in use. Adjust the starting serial or quantity.';
      feedback.classList.remove('hidden');
      feedback.classList.add('text-red-600');
    }
    return;
  }

  input.setCustomValidity('');
  if (state === 'valid') {
    input.classList.add(...SERIAL_OK_CLASSES);
    if (feedback) feedback.classList.add('hidden');
  }
}

async function fetchSerialConflicts(base, { signal } = {}) {
  if (!base.ready || !base.firstSerial) {
    return { available: true, conflict_serials: [] };
  }

  const params = new URLSearchParams({
    year_procured: base.year,
    office_code: base.office,
    start_serial: base.firstSerial,
    quantity: String(base.quantity),
  });
  if (base.category_code) params.append('category_code', base.category_code);

  const response = await fetch(`${SERIAL_CHECK_ENDPOINT}?${params.toString()}`, {
    headers: { Accept: 'application/json' },
    signal,
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok) {
    throw new Error(payload?.message || 'Unable to validate serial numbers right now.');
  }
  return payload || { available: true, conflict_serials: [] };
}

function queueSerialCheck(form, elements, base) {
  const input = elements.serialInput;
  if (!input) return;

  if (elements.serialTimer) {
    clearTimeout(elements.serialTimer);
    elements.serialTimer = null;
  }
  if (elements.serialAbort) {
    elements.serialAbort.abort();
    elements.serialAbort = null;
  }

  if (!base.ready || !base.firstSerial) {
    elements.lastSerialResult = null;
    applySerialState(elements, 'idle');
    return;
  }

  elements.serialTimer = window.setTimeout(async () => {
    const controller = new AbortController();
    elements.serialAbort = controller;
    try {
      const result = await fetchSerialConflicts(base, { signal: controller.signal });
      const conflicts = Array.isArray(result.conflict_serials) ? result.conflict_serials : [];
      const available = conflicts.length === 0;
      elements.lastSerialResult = { signature: base.signature, available, conflicts };
      if (available) {
        applySerialState(elements, 'valid');
      } else {
        const msg = formatSerialConflictMessage(conflicts);
        applySerialState(elements, 'error', msg);
      }
    } catch (error) {
      if (controller.signal.aborted) return;
      elements.lastSerialResult = null;
      const msg = error?.message || 'Unable to validate serial numbers right now.';
      applySerialState(elements, 'error', msg);
    } finally {
      elements.serialAbort = null;
      elements.serialTimer = null;
    }
  }, SERIAL_CHECK_DELAY);
}

async function ensureSerialAvailability(elements, base) {
  if (!elements.serialInput || !base.ready || !base.firstSerial) {
    return { available: true, conflicts: [] };
  }
  if (elements.lastSerialResult && elements.lastSerialResult.signature === base.signature) {
    return elements.lastSerialResult;
  }
  const result = await fetchSerialConflicts(base);
  const conflicts = Array.isArray(result.conflict_serials) ? result.conflict_serials : [];
  const available = conflicts.length === 0;
  elements.lastSerialResult = { signature: base.signature, available, conflicts };
  return elements.lastSerialResult;
}

function renderPreview(form, base, elements) {
  const wrap = elements.previewWrap;
  const list = elements.previewList;
  if (!wrap || !list) return;

  const data = base ? buildPreviewData(base) : null;
  if (!data) {
    wrap.classList.add('hidden');
    list.innerHTML = '';
    return;
  }

  wrap.classList.remove('hidden');
  const rows = [];
  if (data.isBulk) {
    rows.push(`<div><span class="font-semibold">Total:</span> ${data.count} items</div>`);
    rows.push(`<div><span class="font-semibold">Serial range:</span> ${data.firstSerial} → ${data.lastSerial}</div>`);
    rows.push(`<div><span class="font-semibold">First PN:</span> ${data.firstPn}</div>`);
    rows.push(`<div><span class="font-semibold">Last PN:</span> ${data.lastPn}</div>`);
  } else {
    rows.push(`<div><span class="font-semibold">Property Number:</span> ${data.firstPn}</div>`);
  }
  list.innerHTML = rows.join('');

  const previewInput = form.querySelector('[data-property-preview]');
  if (previewInput) previewInput.value = data.firstPn;
}

function toggleLoading(elements, state) {
  const btn = elements.submitBtn;
  if (!btn) return;
  btn.disabled = state;
  btn.dataset.loading = state ? 'true' : 'false';
}

async function submitForm(form) {
  const action = form.getAttribute('action') || window.location.href;
  const method = (form.getAttribute('method') || 'POST').toUpperCase();
  const formData = new FormData(form);

  const response = await fetch(action, {
    method,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: formData,
  });

async function submitForm(form) {
  const action = form.getAttribute('action') || window.location.href;
  const method = (form.getAttribute('method') || 'POST').toUpperCase();
  const formData = new FormData(form);

  const response = await fetch(action, {
    method,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: formData,
  });

  const contentType = response.headers.get('content-type') || '';
  const isJson = contentType.includes('application/json');

  // If response is JSON, parse it. If not, get text (server probably returned HTML / error page).
  let payload = null;
  let rawText = null;
  if (isJson) {
    payload = await response.json().catch(() => null);
  } else {
    rawText = await response.text().catch(() => null);
  }

  if (response.ok) {
    return { ok: true, status: response.status, data: payload, raw: rawText };
  }

  // For 409/422 prefer JSON message structure, but fall back to rawText if present
  if ((response.status === 409 || response.status === 422) && (payload || rawText)) {
    const messages = [];
    if (payload?.errors) {
      Object.values(payload.errors).forEach((arr) => { if (Array.isArray(arr)) messages.push(...arr); });
    }
    if (payload?.message) messages.push(payload.message);
    if (messages.length === 0 && rawText) {
      // strip simple HTML tags for readability (keep it small)
      const stripped = rawText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      messages.push(stripped || 'Validation failed.');
    }
    throw new Error(messages.join(' ') || 'Validation failed.');
  }

  // Generic non-ok path: try JSON.message then fallback to stripped raw HTML or status
  const messageFromJson = payload?.message;
  if (messageFromJson) {
    throw new Error(messageFromJson);
  }
  if (rawText) {
    const stripped = rawText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    throw new Error(stripped || `Request failed (${response.status}).`);
  }

  throw new Error(`Request failed (${response.status}).`);
}
}
function handleSuccess(form, elements, result) {
  const data = result?.data || {};
  const hasSkipped = Array.isArray(data.skipped_serials) && data.skipped_serials.length > 0;
  const toastMessage = hasSkipped ? 'Items saved. Some serials are already in use.' : 'Items saved successfully.';
  showToast('success', toastMessage);

  hideMessage(elements.error);
  hideMessage(elements.feedback);

  form.reset();
  form.dispatchEvent(new Event('reset'));
  window.dispatchEvent(new CustomEvent('close-modal', { detail: 'create-item' }));
  window.location.reload();
}

function handleError(elements, error) {
  const fallback = 'Unable to save item. Please try again.';
  const message = (error && typeof error.message === 'string' && error.message.trim()) ? error.message : fallback;
  showToast('error', message);
  hideMessage(elements.feedback);
  showMessage(elements.error, message);
}

function attachOfficeValidation(form) {
  const input = form.querySelector('[data-add-field="office"]');
  const errorEl = form.querySelector(OFFICE_ERROR_SELECTOR);
  if (!input || !errorEl) return;

  const validate = () => {
    const value = (input.value || '').trim();
    const isEmpty = value === '';

    const isValid = /^[A-Za-z0-9]{1,4}$/.test(value);

    if (isEmpty) {
      input.setCustomValidity('');
      errorEl.classList.add('hidden');
      return;
    }

    if (!isValid) {
      input.setCustomValidity('Office Code must be 1 to 4 alphanumeric characters.');
      errorEl.textContent = 'Office code must be 1–4 alphanumeric characters.';
      errorEl.classList.remove('hidden');
    } else {
      input.setCustomValidity('');
      errorEl.classList.add('hidden');
    }
  };

  input.addEventListener('input', validate);
  input.addEventListener('blur', validate);
  form.addEventListener('reset', () => {
    input.setCustomValidity('');
    errorEl.classList.add('hidden');
  });

  validate();
}

function populateCategorySelects(form) {
  const cats = Array.isArray(window.__serverCategories) ? window.__serverCategories : [];
  form.querySelectorAll('select[data-category-select]').forEach(sel => {
    sel.innerHTML = '';
    if (!cats.length) {
      sel.appendChild(new Option('-- No categories --', ''));
      sel.disabled = true;
      return;
    }
    sel.appendChild(new Option('- Select Category -', ''));
    cats.forEach(c => {
       if (typeof c === 'object' && c !== null) sel.appendChild(new Option(c.name, c.id));
       else sel.appendChild(new Option(c, c));
     });
    sel.disabled = false;
  });
}

function populateOfficeSelects(form) {
  const offices = Array.isArray(window.__serverOffices) ? window.__serverOffices : [];
  form.querySelectorAll('select[data-office-select]').forEach(sel => {
    sel.innerHTML = '';
    if (!offices.length) {
      sel.appendChild(new Option('-- No offices --', ''));
      sel.disabled = true;
      return;
    }
    sel.appendChild(new Option('- Select Office -', ''));
    offices.forEach(o => {
      const code = typeof o === 'object' ? (o.code ?? '') : o;
      const label = typeof o === 'object' ? (o.name ?? code) : o;
      sel.appendChild(new Option(label, code));
    });
    sel.disabled = false;
  });
}

function attachCategoryListeners(form) {
  form.querySelectorAll('select[data-category-select]').forEach(sel => {
    sel.addEventListener('change', (e) => {
      const chosen = (e.target.value ?? '').toString();
      const hidden = form.querySelector('input[data-property-segment="category"], input[name="category_code"]');
      if (hidden) {
        const resolved = resolveCategoryCode(chosen) || '';
        hidden.value = resolved;
        try { hidden.defaultValue = resolved; } catch(_) {}
        hidden.setAttribute('value', resolved);
      }
      const display = form.querySelector('[data-category-display]');
      if (display) {
        const resolved = resolveCategoryCode(chosen) || '';
        display.value = resolved;
      }

      form.querySelectorAll('[data-add-field]').forEach(inp => inp.dispatchEvent(new Event('input', { bubbles: true })));
      form.querySelectorAll('[data-add-field="year"], [data-add-field="office"]').forEach(inp => inp.dispatchEvent(new Event('input', { bubbles: true })));
    });
  });
}

function initAddItemsForm(form) {
  populateCategorySelects(form);
  populateOfficeSelects(form);
  attachCategoryListeners(form);
  const elements = {
    feedback: form.querySelector(FEEDBACK_SELECTOR),
    error: form.querySelector(ERROR_SELECTOR),
    previewWrap: form.querySelector(PREVIEW_WRAP_SELECTOR),
    previewList: form.querySelector(PREVIEW_LIST_SELECTOR),
    submitBtn: form.querySelector(SUBMIT_SELECTOR),
    cancelBtn: form.querySelector(CANCEL_SELECTOR),
    serialInput: form.querySelector('[data-add-field="serial"]'),
    serialFeedback: form.querySelector(SERIAL_FEEDBACK_SELECTOR),
    serialTimer: null,
    serialAbort: null,
    lastSerialResult: null,
  };

  window.addEventListener('server:categories:updated', () => { populateCategorySelects(form); });
  window.addEventListener('server:offices:updated', () => { populateOfficeSelects(form); });
  populateCategorySelects(form);
  attachCategoryListeners(form);

  attachOfficeValidation(form);

  const updateState = () => {
    const base = collectBase(form);
    renderPreview(form, base, elements);
    queueSerialCheck(form, elements, base);
  };

  form.querySelectorAll('[data-add-field]').forEach((input) => {
    input.addEventListener('input', updateState);
    input.addEventListener('change', updateState);
  });

  form.addEventListener('reset', () => {
    elements.lastSerialResult = null;
    if (elements.serialTimer) { clearTimeout(elements.serialTimer); elements.serialTimer = null; }
    if (elements.serialAbort) { elements.serialAbort.abort(); elements.serialAbort = null; }
    applySerialState(elements, 'idle');
  });

  elements.cancelBtn?.addEventListener('click', () => {
    form.reset();
    form.dispatchEvent(new Event('reset'));
    const previewList = form.querySelector(PREVIEW_LIST_SELECTOR);
    const previewWrap = form.querySelector(PREVIEW_WRAP_SELECTOR);
    if (previewList) previewList.innerHTML = '';
    if (previewWrap) previewWrap.classList.add('hidden');
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    hideMessage(elements.feedback);
    hideMessage(elements.error);

    const base = collectBase(form);
    if (!base.ready) {
      const msg = 'Complete year, category code, serial, and office code before saving.';
      showToast('error', msg);
      handleError(elements, new Error(msg));
      return;
    }

    toggleLoading(elements, true);
    try {
      const serialResult = await ensureSerialAvailability(elements, base);
      if (serialResult && serialResult.available === false) {
        const msg = formatSerialConflictMessage(serialResult.conflicts || serialResult.conflict_serials || []);
        applySerialState(elements, 'error', msg);
        showToast('error', msg);
        handleError(elements, new Error(msg));
        return;
      }

      const result = await submitForm(form);
      handleSuccess(form, elements, result);
    } catch (error) {
      console.error(error);
      const msg = error?.message || 'Unable to validate serial numbers right now.';
      applySerialState(elements, 'error', msg);
      showToast('error', msg);
      handleError(elements, error);
    } finally {
      toggleLoading(elements, false);
    }
  });

  updateState();
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[name="office_code"], input[data-add-field="office"], input[data-edit-field="office"], input.instance-part-office').forEach(inp => {
    inp.addEventListener('input', () => {
      inp.value = String(inp.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 4);
    });
  });

  document.querySelectorAll('input[name="start_serial"], input[data-add-field="serial"], input[data-edit-field="serial"], input.instance-part-serial').forEach(inp => {
    inp.addEventListener('input', () => {
      inp.value = String(inp.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 5);
    });
  });

  // GLA sanitizers: digits only, max 4 chars
  document.querySelectorAll('input[name="gla"], input[data-add-field="gla"], input.instance-part-gla').forEach(inp => {
    inp.addEventListener('input', () => {
      inp.value = String(inp.value || '').replace(/\D/g, '').slice(0, 4);
    });
    inp.addEventListener('blur', () => {
      inp.value = String(inp.value || '').replace(/\D/g, '').slice(0, 4);
    });
  });

  document.querySelectorAll('input[name="year_procured"], input[data-manual-config="year"], input[data-property-segment="year"], input[data-edit-field="year"]').forEach(inp => {
    inp.addEventListener('input', () => {
      inp.value = String(inp.value || '').replace(/\D/g, '').slice(0,4);
      if (inp.value.length === 4) {
        const y = parseInt(inp.value, 10);
        if (isNaN(y) || y < 2020) {
          inp.value = '';
          showToast('error', 'Year must be 2020 or later.');
        }
      }
    });
  });
});


document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll(SELECTOR).forEach(initAddItemsForm);
});