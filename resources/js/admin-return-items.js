const CONFIG = window.RETURN_ITEMS_CONFIG || {};
const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];
const LIST_ROUTE = CONFIG.list || '/admin/return-items/list';
const SHOW_BASE = CONFIG.base || '/admin/return-items';
const UPDATE_INSTANCE_BASE = CONFIG.updateInstanceBase || '/admin/return-items/instances';
const COLLECT_BASE = CONFIG.collectBase || '/admin/return-items';
const CSRF_TOKEN = CONFIG.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let RETURN_ROWS = [];

const LOCK_REASON_HISTORY_DEFAULT = 'This return record is read-only because a newer return update exists.';

let MANAGE_ITEMS = [];
let MANAGE_BORROW_ID = null;
let MANAGE_FILTER = '';
let PENDING_COLLECT_ID = null;
let SELECTION_ENABLED = false;
let SELECTED_INSTANCES = new Set();
let CHECKBOXES_VISIBLE = false;
let MANAGE_SEARCH_TERM = { raw: '', normalized: '' };
let VIEW_DETAILS_CACHE = new Map();

function normalizeInventoryStatus(value) {
    return String(value || '').toLowerCase();
}

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

function normalizeSearchTerm(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');
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
        case 'not_received':
            return 'Not Received';
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
        case 'not_received':
            classes = ' bg-gray-200 text-gray-800';
            icon = 'fa-triangle-exclamation';
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

function renderRequestTypeBadge(type) {
    const span = document.createElement('span');
    const key = String(type || 'online').toLowerCase();
    const isWalkIn = key === 'walk-in' || key === 'walkin';
    const classes = isWalkIn
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-blue-100 text-blue-700';
    const icon = isWalkIn ? 'fa-building' : 'fa-wifi';
    const label = isWalkIn ? 'Walk-in' : 'Online';
    span.className = `inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full ${classes}`;
    span.innerHTML = `<i class="fas ${icon} text-xs"></i><span>${label}</span>`;
    return span;
}

function formatRequestCode(row) {
    if (!row) return '';
    const provided = typeof row.formatted_request_id === 'string' ? row.formatted_request_id.trim() : '';
    if (provided) return provided;

    const requestType = (row.request_type || '').toLowerCase();
    if (requestType === 'walk-in') {
        const numeric = row.walk_in_request_id ?? parseInt(String(row.id || '').replace(/\D+/g, ''), 10);
        if (!numeric || Number.isNaN(numeric)) return '';
        return `WI-${String(numeric).padStart(4, '0')}`;
    }

    const identifier = row.borrow_request_id ?? row.id;
    if (!identifier) return '';
    const numeric = parseInt(identifier, 10);
    if (!numeric || Number.isNaN(numeric)) return '';
    return `BR-${String(numeric).padStart(4, '0')}`;
}

function formatDeliveryStatus(status) {
    if (!status) return 'Pending';
    switch (String(status).toLowerCase()) {
        case 'dispatched':
            return 'Borrowed';
        case 'delivered':
            return 'Delivered';
        case 'returned':
            return 'Returned';
        case 'return_pending':
            return 'Return Pending';
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
    const month = SHORT_MONTHS[d.getMonth()];
    if (!month) return value;
    const day = d.getDate();
    const year = d.getFullYear();
    return `${month} ${day}, ${year}`;
}

function formatDateTime(value) {
    if (!value) return '--';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    const month = SHORT_MONTHS[d.getMonth()];
    if (!month) return value;
    const day = d.getDate();
    const year = d.getFullYear();
    const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    return `${month} ${day}, ${year} ${time}`;
}

async function loadReturnItems() {
    try {
        VIEW_DETAILS_CACHE.clear();
        const res = await fetch(LIST_ROUTE, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        RETURN_ROWS = Array.isArray(data) ? data : [];
        renderTable();
    } catch (err) {
        console.error('Failed to load return items', err);
        window.showToast('Failed to load return items. Please refresh the page.', 'error');
    }
}

function renderTable() {
    const tbody = document.getElementById('returnItemsTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!Array.isArray(RETURN_ROWS) || RETURN_ROWS.length === 0) {
        const template = document.getElementById('return-items-empty-state-template');
        tbody.innerHTML = '';
        if (template?.content?.firstElementChild) {
            tbody.appendChild(template.content.firstElementChild.cloneNode(true));
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-gray-500">No return items to review yet.</td></tr>';
        }
        return;
    }

    RETURN_ROWS.forEach((row) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition';

        const requestCode = formatRequestCode(row);
        const internalId = row.borrow_request_id ?? row.id ?? null;
        const datasetCode = requestCode || (internalId != null ? String(internalId) : '');
        tr.dataset.requestCode = datasetCode.toLowerCase();
        const fallbackId = internalId && internalId !== '--' ? `#${internalId}` : '—';

        const tdRequestId = document.createElement('td');
        tdRequestId.className = 'px-6 py-3 text-center font-semibold text-gray-900';
        tdRequestId.dataset.column = 'request-id';
        tdRequestId.textContent = requestCode || fallbackId;
        tr.appendChild(tdRequestId);

        const tdBorrower = document.createElement('td');
        tdBorrower.className = 'px-6 py-3 text-left';
        tdBorrower.dataset.column = 'borrower';
        const borrowerName = document.createElement('div');
        borrowerName.className = 'font-semibold text-gray-900';
        borrowerName.textContent = row.borrower_name || 'Unknown';
        tdBorrower.appendChild(borrowerName);
        tr.appendChild(tdBorrower);

        // Request Type column
        const tdRequestType = document.createElement('td');
        tdRequestType.className = 'px-6 py-3 text-center';
        tdRequestType.dataset.column = 'request-type';
        const requestType = row.request_type || 'regular';
        tdRequestType.appendChild(renderRequestTypeBadge(requestType));
        tr.appendChild(tdRequestType);

        const tdStatus = document.createElement('td');
        tdStatus.className = 'px-6 py-3 text-center';
        tdStatus.dataset.column = 'status';
        const statusBadge = renderStatusBadge(row.delivery_status, formatDeliveryStatus(row.delivery_status));
        tdStatus.appendChild(statusBadge);
        tr.appendChild(tdStatus);

        const actionsTd = document.createElement('td');
        actionsTd.className = 'px-6 py-3 text-center';
        const wrapper = document.createElement('div');
        wrapper.className = 'flex justify-center gap-2';

        const viewTpl = document.getElementById('action-view-template');
        if (viewTpl) {
            const btnFrag = viewTpl.content.cloneNode(true);
            const button = btnFrag.querySelector('[data-action="view"]');
            if (button) {
                const identifier = row.id ?? (row.walk_in_request_id ? `W${row.walk_in_request_id}` : row.borrow_request_id);
                button.addEventListener('click', () => openViewModal(identifier));
            }
            wrapper.appendChild(btnFrag);
        }

        const deliveryStatus = String(row.delivery_status || '').toLowerCase();
        const hasReturnProof = Boolean(row.return_proof_url || row.return_proof_path);
        if (deliveryStatus !== 'returned' && !hasReturnProof) {
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

        if (hasReturnProof) {
            const proofTpl = document.getElementById('action-view-proof-template');
            if (proofTpl) {
                const btnFrag = proofTpl.content.cloneNode(true);
                const button = btnFrag.querySelector('[data-action="view-proof"]');
                if (button) {
                    button.addEventListener('click', () => openReturnProofModal(row));
                }
                wrapper.appendChild(btnFrag);
            }
        }

        actionsTd.appendChild(wrapper);
        tr.appendChild(actionsTd);

        tbody.appendChild(tr);
    });
}

async function fetchReturnDetails(identifier) {
    const idStr = String(identifier ?? '');
    if (!idStr) {
        throw new Error('Missing borrow request identifier');
    }

    if (VIEW_DETAILS_CACHE.has(idStr)) {
        return VIEW_DETAILS_CACHE.get(idStr);
    }

    try {
        const isWalkIn = idStr.startsWith('W');
        const actualId = isWalkIn ? idStr.substring(1) : idStr;
        const url = isWalkIn
            ? `${SHOW_BASE}/walk-in/${encodeURIComponent(actualId)}`
            : `${SHOW_BASE}/${encodeURIComponent(actualId)}`;
        const res = await fetch(url, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) {
            throw new Error(await res.text());
        }
        const data = await res.json();
        VIEW_DETAILS_CACHE.set(idStr, data);
        return data;
    } catch (error) {
        console.error('Failed to load return details', error);
        throw error;
    }
}

async function openManageModal(id) {
    try {
        const data = await fetchReturnDetails(id);
        populateManageModal(data);
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manageReturnItemsModal' }));
    } catch (error) {
        window.showToast('Failed to load return details. Please try again.', 'error');
    }
}

async function openViewModal(id) {
    try {
        const data = await fetchReturnDetails(id);
        populateViewModal(data);
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'viewReturnItemsModal' }));
    } catch (error) {
        window.showToast('Failed to load request details. Please try again.', 'error');
    }
}

async function openReturnProofModal(row) {
    if (!row) {
        window.showToast('Proof details unavailable.', 'error');
        return;
    }

    populateReturnProofModal(row);
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'viewReturnProofModal' }));

    const identifier = row.id ?? row.borrow_request_id ?? (row.walk_in_request_id ? `W${row.walk_in_request_id}` : null);
    if (!identifier) {
        return;
    }

    try {
        const details = await fetchReturnDetails(identifier);
        if (details) {
            const merged = { ...row, ...details };
            populateReturnProofModal(merged);
        }
    } catch (error) {
        console.warn('Failed to refresh proof data', error);
    }
}

function populateReturnProofModal(row) {
    const requestCode = formatRequestCode(row) || row.formatted_request_id || `#${row.borrow_request_id ?? row.id ?? '--'}`;
    const titleEl = document.getElementById('returnProofTitle');
    if (titleEl) {
        titleEl.textContent = `Return Proof for ${requestCode}`;
    }

    const notesEl = document.getElementById('viewReturnProofNotes');
    if (notesEl) {
        const trimmed = (row.return_proof_notes || '').trim();
        notesEl.textContent = trimmed !== '' ? trimmed : '—';
    }

    const viewer = document.getElementById('returnProofViewer');
    const image = document.getElementById('returnProofImage');
    const fallback = document.getElementById('returnProofFallback');
    const link = document.getElementById('returnProofDownloadLink');

    const rawUrl = (row.return_proof_url || row.return_proof_path || '').trim();
    let proofUrl = '';
    if (rawUrl) {
        try {
            const url = new URL(rawUrl, window.location.origin);
            proofUrl = url.href;
        } catch (_) {
            proofUrl = rawUrl;
        }
    }

    if (viewer) {
        viewer.classList.add('hidden');
        viewer.src = '';
    }
    if (image) {
        image.classList.add('hidden');
        image.removeAttribute('src');
    }
    if (fallback) {
        fallback.classList.remove('hidden');
        fallback.textContent = proofUrl
            ? 'Proof preview unavailable. Use the download link above to view the file.'
            : 'No return proof has been uploaded yet.';
    }

    if (link) {
        if (proofUrl) {
            link.href = proofUrl;
            link.classList.remove('hidden');
            const downloadBase = requestCode ? requestCode.replace(/[^A-Z0-9-]/gi, '-') : 'return-proof';
            let extension = '';
            try {
                const parsed = new URL(proofUrl, window.location.origin);
                const pathname = parsed.pathname || '';
                const match = pathname.match(/\.([a-z0-9]+)(?:$|\?)/i);
                if (match) {
                    extension = match[1];
                }
            } catch (_) {
                const match = proofUrl.match(/\.([a-z0-9]+)(?:$|\?)/i);
                if (match) {
                    extension = match[1];
                }
            }

            const filename = extension ? `${downloadBase}-return-proof.${extension}` : `${downloadBase}-return-proof`;
            link.setAttribute('download', filename);
        } else {
            link.href = '#';
            link.classList.add('hidden');
            link.removeAttribute('download');
        }
    }

    if (proofUrl) {
        let detectionPath = proofUrl;
        try {
            const parsed = new URL(proofUrl, window.location.origin);
            detectionPath = parsed.pathname || parsed.href || proofUrl;
        } catch (_) {
            // fall back to raw value
        }

        const isImage = /\.(png|jpe?g|webp|gif)$/i.test(detectionPath);
        if (isImage && image) {
            image.src = proofUrl;
            image.classList.remove('hidden');
            if (fallback) fallback.classList.add('hidden');
        } else if (viewer) {
            viewer.src = proofUrl;
            viewer.classList.remove('hidden');
            if (fallback) fallback.classList.add('hidden');
        }
    }
}

function populateViewModal(data = {}) {
    const setText = (id, value, fallback = '--') => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value && value !== '' ? value : fallback;
        }
    };

    const requestLabel = formatRequestCode(data) || data.formatted_request_id || `#${data.borrow_request_id ?? data.walk_in_request_id ?? '--'}`;
    setText('view-request-id-display', requestLabel);
    const typeLabel = String(data.request_type || '').toLowerCase() === 'walk-in' ? 'Walk-in Request' : 'Online Request';
    setText('view-request-type-label', typeLabel);

    setText('view-borrower-name', data.borrower || 'Unknown');
    setText('view-borrower-email', data.borrower_email ? `Email: ${data.borrower_email}` : 'Email: --');
    setText('view-borrower-phone', data.borrower_phone ? `Phone: ${data.borrower_phone}` : 'Phone: --');
    setText('view-borrower-address', data.borrower_address ? `Address: ${data.borrower_address}` : 'Address: --');

    const statusBadge = document.getElementById('view-status-badge');
    if (statusBadge) {
        statusBadge.innerHTML = '';
        statusBadge.appendChild(renderStatusBadge(data.status, data.status));
    }
    const deliveryBadge = document.getElementById('view-delivery-status-badge');
    if (deliveryBadge) {
        deliveryBadge.innerHTML = '';
        deliveryBadge.appendChild(renderStatusBadge(data.delivery_status, formatDeliveryStatus(data.delivery_status)));
    }

    setText('view-condition-summary', data.condition_summary || '--');
    setText('view-borrow-date', formatDate(data.borrow_date));
    setText('view-return-date', formatDate(data.return_date));
    setText('view-usage-time', data.time_of_usage || '--');
    setText('view-purpose-office', data.purpose_office || '--');
    setText('view-purpose', data.purpose || '--');
    setText('view-event-location', data.location || data.address || '--');
    setText('view-manpower-count', data.manpower_count != null ? String(data.manpower_count) : '--');
    setText('view-requested-at', formatDateTime(data.requested_at));
    setText('view-dispatched-at', formatDateTime(data.dispatched_at));
    setText('view-delivered-at', formatDateTime(data.delivered_at));

    const items = Array.isArray(data.items) ? data.items : [];
    const itemsCount = `${items.length || 0} item${items.length === 1 ? '' : 's'}`;
    setText('view-items-count', itemsCount);

    const tbody = document.getElementById('view-items-tbody');
    if (tbody) {
        tbody.innerHTML = '';
        if (!items.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 4;
            td.className = 'px-4 py-6 text-center text-gray-500';
            td.textContent = 'No items recorded for this request.';
            tr.appendChild(td);
            tbody.appendChild(tr);
        } else {
            items.forEach((item) => {
                const tr = document.createElement('tr');
                tr.className = 'divide-x';

                const tdProperty = document.createElement('td');
                tdProperty.className = 'px-4 py-2 font-medium text-gray-800';
                tdProperty.textContent = item.property_label || 'Untracked Item';
                tr.appendChild(tdProperty);

                const tdName = document.createElement('td');
                tdName.className = 'px-4 py-2 text-gray-700';
                tdName.textContent = item.item_name || 'Unknown';
                tr.appendChild(tdName);

                const tdCondition = document.createElement('td');
                tdCondition.className = 'px-4 py-2';
                tdCondition.appendChild(renderConditionBadge(item.condition, item.condition_label));
                tr.appendChild(tdCondition);

                const tdInventory = document.createElement('td');
                tdInventory.className = 'px-4 py-2';
                tdInventory.appendChild(renderInventoryStatusBadge(item.inventory_status, item.inventory_status_label));
                tr.appendChild(tdInventory);

                tbody.appendChild(tr);
            });
        }
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
    CHECKBOXES_VISIBLE = false;
    
    // Reset UI controls
    const enableSelectionCheckbox = document.getElementById('manage-enable-selection');
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    
    if (enableSelectionCheckbox) enableSelectionCheckbox.checked = false;
    if (bulkConditionSelect) bulkConditionSelect.value = '';

    MANAGE_BORROW_ID = data.id ?? data.borrow_request_id ?? null;
    if (MANAGE_BORROW_ID != null) {
        VIEW_DETAILS_CACHE.set(String(MANAGE_BORROW_ID), data);
    }
    MANAGE_ITEMS = (Array.isArray(data.items) ? data.items : []).map((item) => {
        const propertySource = item.property_number ?? item.property_label ?? '';
        const serialSource = item.serial ?? '';
        const inventoryStatus = normalizeInventoryStatus(item.inventory_status);
        const baseCanUpdate = item.can_update !== false;
        const lockReason = baseCanUpdate
            ? ''
            : (item.lock_reason || LOCK_REASON_HISTORY_DEFAULT);

        return {
            ...item,
            inventory_status: inventoryStatus,
            inventory_status_label: item.inventory_status_label || formatInventoryStatusLabel(item.inventory_status),
            can_update: baseCanUpdate,
            lock_reason: lockReason,
            is_latest_record: item.is_latest_record !== false,
            _search: {
                propertyRaw: String(propertySource || '').toLowerCase(),
                propertyNormalized: normalizeSearchTerm(propertySource),
                serialRaw: String(serialSource || '').toLowerCase(),
                serialNormalized: normalizeSearchTerm(serialSource),
            },
        };
    });
    MANAGE_FILTER = data.default_item || '';

    const hasEditableItems = MANAGE_ITEMS.some((item) => item.can_update);
    const bulkUpdateButtonEl = document.getElementById('manage-bulk-update-btn');

    if (enableSelectionCheckbox) {
        enableSelectionCheckbox.disabled = !hasEditableItems;
        if (!hasEditableItems) {
            enableSelectionCheckbox.checked = false;
        }
    }
    if (bulkConditionSelect) {
        bulkConditionSelect.disabled = !hasEditableItems;
        if (!hasEditableItems) {
            bulkConditionSelect.value = '';
        }
    }
    if (bulkUpdateButtonEl) {
        bulkUpdateButtonEl.disabled = true;
    }

    const requestDisplay = formatRequestCode(data) || (data.borrow_request_id ?? data.id ?? '--');
    setText('manage-request-id', requestDisplay);
    setText('manage-borrower', data.borrower ?? 'Unknown');

    setText('manage-address', data.address);

    const statusBadge = document.getElementById('manage-status-badge');
    if (statusBadge) {
        statusBadge.innerHTML = '';
        statusBadge.appendChild(renderStatusBadge(data.status));
    }

    setText('manage-return-timestamp', formatDateTime(data.return_timestamp));

    MANAGE_SEARCH_TERM = { raw: '', normalized: '' };
    const searchInput = document.getElementById('manage-items-search');
    if (searchInput) {
        searchInput.value = '';
        searchInput.placeholder = 'Search property number';
        searchInput.onfocus = () => {
            searchInput.placeholder = 'Type to Search';
        };
        searchInput.onblur = () => {
            searchInput.placeholder = 'Search property number';
        };
        searchInput.oninput = (event) => {
            const rawValue = String(event.target.value || '');
            MANAGE_SEARCH_TERM = {
                raw: rawValue.toLowerCase(),
                normalized: normalizeSearchTerm(rawValue),
            };
            renderManageRows();
        };
    }

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
    const searchRaw = (MANAGE_SEARCH_TERM.raw || '').trim();
    const searchNormalized = MANAGE_SEARCH_TERM.normalized || '';
    const hasRawSearch = searchRaw.length > 0;
    const hasNormalizedSearch = searchNormalized.length > 0;
    const hasSearch = hasRawSearch || hasNormalizedSearch;

    const filteredEntries = [];

    MANAGE_ITEMS.forEach((item) => {
        const matchesFilter = !MANAGE_FILTER || item.item_name === MANAGE_FILTER;
        if (!matchesFilter) return;

        const searchMeta = item._search || {
            propertyRaw: String(item.property_number || item.property_label || '').toLowerCase(),
            propertyNormalized: normalizeSearchTerm(item.property_number || item.property_label || ''),
            serialRaw: String(item.serial || '').toLowerCase(),
            serialNormalized: normalizeSearchTerm(item.serial),
        };

        const { propertyRaw, propertyNormalized, serialRaw, serialNormalized } = searchMeta;

        let matchPriority = 0;
        let bestIndex = Number.POSITIVE_INFINITY;

        if (hasRawSearch) {
            const propertyIndex = propertyRaw.indexOf(searchRaw);
            if (propertyIndex >= 0) {
                matchPriority = Math.max(matchPriority, 3);
                bestIndex = Math.min(bestIndex, propertyIndex);
            }

            const serialIndex = serialRaw.indexOf(searchRaw);
            if (serialIndex >= 0) {
                matchPriority = Math.max(matchPriority, 1);
                bestIndex = Math.min(bestIndex, serialIndex);
            }
        }

        if (hasNormalizedSearch) {
            const propertyNormalizedIndex = propertyNormalized.indexOf(searchNormalized);
            if (propertyNormalizedIndex >= 0) {
                matchPriority = Math.max(matchPriority, 2);
                bestIndex = Math.min(bestIndex, propertyNormalizedIndex);
            }

            const serialNormalizedIndex = serialNormalized.indexOf(searchNormalized);
            if (serialNormalizedIndex >= 0) {
                matchPriority = Math.max(matchPriority, 1);
                bestIndex = Math.min(bestIndex, serialNormalizedIndex);
            }
        }

        if (hasSearch && matchPriority === 0 && !Number.isFinite(bestIndex)) {
            return;
        }

        filteredEntries.push({
            item,
            priority: matchPriority,
            index: Number.isFinite(bestIndex) ? bestIndex : Number.POSITIVE_INFINITY,
        });
    });

    if (hasSearch) {
        filteredEntries.sort((a, b) => {
            if (a.priority !== b.priority) {
                return b.priority - a.priority;
            }
            if (a.index !== b.index) {
                return a.index - b.index;
            }
            const labelA = String(a.item.property_label || a.item.property_number || '').toLowerCase();
            const labelB = String(b.item.property_label || b.item.property_number || '').toLowerCase();
            return labelA.localeCompare(labelB);
        });
    }

    const rows = filteredEntries.map((entry) => entry.item);

    const selectionAllowed = MANAGE_ITEMS.some((entry) => entry.can_update);
    const selectionActive = selectionAllowed && SELECTION_ENABLED;
    const checkboxesEnabled = selectionAllowed && CHECKBOXES_VISIBLE;

    if (!selectionActive && SELECTED_INSTANCES.size > 0) {
        SELECTED_INSTANCES.clear();
    }

    const checkboxHeader = document.getElementById('manage-checkbox-header');
    if (checkboxHeader) {
        checkboxHeader.style.display = checkboxesEnabled ? '' : 'none';
    }

    if (!rows.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = checkboxesEnabled ? 4 : 3;
        td.className = 'px-4 py-4 text-center text-gray-500';
        td.textContent = 'No property numbers for this selection.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    rows.forEach((item) => {
        const inventoryStatus = normalizeInventoryStatus(item.inventory_status);
        const canUpdate = item.can_update !== false;
        const lockReason = canUpdate
            ? ''
            : (item.lock_reason || LOCK_REASON_HISTORY_DEFAULT);

        item.inventory_status = inventoryStatus;
        item.can_update = canUpdate;
        item.lock_reason = lockReason;

        if (!canUpdate && SELECTED_INSTANCES.has(item.id)) {
            SELECTED_INSTANCES.delete(item.id);
        }

        const tr = document.createElement('tr');
        tr.className = 'divide-x transition-colors';
        tr.dataset.instanceId = item.id;
        tr.dataset.condition = item.condition || 'pending';
        tr.dataset.inventoryStatus = inventoryStatus;
        tr.dataset.locked = canUpdate ? '0' : '1';
        if (lockReason) {
            tr.dataset.lockReason = lockReason;
            tr.title = lockReason;
        }

        if (!canUpdate) {
            tr.classList.add('opacity-70', 'cursor-not-allowed');
        } else if (selectionActive) {
            tr.classList.add('cursor-pointer');
        }

        if (checkboxesEnabled) {
            const tdCheckbox = document.createElement('td');
            tdCheckbox.className = 'px-4 py-3 text-center';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'manage-row-checkbox w-4 h-4 text-purple-600 border-2 border-purple-300 bg-white rounded shadow focus:ring-purple-500';
            checkbox.dataset.instanceId = item.id;
            checkbox.checked = canUpdate && selectionActive && SELECTED_INSTANCES.has(item.id);
            checkbox.disabled = !canUpdate || !selectionActive;
            if (!canUpdate) {
                checkbox.dataset.blocked = '1';
                if (lockReason) {
                    checkbox.title = lockReason;
                }
            }
            checkbox.addEventListener('change', (e) => {
                if (!canUpdate || !selectionActive) {
                    e.target.checked = false;
                    if (lockReason) {
                        window.showToast(lockReason, 'warning');
                    }
                    return;
                }
                if (e.target.checked) {
                    SELECTED_INSTANCES.add(item.id);
                } else {
                    SELECTED_INSTANCES.delete(item.id);
                }
                updateRowHighlight(tr, e.target.checked);
                updateBulkUpdateButton();
            });
            tdCheckbox.appendChild(checkbox);
            tr.appendChild(tdCheckbox);
        }

        if (selectionActive && SELECTED_INSTANCES.has(item.id) && canUpdate) {
            tr.classList.add('bg-purple-200');
        }

        if (selectionActive) {
            tr.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'BUTTON' || e.target.closest('select') || e.target.closest('button') || e.target.closest('input[type="checkbox"]')) {
                    return;
                }
                if (!canUpdate) {
                    if (lockReason) {
                        window.showToast(lockReason, 'warning');
                    }
                    return;
                }

                const instanceId = item.id;
                const isSelected = SELECTED_INSTANCES.has(instanceId);

                if (isSelected) {
                    SELECTED_INSTANCES.delete(instanceId);
                    updateRowHighlight(tr, false);
                    const checkbox = tr.querySelector('.manage-row-checkbox');
                    if (checkbox) checkbox.checked = false;
                } else {
                    SELECTED_INSTANCES.add(instanceId);
                    updateRowHighlight(tr, true);
                    const checkbox = tr.querySelector('.manage-row-checkbox');
                    if (checkbox) checkbox.checked = true;
                }

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
        if (lockReason) {
            const note = document.createElement('div');
            note.className = 'mt-1 text-xs italic text-gray-500';
            note.textContent = lockReason;
            tdCondition.appendChild(note);
        }
        tr.appendChild(tdCondition);

        tbody.appendChild(tr);
    });

    const selectionToggle = document.getElementById('manage-enable-selection');
    if (selectionToggle) {
        selectionToggle.disabled = !selectionAllowed;
        if (!selectionAllowed) {
            selectionToggle.checked = false;
        }
    }

    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    if (bulkConditionSelect) {
        bulkConditionSelect.disabled = !selectionAllowed;
        if (!selectionAllowed) {
            bulkConditionSelect.value = '';
        }
    }

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

function updateBulkUpdateButton() {
    const bulkUpdateBtn = document.getElementById('manage-bulk-update-btn');
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');
    
    if (bulkUpdateBtn && bulkConditionSelect) {
        const hasSelection = SELECTED_INSTANCES.size > 0;
        const hasCondition = bulkConditionSelect.value !== '';
        bulkUpdateBtn.disabled = !hasSelection || !hasCondition;
    }
}

async function bulkUpdateInstances() {
    const bulkUpdateBtn = document.getElementById('manage-bulk-update-btn');
    const bulkConditionSelect = document.getElementById('manage-bulk-condition');

    if (!bulkConditionSelect) return;

    const condition = bulkConditionSelect.value;
    const ids = Array.from(SELECTED_INSTANCES);

    if (!condition) {
        window.showToast?.('Please select a condition to apply.', 'warning');
        return;
    }
    if (!ids.length) {
        window.showToast?.('Select at least one item to update.', 'warning');
        return;
    }

    const restoreButton = () => {
        if (!bulkUpdateBtn) return;
        bulkUpdateBtn.disabled = false;
    };

    if (bulkUpdateBtn) {
        bulkUpdateBtn.disabled = true;
    }

    let successCount = 0;
    let errorMessage = '';

    try {
        for (const id of ids) {
            const item = MANAGE_ITEMS.find((entry) => entry.id === id);
            if (item && item.can_update === false) {
                continue;
            }

            try {
                await updateInstance(id, condition, {
                    showToast: false,
                    renderRows: false,
                    updateTable: false,
                });
                successCount += 1;
            } catch (err) {
                console.error('Bulk update failed for instance', id, err);
                errorMessage = err?.message || 'Failed to update some items.';
            }
        }

        SELECTED_INSTANCES.clear();
        renderManageRows();
        updateBulkUpdateButton();

        if (successCount > 0) {
            window.showToast?.(`${successCount} item${successCount === 1 ? '' : 's'} updated.`, 'success');
            // Refresh the main table to reflect any summaries/status changes
            loadReturnItems(false);
        }

        if (errorMessage && successCount === 0) {
            window.showToast?.(errorMessage, 'error');
        } else if (errorMessage && successCount > 0) {
            window.showToast?.('Some items were not updated. Please retry if needed.', 'warning');
        }
    } finally {
        restoreButton();
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

async function showCollectConfirm(row) {
    PENDING_COLLECT_ID = row?.id ?? row?.borrow_request_id ?? null;
    const messageEl = document.getElementById('collectConfirmMessage');
    const itemsWrapper = document.getElementById('collectItemsWrapper');
    const itemsList = document.getElementById('collectItemsList');
    const itemsCountEl = document.getElementById('collectItemsCount');

    if (messageEl) {
        const borrowId = row?.borrow_request_id ?? row?.id ?? '--';
        const requestLabel = formatRequestCode(row) || `#${borrowId}`;
        messageEl.textContent = `Are you sure request ${requestLabel} has been picked up?`;
    }

    if (itemsWrapper) {
        itemsWrapper.classList.remove('hidden');
    }
    if (itemsList) {
        itemsList.innerHTML = '';
        itemsList.classList.remove('hidden');
        itemsList.innerHTML = '<li class="text-sm text-gray-500">Loading items...</li>';
    }
    if (itemsCountEl) {
        itemsCountEl.textContent = '';
        itemsCountEl.classList.add('hidden');
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'collectConfirmModal' }));

    if (!PENDING_COLLECT_ID) {
        if (itemsCountEl) {
            itemsCountEl.textContent = 'Unable to load items';
        }
        return;
    }

    const currentId = PENDING_COLLECT_ID;

    try {
        const details = await fetchReturnDetails(currentId);
        if (PENDING_COLLECT_ID !== currentId) {
            return;
        }

        const items = Array.isArray(details?.items) ? details.items : [];
        const requestItems = Array.isArray(details?.request_items) ? details.request_items : [];

        // Prefer user-confirmed received quantities when available
        const source = requestItems.length ? requestItems : items;

        // Aggregate by item name to show consolidated counts using received_quantity when present
        // Exclude manpower placeholder items
        const aggregated = source.reduce((acc, item) => {
            const name = (item?.name || item?.item_name || item?.item?.name || 'Item').toString();
            // Skip manpower placeholder
            if (name === '__SYSTEM_MANPOWER_PLACEHOLDER__') {
                return acc;
            }
            const qtyRaw = item?.received_quantity ?? item?.approved_quantity ?? item?.quantity ?? 0;
            const qtyNum = Number(qtyRaw);
            const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1; // default to 1 per item row
            if (!acc[name]) {
                acc[name] = 0;
            }
            acc[name] += qty;
            return acc;
        }, {});

        const aggregatedEntries = Object.entries(aggregated);
        const totalCount = aggregatedEntries.reduce((sum, [, qty]) => sum + (Number.isFinite(qty) ? qty : 0), 0);

        if (itemsCountEl) {
            if (totalCount > 0) {
                const label = totalCount === 1 ? '1 item to collect' : `${totalCount} items to collect`;
                itemsCountEl.textContent = label;
                itemsCountEl.classList.remove('hidden');
            } else {
                itemsCountEl.textContent = '';
                itemsCountEl.classList.add('hidden');
            }
        }

        if (itemsList) {
            if (!aggregatedEntries.length) {
                itemsList.innerHTML = '<li class="text-sm text-gray-500">No items found for this request.</li>';
            } else {
                itemsList.innerHTML = aggregatedEntries.map(([name, qty]) => {
                    const safeQty = Number.isFinite(qty) && qty > 0 ? qty : 1;
                    const label = `${name} (x${safeQty})`;
                    return `<li class="text-sm text-gray-800 list-disc list-inside">${label}</li>`;
                }).join('');
            }
        }
    } catch (error) {
        console.error('Failed to load borrowed items for confirmation', error);
        if (itemsCountEl) {
            itemsCountEl.textContent = 'Unable to load items';
        }
    }
}

async function collectBorrowRequest(id, button) {
    if (!id) return;
    if (button) button.disabled = true;
    try {
        // Detect if it's a walk-in request (ID starts with 'W')
        const idStr = String(id);
        const isWalkIn = idStr.startsWith('W');
        const actualId = isWalkIn ? idStr.substring(1) : idStr;
        
        // Build the correct URL based on request type
        const url = isWalkIn 
            ? `${COLLECT_BASE}/walk-in/${encodeURIComponent(actualId)}/collect`
            : `${COLLECT_BASE}/${encodeURIComponent(actualId)}/collect`;
        
        const res = await fetch(url, {
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
        window.showToast(data.message || 'Items marked as returned successfully.', 'success');
        await loadReturnItems(false);
        VIEW_DETAILS_CACHE.delete(idStr);
    } catch (error) {
        console.error('Failed to mark items as collected', error);
        window.showToast(error.message || 'Failed to mark items as collected. Please try again.', 'error');
    } finally {
        if (button) {
            button.disabled = false;
        }
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
            window.showToast(data.message || 'Item condition updated successfully.', 'success');
        }

        MANAGE_ITEMS = MANAGE_ITEMS.map((item) => {
            if (item.id === instanceId) {
                const updatedStatus = normalizeInventoryStatus(data.inventory_status || item.inventory_status);
                const explicitCanUpdate = typeof data.can_update === 'boolean' ? data.can_update : undefined;
                const normalizedCanUpdate = explicitCanUpdate !== undefined
                    ? explicitCanUpdate
                    : (item.can_update !== false);
                const derivedLockReason = normalizedCanUpdate
                    ? ''
                    : (data.lock_reason || item.lock_reason || LOCK_REASON_HISTORY_DEFAULT);

                return {
                    ...item,
                    condition,
                    condition_label: data.condition_label || item.condition_label,
                    inventory_status: updatedStatus,
                    status: data.status || data.inventory_status || item.status,
                    inventory_status_label: data.inventory_status_label || formatInventoryStatusLabel(data.inventory_status || item.inventory_status),
                    can_update: normalizedCanUpdate,
                    lock_reason: derivedLockReason,
                    is_latest_record: typeof data.is_latest_record === 'boolean' ? data.is_latest_record : item.is_latest_record,
                };
            }
            return item;
        });
        if (renderRows) {
            renderManageRows();
            updateBulkUpdateButton();
        }
        if (updateTable) {
            applyBorrowSummaryUpdate(data);
        }
        if (MANAGE_BORROW_ID != null) {
            VIEW_DETAILS_CACHE.delete(String(MANAGE_BORROW_ID));
        }
        window.dispatchEvent(new CustomEvent('return-items:condition-updated', {
            detail: { instanceId, condition, response: data },
        }));
        return data;
    } catch (error) {
        console.error('Failed to update instance condition', error);
        if (showToast) {
            window.showToast(error.message || 'Failed to update item condition. Please try again.', 'error');
        }
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('returnItemsTableBody')) return;

    loadReturnItems();

    // Live search wiring
    const riSearch = document.getElementById('return-items-live-search');
    if (riSearch) {
        riSearch.addEventListener('input', (e) => {
            const term = (e.target.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#returnItemsTableBody tr[data-request-code]');
            let visible = 0;
            rows.forEach(r => {
                const borrowerCell = r.querySelector('td[data-column="borrower"]') || r.querySelector('td:nth-child(2)');
                const statusCell = r.querySelector('td[data-column="status"]') || r.querySelector('td:nth-child(4)');
                const borrowerText = (borrowerCell?.textContent || '').toLowerCase();
                const statusText = (statusCell?.textContent || '').toLowerCase();
                const requestCode = (r.dataset.requestCode || '').toLowerCase();
                const match = !term || borrowerText.includes(term) || requestCode.includes(term) || statusText.includes(term);
                r.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            const existing = document.getElementById('no-results-row-return');
            if (visible === 0) {
                if (!existing) {
                    const tr = document.createElement('tr');
                    tr.id = 'no-results-row-return';
                    tr.innerHTML = '<td colspan="5" class="px-6 py-8 text-center text-gray-500">No returns found</td>';
                    document.getElementById('returnItemsTableBody')?.appendChild(tr);
                }
            } else if (existing) {
                existing.remove();
            }
        });
        riSearch.addEventListener('focus', function(){ this.placeholder = 'Type to Search'; });
        riSearch.addEventListener('blur', function(){ this.placeholder = 'Search borrower or request ID'; });
    }

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

    // Show or hide checkboxes when selection mode changes
    const showCheckboxes = (show) => {
        CHECKBOXES_VISIBLE = show;
        renderManageRows();
    };

    const enableSelectionCheckbox = document.getElementById('manage-enable-selection');
    if (enableSelectionCheckbox) {
        enableSelectionCheckbox.addEventListener('change', (e) => {
            SELECTION_ENABLED = e.target.checked;

            if (SELECTION_ENABLED) {
                showCheckboxes(true);
            } else {
                SELECTED_INSTANCES.clear();
                showCheckboxes(false);
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
});

window.addEventListener('realtime:borrow-request-status-updated', () => {
    loadReturnItems(false);
});

window.loadAdminReturnItems = loadReturnItems;
window.openManageReturnModal = openManageModal;
window.openViewReturnModal = openViewModal;
