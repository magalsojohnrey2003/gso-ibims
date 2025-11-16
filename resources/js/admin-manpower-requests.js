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
  let CACHE = [];
  let ACTIVE_REQUEST = null;

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
      tbody.innerHTML = `<tr><td colspan="6" class="py-4 text-red-600">Failed to load.</td></tr>`;
    }
  };

  const pickRow = (id) => CACHE.find(r => String(r.id) === String(id));

  const formatDate = (value) => {
    if (!value) return null;
    const safe = value.includes('T') ? value : value.replace(' ', 'T');
    const date = new Date(safe);
    if (Number.isNaN(date.getTime())) return null;
    return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
  };

  const formatSchedule = (row) => {
    const start = formatDate(row.start_at);
    const end = formatDate(row.end_at);
    if (start && end) return `${start} - ${end}`;
    if (start) return start;
    return '—';
  };

  const badgeHtml = (status) => {
    status = (status||'').toLowerCase();
    if (status === 'pending') return document.getElementById('badge-status-pending')?.innerHTML || status;
    if (status === 'approved') return document.getElementById('badge-status-approved')?.innerHTML || status;
    if (status === 'rejected') return document.getElementById('badge-status-rejected')?.innerHTML || status;
    return status || '—';
  };

  const render = () => {
    if (!CACHE.length) {
      const template = document.getElementById('admin-manpower-empty-state-template');
      tbody.innerHTML = '';
      if (template?.content?.firstElementChild) {
        tbody.appendChild(template.content.firstElementChild.cloneNode(true));
      } else {
        tbody.innerHTML = `<tr><td colspan="6" class="py-10 text-center text-gray-500">No manpower requests found</td></tr>`;
      }
      return;
    }
    tbody.innerHTML = CACHE.map(r => {
      const schedule = formatSchedule(r);
      return `<tr data-manpower-id='${r.id}'>
        <td class='px-6 py-3'>${r.user ? r.user.name : '—'}</td>
        <td class='px-6 py-3'>${r.role || r.role_type || '—'}</td>
        <td class='px-6 py-3'>${r.quantity}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${schedule}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>${actionButtons(r)}</td>
      </tr>`;
    }).join('');
  };

  const actionButtons = (r) => {
    if (r.status === 'pending') {
      return `<div class='flex items-center justify-center gap-2'>
        <button data-action='approve' class='inline-flex items-center justify-center w-9 h-9 rounded-full bg-[#22C55E] text-white shadow-none' title='Approve'>
          <i class="fas fa-check"></i>
        </button>
        <button data-action='reject' class='inline-flex items-center justify-center w-9 h-9 rounded-full bg-[#EF4444] text-white shadow-none' title='Reject'>
          <i class="fas fa-times"></i>
        </button>
      </div>`;
    }
    return `<button data-action='view' class='inline-flex items-center justify-center w-9 h-9 rounded-full bg-[#6D28D9] text-white shadow-none' title='View'>
      <i class="fas fa-eye"></i>
    </button>`;
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
      if (key === 'schedule') {
        el.textContent = formatSchedule(row);
      } else if (key === 'letter') {
        if (row.letter_url) {
          el.innerHTML = `<a href='${row.letter_url}' target='_blank' class='text-indigo-600 hover:underline'>Open uploaded letter</a>`;
        } else {
          el.textContent = 'No letter uploaded.';
        }
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
      if (key === 'schedule') {
        el.textContent = formatSchedule(row);
      } else if (key === 'quantity') {
        const approved = row.approved_quantity ? `${row.approved_quantity} / ` : '';
        el.textContent = `${approved}${row.quantity}`;
      } else if (key === 'letter') {
        if (row.letter_url) {
          el.innerHTML = `<a href='${row.letter_url}' target='_blank' class='text-indigo-600 hover:underline'>Open uploaded letter</a>`;
        } else {
          el.textContent = 'No letter uploaded.';
        }
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
    await updateStatus(ACTIVE_REQUEST.id, 'approved', {approved_quantity: qty});
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
      <tr data-role-id='${role.id}' class="border-t">
        <td class="px-4 py-3 font-medium">${role.name}</td>
        <td class="px-4 py-3 text-center">
          <button data-role-delete class="text-red-500 hover:text-red-600"><i class="fas fa-trash"></i></button>
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
