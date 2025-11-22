// resources/js/qty-controls.js
// Delegated handlers for + / - quantity buttons (modern, Vite-ready)
// Allows multi-digit typing by sanitizing digits-only on input and clamping to min..max
// after a short debounce or on blur. Keeps step buttons behavior and HMR guard.

if (window.__qtyControlsInit) {
  // already initialized (prevents double-binding during HMR)
  // eslint-disable-next-line no-new
  new Event('qty-controls-already-initialized');
} else {
  window.__qtyControlsInit = true;

  const DEBOUNCE_MS = 5000;

  function notifyClamp(input, detail = {}) {
    if (!input) return;
    try {
      input.dispatchEvent(new CustomEvent('qty:clamped', { bubbles: true, detail }));
    } catch (err) {
      console.warn('Failed to dispatch qty:clamped event', err);
    }
  }

  function findQtyInput(btn) {
    if (!btn) return null;
    const container = btn.closest('.qty-control');
    if (container) {
      const explicit = container.querySelector('input.qty-input[type="number"]')
                    || container.querySelector('input[type="number"]');
      if (explicit) return explicit;
    }
    if (btn.previousElementSibling && btn.previousElementSibling.tagName === 'INPUT') return btn.previousElementSibling;
    if (btn.nextElementSibling && btn.nextElementSibling.tagName === 'INPUT') return btn.nextElementSibling;
    return null;
  }

  function parseIntOr(v, fallback) {
    const n = parseInt(v, 10);
    return Number.isNaN(n) ? fallback : n;
  }

  function updateButtonsState(container) {
    if (!container) return;
    const input = container.querySelector('input.qty-input[type="number"]') || container.querySelector('input[type="number"]');
    if (!input) return;
    const up = container.querySelector('.btn-step-up');
    const down = container.querySelector('.btn-step-down');

    const min = parseIntOr(input.min ?? input.getAttribute('min'), 1);
    const maxAttr = (up?.dataset?.max) ?? container.dataset?.itemMax ?? input.max ?? null;
    const max = maxAttr !== null && maxAttr !== undefined && String(maxAttr).length ? parseInt(maxAttr, 10) : Infinity;

    const val = parseIntOr(input.value ?? input.defaultValue, min);

    if (up) up.disabled = !(val < max);
    if (down) down.disabled = !(val > min);
  }

  /**
   * Clamp to min..max and update button states.
   * Runs on blur, on step button usage, and after debounce.
   */
  function clampValue(input) {
    if (!input) return;
    const container = input.closest('.qty-control');
    const up = container?.querySelector('.btn-step-up');

    const min = parseIntOr(input.min ?? input.getAttribute('min'), 1);
    const maxAttr = (up?.dataset?.max) ?? container?.dataset?.itemMax ?? input.max ?? null;
    const max = maxAttr !== null && maxAttr !== undefined && String(maxAttr).length ? parseInt(maxAttr, 10) : Infinity;

    let val = parseInt(input.value, 10);
    let clampedToMax = false;
    if (Number.isNaN(val)) {
      // empty or invalid -> fallback to min
      val = min;
    }
    if (val < min) val = min;
    if (Number.isFinite(max) && val > max) {
      val = max;
      clampedToMax = true;
    }

    // Only set if different to reduce selection disruptions
    if (String(input.value) !== String(val)) {
      input.value = val;
      if (clampedToMax) {
        notifyClamp(input, { type: 'max', max });
      }
    }

    if (container) updateButtonsState(container);
  }

  /**
   * Light sanitization while typing: remove any non-digit characters and leading zeros.
   * Do NOT clamp to min/max here (so users can type multi-digit numbers).
   */
  function sanitizeDigitsOnly(input) {
    if (!input) return;
    const raw = String(input.value ?? '');
    // keep digits only
    let cleaned = raw.replace(/[^\d]/g, '');
    // remove leading zeros to avoid "001" awkwardness (but keep single zero if that's the value)
    cleaned = cleaned.replace(/^0+(?=\d)/, '');
    if (cleaned !== raw) {
      input.value = cleaned;
      try { input.setSelectionRange(input.value.length, input.value.length); } catch (e) {}
    }
  }

  // Delegated click handler for step buttons
  document.addEventListener('click', function (e) {
    const upBtn = e.target.closest('.btn-step-up');
    if (upBtn) {
      const input = findQtyInput(upBtn);
      if (!input) return;
      const container = upBtn.closest('.qty-control');

      let max = parseIntOr(upBtn.dataset?.max ?? (container?.dataset?.itemMax) ?? input.max, Infinity);
      if (!Number.isFinite(max)) max = Infinity;

      let val = parseIntOr(input.value, parseIntOr(input.defaultValue, parseIntOr(input.min, 1)));
      if (val < max) {
        if (typeof input.stepUp === 'function') input.stepUp();
        else input.value = Math.min(max, val + 1);
      } else {
        input.value = max;
      }

      // Immediately clamp & update state after stepping
      clampValue(input);

      return;
    }

    const downBtn = e.target.closest('.btn-step-down');
    if (downBtn) {
      const input = findQtyInput(downBtn);
      if (!input) return;
      const container = downBtn.closest('.qty-control');

      const min = parseIntOr(downBtn.dataset?.min ?? input.min ?? '1', 1);

      let val = parseIntOr(input.value, parseIntOr(input.defaultValue, parseIntOr(input.min, 1)));
      if (val > min) {
        if (typeof input.stepDown === 'function') input.stepDown();
        else input.value = Math.max(min, val - 1);
      } else {
        input.value = min;
      }

      // Immediately clamp & update state after stepping
      clampValue(input);

      return;
    }
  }, false);

  // Input & blur handlers (delegated)
  document.addEventListener('DOMContentLoaded', function () {
    // Initialize existing controls
    document.querySelectorAll('.qty-control').forEach(container => {
      const input = container.querySelector('input.qty-input[type="number"]') || container.querySelector('input[type="number"]');
      if (input) {
        // ensure starting value is valid
        clampValue(input);
        updateButtonsState(container);
      }
    });

    // Live typing: sanitize digits only and debounce clamp
    // Live typing: sanitize digits only, snap immediately if > max, debounce for min
    document.body.addEventListener('input', function (e) {
      const target = e.target;
      if (!target) return;
      const isNumberInput = target.matches && (target.matches('input.qty-input') || target.matches('input[type="number"]'));
      if (!isNumberInput) return;
      const container = target.closest('.qty-control');
      if (!container) return;

      // sanitize digits only
      sanitizeDigitsOnly(target);

      // check max immediately
      const up = container.querySelector('.btn-step-up');
      const maxAttr = (up?.dataset?.max) ?? container.dataset?.itemMax ?? target.max ?? null;
      const max = maxAttr !== null && maxAttr !== undefined && String(maxAttr).length ? parseInt(maxAttr, 10) : Infinity;
      let val = parseInt(target.value, 10);

      if (!Number.isNaN(val) && Number.isFinite(max) && val > max) {
        target.value = max; // snap right away
        notifyClamp(target, { type: 'max', max });
        updateButtonsState(container);
        return;
      }

      // debounce only for min side
      if (target._qtyDebounce) clearTimeout(target._qtyDebounce);
      target._qtyDebounce = setTimeout(() => {
        clampValue(target); // this handles min enforcement
        target._qtyDebounce = null;
      }, DEBOUNCE_MS);
    }, { passive: true });

    // On blur, clamp immediately
    document.body.addEventListener('blur', function (e) {
      const target = e.target;
      if (!target) return;
      const isNumberInput = target.matches && (target.matches('input.qty-input') || target.matches('input[type="number"]'));
      if (!isNumberInput) return;
      const container = target.closest('.qty-control');
      if (!container) return;

      if (target._qtyDebounce) { clearTimeout(target._qtyDebounce); target._qtyDebounce = null; }
      clampValue(target);
    }, true); // capture to ensure blur is caught
  });
}
