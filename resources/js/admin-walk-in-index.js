// resources/js/admin-walk-in-index.js

(function() {
  const tableBodyId = 'walkinTableBody';
  const state = {
    rows: [],
  };

  const dateFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });

  const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: 'numeric',
    minute: '2-digit',
  });

  const normalizeDateString = (value) => {
    if (typeof value !== 'string') {
      return value;
    }
    if (value.includes('T')) {
      return value;
    }
    return value.replace(' ', 'T');
  };

  const parseDate = (value) => {
    if (!value) return null;
    const normalized = normalizeDateString(value);
    const dt = normalized instanceof Date ? normalized : new Date(normalized);
    return Number.isNaN(dt.getTime()) ? null : dt;
  };

  const getDateDisplay = (row, prefix) => {
    const explicit = row?.[`${prefix}_date_display`];
    if (explicit) return explicit;
    const parsed = parseDate(row?.[`${prefix}_at`]);
    return parsed ? dateFormatter.format(parsed) : null;
  };

  const getTimeDisplay = (row, prefix) => {
    const explicit = row?.[`${prefix}_time_display`];
    if (explicit) return explicit;
    const parsed = parseDate(row?.[`${prefix}_at`]);
    if (!parsed) return null;
    if (parsed.getHours() === 0 && parsed.getMinutes() === 0 && parsed.getSeconds() === 0) {
      return null;
    }
    return timeFormatter.format(parsed);
  };

  const buildScheduleString = (row, prefix) => {
    const date = getDateDisplay(row, prefix);
    const time = getTimeDisplay(row, prefix);
    if (date && time) {
      return `${date} • ${time}`;
    }
    return date || '—';
  };

  const renderRows = (rows) => {
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) return;

    window.__WALKIN_CACHE__ = Array.isArray(rows) ? rows : [];
    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-gray-500">No walk-in requests yet.</td></tr>';
      return;
    }

    rows.forEach((row, index) => {
      const borrowDate = getDateDisplay(row, 'borrowed') || '—';
      const borrowTime = getTimeDisplay(row, 'borrowed');
      const returnDate = getDateDisplay(row, 'returned') || '—';
      const returnTime = getTimeDisplay(row, 'returned');

      const tr = document.createElement('tr');
      tr.dataset.id = row.id;
      tr.dataset.index = String(index);
      tr.dataset.borrower = (row.borrower_name || '').toLowerCase();
      tr.dataset.office = (row.office_agency || '').toLowerCase();
      tr.innerHTML = `
        <td class="px-6 py-3">${row.id}</td>
        <td class="px-6 py-3">${row.borrower_name || '—'}</td>
        <td class="px-6 py-3">
          <div>${borrowDate}</div>
          ${borrowTime ? `<div class="text-xs text-gray-500">${borrowTime}</div>` : ''}
        </td>
        <td class="px-6 py-3">
          <div>${returnDate}</div>
          ${returnTime ? `<div class="text-xs text-gray-500">${returnTime}</div>` : ''}
        </td>
        <td class="px-6 py-3">
          <div class="flex items-center justify-center gap-2">
            <button type="button" data-action="view" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              <span class="sr-only">View</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button type="button" data-action="print" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              <span class="sr-only">Print</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 9V4H8v5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v8H6z" />
              </svg>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });
  };

  const openModal = (row) => {
    const modalContent = document.getElementById('walkin-modal-content');
    if (!modalContent) return;

    const set = (key, value) => {
      const el = modalContent.querySelector(`[data-field="${key}"]`);
      if (!el) return;
      if (Array.isArray(value)) {
        el.innerHTML = '';
        if (value.length === 0) {
          el.textContent = '—';
          return;
        }
        value.forEach((it) => {
          const li = document.createElement('li');
          li.textContent = `${it.name || `Item #${it.id}`} — x${it.quantity}`;
          el.appendChild(li);
        });
        return;
      }
      el.textContent = value || '—';
    };

    set('borrower_name', row.borrower_name);
    set('office_agency', row.office_agency);
    set('contact_number', row.contact_number);
    set('address', row.address);
    set('purpose', row.purpose);
    set('items', row.items || []);
    set('borrowed_schedule', buildScheduleString(row, 'borrowed'));
    set('returned_schedule', buildScheduleString(row, 'returned'));

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'walkinDetailsModal' }));
  };

  const fetchRows = async () => {
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-gray-500">Loading...</td></tr>';

    try {
      const res = await fetch(window.WALKIN_LIST_ROUTE, {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      state.rows = Array.isArray(data) ? data : [];
      renderRows(state.rows);
    } catch (e) {
  tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-red-600">Failed to load walk-in requests.</td></tr>';
      state.rows = [];
      window.__WALKIN_CACHE__ = [];
    }
  };

  const bindActions = () => {
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) return;

    tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const action = btn.dataset.action;
      const tr = btn.closest('tr');
      const index = tr?.dataset.index ? Number.parseInt(tr.dataset.index, 10) : -1;
      if (Number.isNaN(index) || index < 0) return;
      const row = (window.__WALKIN_CACHE__ || [])[index];
      if (!row) return;

      if (action === 'view') {
        openModal(row);
        return;
      }

      if (action === 'print') {
        const template = window.WALKIN_PRINT_ROUTE_TEMPLATE;
        if (typeof template === 'string' && template.includes('__ID__')) {
          const url = template.replace('__ID__', encodeURIComponent(row.id));
          window.open(url, '_blank', 'noopener');
        }
      }
    });

    const search = document.getElementById('walkin-live-search');
    if (search) {
      search.addEventListener('input', () => {
        const term = (search.value || '').toLowerCase().trim();
        const rows = tbody.querySelectorAll('tr[data-id]');
        rows.forEach((tr) => {
          const borrower = tr.dataset.borrower || '';
          const office = tr.dataset.office || '';
          tr.style.display = term === '' || borrower.includes(term) || office.includes(term)
            ? ''
            : 'none';
        });
      });
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    fetchRows();
    bindActions();
  });
})();
