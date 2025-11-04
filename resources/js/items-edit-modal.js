const SELECTOR = '[data-edit-item-form]';
const FEEDBACK_SELECTOR = '[data-edit-feedback]';
const ERROR_SELECTOR = '[data-edit-error]';
const SUBMIT_SELECTOR = '[data-edit-submit]';
const CANCEL_SELECTOR = '[data-edit-cancel]';

const FIELD_LABELS = {
  year: 'Year',
  category: 'Category',
  gla: 'GLA',
  serial: 'Serial',
  office: 'Office',
};

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
  ? window.showToast.bind(window)
  : (type, message) => {
      if (type === 'error') console.error(message);
      else console.log(message);
    };

function buildErrorSummary(fields) {
  if (!fields || !fields.size) return '';
  const labels = Array.from(fields).map((field) => FIELD_LABELS[field] || field);
  if (labels.length === 1) {
    return `${labels[0]} field is incorrect`;
  }
  return `${labels.join(' & ')} fields are incorrect`;
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
    return { ok: true, data: payload };
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

function wireEditForm(form) {
  const feedbackEl = form.querySelector(FEEDBACK_SELECTOR);
  const errorEl = form.querySelector(ERROR_SELECTOR);
  const submitBtn = form.querySelector(SUBMIT_SELECTOR);
  const cancelBtn = form.querySelector(CANCEL_SELECTOR);
  const modalName = form.dataset.modalName;

  const manager = form.__instanceManager ?? null;

  const clearMessages = () => {
    hideMessage(feedbackEl);
    hideMessage(errorEl);
  };

  cancelBtn?.addEventListener('click', () => {
    clearMessages();
    form.reset();
    if (manager && typeof manager.resetAll === 'function') {
      manager.resetAll();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearMessages();

    toggleLoading(submitBtn, true);

    try {
      if (manager && typeof manager.applyChanges === 'function') {
        const applyResult = await manager.applyChanges();
        if (!applyResult.ok) {
          const summary = applyResult.message || buildErrorSummary(applyResult.fields || new Set());
          if (summary) {
            showMessage(errorEl, summary);
            showToast('error', summary);
          }
          toggleLoading(submitBtn, false);
          return;
        }
      }

      const result = await submitForm(form);
      const data = result?.data || {};
      const message = data.message || 'Item details updated successfully.';

      // Show toast notification
      showToast('success', message);
      showMessage(feedbackEl, message);

      if (modalName) {
        // Delay closing modal slightly to ensure toast is visible
        setTimeout(() => {
          window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
          // Reload to reflect changes
          setTimeout(() => {
            window.location.reload();
          }, 300);
        }, 500);
      } else {
        // If no modal name, just reload after a delay
        setTimeout(() => {
          window.location.reload();
        }, 500);
      }
    } catch (error) {
      console.error(error);
      const message = error?.message || 'Failed to update item details. Please try again.';
      showMessage(errorEl, message);
      showToast('error', message);
    } finally {
      toggleLoading(submitBtn, false);
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll(SELECTOR).forEach(wireEditForm);
});
