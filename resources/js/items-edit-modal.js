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

    // Close the parent modal if the form provides a modal name
    try {
        const modalName = form.getAttribute('data-modal-name');
        if (modalName) {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
        }
    } catch (_) {}

    if (data.property_number) {
        const preview = form.querySelector('[data-property-preview]');
        if (preview) preview.value = data.property_number;
    }

    form.dispatchEvent(new CustomEvent('items:edit:success', { detail: data, bubbles: true }));
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
        const value = input.value.trim();
        const isValid = /^\d{4}$/.test(value);
        input.setCustomValidity(isValid ? '' : 'The office code field must be 4 digits.');
        errorEl.classList.toggle('hidden', isValid || !value);
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
