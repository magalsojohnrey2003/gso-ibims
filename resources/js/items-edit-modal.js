const SELECTOR = '[data-edit-item-form]';
const FEEDBACK_SELECTOR = '[data-edit-feedback]';
const ERROR_SELECTOR = '[data-edit-error]';
const SUBMIT_SELECTOR = '[data-edit-submit]';
const CANCEL_SELECTOR = '[data-edit-cancel]';
const OFFICE_ERROR_SELECTOR = '[data-office-error]';

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

    // --- Persist updated fields into the form DOM so reset/open reflect latest values ---
    try {
        // Helper to set value + defaultValue + attribute safely
        const setInput = (selector, val) => {
            const el = form.querySelector(selector);
            if (!el) return;
            try { el.value = val ?? ''; } catch (_) {}
            try { el.defaultValue = val ?? ''; } catch (_) {}
            try { el.setAttribute('value', val ?? ''); } catch (_) {}
        };

        // Textarea helper (no value attribute)
        const setTextarea = (selector, val) => {
            const el = form.querySelector(selector);
            if (!el) return;
            el.textContent = val ?? '';
            try { el.defaultValue = val ?? ''; } catch (_) {}
            try { el.value = val ?? ''; } catch (_) {}
        };

        // Name
        if (typeof data.name === 'string') {
            setInput('[data-edit-field="name"]');
            setInput('[name="name"]', data.name);
            const nameEl = form.querySelector('[data-edit-field="name"]') || form.querySelector('[name="name"]');
            if (nameEl) { nameEl.value = data.name; nameEl.defaultValue = data.name; nameEl.setAttribute('value', data.name); }
        }

        // Office (preserve alphanumeric)
        if (typeof data.office_code === 'string') {
            const officeEl = form.querySelector('[data-edit-field="office"]') || form.querySelector('[name="office_code"]');
            if (officeEl) {
                officeEl.value = data.office_code;
                officeEl.defaultValue = data.office_code;
                officeEl.setAttribute('value', data.office_code);
            }
        }

        // Year procured
        if (typeof data.year_procured !== 'undefined') {
            const yEl = form.querySelector('[data-edit-field="year"]') || form.querySelector('[name="year_procured"]');
            if (yEl) {
                const sval = String(data.year_procured ?? '');
                yEl.value = sval;
                yEl.defaultValue = sval;
                yEl.setAttribute('value', sval);
            }
        }

        // Serial
        if (typeof data.serial === 'string' || (typeof data.serial !== 'undefined' && data.serial !== null)) {
            const sEl = form.querySelector('[data-edit-field="serial"]') || form.querySelector('[name="serial"]');
            if (sEl && typeof data.serial === 'string') {
                sEl.value = data.serial;
                sEl.defaultValue = data.serial;
                sEl.setAttribute('value', data.serial);
            }
        }

        // PPE: hidden + display inputs (if provided by server)
        if (typeof data.ppe_code === 'string' || typeof data.ppe === 'string') {
            const ppeVal = (data.ppe_code ?? data.ppe ?? '');
            const ppeHidden = form.querySelector('[data-property-segment="ppe"]') || form.querySelector('[name="ppe_code"]');
            const ppeDisplay = form.querySelector('[data-ppe-display]');
            if (ppeHidden) { ppeHidden.value = ppeVal; try { ppeHidden.defaultValue = ppeVal; } catch(_){} ppeHidden.setAttribute('value', ppeVal); }
            if (ppeDisplay) { ppeDisplay.value = ppeVal; try { ppeDisplay.defaultValue = ppeVal; } catch(_){} ppeDisplay.setAttribute('value', ppeVal); }
        }

        // property preview
        if (typeof data.property_number === 'string') {
            const preview = form.querySelector('[data-property-preview]');
            if (preview) {
                preview.value = data.property_number;
                try { preview.defaultValue = data.property_number; } catch (_) {}
                preview.setAttribute('value', data.property_number);
            }
        }

        // description / notes
        if (typeof data.description === 'string' || typeof data.notes === 'string') {
            const desc = data.description ?? data.notes ?? '';
            const textarea = form.querySelector('[data-edit-field="description"]') || form.querySelector('[name="description"]');
            if (textarea) {
                textarea.value = desc;
                try { textarea.defaultValue = desc; } catch (_) {}
                try { textarea.textContent = desc; } catch (_) {}
            }
        }

        // item_instance_id hidden (if response included or already present)
        if (typeof data.item_instance_id !== 'undefined' || typeof data.item_id !== 'undefined') {
            const iid = data.item_instance_id ?? data.item_id ?? null;
            const iidEl = form.querySelector('[name="item_instance_id"]');
            if (iidEl && iid !== null) { iidEl.value = String(iid); iidEl.defaultValue = String(iid); iidEl.setAttribute('value', String(iid)); }
        }

        // Photo: if the server returned a new photo URL, update <img data-edit-photo> src and data-original-src
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

    // Fire event for global listeners (table row updater)
    const normalizedDetail = Object.assign({}, data);
    if (!normalizedDetail.item_id && normalizedDetail.item_id !== 0 && normalizedDetail.id) {
        normalizedDetail.item_id = normalizedDetail.id;
    }
    form.dispatchEvent(new CustomEvent('items:edit:success', { detail: normalizedDetail, bubbles: true }));

    // --- Only AFTER we've persisted the values do we close the modal.
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



