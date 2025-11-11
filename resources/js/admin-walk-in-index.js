// resources/js/admin-walk-in-index.js

(function() {
  function fmtPeriod(borrowedAt, returnedAt) {
    if (!borrowedAt && !returnedAt) return '—';
    const fmt = (s) => {
      try { return new Date(s).toLocaleString(); } catch { return s || '—'; }
    };
    return `${fmt(borrowedAt)} → ${fmt(returnedAt)}`;
  }

  async function loadList() {
    const tbody = document.getElementById('walkinTableBody');
    if (!tbody) return;
    try {
      const res = await fetch(window.WALKIN_LIST_ROUTE, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      tbody.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-gray-500">No walk-in requests yet.</td></tr>';
        return;
      }
      data.forEach((row) => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', row.id);
        tr.innerHTML = `
          <td class="px-6 py-3">${row.id}</td>
          <td class="px-6 py-3">${row.borrower_name || '—'}</td>
          <td class="px-6 py-3">${row.office_agency || '—'}</td>
          <td class="px-6 py-3">${fmtPeriod(row.borrowed_at, row.returned_at)}</td>
          <td class="px-6 py-3">
            <button type="button" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1 text-xs" data-action="view">View</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-red-600">Failed to load walk-in requests.</td></tr>';
    }
  }

  function openModal(row) {
    const modalContent = document.getElementById('walkin-modal-content');
    if (!modalContent) return;
    const set = (key, value) => {
      const el = modalContent.querySelector(`[data-field="${key}"]`);
      if (!el) return;
      if (Array.isArray(value)) {
        el.innerHTML = '';
        value.forEach((it) => {
          const li = document.createElement('li');
          li.textContent = `${it.name || ('Item #' + it.id)} — x${it.quantity}`;
          el.appendChild(li);
        });
      } else {
        el.textContent = value || '—';
      }
    };

    set('borrower_name', row.borrower_name);
    set('office_agency', row.office_agency);
    set('contact_number', row.contact_number);
    set('address', row.address);
    set('purpose', row.purpose);
    set('items', row.items || []);

    document.dispatchEvent(new CustomEvent('open-modal', { detail: 'walkinDetailsModal' }));
  }

  function bindActions() {
    const tbody = document.getElementById('walkinTableBody');
    if (!tbody) return;
    tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action="view"]');
      if (!btn) return;
      const tr = btn.closest('tr');
      const id = tr?.getAttribute('data-id');
      // Find cached data from last fetch if needed; here we re-fetch list and pick item
      // For simplicity, store data on window on load
      const row = (window.__WALKIN_CACHE__ || []).find(r => String(r.id) === String(id));
      if (row) openModal(row);
    });
  }

  async function bootstrap() {
    try {
      const res = await fetch(window.WALKIN_LIST_ROUTE, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      window.__WALKIN_CACHE__ = Array.isArray(data) ? data : [];
    } catch {
      window.__WALKIN_CACHE__ = [];
    }
    await loadList();
    bindActions();

    const search = document.getElementById('walkin-live-search');
    if (search) {
      search.addEventListener('input', () => {
        const term = (search.value || '').toLowerCase();
        const rows = document.querySelectorAll('#walkinTableBody tr[data-id]');
        rows.forEach((tr) => {
          const borrower = (tr.children[1]?.textContent || '').toLowerCase();
          const office = (tr.children[2]?.textContent || '').toLowerCase();
          tr.style.display = (borrower.includes(term) || office.includes(term)) ? '' : 'none';
        });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', bootstrap);
})();
