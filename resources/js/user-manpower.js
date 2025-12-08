// resources/js/user-manpower.js
document.addEventListener('DOMContentLoaded', () => {
  if (!window.USER_MANPOWER) return;
  const tbody = document.getElementById('userManpowerTableBody');
  const search = document.getElementById('user-manpower-search');
  const openBtn = document.getElementById('openManpowerCreate');
  const confirmBtn = document.getElementById('confirmManpowerSubmit');
  const saveBtn = document.getElementById('saveManpowerRequest');
  const form = document.getElementById('userManpowerForm');
  const purposeInput = document.getElementById('mp_purpose');
  const officeInput = document.getElementById('mp_office');
  const locationInput = document.getElementById('mp_location');
  const letterInput = document.getElementById('mp_letter');
  const locationPreview = document.getElementById('locationPreview');
  const schedulePreview = document.getElementById('schedulePreview');
  const wizardSections = Array.from(document.querySelectorAll('[data-mp-step]'));
  const wizardIndicatorItems = Array.from(document.querySelectorAll('#manpowerWizardIndicator [data-step-index]'));
  const wizardPrevButton = document.getElementById('mpWizardPrev');
  const wizardNextButton = document.getElementById('mpWizardNext');
  const stepLabelEl = document.getElementById('manpowerWizardStepLabel');
  const summaryEls = {
    quantity: document.getElementById('mpSummaryQuantity'),
    role: document.getElementById('mpSummaryRole'),
    purpose: document.getElementById('mpSummaryPurpose'),
    office: document.getElementById('mpSummaryOffice'),
    location: document.getElementById('mpSummaryLocation'),
    schedule: document.getElementById('mpSummarySchedule'),
    letter: document.getElementById('mpSummaryLetter'),
  };
  const STEP_LABELS = {
    1: 'Personnel Requirements & Context',
    2: 'Location & Schedule',
    3: 'Documents & Review',
  };
  const TOTAL_STEPS = wizardSections.length || 3;
  let currentStep = 1;
  const INDICATOR_CLASS_STATES = {
    default: {
      container: ['border-gray-200', 'bg-white', 'text-gray-600'],
      badge: ['bg-gray-200', 'text-gray-700'],
    },
    active: {
      container: ['border-purple-500', 'bg-purple-50', 'text-purple-700', 'shadow-sm'],
      badge: ['bg-purple-600', 'text-white'],
    },
    complete: {
      container: ['border-green-500', 'bg-green-50', 'text-green-700', 'shadow-sm'],
      badge: ['bg-green-600', 'text-white'],
    },
  };
  const INDICATOR_RESET_CLASSES = Array.from(new Set([
    ...INDICATOR_CLASS_STATES.default.container,
    ...INDICATOR_CLASS_STATES.active.container,
    ...INDICATOR_CLASS_STATES.complete.container,
  ]));
  const INDICATOR_BADGE_RESET_CLASSES = Array.from(new Set([
    ...INDICATOR_CLASS_STATES.default.badge,
    ...INDICATOR_CLASS_STATES.active.badge,
    ...INDICATOR_CLASS_STATES.complete.badge,
  ]));
  // FilePond instance for letter upload
  let filePondInstance = null;
  const roleEmptyMessage = document.getElementById('mp_role_empty');
  const manpowerRowsContainer = document.getElementById('mpManpowerRows');
  const addRoleBtn = document.getElementById('mpAddRoleRow');
  const municipalitySelect = document.getElementById('mp_municipality');
  const barangaySelect = document.getElementById('mp_barangay');
  const startDateInput = document.getElementById('mp_start_date');
  const startTimeInput = document.getElementById('mp_start_time');
  const endDateInput = document.getElementById('mp_end_date');
  const endTimeInput = document.getElementById('mp_end_time');
  const userViewFields = document.querySelectorAll('[data-user-view]');
  const qrContainer = document.getElementById('userManpowerQr');
  const userRejectionCard = document.getElementById('userManpowerRejectionCard');
  const userRejectionSubject = document.querySelector('[data-user-view="rejection_subject"]');
  const userRejectionDetail = document.querySelector('[data-user-view="rejection_detail"]');
  const userRoleBreakdownList = null;
  const MUNICIPALITY_ALIASES = {
    'cagayan-de-oro': 'cagayan-de-oro-city',
  };
  const LIMITED_MUNICIPALITIES = new Set(['cagayan-de-oro-city', 'tagoloan']);
  const MUNICIPALITY_LABELS = {
    'cagayan-de-oro-city': 'Cagayan de Oro City',
    'cagayan-de-oro': 'Cagayan de Oro City',
    tagoloan: 'Tagoloan',
  };
  const MUNICIPALITY_BARANGAYS = {};
  const ICONS = {
    eye: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    printer: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v8H6z" /></svg>'
  };
  const ROLE_ROW_TEMPLATE_CLASSES = 'flex items-center gap-2 bg-gray-50/80 border border-gray-200 rounded-lg px-3 py-2';
  const ROLE_SELECT_CLASSES = 'gov-input flex-1 min-w-0';
  const ROLE_QTY_CLASSES = 'gov-input w-24';
  let CACHE = [];
  let PENDING_PAYLOAD = null;
  let ROLE_OPTIONS = [];
  const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];

  const buildRoleOptions = (selectedId = '') => {
    const options = ['<option value="">Select role</option>'];
    ROLE_OPTIONS.forEach((role) => {
      const value = String(role.id);
      const selected = value === String(selectedId) ? 'selected' : '';
      options.push(`<option value="${value}" ${selected}>${role.name}</option>`);
    });
    return options.join('');
  };

  const buildManpowerRow = (roleId = '', quantityValue = 1) => {
    const row = document.createElement('div');
    row.className = ROLE_ROW_TEMPLATE_CLASSES;
    row.dataset.roleRow = '1';

    const select = document.createElement('select');
    select.className = ROLE_SELECT_CLASSES;
    select.innerHTML = buildRoleOptions(roleId);
    select.dataset.roleSelect = '1';

    const qtyInput = document.createElement('input');
    qtyInput.type = 'number';
    qtyInput.min = '1';
    qtyInput.max = '99';
    qtyInput.value = String(Math.max(1, Number(quantityValue) || 1));
    qtyInput.className = ROLE_QTY_CLASSES;
    qtyInput.dataset.roleQty = '1';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'inline-flex items-center justify-center h-10 w-10 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-300';
    removeBtn.innerHTML = '<i class="fas fa-trash"></i><span class="sr-only">Remove role</span>';
    removeBtn.addEventListener('click', () => {
      row.remove();
      ensureAtLeastOneRow();
      syncRoleOptions();
      refreshSummary();
    });

    [select, qtyInput, removeBtn].forEach((el) => row.appendChild(el));

    select.addEventListener('change', refreshSummary);
    select.addEventListener('change', syncRoleOptions);
    qtyInput.addEventListener('input', () => {
      let val = qtyInput.value.replace(/\D+/g, '');
      if (val === '') val = '1';
      const num = Math.min(99, Math.max(1, parseInt(val, 10) || 1));
      qtyInput.value = String(num);
      refreshSummary();
    });

    qtyInput.addEventListener('change', refreshSummary);
    qtyInput.addEventListener('change', syncRoleOptions);

    return row;
  };

  const ensureAtLeastOneRow = () => {
    if (!manpowerRowsContainer) return;
    const rows = manpowerRowsContainer.querySelectorAll('[data-role-row]');
    if (rows.length === 0) {
      manpowerRowsContainer.innerHTML = '';
      manpowerRowsContainer.appendChild(buildManpowerRow('', 1));
      syncRoleOptions();
    }
  };

  const resetRoleRows = () => {
    if (!manpowerRowsContainer) return;
    manpowerRowsContainer.innerHTML = '';
    manpowerRowsContainer.appendChild(buildManpowerRow('', 1));
    syncRoleOptions();
    refreshSummary();
  };

  const getManpowerRowsForSummary = () => {
    if (!manpowerRowsContainer) return [];
    const rows = Array.from(manpowerRowsContainer.querySelectorAll('[data-role-row]'));
    return rows.map((row) => {
      const select = row.querySelector('[data-role-select]');
      const qtyInput = row.querySelector('[data-role-qty]');
      const qty = parseInt(qtyInput?.value ?? '', 10);
      const label = select?.options?.[select.selectedIndex]?.text || '';
      const roleId = (select?.value || '').trim();
      const normalizedQty = Number.isInteger(qty) ? Math.min(99, Math.max(1, qty)) : 0;
      return { roleId, label, quantity: normalizedQty };
    });
  };

  const syncRoleOptions = () => {
    if (!manpowerRowsContainer) return;
    const selects = Array.from(manpowerRowsContainer.querySelectorAll('[data-role-select]'));
    const selected = new Set(selects.map((s) => (s.value || '').trim()).filter(Boolean));

    selects.forEach((select) => {
      const current = (select.value || '').trim();
      Array.from(select.options || []).forEach((opt) => {
        if (!opt.value) {
          opt.disabled = false;
          return;
        }
        const isTakenElsewhere = selected.has(opt.value) && opt.value !== current;
        opt.disabled = isTakenElsewhere;
      });
    });

    if (addRoleBtn) {
      const disableAdd = ROLE_OPTIONS.length > 0 && selected.size >= ROLE_OPTIONS.length;
      addRoleBtn.disabled = disableAdd;
    }
  };

  const collectManpowerRows = () => {
    const rows = getManpowerRowsForSummary();
    if (!rows.length) return { ok:false, message:'Please add at least one manpower role.' };
    const seen = new Set();
    for (const row of rows) {
      if (!row.roleId) return { ok:false, message:'Please select a manpower role for each row.' };
      if (seen.has(row.roleId)) return { ok:false, message:'Each manpower role can only be selected once.' };
      seen.add(row.roleId);
      if (!Number.isInteger(row.quantity) || row.quantity < 1) return { ok:false, message:'Quantities must be at least 1.' };
      if (row.quantity > 99) return { ok:false, message:'Maximum of 99 personnel per role.' };
    }
    return { ok:true, rows };
  };

  const formatRoleList = (breakdown = []) => {
    if (!Array.isArray(breakdown) || breakdown.length === 0) return '—';
    const normalized = breakdown
      .map((entry) => {
        const qty = Number(entry?.quantity ?? entry?.qty ?? 0);
        const label = String(entry?.role_name || entry?.label || entry?.role || '').trim();
        const normalizedQty = Number.isFinite(qty) && qty > 0 ? qty : 0;
        if (!label || normalizedQty < 1) return null;
        return { label, qty: normalizedQty };
      })
      .filter(Boolean);

    if (!normalized.length) return '—';

    return normalized
      .map((entry) => `Manpower-${entry.label} (x${entry.qty})`)
      .join(', ');
  };

  const openModal = (name) => window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
  const closeModal = (name) => window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));

  const formatRequestCode = (row) => {
    if (!row) return '';
    const formatted = typeof row.formatted_request_id === 'string' ? row.formatted_request_id.trim() : '';
    if (formatted) return formatted;
    const id = row.id ?? null;
    if (!id) return '';
    return `MP-${String(id).padStart(4, '0')}`;
  };

  const fetchRows = async () => {
    try {
      const res = await fetch(window.USER_MANPOWER.list, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const data = await res.json();
      CACHE = Array.isArray(data) ? data : [];
      render();
    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-red-600">Failed to load.</td></tr>`;
    }
  };

  const fetchRoles = async () => {
    if (!manpowerRowsContainer) return;
    ROLE_OPTIONS = [];
    addRoleBtn?.setAttribute('disabled', 'disabled');
    manpowerRowsContainer.innerHTML = `<div class="text-sm text-gray-600">Loading roles...</div>`;
    try {
      const res = await fetch(window.USER_MANPOWER.roles, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const data = await res.json();
      const roles = Array.isArray(data) ? data : [];
      ROLE_OPTIONS = roles;
      if (!roles.length) {
        manpowerRowsContainer.innerHTML = '';
        roleEmptyMessage?.classList.remove('hidden');
        refreshSummary();
        return;
      }
      roleEmptyMessage?.classList.add('hidden');
      addRoleBtn?.removeAttribute('disabled');
      resetRoleRows();
      syncRoleOptions();
    } catch (e) {
      console.error(e);
      manpowerRowsContainer.innerHTML = `<div class="text-sm text-red-600">Failed to load roles.</div>`;
      roleEmptyMessage?.classList.remove('hidden');
      refreshSummary();
    }
  };

  const loadMunicipalities = async () => {
    if (!municipalitySelect) return;
    municipalitySelect.disabled = true;
    municipalitySelect.innerHTML = `<option value="">Loading municipalities...</option>`;
    try {
      const res = await fetch(window.USER_MANPOWER.locations.municipalities, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const json = await res.json();
      const normalized = (Array.isArray(json?.data) ? json.data : []).map((m) => {
        const originalId = m?.id ? String(m.id) : '';
        const normalizedId = MUNICIPALITY_ALIASES[originalId] || originalId;
        const label = m?.name || MUNICIPALITY_LABELS[normalizedId] || MUNICIPALITY_LABELS[originalId] || normalizedId;

        return {
          ...m,
          id: normalizedId,
          name: label,
          label,
        };
      });

      const municipalities = normalized
        .filter((m) => m?.id && LIMITED_MUNICIPALITIES.has(m.id))
        .sort((a, b) => (a.name || '').localeCompare(b.name || '', undefined, { sensitivity: 'base' }));
      if (!municipalities.length) {
        municipalitySelect.innerHTML = `<option value="">Unavailable</option>`;
        municipalitySelect.disabled = true;
        refreshSummary();
        return;
      }
      municipalitySelect.innerHTML = [
        `<option value="">Select municipality</option>`,
        ...municipalities.map((m) => {
          const label = m.name || MUNICIPALITY_LABELS[m.id] || m.id;
          return `<option value="${m.id}">${label}</option>`;
        })
      ].join('');
      municipalitySelect.disabled = false;
      barangaySelect.innerHTML = `<option value="">Select barangay</option>`;
      barangaySelect.disabled = true;
      refreshSummary();
    } catch (e) {
      console.error(e);
      municipalitySelect.innerHTML = `<option value="">Failed to load municipalities</option>`;
      refreshSummary();
    }
  };

  const loadBarangays = async (municipalityId) => {
    if (!barangaySelect) return;
    const normalizedId = MUNICIPALITY_ALIASES[municipalityId] || municipalityId;

    if (!normalizedId) {
      barangaySelect.innerHTML = `<option value="">Select barangay</option>`;
      barangaySelect.disabled = true;
      refreshSummary();
      return;
    }

    const localBarangays = MUNICIPALITY_BARANGAYS[normalizedId];
    if (Array.isArray(localBarangays) && localBarangays.length) {
      barangaySelect.innerHTML = [
        `<option value="">Select barangay</option>`,
        ...localBarangays.map((b) => `<option value="${b.id}">${b.name}</option>`),
      ].join('');
      barangaySelect.disabled = false;
      refreshSummary();
      return;
    }

    barangaySelect.disabled = true;
    barangaySelect.innerHTML = `<option value="">Loading barangays...</option>`;
    try {
      const url = `${window.USER_MANPOWER.locations.barangays}/${normalizedId}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const json = await res.json();
      const barangays = (Array.isArray(json?.data) ? json.data : [])
        .map((b) => {
          const id = b?.id ?? b?.code ?? null;
          const name = b?.name ?? b?.label ?? null;
          if (!id || !name) return null;
          return {
            id: String(id),
            name: String(name),
          };
        })
        .filter((entry) => entry && entry.id && entry.name)
        .sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));

      MUNICIPALITY_BARANGAYS[normalizedId] = barangays;
      barangaySelect.innerHTML = [
        `<option value="">Select barangay</option>`,
        ...barangays.map(b => `<option value="${b.id}">${b.name}</option>`)
      ].join('');
      barangaySelect.disabled = false;
      refreshSummary();
    } catch (e) {
      console.error(e);
      barangaySelect.innerHTML = `<option value="">Failed to load barangays</option>`;
      refreshSummary();
    }
  };

  municipalitySelect?.addEventListener('change', (e) => {
    const rawValue = e.target.value;
    const normalizedValue = MUNICIPALITY_ALIASES[rawValue] || rawValue;
    loadBarangays(normalizedValue);
  });

  const badgeHtml = (status) => window.renderUserManpowerBadge ? window.renderUserManpowerBadge(status) : (status||'—');

  const formatDate = (value) => {
    if (!value) return null;
    const input = typeof value === 'string' ? (value.includes('T') ? value : value.replace(' ', 'T')) : value;
    const date = new Date(input);
    if (Number.isNaN(date.getTime())) return null;
    const month = SHORT_MONTHS[date.getMonth()];
    if (!month) return null;
    const day = date.getDate();
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
  };

  const formatDateDisplay = (value) => formatDate(value) || '—';

  const getSelectLabel = (select) => {
    if (!select || !('selectedIndex' in select)) return '';
    const option = select.options?.[select.selectedIndex];
    if (!option) return '';
    return option.text ?? '';
  };

  const formatPlainDate = (value) => {
    if (typeof value !== 'string' || value.length === 0) return null;
    const [year, month, day] = value.split('-');
    if (!year || !month || !day) return null;
    const monthIndex = Number(month) - 1;
    const monthName = SHORT_MONTHS[monthIndex];
    const dayNumber = Number(day);
    if (!monthName || Number.isNaN(dayNumber)) return null;
    return `${monthName} ${dayNumber}, ${year}`;
  };

  const formatTimeLabel = (value) => {
    if (!value) return '';
    const [hoursPart, minutesPart = '0'] = value.split(':');
    const hours = parseInt(hoursPart, 10);
    if (Number.isNaN(hours)) return value;
    const minutes = parseInt(minutesPart, 10) || 0;
    const suffix = hours >= 12 ? 'PM' : 'AM';
    const normalizedHours = ((hours + 11) % 12) + 1;
    const paddedMinutes = String(minutes).padStart(2, '0');
    return `${normalizedHours}:${paddedMinutes} ${suffix}`;
  };

  const refreshSummary = () => {
    if (!summaryEls) return;
    const roleRows = getManpowerRowsForSummary();
    const totalQuantity = roleRows.reduce((acc, row) => acc + (Number.isInteger(row.quantity) ? row.quantity : 0), 0);
    const roleSummary = formatRoleList(roleRows);
    if (summaryEls.quantity) {
      summaryEls.quantity.textContent = totalQuantity > 0
        ? `${totalQuantity} personnel${totalQuantity === 1 ? '' : 's'}`
        : '—';
    }

    if (summaryEls.role) {
      summaryEls.role.textContent = roleSummary || '—';
    }

    // role breakdown in review summary removed

    if (summaryEls.purpose) {
      const purposeValue = (purposeInput?.value || '').trim();
      summaryEls.purpose.textContent = purposeValue || '—';
    }

    if (summaryEls.office) {
      const officeValue = (officeInput?.value || '').trim();
      summaryEls.office.textContent = officeValue || '—';
    }

    if (summaryEls.location) {
      const municipalityText = municipalitySelect && municipalitySelect.value ? getSelectLabel(municipalitySelect) : '';
      const barangayText = barangaySelect && barangaySelect.value ? getSelectLabel(barangaySelect) : '';
      const areaText = (locationInput?.value || '').trim();
      const parts = [municipalityText, barangayText, areaText].filter(Boolean);
      summaryEls.location.textContent = parts.length ? parts.join(', ') : '—';
    }

    if (summaryEls.schedule) {
      const startDateValue = startDateInput?.value || '';
      const endDateValue = endDateInput?.value || '';
      const startLabel = startDateValue ? formatPlainDate(startDateValue) : null;
      const endLabel = endDateValue ? formatPlainDate(endDateValue) : null;

      const startDisplay = startLabel
        ? `${startLabel}${startTimeInput?.value ? ` ${formatTimeLabel(startTimeInput.value)}` : ''}`
        : null;
      const endDisplay = endLabel
        ? `${endLabel}${endTimeInput?.value ? ` ${formatTimeLabel(endTimeInput.value)}` : ''}`
        : null;

      if (startDisplay || endDisplay) {
        summaryEls.schedule.textContent = `Start: ${startDisplay || '—'} • End: ${endDisplay || '—'}`;
      } else {
        summaryEls.schedule.textContent = '—';
      }
    }

    if (summaryEls.letter) {
      let letterName = null;
      if (filePondInstance && typeof filePondInstance.getFiles === 'function') {
        const files = filePondInstance.getFiles();
        if (files.length > 0) {
          letterName = files[0].file?.name || null;
        }
      }
      if (!letterName && letterInput?.files?.length) {
        letterName = letterInput.files[0].name;
      }
      summaryEls.letter.textContent = letterName || 'No letter uploaded.';
    }
  };

  const getTodayIsoDate = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const getRoundedNowTime = () => {
    const now = new Date();
    now.setSeconds(0, 0);
    const remainder = now.getMinutes() % 5;
    if (remainder !== 0) {
      now.setMinutes(now.getMinutes() + (5 - remainder));
    }
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
  };

  const applyScheduleConstraints = () => {
    if (!startDateInput || !endDateInput) return;

    const today = getTodayIsoDate();
    const roundedNow = getRoundedNowTime();

    startDateInput.setAttribute('min', today);
    if (startDateInput.value && startDateInput.value < today) {
      startDateInput.value = today;
    }

    const startDateValue = startDateInput.value || '';
    const minEndDate = startDateValue || today;
    endDateInput.setAttribute('min', minEndDate);
    if (endDateInput.value && endDateInput.value < minEndDate) {
      endDateInput.value = minEndDate;
    }

    if (startTimeInput) {
      startTimeInput.max = '23:59';
      if (startDateValue && startDateValue === today) {
        startTimeInput.min = roundedNow;
        if (startTimeInput.value && startTimeInput.value < roundedNow) {
          startTimeInput.value = roundedNow;
        }
      } else {
        startTimeInput.removeAttribute('min');
      }
    }

    if (endTimeInput) {
      endTimeInput.max = '23:59';
      const endDateValue = endDateInput.value || '';
      let endMin = '';
      if (endDateValue && startDateValue && endDateValue === startDateValue) {
        endMin = startTimeInput?.value || (startDateValue === today ? roundedNow : '');
      } else if (endDateValue && endDateValue === today) {
        endMin = roundedNow;
      }

      if (endMin) {
        endTimeInput.min = endMin;
        if (endTimeInput.value && endTimeInput.value < endMin) {
          endTimeInput.value = endMin;
        }
      } else {
        endTimeInput.removeAttribute('min');
      }
    }

    refreshSummary();
  };

  const bindSummaryListener = (el) => {
    if (!el) return;
    ['input', 'change'].forEach((eventName) => el.addEventListener(eventName, refreshSummary));
  };

  [
    purposeInput,
    officeInput,
    municipalitySelect,
    barangaySelect,
    locationInput,
    startDateInput,
    startTimeInput,
    endDateInput,
    endTimeInput,
  ].forEach(bindSummaryListener);

  [startDateInput, endDateInput, startTimeInput, endTimeInput].forEach((input) => {
    input?.addEventListener('change', applyScheduleConstraints);
  });
  startTimeInput?.addEventListener('input', applyScheduleConstraints);
  endTimeInput?.addEventListener('input', applyScheduleConstraints);

  letterInput?.addEventListener('change', refreshSummary);

  addRoleBtn?.addEventListener('click', () => {
    if (!manpowerRowsContainer || !ROLE_OPTIONS.length) return;
    manpowerRowsContainer.appendChild(buildManpowerRow('', 1));
    syncRoleOptions();
    refreshSummary();
  });

  const updateIndicator = () => {
    if (!wizardIndicatorItems.length) return;
    wizardIndicatorItems.forEach((item) => {
      const idx = Number(item.dataset.stepIndex);
      const badge = item.querySelector('[data-step-badge]');
      const state = idx < currentStep ? 'complete' : (idx === currentStep ? 'active' : 'default');
      INDICATOR_RESET_CLASSES.forEach((cls) => item.classList.remove(cls));
      if (badge) {
        INDICATOR_BADGE_RESET_CLASSES.forEach((cls) => badge.classList.remove(cls));
      }
      INDICATOR_CLASS_STATES[state]?.container.forEach((cls) => item.classList.add(cls));
      if (badge) {
        INDICATOR_CLASS_STATES[state]?.badge.forEach((cls) => badge.classList.add(cls));
      }
    });
  };

  const goToStep = (step) => {
    if (!wizardSections.length) {
      if (stepLabelEl) {
        const label = STEP_LABELS[currentStep] || '';
        stepLabelEl.textContent = label ? `Step ${currentStep} of ${TOTAL_STEPS}: ${label}` : `Step ${currentStep} of ${TOTAL_STEPS}`;
      }
      return;
    }
    currentStep = Math.max(1, Math.min(step, TOTAL_STEPS));
    wizardSections.forEach((section) => {
      const idx = Number(section.dataset.mpStep);
      section.classList.toggle('hidden', idx !== currentStep);
    });
    wizardPrevButton?.classList.toggle('hidden', currentStep === 1);
    wizardNextButton?.classList.toggle('hidden', currentStep === TOTAL_STEPS);
    saveBtn?.classList.toggle('hidden', currentStep !== TOTAL_STEPS);
    if (stepLabelEl) {
      const label = STEP_LABELS[currentStep] || '';
      stepLabelEl.textContent = label ? `Step ${currentStep} of ${TOTAL_STEPS}: ${label}` : `Step ${currentStep} of ${TOTAL_STEPS}`;
    }
    updateIndicator();
    refreshSummary();
  };

  const validateStepOne = () => {
    const collected = collectManpowerRows();
    if (!collected.ok) {
      window.showToast?.(collected.message, 'warning');
      return false;
    }
    const totalQuantity = collected.rows.reduce((acc, row) => acc + (Number.isInteger(row.quantity) ? row.quantity : 0), 0);
    if (totalQuantity < 1) {
      window.showToast?.('Quantity must be at least 1', 'warning');
      return false;
    }
    if (totalQuantity > 99) {
      window.showToast?.('Maximum of 99 personnel only', 'warning');
      return false;
    }
    const purposeValue = (purposeInput?.value || '').trim();
    if (!purposeValue) {
      window.showToast?.('Purpose is required', 'warning');
      purposeInput?.focus();
      return false;
    }
    return true;
  };

  const validateStepTwo = () => {
    if (!municipalitySelect?.value) {
      window.showToast?.('Please select a municipality/city', 'warning');
      municipalitySelect?.focus();
      return false;
    }
    if (!barangaySelect?.value) {
      window.showToast?.('Please select a barangay', 'warning');
      barangaySelect?.focus();
      return false;
    }
    const locationValue = (locationInput?.value || '').trim();
    if (!locationValue) {
      window.showToast?.('Specific area is required', 'warning');
      locationInput?.focus();
      return false;
    }
    const startDateValue = startDateInput?.value || '';
    const endDateValue = endDateInput?.value || '';
    if (!startDateValue) {
      window.showToast?.('Start date is required', 'warning');
      startDateInput?.focus();
      return false;
    }
    if (!endDateValue) {
      window.showToast?.('End date is required', 'warning');
      endDateInput?.focus();
      return false;
    }
    const startAtValue = buildDateTimeString(startDateValue, startTimeInput?.value || '00:00', '00:00');
    const endAtValue = buildDateTimeString(endDateValue, endTimeInput?.value || '23:59', '23:59');
    if (startAtValue && endAtValue && new Date(endAtValue) < new Date(startAtValue)) {
      window.showToast?.('End schedule must be on or after the start schedule.', 'warning');
      endDateInput?.focus();
      return false;
    }
    return true;
  };

  const validateStep = (step) => {
    if (step === 1) return validateStepOne();
    if (step === 2) return validateStepTwo();
    return true;
  };

  const appendEmptyStateRow = (fallback = 'No requests found') => {
    const template = document.getElementById('user-manpower-empty-state-template');
    tbody.innerHTML = '';
    if (template?.content?.firstElementChild) {
      tbody.appendChild(template.content.firstElementChild.cloneNode(true));
    } else {
      tbody.innerHTML = `<tr><td colspan="6" class="py-10 text-center text-gray-500">${fallback}</td></tr>`;
    }
  };

  const render = () => {
    let rows = CACHE;
    const term = (search?.value || '').toLowerCase().trim();
    if (term) {
      rows = rows.filter(r => {
        const roleMatch = (formatRoleList(r.role_breakdown) || r.role || '').toLowerCase().includes(term);
        const purposeMatch = (r.purpose || '').toLowerCase().includes(term);
        const codeMatch = (formatRequestCode(r) || '').toLowerCase().includes(term);
        return roleMatch || purposeMatch || codeMatch;
      });
    }
    if (!rows.length) {
      appendEmptyStateRow();
      return;
    }
    tbody.innerHTML = rows.map(r => {
      const requestCode = formatRequestCode(r);
      const borrowDate = formatDateDisplay(r.start_at);
      const returnDate = formatDateDisplay(r.end_at);
      const printTemplate = window.USER_MANPOWER?.print || '';
      const printUrl = printTemplate ? printTemplate.replace('__ID__', r.id) : '#';
      const status = String(r.status || '').toLowerCase();
      const showPrint = status === 'validated' || status === 'approved';
      const actionButtons = [`<button data-action='view' class='btn-action btn-view h-10 w-10' title='View'>
                  <span class='sr-only'>View</span>
                  ${ICONS.eye}
                </button>`];
      if (showPrint) {
        actionButtons.push(`<a href='${printUrl}' target='_blank' rel='noopener' class='btn-action btn-print h-10 w-10' title='Print'>
                  <span class='sr-only'>Print</span>
                  ${ICONS.printer}
                </a>`);
      }
      const actionsHtml = actionButtons.join('');
      return `<tr data-request-id='${r.id}'>
        <td class='px-6 py-3 font-semibold'>${requestCode || `#${r.id}`}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${borrowDate}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${returnDate}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>
            <div class='flex items-center justify-center gap-2'>
                ${actionsHtml}
            </div>
        </td>
      </tr>`;
    }).join('');
  };

  const buildDateTimeString = (dateValue, timeValue, fallback) => {
    if (!dateValue) return null;
    const time = timeValue || fallback;
    return `${dateValue}T${time}:00`;
  };

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const buildExpandableRoleHtml = (row) => {
    const breakdown = Array.isArray(row.role_breakdown) ? row.role_breakdown : [];
    const summary = formatRoleList(breakdown);
    if (summary && summary !== '—') return escapeHtml(summary);
    return escapeHtml(String(row.role || '').trim()) || '—';
  };

  const collectFormData = () => {
    const collected = collectManpowerRows();
    if (!collected.ok) return { ok:false, message: collected.message };
    const roles = collected.rows.map((row) => ({ manpower_role_id: row.roleId, quantity: row.quantity, role_name: row.label }));
    const quantity = collected.rows.reduce((acc, row) => acc + (Number.isInteger(row.quantity) ? row.quantity : 0), 0);
    const purpose = (purposeInput?.value || '').trim();
    const location = (locationInput?.value || '').trim();
    const office_agency = (officeInput?.value || '').trim();
    const municipality_id = (municipalitySelect?.value || '').trim();
    const barangay_id = (barangaySelect?.value || '').trim();
    const start_date = startDateInput?.value || '';
    const end_date = endDateInput?.value || '';
    const start_time = startTimeInput?.value || '';
    const end_time = endTimeInput?.value || '';

    if (!Number.isInteger(quantity) || quantity < 1) return { ok:false, message:'Quantity must be at least 1' };
    if (quantity > 99) return { ok:false, message:'Maximum of 99 personnel only' };
    if (!municipality_id) return { ok:false, message:'Please select a municipality/city' };
    if (!barangay_id) return { ok:false, message:'Please select a barangay' };
    if (!location) return { ok:false, message:'Specific area is required' };
    if (!purpose) return { ok:false, message:'Purpose is required' };
    if (!start_date) return { ok:false, message:'Start date is required' };
    if (!end_date) return { ok:false, message:'End date is required' };

    const startAtValue = buildDateTimeString(start_date, start_time || '00:00', '00:00');
    const endAtValue = buildDateTimeString(end_date, end_time || '23:59', '23:59');

    if (new Date(endAtValue) < new Date(startAtValue)) {
      return { ok:false, message:'End schedule must be on or after the start schedule.' };
    }

    return {
      ok: true,
      data: {
        quantity,
        manpower_roles: JSON.stringify(roles),
        has_multiple_roles: roles.length > 1 ? 1 : 0,
        purpose,
        location,
        office_agency,
        municipality_id,
        barangay_id,
        start_date,
        start_time,
        end_date,
        end_time,
      }
    };
  };

  wizardNextButton?.addEventListener('click', () => {
    if (!validateStep(currentStep)) return;
    goToStep(currentStep + 1);
  });

  wizardPrevButton?.addEventListener('click', () => {
    goToStep(currentStep - 1);
  });

  openBtn?.addEventListener('click', () => {
    form?.reset();
    initFilePond();
    if (filePondInstance && typeof filePondInstance.removeFiles === 'function') {
      filePondInstance.removeFiles();
    } else if (letterInput) {
      letterInput.value = '';
    }
    PENDING_PAYLOAD = null;
    fetchRoles();
    loadMunicipalities();
    if (barangaySelect) {
      barangaySelect.innerHTML = `<option value="">Select barangay</option>`;
      barangaySelect.disabled = true;
    }
    if (locationPreview) locationPreview.textContent = '—';
    if (schedulePreview) schedulePreview.textContent = '—';
    applyScheduleConstraints();
    goToStep(1);
    openModal('userManpowerCreateModal');
  });

  // FilePond initialization (if present)
  const initFilePond = () => {
    if (!letterInput || !window.FilePond) return;
    try {
      if (typeof FilePond.registerPlugin === 'function' && window.FilePondPluginFileValidateType && window.FilePondPluginFileValidateSize) {
        FilePond.registerPlugin(window.FilePondPluginFileValidateType, window.FilePondPluginFileValidateSize);
      }
    } catch (pluginError) {
      console.warn('FilePond plugin registration failed', pluginError);
    }

    const pondOptions = {
      acceptedFileTypes: ['application/pdf', 'image/png', 'image/jpeg'],
      maxFileSize: '5MB',
      allowMultiple: false,
      name: 'letter_file',
      credits: false,
      labelIdle: 'Drag & Drop your letter or <span class="filepond--label-action">Browse</span>',
      onaddfile: refreshSummary,
      onremovefile: refreshSummary,
      onupdatefiles: refreshSummary,
    };

    filePondInstance = FilePond.find(letterInput) || FilePond.create(letterInput, pondOptions);
    if (filePondInstance?.setOptions) {
      filePondInstance.setOptions(pondOptions);
    }
    refreshSummary();
  };

  initFilePond();
  goToStep(currentStep);

  saveBtn?.addEventListener('click', () => {
    if (currentStep !== TOTAL_STEPS) {
      goToStep(TOTAL_STEPS);
      return;
    }
    const v = collectFormData();
    if (!v.ok) {
      window.showToast?.(v.message, 'warning');
      return;
    }
    PENDING_PAYLOAD = v.data;
    openModal('userManpowerConfirmModal');
  });

  confirmBtn?.addEventListener('click', async () => {
    if (!PENDING_PAYLOAD) return;
    try {
      const fd = new FormData();
      Object.entries(PENDING_PAYLOAD).forEach(([k,v]) => fd.append(k, v ?? ''));
      // FilePond file (if present)
      if (filePondInstance && filePondInstance.getFiles().length > 0) {
        const fileObj = filePondInstance.getFiles()[0].file;
        fd.append('letter_file', fileObj);
      }
      fd.append('_token', window.USER_MANPOWER.csrf);
      const res = await fetch(window.USER_MANPOWER.store, { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      closeModal('userManpowerConfirmModal');
      closeModal('userManpowerCreateModal');
      window.showToast?.(data.message || 'Your manpower request has been submitted.', 'success');
      await fetchRows();
      PENDING_PAYLOAD = null;
    } catch(e) {
      window.showToast?.(e.message || 'Failed to submit manpower request.', 'error');
    }
  });

  tbody?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="view"]');
    if (!btn) return;
    const tr = btn.closest('tr[data-request-id]');
    if (!tr) return;
    const id = tr.dataset.requestId;
    const row = CACHE.find(r => String(r.id) === String(id));
    if (!row) return;
    hydrateViewModal(row);
    openModal('userManpowerViewModal');
  });

  const hydrateViewModal = (row) => {
    const formatAssignedNames = (names) => {
      if (!Array.isArray(names)) return '—';
      const normalized = names
        .map((name) => String(name || '').trim())
        .filter(Boolean);
      return normalized.length ? normalized.join(', ') : '—';
    };

    userViewFields.forEach(el => {
      const key = el.dataset.userView;
      if (key === 'quantity') {
        const approved = row.approved_quantity != null ? row.approved_quantity : '—';
        el.textContent = `${approved} / ${row.quantity}`;
      } else if (key === 'borrow_date') {
        el.textContent = formatDateDisplay(row.start_at);
      } else if (key === 'return_date') {
        el.textContent = formatDateDisplay(row.end_at);
      } else if (key === 'status') {
        el.innerHTML = badgeHtml(row.status);
      } else if (key === 'id') {
        el.textContent = formatRequestCode(row) || `#${row.id}`;
      } else if (key === 'location') {
        el.textContent = row.location || '—';
      } else if (key === 'municipality') {
        el.textContent = row.municipality || '—';
      } else if (key === 'barangay') {
        el.textContent = row.barangay || '—';
      } else if (key === 'role') {
        // render expandable summary for role
        el.innerHTML = buildExpandableRoleHtml(row, 2);
      } else if (key === 'assigned_personnel_names') {
        el.textContent = formatAssignedNames(row.assigned_personnel_names);
      } else {
        el.textContent = row[key] || '—';
      }
    });

    if (row.public_url) {
      qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(row.public_url)}" alt="Request status QR code" class="w-40 h-40 object-contain" />`;
      const link = document.querySelector('[data-user-view="public-url"]');
      if (link) {
        link.href = row.public_url;
        link.classList.remove('hidden');
      }
    } else {
      qrContainer.innerHTML = `<div class="text-sm text-gray-400">QR code unavailable.</div>`;
      const link = document.querySelector('[data-user-view="public-url"]');
      if (link) {
        link.href = '#';
        link.classList.add('hidden');
      }
    }

    const hasRejection = Boolean((row.rejection_reason_subject || '').trim() || (row.rejection_reason_detail || '').trim());
    if (userRejectionCard) {
      if (hasRejection) {
        userRejectionCard.classList.remove('hidden');
        if (userRejectionSubject) {
          userRejectionSubject.textContent = row.rejection_reason_subject || '—';
        }
        if (userRejectionDetail) {
          userRejectionDetail.textContent = row.rejection_reason_detail || '—';
        }
      } else {
        userRejectionCard.classList.add('hidden');
        if (userRejectionSubject) userRejectionSubject.textContent = '—';
        if (userRejectionDetail) userRejectionDetail.textContent = '—';
      }
    }

    // role breakdown list not used in user view
  };

  search?.addEventListener('input', render);

  fetchRoles();
  loadMunicipalities();
  fetchRows();
  applyScheduleConstraints();
});
