const CONFIG = window.RETURN_ITEMS_CONFIG || {};
const LIST_ROUTE = CONFIG.list || '/admin/return-items/list';
const SHOW_BASE = CONFIG.base || '/admin/return-items';
const UPDATE_INSTANCE_BASE = CONFIG.updateInstanceBase || '/admin/return-items/instances';
const CSRF_TOKEN = CONFIG.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let RETURN_ROWS = [];
let CURRENT_PAGE = 1;
const PER_PAGE = 10;

function cloneTemplate(id, fallbackText) {
    const tpl = document.getElementById(id);
    if (!tpl) {
        const span = document.createElement('span');
        span.className = 'inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-200 text-gray-700';
        span.textContent = fallbackText ?? '—';
        return span;
    }
    return tpl.content.cloneNode(true);
}

function renderStatusBadge(status) {
    const key = String(status || 'pending').toLowerCase();
    const fragment = cloneTemplate(`badge-status-${key}`, status || 'Pending');
    return fragment;
}

function renderConditionBadge(condition) {
    const key = String(condition || 'pending').toLowerCase();
    const fragment = cloneTemplate(`badge-condition-${key}`, condition || 'Pending');
    return fragment;
}

function formatDeliveryStatus(status) {
    if (!status) return 'Pending';
    const value = status.toString().toLowerCase();
    if (value === 'dispatched') return 'Dispatched';
    if (value === 'returned') return 'Returned';
    return status;
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

async function loadReturnItems(resetPage = true) {
    try {
        const res = await fetch(LIST_ROUTE, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        RETURN_ROWS = Array.isArray(data) ? data : [];
        if (resetPage) CURRENT_PAGE = 1;
        renderTable();
    } catch (err) {
        console.error('Failed to load return items', err);
        showAlert('error', 'Failed to load return items.');
    }
}

function showAlert(type, message) {
    const tpl = document.getElementById(type === 'success' ? 'alert-success-template' : 'alert-error-template');
    const container = document.getElementById('alertContainer');
    if (!tpl || !container) return;
    const frag = tpl.content.cloneNode(true);
    const span = frag.querySelector('[data-alert-message]');
    if (span) span.textContent = message;
    const node = container.appendChild(frag);
    setTimeout(() => {
        if (container.contains(node)) node.remove();
    }, 4000);
}

function renderTable() {
    const tbody = document.getElementById('returnItemsTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!Array.isArray(RETURN_ROWS) || RETURN_ROWS.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-gray-500">No delivered borrow requests yet.</td></tr>';
        return;
    }

    const totalPages = Math.max(1, Math.ceil(RETURN_ROWS.length / PER_PAGE));
    CURRENT_PAGE = Math.min(Math.max(1, CURRENT_PAGE), totalPages);
    const start = (CURRENT_PAGE - 1) * PER_PAGE;
    const rows = RETURN_ROWS.slice(start, start + PER_PAGE);

    rows.forEach((row) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition';

        const tdBorrowId = document.createElement('td');
        tdBorrowId.className = 'px-6 py-3';
        tdBorrowId.textContent = row.borrow_request_id ?? row.id ?? '—';
        tr.appendChild(tdBorrowId);

        const tdBorrower = document.createElement('td');
        tdBorrower.className = 'px-6 py-3';
        tdBorrower.textContent = row.borrower_name || 'Unknown';
        tr.appendChild(tdBorrower);

        const tdDelivery = document.createElement('td');
        tdDelivery.className = 'px-6 py-3';
        const deliveryBadge = renderStatusBadge(row.delivery_status);
        tdDelivery.appendChild(deliveryBadge);
        tr.appendChild(tdDelivery);

        const tdCondition = document.createElement('td');
        tdCondition.className = 'px-6 py-3';
        const conditionBadge = renderConditionBadge(row.condition);
        tdCondition.appendChild(conditionBadge);
        tr.appendChild(tdCondition);

        const actionsTd = document.createElement('td');
        actionsTd.className = 'px-6 py-3';
        const wrapper = document.createElement('div');
        wrapper.className = 'flex justify-center gap-2';
        const manageTpl = document.getElementById('action-manage-template');
        if (manageTpl) {
            const btn = manageTpl.content.cloneNode(true);
            const button = btn.querySelector('[data-action]');
            if (button) {
                button.addEventListener('click', () => openManageModal(row.id));
            }
            wrapper.appendChild(btn);
        }
        actionsTd.appendChild(wrapper);
        tr.appendChild(actionsTd);

        tbody.appendChild(tr);
    });

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;
    container.innerHTML = '';
    if (totalPages <= 1) return;

    const createBtn = (label, page, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.className = [
            'px-3 py-1 rounded-md text-sm transition',
            active ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-purple-100',
            disabled ? ' opacity-50 cursor-not-allowed' : '',
        ].join(' ');
        if (!disabled) {
            btn.addEventListener('click', () => {
                CURRENT_PAGE = page;
                renderTable();
            });
        }
        return btn;
    };

    container.appendChild(createBtn('Prev', Math.max(1, CURRENT_PAGE - 1), CURRENT_PAGE === 1));
    for (let page = 1; page <= totalPages; page += 1) {
        container.appendChild(createBtn(page, page, false, page === CURRENT_PAGE));
    }
    container.appendChild(createBtn('Next', Math.min(totalPages, CURRENT_PAGE + 1), CURRENT_PAGE === totalPages));
}

async function openManageModal(id) {
    try {
        const res = await fetch(`${SHOW_BASE}/${encodeURIComponent(id)}`, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) {
            throw new Error(await res.text());
        }
        const data = await res.json();
        populateManageModal(data);
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manageReturnItemsModal' }));
    } catch (error) {
        console.error('Failed to load return details', error);
        showAlert('error', 'Failed to load return details.');
    }
}

function populateManageModal(data) {
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '—';
    };

    setText('manage-borrow-id', data.borrow_request_id ?? data.id ?? '—');
    setText('manage-borrower', data.borrower ?? 'Unknown');
    const addressInput = document.getElementById('manage-address');
    if (addressInput) {
        addressInput.value = data.address ?? '';
    }

    const statusBadge = document.getElementById('manage-status-badge');
    if (statusBadge) {
        statusBadge.innerHTML = '';
        const badge = renderStatusBadge(data.status);
        statusBadge.appendChild(badge);
    }

    setText('manage-delivery-status', formatDeliveryStatus(data.delivery_status));
    setText('manage-borrow-date', formatDate(data.borrow_date));
    setText('manage-return-date', formatDate(data.return_date));

    const tbody = document.getElementById('manage-items-tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const items = Array.isArray(data.items) ? data.items : [];
    if (!items.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 3;
        td.className = 'px-4 py-4 text-center text-gray-500';
        td.textContent = 'No borrowed items recorded.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    items.forEach((item) => {
        const tr = document.createElement('tr');
        tr.className = 'divide-x';

        const tdLabel = document.createElement('td');
        tdLabel.className = 'px-4 py-3 text-left font-medium text-gray-800';
        tdLabel.textContent = item.property_label || 'Untracked Item';
        tr.appendChild(tdLabel);

        const tdSelect = document.createElement('td');
        tdSelect.className = 'px-4 py-3';
        const select = document.createElement('select');
        select.className = 'w-full border border-gray-300 rounded-md text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-purple-300';
        [
            { value: 'good', label: 'Good' },
            { value: 'minor_damage', label: 'Minor Damage' },
            { value: 'damage', label: 'Damage' },
            { value: 'missing', label: 'Missing' },
        ].forEach((opt) => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            select.appendChild(option);
        });
        select.value = item.condition || 'pending';
        tdSelect.appendChild(select);
        tr.appendChild(tdSelect);

        const tdAction = document.createElement('td');
        tdAction.className = 'px-4 py-3';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'inline-flex items-center px-3 py-1 text-xs font-semibold rounded-md bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300';
        button.textContent = 'Update';
        button.addEventListener('click', () => updateInstance(item.id, select.value, button));
        tdAction.appendChild(button);
        tr.appendChild(tdAction);

        tbody.appendChild(tr);
    });
}

async function updateInstance(instanceId, condition, button) {
    if (!instanceId) return;
    button.disabled = true;
    try {
        const res = await fetch(`${UPDATE_INSTANCE_BASE}/${encodeURIComponent(instanceId)}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ condition }),
        });
        if (!res.ok) {
            const payload = await res.json().catch(() => null);
            throw new Error(payload?.message || `HTTP ${res.status}`);
        }
        const data = await res.json().catch(() => ({}));
        showAlert('success', data.message || 'Condition updated.');
        await loadReturnItems(false);
    } catch (error) {
        console.error('Failed to update instance condition', error);
        showAlert('error', error.message || 'Failed to update condition.');
    } finally {
        button.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('returnItemsTableBody')) return;
    loadReturnItems();
});

window.loadAdminReturnItems = loadReturnItems;
window.openManageReturnModal = openManageModal;
