// resources/js/user-manpower.js
document.addEventListener('DOMContentLoaded', () => {
  if (!window.USER_MANPOWER) return;
  const tbody = document.getElementById('userManpowerTableBody');
  const search = document.getElementById('user-manpower-search');
  const openBtn = document.getElementById('openManpowerCreate');
  const confirmBtn = document.getElementById('confirmManpowerSubmit');
  const saveBtn = document.getElementById('saveManpowerRequest');
  const form = document.getElementById('userManpowerForm');
  // FilePond instance for letter upload
  let filePondInstance = null;
  const roleSelect = document.getElementById('mp_role');
  const roleEmptyMessage = document.getElementById('mp_role_empty');
  const municipalitySelect = document.getElementById('mp_municipality');
  const barangaySelect = document.getElementById('mp_barangay');
  const startDateInput = document.getElementById('mp_start_date');
  const startTimeInput = document.getElementById('mp_start_time');
  const endDateInput = document.getElementById('mp_end_date');
  const endTimeInput = document.getElementById('mp_end_time');
  const userViewFields = document.querySelectorAll('[data-user-view]');
  const qrContainer = document.getElementById('userManpowerQr');
  const ICONS = {
    eye: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    printer: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v8H6z" /></svg>'
  };
  let CACHE = [];
  let PENDING_PAYLOAD = null;
  const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];

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
      tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-red-600">Failed to load.</td></tr>`;
    }
  };

  const fetchRoles = async () => {
    if (!roleSelect) return;
    roleSelect.disabled = true;
    roleSelect.innerHTML = `<option value="">Loading roles...</option>`;
    try {
      const res = await fetch(window.USER_MANPOWER.roles, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const data = await res.json();
      const roles = Array.isArray(data) ? data : [];
      if (!roles.length) {
        roleSelect.innerHTML = `<option value="">No roles available</option>`;
        roleSelect.disabled = true;
        roleEmptyMessage?.classList.remove('hidden');
        return;
      }
      roleEmptyMessage?.classList.add('hidden');
      roleSelect.disabled = false;
      roleSelect.innerHTML = [
        `<option value="">Select a role</option>`,
        ...roles.map(role => `<option value="${role.id}">${role.name}</option>`)
      ].join('');
    } catch (e) {
      console.error(e);
      roleSelect.innerHTML = `<option value="">Failed to load roles</option>`;
    }
  };

  const loadMunicipalities = async () => {
    if (!municipalitySelect) return;
    municipalitySelect.disabled = true;
    municipalitySelect.innerHTML = `<option value="">Loading municipalities...</option>`;
    try {
      const res = await fetch(window.USER_MANPOWER.locations.municipalities, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const json = await res.json();
      const municipalities = Array.isArray(json?.data) ? json.data : [];
      municipalitySelect.innerHTML = [
        `<option value="">Select municipality</option>`,
        ...municipalities.map(m => `<option value="${m.id}">${m.name}</option>`)
      ].join('');
      municipalitySelect.disabled = false;
      barangaySelect.innerHTML = `<option value="">Select barangay</option>`;
      barangaySelect.disabled = true;
    } catch (e) {
      console.error(e);
      municipalitySelect.innerHTML = `<option value="">Failed to load municipalities</option>`;
    }
  };

  const loadBarangays = async (municipalityId) => {
    if (!barangaySelect) return;
    if (!municipalityId) {
      barangaySelect.innerHTML = `<option value="">Select barangay</option>`;
      barangaySelect.disabled = true;
      return;
    }
    barangaySelect.disabled = true;
    barangaySelect.innerHTML = `<option value="">Loading barangays...</option>`;
    try {
      const url = `${window.USER_MANPOWER.locations.barangays}/${municipalityId}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const json = await res.json();
      const barangays = Array.isArray(json?.data) ? json.data : [];
      barangaySelect.innerHTML = [
        `<option value="">Select barangay</option>`,
        ...barangays.map(b => `<option value="${b.id}">${b.name}</option>`)
      ].join('');
      barangaySelect.disabled = false;
    } catch (e) {
      console.error(e);
      barangaySelect.innerHTML = `<option value="">Failed to load barangays</option>`;
    }
  };

  municipalitySelect?.addEventListener('change', (e) => loadBarangays(e.target.value));

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

  const appendEmptyStateRow = (fallback = 'No requests found') => {
    const template = document.getElementById('user-manpower-empty-state-template');
    tbody.innerHTML = '';
    if (template?.content?.firstElementChild) {
      tbody.appendChild(template.content.firstElementChild.cloneNode(true));
    } else {
      tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-gray-500">${fallback}</td></tr>`;
    }
  };

  const render = () => {
    let rows = CACHE;
    const term = (search?.value || '').toLowerCase().trim();
    if (term) {
      rows = rows.filter(r => {
        const roleMatch = (r.role || '').toLowerCase().includes(term);
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
      const approved = r.approved_quantity != null ? r.approved_quantity : '—';
      const printTemplate = window.USER_MANPOWER?.print || '';
      const printUrl = printTemplate ? printTemplate.replace('__ID__', r.id) : '#';
      return `<tr data-request-id='${r.id}'>
        <td class='px-6 py-3 font-semibold'>${requestCode || `#${r.id}`}</td>
        <td class='px-6 py-3'>${approved} / ${r.quantity}</td>
        <td class='px-6 py-3'>${r.role || '—'}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${borrowDate}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${returnDate}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>
            <div class='flex items-center justify-center gap-2'>
                <button data-action='view' class='btn-action btn-view h-10 w-10' title='View'>
                  <span class='sr-only'>View</span>
                  ${ICONS.eye}
                </button>
                <a href='${printUrl}' target='_blank' rel='noopener' class='btn-action btn-print h-10 w-10' title='Print'>
                  <span class='sr-only'>Print</span>
                  ${ICONS.printer}
                </a>
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

  const collectFormData = () => {
    const quantity = parseInt(document.getElementById('mp_quantity').value, 10);
    const manpower_role_id = roleSelect.value.trim();
    const purpose = document.getElementById('mp_purpose').value.trim();
    const location = document.getElementById('mp_location').value.trim();
    const office_agency = document.getElementById('mp_office').value.trim();
    const municipality_id = municipalitySelect.value.trim();
    const barangay_id = barangaySelect.value.trim();
    const start_date = startDateInput.value;
    const end_date = endDateInput.value;
    const start_time = startTimeInput.value;
    const end_time = endTimeInput.value;

    if (!Number.isInteger(quantity) || quantity < 1) return { ok:false, message:'Quantity must be at least 1' };
    if (quantity > 999) return { ok:false, message:'Maximum of 999 personnel only' };
    if (!manpower_role_id) return { ok:false, message:'Please select a manpower role' };
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
        manpower_role_id,
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

  openBtn?.addEventListener('click', () => {
    form?.reset();
    if (filePondInstance && typeof filePondInstance.removeFiles === 'function') filePondInstance.removeFiles();
    PENDING_PAYLOAD = null;
    fetchRoles();
    loadMunicipalities();
    openModal('userManpowerCreateModal');
  });

  // FilePond initialization (if present)
  if (window.FilePond && document.getElementById('mp_letter')) {
    filePondInstance = FilePond.create(document.getElementById('mp_letter'), {
      acceptedFileTypes: ['application/pdf', 'image/png', 'image/jpeg'],
      maxFileSize: '5MB',
      allowMultiple: false,
      name: 'letter_file',
      credits: false
    });
  }

  saveBtn?.addEventListener('click', () => {
    const v = collectFormData();
    if (!v.ok) {
      window.showToast('warning', v.message);
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
      await fetchRows();
    } catch(e) {
      window.showToast('error', e.message || 'Failed to submit manpower request.');
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
    userViewFields.forEach(el => {
      const key = el.dataset.userView;
      if (key === 'quantity') {
        const approved = row.approved_quantity != null ? row.approved_quantity : '—';
        el.textContent = `${approved} / ${row.quantity}`;
      } else if (key === 'borrow_date') {
        el.textContent = formatDateDisplay(row.start_at);
      } else if (key === 'return_date') {
        el.textContent = formatDateDisplay(row.end_at);
      } else if (key === 'letter') {
        if (row.letter_url) {
          el.innerHTML = `<a href='${row.letter_url}' target='_blank' class='text-indigo-600 hover:underline'>Open uploaded letter</a>`;
        } else {
          el.textContent = 'No letter uploaded.';
        }
      } else if (key === 'status') {
        el.textContent = row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : '—';
      } else if (key === 'id') {
        el.textContent = formatRequestCode(row) || `#${row.id}`;
      } else if (key === 'location') {
        el.textContent = row.location || '—';
      } else if (key === 'municipality') {
        el.textContent = row.municipality || '—';
      } else if (key === 'barangay') {
        el.textContent = row.barangay || '—';
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
  };

  search?.addEventListener('input', render);

  fetchRoles();
  loadMunicipalities();
  fetchRows();
});
