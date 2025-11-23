// resources/js/admin-manpower-requests.js
document.addEventListener('DOMContentLoaded', () => {
  if (!window.ADMIN_MANPOWER) return;
  const tbody = document.getElementById('adminManpowerTableBody');
  const search = document.getElementById('admin-manpower-search');
  const statusFilter = document.getElementById('admin-manpower-status');
  const manageRolesBtn = document.getElementById('openManageRoles');
  const rolesTableBody = document.getElementById('adminRolesTableBody');
  const saveRoleBtn = document.getElementById('adminSaveRole');
  const roleNameInput = document.getElementById('adminRoleName');
  const approveFields = document.querySelectorAll('[data-approve-field]');
  const viewFields = document.querySelectorAll('[data-view-field]');
  const approvedQuantityInput = document.getElementById('adminApprovedQuantity');
  const confirmApproveBtn = document.getElementById('confirmAdminApproval');
  const ICONS = {
    check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>',
    xMark: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>',
    eye: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    trash: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21a48.11 48.11 0 00-3.478-.397M7.53 5.79a48.108 48.108 0 00-3.4.273M15 5.25V4.5A1.5 1.5 0 0013.5 3h-3A1.5 1.5 0 009 4.5v.75m7.5 0a48.667 48.667 0 013.468.34M9 5.25a48.667 48.667 0 00-3.468.34M4.5 6.75h15" /></svg>'
  };
  let CACHE = [];
  let ACTIVE_REQUEST = null;

  const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];

  const formatRequestCode = (row) => {
    if (!row) return '';
    const formatted = typeof row.formatted_request_id === 'string' ? row.formatted_request_id.trim() : '';
    if (formatted) return formatted;
    const rawId = row.id ?? null;
    if (!rawId) return '';
    return `MP-${String(rawId).padStart(4, '0')}`;
  };

  const fetchRows = async () => {
    try {
      const params = new URLSearchParams();
      const q = search.value.trim();
      if (q) params.set('q', q);
      const sv = statusFilter.value.trim();
      if (sv) params.set('status', sv);
      const res = await fetch(`${window.ADMIN_MANPOWER.list}?${params.toString()}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      CACHE = Array.isArray(data) ? data : [];
      render();
    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-red-600">Failed to load.</td></tr>`;
    }
  };

  const pickRow = (id) => CACHE.find(r => String(r.id) === String(id));

  const formatDate = (value) => {
    if (!value) return null;
    const safe = value.includes('T') ? value : value.replace(' ', 'T');
    const date = new Date(safe);
    if (Number.isNaN(date.getTime())) return null;
    const month = SHORT_MONTHS[date.getMonth()] || '';
    const day = date.getDate();
    const year = date.getFullYear();
    if (!month || !day || !year) return null;
    return `${month} ${day}, ${year}`;
  };

  const formatDateDisplay = (value) => formatDate(value) || '—';

  const badgeHtml = (status) => {
    status = (status||'').toLowerCase();
    if (status === 'pending') return document.getElementById('badge-status-pending')?.innerHTML || status;
    if (status === 'validated') return document.getElementById('badge-status-validated')?.innerHTML || status;
    if (status === 'approved') return document.getElementById('badge-status-approved')?.innerHTML || status;
    if (status === 'rejected') return document.getElementById('badge-status-rejected')?.innerHTML || status;
    return status || '—';
  };

  const escapeAttr = (value) => String(value ?? '').replace(/["'&<>]/g, (char) => {
    switch (char) {
      case '"': return '&quot;';
      case "'": return '&#39;';
      case '&': return '&amp;';
      case '<': return '&lt;';
      case '>': return '&gt;';
      default: return char;
    }
  });

  const buildLetterPreview = (url) => {
    const noLetter = `<div class="inline-flex items-center gap-2 text-sm text-gray-500"><i class="fas fa-file-circle-xmark text-lg"></i><span>No letter uploaded.</span></div>`;
    if (!url) {
      return noLetter;
    }

    const safeUrl = escapeAttr(url);
    const base = safeUrl.split('?')[0].toLowerCase();
    const header = `<div class="inline-flex items-center gap-2 text-sm font-semibold text-sky-700"><i class="fas fa-file-alt text-base"></i><span>Uploaded Letter</span></div>`;
    const linkHtml = `<a href='${safeUrl}' target='_blank' rel='noopener' class='inline-flex items-center gap-2 text-xs font-medium text-sky-600 hover:text-sky-700 transition-colors'><i class="fas fa-arrow-up-right-from-square text-[0.7rem]"></i><span>Open uploaded letter</span></a>`;

    if (/(\.png|\.jpe?g|\.gif|\.webp|\.bmp|\.svg)$/.test(base)) {
      return `<div class='space-y-2'>
        ${header}
        <figure class='rounded-lg border border-sky-100 bg-sky-50/60 p-3 shadow-sm'>
          <img src='${safeUrl}' alt='Uploaded letter preview' loading='lazy' class='w-full max-h-72 object-contain rounded-md' />
        </figure>
        <div>${linkHtml}</div>
      </div>`;
    }

    if (/\.pdf$/.test(base)) {
      return `<div class='space-y-2'>
        ${header}
        <div class='rounded-lg border border-sky-100 bg-sky-50/60 shadow-sm overflow-hidden'>
          <iframe src='${safeUrl}' title='Uploaded letter preview' class='w-full h-72'></iframe>
        </div>
        <div>${linkHtml}</div>
      </div>`;
    }

    return `<div class='space-y-2'>${header}<div>${linkHtml}</div></div>`;
  };

  const render = () => {
    if (!CACHE.length) {
      const template = document.getElementById('admin-manpower-empty-state-template');
      tbody.innerHTML = '';
      if (template?.content?.firstElementChild) {
        tbody.appendChild(template.content.firstElementChild.cloneNode(true));
      } else {
        tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-gray-500">No manpower requests found</td></tr>`;
      }
      return;
    }
    tbody.innerHTML = CACHE.map(r => {
      const requestCode = formatRequestCode(r);
      const borrowDate = formatDateDisplay(r.start_at);
      const returnDate = formatDateDisplay(r.end_at);
      return `<tr data-manpower-id='${r.id}'>
        <td class='px-6 py-3 font-semibold text-gray-900'>${requestCode || ''}</td>
        <td class='px-6 py-3'>${r.user ? r.user.name : '—'}</td>
        <td class='px-6 py-3'>${r.role || r.role_type || '—'}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${borrowDate}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${returnDate}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>${actionButtons(r)}</td>
      </tr>`;
    }).join('');
  };

  const actionButtons = (r) => {
    const status = String(r.status || '').toLowerCase();
    if (status === 'pending') {
      return `<div class="flex items-center justify-center gap-2">
        <button data-action="approve" class="btn-action btn-accept h-10 w-10" title="Validate">
          <span class="sr-only">Validate</span>
          ${ICONS.check}
        </button>
        <button data-action="reject" class="btn-action btn-reject h-10 w-10" title="Reject">
          <span class="sr-only">Reject</span>
          ${ICONS.xMark}
        </button>
      </div>`;
    }
    if (status === 'validated') {
      return `<span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border border-sky-200 bg-sky-50 text-sky-700"><i class="fas fa-clock text-[0.7rem]"></i><span>Pending Submission</span></span>`;
    }
    return `<div class="flex items-center justify-center gap-2">
      <button data-action="view" class="btn-action btn-view h-10 w-10" title="View">
        <span class="sr-only">View</span>
        ${ICONS.eye}
      </button>
    </div>`;
  };

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const tr = btn.closest('tr[data-manpower-id]');
    if (!tr) return;
    const id = tr.dataset.manpowerId;
    const action = btn.dataset.action;
    const row = pickRow(id);
    if (!row) return;

    if (action === 'approve') {
      ACTIVE_REQUEST = row;
      hydrateApproveModal(row);
      openModal('adminManpowerApproveModal');
    } else if (action === 'reject') {
      confirmReject(row);
    } else if (action === 'view') {
      hydrateViewModal(row);
      openModal('adminManpowerViewModal');
    }
  });

  const openModal = (name) => window.dispatchEvent(new CustomEvent('open-modal', {detail: name}));
  const closeModal = (name) => window.dispatchEvent(new CustomEvent('close-modal', {detail: name}));

  const hydrateApproveModal = (row) => {
    approvedQuantityInput.value = row.quantity;
    approveFields.forEach(el => {
      const key = el.dataset.approveField;
      if (key === 'borrow_date') {
        el.textContent = formatDateDisplay(row.start_at);
      } else if (key === 'return_date') {
        el.textContent = formatDateDisplay(row.end_at);
      } else if (key === 'letter') {
        el.innerHTML = buildLetterPreview(row.letter_url);
      } else if (key === 'user') {
        el.textContent = row.user?.name || '—';
      } else if (key === 'quantity') {
        el.textContent = row.quantity;
      } else {
        el.textContent = row[key] || '—';
      }
    });
  };

  const hydrateViewModal = (row) => {
    viewFields.forEach(el => {
      const key = el.dataset.viewField;
      if (key === 'borrow_date') {
        el.textContent = formatDateDisplay(row.start_at);
      } else if (key === 'return_date') {
        el.textContent = formatDateDisplay(row.end_at);
      } else if (key === 'quantity') {
        const approved = row.approved_quantity ? `${row.approved_quantity} / ` : '';
        el.textContent = `${approved}${row.quantity}`;
      } else if (key === 'letter') {
        el.innerHTML = buildLetterPreview(row.letter_url);
      } else if (key === 'user') {
        el.textContent = row.user?.name || '—';
      } else if (key === 'status') {
        el.textContent = row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : '—';
      } else {
        el.textContent = row[key] || '—';
      }
    });
  };

  const confirmReject = async (row) => {
    if (!window.confirm('Reject this request?')) return;
    await updateStatus(row.id, 'rejected');
  };

  confirmApproveBtn?.addEventListener('click', async () => {
    if (!ACTIVE_REQUEST) return;
    const qty = parseInt(approvedQuantityInput.value, 10);
    if (!qty || qty < 1) {
      window.showToast?.('warning', 'Approved quantity must be at least 1.');
      return;
    }
    if (qty > ACTIVE_REQUEST.quantity) {
      window.showToast?.('warning', 'Approved quantity cannot exceed requested quantity.');
      return;
    }
    await updateStatus(ACTIVE_REQUEST.id, 'validated', {approved_quantity: qty});
    closeModal('adminManpowerApproveModal');
    ACTIVE_REQUEST = null;
  });

  const updateStatus = async (id, status, extra = {}) => {
    try {
      const res = await fetch(window.ADMIN_MANPOWER.status(id), {
        method: 'POST',
        headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.ADMIN_MANPOWER.csrf,'Content-Type':'application/json'},
        body: JSON.stringify({status, ...extra})
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      window.showToast?.('success', `Request ${status}.`);
      await fetchRows();
    } catch (e) { 
      console.error(e); 
      window.showToast?.('error', e.message || 'Status update failed'); 
    }
  };

  // Manage roles
  const fetchRoles = async () => {
    if (!rolesTableBody) return;
    try {
      const res = await fetch(window.ADMIN_MANPOWER.roles.list, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      renderRoles(Array.isArray(data) ? data : []);
    } catch (e) {
      console.error(e);
      rolesTableBody.innerHTML = `<tr><td colspan="2" class="px-4 py-4 text-center text-red-500">Failed to load roles.</td></tr>`;
    }
  };

  const renderRoles = (roles) => {
    if (!roles.length) {
      rolesTableBody.innerHTML = `<tr><td colspan="2" class="px-4 py-4 text-center text-gray-500">No roles available.</td></tr>`;
      return;
    }
    rolesTableBody.innerHTML = roles.map(role => `
      <tr data-role-id='${role.id}'>
        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">${role.name}</td>
        <td class="px-4 py-3 text-center">
          <button data-role-delete class="btn-action btn-delete h-9 w-9" title="Remove role">
            <span class="sr-only">Remove role</span>
            ${ICONS.trash}
          </button>
        </td>
      </tr>
    `).join('');
  };

  rolesTableBody?.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-role-delete]');
    if (!btn) return;
    const tr = btn.closest('tr[data-role-id]');
    if (!tr) return;
    const id = tr.dataset.roleId;
    if (!window.confirm('Delete this role?')) return;
    try {
      const res = await fetch(window.ADMIN_MANPOWER.roles.delete(id), {
        method: 'DELETE',
        headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.ADMIN_MANPOWER.csrf}
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      window.showToast?.('success', 'Role deleted.');
      await fetchRoles();
    } catch (err) {
      console.error(err);
      window.showToast?.('error', err.message || 'Failed to delete role.');
    }
  });

  saveRoleBtn?.addEventListener('click', async () => {
    const name = roleNameInput.value.trim();
    if (!name) {
      window.showToast?.('warning', 'Role type is required.');
      return;
    }
    try {
      const res = await fetch(window.ADMIN_MANPOWER.roles.store, {
        method: 'POST',
        headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.ADMIN_MANPOWER.csrf,'Content-Type':'application/json'},
        body: JSON.stringify({name})
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      roleNameInput.value = '';
      window.showToast?.('success', 'Role added.');
      await fetchRoles();
    } catch (err) {
      console.error(err);
      window.showToast?.('error', err.message || 'Failed to save role.');
    }
  });

  manageRolesBtn?.addEventListener('click', () => {
    fetchRoles();
    openModal('adminManageRolesModal');
  });

  search?.addEventListener('input', () => { fetchRows(); });
  statusFilter?.addEventListener('change', () => { fetchRows(); });

  fetchRows();
});
