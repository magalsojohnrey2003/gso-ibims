// resources/js/item-edit.js
const SELECTOR = '[data-edit-item-form]';
const FEEDBACK_SELECTOR = '[data-edit-feedback]';
const ERROR_SELECTOR = '[data-edit-error]';
const SUBMIT_SELECTOR = '[data-edit-submit]';
const CANCEL_SELECTOR = '[data-edit-cancel]';
const OFFICE_ERROR_SELECTOR = '[data-office-error]';

async function showActionConfirm(title, message, icon = '') {
  if (typeof window.showModalConfirm === 'function') {
    return await window.showModalConfirm({ title, message, icon });
  }
  return Promise.resolve(window.confirm(message));
}

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
  ? window.showToast.bind(window)
  : (type, message) => {
      if (type === 'error') console.error(message);
      else console.log(message);
    };

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

function toggleLoading(btn, state) {
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

    const contentType = response.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    const payload = isJson ? await response.json() : null;

    if (response.ok) {
        return { ok: true, status: response.status, data: payload };
    }

    if ((response.status === 409 || response.status === 422) && payload) {
        const messages = [];
        if (payload.errors) {
            Object.values(payload.errors).forEach((arr) => {
                if (Array.isArray(arr)) messages.push(...arr);
            });
        }
        if (payload.message) messages.push(payload.message);
        throw new Error(messages.join(' ') || 'Validation failed.');
    }

    const message = payload?.message || `Request failed (${response.status}).`;
    throw new Error(message);
}

function handleSuccess(form, feedbackEl, errorEl, result) {
    hideMessage(errorEl);

    const data = result?.data || {};
    const message = data.message || 'Item updated.';
    showMessage(feedbackEl, message);
    try { showToast('success', message); } catch (_) {}

    try {
        const setInput = (selector, val) => {
            const el = form.querySelector(selector);
            if (!el) return;
            try { el.value = val ?? ''; } catch (_) {}
            try { el.defaultValue = val ?? ''; } catch (_) {}
            try { el.setAttribute('value', val ?? ''); } catch (_) {}
        };

        if (typeof data.name === 'string') {
            const nameEl = form.querySelector('[data-edit-field="name"]') || form.querySelector('[name="name"]');
            if (nameEl) { nameEl.value = data.name; nameEl.defaultValue = data.name; nameEl.setAttribute('value', data.name); }
        }

        if (typeof data.office_code === 'string') {
            const officeEl = form.querySelector('[data-edit-field="office"]') || form.querySelector('[name="office_code"]');
            if (officeEl) {
                officeEl.value = data.office_code;
                officeEl.defaultValue = data.office_code;
                officeEl.setAttribute('value', data.office_code);
            }
        }

        if (typeof data.year_procured !== 'undefined') {
            const yEl = form.querySelector('[data-edit-field="year"]') || form.querySelector('[name="year_procured"]');
            if (yEl) {
                const sval = String(data.year_procured ?? '');
                yEl.value = sval;
                yEl.defaultValue = sval;
                yEl.setAttribute('value', sval);
            }
        }

        if (typeof data.serial === 'string' || (typeof data.serial !== 'undefined' && data.serial !== null)) {
            const sEl = form.querySelector('[data-edit-field="serial"]') || form.querySelector('[name="serial"]');
            if (sEl && typeof data.serial === 'string') {
                sEl.value = data.serial;
                sEl.defaultValue = data.serial;
                sEl.setAttribute('value', data.serial);
            }
        }

        // Category code: update hidden/display if provided
        if (typeof data.category_code === 'string' || typeof data.category === 'string') {
            const codeVal = (data.category_code ?? data.category ?? '');
            const hidden = form.querySelector('[data-property-segment="category"]') || form.querySelector('[name="category_code"]');
            const display = form.querySelector('[data-category-display]');
            if (hidden) { hidden.value = codeVal; try { hidden.defaultValue = codeVal; } catch(_){} hidden.setAttribute('value', codeVal); }
            if (display) { display.value = codeVal; try { display.defaultValue = codeVal; } catch(_){} display.setAttribute('value', codeVal); }
        }

        if (typeof data.property_number === 'string') {
            const preview = form.querySelector('[data-property-preview]');
            if (preview) {
                preview.value = data.property_number;
                try { preview.defaultValue = data.property_number; } catch (_) {}
                preview.setAttribute('value', data.property_number);
            }
        }

        if (typeof data.description === 'string' || typeof data.notes === 'string') {
            const desc = data.description ?? data.notes ?? '';
            const textarea = form.querySelector('[data-edit-field="description"]') || form.querySelector('[name="description"]');
            if (textarea) {
                textarea.value = desc;
                try { textarea.defaultValue = desc; } catch (_) {}
                try { textarea.textContent = desc; } catch (_) {}
            }
        }

        if (typeof data.item_instance_id !== 'undefined' || typeof data.item_id !== 'undefined') {
            const iid = data.item_instance_id ?? data.item_id ?? null;
            const iidEl = form.querySelector('[name="item_instance_id"]');
            if (iidEl && iid !== null) { iidEl.value = String(iid); iidEl.defaultValue = String(iid); iidEl.setAttribute('value', String(iid)); }
        }

        if (typeof data.photo === 'string' && data.photo) {
            const photoEl = form.querySelector('[data-edit-photo]');
            if (photoEl) {
                photoEl.src = data.photo;
                photoEl.setAttribute('data-original-src', data.photo);
            }
        }
    } catch (err) {
        console.warn('Could not persist updated values into edit form:', err);
    }

    const normalizedDetail = Object.assign({}, data);
    if (!normalizedDetail.item_id && normalizedDetail.normalizedId !== 0 && normalizedDetail.id) {
        normalizedDetail.item_id = normalizedDetail.id;
    }
    form.dispatchEvent(new CustomEvent('items:edit:success', { detail: normalizedDetail, bubbles: true }));

    try {
        const modalName = form.getAttribute('data-modal-name');
        if (modalName) {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
        }
    } catch (_) {}
}

function handleError(feedbackEl, errorEl, error) {
    hideMessage(feedbackEl);
    const msg = error.message || 'Unable to update item.';

    const duplicateIndicator = 'Another item already uses the property number';
    if (msg.includes(duplicateIndicator)) {
        try {
            const form = document.querySelector('[data-edit-item-form]');
            if (form) {
                const serialInput = form.querySelector('[data-edit-field="serial"], input[name="serial"]');
                if (serialInput) {
                    const orig = serialInput.getAttribute('data-original-serial');
                    if (orig !== null) {
                        serialInput.value = orig;
                    }
                }
            }
        } catch (e) {
            console.warn('Could not revert serial input:', e);
        }
    }
    showMessage(errorEl, msg);
    try { showToast('error', msg); } catch (_) {}
}

function attachOfficeValidation(form) {
    const input = form.querySelector('[data-edit-field="office"]');
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
            input.setCustomValidity('The office code must be 1 to 4 alphanumeric characters.');
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
    const cats = window.__serverCategories || [];
    form.querySelectorAll('select[data-category-select]').forEach(sel => {
        if (sel.options && sel.options.length > 0) return;
        sel.innerHTML = '';
        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = typeof c === 'object' && c !== null ? String(c.id) : String(c);
            opt.textContent = typeof c === 'object' && c !== null ? String(c.name) : String(c);
            sel.appendChild(opt);
        });
    });
}

function attachCategoryListeners(form) {
    form.querySelectorAll('select[data-category-select]').forEach(sel => {
        sel.addEventListener('change', (e) => {
            const chosen = (e.target.value ?? '').toString();
            const hidden = form.querySelector('input[data-property-segment="category"], input[name="category_code"]');
            if (hidden) {
                let resolved = '';
                if (/^\d+$/.test(chosen)) resolved = String(parseInt(chosen,10)).padStart(4,'0');
                if (!resolved && window.__serverCategoryCodeMap) resolved = window.__serverCategoryCodeMap[chosen] || '';
                hidden.value = resolved;
                try { hidden.defaultValue = resolved; } catch(_) {}
            }
            const display = form.querySelector('[data-category-display]');
            if (display) {
                const resolved = (window.__serverCategoryCodeMap && window.__serverCategoryCodeMap[chosen]) || '';
                display.value = resolved;
            }

            form.querySelectorAll('input, select').forEach(inp => inp.dispatchEvent(new Event('input', { bubbles: true })));
        });
    });
}

function initEditForm(form) {
    const feedbackEl = form.querySelector(FEEDBACK_SELECTOR);
    const errorEl = form.querySelector(ERROR_SELECTOR);
    const submitBtn = form.querySelector(SUBMIT_SELECTOR);
    const cancelBtn = form.querySelector(CANCEL_SELECTOR);

    attachOfficeValidation(form);

    form.addEventListener('input', () => {
        hideMessage(errorEl);
    });

    cancelBtn?.addEventListener('click', () => {
        form.reset();
        form.dispatchEvent(new Event('reset'));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideMessage(feedbackEl);
        hideMessage(errorEl);

        toggleLoading(submitBtn, true);
        try {
            const result = await submitForm(form);
            handleSuccess(form, feedbackEl, errorEl, result);
        } catch (error) {
            console.error(error);
            handleError(feedbackEl, errorEl, error);
        } finally {
            toggleLoading(submitBtn, false);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(SELECTOR).forEach(initEditForm);
});

// resources/js/admin/item-instances-inline.js
// If you append to item-edit.js, ensure it's loaded after DOMContentLoaded handlers.

document.addEventListener('DOMContentLoaded', () => {
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';

  const debounce = (fn, delay = 500) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  };

  // PATCH an instance (returns { ok, status, json })
  async function patchInstance(instanceId, payload) {
    const res = await fetch(`/admin/item-instances/${instanceId}`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: (function () {
        // use FormData so we keep usual server expectations
        const fd = new FormData();
        Object.keys(payload).forEach(k => {
          if (payload[k] !== null) fd.append(k, payload[k]);
          else fd.append(k, '');
        });
        return fd;
      })(),
      credentials: 'same-origin',
    });

    const ct = res.headers.get('content-type') || '';
    const json = ct.includes('application/json') ? await res.json().catch(() => null) : null;
    return { ok: res.ok, status: res.status, json };
  }

  // DELETE an instance
  async function deleteInstance(instanceId) {
    const res = await fetch(`/admin/item-instances/${instanceId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
    });
    const ct = res.headers.get('content-type') || '';
    const json = ct.includes('application/json') ? await res.json().catch(() => null) : null;
    return { ok: res.ok, status: res.status, json };
  }

  // Helper: assemble visible PN for display (best-effort)
function assemblePN({ year_procured, category_code, serial, office_code }) {
  const y = String(year_procured || '').trim();
  const p = String(category_code || '').trim();
  const s = String(serial || '').trim();
  const o = String(office_code || '').trim();
  return [y, p, s, o].every(x => x) ? `${y}-${p}-${s}-${o}` : '';
}

  // For each row: set up handlers
  function initInstanceRow(row) {
    if (!row || row._instancesInitialized) return;
    row._instancesInitialized = true;

    const instanceId = row.getAttribute('data-instance-id');
    const yearEl = row.querySelector('.instance-part-year');
    const categoryEl = row.querySelector('.instance-part-category');
    const serialEl = row.querySelector('.instance-part-serial');
    const officeEl = row.querySelector('.instance-part-office');
    const statusEl = row.querySelector('.instance-status');

    // store original values so we can rollback serial only
    if (yearEl) yearEl.dataset.orig = yearEl.value ?? '';
    if (categoryEl) categoryEl.dataset.orig = categoryEl.value ?? '';
    if (serialEl) serialEl.dataset.orig = serialEl.value ?? '';
    if (officeEl) officeEl.dataset.orig = officeEl.value ?? '';

    // Debounced save
    const doSave = debounce(async () => {
      const payload = {
        year: yearEl ? yearEl.value.trim() || null : null,
        category: categoryEl ? categoryEl.value.trim() || null : null,
        category_code: categoryEl ? categoryEl.value.trim() || null : null,
        serial: serialEl ? serialEl.value.trim() || null : null,
        office: officeEl ? officeEl.value.trim() || null : null,
      };

      const prevStatus = statusEl ? statusEl.textContent : '';
      if (statusEl) statusEl.textContent = 'Saving...';

      try {
        const { ok, json } = await patchInstance(instanceId, payload);

        if (ok) {
          const pn = (json && json.property_number) ? json.property_number : assemblePN(payload);
          if (statusEl && pn) statusEl.textContent = pn;
          // update orig snapshots to the new values
          if (yearEl) yearEl.dataset.orig = yearEl.value ?? '';
          if (categoryEl) categoryEl.dataset.orig = categoryEl.value ?? '';
          if (serialEl) serialEl.dataset.orig = serialEl.value ?? '';
          if (officeEl) officeEl.dataset.orig = officeEl.value ?? '';
          try { if (typeof window.showToast === 'function') window.showToast('success', 'Instance updated'); } catch (_) {}
          window.dispatchEvent(new CustomEvent('instance:updated', { detail: { instanceId, payload, response: json } }));
          return;
        }

        // if validation failed, detect serial-specific error
        let serialError = false;
        if (json) {
          if (json.errors && (json.errors.serial || json.errors.serial_int)) serialError = true;
          if (typeof json.message === 'string' && /serial|duplicate|already in use|property number/i.test(json.message)) serialError = true;
        }

        if (serialError) {
          // rollback only serial
          if (serialEl && serialEl.dataset.orig !== undefined) {
            serialEl.value = serialEl.dataset.orig;
          }
          if (statusEl) statusEl.textContent = prevStatus || 'Conflict';
          const userMsg = (json && (json.message || (json.errors && json.errors.serial && json.errors.serial.join(' ')))) || 'Serial duplicate — reverted.';
          try { if (typeof window.showToast === 'function') window.showToast('error', userMsg); } catch (_) {}
          window.dispatchEvent(new CustomEvent('instance:update:serial_conflict', { detail: { instanceId, message: userMsg } }));
          return;
        }

        // other validation error — show message but do not revert other fields per your request
        const fallbackMsg = (json && (json.message || (json.errors && Object.values(json.errors).flat().join(' ')))) || 'Validation failed';
        if (statusEl) statusEl.textContent = prevStatus || 'Error';
        try { if (typeof window.showToast === 'function') window.showToast('error', fallbackMsg); } catch (_) {}
        window.dispatchEvent(new CustomEvent('instance:update:failed', { detail: { instanceId, status: 422, message: fallbackMsg } }));
      } catch (err) {
        console.error(err);
        if (statusEl) statusEl.textContent = prevStatus || 'Error';
        try { if (typeof window.showToast === 'function') window.showToast('error', 'Failed to update instance.'); } catch (_) {}
      }
    }, 700);

    // wire inputs
    [yearEl, categoryEl, serialEl, officeEl].forEach(el => {
      if (!el) return;
      el.addEventListener('input', () => {
        // sanitize office to alphanumeric and serial to alphanumeric (you already have similar logic)
        if (el === officeEl) {
          el.value = el.value.replace(/[^a-zA-Z0-9]/g, '');
        }
        if (el === serialEl) {
          // allow alphanumeric here (you said serial can be alphanumeric)
          el.value = el.value.replace(/[^A-Za-z0-9]/g, '');
        }
        doSave();
      });
    });

    // Remove button
    const removeBtn = row.querySelector('.instance-remove-btn');
           if (removeBtn) {
      removeBtn.addEventListener('click', async (ev) => {
        ev.preventDefault();
        const confirmed = await showActionConfirm('Remove property number', 'Are you sure you want to remove this property number?', { confirmText: 'Remove' });
        if (!confirmed) return;

        removeBtn.disabled = true;
        try {
          const { ok, json, status } = await deleteInstance(instanceId);
          if (ok) {
            row.remove();
            window.dispatchEvent(new CustomEvent('instance:deleted', { detail: { instanceId, response: json } }));
            try { if (typeof window.showToast === 'function') window.showToast('success', 'Instance deleted'); } catch (_) {}
            return;
          }
          const msg = (json && (json.message || (json.errors && json.errors.serial && json.errors.serial.join(' ')))) || `Failed to delete instance (${status})`;
          showToast('error', msg);
          removeBtn.disabled = false;
        } catch (err) {
          console.error('Instance delete failed', err);
          showToast('error', 'Failed to delete instance');
          removeBtn.disabled = false;
        }
      });
    }

  }

  // Initialize rows present on load
  document.querySelectorAll('.edit-instance-row').forEach(initInstanceRow);

  // If you dynamically add new rows in DOM later, use MutationObserver to initialize them automatically
  const container = document.getElementById('edit_instances_container');
  if (container) {
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        for (const added of m.addedNodes) {
          if (added instanceof Element && added.classList.contains('edit-instance-row')) {
            initInstanceRow(added);
          } else if (added instanceof Element) {
            added.querySelectorAll('.edit-instance-row').forEach(initInstanceRow);
          }
        }
      }
    });
    mo.observe(container, { childList: true, subtree: true });
  }

});

document.addEventListener('DOMContentLoaded', () => {
  // Uppercase and limit serial/office across add/edit forms
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

  // Year fields: digits only
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