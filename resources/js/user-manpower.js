// resources/js/user-manpower.js
document.addEventListener('DOMContentLoaded', () => {
  if (!window.USER_MANPOWER) return;
  const tbody = document.getElementById('userManpowerTableBody');
  const search = document.getElementById('user-manpower-search');
  const openBtn = document.getElementById('openManpowerCreate');
  const confirmBtn = document.getElementById('confirmManpowerSubmit');
  const saveBtn = document.getElementById('saveManpowerRequest');
  const nextBtn = document.getElementById('goToStep2');
  const backBtn = document.getElementById('backToStep1');
  const form = document.getElementById('userManpowerForm');
  const preview = document.getElementById('mp_letter_preview');
  let CACHE = [];
  let PENDING_PAYLOAD = null;

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

  const badgeHtml = (status) => window.renderUserManpowerBadge ? window.renderUserManpowerBadge(status) : (status||'—');

  const render = () => {
    let rows = CACHE;
    const term = (search?.value || '').toLowerCase().trim();
    if (term) {
      rows = rows.filter(r => (r.role||'').toLowerCase().includes(term) || (r.purpose||'').toLowerCase().includes(term));
    }
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-gray-500">No requests found</td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map(r => {
      const sched = (r.start_at && r.end_at) ? `${r.start_at}<br class='hidden md:block'/>to ${r.end_at}` : '—';
      return `<tr data-request-id='${r.id}'>
        <td class='px-6 py-3'>#${r.id}</td>
        <td class='px-6 py-3'>${r.quantity}</td>
        <td class='px-6 py-3'>${r.role}</td>
        <td class='px-6 py-3 text-left'>${r.purpose}</td>
        <td class='px-6 py-3 text-xs'>${sched}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>${r.letter_url ? `<a href='${r.letter_url}' target='_blank' class='text-indigo-600 hover:underline'>View</a>` : '—'}</td>`;
    }).join('');
  };

  const validateStep1 = () => {
    const quantity = parseInt(document.getElementById('mp_quantity').value, 10);
    const role = document.getElementById('mp_role').value.trim();
    const purpose = document.getElementById('mp_purpose').value.trim();
    const location = document.getElementById('mp_location').value.trim();
    const office_agency = document.getElementById('mp_office').value.trim();
    const start_at = document.getElementById('mp_start').value;
    const end_at = document.getElementById('mp_end').value;
    if (!quantity || quantity < 1) return { ok:false, message:'Quantity must be at least 1' };
    if (!role) return { ok:false, message:'Role is required' };
    if (!purpose) return { ok:false, message:'Purpose is required' };
    if (!location) return { ok:false, message:'Location is required' };
    if (!start_at) return { ok:false, message:'Start date/time is required' };
    if (!end_at) return { ok:false, message:'End date/time is required' };
    if (new Date(end_at) <= new Date(start_at)) return { ok:false, message:'End must be after start' };
    return { ok:true, data: { quantity, role, purpose, location, office_agency, start_at, end_at } };
  };

  const openModal = (name) => window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
  const closeModal = (name) => window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));

  openBtn?.addEventListener('click', () => {
    form?.reset();
    preview?.classList.add('hidden');
    PENDING_PAYLOAD = null;
    openModal('userManpowerCreateModal');
  });

  nextBtn?.addEventListener('click', () => {
    const v = validateStep1();
    if (!v.ok) { 
      window.showToast('warning', v.message); 
      return; 
    }
    PENDING_PAYLOAD = v.data;
    // switch accordion to step 2
    const triggers = document.querySelectorAll('[data-accordion-trigger]');
    if (triggers[0]) triggers[0].click();
    if (triggers[1]) triggers[1].click();
  });

  backBtn?.addEventListener('click', () => {
    const triggers = document.querySelectorAll('[data-accordion-trigger]');
    if (triggers[1]) triggers[1].click();
    if (triggers[0]) triggers[0].click();
  });

  document.getElementById('mp_letter')?.addEventListener('change', (e) => {
    const file = e.target.files && e.target.files[0];
    if (!file) { preview.classList.add('hidden'); preview.innerHTML = ''; return; }
    preview.classList.remove('hidden');
    const sizeKB = Math.round(file.size/1024);
    preview.innerHTML = `<div class='text-sm'><b>Selected:</b> ${file.name} <span class='text-gray-500'>(${sizeKB} KB)</span></div>`;
  });

  saveBtn?.addEventListener('click', () => {
    const v = validateStep1();
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
      Object.entries(PENDING_PAYLOAD).forEach(([k,v]) => fd.append(k, v));
      const file = document.getElementById('mp_letter').files?.[0];
      if (file) fd.append('letter', file);
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

  search?.addEventListener('input', render);

  fetchRows();
});
