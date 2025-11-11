// resources/js/admin-walk-in.js

(function() {
  function clamp(value, min, max) {
    const n = parseInt(value, 10);
    if (!Number.isFinite(n)) return 0;
    return Math.max(min, Math.min(max, n));
  }

  function collectSelected() {
    const inputs = document.querySelectorAll('[data-qty-input]');
    const items = [];
    inputs.forEach((el) => {
      const max = parseInt(el.getAttribute('max') || '0', 10) || 0;
      let v = clamp(el.value, 0, max);
      if (v > 0) {
        items.push({ id: parseInt(el.dataset.itemId, 10), quantity: v });
      }
    });
    return items;
  }

  function updateSelectedCount() {
    const items = collectSelected();
    const countEl = document.getElementById('selectedCount');
    if (countEl) countEl.textContent = String(items.length);
  }

  function attachQtyHandlers() {
    document.querySelectorAll('[data-qty-input]').forEach((el) => {
      el.addEventListener('input', () => {
        const max = parseInt(el.getAttribute('max') || '0', 10) || 0;
        let raw = String(el.value ?? '').replace(/[^\d]/g, '');
        if (raw.length > 3) raw = raw.slice(0, 3);
        let v = clamp(raw || '0', 0, max);
        el.value = String(v);
        updateSelectedCount();
      });
      el.addEventListener('blur', () => {
        const max = parseInt(el.getAttribute('max') || '0', 10) || 0;
        el.value = String(clamp(el.value, 0, max));
        updateSelectedCount();
      });
    });
  }

  function attachSearchHandler() {
    const search = document.getElementById('walkinSearch');
    if (!search) return;
    search.addEventListener('input', () => {
      const term = (search.value || '').toLowerCase().trim();
      document.querySelectorAll('[data-item-row]').forEach((row) => {
        const name = row.getAttribute('data-name') || '';
        row.style.display = name.includes(term) ? '' : 'none';
      });
    });
  }

  function attachSubmitHandler() {
    const submitBtn = document.getElementById('walkinSubmitBtn');
    const form = document.getElementById('walkinForm');
    if (!submitBtn || !form) return;

    submitBtn.addEventListener('click', async () => {
      const items = collectSelected();
      if (items.length === 0) {
        alert('Please select at least one item (quantity > 0).');
        return;
      }

      // Map form data
      const payload = {
        borrower_name: document.getElementById('borrower_name')?.value || '',
        office_agency: document.getElementById('office_agency')?.value || '',
        contact_number: document.getElementById('contact_number')?.value || '',
        address: document.getElementById('address')?.value || '',
        purpose: document.getElementById('purpose')?.value || '',
        borrowed_at: document.getElementById('borrowed_at')?.value || '',
        returned_at: document.getElementById('returned_at')?.value || '',
        items,
      };

      try {
        const res = await fetch(form.getAttribute('action'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          alert(data?.message || 'Failed to submit walk-in request.');
          return;
        }
        alert(data?.message || 'Walk-in request submitted.');
        // Redirect back to index to show in table
        window.location.href = '/admin/walk-in';
      } catch (e) {
        alert('Network error. Please try again.');
      }
    });
  }

  function attachClearHandler() {
    const btn = document.getElementById('walkinClearBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-qty-input]').forEach((el) => {
        el.value = '0';
      });
      updateSelectedCount();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    attachQtyHandlers();
    attachSearchHandler();
    attachSubmitHandler();
    attachClearHandler();
    updateSelectedCount();
  });
})();
