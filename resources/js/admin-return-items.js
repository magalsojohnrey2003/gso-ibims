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
let MANAGE_SORT_CONDITION = '';
let SELECTION_ENABLED = false;
let SELECTED_INSTANCES = new Set();
let CHECKBOXES_VISIBLE = false;

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
    const base = 'inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full';
    const key = String(status || '').toLowerCase();
    let classes = ' bg-gray-100 text-gray-700';
    let icon = 'fa-question-circle';

    switch (key) {
        case 'available':
            classes = ' bg-green-100 text-green-700';
            icon = 'fa-check-circle';
            break;
        case 'borrowed':
            classes = ' bg-indigo-100 text-indigo-700';
            icon = 'fa-box';
            break;
        case 'damaged':
        case 'missing':
            classes = ' bg-red-100 text-red-700';
            icon = 'fa-exclamation-triangle';
            break;
        case 'under_repair':
            classes = ' bg-yellow-100 text-yellow-700';
            icon = 'fa-wrench';
            break;
        case 'retired':
            classes = ' bg-gray-200 text-gray-800';
            icon = 'fa-archive';
            break;
        default:
            classes = ' bg-gray-100 text-gray-700';
            icon = 'fa-question-circle';
            break;
    }

    span.className = `${base}${classes}`;
    span.innerHTML = `<i class="fas ${icon} text-xs"></i><span>${label || formatInventoryStatusLabel(status)}</span>`;
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
        showAlert('error', 'Failed to load return items. Please refresh the page.');
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
        showAlert('error', 'Failed to load return details. Please try again.');
    }
}

function populateManageModal(data) {
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '--';
    };

    // Reset selection state
    SELECTED_INSTANCES.clear();
    SELECTION_ENABLED = false;
    MANAGE_SORT_CONDITION = '';
    CHECKBOXES_VISIBLE = false;
    
    // Reset UI controls
    const enableSelectionCheckbox = document.getElementById('manage-enable-selection');
    const selectAllCheckbox = document.getElementById('manage-select-all');
    const selectAllHeader = document.getElementById('manage-select-all-header');
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    const sortConditionSelect = document.getElementById('manage-sort-condition');
    
    if (enableSelectionCheckbox) enableSelectionCheckbox.checked = false;
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    if (selectAllHeader) selectAllHeader.checked = false;
    if (bulkConditionSelect) bulkConditionSelect.value = '';
    if (sortConditionSelect) sortConditionSelect.value = '';

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
    let rows = MANAGE_ITEMS.filter((item) => !MANAGE_FILTER || item.item_name === MANAGE_FILTER);

    // Apply sorting by condition
    if (MANAGE_SORT_CONDITION) {
        rows = rows.filter((item) => {
            const condition = (item.condition || 'pending').toLowerCase();
            return condition === MANAGE_SORT_CONDITION.toLowerCase();
        });
    }

    // Show/hide checkbox column
    const checkboxHeader = document.getElementById('manage-checkbox-header');
    if (checkboxHeader) {
        checkboxHeader.style.display = CHECKBOXES_VISIBLE ? '' : 'none';
    }

    if (!rows.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = CHECKBOXES_VISIBLE ? 4 : 3;
        td.className = 'px-4 py-4 text-center text-gray-500';
        td.textContent = 'No property numbers for this selection.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    rows.forEach((item) => {
        const tr = document.createElement('tr');
        tr.className = 'divide-x transition-colors';
        if (SELECTION_ENABLED) {
            tr.classList.add('cursor-pointer');
        }
        tr.dataset.instanceId = item.id;
        tr.dataset.condition = item.condition || 'pending';

        // Checkbox cell (only shown when CHECKBOXES_VISIBLE is true)
        if (CHECKBOXES_VISIBLE) {
            const tdCheckbox = document.createElement('td');
            tdCheckbox.className = 'px-4 py-3 text-center';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'manage-row-checkbox w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500';
            checkbox.dataset.instanceId = item.id;
            checkbox.checked = SELECTED_INSTANCES.has(item.id);
            checkbox.disabled = !SELECTION_ENABLED && !CHECKBOXES_VISIBLE;
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    SELECTED_INSTANCES.add(item.id);
                } else {
                    SELECTED_INSTANCES.delete(item.id);
                }
                updateRowHighlight(tr, e.target.checked);
                updateSelectAllState();
                updateBulkUpdateButton();
            });
            tdCheckbox.appendChild(checkbox);
            tr.appendChild(tdCheckbox);
        }

        // Apply purple highlight if selected
        if (SELECTED_INSTANCES.has(item.id)) {
            tr.classList.add('bg-purple-200');
        }

        // Add click handler for individual row selection (only when selection mode is enabled)
        if (SELECTION_ENABLED) {
            tr.addEventListener('click', (e) => {
                // Don't trigger if clicking on checkbox, select, or button elements
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'BUTTON' || e.target.closest('select') || e.target.closest('button') || e.target.closest('input[type="checkbox"]')) {
                    return;
                }
                
                const instanceId = item.id;
                const isSelected = SELECTED_INSTANCES.has(instanceId);
                
                if (isSelected) {
                    SELECTED_INSTANCES.delete(instanceId);
                    updateRowHighlight(tr, false);
                    // Update checkbox if visible
                    const checkbox = tr.querySelector('.manage-row-checkbox');
                    if (checkbox) checkbox.checked = false;
                } else {
                    SELECTED_INSTANCES.add(instanceId);
                    updateRowHighlight(tr, true);
                    // Update checkbox if visible
                    const checkbox = tr.querySelector('.manage-row-checkbox');
                    if (checkbox) checkbox.checked = true;
                }
                
                updateSelectAllState();
                updateBulkUpdateButton();
            });
        }

        const tdPropertyNumber = document.createElement('td');
        tdPropertyNumber.className = 'px-4 py-3 text-left font-medium text-gray-800';
        tdPropertyNumber.textContent = item.property_label || 'Untracked Item';
        tr.appendChild(tdPropertyNumber);

        const tdItemName = document.createElement('td');
        tdItemName.className = 'px-4 py-3 text-left text-gray-700';
        tdItemName.textContent = item.item_name || 'Unknown';
        tr.appendChild(tdItemName);

        const tdCondition = document.createElement('td');
        tdCondition.className = 'px-4 py-3 text-left';
        tdCondition.appendChild(renderConditionBadge(item.condition, item.condition_label));
        tr.appendChild(tdCondition);

        tbody.appendChild(tr);
    });

    updateSelectAllState();
    updateBulkUpdateButton();
}

function updateRowHighlight(tr, isSelected) {
    if (isSelected) {
        tr.classList.add('bg-purple-200');
        tr.classList.remove('bg-purple-100');
    } else {
        tr.classList.remove('bg-purple-200', 'bg-purple-100');
    }
}

function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('manage-select-all');
    const selectAllHeader = document.getElementById('manage-select-all-header');
    const rows = document.querySelectorAll('#manage-items-tbody tr[data-instance-id]');
    
    if (rows.length === 0) {
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        if (selectAllHeader) selectAllHeader.checked = false;
        return;
    }

    const selectedCount = Array.from(rows).filter(tr => SELECTED_INSTANCES.has(tr.dataset.instanceId)).length;
    const allChecked = selectedCount === rows.length && rows.length > 0;
    
    if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
    if (selectAllHeader) selectAllHeader.checked = allChecked;
}

function updateBulkUpdateButton() {
    const bulkUpdateBtn = document.getElementById('manage-bulk-update-btn');
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    
    if (bulkUpdateBtn && bulkConditionSelect) {
        const hasSelection = SELECTED_INSTANCES.size > 0;
        const hasCondition = bulkConditionSelect.value !== '';
        bulkUpdateBtn.disabled = !hasSelection || !hasCondition;
    }
}

function applyBorrowSummaryUpdate(payload) {
    if (!payload || !MANAGE_BORROW_ID) return;

    const summaryLabel = payload.borrow_summary || null;
    const latestStatus = payload.latest_status || null;
    const latestDeliveryStatus = payload.latest_delivery_status || null;

    let mutated = false;

    RETURN_ROWS = RETURN_ROWS.map((row) => {
        const borrowId = row.borrow_request_id ?? row.id;
        if (borrowId === MANAGE_BORROW_ID) {
            const next = { ...row };
            if (summaryLabel) {
                const normalized = summaryLabel
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/_+/g, '_')
                    .replace(/^_|_$/g, '');

                next.condition_label = summaryLabel;
                next.condition = normalized || next.condition || 'pending';
                mutated = true;
            }
            if (latestStatus) {
                next.status = latestStatus;
                mutated = true;
            }
            if (latestDeliveryStatus) {
                next.delivery_status = latestDeliveryStatus;
                mutated = true;
            }
            return next;
        }
        return row;
    });

    if (mutated) {
        renderTable();
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
        showAlert('success', data.message || 'Items marked as returned successfully.');
        await loadReturnItems(false);
        window.dispatchEvent(new CustomEvent('return-items:collected', { detail: { borrowRequestId: id, response: data } }));
    } catch (error) {
        console.error('Failed to mark as collected', error);
        showAlert('error', error.message || 'Failed to mark items as collected. Please try again.');
    } finally {
        if (button) button.disabled = false;
    }
}

async function bulkUpdateInstances() {
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    const bulkUpdateBtn = document.getElementById('manage-bulk-update-btn');
    
    if (!bulkConditionSelect || !bulkUpdateBtn) return;
    
    const condition = bulkConditionSelect.value;
    if (!condition || SELECTED_INSTANCES.size === 0) return;

    bulkUpdateBtn.disabled = true;
    const buttonText = bulkUpdateBtn.textContent;
    bulkUpdateBtn.textContent = 'Updating...';

    try {
        const instanceIds = Array.from(SELECTED_INSTANCES);
        const updateOptions = { showToast: false, renderRows: false, updateTable: false };
        const results = await Promise.all(instanceIds.map((id) => updateInstance(id, condition, updateOptions)));
        const finalResult = results.length ? results[results.length - 1] : null;
        
        showAlert('success', `Condition updated successfully for ${instanceIds.length} item(s).`);
        
        // Clear selection
        SELECTED_INSTANCES.clear();
        bulkConditionSelect.value = '';
        renderManageRows();
        updateSelectAllState();
        updateBulkUpdateButton();
        if (finalResult) {
            applyBorrowSummaryUpdate(finalResult);
        }
    } catch (error) {
        console.error('Bulk update failed', error);
        showAlert('error', 'Failed to update condition for some items. Please try again.');
    } finally {
        bulkUpdateBtn.disabled = false;
        bulkUpdateBtn.textContent = buttonText;
    }
}

async function updateInstance(instanceId, condition, options = {}) {
    const {
        showToast = true,
        renderRows = true,
        updateTable = true,
    } = options;

    if (!instanceId) return;

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
        if (showToast) {
            showAlert('success', data.message || 'Item condition updated successfully.');
        }

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
        if (renderRows) {
            renderManageRows();
            updateSelectAllState();
            updateBulkUpdateButton();
        }
        if (updateTable) {
            applyBorrowSummaryUpdate(data);
        }
        window.dispatchEvent(new CustomEvent('return-items:condition-updated', {
            detail: { instanceId, condition, response: data },
        }));
        return data;
    } catch (error) {
        console.error('Failed to update instance condition', error);
        if (showToast) {
            showAlert('error', error.message || 'Failed to update item condition. Please try again.');
        }
        throw error;
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

    // Show/hide checkboxes function
    const showCheckboxes = (show) => {
        CHECKBOXES_VISIBLE = show;
        renderManageRows();
    };

    // Select All checkbox (works independently)
    const selectAllCheckbox = document.getElementById('manage-select-all');
    const selectAllHeader = document.getElementById('manage-select-all-header');
    
    const handleSelectAll = (checked) => {
        // Show checkboxes when Select All is clicked
        if (checked && !CHECKBOXES_VISIBLE) {
            showCheckboxes(true);
        } else if (!checked) {
            // Hide checkboxes if Select All is unchecked AND Select is also unchecked
            const enableSelectionCheckbox = document.getElementById('manage-enable-selection');
            const selectChecked = enableSelectionCheckbox && enableSelectionCheckbox.checked;
            if (!selectChecked && CHECKBOXES_VISIBLE) {
                showCheckboxes(false);
            }
        }
        
        const rows = document.querySelectorAll('#manage-items-tbody tr[data-instance-id]');
        rows.forEach(tr => {
            const instanceId = tr.dataset.instanceId;
            if (checked) {
                SELECTED_INSTANCES.add(instanceId);
                updateRowHighlight(tr, true);
                // Update checkbox if visible
                const checkbox = tr.querySelector('.manage-row-checkbox');
                if (checkbox) checkbox.checked = true;
            } else {
                SELECTED_INSTANCES.delete(instanceId);
                updateRowHighlight(tr, false);
                // Update checkbox if visible
                const checkbox = tr.querySelector('.manage-row-checkbox');
                if (checkbox) checkbox.checked = false;
            }
        });
        updateSelectAllState();
        updateBulkUpdateButton();
    };

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            handleSelectAll(e.target.checked);
        });
    }

    if (selectAllHeader) {
        selectAllHeader.addEventListener('change', (e) => {
            handleSelectAll(e.target.checked);
        });
    }

    // Enable/Disable selection mode (for individual row clicks)
    const enableSelectionCheckbox = document.getElementById('manage-enable-selection');
    if (enableSelectionCheckbox) {
        enableSelectionCheckbox.addEventListener('change', (e) => {
            SELECTION_ENABLED = e.target.checked;
            
            // Show checkboxes when Select is enabled
            if (SELECTION_ENABLED && !CHECKBOXES_VISIBLE) {
                showCheckboxes(true);
            }
            
            // Re-render rows to update click handlers and checkbox states
            renderManageRows();
            
            // Hide checkboxes and clear selection when disabling (but keep checkboxes visible if Select All is checked)
            if (!SELECTION_ENABLED) {
                const selectAllCheckbox = document.getElementById('manage-select-all');
                const selectAllChecked = selectAllCheckbox && selectAllCheckbox.checked;
                
                if (!selectAllChecked) {
                    // Hide checkboxes if Select All is also unchecked
                    if (CHECKBOXES_VISIBLE) {
                        showCheckboxes(false);
                    }
                    SELECTED_INSTANCES.clear();
                    const rows = document.querySelectorAll('#manage-items-tbody tr');
                    rows.forEach(tr => updateRowHighlight(tr, false));
                    updateSelectAllState();
                    updateBulkUpdateButton();
                }
            }
        });
    }

    // Bulk update button
    const bulkUpdateBtn = document.getElementById('manage-bulk-update-btn');
    if (bulkUpdateBtn) {
        bulkUpdateBtn.addEventListener('click', bulkUpdateInstances);
    }

    // Bulk condition select change
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    if (bulkConditionSelect) {
        bulkConditionSelect.addEventListener('change', updateBulkUpdateButton);
    }

    // Sort by condition
    const sortConditionSelect = document.getElementById('manage-sort-condition');
    if (sortConditionSelect) {
        sortConditionSelect.addEventListener('change', (e) => {
            MANAGE_SORT_CONDITION = e.target.value;
            renderManageRows();
        });
    }
});

window.addEventListener('realtime:borrow-request-status-updated', () => {
    loadReturnItems(false);
});

window.loadAdminReturnItems = loadReturnItems;
window.openManageReturnModal = openManageModal;
