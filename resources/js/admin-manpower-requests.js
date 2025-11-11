// resources/js/admin-manpower-requests.js
document.addEventListener('DOMContentLoaded', () => {
  if (!window.ADMIN_MANPOWER) return;
  const tbody = document.getElementById('adminManpowerTableBody');
  const search = document.getElementById('admin-manpower-search');
  const statusFilter = document.getElementById('admin-manpower-status');
  const rejectModalName = 'adminManpowerRejectModal';
  let REJECT_TARGET_ID = null;
  let CACHE = [];

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
      tbody.innerHTML = `<tr><td colspan="9" class="py-4 text-red-600">Failed to load.</td></tr>`;
    }
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
      tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-gray-500">No manpower requests found</td></tr>`;
      return;
    }
    tbody.innerHTML = CACHE.map(r => {
      const schedule = (r.start_at && r.end_at) ? `${r.start_at}<br class='hidden md:block'/>to ${r.end_at}` : '—';
      return `<tr data-manpower-id='${r.id}'>
        <td class='px-6 py-3'>${r.user ? r.user.name : '—'}</td>
        <td class='px-6 py-3'>${r.quantity}</td>
        <td class='px-6 py-3'>${r.role}</td>
        <td class='px-6 py-3 text-left'>${r.purpose}</td>
        <td class='px-6 py-3'>${r.office_agency || '—'}</td>
        <td class='px-6 py-3 text-xs'>${schedule}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>${r.letter_url ? `<a href='${r.letter_url}' target='_blank' class='text-indigo-600 hover:underline'>View</a>` : '—'}</td>
        <td class='px-6 py-3'>${actionButtons(r)}</td>
      </tr>`;
    }).join('');
  };

  const actionButtons = (r) => {
    if (r.status === 'pending') {
      return `<div class='flex items-center justify-center gap-2'>
        <button data-action='approve' class='h-9 w-9 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center'><i class='fas fa-check'></i></button>
        <button data-action='reject' class='h-9 w-9 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center'><i class='fas fa-times'></i></button>
      </div>`;
    }
    if (r.status === 'approved') {
      return `<span class='text-green-600 font-semibold text-xs'>Approved</span>`;
    }
    if (r.status === 'rejected') {
      return `<span class='text-red-600 font-semibold text-xs' title='${(r.rejection_reason_subject||'')}: ${(r.rejection_reason_detail||'')}' >Rejected</span>`;
    }
    return '—';
  };

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const tr = btn.closest('tr[data-manpower-id]');
    if (!tr) return;
    const id = tr.getAttribute('data-manpower-id');
    const action = btn.getAttribute('data-action');
    if (action === 'approve') {
      updateStatus(id, 'approved');
    } else if (action === 'reject') {
      REJECT_TARGET_ID = id;
      window.dispatchEvent(new CustomEvent('open-modal', {detail:'adminManpowerRejectModal'}));
      document.querySelector('[name="adminManpowerRejectModal"]');
    }
  });

  document.getElementById('confirmRejectBtn')?.addEventListener('click', () => {
    if (!REJECT_TARGET_ID) return;
    const subj = document.getElementById('rejectSubject').value.trim();
    const det = document.getElementById('rejectDetail').value.trim();
    if (!subj || !det) {
      alert('Please provide subject and detail.');
      return;
    }
    updateStatus(REJECT_TARGET_ID, 'rejected', {rejection_reason_subject: subj, rejection_reason_detail: det});
    window.dispatchEvent(new CustomEvent('close-modal', {detail:'adminManpowerRejectModal'}));
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
      await fetchRows();
    } catch (e) { console.error(e); alert(e.message || 'Status update failed'); }
  };

  search?.addEventListener('input', () => { fetchRows(); });
  statusFilter?.addEventListener('change', () => { fetchRows(); });

  fetchRows();
});
