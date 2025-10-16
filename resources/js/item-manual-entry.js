// resources/js/item-manual-entry.js
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
  root.className = 'flex items-start gap-4 edit-instance-row';
  root.setAttribute('data-instance-temp-index', String(idx));

  const left = document.createElement('div');
  left.className = 'flex-none w-10 text-center';
  const circle = document.createElement('div');
  circle.className = 'inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-medium';
  circle.textContent = String(idx + 1);
  left.appendChild(circle);

  const panel = document.createElement('div');
  panel.className = 'flex-1 bg-indigo-50 rounded-lg px-4 py-3 flex items-center gap-3 flex-nowrap';

  // YEAR input (digits only, 4 chars, enforce >= 2020)
  const yInput = document.createElement('input');
  yInput.type = 'text';
  yInput.inputMode = 'numeric';
  yInput.maxLength = 4;
  yInput.placeholder = 'Year Procured';
  yInput.className = 'w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-year';
  yInput.value = initial.year ?? '';
  yInput.dataset.part = 'year';

  // PPE
  const pInput = document.createElement('input');
  pInput.type = 'text';
  pInput.placeholder = 'PPE';
  pInput.className = 'w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-ppe';
  pInput.value = initial.ppe ?? '';
  pInput.dataset.part = 'ppe';

  // SERIAL (uppercase, limit 5)
  const sInput = document.createElement('input');
  sInput.type = 'text';
  sInput.maxLength = 5;
  sInput.placeholder = 'Serial';
  sInput.className = 'w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-serial';
  sInput.value = initial.serial ?? '';
  sInput.dataset.part = 'serial';

  // OFFICE (uppercase, max 4)
  const oInput = document.createElement('input');
  oInput.type = 'text';
  oInput.maxLength = 4;
  oInput.placeholder = 'Office';
  oInput.className = 'w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-office';
  oInput.value = initial.office ?? '';
  oInput.dataset.part = 'office';

  const actions = document.createElement('div');
  actions.className = 'flex-none ml-2';
  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.className = 'text-red-600 text-sm px-2 py-1 rounded-md hover:bg-red-50 instance-remove-btn';
  removeBtn.textContent = 'Remove';
  actions.appendChild(removeBtn);

  const status = document.createElement('div');
  status.className = 'flex-none ml-4 text-xs text-yellow-700 instance-status';
  status.dataset.part = 'status';

  const sep = () => { const d = document.createElement('div'); d.className = 'text-gray-500 select-none'; d.textContent = '-'; return d; };

  panel.appendChild(yInput);
  panel.appendChild(sep());
  panel.appendChild(pInput);
  panel.appendChild(sep());
  panel.appendChild(sInput);
  panel.appendChild(sep());
  panel.appendChild(oInput);
  panel.appendChild(actions);

  root.appendChild(left);
  root.appendChild(panel);
  root.appendChild(status);

  // initial property number display & status
  const initialPN = buildPropertyNumber({
    year: yInput.value,
    ppe: pInput.value,
    serial: sInput.value,
    office: oInput.value
  }, serialWidthHint);
  root.dataset.propertyNumber = initialPN;
  status.textContent = initialPN.includes('----') ? 'Incomplete' : '';


function enforceYearInput() {
  // digits only, max 4 chars
  yInput.value = (yInput.value || '').replace(/\D/g, '').slice(0, 4);
  const val = parseInt(yInput.value, 10);
  const currentYear = new Date().getFullYear();

  if (!isNaN(val)) {
    if (val < 2020 || val > currentYear) {
      yInput.value = '';
      showToast('error', `Please enter a valid year between 2020 and ${currentYear}.`);
    }
  }
}


function enforceAlnumUpper(el, maxLen) {
  // allow letters+digits only and convert to uppercase
  el.value = String(el.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  if (typeof maxLen === 'number') el.value = el.value.slice(0, maxLen);
}

function updatePN() {
  const pn = buildPropertyNumber({
    year: yInput.value,
    ppe: pInput.value,
    serial: sInput.value,
    office: oInput.value
  }, serialWidthHint);
  root.dataset.propertyNumber = pn;
  status.textContent = pn.includes('----') ? 'Incomplete' : '';
}

// listeners
yInput.addEventListener('input', () => { enforceYearInput(); updatePN(); });
pInput.addEventListener('input', () => { enforceAlnumUpper(pInput, 16); updatePN(); });
sInput.addEventListener('input', () => { enforceAlnumUpper(sInput, 5); updatePN(); });
oInput.addEventListener('input', () => { enforceAlnumUpper(oInput, 4); updatePN(); });

  // remove button
  removeBtn.addEventListener('click', () => { root.remove(); renumberRows(document.getElementById(ROWS_CONTAINER_ID)); });

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
        const parts = {
          year: baseConfig.year || '',
          ppe: baseConfig.ppe || '',
          office: baseConfig.office || '',
          serial: baseConfig.serial || ''
        };
        ['year','ppe','office','serial'].forEach(k => {
          const inp = el.querySelector(`input[data-part="${k}"]`);
          if (inp && !inp.value) inp.value = parts[k];
        });
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
      let initSerial = baseConfig.serial || '';
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
    const serial = form.querySelector('[data-manual-config="serial"]')?.value ?? '';
    const office = form.querySelector('[data-manual-config="office"]')?.value ?? '';
    const ppe = ''; // no PPE field in modal anymore
    return { year: norm(year), ppe, serial: norm(serial), office: norm(office) };
  }

  function attachForm(form) {
    if (!form) return;
    const container = form.querySelector(`#${ROWS_CONTAINER_ID}`);
    if (!container) return;

    const quantityInput = form.querySelector('[data-manual-quantity]');
    if (quantityInput) {
      quantityInput.setAttribute('inputmode', 'numeric');
      quantityInput.setAttribute('maxlength', '3');
      quantityInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 3);
      });
    }

    const submitBtn = form.querySelector('[data-manual-submit]') || form.querySelector('[type="submit"]');
    const feedbackEl = form.querySelector('[data-manual-feedback]');
    const errorEl = form.querySelector('[data-manual-error]');

    // categories populating (if window.__serverCategories available)
    form.querySelectorAll('select[data-category-select]').forEach(sel => {
      const cats = window.__serverCategories || [];
      sel.innerHTML = '';
      cats.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        sel.appendChild(opt);
      });
    });

    const renderFromQuantity = () => {
      let qty = parseInt(quantityInput?.value ?? String(DEFAULT_ROWS), 10);
      if (!Number.isFinite(qty) || qty < 1) qty = DEFAULT_ROWS;
      if (qty > MAX_ROWS) qty = MAX_ROWS;
      const cfg = getConfigFromForm(form);
      buildRows(container, qty, cfg);
    };

    const configFields = Array.from(form.querySelectorAll('[data-manual-config]'));
configFields.forEach(f => {
  f.addEventListener('input', (e) => {
    const field = e.target.getAttribute('data-manual-config');
    const container = form.querySelector(`#${ROWS_CONTAINER_ID}`);
    if (!container) return;

    // Enforce uppercase for OFFICE and SERIAL
    if (field === 'office' || field === 'serial') {
      e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, field === 'serial' ? 5 : 4);
    }

    // Update only matching field in every row dynamically
    container.querySelectorAll(`input[data-part="${field}"]`).forEach(inp => {
      inp.value = e.target.value;
      inp.dispatchEvent(new Event('input', { bubbles: true }));
    });

    // Re-render only if quantity or year changed
    if (field === 'quantity' || field === 'year') {
      renderFromQuantity();
    }
  });
});

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
