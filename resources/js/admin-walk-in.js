// resources/js/admin-walk-in.js

(function() {
  const MANPOWER_ROLES_ENDPOINT = window.MANPOWER_ROLES_ENDPOINT || '/admin/manpower-roles';
  let MANPOWER_ROLES_CACHE = [];
  let manpowerRolesPromise = null;

  function clamp(value, min, max) {
    const n = parseInt(value, 10);
    if (!Number.isFinite(n)) return 0;
    return Math.max(min, Math.min(max, n));
  }

  function digitsOnly(value) {
    if (typeof value !== 'string') {
      value = value == null ? '' : String(value);
    }
    return value.replace(/[^0-9]/g, '');
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
      const digits = digitsOnly(input.value || '');
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

  async function loadManpowerRoles(force = false) {
    if (!force && MANPOWER_ROLES_CACHE.length) {
      return MANPOWER_ROLES_CACHE;
    }

    if (manpowerRolesPromise && !force) {
      return manpowerRolesPromise;
    }

    manpowerRolesPromise = (async () => {
      const res = await fetch(MANPOWER_ROLES_ENDPOINT, {
        headers: { Accept: 'application/json' },
      });

      if (!res.ok) {
        throw new Error(`Failed to load manpower roles (${res.status})`);
      }

      const payload = await res.json().catch(() => []);
      const normalized = Array.isArray(payload) ? payload : [];
      MANPOWER_ROLES_CACHE = normalized
        .map((role) => ({
          id: Number(role?.id ?? 0),
          name: typeof role?.name === 'string' ? role.name.trim() : '',
        }))
        .filter((role) => role.id && role.name);

      MANPOWER_ROLES_CACHE.sort((a, b) => a.name.localeCompare(b.name));
      return MANPOWER_ROLES_CACHE;
    })().catch((error) => {
      console.error('Failed to fetch manpower roles', error);
      if (typeof window.showToast === 'function') {
        window.showToast('Unable to load manpower roles. Try again.', 'error');
      }
      MANPOWER_ROLES_CACHE = [];
      return [];
    });

    return manpowerRolesPromise;
  }

  function findManpowerRoleByName(name) {
    if (!name) return null;
    const normalized = String(name).toLowerCase();
    return MANPOWER_ROLES_CACHE.find((role) => role.name.toLowerCase() === normalized) || null;
  }

  function getSelectedRoleNames(exceptEl = null) {
    return Array.from(document.querySelectorAll('[data-manpower-role]'))
      .filter((el) => el !== exceptEl)
      .map((el) => (el.value || '').trim())
      .filter(Boolean);
  }

  function populateRoleSelect(selectEl, selectedRoleName = '', excludeRoles = []) {
    if (!selectEl) return;

    selectEl.innerHTML = '';
    const roles = MANPOWER_ROLES_CACHE;
    const excludeSet = new Set(excludeRoles.map((r) => r.toLowerCase()));

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = roles.length ? 'Select role' : 'No roles available';
    placeholder.disabled = true;
    placeholder.selected = true;
    selectEl.appendChild(placeholder);

    let matched = false;
    const current = (selectedRoleName || '').toLowerCase();
    const availableRoles = roles.filter((role) => {
      if (!role?.name) return false;
      const nameLc = role.name.toLowerCase();
      return !excludeSet.has(nameLc);
    });

    availableRoles.forEach((role) => {
      const option = document.createElement('option');
      option.value = role.name;
      option.textContent = role.name;
      option.dataset.roleId = String(role.id);
      if (current && role.name.toLowerCase() === current) {
        option.selected = true;
        matched = true;
      }
      selectEl.appendChild(option);
    });

    if (availableRoles.length) {
      selectEl.disabled = false;
      if (matched) {
        placeholder.selected = false;
      } else {
        selectEl.value = availableRoles[0].name;
        placeholder.selected = false;
      }
    } else {
      selectEl.disabled = true;
    }
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
    const confirmModalName = 'walkinConfirmModal';
    if (!submitBtn || !form) return;

    let pendingPayload = null;

    submitBtn.addEventListener('click', async () => {
      const items = collectSelected();
      if (items.length === 0) {
        window.showToast('Please select at least one item (quantity > 0).', 'warning');
        return;
      }

      const rawBorrowerId = document.getElementById('user_id')?.value || '';
      const borrowerId = rawBorrowerId ? parseInt(rawBorrowerId, 10) || null : null;

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
        borrower_id: borrowerId,
      };

      pendingPayload = payload;
      await ensureDefaultManpowerRow();
      window.dispatchEvent(new CustomEvent('open-modal', { detail: confirmModalName }));
    });

    const confirmSubmitBtn = document.getElementById('walkinConfirmSubmitBtn');
    if (confirmSubmitBtn) {
      confirmSubmitBtn.addEventListener('click', async () => {
        if (!pendingPayload) return;
        const manpower = collectManpowerRows();
        if (manpower.error) {
          window.showToast(manpower.error, 'error');
          return;
        }
        const payload = { ...pendingPayload, manpower };
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
          window.location.href = '/admin/walk-in';
        } catch (e) {
          window.showToast('Network error. Please try again.', 'error');
        }
      });
    }
  }

  async function ensureDefaultManpowerRow() {
    const container = document.getElementById('walkinManpowerRows');
    if (!container) return;
    if (container.children.length === 0) {
      await loadManpowerRoles();
      if (!MANPOWER_ROLES_CACHE.length) {
        if (typeof window.showToast === 'function') {
          window.showToast('No manpower roles available. Please add roles before continuing.', 'error');
        }
        return;
      }
      const assist = findManpowerRoleByName('Assist');
      const fallback = MANPOWER_ROLES_CACHE[0]?.name || 'Assist';
      const selectedRole = assist?.name || fallback;
      container.appendChild(buildManpowerRow(selectedRole, 10));
    }
    updateManpowerControls();
  }

  function buildManpowerRow(roleValue = '', quantityValue = 1) {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3';

    const roleSelect = document.createElement('select');
    roleSelect.className = 'gov-input flex-1 text-sm';
    roleSelect.required = true;
    roleSelect.dataset.manpowerRole = '1';
    populateRoleSelect(roleSelect, roleValue, getSelectedRoleNames());
    roleSelect.addEventListener('change', () => {
      updateManpowerControls();
    });

    const qtyInput = document.createElement('input');
    qtyInput.type = 'number';
    qtyInput.className = 'gov-input w-24 text-sm';
    qtyInput.min = '1';
    qtyInput.max = '999';
    qtyInput.value = quantityValue;
    qtyInput.dataset.manpowerQty = '1';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'text-gray-400 hover:text-red-500 transition';
    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
    removeBtn.addEventListener('click', () => {
      row.remove();
      updateManpowerControls();
    });

    row.appendChild(roleSelect);
    row.appendChild(qtyInput);
    row.appendChild(removeBtn);
    return row;
  }

  function collectManpowerRows() {
    const container = document.getElementById('walkinManpowerRows');
    if (!container) return { role: 'Assist', quantity: 10 };
    const rows = Array.from(container.querySelectorAll('[data-manpower-role]')).length
      ? Array.from(container.children)
      : Array.from(container.querySelectorAll('[data-manpower-role]'));
    const roleInputs = Array.from(container.querySelectorAll('[data-manpower-role]'));
    const qtyInputs = Array.from(container.querySelectorAll('[data-manpower-qty]'));
    const role = roleInputs[0]?.value?.trim() || '';
    const qtyRaw = qtyInputs[0]?.value ?? '10';
    const qty = Math.max(1, parseInt(qtyRaw, 10) || 10);
    if (!role) {
      return { error: 'Please select at least one manpower role.' };
    }
    return { role, quantity: qty };
  }

  function bindManpowerAdder() {
    const addBtn = document.getElementById('walkinAddManpowerRow');
    if (!addBtn) return;
    addBtn.addEventListener('click', async () => {
      const container = document.getElementById('walkinManpowerRows');
      if (!container) return;
      const roles = await loadManpowerRoles();
      if (!roles.length) {
        if (typeof window.showToast === 'function') {
          window.showToast('No manpower roles available to add.', 'warning');
        }
        return;
      }
      const inUse = new Set(getSelectedRoleNames());
      const available = roles.filter((role) => role?.name && !inUse.has(role.name));
      if (!available.length) {
        if (typeof window.showToast === 'function') {
          window.showToast('All manpower roles are already selected.', 'info');
        }
        updateManpowerControls();
        return;
      }
      const defaultRole = available[0]?.name || '';
      container.appendChild(buildManpowerRow(defaultRole, 1));
      updateManpowerControls();
    });
  }

  function refreshRoleSelectOptions() {
    const selects = Array.from(document.querySelectorAll('[data-manpower-role]'));
    selects.forEach((select) => {
      const exclude = getSelectedRoleNames(select);
      populateRoleSelect(select, select.value, exclude);
    });
  }

  function updateManpowerControls() {
    refreshRoleSelectOptions();
    const addBtn = document.getElementById('walkinAddManpowerRow');
    if (!addBtn) return;
    const totalRoles = MANPOWER_ROLES_CACHE.length;
    const used = new Set(getSelectedRoleNames());
    const allUsed = totalRoles > 0 && used.size >= totalRoles;
    addBtn.disabled = allUsed || totalRoles === 0;
    addBtn.classList.toggle('opacity-50', addBtn.disabled);
    addBtn.classList.toggle('cursor-not-allowed', addBtn.disabled);
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
      updateTimeBoundaries();
    });
  }

  function getTodayDateString() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function getRoundedNowTime() {
    const now = new Date();
    now.setSeconds(0, 0);
    const remainder = now.getMinutes() % 5;
    if (remainder !== 0) {
      now.setMinutes(now.getMinutes() + (5 - remainder));
    }
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
  }

  function clampTimeValue(input, minValue) {
    if (!input || !minValue) return;
    if (input.value && input.value < minValue) {
      input.value = minValue;
    }
  }

  function updateTimeBoundaries() {
    const borrowedDate = document.getElementById('borrowed_date');
    const returnedDate = document.getElementById('returned_date');
    const borrowedTime = document.getElementById('borrowed_time');
    const returnedTime = document.getElementById('returned_time');
    if (!borrowedTime || !returnedTime) return;

    const today = getTodayDateString();
    const roundedNow = getRoundedNowTime();

    borrowedTime.max = '23:59';
    returnedTime.max = '23:59';

    if (borrowedDate?.value === today) {
      borrowedTime.min = roundedNow;
      clampTimeValue(borrowedTime, roundedNow);
    } else {
      borrowedTime.removeAttribute('min');
    }

    let endMin = '';
    if (returnedDate?.value && borrowedDate?.value && returnedDate.value === borrowedDate.value) {
      endMin = borrowedTime.value || (returnedDate.value === today ? roundedNow : '');
    } else if (returnedDate?.value === today) {
      endMin = roundedNow;
    }

    if (endMin) {
      returnedTime.min = endMin;
      clampTimeValue(returnedTime, endMin);
    } else {
      returnedTime.removeAttribute('min');
    }
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
        updateTimeBoundaries();
        checkAvailability();
      });
    }
    
    if (returnedDate) {
      returnedDate.setAttribute('min', today);
      returnedDate.addEventListener('change', () => {
        updateTimeBoundaries();
        checkAvailability();
      });
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

  function applyPrefillFromQuery() {
    if (!window.location || !window.location.search) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    if (params.size === 0) {
      return;
    }

    let prefilled = false;
    const assign = (id, value) => {
      if (!value) return false;
      const el = document.getElementById(id);
      if (!el) return false;
      el.value = value;
      return true;
    };

    if (params.has('borrower_id')) {
      // Preserve the selected account so the request links to the user
      prefilled = assign('user_id', params.get('borrower_id')) || prefilled;
    }

    if (params.has('borrower_name')) {
      prefilled = assign('borrower_name', params.get('borrower_name')) || prefilled;
    }

    if (params.has('office_agency')) {
      prefilled = assign('office_agency', params.get('office_agency')) || prefilled;
    }

    if (params.has('address')) {
      prefilled = assign('address', params.get('address')) || prefilled;
    }

    if (params.has('contact_number')) {
      const contactInput = document.getElementById('contact_number');
      if (contactInput) {
        contactInput.value = digitsOnly(params.get('contact_number'));
        contactInput.dispatchEvent(new Event('input', { bubbles: true }));
        prefilled = true;
      }
    }

    if (prefilled && params.get('prefill') === 'borrower' && typeof window.showToast === 'function') {
      window.showToast('Borrower details pre-filled from selected account.', 'info');
    }

    if (prefilled && window.history && window.history.replaceState) {
      const cleanUrl = `${window.location.origin}${window.location.pathname}`;
      window.history.replaceState({}, document.title, cleanUrl);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    attachQtyHandlers();
    attachSearchHandler();
    attachSubmitHandler();
    attachClearHandler();
    enforceDigitsOnly(document.getElementById('contact_number'));
    initDateInputs();
    applyPrefillFromQuery();
    loadManpowerRoles().then(() => {
      ensureDefaultManpowerRow();
      updateManpowerControls();
    });
    bindManpowerAdder();
    
    ['borrowed_time', 'returned_time'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', () => {
          updateUsagePreview();
          updateTimeBoundaries();
        });
        el.addEventListener('change', () => {
          updateUsagePreview();
          updateTimeBoundaries();
        });
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
    updateTimeBoundaries();
  });
})();
