// resources/js/item-instances-manager.js
const ROW_INVALID_CLASSES = ['ring-2', 'ring-red-300', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-300'];
const SERIAL_MAX_LENGTH = 5;

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
  ? window.showToast.bind(window)
  : (type, message) => {
      if (type === 'error') console.error(message);
      else console.log(message);
    };

const sanitizeYear = (value) => String(value || '').replace(/\D/g, '').slice(0, 4);
const sanitizeGla = (value) => String(value || '').replace(/\D/g, '').slice(0, 4);
const sanitizeOffice = (value) => String(value || '').replace(/\D/g, '').slice(0, 4);
const sanitizeCategory = (value) => String(value || '').replace(/\D/g, '').slice(0, 4);

const sanitizeSerialInput = (value) => String(value || '').toUpperCase().replace(/[^A-Za-z0-9]/g, '').slice(0, SERIAL_MAX_LENGTH);

function parseSerialSegments(value) {
  const segments = [];
  if (!value) return segments;
  let currentType = null;
  let currentValue = '';
  for (const char of value) {
    const type = /[0-9]/.test(char) ? 'digit' : 'letter';
    if (type !== currentType) {
      if (currentValue) {
        segments.push({ type: currentType, value: currentValue });
      }
      currentType = type;
      currentValue = char;
    } else {
      currentValue += char;
    }
  }
  if (currentValue) {
    segments.push({ type: currentType, value: currentValue });
  }
  return segments;
}

function computeDigitWidth(length, lettersLength = 0) {
  if (length <= 1) return Math.min(2, Math.max(1, 5 - lettersLength));
  if (length === 2) return Math.min(2, Math.max(2, 5 - lettersLength));
  if (length === 3) return Math.min(4, Math.max(3, 5 - lettersLength));
  return Math.min(length, Math.max(length, 5 - lettersLength));
}

function serialHasDigit(value) {
  return /\d/.test(value || '');
}

function formatSerialValue(raw) {
  const sanitized = sanitizeSerialInput(raw);
  if (!sanitized) return '';
  const segments = parseSerialSegments(sanitized);
  const letterLengthTotal = segments
    .filter((segment) => segment.type === 'letter')
    .reduce((sum, segment) => sum + segment.value.length, 0);

  const formattedSegments = segments.map((segment) => {
    if (segment.type === 'digit') {
      const width = computeDigitWidth(segment.value.length, letterLengthTotal);
      let padded = segment.value.padStart(width, '0');
      if (padded.length + letterLengthTotal > SERIAL_MAX_LENGTH) {
        const allowed = Math.max(segment.value.length, SERIAL_MAX_LENGTH - letterLengthTotal);
        padded = padded.slice(-allowed);
      }
      return padded;
    }
    return segment.value;
  });

  return formattedSegments.join('').slice(0, SERIAL_MAX_LENGTH);
}

function sanitizeSerial(value) {
  const formatted = formatSerialValue(value);
  return formatted;
}

function readRow(inputs) {
  return {
    year: sanitizeYear(inputs.yearEl?.value ?? ''),
    category: sanitizeCategory(inputs.categoryEl?.value ?? ''),
    gla: sanitizeGla(inputs.glaEl?.value ?? ''),
    serial: sanitizeSerial(inputs.serialEl?.value ?? ''),
    office: sanitizeOffice(inputs.officeEl?.value ?? ''),
  };
}

function applyRowValues(inputs, values) {
  if (inputs.yearEl) inputs.yearEl.value = values.year ?? '';
  if (inputs.categoryEl) inputs.categoryEl.value = values.category ?? '';
  if (inputs.glaEl) inputs.glaEl.value = values.gla ?? '';
  if (inputs.serialEl) inputs.serialEl.value = values.serial ?? '';
  if (inputs.officeEl) inputs.officeEl.value = values.office ?? '';
}

function inputsForRow(row) {
  return {
    yearEl: row.querySelector('.instance-part-year'),
    categoryEl: row.querySelector('.instance-part-category'),
    glaEl: row.querySelector('.instance-part-gla'),
    serialEl: row.querySelector('.instance-part-serial'),
    officeEl: row.querySelector('.instance-part-office'),
    removeBtn: row.querySelector('.instance-remove-btn'),
  };
}

function setInvalid(input, reason) {
  if (!input) return;
  const reasons = input.__invalidReasons || new Set();
  reasons.add(reason);
  input.__invalidReasons = reasons;
  ROW_INVALID_CLASSES.forEach((cls) => input.classList.add(cls));
  input.dataset.invalid = '1';
  input.setAttribute('aria-invalid', 'true');
  if (reason === 'duplicate') {
    input.dataset.duplicate = '1';
  }
}

function clearInvalid(input, reason) {
  if (!input || !input.__invalidReasons) return;
  const reasons = input.__invalidReasons;
  reasons.delete(reason);
  if (reasons.size === 0) {
    ROW_INVALID_CLASSES.forEach((cls) => input.classList.remove(cls));
    delete input.__invalidReasons;
    input.removeAttribute('data-invalid');
    input.removeAttribute('aria-invalid');
  }
  if (reason === 'duplicate') {
    delete input.dataset.duplicate;
  }
}

function valuesEqual(a, b) {
  return a.year === b.year &&
    a.category === b.category &&
    a.gla === b.gla &&
    a.serial === b.serial &&
    a.office === b.office;
}

function buildErrorSummary(fields) {
  if (!fields.size) return '';
  const labels = {
    year: 'Year',
    category: 'Category',
    gla: 'GLA',
    serial: 'Serial',
    office: 'Office',
  };
  const names = Array.from(fields).map((f) => labels[f] || f);
  if (names.length === 1) return `${names[0]} field is incorrect`;
  return `${names.join(' & ')} fields are incorrect`;
}

class ItemInstancesManager {
  constructor(form) {
    this.form = form;
    this.container = form.querySelector('#edit_instances_container');
    if (!this.container) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';
    this.api = {
      async patch(instanceId, payload) {
        const res = await fetch(`/admin/item-instances/${instanceId}`, {
          method: 'PATCH',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload),
          credentials: 'same-origin',
        });
        const json = await res.json().catch(() => null);
        return { ok: res.ok, status: res.status, json };
      },
      async destroy(instanceId) {
        const res = await fetch(`/admin/item-instances/${instanceId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
          },
          credentials: 'same-origin',
        });
        const json = await res.json().catch(() => null);
        return { ok: res.ok, status: res.status, json };
      },
    };

    this.rows = [];
    this.registerExistingRows();
    this.bindEvents();
    this.form.__instanceManager = this;
  }

  registerExistingRows() {
    this.container.querySelectorAll('.edit-instance-row').forEach((row) => {
      const inputs = inputsForRow(row);
      const initial = readRow(inputs);
      const state = {
        id: row.getAttribute('data-instance-id') || null,
        row,
        inputs,
        initial,
        current: { ...initial },
        removed: false,
        dirty: false,
      };
      row.__instanceState = state;
      applyRowValues(inputs, initial);
      this.rows.push(state);
    });
  }

  bindEvents() {
    this.container.addEventListener('input', (event) => this.onInput(event));
    this.container.addEventListener('click', (event) => this.onRemove(event));
  }

  onInput(event) {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) return;
    const row = target.closest('.edit-instance-row');
    if (!row || !row.__instanceState) return;
    const state = row.__instanceState;
    if (state.removed) return;

    const field = this.fieldFromInput(target);
    if (!field) return;

    switch (field) {
      case 'year':
        target.value = sanitizeYear(target.value);
        break;
      case 'gla':
        target.value = sanitizeGla(target.value);
        break;
      case 'serial': {
        const sanitized = sanitizeSerial(target.value);
        if (sanitized !== target.value) target.value = sanitized;
        break;
      }
      case 'office':
        target.value = sanitizeOffice(target.value);
        break;
      case 'category':
        target.value = sanitizeCategory(target.value);
        break;
      default:
        break;
    }

    state.current = readRow(state.inputs);
   state.dirty = !valuesEqual(state.current, state.initial);
   row.dataset.dirty = state.dirty ? '1' : '';
   this.validateRow(state);
    this.markDuplicateSerials();
  }

  onRemove(event) {
    const btn = event.target instanceof Element ? event.target.closest('.instance-remove-btn') : null;
    if (!btn) return;
    const row = btn.closest('.edit-instance-row');
    if (!row || !row.__instanceState) return;
    event.preventDefault();
    const state = row.__instanceState;

    const removing = !state.removed;
    if (removing) {
      const confirmMessage = 'Remove this property number? It will be deleted once you click Update.';
      if (!window.confirm(confirmMessage)) return;
      state.removed = true;
      row.dataset.removed = '1';
      row.classList.add('opacity-50');
      state.inputs.yearEl?.setAttribute('disabled', 'disabled');
      state.inputs.categoryEl?.setAttribute('disabled', 'disabled');
      state.inputs.glaEl?.setAttribute('disabled', 'disabled');
      state.inputs.serialEl?.setAttribute('disabled', 'disabled');
      state.inputs.officeEl?.setAttribute('disabled', 'disabled');
      btn.dataset.icon = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-rotate-left"></i>';
    } else {
      state.removed = false;
      row.dataset.removed = '';
      row.classList.remove('opacity-50');
      state.inputs.yearEl?.removeAttribute('disabled');
      state.inputs.categoryEl?.removeAttribute('disabled');
      state.inputs.glaEl?.removeAttribute('disabled');
      state.inputs.serialEl?.removeAttribute('disabled');
      state.inputs.officeEl?.removeAttribute('disabled');
      if (btn.dataset.icon) {
        btn.innerHTML = btn.dataset.icon;
        delete btn.dataset.icon;
      }
    }
    this.markDuplicateSerials();
  }

  fieldFromInput(input) {
    if (input.classList.contains('instance-part-year')) return 'year';
    if (input.classList.contains('instance-part-category')) return 'category';
    if (input.classList.contains('instance-part-gla')) return 'gla';
    if (input.classList.contains('instance-part-serial')) return 'serial';
    if (input.classList.contains('instance-part-office')) return 'office';
    return null;
  }

  resetAll() {
    this.rows.forEach((state) => {
      state.removed = false;
      state.dirty = false;
      state.current = { ...state.initial };
      state.row.dataset.removed = '';
      state.row.dataset.dirty = '';
      state.row.classList.remove('opacity-50');
      Object.values(state.inputs).forEach((input) => {
        if (input instanceof HTMLInputElement) {
          input.removeAttribute('disabled');
          clearInvalid(input, 'empty');
          clearInvalid(input, 'format');
          clearInvalid(input, 'duplicate');
        }
      });
      applyRowValues(state.inputs, state.initial);
    });
    this.markDuplicateSerials();
  }

  async applyChanges() {
    const errors = new Set();
    this.rows.forEach((state) => {
      if (state.removed) return;
      const validation = this.validateRow(state);
      validation.forEach((field) => errors.add(field));
    });

    const hasDuplicates = this.markDuplicateSerials();
    if (hasDuplicates) {
      errors.add('serial');
    }

    if (errors.size) {
      const summary = buildErrorSummary(errors);
      if (summary) showToast('error', summary);
      this.focusFirstInvalid();
      return { ok: false, message: summary, fields: errors };
    }

    const updates = this.rows.filter((state) => !state.removed && state.dirty && !valuesEqual(state.current, state.initial));
    const deletions = this.rows.filter((state) => state.removed);

    for (const state of updates) {
      if (!state.id) continue;
      const payload = {
        year: state.current.year || undefined,
        category: state.current.category || undefined,
        gla: state.current.gla || undefined,
        serial: state.current.serial || undefined,
        office: state.current.office || undefined,
      };
      const result = await this.api.patch(state.id, payload);
      if (!result.ok) {
        const message = (result.json && (result.json.message || (result.json.errors && Object.values(result.json.errors).flat().join(' ')))) || 'Failed to update instance.';
        showToast('error', message);
        return { ok: false, message };
      }
      state.initial = { ...state.current };
      state.dirty = false;
      state.row.dataset.dirty = '';
      window.dispatchEvent(new CustomEvent('instance:updated', { detail: { instanceId: state.id, payload: state.current } }));
    }

    for (const state of deletions) {
      if (!state.id) continue;
      const result = await this.api.destroy(state.id);
      if (!result.ok) {
        const message = (result.json && (result.json.message || result.json.error)) || 'Failed to delete instance.';
        showToast('error', message);
        return { ok: false, message };
      }
      state.row.remove();
      this.rows = this.rows.filter((item) => item !== state);
      window.dispatchEvent(new CustomEvent('instance:deleted', { detail: { instanceId: state.id } }));
    }

    return { ok: true };
  }

  validateRow(state) {
    const fields = new Set();
    const { inputs } = state;
    const current = state.current;

    const yearValid = current.year.length === 4 && parseInt(current.year, 10) >= 2020;
    if (!yearValid) {
      setInvalid(inputs.yearEl, 'format');
      fields.add('year');
    } else {
      clearInvalid(inputs.yearEl, 'format');
    }

    const categoryValid = current.category.length === 4 && /^\d{4}$/.test(current.category);
    if (!categoryValid) {
      setInvalid(inputs.categoryEl, 'format');
      fields.add('category');
    } else {
      clearInvalid(inputs.categoryEl, 'format');
    }

    const glaValid = current.gla.length >= 1 && current.gla.length <= 4;
    if (!glaValid) {
      setInvalid(inputs.glaEl, 'format');
      fields.add('gla');
    } else {
      clearInvalid(inputs.glaEl, 'format');
    }

    const serialValid = current.serial.length >= 1 && serialHasDigit(current.serial);
    if (!serialValid) {
      setInvalid(inputs.serialEl, 'format');
      fields.add('serial');
    } else {
      clearInvalid(inputs.serialEl, 'format');
    }

    const officeValid = current.office.length === 4 && /^\d{4}$/.test(current.office);
    if (!officeValid) {
      setInvalid(inputs.officeEl, 'format');
      fields.add('office');
    } else {
      clearInvalid(inputs.officeEl, 'format');
    }

    return fields;
  }

  focusFirstInvalid() {
    if (!this.container) return;
    const invalidInput = this.container.querySelector('input[data-invalid="1"]');
    if (invalidInput instanceof HTMLInputElement) {
      try {
        invalidInput.focus({ preventScroll: true });
      } catch (_) {
        invalidInput.focus();
      }
    }
  }

  markDuplicateSerials() {
    const seen = new Map();
    const duplicates = new Set();

    this.rows.forEach((state) => {
      if (state.removed) return;
      const serial = sanitizeSerial(state.current.serial || '');
      if (!serial || !serialHasDigit(serial)) return;
      const key = serial.toUpperCase();
      if (seen.has(key)) {
        duplicates.add(state);
        duplicates.add(seen.get(key));
      } else {
        seen.set(key, state);
      }
    });

    this.rows.forEach((state) => {
      const input = state.inputs.serialEl;
      if (!input) return;
      if (duplicates.has(state)) {
        setInvalid(input, 'duplicate');
      } else {
        clearInvalid(input, 'duplicate');
      }
    });

    return duplicates.size > 0;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-edit-item-form]').forEach((form) => {
    new ItemInstancesManager(form);
  });
});
