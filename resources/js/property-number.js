const PROPERTY_FORM_SELECTOR = '[data-property-form]';
const RAW_CATEGORY_PPE_MAP = typeof window !== 'undefined' && window.CATEGORY_PPE_MAP ? window.CATEGORY_PPE_MAP : {};
const CASELESS_PPE_MAP = Object.keys(RAW_CATEGORY_PPE_MAP).reduce((acc, key) => {
    const value = RAW_CATEGORY_PPE_MAP[key];
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

    // Allow alphanumeric if requested (e.g., for Office Code)
    const cleaned = allowAlpha
        ? trimmed.replace(/[^A-Za-z0-9]/g, '') // keep letters + numbers
        : trimmed.replace(/[^0-9]/g, '');

    if (!minLength) return cleaned;

    // Only pad purely numeric segments; don't pad alphanumerics like 4d34
    const shouldPad = !allowAlpha && /^[0-9]+$/.test(cleaned);
    return shouldPad
        ? cleaned.padStart(Math.max(minLength, cleaned.length), '0')
        : cleaned;
}


function buildPreview(form) {
    const year = normalizeSegment(form.querySelector('[data-property-segment="year"]')?.value || '', 4);
    const ppe = normalizeSegment(form.querySelector('[data-property-segment="ppe"]')?.value || '', 2);
    const rawSerial = form.querySelector('[data-property-segment="serial"]')?.value ?? '';
    const serialWidth = Math.max(4, rawSerial.trim().length || 0);
    const serial = normalizeSegment(rawSerial, serialWidth);
    const office = normalizeSegment(form.querySelector('[data-property-segment="office"]')?.value || '', 4, true);

    if (year || ppe || serial || office) {
        return [year || '----', ppe || '----', serial || '----', office || '----'].join('-');
    }

    return '----';
}

function updatePreview(form) {
    const previewEl = form.querySelector('[data-property-preview]');
    if (previewEl) {
        previewEl.value = buildPreview(form);
    }
}

function resolvePpeCode(selectEl) {
    if (!selectEl) return '';
    const rawValue = selectEl.value ?? '';
    if (!rawValue) return '';

    const key = typeof rawValue === 'string' ? rawValue : String(rawValue);
    const direct = CASELESS_PPE_MAP[key] ?? CASELESS_PPE_MAP[key.toLowerCase()];
    if (direct) return direct;

    const option = selectEl.options?.[selectEl.selectedIndex];
    if (option) {
        const attr = option.getAttribute('data-ppe-code');
        if (attr) return attr;
    }

    return '';
}

function applyCategoryPpe(form) {
    const categorySelect = form.querySelector('[data-category-select]');
    if (!categorySelect) return;

    const ppeHidden = form.querySelector('[data-property-segment="ppe"]');
    const ppeDisplay = form.querySelector('[data-ppe-display]');

    const sync = () => {
        const code = resolvePpeCode(categorySelect) || (ppeHidden?.value ?? '');
        if (ppeHidden) ppeHidden.value = code;
        if (ppeDisplay) ppeDisplay.value = code;
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
        // Keep at most 4 digits, do not enforce a range.
        const digits = input.value.replace(/[^0-9]/g, '').slice(0, 4);
        input.value = digits;
    };

    input.addEventListener('input', clamp);
    input.addEventListener('blur', clamp);
    form.addEventListener('reset', () => {
        input.value = '';
    });

    // Initialize without forcing any default like 2020
    clamp();
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll(PROPERTY_FORM_SELECTOR).forEach((form) => {
            applyCategoryPpe(form);
            attachPreview(form);
            attachYearClamp(form);
        });
    });
}

export { buildPreview, normalizeSegment, resolvePpeCode };
