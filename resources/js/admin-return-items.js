const CONFIG = window.RETURN_ITEMS_CONFIG || {};
const LIST_ROUTE = CONFIG.list || '/admin/return-items/list';
const SHOW_BASE = CONFIG.base || '/admin/return-items';
const UPDATE_INSTANCE_BASE = CONFIG.updateInstanceBase || '/admin/return-items/instances';
const COLLECT_BASE = CONFIG.collectBase || '/admin/return-items';
const CSRF_TOKEN = CONFIG.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let RETURN_ROWS = [];
let CURRENT_PAGE = 1;
const PER_PAGE = 10;

let MANAGE_ITEMS = [];
let MANAGE_BORROW_ID = null;
let MANAGE_FILTER = '';
let PENDING_COLLECT_ID = null;

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

function renderStatusBadge(status, label) {
    const key = String(status || 'pending').toLowerCase();
    const fragment = cloneTemplate(`badge-status-${key}`, label || status || 'Pending');
    return fragment;
}

function renderConditionBadge(condition, label) {
    const key = String(condition || 'pending').toLowerCase();
    const fragment = cloneTemplate(`badge-condition-${key}`, label || condition || 'Pending');
    return fragment;
}

function formatInventoryStatusLabel(status) {
    switch (String(status || '').toLowerCase()) {
        case 'available':
            return 'Available';
        case 'borrowed':
            return 'Borrowed';
        case 'damaged':
            return 'Damaged';
        case 'under_repair':
            return 'Under Repair';
        case 'retired':
            return 'Retired';
        case 'missing':
            return 'Missing';
        default:
            return 'Unknown';
    }
}

function renderInventoryStatusBadge(status, label) {
    const span = document.createElement('span');
    const base = 'inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full';
    const key = String(status || '').toLowerCase();
    let classes = ' bg-gray-100 text-gray-700';

    switch (key) {
        case 'available':
            classes = ' bg-green-100 text-green-700';
            break;
        case 'borrowed':
            classes = ' bg-indigo-100 text-indigo-700';
            break;
        case 'damaged':
        case 'missing':
            classes = ' bg-red-100 text-red-700';
            break;
        case 'under_repair':
            classes = ' bg-yellow-100 text-yellow-700';
            break;
        case 'retired':
            classes = ' bg-gray-200 text-gray-800';
            break;
        default:
            classes = ' bg-gray-100 text-gray-700';
            break;
    }

    span.className = `${base}${classes}`;
    span.textContent = label || formatInventoryStatusLabel(status);
    return span;
}

function formatDeliveryStatus(status) {
    if (!status) return 'Pending';
    switch (String(status).toLowerCase()) {
        case 'dispatched':
            return 'Borrowed';
        case 'returned':
            return 'Returned';
        case 'not_received':
            return 'Not Received';
        default:
            return status;
    }
}

function formatDate(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function formatDateTime(value) {
    if (!value) return '--';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
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
        tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-gray-500">No return items to review yet.</td></tr>';
        return;
    }

    const totalPages = Math.max(1, Math.ceil(RETURN_ROWS.length / PER_PAGE));
    CURRENT_PAGE = Math.min(Math.max(1, CURRENT_PAGE), totalPages);
    const start = (CURRENT_PAGE - 1) * PER_PAGE;
    const rows = RETURN_ROWS.slice(start, start + PER_PAGE);

    rows.forEach((row) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition';

        const borrowId = row.borrow_request_id ?? row.id ?? '--';

        const tdBorrower = document.createElement('td');
        tdBorrower.className = 'px-6 py-3 text-left';
        const borrowerName = document.createElement('div');
        borrowerName.className = 'font-semibold text-gray-900';
        borrowerName.textContent = row.borrower_name || 'Unknown';
        const borrowerId = document.createElement('div');
        borrowerId.className = 'text-xs text-gray-500';
        borrowerId.textContent = `Borrow ID: ${borrowId}`;
        tdBorrower.appendChild(borrowerName);
        tdBorrower.appendChild(borrowerId);
        tr.appendChild(tdBorrower);

        const tdStatus = document.createElement('td');
        tdStatus.className = 'px-6 py-3 text-left';
        const statusBadge = renderStatusBadge(row.delivery_status, formatDeliveryStatus(row.delivery_status));
        tdStatus.appendChild(statusBadge);
        tr.appendChild(tdStatus);

        const tdCondition = document.createElement('td');
        tdCondition.className = 'px-6 py-3 text-left';
        const conditionBadge = renderConditionBadge(row.condition, row.condition_label);
        tdCondition.appendChild(conditionBadge);
        tr.appendChild(tdCondition);

        const actionsTd = document.createElement('td');
        actionsTd.className = 'px-6 py-3 text-center';
        const wrapper = document.createElement('div');
        wrapper.className = 'flex justify-center gap-2';

        const deliveryStatus = String(row.delivery_status || '').toLowerCase();
        if (deliveryStatus === 'dispatched') {
            const collectTpl = document.getElementById('action-collect-template');
            if (collectTpl) {
                const btnFrag = collectTpl.content.cloneNode(true);
                const button = btnFrag.querySelector('[data-action="collect"]');
                if (button) {
                    button.addEventListener('click', () => showCollectConfirm(row));
                }
                wrapper.appendChild(btnFrag);
            }
        } else {
            const manageTpl = document.getElementById('action-manage-template');
            if (manageTpl) {
                const btnFrag = manageTpl.content.cloneNode(true);
                const button = btnFrag.querySelector('[data-action="manage"]');
                if (button) {
                    button.addEventListener('click', () => openManageModal(row.id));
                }
                wrapper.appendChild(btnFrag);
            }
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
        if (el) el.textContent = value ?? '--';
    };

    MANAGE_BORROW_ID = data.id ?? data.borrow_request_id ?? null;
    MANAGE_ITEMS = (Array.isArray(data.items) ? data.items : []).map((item) => ({
        ...item,
        inventory_status_label: item.inventory_status_label || formatInventoryStatusLabel(item.inventory_status),
    }));
    MANAGE_FILTER = data.default_item || '';

    setText('manage-borrow-id', data.borrow_request_id ?? data.id ?? '--');
    setText('manage-borrower', data.borrower ?? 'Unknown');

    const addressInput = document.getElementById('manage-address');
    if (addressInput) {
        addressInput.value = data.address ?? '';
    }

    const statusBadge = document.getElementById('manage-status-badge');
    if (statusBadge) {
        statusBadge.innerHTML = '';
        statusBadge.appendChild(renderStatusBadge(data.status));
    }

    setText('manage-delivery-status', formatDeliveryStatus(data.delivery_status));
    setText('manage-borrow-date', formatDate(data.borrow_date));
    setText('manage-return-timestamp', formatDateTime(data.return_timestamp));

    const filterWrapper = document.getElementById('manage-item-filter-wrapper');
    const filterSelect = document.getElementById('manage-item-filter');
    if (filterSelect) {
        filterSelect.innerHTML = '';
        const options = Array.isArray(data.item_options) ? data.item_options : [];
        if (options.length > 1) {
            filterWrapper?.classList.remove('hidden');
            options.forEach((opt) => {
                const option = document.createElement('option');
                option.value = opt.name;
                option.textContent = `${opt.name} (${opt.count})`;
                filterSelect.appendChild(option);
            });
            filterSelect.value = MANAGE_FILTER || options[0].name;
            MANAGE_FILTER = filterSelect.value;
            filterSelect.onchange = () => {
                MANAGE_FILTER = filterSelect.value;
                renderManageRows();
            };
        } else {
            filterWrapper?.classList.add('hidden');
            MANAGE_FILTER = options[0]?.name || '';
        }
    }

    renderManageRows();
}

function renderManageRows() {
    const tbody = document.getElementById('manage-items-tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    const rows = MANAGE_ITEMS.filter((item) => !MANAGE_FILTER || item.item_name === MANAGE_FILTER);

    if (!rows.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 4;
        td.className = 'px-4 py-4 text-center text-gray-500';
        td.textContent = 'No property numbers for this selection.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    rows.forEach((item) => {
        const tr = document.createElement('tr');
        tr.className = 'divide-x';
        tr.dataset.instanceId = item.id;

        const tdLabel = document.createElement('td');
        tdLabel.className = 'px-4 py-3 text-left font-medium text-gray-800';
        tdLabel.textContent = item.property_label || 'Untracked Item';
        tr.appendChild(tdLabel);

        const tdStatus = document.createElement('td');
        tdStatus.className = 'px-4 py-3 text-left';
        tdStatus.appendChild(renderInventoryStatusBadge(item.inventory_status, item.inventory_status_label));
        tr.appendChild(tdStatus);

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
        select.value = item.condition && item.condition !== 'pending' ? item.condition : 'good';
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

        MANAGE_ITEMS = MANAGE_ITEMS.map((item) => {
            if (item.id === instanceId) {
                return {
                    ...item,
                    condition,
                    condition_label: data.condition_label || item.condition_label,
                    inventory_status: data.inventory_status || item.inventory_status,
                    inventory_status_label: data.inventory_status_label || formatInventoryStatusLabel(data.inventory_status || item.inventory_status),
                };
            }
            return item;
        });
        renderManageRows();
        await loadReturnItems(false);
        window.dispatchEvent(new CustomEvent('return-items:condition-updated', {
            detail: {
                instanceId,
                condition,
                response: data,
            },
        }));
    } catch (error) {
        console.error('Failed to update instance condition', error);
        showAlert('error', error.message || 'Failed to update condition.');
    } finally {
        button.disabled = false;
    }
}

function showCollectConfirm(row) {
    PENDING_COLLECT_ID = row?.id ?? row?.borrow_request_id ?? null;
    const messageEl = document.getElementById('collectConfirmMessage');
    if (messageEl) {
        const borrowId = row?.borrow_request_id ?? row?.id ?? '--';
        messageEl.textContent = `Are you sure borrow request #${borrowId} has been picked up?`;
    }
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'collectConfirmModal' }));
}

async function collectBorrowRequest(id, button) {
    if (!id) return;
    if (button) button.disabled = true;
    try {
        const res = await fetch(`${COLLECT_BASE}/${encodeURIComponent(id)}/collect`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
        });
        if (!res.ok) {
            const payload = await res.json().catch(() => null);
            throw new Error(payload?.message || `HTTP ${res.status}`);
        }

        const data = await res.json().catch(() => ({}));
        showAlert('success', data.message || 'Successfully returned.');
        await loadReturnItems(false);
        window.dispatchEvent(new CustomEvent('return-items:collected', { detail: { borrowRequestId: id, response: data } }));
    } catch (error) {
        console.error('Failed to mark as collected', error);
        showAlert('error', error.message || 'Failed to mark as collected.');
    } finally {
        if (button) button.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('returnItemsTableBody')) return;

    loadReturnItems();

    const collectConfirmBtn = document.getElementById('collectConfirmBtn');
    if (collectConfirmBtn) {
        collectConfirmBtn.addEventListener('click', async () => {
            if (!PENDING_COLLECT_ID) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'collectConfirmModal' }));
                return;
            }
            const btn = collectConfirmBtn;
            btn.disabled = true;
            try {
                await collectBorrowRequest(PENDING_COLLECT_ID, btn);
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'collectConfirmModal' }));
            } finally {
                btn.disabled = false;
                PENDING_COLLECT_ID = null;
            }
        });
    }
});

window.addEventListener('realtime:borrow-request-status-updated', () => {
    loadReturnItems(false);
});

window.loadAdminReturnItems = loadReturnItems;
window.openManageReturnModal = openManageModal;
