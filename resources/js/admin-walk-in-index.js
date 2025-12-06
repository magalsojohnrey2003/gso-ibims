// resources/js/admin-walk-in-index.js

(function() {
  const tableBodyId = 'walkinTableBody';
  const state = {
    rows: [],
  };
  const borrowerTableBodyId = 'walkinBorrowerTableBody';
  const borrowerState = {
    rows: [],
    searchTerm: '',
  };
  let borrowerAbortController = null;
  let borrowerSearchDebounce = null;
  const ICONS = {
    printer: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v8H6z" /></svg>',
    eye: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    truck: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.5h9a1.5 1.5 0 011.5 1.5v7.5m-6 0H3.75A1.5 1.5 0 012.25 15V7.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M12.75 12h3.028a1.5 1.5 0 011.122.5l2.55 2.85a1.5 1.5 0 01.4 1v1.65A1.5 1.5 0 0118.35 19.5H18" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 19.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM18 19.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" /></svg>'
  };
  const BORROWER_STATUS_COLORS = {
    good: 'bg-emerald-100 text-emerald-700',
    fair: 'bg-amber-100 text-amber-700',
    under_review: 'bg-amber-100 text-amber-700',
    risk: 'bg-rose-100 text-rose-700',
    restricted: 'bg-rose-100 text-rose-700',
    suspended: 'bg-rose-100 text-rose-700',
  };
  let loadingMarkup = null;

  const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];

  const cloneTemplate = (id) => {
    const template = document.getElementById(id);
    if (!template || !template.content || !template.content.firstElementChild) {
      return null;
    }
    return template.content.firstElementChild.cloneNode(true);
  };

  const escapeHtml = (value) => {
    if (value == null) {
      return '';
    }
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  const digitsOnly = (value) => {
    if (typeof value !== 'string') {
      value = value == null ? '' : String(value);
    }
    return value.replace(/\D+/g, '');
  };

  const formatBorrowerPhone = (value) => {
    const digits = digitsOnly(value);
    if (digits.length === 11) {
      return digits.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
    }
    if (digits.length === 10) {
      return digits.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    }
    if (digits.length === 0) {
      return '';
    }
    return digits.replace(/(\d{3})(?=\d)/g, '$1 ');
  };

  const getBorrowerStatusBadge = (row) => {
    const normalized = (row?.borrowing_status || '').toLowerCase();
    const fallback = row?.borrowing_status_label || 'Unknown';
    const colorClass = BORROWER_STATUS_COLORS[normalized] || 'bg-gray-100 text-gray-700';
    const label = normalized === 'good'
      ? (row?.borrowing_status_label || 'Good Standing')
      : normalized === 'fair' || normalized === 'under_review'
        ? (row?.borrowing_status_label || 'Fair Standing')
        : normalized === 'risk' || normalized === 'restricted' || normalized === 'suspended'
          ? (row?.borrowing_status_label || 'Needs Attention')
          : fallback;

    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full ${colorClass}"><i class="fas fa-user-shield"></i> ${escapeHtml(label)}</span>`;
  };

  const userProfileModalId = 'walkinUserProfileModal';

  const userProfileFields = {
    id: (row) => (row?.id ? `#${row.id}` : '—'),
    name: (row) => row?.name || '—',
    email: (row) => row?.email || '—',
    phone: (row) => {
      const formatted = formatBorrowerPhone(row?.phone || '');
      return formatted || '—';
    },
    address: (row) => row?.address || '—',
    joined_at: (row) => row?.registered_at_display || '—',
    standing: (row) => getBorrowerStatusBadge(row),
  };

  const updateUserProfileModal = (row) => {
    Object.entries(userProfileFields).forEach(([key, resolver]) => {
      const value = resolver(row);
      const target = document.querySelector(`[data-user-profile="${key}"]`);
      if (!target) return;
      if (key === 'standing') {
        target.innerHTML = value;
      } else {
        target.textContent = value;
      }
    });
  };

  const formatDateDisplay = (date) => {
    if (!(date instanceof Date)) return null;
    if (Number.isNaN(date.getTime())) return null;
    const month = SHORT_MONTHS[date.getMonth()];
    if (!month) return null;
    const day = date.getDate();
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
  };

  const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: 'numeric',
    minute: '2-digit',
  });

  const formatRequestId = (row) => {
    if (!row) return '';
    const formatted = typeof row.formatted_request_id === 'string' ? row.formatted_request_id.trim() : '';
    if (formatted) return formatted;
    const rawId = row.id ?? null;
    if (!rawId) return '';
    return `WI-${String(rawId).padStart(4, '0')}`;
  };

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
    return parsed ? formatDateDisplay(parsed) : null;
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

  const getStatusBadge = (status) => {
    const badges = {
      pending: '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock"></i> Pending</span>',
      approved: '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle"></i> Approved</span>',
      delivered: '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-truck"></i> Delivered</span>',
    };
    return badges[status] || '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700"><i class="fas fa-question-circle"></i> Unknown</span>';
  };

  const getActionButtons = (row) => {
    const status = row.status || 'pending';
    const buttons = [];

    // Print button - always visible
    buttons.push(`
      <button type="button" data-action="print" class="btn-action btn-print h-10 w-10" title="Print">
        <span class="sr-only">Print</span>
        ${ICONS.printer}
      </button>
    `);

    // Conditional buttons based on status
    if (status === 'approved') {
      buttons.push(`
        <button type="button" data-action="deliver" class="btn-action btn-deliver h-10 w-10" title="Deliver Items">
          <span class="sr-only">Deliver Items</span>
          ${ICONS.truck}
        </button>
      `);
    }

    if (status === 'delivered') {
      buttons.push(`
        <button type="button" data-action="view" class="btn-action btn-view h-10 w-10" title="View">
          <span class="sr-only">View</span>
          ${ICONS.eye}
        </button>
      `);
    }

    return buttons.join('');
  };

  const renderBorrowerRows = (rows) => {
    const tbody = document.getElementById(borrowerTableBodyId);
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
      const emptyRow = cloneTemplate('walkin-borrower-empty-template');
      if (emptyRow) {
        tbody.appendChild(emptyRow);
      } else {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="3" class="px-6 py-8 text-center text-gray-500">No borrower accounts found.</td>';
        tbody.appendChild(tr);
      }
      return;
    }

    rows.forEach((row) => {
      const tr = document.createElement('tr');
      tr.dataset.id = String(row.id);
      tr.innerHTML = `
        <td class="px-6 py-4 align-middle text-center font-semibold text-gray-900">${row.id ? `#${escapeHtml(row.id)}` : '—'}</td>
        <td class="px-6 py-4 align-middle text-center">
          <span class="font-medium text-gray-900">${escapeHtml(row.name || '—')}</span>
        </td>
        <td class="px-6 py-4 align-middle text-center">
          <div class="flex items-center justify-center gap-2">
            <button type="button" class="btn-action btn-view h-9 w-9" data-borrower-action="view" title="View borrower">
              <span class="sr-only">View borrower</span>
              ${ICONS.eye}
            </button>
            <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition" data-borrower-action="select">
              <i class="fas fa-arrow-right"></i>
              <span>Select</span>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });
  };

  const renderBorrowerLoading = () => {
    const tbody = document.getElementById(borrowerTableBodyId);
    if (!tbody) return;
    tbody.innerHTML = '';
    const loadingRow = cloneTemplate('walkin-borrower-loading-template');
    if (loadingRow) {
      tbody.appendChild(loadingRow);
    }
  };

  const renderBorrowerError = () => {
    const tbody = document.getElementById(borrowerTableBodyId);
    if (!tbody) return;
    tbody.innerHTML = '';
    const errorRow = cloneTemplate('walkin-borrower-error-template');
    if (errorRow) {
      tbody.appendChild(errorRow);
    } else {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td colspan="3" class="px-6 py-8 text-center text-red-600">Unable to load borrower accounts.</td>';
      tbody.appendChild(tr);
    }
  };

  const fetchBorrowers = async (term = '') => {
    if (typeof window.WALKIN_BORROWERS_ROUTE !== 'string') {
      return;
    }

    renderBorrowerLoading();

    if (borrowerAbortController) {
      borrowerAbortController.abort();
    }

    borrowerAbortController = new AbortController();

    try {
      const url = new URL(window.WALKIN_BORROWERS_ROUTE, window.location.origin);
      if (term) {
        url.searchParams.set('q', term);
      }
      const res = await fetch(url.toString(), {
        headers: { Accept: 'application/json' },
        signal: borrowerAbortController.signal,
      });

      if (!res.ok) {
        throw new Error(`Failed to fetch borrowers: ${res.status}`);
      }

      const data = await res.json();
      borrowerState.rows = Array.isArray(data?.data) ? data.data : [];
      renderBorrowerRows(borrowerState.rows);
      borrowerAbortController = null;
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }
      console.error('Borrower fetch error:', error);
      borrowerState.rows = [];
      renderBorrowerError();
      if (typeof window.showToast === 'function') {
        window.showToast('Unable to load borrower accounts.', 'error');
      }
      borrowerAbortController = null;
    }
  };

  const selectBorrower = (row) => {
    if (typeof window.WALKIN_CREATE_ROUTE !== 'string') {
      if (typeof window.showToast === 'function') {
        window.showToast('Walk-in form route is unavailable.', 'error');
      }
      return;
    }

    const params = new URLSearchParams();
    if (row?.name) {
      params.set('borrower_name', row.name);
    }
    if (row?.phone) {
      params.set('contact_number', digitsOnly(row.phone));
    }
    if (row?.address) {
      params.set('address', row.address);
    }
    params.set('borrower_id', String(row?.id ?? ''));
    if (row?.borrowing_status) {
      params.set('borrower_status', row.borrowing_status);
    }
    params.set('prefill', 'borrower');

    const url = `${window.WALKIN_CREATE_ROUTE}?${params.toString()}`;
    window.location.href = url;
  };

  const bindBorrowerModal = () => {
    const openBtn = document.querySelector('[data-walkin-open-borrower]');
    const startBlankBtn = document.querySelector('[data-walkin-start-blank]');
    const searchInput = document.getElementById('walkin-borrower-search');
    const tbody = document.getElementById(borrowerTableBodyId);

    if (startBlankBtn) {
      startBlankBtn.addEventListener('click', () => {
        if (typeof window.WALKIN_CREATE_ROUTE === 'string') {
          window.location.href = window.WALKIN_CREATE_ROUTE;
        }
      });
    }

    if (openBtn) {
      openBtn.addEventListener('click', () => {
        borrowerState.searchTerm = '';
        if (searchInput) {
          searchInput.value = '';
        }
        if (tbody) {
          renderBorrowerLoading();
        }
        fetchBorrowers('');
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'walkinSelectBorrowerModal' }));
        setTimeout(() => {
          if (searchInput) {
            searchInput.focus();
          }
        }, 120);
      });
    }

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        const nextTerm = searchInput.value.trim();
        borrowerState.searchTerm = nextTerm;
        if (borrowerSearchDebounce) {
          clearTimeout(borrowerSearchDebounce);
        }
        borrowerSearchDebounce = setTimeout(() => {
          fetchBorrowers(nextTerm);
        }, 350);
      });
    }

    if (tbody) {
      tbody.addEventListener('click', (event) => {
        const actionBtn = event.target.closest('[data-borrower-action]');
        if (!actionBtn) return;
        const tr = actionBtn.closest('tr[data-id]');
        if (!tr) return;
        const row = borrowerState.rows.find((entry) => String(entry.id) === tr.dataset.id);
        if (!row) return;

        const action = actionBtn.getAttribute('data-borrower-action');
        if (action === 'view') {
          updateUserProfileModal(row);
          window.dispatchEvent(new CustomEvent('open-modal', { detail: userProfileModalId }));
          return;
        }

        if (action === 'select') {
          selectBorrower(row);
        }
      });
    }
  };

  const renderRows = (rows) => {
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) return;

    window.__WALKIN_CACHE__ = Array.isArray(rows) ? rows : [];
    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
      const template = document.getElementById('walkin-empty-state-template');
      tbody.innerHTML = '';
      if (template?.content?.firstElementChild) {
        tbody.appendChild(template.content.firstElementChild.cloneNode(true));
      } else {
        tbody.innerHTML = '<tr><td colspan="6" class="py-10 text-center text-gray-500">No walk-in requests yet.</td></tr>';
      }
      return;
    }

    rows.forEach((row, index) => {
      const borrowDate = getDateDisplay(row, 'borrowed') || '—';
      const borrowTime = getTimeDisplay(row, 'borrowed');
      const returnDate = getDateDisplay(row, 'returned') || '—';
      const returnTime = getTimeDisplay(row, 'returned');
      const requestCode = formatRequestId(row);

      const tr = document.createElement('tr');
      tr.dataset.id = row.id;
      tr.dataset.index = String(index);
      tr.dataset.borrower = (row.borrower_name || '').toLowerCase();
      tr.dataset.office = (row.office_agency || '').toLowerCase();
      tr.dataset.requestCode = requestCode.toLowerCase();
      tr.innerHTML = `
        <td class="px-6 py-3 font-semibold text-gray-900">${requestCode || row.id}</td>
        <td class="px-6 py-3">${row.borrower_name || '—'}</td>
        <td class="px-6 py-3">
          <div>${borrowDate}</div>
          ${borrowTime ? `<div class="text-xs text-gray-500">${borrowTime}</div>` : ''}
        </td>
        <td class="px-6 py-3">
          <div>${returnDate}</div>
          ${returnTime ? `<div class="text-xs text-gray-500">${returnTime}</div>` : ''}
        </td>
        <td class="px-6 py-3">${getStatusBadge(row.status)}</td>
        <td class="px-6 py-3">
          <div class="flex items-center justify-center gap-2">
            ${getActionButtons(row)}
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

    if (!loadingMarkup) {
      const existingLoadingRow = tbody.querySelector('[data-walkin-loading-row]');
      if (existingLoadingRow) {
        loadingMarkup = existingLoadingRow.outerHTML;
      }
    }

    if (loadingMarkup) {
      tbody.innerHTML = loadingMarkup;
    } else {
      tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-gray-500">Loading...</td></tr>';
    }

    try {
      const res = await fetch(window.WALKIN_LIST_ROUTE, {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      state.rows = Array.isArray(data) ? data : [];
      renderRows(state.rows);
    } catch (e) {
  tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-600">Failed to load walk-in requests.</td></tr>';
      state.rows = [];
      window.__WALKIN_CACHE__ = [];
    }
  };

  const handleDeliver = (row) => {
    // Populate confirmation modal
    const borrowerNameEl = document.getElementById('confirmBorrowerName');
    const officeAgencyEl = document.getElementById('confirmOfficeAgency');
    const itemsListEl = document.getElementById('confirmItemsList');

    if (borrowerNameEl) {
      borrowerNameEl.textContent = row.borrower_name || 'N/A';
    }
    if (officeAgencyEl) {
      officeAgencyEl.textContent = row.office_agency || 'N/A';
    }
    if (itemsListEl) {
      itemsListEl.innerHTML = '';
      if (row.items && row.items.length > 0) {
        row.items.forEach(item => {
          const li = document.createElement('li');
          const label = item.name || `Item #${item.item_id}`;
          li.textContent = `${label}(x${item.quantity})`;
          itemsListEl.appendChild(li);
        });
      } else {
        const li = document.createElement('li');
        li.textContent = 'No items';
        itemsListEl.appendChild(li);
      }
    }

    // Store the row ID for confirmation
    const confirmBtn = document.getElementById('walkinDeliverConfirmBtn');
    if (confirmBtn) {
      confirmBtn.dataset.requestId = row.id;
    }

    // Open the modal
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'walkinDeliverConfirmModal' }));
  };

  const confirmDeliver = async () => {
    const confirmBtn = document.getElementById('walkinDeliverConfirmBtn');
    if (!confirmBtn) return;

    const id = confirmBtn.dataset.requestId;
    if (!id) return;

    const template = window.WALKIN_DELIVER_ROUTE_TEMPLATE;
    if (!template || typeof template !== 'string' || !template.includes('__ID__')) {
      window.showToast('Deliver route not configured properly.', 'error');
      return;
    }

    const url = template.replace('__ID__', encodeURIComponent(id));

    confirmBtn.disabled = true;
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window.CSRF_TOKEN || '',
        },
      });

      const data = await res.json();

      if (res.ok) {
        // Close modal
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'walkinDeliverConfirmModal' }));
        window.showToast(data.message || 'Walk-in request delivered successfully!', 'success');
        fetchRows(); // Refresh the table
      } else {
        window.showToast(data.message || 'Failed to deliver the request.', 'error');
      }
    } catch (e) {
      console.error('Deliver error:', e);
      window.showToast('An error occurred while delivering the request.', 'error');
    } finally {
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = originalText;
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
        return;
      }

      if (action === 'deliver') {
        handleDeliver(row);
        return;
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
          const code = tr.dataset.requestCode || '';
          tr.style.display = term === '' || borrower.includes(term) || office.includes(term) || code.includes(term)
            ? ''
            : 'none';
        });
      });
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    fetchRows();
    bindActions();
    bindBorrowerModal();

    // Bind confirm deliver button
    const confirmBtn = document.getElementById('walkinDeliverConfirmBtn');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', confirmDeliver);
    }
  });
})();
