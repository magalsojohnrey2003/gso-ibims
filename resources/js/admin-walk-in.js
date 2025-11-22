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
        window.showToast('Please select at least one item (quantity > 0).', 'warning');
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
          window.showToast(data?.message || 'Failed to submit walk-in request.', 'error');
          return;
        }
        window.showToast(data?.message || 'Walk-in request submitted successfully.', 'success');
        // Redirect back to index to show in table
        window.location.href = '/admin/walk-in';
      } catch (e) {
        window.showToast('Network error. Please try again.', 'error');
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

  function getTodayDateString() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function initDateInputs() {
    const borrowedDate = document.getElementById('borrowed_date');
    const returnedDate = document.getElementById('returned_date');
    const today = getTodayDateString();
    
    if (borrowedDate) {
      borrowedDate.setAttribute('min', today);
      borrowedDate.addEventListener('change', () => {
        // Update return date min to borrowed date
        if (returnedDate && borrowedDate.value) {
          returnedDate.setAttribute('min', borrowedDate.value);
          // Clear return date if it's before borrowed date
          if (returnedDate.value && returnedDate.value < borrowedDate.value) {
            returnedDate.value = '';
          }
        }
        checkAvailability();
      });
    }
    
    if (returnedDate) {
      returnedDate.setAttribute('min', today);
      returnedDate.addEventListener('change', checkAvailability);
    }
  }

  let blockedDates = [];
  let availabilityCheckTimeout = null;

  function checkAvailability() {
    const borrowedDate = document.getElementById('borrowed_date');
    const returnedDate = document.getElementById('returned_date');
    
    if (!borrowedDate?.value || !returnedDate?.value) {
      clearAvailabilityWarning();
      return;
    }

    // Debounce availability check
    clearTimeout(availabilityCheckTimeout);
    availabilityCheckTimeout = setTimeout(async () => {
      const items = collectSelected();
      if (items.length === 0) {
        clearAvailabilityWarning();
        return;
      }

      try {
        const itemsJson = encodeURIComponent(JSON.stringify(items));
        const res = await fetch(`/user/availability?items=${itemsJson}`);
        if (!res.ok) throw new Error('Failed to fetch availability');
        
        blockedDates = await res.json();
        validateDateRange(borrowedDate.value, returnedDate.value);
      } catch (e) {
        console.error('Availability check failed:', e);
        clearAvailabilityWarning();
      }
    }, 500);
  }

  function validateDateRange(startDate, endDate) {
    const dates = getDatesBetween(startDate, endDate);
    const hasBlockedDate = dates.some(date => blockedDates.includes(date));
    
    if (hasBlockedDate) {
      showAvailabilityWarning('Selected date range includes dates when some items are not available. Please choose another range.');
    } else {
      clearAvailabilityWarning();
    }
  }

  function getDatesBetween(start, end) {
    const dates = [];
    const startDate = new Date(start);
    const endDate = new Date(end);
    
    for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      dates.push(`${year}-${month}-${day}`);
    }
    
    return dates;
  }

  function showAvailabilityWarning(message) {
    let warning = document.getElementById('availabilityWarning');
    if (!warning) {
      const borrowedDate = document.getElementById('borrowed_date');
      if (!borrowedDate || !borrowedDate.parentElement || !borrowedDate.parentElement.parentElement) return;
      
      warning = document.createElement('div');
      warning.id = 'availabilityWarning';
      warning.className = 'rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 mt-2';
      warning.innerHTML = `<strong>⚠ Availability Issue:</strong> <span id="availabilityWarningText"></span>`;
      
      // Insert after the date grid
      borrowedDate.parentElement.parentElement.parentElement.insertBefore(
        warning,
        borrowedDate.parentElement.parentElement.nextSibling
      );
    }
    
    const textEl = document.getElementById('availabilityWarningText');
    if (textEl) textEl.textContent = message;
    warning.classList.remove('hidden');
  }

  function clearAvailabilityWarning() {
    const warning = document.getElementById('availabilityWarning');
    if (warning) warning.classList.add('hidden');
  }

  document.addEventListener('DOMContentLoaded', () => {
    attachQtyHandlers();
    attachSearchHandler();
    attachSubmitHandler();
    attachClearHandler();
    enforceDigitsOnly(document.getElementById('contact_number'));
    initDateInputs();
    
    ['borrowed_time', 'returned_time'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', updateUsagePreview);
        el.addEventListener('change', updateUsagePreview);
      }
    });
    
    // Check availability when items change
    document.addEventListener('input', (e) => {
      if (e.target.matches('[data-qty-input]')) {
        checkAvailability();
      }
    });
    
    updateUsagePreview();
    updateSelectedCount();
  });
})();
