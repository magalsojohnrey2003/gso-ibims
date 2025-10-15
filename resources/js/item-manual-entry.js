// resources/js/item-manual-entry.js
// Manual Property Number Entry — adjusted to keep inputs inline and small
(function () {
  'use strict';

  const FORM_SELECTOR = '[data-manual-form]';
  const ROWS_CONTAINER_ID = 'manual_rows_container';
  const MAX_ROWS = 500;
  const DEFAULT_ROWS = 1;
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function padSerial(n, width) {
    const w = Math.max(1, parseInt(width || 1, 10));
    const val = Number.isFinite(Number(n)) ? Math.max(0, Number(n)) : 0;
    return String(val).padStart(w, '0');
  }
  function norm(v) { return String(v ?? '').trim(); }

  function buildPropertyNumber({ year, ppe, serial, office }, serialWidthHint = null) {
    const y = norm(year) || '----';
    const p = norm(ppe) || '----';
    let s = norm(serial) || '----';
    const o = norm(office) || '----';
    if (serialWidthHint && /^[0-9]+$/.test(s)) {
      s = padSerial(parseInt(s, 10), serialWidthHint);
    }
    return `${y}-${p}-${s}-${o}`;
  }

  function createRowElement(idx, initial = {}, serialWidthHint = null) {
    const root = document.createElement('div');
    root.className = 'flex items-start gap-4';

    // left index circle
    const left = document.createElement('div');
    left.className = 'flex-none w-10 text-center';
    const circle = document.createElement('div');
    circle.className = 'inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-medium';
    circle.textContent = String(idx + 1);
    left.appendChild(circle);

    // right panel (rounded card) — prevent wrapping so inputs stay on one row
    const panel = document.createElement('div');
    panel.className = 'flex-1 bg-indigo-50 rounded-lg px-4 py-3 flex items-center gap-3 flex-nowrap';

    // Year input (small pill)
    const yInput = document.createElement('input');
    yInput.type = 'text';
    yInput.className = 'w-20 text-center text-sm rounded-md border px-2 py-1 bg-white';
    yInput.value = initial.year ?? '';
    yInput.setAttribute('aria-label', `Year row ${idx+1}`);
    yInput.dataset.part = 'year';

    // separator dash
    const sep1 = document.createElement('div');
    sep1.className = 'text-gray-500 select-none';
    sep1.textContent = '-';

    // PPE (read-only)
    const pInput = document.createElement('input');
    pInput.type = 'text';
    pInput.className = 'w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100';
    pInput.value = initial.ppe ?? '';
    pInput.readOnly = true;
    pInput.dataset.part = 'ppe';
    pInput.setAttribute('aria-label', `PPE row ${idx+1}`);

    // separator dash
    const sep2 = document.createElement('div');
    sep2.className = 'text-gray-500 select-none';
    sep2.textContent = '-';

    // Serial (small pill) — kept compact
    const sInput = document.createElement('input');
    sInput.type = 'text';
    sInput.className = 'w-20 text-center text-sm rounded-md border px-2 py-1 bg-white';
    sInput.value = initial.serial ?? '';
    sInput.dataset.part = 'serial';
    sInput.setAttribute('aria-label', `Serial row ${idx+1}`);

    // separator dash
    const sep3 = document.createElement('div');
    sep3.className = 'text-gray-500 select-none';
    sep3.textContent = '-';

    // Office (small pill)
    const oInput = document.createElement('input');
    oInput.type = 'text';
    oInput.className = 'w-20 text-center text-sm rounded-md border px-2 py-1 bg-white';
    oInput.value = initial.office ?? '';
    oInput.dataset.part = 'office';
    oInput.setAttribute('aria-label', `Office row ${idx+1}`);

    // actions (remove)
    const actions = document.createElement('div');
    actions.className = 'flex-none ml-2';
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'text-red-600 text-sm px-2 py-1 rounded-md hover:bg-red-50';
    removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', () => {
      root.remove();
      renumberRows(document.getElementById(ROWS_CONTAINER_ID));
    });
    actions.appendChild(removeBtn);

    // small status to the right (no full-width so no wrapping)
    const status = document.createElement('div');
    status.className = 'flex-none ml-4 text-xs text-yellow-700';
    status.dataset.part = 'status';

    // append inputs in order
    panel.appendChild(yInput);
    panel.appendChild(sep1);
    panel.appendChild(pInput);
    panel.appendChild(sep2);
    panel.appendChild(sInput);
    panel.appendChild(sep3);
    panel.appendChild(oInput);
    panel.appendChild(actions);

    root.appendChild(left);
    root.appendChild(panel);
    root.appendChild(status);

    // initialize dataset propertyNumber and status
    const initialPN = buildPropertyNumber({
      year: yInput.value,
      ppe: pInput.value,
      serial: sInput.value,
      office: oInput.value
    }, serialWidthHint);
    root.dataset.propertyNumber = initialPN;
    status.textContent = initialPN.includes('----') ? 'Incomplete' : '';

    // input events update pn and status
    const updatePN = () => {
      const pn = buildPropertyNumber({
        year: yInput.value,
        ppe: pInput.value,
        serial: sInput.value,
        office: oInput.value
      }, serialWidthHint);
      root.dataset.propertyNumber = pn;
      status.textContent = pn.includes('----') ? 'Incomplete' : '';
    };
    [yInput, sInput, oInput].forEach(el => el.addEventListener('input', updatePN));

    return root;
  }

  function renumberRows(container) {
    if (!container) return;
    const rows = Array.from(container.children).filter(n => n.querySelector('.inline-flex'));
    rows.forEach((r, idx) => {
      const circle = r.querySelector('.inline-flex');
      if (circle) circle.textContent = String(idx + 1);
    });
  }

  function buildRows(containerEl, count, baseConfig = {}) {
    if (!containerEl) return;
    const existing = Array.from(containerEl.children).filter(n => n.dataset);
    const current = existing.length;

    if (current === count) {
      existing.forEach((el, i) => {
        const y = el.querySelector('input[data-part="year"]');
        const p = el.querySelector('input[data-part="ppe"]');
        const s = el.querySelector('input[data-part="serial"]');
        const o = el.querySelector('input[data-part="office"]');
        if (y && !y.value) y.value = baseConfig.year || '';
        if (p && !p.value) p.value = baseConfig.ppe || '';
        if (o && !o.value) o.value = baseConfig.office || '';
        if (s && !s.value) {
          const serialHint = baseConfig.serial || '';
          if (serialHint && /^[0-9]+$/.test(serialHint)) {
            const width = Math.max(4, serialHint.length || 4);
            s.value = padSerial((parseInt(serialHint, 10) || 0) + i, width);
          } else {
            s.value = baseConfig.serial || '';
          }
        }
        el.querySelectorAll('input').forEach(inp => inp.dispatchEvent(new Event('input', { bubbles: true })));
      });
      return;
    }

    if (current > count) {
      for (let i = current - 1; i >= count; i--) {
        const el = existing[i];
        if (el) el.remove();
      }
      renumberRows(containerEl);
      return;
    }

    const serialHint = baseConfig.serial || '';
    for (let i = 0; i < (count - current); i++) {
      const index = current + i;
      let initSerial = '';
      if (serialHint && /^[0-9]+$/.test(serialHint)) {
        const baseNum = parseInt(serialHint, 10) || 0;
        const width = Math.max(4, serialHint.length || 4);
        initSerial = padSerial(baseNum + index, width);
      } else if (serialHint) {
        initSerial = serialHint;
      }
      const initial = {
        year: baseConfig.year || '',
        ppe: baseConfig.ppe || '',
        serial: initSerial,
        office: baseConfig.office || ''
      };
      const row = createRowElement(index, initial, Math.max(4, (serialHint && /^[0-9]+$/.test(serialHint) ? serialHint.length : 4)));
      containerEl.appendChild(row);
    }
    renumberRows(containerEl);
  }

  function getConfigFromForm(form) {
    const year = form.querySelector('[data-manual-config="year"]')?.value ?? '';
    const ppe = form.querySelector('[data-ppe-display]')?.value ?? '';
    const serial = form.querySelector('[data-manual-config="serial"]')?.value ?? '';
    const office = form.querySelector('[data-manual-config="office"]')?.value ?? '';
    return { year: norm(year), ppe: norm(ppe), serial: norm(serial), office: norm(office) };
  }

  function attachForm(form) {
    if (!form) return;
    const container = form.querySelector(`#${ROWS_CONTAINER_ID}`);
    if (!container) return;

    const quantityInput = form.querySelector('[data-manual-quantity]');
    const submitBtn = form.querySelector('[data-manual-submit]') || form.querySelector('[type="submit"]');
    const feedbackEl = form.querySelector('[data-manual-feedback]');
    const errorEl = form.querySelector('[data-manual-error]');

    form.querySelectorAll('select[data-category-select]').forEach(sel => {
      sel.addEventListener('change', () => {
        setTimeout(() => {
          const ppeDisplay = form.querySelector('[data-ppe-display]');
          const ppeHidden = form.querySelector('input[data-property-segment="ppe"], input[name="ppe_code"]');
          const ppeVal = ppeDisplay?.value ?? '';
          if (ppeHidden) ppeHidden.value = ppeVal;
          Array.from(container.children).forEach(row => {
            const pin = row.querySelector('input[data-part="ppe"]');
            if (pin) pin.value = ppeVal;
            pin && pin.dispatchEvent(new Event('input', { bubbles: true }));
          });
        }, 0);
      });
    });

    const renderFromQuantity = () => {
      let qty = parseInt(quantityInput?.value ?? String(DEFAULT_ROWS), 10);
      if (!Number.isFinite(qty) || qty < 1) qty = DEFAULT_ROWS;
      if (qty > MAX_ROWS) qty = MAX_ROWS;
      const cfg = getConfigFromForm(form);
      buildRows(container, qty, cfg);
    };

    const configFields = Array.from(form.querySelectorAll('[data-manual-config], [data-ppe-display]'));
    configFields.forEach(f => f.addEventListener('input', renderFromQuantity));
    quantityInput && quantityInput.addEventListener('input', renderFromQuantity);

    renderFromQuantity();

    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      if (feedbackEl) { feedbackEl.classList.add('hidden'); feedbackEl.textContent = ''; }
      if (errorEl) { errorEl.classList.add('hidden'); errorEl.textContent = ''; }

      const rows = Array.from(container.children).filter(n => n.dataset && n.dataset.propertyNumber);
      const pns = [];
      rows.forEach(r => {
        const pn = r.dataset.propertyNumber ?? '';
        if (!pn || pn.includes('----')) return;
        pns.push(pn);
      });

      if (pns.length === 0) {
        if (errorEl) { errorEl.textContent = 'No complete property numbers to submit. Fill all fields for each row.'; errorEl.classList.remove('hidden'); }
        return;
      }

      const fd = new FormData(form);
      fd.delete('property_numbers[]');
      pns.forEach(pn => fd.append('property_numbers[]', pn));

      const headers = { 'X-Requested-With': 'XMLHttpRequest' };
      if (CSRF_TOKEN) headers['X-CSRF-TOKEN'] = CSRF_TOKEN;

      try {
        submitBtn && (submitBtn.disabled = true);
        const route = window.__manualStoreRoute || form.getAttribute('action') || '/admin/items/manual-store';
        const res = await fetch(route, {
          method: 'POST',
          headers,
          body: fd,
          credentials: 'same-origin'
        });
        const payload = await res.json().catch(() => null);
        if (!res.ok) {
          const msg = payload?.message || `Save failed (${res.status})`;
          if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('hidden'); }
          return;
        }

        const created = Array.isArray(payload?.created) ? payload.created.length : (payload?.created_count ?? 0);
        const skipped = Array.isArray(payload?.skipped) ? payload.skipped.length : (payload?.skipped_count ?? 0);
        const msg = created > 0 ? `Created ${created} items${skipped ? `, ${skipped} skipped` : ''}.` : 'No items created.';
        if (feedbackEl) { feedbackEl.textContent = msg; feedbackEl.classList.remove('hidden'); }

        setTimeout(() => {
          try { window.dispatchEvent(new CustomEvent('close-modal', { detail: form.getAttribute('data-modal-name') || 'manual-property-entry' })); } catch (_) {}
          setTimeout(() => location.reload(), 350);
        }, 300);
      } catch (err) {
        console.error('Manual store failed', err);
        if (errorEl) { errorEl.textContent = 'Failed to save items. Please try again.'; errorEl.classList.remove('hidden'); }
      } finally {
        submitBtn && (submitBtn.disabled = false);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(FORM_SELECTOR).forEach(form => {
      try { attachForm(form); } catch (err) { console.error('init manual form failed', err); }
    });
  });
})();
