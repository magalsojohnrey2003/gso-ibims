// item-instances-editor.js
document.addEventListener('DOMContentLoaded', () => {
  // Only run if edit_instances_container exists
  const container = document.getElementById('edit_instances_container');
  if (!container) return;

  const debounce = (fn, wait = 400) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  };

  function buildPNFromRow(row) {
    const y = row.querySelector('.instance-part-year')?.value?.trim() ?? '';
    const p = row.querySelector('.instance-part-ppe')?.value?.trim() ?? '';
    const s = row.querySelector('.instance-part-serial')?.value?.trim() ?? '';
    const o = row.querySelector('.instance-part-office')?.value?.trim() ?? '';
    if (!y && !p && !s && !o) return '';
    return `${y}-${p}-${s}-${o}`;
  }

  async function patchInstance(instanceId, payload, row) {
    try {
      const url = `/admin/item-instances/${instanceId}`;
      const res = await fetch(url, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });

      if (res.ok) {
        const json = await res.json();
        // update status text & totals
        const statusEl = row.querySelector('.instance-status');
        if (statusEl && json.instance?.property_number) statusEl.textContent = json.instance.property_number;

        // dispatch global event to update items row totals
        if (json.item) {
          window.dispatchEvent(new CustomEvent('items:edit:success', { detail: {
            item_id: json.item.id,
            total_qty: json.item.total_qty,
            available_qty: json.item.available_qty,
            message: 'Instance updated.',
            property_number: json.instance?.property_number ?? null,
          }}));
        }

        // subtle success UI
        row.classList.add('bg-green-50');
        setTimeout(() => row.classList.remove('bg-green-50'), 700);
        return true;
      }

      // handle non-ok statuses
      const payload = await res.json().catch(() => null);
      const msg = payload?.message || `Failed to update (status ${res.status})`;
      showInlineError(row, msg);
      return false;
    } catch (err) {
      showInlineError(row, 'Network error while saving.');
      return false;
    }
  }

  async function deleteInstance(instanceId, row) {
    if (!confirm('Delete this instance? This cannot be undone.')) return;
    try {
      const url = `/admin/item-instances/${instanceId}`;
      const res = await fetch(url, {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin'
      });

      if (res.ok) {
        const json = await res.json().catch(() => null);
        // remove row from DOM
        row.remove();
        // re-number rows
        Array.from(container.querySelectorAll('.edit-instance-row')).forEach((r, i) => {
          const circ = r.querySelector('.inline-flex');
          if (circ) circ.textContent = String(i + 1);
        });

        if (json && json.item) {
          window.dispatchEvent(new CustomEvent('items:edit:success', { detail: {
            item_id: json.item.id,
            total_qty: json.item.total_qty,
            available_qty: json.item.available_qty,
            message: 'Instance deleted.',
          }}));
        }
        return true;
      }

      const payload = await res.json().catch(() => null);
      const msg = payload?.message || `Failed to delete (status ${res.status})`;
      showInlineError(row, msg);
      return false;
    } catch (err) {
      showInlineError(row, 'Network error while deleting.');
      return false;
    }
  }

  function showInlineError(row, message) {
    let el = row.querySelector('.instance-error');
    if (!el) {
      el = document.createElement('div');
      el.className = 'instance-error text-xs text-red-600 mt-2';
      row.appendChild(el);
    }
    el.textContent = message;
    setTimeout(() => { if (el) el.remove(); }, 5000);
  }

  // attach listeners per row
  const rows = Array.from(container.querySelectorAll('.edit-instance-row'));
  rows.forEach(row => {
    const instanceId = row.dataset.instanceId;
    const parts = ['.instance-part-year', '.instance-part-ppe', '.instance-part-serial', '.instance-part-office'];
    const inputs = parts.map(sel => row.querySelector(sel)).filter(Boolean);

    // autosave on blur or after debounce
    const save = debounce(async () => {
      const pn = buildPNFromRow(row);
      if (!pn) {
        // if all empty, do not patch (or optionally you could delete)
        return;
      }
      // send full property_number to server for canonical parsing
      await patchInstance(instanceId, { property_number: pn }, row);
    }, 500);

    inputs.forEach(inp => {
      inp.addEventListener('input', () => {
        // update PN live in status area
        const statusEl = row.querySelector('.instance-status');
        const pn = buildPNFromRow(row);
        if (statusEl) statusEl.textContent = pn;
      });

      inp.addEventListener('blur', save);
    });

    // Remove button
    const removeBtn = row.querySelector('.instance-remove-btn');
    if (removeBtn) {
      removeBtn.addEventListener('click', async () => {
        await deleteInstance(instanceId, row);
      });
    }
  });
});
