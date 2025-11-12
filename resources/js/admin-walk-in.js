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

  function enforceDigitsOnly(input, maxLength = 11) {
    if (!input) return;
  const controlKeys = new Set(['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter']);
    const sanitize = () => {
      const digits = (input.value || '').replace(/[^0-9]/g, '');
      const trimmed = digits.slice(0, maxLength);
      if (input.value !== trimmed) {
        input.value = trimmed;
      }
    };

    input.addEventListener('keydown', (event) => {
      if (event.ctrlKey || event.metaKey || event.altKey) return;
      if (controlKeys.has(event.key)) return;
      if (/^[0-9]$/.test(event.key)) return;
      event.preventDefault();
    });

    input.addEventListener('input', sanitize);
    input.addEventListener('paste', () => {
      setTimeout(sanitize, 0);
    });
  }

  function buildDateTimePayload(dateId, timeId) {
    const dateValue = document.getElementById(dateId)?.value?.trim();
    const timeValue = document.getElementById(timeId)?.value?.trim();
    if (!dateValue) {
      return '';
    }
    if (!timeValue) {
      return dateValue;
    }
    return `${dateValue} ${timeValue}`;
  }

  function formatTimePreview(value) {
    if (!value) return '';
    const [hours, minutes] = value.split(':');
    const h = Number.parseInt(hours, 10);
    if (Number.isNaN(h)) return value;
    const m = Number.parseInt(minutes ?? '0', 10) || 0;
    const period = h >= 12 ? 'PM' : 'AM';
    const normalizedHour = ((h + 11) % 12) + 1;
    const paddedMinutes = m.toString().padStart(2, '0');
    return `${normalizedHour}:${paddedMinutes} ${period}`;
  }

  function updateUsagePreview() {
    const preview = document.getElementById('timeUsagePreview');
    if (!preview) return;
    const range = preview.querySelector('[data-preview-range]');
    const borrowTime = document.getElementById('borrowed_time')?.value?.trim();
    const returnTime = document.getElementById('returned_time')?.value?.trim();
    if (borrowTime && returnTime) {
      const start = formatTimePreview(borrowTime);
      const end = formatTimePreview(returnTime);
      range.textContent = `${start} - ${end}`;
      preview.classList.remove('hidden');
    } else {
      range.textContent = '—';
      preview.classList.add('hidden');
    }
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
        borrowed_at: buildDateTimePayload('borrowed_date', 'borrowed_time'),
        returned_at: buildDateTimePayload('returned_date', 'returned_time'),
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
      const dateFields = ['borrowed_date', 'borrowed_time', 'returned_date', 'returned_time'];
      dateFields.forEach((id) => {
        const input = document.getElementById(id);
        if (input) input.value = '';
      });
      const contact = document.getElementById('contact_number');
      if (contact) contact.value = '';
      updateSelectedCount();
      updateUsagePreview();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    attachQtyHandlers();
    attachSearchHandler();
    attachSubmitHandler();
    attachClearHandler();
    enforceDigitsOnly(document.getElementById('contact_number'));
    ['borrowed_time', 'returned_time'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', updateUsagePreview);
        el.addEventListener('change', updateUsagePreview);
      }
    });
    updateUsagePreview();
    updateSelectedCount();
  });
})();
