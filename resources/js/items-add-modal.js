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
const SERIAL_OK_CLASSES = [];
const ROW_INVALID_CLASSES = ['border-red-300', 'ring-1', 'ring-red-200', 'focus:border-red-400', 'focus:ring-red-200'];

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
  ? window.showToast.bind(window)
  : (type, message) => {
      if (type === 'error') console.error(message);
      else console.log(message);
    };

const FIELD_LABELS = {
  year: 'Year',
  category: 'Category',
  gla: 'GLA',
  serial: 'Serial',
  office: 'Office',
  serial_no: 'Serial No.',
  model_no: 'Model No.',
};

function sanitizeSerialInput(value) {
  if (!value) return '';
  return String(value).toUpperCase().replace(/[^A-Za-z0-9]/g, '').slice(0, 5);
}

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

function serialContainsDigit(serial) {
  return /[0-9]/.test(serial || '');
}

function incrementLetters(value) {
  return value
    .split('')
    .map((char) => {
      const code = char.charCodeAt(0);
      if (code < 65 || code > 90) return char;
      const next = ((code - 65 + 1) % 26) + 65;
      return String.fromCharCode(next);
    })
    .join('');
}

function formatSerialValue(raw) {
  const sanitized = sanitizeSerialInput(raw);
  if (!sanitized) return '';
  const segments = parseSerialSegments(sanitized);
  const letterLengthTotal = segments
    .filter((seg) => seg.type === 'letter')
    .reduce((sum, seg) => sum + seg.value.length, 0);

  const formattedSegments = segments.map((segment) => {
    if (segment.type === 'digit') {
      const width = computeDigitWidth(segment.value.length, letterLengthTotal);
      let padded = segment.value.padStart(width, '0');
      if (padded.length + letterLengthTotal > 5) {
        const allowed = Math.max(segment.value.length, 5 - letterLengthTotal);
        padded = padded.slice(-allowed);
      }
      return padded;
    }
    return segment.value;
  });

  const result = formattedSegments.join('').slice(0, 5);
  return result;
}

function incrementSerialValue(value) {
  const sanitized = sanitizeSerialInput(value);
  if (!sanitized) return '';
  const segments = parseSerialSegments(sanitized);
  return segments
    .map((segment) => {
      if (segment.type === 'digit') {
        const width = segment.value.length;
        const incremented = String(parseInt(segment.value, 10) + 1);
        return incremented.padStart(width, '0');
      }
      return incrementLetters(segment.value);
    })
    .join('')
    .slice(0, 5);
}

function stripSerialPadding(value) {
  if (!value) return '';
  const upper = String(value).toUpperCase();
  const match = upper.match(/^([A-Z]*)(0*)(\d+)$/);
  if (match) {
    const prefix = match[1] || '';
    const digits = match[3] || '';
    const trimmedDigits = digits.replace(/^0+/, '');
    const normalizedDigits = trimmedDigits === '' ? (digits ? '0' : '') : trimmedDigits;
    return prefix + normalizedDigits;
  }
  return upper;
}

function displaySerialValue(value) {
  if (!value) return '';
  return stripSerialPadding(value);
}

function buildErrorSummary(fields) {
  if (!fields || fields.size === 0) return '';
  const labels = Array.from(fields).map((field) => FIELD_LABELS[field] || field);
  if (labels.length === 1) {
    return `${labels[0]} field is incorrect`;
  }
  return `${labels.join(' & ')} fields are incorrect`;
}

function bootstrapItemsData() {
  if (typeof document === 'undefined') return;
  const source = document.querySelector('[data-items-bootstrap]');
  if (!source) return;

  const parseJSON = (raw) => {
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  };

  const categories = parseJSON(source.dataset.itemsCategories) || [];
  const offices = parseJSON(source.dataset.itemsOffices) || [];
  const categoryCodesRaw = parseJSON(source.dataset.itemsCategoryCodes) || {};

  if (typeof window !== 'undefined') {
    const normalizedCategories = Array.isArray(categories) ? categories.map(normalizeCategory) : [];
    if (normalizedCategories.length) {
      window.__serverCategories = normalizedCategories;
    } else if (!Array.isArray(window.__serverCategories)) {
      window.__serverCategories = [];
    }

    if (Array.isArray(offices) && offices.length) {
      window.__serverOffices = offices.map(normalizeOffice);
    } else if (!Array.isArray(window.__serverOffices)) {
      window.__serverOffices = [];
    }

    const seededMap = (categoryCodesRaw && typeof categoryCodesRaw === 'object' && !Array.isArray(categoryCodesRaw))
      ? Object.entries(categoryCodesRaw).reduce((acc, [key, value]) => {
          const digits = String(value || '').replace(/\D/g, '').slice(0, 4);
          if (digits) acc[String(key)] = digits.padStart(4, '0');
          return acc;
        }, {})
      : {};

    const mergedMap = buildCategoryCodeMap(normalizedCategories, seededMap);
    window.__servercategoryCodeMap = mergedMap;
    window.__serverCategoryCodeMap = mergedMap;
    window.CATEGORY_CODE_MAP = mergedMap;
  }
}

function normalizeCategory(item) {
  const name = item && typeof item === 'object' ? (item.name ?? '') : (item ?? '');
  const id = item && typeof item === 'object' ? (item.id ?? null) : null;
  const rawCode = item && typeof item === 'object'
    ? (item.category_code ?? item.code ?? '')
    : '';
  const digits = String(rawCode || '').replace(/\D/g, '').slice(0, 4);
  const code = digits ? digits.padStart(4, '0') : '';
  return {
    id,
    name: String(name || ''),
    category_code: code,
    code,
  };
}

function normalizeOffice(item) {
  const rawCode = item && typeof item === 'object' ? (item.code ?? '') : (item ?? '');
  const digits = String(rawCode || '').replace(/\D/g, '').slice(0, 4);
  const code = digits ? digits.padStart(4, '0') : '';
  const name = item && typeof item === 'object' ? (item.name ?? '') : '';
  return {
    code,
    name: String(name || ''),
  };
}

function buildCategoryCodeMap(categories, seed = {}) {
  const map = { ...(seed || {}) };
  (Array.isArray(categories) ? categories : []).forEach((cat) => {
    if (!cat || typeof cat !== 'object') return;
    const raw = cat.code ?? cat.category_code ?? '';
    const digits = String(raw || '').replace(/\D/g, '').slice(0, 4);
    if (!digits) return;
    const code = digits.padStart(4, '0');
    if (cat.name) {
      map[String(cat.name)] = code;
    }
    if (cat.id !== undefined && cat.id !== null) {
      map[String(cat.id)] = code;
    }
  });
  return map;
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
    gla: form.querySelector('select[data-gla-select], [data-add-field="gla"], input[name="gla"]'),
    office: form.querySelector('[data-add-field="office"]'),
    serial: form.querySelector('[data-add-field="serial"]'),
    quantity: form.querySelector('[data-add-field="quantity"]'),
  };

  const year = (fields.year?.value || '').toString().trim();
  const categoryValue = (fields.categoryEl?.value ?? '').trim();

  let categoryCodeValue = fields.categoryHidden?.value ?? '';
  if (!categoryCodeValue && fields.categoryEl) {
    categoryCodeValue = resolveCategoryCode(fields.categoryEl) || '';
  }
  if (!categoryCodeValue && categoryValue) {
    categoryCodeValue = resolveCategoryCode(categoryValue) || '';
  }

  const categoryCode = normalizeSegment(categoryCodeValue, 4).replace(/\D/g, '').slice(0, 4);
  const glaVal = (fields.gla?.value || '').replace(/\D/g, '').slice(0, 4);
  const officeDigits = (fields.office?.value || '').toString().replace(/\D/g, '').slice(0, 4);
  const office = officeDigits;

  const rawSerialInput = fields.serial?.value ?? '';
  const sanitizedSerial = sanitizeSerialInput(rawSerialInput);
  const formattedSerial = sanitizedSerial ? formatSerialValue(sanitizedSerial) : '';
  const serialHasDigit = serialContainsDigit(formattedSerial);
  const serialDigitsOnly = formattedSerial.replace(/[^0-9]/g, '');
  const serialLettersLength = formattedSerial.replace(/[^A-Z]/g, '').length;
  const serialWidth = serialDigitsOnly
    ? computeDigitWidth(serialDigitsOnly.length, serialLettersLength)
    : 0;
  const serialStartInt = serialDigitsOnly ? parseInt(serialDigitsOnly, 10) : Number.NaN;
  const hasSerialLetters = /[A-Z]/.test(formattedSerial);

  let quantity = parseInt(fields.quantity?.value ?? '1', 10);
  if (!Number.isFinite(quantity) || quantity < 1) quantity = 1;

  const ready = Boolean(year.length === 4 && categoryCode.length === 4 && office.length === 4 && serialHasDigit);
  const signature = [year, categoryCode, glaVal, office, formattedSerial, quantity].join('|');

  return {
    ready,
    year,
    category: categoryValue,
    category_code: categoryCode,
    gla: glaVal,
    office,
    serialValue: formattedSerial,
    serialHasDigit,
    serialHasLetters: hasSerialLetters,
    serialDigitsOnly,
    serialWidth,
    serialStartInt,
    quantity,
    signature,
  };
}

function buildPreviewData(base) {
  if (!base.ready || !base.serialValue) return null;
  const count = base.quantity;
  const firstSerial = base.serialValue;
  let lastSerial = firstSerial;

  for (let i = 1; i < count; i += 1) {
    lastSerial = incrementSerialValue(lastSerial);
  }

  const firstPn = `${base.year}-${base.category_code}-${base.gla || ''}-${firstSerial}-${base.office}`;
  const lastPn = `${base.year}-${base.category_code}-${base.gla || ''}-${lastSerial}-${base.office}`;

  return {
    count,
    firstSerial,
    lastSerial,
    firstPn,
    lastPn,
    isBulk: count > 1,
  };
}

class PropertyRowsManager {
  constructor(form, options = {}) {
    this.form = form;
    this.options = options || {};
    this.onChange = typeof this.options.onChange === 'function' ? this.options.onChange : null;
    this.container = form.querySelector('[data-property-rows-container]');
    this.template = form.querySelector('template[data-property-row-template]');
    this.quantityInput = form.querySelector('[data-add-field="quantity"]');
    this.invalidReasons = new WeakMap();
    this.cachedBaseSerial = '';
    this.serialCache = [];
    this.latestBase = {};

    if (this.quantityInput) {
      this.quantityInput.addEventListener('input', () => {
        this.sanitizeQuantity();
        this.sync(this.latestBase);
      });
    }

    if (this.container) {
      this.container.addEventListener('input', (event) => this.handleInput(event));
      this.container.addEventListener('blur', (event) => this.handleBlur(event), true);
      this.container.addEventListener('click', (event) => this.handleRemove(event));
    }

    this.emitChange('init');
  }

  sanitizeQuantity() {
    if (!this.quantityInput) return 1;
    const raw = String(this.quantityInput.value || '').replace(/\D/g, '');
    let value = parseInt(raw || '1', 10);
    if (!Number.isFinite(value) || value < 1) value = 1;
    this.quantityInput.value = String(value);
    return value;
  }

  getQuantity() {
    return this.sanitizeQuantity();
  }

  hasRows() {
    return Boolean(this.container && this.container.querySelector('[data-property-row]'));
  }

  resetCaches(baseSerial) {
    const normalized = baseSerial || '';
    if (this.cachedBaseSerial !== normalized) {
      this.cachedBaseSerial = normalized;
      this.serialCache = normalized ? [normalized] : [];
    }
  }

  getSerialForIndex(baseSerial, index) {
    if (!baseSerial) return '';
    this.resetCaches(baseSerial);
    if (!this.serialCache.length) {
      this.serialCache = [baseSerial];
    }
    while (this.serialCache.length <= index) {
      const previous = this.serialCache[this.serialCache.length - 1];
      const next = incrementSerialValue(previous);
      if (!serialContainsDigit(next)) break;
      this.serialCache.push(next);
    }
    return this.serialCache[index] || '';
  }

  sync(base = {}) {
    if (!this.container || !this.template) return;
    this.latestBase = base || {};
    this.resetCaches(base.serialValue || '');
    const target = this.getQuantity();
    let rows = Array.from(this.container.querySelectorAll('[data-property-row]'));

    while (rows.length < target) {
      const index = rows.length;
      const row = this.createRow(index, this.latestBase);
      if (!row) break;
      rows.push(row);
    }

    while (rows.length > target) {
      const row = rows.pop();
      row?.remove();
    }

    rows = Array.from(this.container.querySelectorAll('[data-property-row]'));
    rows.forEach((row, idx) => this.populateRow(row, idx, this.latestBase, false));
    this.updateRemoveButtons();
    this.checkDuplicates();
    this.emitChange('sync');
  }

  createRow(index, base) {
    if (!this.template) return null;
    const fragment = document.importNode(this.template.content, true);
    const row = fragment.querySelector('[data-property-row]');
    if (!row) return null;
    this.populateRow(row, index, base, true);
    this.container.appendChild(fragment);
    return row;
  }

  populateRow(row, index, base, isNew) {
    if (!row) return;
    row.dataset.rowIndex = String(index);
    const display = row.querySelector('[data-row-index]');
    if (display) {
      display.textContent = String(index + 1);
    }

    row.querySelectorAll('[data-row-field]').forEach((input) => {
      if (!(input instanceof HTMLInputElement)) return;
      const field = input.dataset.rowField;
      if (!field) return;
      const nameKey = input.dataset.rowName || field;
      input.name = `property_numbers_components[${index + 1}][${nameKey}]`;

      const baseValue = this.resolveBaseValue(field, base, index);

      if (field === 'category') {
        input.value = baseValue ?? input.value ?? '';
        input.dataset.autofill = baseValue ? '1' : '';
        input.dataset.autofillValue = baseValue || '';
        return;
      }

      const shouldAutofill = input.dataset.autofill === '1' || !input.value || isNew;

      if (field === 'serial' && !baseValue && !shouldAutofill) {
        const formatted = formatSerialValue(input.value);
        const display = stripSerialPadding(formatted);
        if (display !== input.value) {
          input.value = display;
        }
      }

      if (baseValue) {
        if (shouldAutofill || input.dataset.autofill === '1') {
          input.value = field === 'serial' ? displaySerialValue(baseValue) : baseValue;
          input.dataset.autofill = '1';
          input.dataset.autofillValue = field === 'serial' ? displaySerialValue(baseValue) : baseValue;
        }
      } else {
        // If GLA base becomes empty and this field was autofilled, clear it to mirror the dropdown reset
        if (field === 'gla' && (shouldAutofill || input.dataset.autofill === '1')) {
          input.value = '';
        }
        if (input.dataset.autofill === '1') {
          delete input.dataset.autofill;
          delete input.dataset.autofillValue;
        }
      }
    });
    this.validateRow(row);
  }

  resolveBaseValue(field, base, index) {
    if (!base) return '';
    switch (field) {
      case 'year':
        return base.year && base.year.length === 4 ? base.year : '';
      case 'category':
        return base.category_code || '';
      case 'gla':
        return base.gla || '';
      case 'serial':
        if (!base.serialValue) return '';
        return this.getSerialForIndex(base.serialValue, index);
      case 'office':
        return base.office ? base.office.toString().slice(0, 4).toUpperCase() : '';
      default:
        return '';
    }
  }

  updateRemoveButtons() {
    if (!this.container) return;
    const rows = Array.from(this.container.querySelectorAll('[data-property-row]'));
    rows.forEach((row) => {
      const btn = row.querySelector('[data-row-remove]');
      if (!(btn instanceof HTMLButtonElement)) return;
      if (rows.length <= 1) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
      } else {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
      }
    });
  }

  handleInput(event) {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) return;
    const field = target.dataset.rowField;
    if (!field) return;

    const sanitized = this.sanitizeFieldValue(field, target.value || '');
    if (sanitized !== target.value) {
      target.value = sanitized;
    }

    if (target.dataset.autofill === '1' && target.dataset.autofillValue !== sanitized) {
      delete target.dataset.autofill;
      delete target.dataset.autofillValue;
    }

    const row = target.closest('[data-property-row]');
    if (row) {
      this.validateRow(row);
    }
    this.checkDuplicates();
    this.emitChange('input');
  }

  handleBlur(event) {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) return;
    if (target.dataset.rowField !== 'serial') return;
    const formatted = formatSerialValue(target.value || '');
    const display = stripSerialPadding(formatted);
    if (display !== target.value) {
      target.value = display;
    }
    const row = target.closest('[data-property-row]');
    if (row) {
      this.validateRow(row);
    }
    this.checkDuplicates();
    if (target.dataset.duplicate === '1') {
      setTimeout(() => {
        try {
          target.focus({ preventScroll: true });
        } catch (_) {
          target.focus();
        }
      }, 0);
    }
    this.emitChange('blur');
  }

  handleRemove(event) {
    const button = event.target instanceof Element ? event.target.closest('[data-row-remove]') : null;
    if (!button) return;
    event.preventDefault();
    const row = button.closest('[data-property-row]');
    if (!row || !this.container) return;
    const rows = Array.from(this.container.querySelectorAll('[data-property-row]'));
    if (rows.length <= 1) return;
    row.remove();
    const remaining = Array.from(this.container.querySelectorAll('[data-property-row]'));
    if (this.quantityInput) {
      this.quantityInput.value = String(Math.max(1, remaining.length));
    }
    remaining.forEach((r, idx) => this.populateRow(r, idx, this.latestBase, false));
    this.updateRemoveButtons();
    this.checkDuplicates();
    this.emitChange('remove');
  }

  sanitizeFieldValue(field, value) {
    switch (field) {
      case 'year':
        return value.replace(/\D/g, '').slice(0, 4);
      case 'gla':
        return value.replace(/\D/g, '').slice(0, 4);
      case 'serial':
        return stripSerialPadding(sanitizeSerialInput(value));
      case 'office':
        return value.replace(/\D/g, '').slice(0, 4);
      case 'category':
        return value.replace(/\D/g, '').slice(0, 4);
      default:
        return value;
    }
  }

  setInvalidReason(input, reason) {
    if (!(input instanceof HTMLInputElement)) return;
    let set = this.invalidReasons.get(input);
    if (!set) {
      set = new Set();
      this.invalidReasons.set(input, set);
    }
    if (!set.has(reason)) {
      set.add(reason);
      ROW_INVALID_CLASSES.forEach((cls) => input.classList.add(cls));
      input.dataset.invalid = '1';
      input.setAttribute('aria-invalid', 'true');
      if (reason === 'duplicate') {
        input.dataset.duplicate = '1';
      }
    }
  }

  clearInvalidReason(input, reason) {
    if (!(input instanceof HTMLInputElement)) return;
    const set = this.invalidReasons.get(input);
    if (!set) return;
    set.delete(reason);
    if (set.size === 0) {
      this.invalidReasons.delete(input);
      ROW_INVALID_CLASSES.forEach((cls) => input.classList.remove(cls));
      delete input.dataset.invalid;
      input.removeAttribute('aria-invalid');
    }
    if (reason === 'duplicate') {
      delete input.dataset.duplicate;
    }
  }

  validateRow(row) {
    const invalidFields = new Set();
    if (!row) return invalidFields;

    const inputs = Array.from(row.querySelectorAll('[data-row-field]')).filter((input) => input instanceof HTMLInputElement);

    inputs.forEach((input) => {
      const field = input.dataset.rowField;
      if (!field) return;

      const value = input.value.trim();
      if (!value) {
        this.setInvalidReason(input, 'empty');
        invalidFields.add(field);
        return;
      }
      this.clearInvalidReason(input, 'empty');

      switch (field) {
        case 'year': {
          const isValidYear = /^\d{4}$/.test(value);
          if (!isValidYear) {
            this.setInvalidReason(input, 'format');
            invalidFields.add('year');
          } else {
            this.clearInvalidReason(input, 'format');
          }
          break;
        }
        case 'category': {
          const isValidCategory = /^\d{4}$/.test(value);
          if (!isValidCategory) {
            this.setInvalidReason(input, 'format');
            invalidFields.add('category');
          } else {
            this.clearInvalidReason(input, 'format');
          }
          break;
        }
        case 'gla': {
          const isValidGla = /^\d{1,4}$/.test(value);
          if (!isValidGla) {
            this.setInvalidReason(input, 'format');
            invalidFields.add('gla');
          } else {
            this.clearInvalidReason(input, 'format');
          }
          break;
        }
        case 'serial': {
          const formatted = formatSerialValue(value);
          const display = stripSerialPadding(formatted);
          if (display !== input.value) input.value = display;
          if (!serialContainsDigit(formatted)) {
            this.setInvalidReason(input, 'digit');
            invalidFields.add('serial');
          } else {
            this.clearInvalidReason(input, 'digit');
          }
          break;
        }
        case 'office': {
          const isValidOffice = /^\d{4}$/.test(value);
          if (!isValidOffice) {
            this.setInvalidReason(input, 'format');
            invalidFields.add('office');
          } else {
            this.clearInvalidReason(input, 'format');
          }
          break;
        }
        default:
          break;
      }
    });

    return invalidFields;
  }

  validateAll() {
    if (!this.container) return { valid: true, fields: new Set() };
    const rows = Array.from(this.container.querySelectorAll('[data-property-row]'));
    const aggregate = new Set();
    let valid = true;
    rows.forEach((row) => {
      const rowErrors = this.validateRow(row);
      if (rowErrors.size) valid = false;
      rowErrors.forEach((field) => aggregate.add(field));
    });
    if (this.checkDuplicates()) {
      valid = false;
      aggregate.add('serial');
    }
    return { valid, fields: aggregate };
  }

  checkDuplicates() {
    if (!this.container) return false;
    const serialInputs = Array.from(this.container.querySelectorAll('[data-row-field="serial"]')).filter((el) => el instanceof HTMLInputElement);
    const seen = new Map();
    const duplicates = new Set();

    serialInputs.forEach((input) => {
      const formatted = formatSerialValue(input.value.trim());
      if (!formatted || !serialContainsDigit(formatted)) {
        this.clearInvalidReason(input, 'duplicate');
        return;
      }
      if (seen.has(formatted)) {
        duplicates.add(input);
        duplicates.add(seen.get(formatted));
      } else {
        seen.set(formatted, input);
      }
    });

    serialInputs.forEach((input) => {
      if (duplicates.has(input)) {
        this.setInvalidReason(input, 'duplicate');
      } else {
        this.clearInvalidReason(input, 'duplicate');
      }
    });

    return duplicates.size > 0;
  }

  focusFirstInvalid() {
    if (!this.container) return;
    const target = this.container.querySelector('[data-invalid="1"], [data-duplicate="1"]');
    if (target instanceof HTMLInputElement) {
      try {
        target.focus({ preventScroll: true });
      } catch (_) {
        target.focus();
      }
    }
  }

  reset() {
    if (this.quantityInput) this.quantityInput.value = '1';
    if (this.container) {
      while (this.container.firstChild) {
        this.container.removeChild(this.container.firstChild);
      }
    }
    this.invalidReasons = new WeakMap();
    this.latestBase = {};
    this.cachedBaseSerial = '';
    this.serialCache = [];
    this.sync(this.latestBase);
    this.emitChange('reset');
  }

  getRowValues() {
    if (!this.container) return [];
    return Array.from(this.container.querySelectorAll('[data-property-row]')).map((row, index) => {
      const values = {
        index,
        year: '',
        category: '',
        gla: '',
        serial: '',
        office: '',
      };
      row.querySelectorAll('[data-row-field]').forEach((input) => {
        if (!(input instanceof HTMLInputElement)) return;
        const field = input.dataset.rowField;
        if (!field) return;
        let value = (input.value || '').trim();
        if (['year', 'category', 'gla', 'office'].includes(field)) {
          value = value.replace(/\s+/g, '');
        }
        if (field === 'serial') {
          value = formatSerialValue(value);
        }
        values[field] = value.toUpperCase();
      });
      values.propertyNumber = this.composePropertyNumber(values);
      return values;
    });
  }

  composePropertyNumber(fields = {}) {
    const segment = (value, fallback) => {
      if (!value) return fallback;
      const trimmed = String(value).trim();
      return trimmed === '' ? fallback : trimmed.toUpperCase();
    };
    const year = segment(fields.year, '----');
    const category = segment(fields.category, '----');
    const gla = segment(fields.gla, '----');
    const serial = segment(fields.serial, '-----');
    const office = segment(fields.office, '----');
    return [year, category, gla, serial, office].join('-');
  }

  areRowsComplete(rows = null) {
    const dataset = Array.isArray(rows) ? rows : this.getRowValues();
    if (!dataset.length) return false;
    return dataset.every((row) => Boolean(row.year && row.category && row.gla && row.serial && row.office));
  }

  emitChange(reason = 'sync') {
    if (typeof this.onChange !== 'function') return;
    const rows = this.getRowValues();
    try {
      this.onChange({
        reason,
        rows,
        complete: this.areRowsComplete(rows),
      });
    } catch (error) {
      console.error('PropertyRowsManager onChange handler failed', error);
    }
  }
}

class SerialModelRowsManager {
  constructor(form, rowsManager, options = {}) {
    this.form = form;
    this.rowsManager = rowsManager;
    this.options = options || {};
    this.toast = typeof this.options.toast === 'function' ? this.options.toast : null;

    this.section = form.querySelector('[data-serial-model-section]');
    this.trigger = form.querySelector('[data-serial-model-trigger]');
    this.panel = form.querySelector('[data-serial-model-panel]');
    this.container = form.querySelector('[data-serial-model-container]');
    this.template = form.querySelector('template[data-serial-model-template]');
  this.messageEl = form.querySelector('[data-serial-model-message]');
  this.modelGenerator = form.querySelector('[data-serial-model-panel] [data-model-generator]');
  this.allowSerial = true;
  this.allowModel = true;
  this.rows = [];
  this.rowsComplete = false;
  this.isLocked = true;
  this.hasAutoOpened = false;
    this.lockMessage = '';
    this.invalidReasons = new WeakMap();

    if (this.trigger) {
      this.trigger.addEventListener('click', (event) => this.handleTriggerClick(event));
    }
    if (this.rowsManager && typeof this.rowsManager.onChange === 'function') {
      // no-op, just documenting dependency
    }
    // Wire up "Generate Model No." input to live-fill all model_no fields
    if (this.modelGenerator) {
      this.modelGenerator.addEventListener('input', () => this.applyModelGenerator());
    }
  }

  handleRowsChanged(payload = {}) {
    this.rows = Array.isArray(payload.rows) ? payload.rows : [];
    this.rowsComplete = Boolean(payload.complete);
    this.refresh();
  }

  refresh() {
    this.buildRows(this.rows);
    this.updateAvailability();
    this.applyOptionState();
    this.applyModelGenerator();
  }

  handleTriggerClick(event) {
    if (!this.trigger) return;
    const expanded = this.trigger.getAttribute('aria-expanded') === 'true';
    if (this.isLocked && !expanded) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (this.toast && this.lockMessage) {
        this.toast('error', this.lockMessage);
      }
    }
  }

  buildRows(rows = []) {
    if (!this.container || !this.template) return;
    const existingBySignature = new Map();
    const existingByIndex = new Map();

    Array.from(this.container.querySelectorAll('[data-serial-model-row]')).forEach((rowEl) => {
      const serialInput = rowEl.querySelector('[data-serial-model-field="serial_no"]');
      const modelInput = rowEl.querySelector('[data-serial-model-field="model_no"]');
      const record = {
        serial: serialInput?.value || '',
        model: modelInput?.value || '',
      };
      const signature = (rowEl.dataset.serialSignature || serialInput?.dataset.propertyNumber || '').trim().toUpperCase();
      if (signature) {
        existingBySignature.set(signature, record);
      }
      const index = Number(rowEl.dataset.serialRowIndex ?? -1);
      if (!Number.isNaN(index) && index >= 0) {
        existingByIndex.set(index, record);
      }
    });

    this.container.innerHTML = '';

    rows.forEach((rowData, index) => {
      const fragment = document.importNode(this.template.content, true);
      const rowEl = fragment.querySelector('[data-serial-model-row]');
      if (!rowEl) return;
      const signature = (rowData.propertyNumber || '').trim().toUpperCase();
      const stored = existingBySignature.get(signature) || existingByIndex.get(index) || null;
      this.populateRow(rowEl, rowData, index, stored);
      this.container.appendChild(fragment);
    });
  }

  populateRow(rowEl, rowData, index, storedValues = null) {
    if (!rowEl) return;
    rowEl.dataset.serialRowIndex = String(index);
    rowEl.dataset.serialSignature = (rowData.propertyNumber || '').trim().toUpperCase();
    const pnDisplay = rowEl.querySelector('[data-serial-model-pn]');
    if (pnDisplay) {
      pnDisplay.textContent = rowData.propertyNumber || '---- ---- ----';
    }

    const serialInput = rowEl.querySelector('[data-serial-model-field="serial_no"]');
    const modelInput = rowEl.querySelector('[data-serial-model-field="model_no"]');

    if (serialInput) {
      serialInput.name = `property_numbers_components[${index + 1}][serial_no]`;
      serialInput.dataset.propertyNumber = rowData.propertyNumber || '';
      if (storedValues && typeof storedValues.serial === 'string') {
        serialInput.value = storedValues.serial;
      }
    }

    if (modelInput) {
      modelInput.name = `property_numbers_components[${index + 1}][model_no]`;
      modelInput.dataset.propertyNumber = rowData.propertyNumber || '';
      if (storedValues && typeof storedValues.model === 'string') {
        modelInput.value = storedValues.model;
      } else if (this.modelGenerator && this.modelGenerator.value) {
        modelInput.value = this.modelGenerator.value;
      }
    }
  }

  applyModelGenerator() {
    if (!this.container || !this.modelGenerator) return;
    const val = (this.modelGenerator.value || '').toUpperCase();
    const rows = Array.from(this.container.querySelectorAll('[data-serial-model-row]'));
    rows.forEach((rowEl) => {
      const input = rowEl.querySelector('[data-serial-model-field="model_no"]');
      if (input && !input.disabled) {
        input.value = val;
      }
    });
  }

  updateAvailability() {
    const hasRows = Array.isArray(this.rows) && this.rows.length > 0;

    if (!hasRows || !this.rowsComplete) {
      this.isLocked = true;
      this.lockMessage = 'Complete property number rows before adding serial or model numbers.';
      this.hasAutoOpened = false; // reset flag if relocked
    } else {
      // If just unlocked, auto-open once
      if (this.isLocked) {
        this.hasAutoOpened = false;
      }
      this.isLocked = false;
      this.lockMessage = '';
    }

    if (this.messageEl) {
      if (this.isLocked) {
        this.messageEl.textContent = this.lockMessage;
        this.messageEl.classList.remove('hidden');
      } else {
        this.messageEl.textContent = 'Provide per-row Serial and Model numbers as needed.';
        this.messageEl.classList.remove('hidden');
      }
    }

    if (this.trigger) {
      if (this.isLocked) {
        this.trigger.setAttribute('aria-disabled', 'true');
        this.trigger.classList.add('opacity-60', 'cursor-not-allowed');
        if (this.trigger.getAttribute('aria-expanded') === 'true') {
          window.setTimeout(() => {
            if (this.trigger && this.trigger.getAttribute('aria-expanded') === 'true') {
              this.trigger.click();
            }
          }, 0);
        }
      } else {
        this.trigger.removeAttribute('aria-disabled');
        this.trigger.classList.remove('opacity-60', 'cursor-not-allowed');
        // Only auto-open ONCE after unlock
        if (!this.hasAutoOpened && this.trigger.getAttribute('aria-expanded') !== 'true') {
          window.setTimeout(() => {
            if (this.trigger && this.trigger.getAttribute('aria-expanded') !== 'true') {
              this.trigger.click();
              this.hasAutoOpened = true;
            }
          }, 0);
        }
      }
    }
  }

  applyOptionState() {
    const rows = Array.from(this.container?.querySelectorAll('[data-serial-model-row]') || []);
    rows.forEach((rowEl) => {
      const serialInput = rowEl.querySelector('[data-serial-model-field="serial_no"]');
      const serialWrap = serialInput?.closest('.flex-1');
      const modelInput = rowEl.querySelector('[data-serial-model-field="model_no"]');
      const modelWrap = modelInput?.closest('.flex-1');

      if (serialInput) {
        const enabled = !this.isLocked;
        serialInput.disabled = !enabled;
        if (serialWrap) {
          serialWrap.classList.toggle('opacity-50', !enabled);
        }
        if (!enabled) {
          this.clearInvalid(serialInput, 'empty');
          this.clearInvalid(serialInput, 'format');
        }
      }

      if (modelInput) {
        const enabled = !this.isLocked;
        modelInput.disabled = !enabled;
        if (modelWrap) {
          modelWrap.classList.toggle('opacity-50', !enabled);
        }
        if (!enabled) {
          this.clearInvalid(modelInput, 'empty');
          this.clearInvalid(modelInput, 'format');
        }
      }
    });
  }

  validate() {
    const errors = new Set();
    if (this.isLocked || !this.rows.length) {
      return { ok: true, fields: errors };
    }

  const serialRegex = /^[A-Za-z0-9]{1,100}$/;
  const modelRegex = /^[A-Za-z0-9]{1,100}$/;

    const rows = Array.from(this.container?.querySelectorAll('[data-serial-model-row]') || []);
    rows.forEach((rowEl) => {
      const serialInput = rowEl.querySelector('[data-serial-model-field="serial_no"]');
      if (serialInput && !serialInput.disabled) {
        const value = (serialInput.value || '').trim().toUpperCase();
        serialInput.value = value;
        if (!value) {
          this.clearInvalid(serialInput, 'empty');
          this.clearInvalid(serialInput, 'format');
        } else if (!serialRegex.test(value)) {
          this.clearInvalid(serialInput, 'empty');
          this.markInvalid(serialInput, 'format');
          errors.add('serial_no');
        } else {
          this.clearInvalid(serialInput, 'empty');
          this.clearInvalid(serialInput, 'format');
        }
      }

      const modelInput = rowEl.querySelector('[data-serial-model-field="model_no"]');
      if (modelInput && !modelInput.disabled) {
        const value = (modelInput.value || '').trim().toUpperCase();
        modelInput.value = value;
        if (!value) {
          this.clearInvalid(modelInput, 'empty');
          this.clearInvalid(modelInput, 'format');
        } else if (!modelRegex.test(value)) {
          this.clearInvalid(modelInput, 'empty');
          this.markInvalid(modelInput, 'format');
          errors.add('model_no');
        } else {
          this.clearInvalid(modelInput, 'empty');
          this.clearInvalid(modelInput, 'format');
        }
      }
    });

    if (errors.size > 0) {
      const summary = buildErrorSummary(errors);
      return { ok: false, fields: errors, message: summary };
    }
    return { ok: true, fields: errors };
  }

  markInvalid(input, reason) {
    if (!(input instanceof HTMLInputElement)) return;
    let set = this.invalidReasons.get(input);
    if (!set) {
      set = new Set();
      this.invalidReasons.set(input, set);
    }
    if (!set.has(reason)) {
      set.add(reason);
      ROW_INVALID_CLASSES.forEach((cls) => input.classList.add(cls));
      input.dataset.invalid = '1';
      input.setAttribute('aria-invalid', 'true');
    }
  }

  clearInvalid(input, reason) {
    if (!(input instanceof HTMLInputElement)) return;
    const set = this.invalidReasons.get(input);
    if (!set) return;
    set.delete(reason);
    if (set.size === 0) {
      this.invalidReasons.delete(input);
      ROW_INVALID_CLASSES.forEach((cls) => input.classList.remove(cls));
      input.removeAttribute('data-invalid');
      input.removeAttribute('aria-invalid');
    }
  }

  focusFirstInvalid() {
    if (!this.container) return;
    const target = this.container.querySelector('input[data-invalid="1"]');
    if (target instanceof HTMLInputElement) {
      try {
        target.focus({ preventScroll: true });
      } catch (_) {
        target.focus();
      }
    }
  }

  reset() {
    this.rows = [];
    this.rowsComplete = false;
    this.invalidReasons = new WeakMap();
    if (this.container) {
      while (this.container.firstChild) {
        this.container.removeChild(this.container.firstChild);
      }
    }
    this.updateAvailability();
    this.applyOptionState();
  }
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
  if (!base.ready || !base.serialValue || base.serialHasLetters) {
    return { available: true, conflict_serials: [] };
  }

  const params = new URLSearchParams({
    year_procured: base.year,
    office_code: base.office,
    start_serial: base.serialValue,
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

  if (!base.ready || !base.serialValue || base.serialHasLetters) {
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
  if (!elements.serialInput || !base.ready || !base.serialValue || base.serialHasLetters) {
    return { available: true, conflicts: [] };
  }
  if (!base.gla) {
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
    while (list.firstChild) list.removeChild(list.firstChild);
    return;
  }

  wrap.classList.remove('hidden');
  while (list.firstChild) list.removeChild(list.firstChild);

  const makeRow = (label, value) => {
    const row = document.createElement('div');
    const strong = document.createElement('span');
    strong.classList.add('font-semibold');
    strong.textContent = `${label}: `;
    row.appendChild(strong);
    row.appendChild(document.createTextNode(value));
    return row;
  };

  if (data.isBulk) {
    list.appendChild(makeRow('Total', `${data.count} items`));
    list.appendChild(makeRow('Serial range', `${data.firstSerial} → ${data.lastSerial}`));
    list.appendChild(makeRow('First PN', data.firstPn));
    list.appendChild(makeRow('Last PN', data.lastPn));
  } else {
    list.appendChild(makeRow('Property Number', data.firstPn));
  }

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
function handleSuccess(form, elements, result) {
  const data = result?.data || {};
  const createdCount = Number(data.created_count || 0);
  const hasSkipped = Array.isArray(data.skipped_serials) && data.skipped_serials.length > 0;

  if (createdCount === 0) {
    const msg = hasSkipped
      ? 'No items were created. All property numbers were already in use.'
      : 'No items were created. Please review your inputs and try again.';
  showToast(msg, 'error');
    hideMessage(elements.feedback);
    showMessage(elements.error, msg);
    return; // Do not reset/close/reload when nothing was created
  }

  const toastMessage = hasSkipped
    ? 'Items added successfully. Some property numbers were skipped because they are already in use.'
    : 'Items added successfully.';
  
  // Show toast first, then reload after a short delay to ensure toast is visible
  showToast(toastMessage, 'success');

  hideMessage(elements.error);
  hideMessage(elements.feedback);

  form.reset();
  form.dispatchEvent(new Event('reset'));
  window.dispatchEvent(new CustomEvent('close-modal', { detail: 'create-item' }));
  
  // Optionally store created property numbers for post-reload highlighting if needed
  if (Array.isArray(data.created_pns) && data.created_pns.length > 0) {
    try {
      sessionStorage.setItem('createdPropertyNumbers', JSON.stringify(data.created_pns));
    } catch (_) {}
  }
  
  // Delay reload to ensure toast is visible
  setTimeout(() => {
    window.location.reload();
  }, 500);
}

function handleError(elements, error) {
  const fallback = 'Failed to add items. Please check the form and try again.';
  const message = (error && typeof error.message === 'string' && error.message.trim()) ? error.message : fallback;
  showToast(message, 'error');
  hideMessage(elements.feedback);
  showMessage(elements.error, message);
}

function attachOfficeValidation(form) {
  const input = form.querySelector('[data-add-field="office"]');
  const errorEl = form.querySelector(OFFICE_ERROR_SELECTOR);
  if (!input || !errorEl) return;

  const validate = () => {
    const raw = (input.value || '').trim();
    if (raw === '') {
      input.setCustomValidity('');
      errorEl.classList.add('hidden');
      return;
    }

    const digits = raw.replace(/\D/g, '');
    const isValid = /^\d{4}$/.test(digits);
    input.value = digits.slice(0, 4);

    if (!isValid) {
      input.setCustomValidity('Office code must be exactly 4 digits.');
      errorEl.textContent = 'Office code must be exactly 4 digits.';
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
    // Short placeholder to avoid widening native dropdown popover
    sel.appendChild(new Option('- Category -', ''));
    cats.forEach((cat) => {
      if (cat && typeof cat === 'object') {
        const option = new Option(cat.name || '', cat.id ?? cat.code ?? '');
        const code = cat.code || cat.category_code || '';
        if (code) option.dataset.categoryCode = code;
        option.dataset.categoryName = cat.name || '';
        if (cat.id !== undefined && cat.id !== null) option.dataset.categoryId = String(cat.id);
        sel.appendChild(option);
      } else {
        const option = new Option(String(cat || ''), String(cat || ''));
        sel.appendChild(option);
      }
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
    // Short placeholder to avoid widening native dropdown popover
    sel.appendChild(new Option('- Office -', ''));
    offices.forEach(o => {
      const code = typeof o === 'object' ? (o.code ?? '') : o;
      const sanitized = String(code || '').replace(/\D/g, '').slice(0, 4);
      const finalCode = sanitized ? sanitized.padStart(4, '0') : '';
      const name = typeof o === 'object' ? (o.name ?? '') : '';
      const label = name || finalCode;
      const option = new Option(label, finalCode);
      option.dataset.officeCode = finalCode;
      option.dataset.officeName = name;
      sel.appendChild(option);
    });
    sel.disabled = false;
  });
}

function initCategoryOfficeManagement() {
  if (typeof document === 'undefined') return;

  const categoryListBody = document.getElementById('category-list-body');
  const officeListBody = document.getElementById('office-list-body');
  const categoryRowTemplate = document.querySelector('template[data-category-row-template]');
  const categoryEmptyTemplate = document.querySelector('template[data-category-empty-template]');
  const officeRowTemplate = document.querySelector('template[data-office-row-template]');
  const officeEmptyTemplate = document.querySelector('template[data-office-empty-template]');
  const categoryAddBtn = document.getElementById('category-add-btn');
  const officeAddBtn = document.getElementById('office-add-btn');
  const newCategoryInput = document.getElementById('new-category-name');
  const newCategoryCodeInput = document.getElementById('new-category-code');
  const newOfficeCodeInput = document.getElementById('new-office-code');
  const newOfficeNameInput = document.getElementById('new-office-name');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const enforceDigits = (input) => {
    if (!input) return;
    input.value = String(input.value || '').replace(/\D/g, '').slice(0, 4);
  };

  newCategoryCodeInput?.addEventListener('input', () => enforceDigits(newCategoryCodeInput));
  newOfficeCodeInput?.addEventListener('input', () => enforceDigits(newOfficeCodeInput));

  if (!categoryListBody && !officeListBody) return;

  const state = {
    categories: Array.isArray(window.__serverCategories) ? window.__serverCategories.map(normalizeCategory) : [],
    offices: Array.isArray(window.__serverOffices) ? window.__serverOffices.map(normalizeOffice) : [],
  };

  const renderCategories = () => {
    if (!categoryListBody) return;
    while (categoryListBody.firstChild) categoryListBody.removeChild(categoryListBody.firstChild);
    if (!state.categories.length) {
      if (categoryEmptyTemplate) {
        categoryListBody.appendChild(document.importNode(categoryEmptyTemplate.content, true));
      }
      return;
    }
    state.categories.forEach((cat, idx) => {
      if (!categoryRowTemplate) return;
      const fragment = document.importNode(categoryRowTemplate.content, true);
      const row = fragment.querySelector('[data-category-row]');
      if (row) {
        row.dataset.categoryId = cat.id ?? '';
        row.dataset.index = String(idx);
      }
      const nameEl = fragment.querySelector('[data-category-name]');
      if (nameEl) nameEl.textContent = cat.name || 'N/A';
      const codeEl = fragment.querySelector('[data-category-code]');
      if (codeEl) codeEl.textContent = cat.code || 'N/A';
      const manageGlaBtn = fragment.querySelector('[data-manage-gla]');
      if (manageGlaBtn) {
        if (cat.id !== undefined && cat.id !== null) manageGlaBtn.setAttribute('data-id', String(cat.id));
        manageGlaBtn.setAttribute('data-name', cat.name || '');
        if (cat.code) manageGlaBtn.setAttribute('data-code', cat.code);
      }
      const deleteBtn = fragment.querySelector('[data-delete-cat]');
      if (deleteBtn) {
        if (cat.id !== undefined && cat.id !== null) deleteBtn.setAttribute('data-id', String(cat.id));
        deleteBtn.setAttribute('data-name', cat.name || '');
        if (cat.code) deleteBtn.setAttribute('data-code', cat.code);
      }
      categoryListBody.appendChild(fragment);
    });
  };

  const renderOffices = () => {
    if (!officeListBody) return;
    while (officeListBody.firstChild) officeListBody.removeChild(officeListBody.firstChild);
    if (!state.offices.length) {
      if (officeEmptyTemplate) {
        officeListBody.appendChild(document.importNode(officeEmptyTemplate.content, true));
      }
      return;
    }
    state.offices.forEach((office, idx) => {
      if (!officeRowTemplate) return;
      const fragment = document.importNode(officeRowTemplate.content, true);
      const row = fragment.querySelector('[data-office-row]');
      if (row) {
        row.dataset.officeCode = office.code || '';
        row.dataset.index = String(idx);
      }
      const codeEl = fragment.querySelector('[data-office-code]');
      if (codeEl) codeEl.textContent = office.code || 'N/A';
      const nameEl = fragment.querySelector('[data-office-name]');
      if (nameEl) nameEl.textContent = office.name || 'N/A';
      const viewBtn = fragment.querySelector('[data-view-office]');
      if (viewBtn) {
        viewBtn.setAttribute('data-code', office.code || '');
        if (office.name) viewBtn.setAttribute('data-name', office.name);
      }
      const deleteBtn = fragment.querySelector('[data-delete-office]');
      if (deleteBtn) deleteBtn.setAttribute('data-code', office.code || '');
      officeListBody.appendChild(fragment);
    });
  };

  const updateCategoryState = (categories) => {
    state.categories = Array.isArray(categories) ? categories.map(normalizeCategory) : [];
    window.__serverCategories = state.categories;
    const merged = buildCategoryCodeMap(state.categories, {});
    window.__servercategoryCodeMap = merged;
    window.__serverCategoryCodeMap = merged;
    window.CATEGORY_CODE_MAP = merged;
    renderCategories();
    window.dispatchEvent(new Event('server:categories:updated'));
  };

  const updateOfficeState = (offices) => {
    state.offices = Array.isArray(offices) ? offices.map(normalizeOffice) : [];
    window.__serverOffices = state.offices;
    renderOffices();
    window.dispatchEvent(new Event('server:offices:updated'));
  };

  const fetchJSON = async (url, options = {}) => {
    const response = await fetch(url, options);
    const contentType = response.headers.get('content-type') || '';
    if (!response.ok) {
      let message = `Request failed (${response.status})`;
      if (contentType.includes('application/json')) {
        const body = await response.json().catch(() => null);
        if (body?.message) message = body.message;
      }
      throw new Error(message);
    }
    if (!contentType.includes('application/json')) return null;
    return response.json().catch(() => null);
  };

  const fetchCategoriesFromServer = async () => {
    try {
      const data = await fetchJSON('/admin/api/categories', { headers: { Accept: 'application/json' } });
      if (data && Array.isArray(data.data)) {
        updateCategoryState(data.data);
      }
    } catch (error) {
      console.warn('Failed to load categories', error);
      renderCategories();
    }
  };

  const fetchOfficesFromServer = async () => {
    try {
      const data = await fetchJSON('/admin/api/offices', { headers: { Accept: 'application/json' } });
      if (data && Array.isArray(data.data)) {
        updateOfficeState(data.data);
      }
    } catch (error) {
      console.warn('Failed to load offices', error);
      renderOffices();
    }
  };

  renderCategories();
  renderOffices();

  if (!state.categories.length) {
    fetchCategoriesFromServer();
  }
  if (!state.offices.length) {
    fetchOfficesFromServer();
  }

  categoryAddBtn?.addEventListener('click', async () => {
    const name = (newCategoryInput?.value || '').trim();
    const rawCode = (newCategoryCodeInput?.value || '').trim();
    const code = rawCode.replace(/\D/g, '').slice(0, 4);
    if (!name) {
  showToast('Please enter a category name.', 'error');
      return;
    }
    if (code.length !== 4) {
  showToast('Category code must be exactly 4 digits.', 'error');
      return;
    }
    try {
      await fetchJSON('/admin/api/categories', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
        },
        body: JSON.stringify({ name, category_code: code }),
      });
      if (newCategoryInput) newCategoryInput.value = '';
      if (newCategoryCodeInput) newCategoryCodeInput.value = '';
      await fetchCategoriesFromServer();
  showToast('Category added successfully.', 'success');
    } catch (error) {
  showToast(error.message || 'Failed to add category. Please try again.', 'error');
    }
  });

  officeAddBtn?.addEventListener('click', async () => {
    const rawCode = (newOfficeCodeInput?.value || '').trim();
    const code = rawCode.replace(/\D/g, '').slice(0, 4);
    const displayName = (newOfficeNameInput?.value || '').trim();
    if (code.length !== 4) {
  showToast('Office code must be exactly 4 digits.', 'error');
      return;
    }
    try {
      await fetchJSON('/admin/api/offices', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
        },
        body: JSON.stringify({ code, name: displayName }),
      });
      if (newOfficeCodeInput) newOfficeCodeInput.value = '';
      if (newOfficeNameInput) newOfficeNameInput.value = '';
      await fetchOfficesFromServer();
  showToast('Office added successfully.', 'success');
    } catch (error) {
  showToast(error.message || 'Failed to add office. Please try again.', 'error');
    }
  });

  categoryListBody?.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;

    const deleteBtn = target.closest('[data-delete-cat]');
    if (deleteBtn) {
      const nameAttr = deleteBtn.getAttribute('data-name') || '';
      if (!window.confirm(`Delete category "${nameAttr}"?`)) return;
      try {
        await fetchJSON(`/admin/api/categories/${encodeURIComponent(nameAttr)}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        await fetchCategoriesFromServer();
  showToast('Category deleted successfully.', 'success');
      } catch (error) {
  showToast(error.message || 'Failed to delete category. Please try again.', 'error');
      }
      return;
    }

    const manageGlaBtn = target.closest('[data-manage-gla]');
    if (manageGlaBtn) {
      const categoryId = manageGlaBtn.getAttribute('data-id');
      const categoryName = manageGlaBtn.getAttribute('data-name') || '';
      if (categoryId) {
        openManageGLAModal(categoryId, categoryName);
      }
    }
  });

  officeListBody?.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;

    const deleteBtn = target.closest('[data-delete-office]');
    if (deleteBtn) {
      const codeAttr = deleteBtn.getAttribute('data-code') || '';
      if (!window.confirm(`Delete office "${codeAttr}"?`)) return;
      try {
        await fetchJSON(`/admin/api/offices/${encodeURIComponent(codeAttr)}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        await fetchOfficesFromServer();
  showToast('Office deleted successfully.', 'success');
      } catch (error) {
  showToast(error.message || 'Failed to delete office. Please try again.', 'error');
      }
      return;
    }

    const viewBtn = target.closest('[data-view-office]');
    if (viewBtn) {
      const codeAttr = viewBtn.getAttribute('data-code') || '';
  window.showToast(`View items for office: ${codeAttr}`, 'info');
    }
  });

  // GLA Management
  initGLAManagement();
}

function initGLAManagement() {
  if (typeof document === 'undefined') return;

  const glaListBody = document.getElementById('gla-list-body');
  const glaRowTemplate = document.querySelector('template[data-gla-row-template]');
  const glaEmptyTemplate = document.querySelector('template[data-gla-empty-template]');
  const glaAddBtn = document.getElementById('gla-add-btn');
  const newGlaNameInput = document.getElementById('new-gla-name');
  const newGlaCodeInput = document.getElementById('new-gla-code');
  const glaParentIdInput = document.getElementById('gla-parent-id');
  const glaModalTitle = document.getElementById('gla-modal-title');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  if (!glaListBody) return;

  let currentParentId = null;
  let currentGLAs = [];

  const enforceGLADigits = (input) => {
    if (!input) return;
    input.value = String(input.value || '').replace(/\D/g, '').slice(0, 4);
  };

  newGlaCodeInput?.addEventListener('input', () => enforceGLADigits(newGlaCodeInput));

  const renderGLAs = () => {
    if (!glaListBody) return;
    while (glaListBody.firstChild) glaListBody.removeChild(glaListBody.firstChild);
    
    if (!currentGLAs.length) {
      if (glaEmptyTemplate) {
        glaListBody.appendChild(document.importNode(glaEmptyTemplate.content, true));
      }
      return;
    }

    currentGLAs.forEach((gla) => {
      if (!glaRowTemplate) return;
      const fragment = document.importNode(glaRowTemplate.content, true);
      const row = fragment.querySelector('[data-gla-row]');
      if (row) row.dataset.glaId = gla.id ?? '';
      
      const nameEl = fragment.querySelector('[data-gla-name]');
      if (nameEl) nameEl.textContent = gla.name || 'N/A';
      
      const codeEl = fragment.querySelector('[data-gla-code]');
      if (codeEl) codeEl.textContent = gla.category_code || gla.code || 'N/A';
      
      const deleteBtn = fragment.querySelector('[data-delete-gla]');
      if (deleteBtn) {
        deleteBtn.setAttribute('data-id', String(gla.id));
        deleteBtn.setAttribute('data-name', gla.name || '');
      }
      
      glaListBody.appendChild(fragment);
    });
  };

  const fetchJSON = async (url, options = {}) => {
    const response = await fetch(url, options);
    const contentType = response.headers.get('content-type') || '';
    if (!response.ok) {
      let message = `Request failed (${response.status})`;
      if (contentType.includes('application/json')) {
        const body = await response.json().catch(() => null);
        if (body?.message) message = body.message;
      }
      throw new Error(message);
    }
    if (!contentType.includes('application/json')) return null;
    return response.json().catch(() => null);
  };

  const fetchGLAsFromServer = async (parentId) => {
    try {
      const data = await fetchJSON(`/admin/api/categories/${parentId}/glas`, {
        headers: { Accept: 'application/json' }
      });
      if (data && Array.isArray(data.data)) {
        currentGLAs = data.data;
        renderGLAs();
      }
    } catch (error) {
      console.warn('Failed to load GLAs', error);
      renderGLAs();
    }
  };

  window.openManageGLAModal = async (categoryId, categoryName) => {
    currentParentId = categoryId;
    if (glaParentIdInput) glaParentIdInput.value = categoryId;
    if (glaModalTitle) glaModalTitle.textContent = `Manage GLA for: ${categoryName}`;
    if (newGlaNameInput) newGlaNameInput.value = '';
    if (newGlaCodeInput) newGlaCodeInput.value = '';
    
    await fetchGLAsFromServer(categoryId);
    
    // Open modal using Alpine.js
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-gla' }));
    document.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-gla' }));
  };

  glaAddBtn?.addEventListener('click', async () => {
    const name = (newGlaNameInput?.value || '').trim();
    const code = (newGlaCodeInput?.value || '').trim().replace(/\D/g, '');
    
    if (!name) {
  showToast('Please enter a GLA name.', 'error');
      return;
    }
    if (code.length < 1 || code.length > 4) {
  showToast('GLA code must be 1-4 digits.', 'error');
      return;
    }
    if (!currentParentId) {
  showToast('No parent category selected.', 'error');
      return;
    }

    try {
      await fetchJSON(`/admin/api/categories/${currentParentId}/glas`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
        },
        body: JSON.stringify({ name, category_code: code }),
      });
      
      if (newGlaNameInput) newGlaNameInput.value = '';
      if (newGlaCodeInput) newGlaCodeInput.value = '';
      await fetchGLAsFromServer(currentParentId);
  showToast('GLA added successfully.', 'success');
      
      // Notify that GLAs were updated so dropdowns can refresh
      window.dispatchEvent(new Event('server:glas:updated'));
    } catch (error) {
  showToast(error.message || 'Failed to add GLA. Please try again.', 'error');
    }
  });

  glaListBody?.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;

    const deleteBtn = target.closest('[data-delete-gla]');
    if (deleteBtn) {
      const glaId = deleteBtn.getAttribute('data-id');
      const nameAttr = deleteBtn.getAttribute('data-name') || '';
      
      if (!window.confirm(`Delete GLA "${nameAttr}"?`)) return;
      
      try {
        await fetchJSON(`/admin/api/categories/${currentParentId}/glas/${glaId}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        await fetchGLAsFromServer(currentParentId);
  showToast('GLA deleted successfully.', 'success');
        
        // Notify that GLAs were updated
        window.dispatchEvent(new Event('server:glas:updated'));
      } catch (error) {
        showToast(error.message || 'Failed to delete GLA. Please try again.', 'error');
      }
    }
  });
}

function attachCategoryListeners(form) {
  form.querySelectorAll('select[data-category-select]').forEach(sel => {
    sel.addEventListener('change', async (e) => {
      const selectEl = e.target;
      const chosen = (selectEl?.value ?? '').toString();
      const hidden = form.querySelector('input[data-property-segment="category"], input[name="category_code"]');
      if (hidden) {
        const resolved = resolveCategoryCode(selectEl) || resolveCategoryCode(chosen) || '';
        hidden.value = resolved;
        try { hidden.defaultValue = resolved; } catch(_) {}
        hidden.setAttribute('value', resolved);
      }
      const display = form.querySelector('[data-category-display]');
      if (display) {
        const resolved = resolveCategoryCode(selectEl) || resolveCategoryCode(chosen) || '';
        display.value = resolved;
      }

      // Populate GLA dropdown based on selected category
      await populateGLADropdown(form, selectEl.selectedOptions[0]?.dataset?.categoryId);

      form.querySelectorAll('[data-add-field]').forEach(inp => inp.dispatchEvent(new Event('input', { bubbles: true })));
      form.querySelectorAll('[data-add-field="year"], [data-add-field="office"]').forEach(inp => inp.dispatchEvent(new Event('input', { bubbles: true })));
    });
  });
}

async function populateGLADropdown(form, categoryId) {
  const glaSelect = form.querySelector('select[data-gla-select]');
  if (!glaSelect) return;

  // Track latest request to avoid race-condition duplicates
  form.__glaRequestId = (form.__glaRequestId || 0) + 1;
  const currentReq = form.__glaRequestId;

  if (!categoryId) {
    glaSelect.disabled = true;
    glaSelect.value = '';
    return;
  }

  try {
    const response = await fetch(`/admin/api/categories/${categoryId}/glas`, {
      headers: { Accept: 'application/json' }
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch GLAs');
    }

    const data = await response.json();

    // Ignore if a newer request has been made
    if (currentReq !== form.__glaRequestId) {
      return;
    }

    // Clear existing options just before we append the latest set
    while (glaSelect.options.length > 1) {
      glaSelect.remove(1);
    }
    const glas = data.data || [];

    if (glas.length === 0) {
      glaSelect.disabled = true;
      glaSelect.value = '';
      return;
    }

    glas.forEach(gla => {
      const option = document.createElement('option');
      option.value = gla.category_code || gla.code || '';
      option.textContent = gla.name || '';
      option.dataset.glaId = gla.id;
      option.dataset.glaCode = gla.category_code || gla.code || '';
      glaSelect.appendChild(option);
    });

    glaSelect.disabled = false;
  } catch (error) {
    console.error('Error loading GLAs:', error);
    glaSelect.disabled = true;
  }
}

function attachGLAListeners(form) {
  const glaSelect = form.querySelector('select[data-gla-select]');
  if (!glaSelect) return;

  glaSelect.addEventListener('change', (e) => {
    // Trigger input events on form fields to update property number rows
    form.querySelectorAll('[data-add-field]').forEach(inp => {
      inp.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
}

function initAddItemsForm(form) {
  populateCategorySelects(form);
  populateOfficeSelects(form);
  attachCategoryListeners(form);
  let serialManager = null;
  const rowsManager = new PropertyRowsManager(form, {
    onChange: (payload) => {
      if (serialManager) {
        serialManager.handleRowsChanged(payload);
      }
    },
  });
  serialManager = new SerialModelRowsManager(form, rowsManager, { toast: showToast });
  serialManager.handleRowsChanged({
    reason: 'init',
    rows: rowsManager.getRowValues(),
    complete: rowsManager.areRowsComplete(),
  });
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
    serialManager,
  };

  window.addEventListener('server:categories:updated', () => { populateCategorySelects(form); });
  window.addEventListener('server:offices:updated', () => { populateOfficeSelects(form); });
  window.addEventListener('server:glas:updated', () => {
    // Refresh GLA dropdown for currently selected category
    const categorySelect = form.querySelector('select[data-category-select]');
    if (categorySelect && categorySelect.selectedOptions[0]) {
      const categoryId = categorySelect.selectedOptions[0].dataset.categoryId;
      if (categoryId) {
        populateGLADropdown(form, categoryId);
      }
    }
  });
  // Initial population and listeners are already set above; avoid double-attaching
  attachGLAListeners(form);

  attachOfficeValidation(form);

  const resetMessages = () => {
    hideMessage(elements.error);
    hideMessage(elements.feedback);
  };

  const updateState = () => {
    const base = collectBase(form);
    renderPreview(form, base, elements);
    queueSerialCheck(form, elements, base);
    rowsManager.sync(base);
  };

  // Track current section to prevent unwanted rollback
  let currentActiveSection = 'create-item-info';
  let userInitiatedClose = false;

  // Disabled auto-open of next accordion section
  const checkAndOpenNextSection = () => {};

  // Listen for accordion close events to track user-initiated closes
  form.addEventListener('accordion:closed', (event) => {
    const target = event.target;
    if (target && target.id) {
      // Only set userInitiatedClose if it's not a section we're auto-managing
      if (target.id !== 'create-item-info' && !userInitiatedClose) {
        userInitiatedClose = true;
        setTimeout(() => { userInitiatedClose = false; }, 500);
      }
    }
  }, true);

  form.querySelectorAll('[data-add-field]').forEach((input) => {
    input.addEventListener('input', () => {
      updateState();
    });
    input.addEventListener('change', () => {
      updateState();
    });
    input.addEventListener('focus', () => {
      // Update current section when user focuses on a field
      const panel = input.closest('[data-accordion-panel]');
      if (panel && panel.id) {
        currentActiveSection = panel.id;
      }
    });
  });

  // Also listen to property rows container for row changes
  const rowsContainer = form.querySelector('[data-property-rows-container]');
  if (rowsContainer) {
    rowsContainer.addEventListener('input', () => {
      // No auto-open next section
    });
  }

  form.addEventListener('reset', () => {
    elements.lastSerialResult = null;
    if (elements.serialTimer) { clearTimeout(elements.serialTimer); elements.serialTimer = null; }
    if (elements.serialAbort) { elements.serialAbort.abort(); elements.serialAbort = null; }
    applySerialState(elements, 'idle');
    rowsManager.reset();
    elements.serialManager?.reset();
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
    const hasRows = rowsManager.hasRows();

    const errorFields = new Set();

    if (base.serialValue && !base.serialHasDigit) {
      errorFields.add('serial');
      applySerialState(elements, 'error', 'Serial must include at least one number.');
    } else {
      applySerialState(elements, 'idle');
    }

    const rowValidation = rowsManager.validateAll();
    rowValidation.fields.forEach((field) => errorFields.add(field));

    let serialValidation = { ok: true, fields: new Set(), message: '' };
    if (elements.serialManager) {
      serialValidation = elements.serialManager.validate();
      if (serialValidation.fields instanceof Set) {
        serialValidation.fields.forEach((field) => errorFields.add(field));
      }
    }

    if (errorFields.size > 0 || !serialValidation.ok) {
      let summary = '';
      if (rowValidation.fields.size) {
        summary = buildErrorSummary(rowValidation.fields);
      }
      if (!summary && !serialValidation.ok) {
        const serialFields = serialValidation.fields instanceof Set ? serialValidation.fields : new Set();
        summary = serialValidation.message || buildErrorSummary(serialFields);
      }
      if (!summary && errorFields.size) {
        summary = buildErrorSummary(errorFields);
      }
      if (summary) {
  showMessage(elements.error, summary);
  showToast(summary, 'error');
      }
      if (rowValidation.fields.size) {
        rowsManager.focusFirstInvalid();
      } else if (!serialValidation.ok) {
        elements.serialManager?.focusFirstInvalid();
      }
      return;
    }

    hideMessage(elements.error);

    toggleLoading(elements, true);
    try {
      if (!hasRows) {
        if (!base.ready) {
          const msg = 'Complete property number details before saving.';
          throw new Error(msg);
        }
        const serialResult = await ensureSerialAvailability(elements, base);
        if (serialResult && serialResult.available === false) {
          const msg = formatSerialConflictMessage(serialResult.conflicts || serialResult.conflict_serials || []);
          applySerialState(elements, 'error', msg);
          throw new Error(msg);
        }
      }

      const result = await submitForm(form);
      handleSuccess(form, elements, result);
    } catch (error) {
      console.error(error);
      const msg = error?.message || 'Unable to validate serial numbers right now.';
  applySerialState(elements, 'error', msg);
  showToast(msg, 'error');
      handleError(elements, error);
    } finally {
      toggleLoading(elements, false);
    }
  });

  updateState();
}

document.addEventListener('DOMContentLoaded', () => {
  initCategoryOfficeManagement();
  document.querySelectorAll('input[name="office_code"], input[data-add-field="office"], input[data-edit-field="office"], input.instance-part-office').forEach(inp => {
    inp.addEventListener('input', () => {
      inp.value = String(inp.value || '').replace(/\D/g, '').slice(0, 4);
    });
  });

  document.querySelectorAll('input[name="start_serial"], input[data-add-field="serial"], input[data-edit-field="serial"], input.instance-part-serial').forEach(inp => {
    inp.addEventListener('input', () => {
      const sanitized = sanitizeSerialInput(inp.value || '');
      if (sanitized !== inp.value) {
        inp.value = sanitized;
      }
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
          showToast('Year must be 2020 or later.', 'error');
        }
      }
    });
  });
});


document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll(SELECTOR).forEach(initAddItemsForm);
  
  // Highlight newly added items
  const newItemIds = sessionStorage.getItem('newItemIds');
  if (newItemIds) {
    try {
      const ids = JSON.parse(newItemIds);
      if (Array.isArray(ids) && ids.length > 0) {
        ids.forEach(itemId => {
          const row = document.querySelector(`tr[data-item-row="${itemId}"]`);
          if (row) {
            row.classList.add('new-item-highlight');
            // Remove the class after animation completes (60 seconds)
            setTimeout(() => {
              row.classList.remove('new-item-highlight');
            }, 60000);
          }
        });
      }
      // Clear the stored IDs
      sessionStorage.removeItem('newItemIds');
    } catch (e) {
      console.error('Failed to parse newItemIds:', e);
      sessionStorage.removeItem('newItemIds');
    }
  }
});
