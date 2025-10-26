const PROPERTY_FORM_SELECTOR = '[data-property-form]';
const RAW_CATEGORY_CODE_MAP = typeof window !== 'undefined' && window.CATEGORY_CODE_MAP ? window.CATEGORY_CODE_MAP : {};
const CASELESS_CATEGORY_MAP = Object.keys(RAW_CATEGORY_CODE_MAP).reduce((acc, key) => {
    const value = RAW_CATEGORY_CODE_MAP[key];
    acc[key] = value;
    if (typeof key === 'string') {
        acc[key.toLowerCase()] = value;
    }
    return acc;
}, {});

// Allow any year; do not clamp to a min/max.

function normalizeSegment(value, minLength = 0, allowAlpha = false) {
     if (!value) return '';
     const trimmed = String(value).trim();
 
     const cleaned = allowAlpha || minLength > 0 && /[A-Za-z]/.test(trimmed)
         ? trimmed.replace(/[^A-Za-z0-9]/g, '')
         : trimmed.replace(/[^0-9]/g, '');
 
     if (minLength > 0 && /[A-Za-z]/.test(cleaned)) {
         const up = cleaned.toUpperCase();
         return up.slice(0, 4);
     }
 
     if (!minLength) return cleaned;
 
     const shouldPad = !allowAlpha && /^[0-9]+$/.test(cleaned);
     return shouldPad ? cleaned.padStart(Math.max(minLength, cleaned.length), '0') : cleaned;
 }

function buildPreview(form) {
    const year = normalizeSegment(form.querySelector('[data-property-segment="year"]')?.value || '', 4);
    const category = normalizeSegment(form.querySelector('[data-property-segment="category"]')?.value || '', 1);
    const gla = (form.querySelector('[data-property-segment="gla"]')?.value || '').replace(/\D/g, '');
    const rawSerial = form.querySelector('[data-property-segment="serial"]')?.value ?? '';
    const serialWidth = Math.max(4, rawSerial.trim().length || 0);
    const serial = normalizeSegment(rawSerial, serialWidth);
    const office = normalizeSegment(form.querySelector('[data-property-segment="office"]')?.value || '', 4, true);

    if (year || category || gla || serial || office) {
        return [year || '----', category ||  '----', gla || '----', serial || '----', office || '----'].join('-');
    }

    return '----';
}

function updatePreview(form) {
    const previewEl = form.querySelector('[data-property-preview]');
    if (previewEl) {
        previewEl.value = buildPreview(form);
    }
}

function resolveCategoryCode(selectEl) {
     if (!selectEl) return '';
     const rawValue = (typeof selectEl === 'string') ? selectEl : (selectEl.value ?? '');
     if (!rawValue) return '';
 
     const key = String(rawValue);
     const direct = CASELESS_CATEGORY_MAP[key] ?? CASELESS_CATEGORY_MAP[key.toLowerCase()];
     if (direct) {
         const cleaned = String(direct).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0,4);
         return cleaned;
     }
 
     if (typeof selectEl === 'object' && selectEl.options) {
         const option = selectEl.options?.[selectEl.selectedIndex];
         if (option) {
             const attr = option.getAttribute('data-category-code') || option.getAttribute('data-category-id') || option.getAttribute('data-category-name');
             if (attr) {
                 const cleaned = String(attr).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0,4);
                 return cleaned;
             }
         }
     }
 
     const only = String(rawValue).replace(/[^A-Za-z0-9]/g, '').toUpperCase();
     if (!only) return '';
     return only.slice(0,4);
 }

function applyCategoryCode(form) {
    const categorySelect = form.querySelector('[data-category-select]');
    if (!categorySelect) return;

    const codeHidden = form.querySelector('[data-property-segment="category"]');
    const codeDisplay = form.querySelector('[data-category-display]');

    const sync = () => {
        const code = resolveCategoryCode(categorySelect) || (codeHidden?.value ?? '');
        if (codeHidden) codeHidden.value = code;
        if (codeDisplay) codeDisplay.value = code;
        updatePreview(form);
    };

    categorySelect.addEventListener('change', sync);
    sync();
}

function attachPreview(form) {
    const update = () => updatePreview(form);
    form.querySelectorAll('[data-property-segment]').forEach((input) => {
        input.addEventListener('input', update);
    });
    update();
}

function attachYearClamp(form) {
    const input = form.querySelector('[data-property-segment="year"]');
    if (!input) return;

    const clamp = () => {
        const digits = input.value.replace(/[^0-9]/g, '').slice(0, 4);
        input.value = digits;
    };

    input.addEventListener('input', clamp);
    input.addEventListener('blur', clamp);
    form.addEventListener('reset', () => {
        input.value = '';
    });

    clamp();
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll(PROPERTY_FORM_SELECTOR).forEach((form) => {
            applyCategoryCode(form);
            attachPreview(form);
            attachYearClamp(form);
        });
    });
}

export { buildPreview, normalizeSegment, resolveCategoryCode };