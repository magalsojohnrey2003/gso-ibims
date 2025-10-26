// resources/js/item-instances-manager.js
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('edit_instances_container');
  if (!container) return;

  const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';

  const debounce = (fn, delay = 700) => {
    let t = null;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  };

  async function apiPatchInstance(instanceId, payload) {
    try {
      const res = await fetch(`/admin/item-instances/${instanceId}`, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      const json = await res.json().catch(() => null);
      return { ok: res.ok, status: res.status, json };
    } catch (err) {
      return { ok: false, status: 0, json: null, error: err };
    }
  }

  async function apiDeleteInstance(instanceId) {
    try {
      const res = await fetch(`/admin/item-instances/${instanceId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });
      const json = await res.json().catch(() => null);
      return { ok: res.ok, status: res.status, json };
    } catch (err) {
      return { ok: false, status: 0, json: null, error: err };
    }
  }

  function inputsForRow(row) {
    return {
      yearEl: row.querySelector('.instance-part-year'),
      categoryEl: row.querySelector('.instance-part-category, .instance-part-category'),
      glaEl: row.querySelector('.instance-part-gla'),
      serialEl: row.querySelector('.instance-part-serial'),
      officeEl: row.querySelector('.instance-part-office'),
      statusEl: row.querySelector('.instance-status'),
    };
  }

  function snapshotRow(row) {
    const { yearEl, categoryEl, serialEl, officeEl } = inputsForRow(row);
    if (yearEl) yearEl.dataset.orig = yearEl.value ?? '';
    if (categoryEl) categoryEl.dataset.orig = categoryEl.value ?? '';
    if (serialEl) serialEl.dataset.orig = serialEl.value ?? '';
    if (officeEl) officeEl.dataset.orig = officeEl.value ?? '';
  }

  function assemblePN({ year_procured, category_code, serial, office_code }) {
    const y = String(year_procured || '').trim();
    const p = String(category_code || '').trim().toUpperCase();
    const s = String(serial || '').trim();
    const o = String(office_code || '').trim();
    return (y && p && s && o) ? `${y}-${p}-${s}-${o}` : '';
  }

  const scheduleSaveForRow = debounce(async (row) => {
    const instanceId = row.getAttribute('data-instance-id');
    if (!instanceId) return;

    const { yearEl, categoryEl, serialEl, officeEl, statusEl } = inputsForRow(row);

    const payload = {
      year: yearEl?.value?.trim() || undefined,
      category_code: categoryEl?.value?.trim()?.toUpperCase() || undefined,
      gla: glaEl?.value?.trim() || undefined,
      serial: serialEl?.value?.trim() || undefined,
      office: officeEl?.value?.trim() || undefined,
    };

    const prevStatus = statusEl?.textContent || '';
    if (statusEl) statusEl.textContent = 'Saving...';

    const result = await apiPatchInstance(instanceId, payload);

    if (result.ok) {
      const pn = result.json?.instance?.property_number ?? result.json?.property_number ?? assemblePN({
        year_procured: payload.year,
        category_code: payload.category_code,
        serial: payload.serial,
        office_code: payload.office,
      });
      if (statusEl) statusEl.textContent = pn || prevStatus;
      snapshotRow(row);
      if (result.json?.item) {
        window.dispatchEvent(new CustomEvent('items:edit:success', { detail: result.json.item }));
      }
      try { if (typeof window.showToast === 'function') window.showToast('success', 'Instance updated'); } catch (_) {}
      return;
    }

    let serialError = false;
    const json = result.json;

    if (json) {
      if (json.errors && (json.errors.serial || json.errors.serial_int)) serialError = true;
      if (typeof json.message === 'string' && /serial|duplicate|already in use|property number/i.test(json.message)) serialError = true;
    }

    if (serialError) {
      const { serialEl } = inputsForRow(row);
      if (serialEl && serialEl.dataset.orig !== undefined) {
        serialEl.value = serialEl.dataset.orig;
      }
      if (statusEl) statusEl.textContent = prevStatus || 'Conflict';
      const userMsg = (json && (json.message || (json.errors && json.errors.serial && json.errors.serial.join(' ')))) || 'Serial conflict — reverted.';
      try { if (typeof window.showToast === 'function') window.showToast('error', userMsg); } catch (_) {}
      window.dispatchEvent(new CustomEvent('instance:update:serial_conflict', { detail: { instanceId, message: userMsg } }));
      return;
    }

    const fallbackMsg = (json && (json.message || (json.errors && Object.values(json.errors).flat().join(' ')))) || 'Validation failed';
    if (statusEl) statusEl.textContent = prevStatus || 'Error';
    try { if (typeof window.showToast === 'function') window.showToast('error', fallbackMsg); } catch (_) {}
    window.dispatchEvent(new CustomEvent('instance:update:failed', { detail: { instanceId, status: result.status, message: fallbackMsg } }));
  }, 700);

  Array.from(container.querySelectorAll('.edit-instance-row')).forEach((row) => {
    snapshotRow(row);
  });

  container.addEventListener('input', (e) => {
    const t = e.target;
    if (!t.matches('.instance-part-year, .instance-part-category, .instance-part-serial, .instance-part-office, .instance-part-category')) return;
    const row = t.closest('[data-instance-id]');
    if (!row) return;
    if (t.classList.contains('instance-part-office')) {
      t.value = t.value.replace(/[^a-zA-Z0-9]/g, '').slice(0,4);
    }
    if (t.classList.contains('instance-part-serial')) {
      t.value = t.value.replace(/[^A-Za-z0-9]/g, '');
    }
    if (t.classList.contains('instance-part-gla')) {
      t.value = t.value.replace(/\D/g, '').slice(0,6);
    }

    scheduleSaveForRow(row);
  });

  container.addEventListener('click', async (e) => {
    const btn = e.target.closest('.instance-remove-btn');
    if (!btn) return;
    const row = btn.closest('[data-instance-id]');
    if (!row) return;
    const instanceId = row.getAttribute('data-instance-id');
    if (!instanceId) return;

    const confirmed = await showActionConfirm('Remove property number', 'Are you sure you want to remove this property number?');
    if (!confirmed) return;

    btn.disabled = true;
    try {
      const { ok, status, json } = await apiDeleteInstance(instanceId);
      if (!ok) {
        const message = (json && (json.message || json.error)) ? (json.message || json.error) : `Delete failed (${status})`;
        showToast('error', message);
        btn.disabled = false;
        return;
      }

      row.remove();

      Array.from(container.querySelectorAll('.edit-instance-row')).forEach((r, idx) => {
        const circle = r.querySelector('.inline-flex');
        if (circle) circle.textContent = String(idx + 1);
      });

      try { if (typeof window.showToast === 'function') window.showToast('success', 'Instance deleted'); } catch (_) {}
      window.dispatchEvent(new CustomEvent('instance:deleted', { detail: { instanceId } }));

    } catch (err) {
      console.error('Delete instance failed', err);
      showToast('error', 'Unexpected error while deleting instance.');
      btn.disabled = false;
    }
  });

  const mo = new MutationObserver((mutations) => {
    for (const m of mutations) {
      for (const added of m.addedNodes) {
        if (added instanceof Element) {
          if (added.classList.contains('edit-instance-row')) snapshotRow(added);
          added.querySelectorAll && added.querySelectorAll('.edit-instance-row').forEach(snapshotRow);
        }
      }
    }
  });
  mo.observe(container, { childList: true, subtree: true });
});