// resources/js/my-borrowed-items.js
const LIST_ROUTE = window.LIST_ROUTE || '/user/my-borrowed-items/list';
const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const MANPOWER_PLACEHOLDER = '__SYSTEM_MANPOWER_PLACEHOLDER__';

let ITEMS_CACHE = [];
const confirmReceiveState = {
    requestId: null,
    requestType: null,
    draft: null, // holds latest input values keyed by request id
};

const markReturnState = {
    requestId: null,
};

// ---------- helpers ----------
export function formatDate(dateStr) {
    if (!dateStr) return "N/A";
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    const month = d.toLocaleString("en-US", { month: "short" });
    const day = d.getDate();
    const year = d.getFullYear();
    return `${month}. ${day}, ${year}`;
}

function computeOverdueDays(req) {
    if (!req) return 0;
    // Only consider overdue when delivery has completed (delivered)
    const delivery = (req.delivery_status || '').toLowerCase();
    const status = (req.status || '').toLowerCase();
    if (delivery !== 'delivered' && status !== 'delivered') return 0;
    const value = req.return_date || req.returnDate || null;
    if (!value) return 0;
    const ret = new Date(value);
    if (Number.isNaN(ret.getTime())) return 0;
    const now = new Date();
    const startNow = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const startRet = new Date(ret.getFullYear(), ret.getMonth(), ret.getDate());
    const diffMs = startNow - startRet;
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    return days > 0 ? days : 0;
}

function isOverdue(req) {
    return computeOverdueDays(req) > 0;
}

function formatUsageRange(range) {
    if (!range || typeof range !== 'string') return '—';
    const trimmed = range.trim();
    if (!trimmed.includes('-')) return trimmed || '—';
    const [startRaw, endRaw] = trimmed.split('-');

    const formatTime = (value) => {
        if (!value) return null;
        const [hours, minutes] = value.split(':');
        if (hours === undefined || minutes === undefined) return null;
        const date = new Date();
        date.setHours(Number(hours), Number(minutes), 0, 0);
        if (Number.isNaN(date.getTime())) return null;
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    };

    const startLabel = formatTime(startRaw);
    const endLabel = formatTime(endRaw);
    if (!startLabel || !endLabel) return trimmed || '—';
    return `${startLabel} - ${endLabel}`;
}

function formatBorrowRequestCodeLocal(req) {
    if (!req) return '';
    const formatted = typeof req.formatted_request_id === 'string' ? req.formatted_request_id.trim() : '';
    if (formatted) return formatted;
    const rawId = req.id ?? null;
    if (!rawId) return '';
    const prefix = (req.type || '').toLowerCase() === 'walkin' ? 'WI' : 'BR';
    return `${prefix}-${String(rawId).padStart(4, '0')}`;
}

function findCachedRequest(id, type = null) {
    if (!Number.isFinite(Number(id))) return null;
    const targetType = type ? String(type).toLowerCase() : null;
    return ITEMS_CACHE.find((entry) => {
        const sameId = Number(entry?.id) === Number(id);
        if (!sameId) return false;
        if (!targetType) return true;
        return (entry?.type || 'borrow').toLowerCase() === targetType;
    }) || null;
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function isManpowerEntry(item) {
    if (!item) return false;
    if (item.is_manpower) return true;
    const baseName = item?.item?.name || item?.name || '';
    return baseName === MANPOWER_PLACEHOLDER;
}

function sanitizeToTwoDigits(value) {
    if (value === null || value === undefined) return '';
    const digits = String(value).replace(/[^0-9]/g, '');
    return digits.slice(0, 2);
}

function clampDigitsToMax(digits, maxValue) {
    if (digits === '') return '';
    if (!Number.isFinite(maxValue)) return digits;
    const numeric = Number(digits);
    if (!Number.isFinite(numeric)) return digits;
    if (numeric > maxValue) {
        return sanitizeToTwoDigits(maxValue);
    }
    return digits;
}

function enforceTwoDigitInput(input, options = {}) {
    if (!input) return;
    const maxValue = Number(options.max);
    input.addEventListener('input', () => {
        let digits = input.value.replace(/[^0-9]/g, '').slice(0, 2);
        if (Number.isFinite(maxValue)) {
            digits = clampDigitsToMax(digits, maxValue);
        }
        input.value = digits;
    });
}

function resolveBorrowItemName(item) {
    if (!item || typeof item !== 'object') {
        return 'Unknown';
    }

    const provided = typeof item.display_name === 'string' ? item.display_name.trim() : '';
    if (provided) {
        return provided;
    }

    const roleName = (item.manpower_role || item.manpower_role_name || '').trim();
    const baseName = (item?.item?.name || item.name || '').trim();

    if (isManpowerEntry(item)) {
        return roleName || 'Manpower';
    }

    if (baseName === MANPOWER_PLACEHOLDER) {
        return roleName || 'Manpower';
    }

    if (baseName) {
        return baseName;
    }

    return roleName || 'Unknown';
}

function getBadgeHtml(status) {
    const st = (status || '').toLowerCase();
    if (typeof window.renderStatusBadge === 'function') {
        try {
            return window.renderStatusBadge(st);
        } catch (err) {
            console.error('renderStatusBadge failed', err);
        }
    }
    // Fallback with icon mapping
    const statusIcons = {
        'approved': 'fa-check-circle',
        'pending': 'fa-clock',
        'return_pending': 'fa-reply',
        'returned': 'fa-arrow-left',
        'rejected': 'fa-times-circle',
        'qr_verified': 'fa-check-circle',
        'validated': 'fa-check-circle',
        'dispatched': 'fa-truck',
        'delivered': 'fa-truck',
        'return_pending': 'fa-reply',
        'not_received': 'fa-triangle-exclamation',
    };
    const statusColors = {
        'approved': 'bg-green-100 text-green-700',
        'pending': 'bg-yellow-100 text-yellow-700',
        'return_pending': 'bg-blue-100 text-blue-700',
        'returned': 'bg-emerald-100 text-emerald-700',
        'rejected': 'bg-red-100 text-red-700',
        'qr_verified': 'bg-green-100 text-green-700',
        'validated': 'bg-blue-100 text-blue-700',
        'dispatched': 'bg-indigo-100 text-indigo-700',
        'delivered': 'bg-blue-100 text-blue-800',
        'not_received': 'bg-red-100 text-red-700',
    };
    const icon = statusIcons[st] || 'fa-question-circle';
    const color = statusColors[st] || 'bg-gray-100 text-gray-700';
    const label = st ? (st.charAt(0).toUpperCase() + st.slice(1).replace(/_/g, ' ')) : '—';
    return `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full ${color}"><i class="fas ${icon} text-xs"></i><span>${escapeHtml(label)}</span></span>`;
}

function getRequestTypeBadge(type) {
    const key = (type || 'borrow').toLowerCase();
    const label = key === 'walkin' ? 'Walk-in' : 'Online';
    const color = key === 'walkin'
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-blue-100 text-blue-700';
    const icon = key === 'walkin' ? 'fa-building' : 'fa-wifi';
    return `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full ${color}"><i class="fas ${icon} text-xs"></i><span>${escapeHtml(label)}</span></span>`;
}

function showError(m){ window.showToast(m, 'error'); }
function showSuccess(m){ window.showToast(m, 'success'); }

function appendEmptyState(tbody, templateId, colspan, fallbackText) {
    const template = document.getElementById(templateId);
    tbody.innerHTML = '';
    if (template?.content?.firstElementChild) {
        tbody.appendChild(template.content.firstElementChild.cloneNode(true));
    } else {
        tbody.innerHTML = `<tr><td colspan="${colspan}" class="py-10 text-center text-gray-500">${fallbackText}</td></tr>`;
    }
}

// ---------- fetch ----------
export async function loadMyBorrowedItems() {
    try {
        const res = await fetch(LIST_ROUTE, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            const err = await res.json().catch(()=>null);
            throw err?.message || `HTTP ${res.status}`;
        }
        const json = await res.json();
        ITEMS_CACHE = Array.isArray(json) ? json : (Array.isArray(json.data) ? json.data : []);
        renderItems();
    } catch (e) {
        console.error('loadMyBorrowedItems error', e);
        showError("Failed to load borrowed items. Please refresh the page.");
    }
}

// Pagination removed - displaying all results with scrolling

// ---------- create buttons from template ----------
function createButtonFromTemplate(templateId, req) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    const btn = frag.querySelector('[data-action]');
    if (!btn) return frag;
    const action = btn.getAttribute('data-action');
    const id = typeof req === 'object' ? req?.id : req;
    const type = typeof req === 'object' ? (req?.type || null) : null;

        if (action === 'view') {
            btn.addEventListener('click', () => viewBorrowDetails(id));
    } else if (action === 'return') {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            // Fallback handle in case return action templates linger
            showError('Return processing is handled by the administrator. Please contact support if needed.');
        });
    } else if (action === 'print') {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const url = `/user/my-borrowed-items/${encodeURIComponent(id)}/print`;
            window.open(url, '_blank');
        });
    } else if (action === 'routing-slip') {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const isWalkIn = (type || '').toLowerCase() === 'walkin';
            const url = isWalkIn
                ? `/user/my-borrowed-items/walk-in/${encodeURIComponent(id)}/routing-slip`
                : `/user/my-borrowed-items/${encodeURIComponent(id)}/routing-slip`;
            window.open(url, '_blank');
        });
    } else if (action === 'mark-returned') {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            openMarkReturnModal(id);
        });
    } else if (action === 'confirm-delivery') {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            openConfirmReceiveModal(req);
        });
    } else if (action === 'report-not-received') {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            openConfirmReportNotReceivedModal(id, { type });
        });
    }
    return frag;
}

function openConfirmReceiveModal(requestInput, options = {}) {
    const numericId = Number(requestInput?.id ?? requestInput);
    if (!Number.isFinite(numericId)) {
        confirmReceiveState.requestId = null;
        confirmReceiveState.requestType = null;
        return;
    }

    const requestType = typeof requestInput === 'object'
        ? (requestInput?.type || null)
        : (options?.type || null);

    const request = typeof requestInput === 'object'
        ? requestInput
        : findCachedRequest(numericId, requestType);

    confirmReceiveState.requestId = numericId;
    confirmReceiveState.requestType = request?.type || requestType || null;

    if (!options?.preserveDraft && confirmReceiveState.draft && confirmReceiveState.draft.requestId !== numericId) {
        confirmReceiveState.draft = null;
    }

    const labelEl = document.getElementById('confirmReceiveRequestLabel');
    if (labelEl) {
        const code = request ? (formatBorrowRequestCodeLocal(request) || `#${request.id}`) : `#${numericId}`;
        labelEl.textContent = code;
    }

    const reportBtn = document.getElementById('confirmReceiveReportBtn');
    const isWalkIn = (request?.type || requestType || '').toLowerCase() === 'walkin';
    if (reportBtn) {
        const allowReport = isWalkIn ? ['delivered', 'dispatched'].includes((request?.delivery_status || '').toLowerCase()) : true;
        reportBtn.classList.toggle('hidden', !allowReport);
        reportBtn.disabled = !allowReport;
    }

    renderConfirmReceiveItems(request);

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirmReceiveModal' }));
}

function openConfirmReportNotReceivedModal(requestId, options = {}) {
    const numericId = Number(requestId);
    if (!Number.isFinite(numericId)) return;
    confirmReportState.requestId = numericId;
    confirmReportState.origin = options.origin || null;
    confirmReportState.requestType = options.type || null;
    const reasonInput = document.getElementById('confirmReportNotReceivedReason');
    if (reasonInput) {
        reasonInput.value = '';
    }
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirmReportNotReceivedModal' }));
}

const confirmReportState = { requestId: null, origin: null, requestType: null };

function renderConfirmReceiveItems(request) {
    const container = document.getElementById('confirmReceiveItemsContainer');
    if (!container) return;

    container.innerHTML = '';

    const items = Array.isArray(request?.items)
        ? request.items.filter((item) => !isManpowerEntry(item))
        : [];

    if (!items.length) {
        container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No physical items require confirmation.</p>';
        return;
    }

    items.forEach((item) => {
        const name = resolveBorrowItemName(item);
        const approvedQty = Number(item.approved_quantity ?? item.quantity ?? 0);
        const requestedQty = Number(item.requested_quantity ?? item.quantity ?? 0);
        const defaultValue = approvedQty;
        const sanitizedDefault = clampDigitsToMax(sanitizeToTwoDigits(defaultValue), approvedQty);
        const row = document.createElement('div');
        row.className = 'rounded-lg border border-gray-200 bg-white px-4 py-3 space-y-2 dark:border-gray-700 dark:bg-gray-900';

        const header = document.createElement('div');
        header.className = 'flex items-center justify-between gap-2';

        const nameEl = document.createElement('span');
        nameEl.className = 'text-sm font-semibold text-gray-900 dark:text-gray-100';
        nameEl.textContent = name;

        const metaEl = document.createElement('span');
        metaEl.className = 'text-xs text-gray-500 dark:text-gray-400';
        const metaParts = [`Requested ${requestedQty}`];
        metaParts.push(`Approved ${approvedQty}`);
        metaEl.textContent = metaParts.join(' • ');

        header.appendChild(nameEl);
        header.appendChild(metaEl);

        const inputRow = document.createElement('div');
        inputRow.className = 'flex items-center gap-2';

        const input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.maxLength = 2;
        input.value = sanitizedDefault;
        input.className = 'gov-input w-24 text-sm';
        input.setAttribute('pattern', '[0-9]*');
        input.required = true;
        input.title = `Enter received quantity for ${name}`;
        input.dataset.receiveInput = '1';
        const borrowRequestItemId = Number(item.borrow_request_item_id ?? item.id ?? null);
        if (Number.isFinite(borrowRequestItemId)) {
            input.dataset.borrowRequestItemId = String(borrowRequestItemId);
        }
        input.dataset.approvedQuantity = String(approvedQty);
        input.dataset.itemName = name;
        input.setAttribute('aria-label', `Received quantity for ${name}`);
        enforceTwoDigitInput(input, { max: approvedQty });

        const denominator = document.createElement('span');
        denominator.className = 'text-sm text-gray-600 dark:text-gray-300 flex items-center gap-1';

        const slash = document.createElement('span');
        slash.className = 'text-gray-400';
        slash.textContent = '/';

        const approvedBadge = document.createElement('span');
        approvedBadge.className = 'font-semibold text-gray-900 dark:text-gray-100 border-b border-dashed border-gray-400 cursor-help';
        approvedBadge.title = 'Item Approved';
        approvedBadge.textContent = String(approvedQty);

        denominator.appendChild(slash);
        denominator.appendChild(approvedBadge);

        inputRow.appendChild(input);
        inputRow.appendChild(denominator);

        row.appendChild(header);
        row.appendChild(inputRow);

        container.appendChild(row);
    });

    if (items.length) {
        const requestId = request?.id ?? confirmReceiveState.requestId;
        const requestType = request?.type || confirmReceiveState.requestType;
        applyConfirmReceiveDraft(requestId, requestType);
    }
}

function collectConfirmReceiveInputs() {
    const container = document.getElementById('confirmReceiveItemsContainer');
    if (!container) {
        return { items: [] };
    }

    const inputs = Array.from(container.querySelectorAll('[data-receive-input]'));
    if (!inputs.length) {
        return { items: [] };
    }

    const items = [];

    for (const input of inputs) {
        const itemName = input.dataset.itemName || 'Item';
        const id = Number(input.dataset.borrowRequestItemId);
        if (!Number.isFinite(id)) {
            continue;
        }

        const approved = Number(input.dataset.approvedQuantity);
        const rawValue = clampDigitsToMax(sanitizeToTwoDigits(input.value), approved);
        input.value = rawValue;
        const numeric = rawValue === '' ? NaN : Number.parseInt(rawValue, 10);

        if (!Number.isFinite(numeric) || numeric < 0) {
            input.focus();
            return { error: `Enter a valid received quantity for ${itemName}.` };
        }

        if (Number.isFinite(approved) && numeric > approved) {
            input.value = clampDigitsToMax(String(approved), approved);
            input.focus();
            return { error: `${itemName} cannot exceed the approved quantity (${approved}).` };
        }

        items.push({
            id,
            received_quantity: numeric,
        });
    }

    return { items };
}

function captureConfirmReceiveDraft(requestId, requestType = null) {
    const numericId = Number(requestId);
    if (!Number.isFinite(numericId)) return;
    const typeKey = requestType || confirmReceiveState.requestType || null;

    const container = document.getElementById('confirmReceiveItemsContainer');
    if (!container) return;

    const inputs = Array.from(container.querySelectorAll('[data-receive-input]'));
    if (!inputs.length) {
        confirmReceiveState.draft = { requestId: numericId, requestType: typeKey, values: {} };
        return;
    }

    const values = {};
    inputs.forEach((input) => {
        const itemId = input.dataset.borrowRequestItemId;
        if (!itemId) {
            return;
        }
        const max = Number(input.dataset.approvedQuantity);
        const sanitized = clampDigitsToMax(sanitizeToTwoDigits(input.value), max);
        input.value = sanitized;
        values[itemId] = sanitized;
    });

    confirmReceiveState.draft = { requestId: numericId, requestType: typeKey, values };
}

function applyConfirmReceiveDraft(requestId, requestType = null) {
    if (!confirmReceiveState?.draft) return;

    const numericId = Number(requestId);
    if (!Number.isFinite(numericId)) return;

    const draft = confirmReceiveState.draft;
    const typeKey = requestType || confirmReceiveState.requestType || null;
    const draftType = (draft.requestType || null);
    if (!draft || draft.requestId !== numericId) return;
    if (draftType && typeKey && String(draftType).toLowerCase() !== String(typeKey).toLowerCase()) return;

    const container = document.getElementById('confirmReceiveItemsContainer');
    if (!container) return;

    const inputs = Array.from(container.querySelectorAll('[data-receive-input]'));
    inputs.forEach((input) => {
        const itemId = input.dataset.borrowRequestItemId;
        if (!itemId) return;
        if (Object.prototype.hasOwnProperty.call(draft.values, itemId)) {
            const max = Number(input.dataset.approvedQuantity);
            const value = clampDigitsToMax(sanitizeToTwoDigits(draft.values[itemId]), max);
            input.value = value;
        }
    });
}

function getReturnProofPond() {
    const input = document.getElementById('markReturnProofInput');
    if (!input) return null;
    if (input._filepondInstance) {
        return input._filepondInstance;
    }

    const FilePondLib = window.FilePond || (typeof FilePond !== 'undefined' ? FilePond : null);
    if (!FilePondLib) return null;

    try {
        const root = input.parentElement?.querySelector('.filepond--root');
        if (root && typeof FilePondLib.find === 'function') {
            const ponds = FilePondLib.find(root);
            if (Array.isArray(ponds) && ponds.length > 0) {
                return ponds[0];
            }
        }
    } catch (error) {
        console.warn('Failed to resolve return proof FilePond instance', error);
    }

    return null;
}

function openMarkReturnModal(requestId) {
    const numericId = Number(requestId);
    if (!Number.isFinite(numericId)) {
        markReturnState.requestId = null;
        return;
    }

    const request = ITEMS_CACHE.find((entry) => Number(entry?.id) === numericId) || null;
    markReturnState.requestId = numericId;

    const labelEl = document.getElementById('markReturnRequestLabel');
    if (labelEl) {
        const code = request ? (formatBorrowRequestCodeLocal(request) || `#${request.id}`) : `#${numericId}`;
        labelEl.textContent = code;
    }

    const proofInput = document.getElementById('markReturnProofInput');
    const proofPond = getReturnProofPond();
    if (proofPond) {
        proofPond.removeFiles();
    } else if (proofInput) {
        proofInput.value = '';
    }

    const noteInput = document.getElementById('markReturnNotesInput');
    if (noteInput) {
        noteInput.value = '';
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'markReturnModal' }));
}

// ---------- render table ----------
function renderItems() {
    const tbody = document.getElementById("myBorrowedItemsTableBody");
    if (!tbody) return;
    tbody.innerHTML = "";

    if (!ITEMS_CACHE.length) {
        appendEmptyState(tbody, 'my-borrowed-items-empty-state-template', 6, 'No borrowed items');
        return;
    }

    // Apply live search if present
    const searchEl = document.getElementById('my-borrowed-items-live-search');
    const term = (searchEl?.value || '').toLowerCase().trim();
    const list = ITEMS_CACHE.filter(req => {
        const formattedId = (formatBorrowRequestCodeLocal(req) || String(req.id ?? '')).toLowerCase();
        return !term || formattedId.includes(term);
    });

    if (!list.length) {
        appendEmptyState(tbody, 'my-borrowed-items-empty-state-template', 6, 'No items match your search');
        return;
    }

    list.forEach(req => {
        const tr = document.createElement("tr");
        tr.className = "transition hover:bg-purple-50 hover:shadow-md";
        tr.dataset.requestId = String(req.id);
        tr.dataset.requestType = (req.type || 'borrow');

        const requestCode = formatBorrowRequestCodeLocal(req) || `#${req.id ?? ''}`;
        const tdId = `<td class="px-4 py-3">${escapeHtml(requestCode)}</td>`;
        const typeBadge = getRequestTypeBadge(req.type);
        const tdType = `<td class="px-4 py-3">${typeBadge}</td>`;
        const tdBorrowDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.borrow_date))}</td>`;
            // Show overdue styling only when delivered (computeOverdueDays enforces delivered check)
            const overdueDays = computeOverdueDays(req);
            let tdReturnDate = '';
            if (overdueDays > 0) {
                tdReturnDate = `<td class="px-4 py-3 text-left text-red-600">
                        <div class="font-medium">${escapeHtml(formatDate(req.return_date))}</div>
                        <div class="mt-1 text-xs font-bold text-red-700">(${escapeHtml(String(overdueDays))} days overdue)</div>
                    </td>`;
            } else {
                tdReturnDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.return_date))}</td>`;
            }

        const deliveryStatusRaw = (req.delivery_status || '').toLowerCase();
        const baseStatus = (req.status || '').toLowerCase();
        const effectiveStatus = deliveryStatusRaw && deliveryStatusRaw !== 'pending'
            ? deliveryStatusRaw
            : baseStatus;
        const badgeHtml = getBadgeHtml(effectiveStatus);
        const tdStatus = `<td class="px-4 py-3">${badgeHtml}</td>`;

        const tdActions = document.createElement("td");
        tdActions.className = "px-4 py-3";
        const wrapper = document.createElement("div");
        wrapper.className = "flex justify-center gap-2";

        const statusKey = baseStatus;
        const deliveryStatus = deliveryStatusRaw;
        const displayStatus = deliveryStatus && deliveryStatus !== 'pending' ? deliveryStatus : statusKey;
        const isWalkIn = (req.type || '').toLowerCase() === 'walkin';
        const deliveredAt = req.delivered_at || null;
        const hasReturnProof = Boolean(req.return_proof_path || req.return_proof_url);

        // Walk-in specific action rules
        if (isWalkIn) {
            const normStatus = displayStatus;
            wrapper.appendChild(createButtonFromTemplate("btn-view-template", req));

            const shouldShowConfirm = normStatus === 'dispatched';
            if (shouldShowConfirm) {
                wrapper.appendChild(createButtonFromTemplate("btn-confirm-delivery-template", req));
            }

            const shouldShowReport = normStatus === 'dispatched';
            if (shouldShowReport) {
                const reportBtn = document.createElement('button');
                reportBtn.type = 'button';
                reportBtn.className = 'btn-action btn-report h-10 w-10';
                reportBtn.title = 'Report Not Received';
                reportBtn.innerHTML = '<span class="sr-only">Report Not Received</span><i class="fas fa-triangle-exclamation text-sm"></i>';
                reportBtn.addEventListener('click', () => {
                    openConfirmReportNotReceivedModal(req.id, { type: 'walkin' });
                });
                wrapper.appendChild(reportBtn);
            }

            const shouldShowRoutingSlip = ['delivered', 'returned'].includes(deliveryStatus);
            if (shouldShowRoutingSlip) {
                wrapper.appendChild(createButtonFromTemplate("btn-routing-slip-template", req));
            }

            tdActions.appendChild(wrapper);
            tr.innerHTML = tdId + tdType + tdBorrowDate + tdReturnDate + tdStatus;
            tr.appendChild(tdActions);
            tbody.appendChild(tr);
            return;
        }

        wrapper.appendChild(createButtonFromTemplate("btn-view-template", req));

        const shouldShowPrint = statusKey === 'validated' && !['dispatched', 'not_received', 'delivered'].includes(deliveryStatus);
        if (shouldShowPrint) {
            wrapper.appendChild(createButtonFromTemplate("btn-print-template", req));
        }

        const shouldShowRoutingSlip = deliveryStatus === 'delivered' || deliveryStatus === 'returned';
        if (shouldShowRoutingSlip) {
            wrapper.appendChild(createButtonFromTemplate("btn-routing-slip-template", req));
        }

        const shouldShowMarkReturned = deliveryStatus === 'delivered' && !hasReturnProof;
        if (shouldShowMarkReturned) {
            wrapper.appendChild(createButtonFromTemplate("btn-mark-returned-template", req));
        }

        const shouldShowConfirm = deliveryStatus === 'dispatched' && !deliveredAt;
        if (shouldShowConfirm) {
            wrapper.appendChild(createButtonFromTemplate("btn-confirm-delivery-template", req));
        }

        tdActions.appendChild(wrapper);

        tr.innerHTML = tdId + tdType + tdBorrowDate + tdReturnDate + tdStatus;
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });
}

// ---------- modal population ----------
export function fillBorrowModal(data) {
    if (!data) return;
    const isWalkIn = (data.type || '').toLowerCase() === 'walkin';
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (!el) return;
        const normalized = value === undefined || value === null || value === '' ? '—' : value;
        el.textContent = normalized;
    };

    const modalRoot = document.getElementById('borrowDetailsModalRoot');
    if (modalRoot) {
        modalRoot.dataset.requestId = data.id ? String(data.id) : '';
    }

    const pickText = (...values) => {
        for (const value of values) {
            if (typeof value === 'string') {
                const trimmed = value.trim();
                if (trimmed.length) return trimmed;
            }
        }
        return '';
    };

    const requestCode = formatBorrowRequestCodeLocal(data) || (data.id ? `#${data.id}` : '—');
    const requestLabel = isWalkIn ? 'Walk-In Request' : 'Borrow Request';
    setText('mbi-short-status', `${requestLabel} ${requestCode}`);
    setText('mbi-summary-id', requestCode);

    const itemsArray = Array.isArray(data.items) ? data.items : [];
    const itemsLabel = itemsArray.length === 1 ? '1 item' : `${itemsArray.length} items`;
    setText('mbi-summary-items', itemsLabel);
    const itemsSummary = document.getElementById('mbi-items-summary');
    if (itemsSummary) itemsSummary.textContent = itemsLabel;

    const purposeValue = pickText(data.purpose, data.purpose_description, data.purpose_text, data.purpose_detail);
    setText('mbi-summary-purpose', purposeValue || '—');

    const delivery = (data.delivery_status || '').toLowerCase();
    const statusKey = delivery || (data.status || '').toLowerCase();
    const statusContainer = document.getElementById('mbi-summary-status');
    if (statusContainer) {
        try {
            statusContainer.innerHTML = getBadgeHtml(statusKey);
        } catch (err) {
            statusContainer.textContent = statusKey ? statusKey.toUpperCase() : '—';
        }
    }

    setText('mbi-schedule-borrow', formatDate(data.borrow_date));
    setText('mbi-schedule-return', formatDate(data.return_date));

    // Show overdue alert inside details modal when applicable
    try {
        const overdueAlert = document.getElementById('mbi-schedule-overdue-alert');
        const overdueText = document.getElementById('mbi-schedule-overdue-alert-text');
        if (overdueAlert && overdueText) {
            const days = computeOverdueDays(data);
            if (days > 0) {
                overdueText.textContent = `This request is overdue by ${days} day${days === 1 ? '' : 's'}.`;
                overdueAlert.classList.remove('hidden');
            } else {
                overdueAlert.classList.add('hidden');
            }
        }
    } catch (e) {
        console.warn('Failed to update overdue alert', e);
    }

    const rawUsageValue = pickText(data.time_of_usage);
    const hasUsage = rawUsageValue.length > 0;
    const scheduleTimeEl = document.getElementById('mbi-schedule-time');
    if (scheduleTimeEl) {
        scheduleTimeEl.textContent = hasUsage ? formatUsageRange(rawUsageValue) : '—';
    }
    const scheduleTimeRow = document.getElementById('mbi-schedule-time-row');
    if (scheduleTimeRow) {
        scheduleTimeRow.classList.toggle('hidden', !hasUsage);
    }

    let municipality = pickText(
        data.municipality,
        data.municipality_name,
        data.municipality_label,
        data.location_municipality,
        data.delivery_municipality
    );
    let barangay = pickText(
        data.barangay,
        data.barangay_name,
        data.location_barangay,
        data.delivery_barangay
    );
    let specificArea = pickText(
        data.specific_area,
        data.location_specific_area,
        data.delivery_specific_area,
        data.address_specific_area
    );
    const compositeLocation = pickText(
        data.delivery_location,
        data.location,
        data.address,
        data.full_location
    );

    if (compositeLocation) {
        const parts = compositeLocation.split(',').map(part => part.trim()).filter(Boolean);

        if (!municipality && parts.length) {
            municipality = parts.shift();
        }
        if (!barangay && parts.length) {
            barangay = parts.shift();
        }
        if (!specificArea && parts.length) {
            specificArea = parts.join(', ');
        }

        if (!specificArea && !parts.length) {
            specificArea = compositeLocation.trim();
        }
    }

    setText('mbi-location-municipality', municipality || '—');
    setText('mbi-location-barangay', barangay || '—');
    setText('mbi-location-area', specificArea || '—');

    const physicalItems = itemsArray.filter((i) => !isManpowerEntry(i));
    const manpowerItems = itemsArray.filter((i) => isManpowerEntry(i));

    const itemsList = document.getElementById('mbi-items');
    if (itemsList) {
        const itemsHtml = physicalItems.map((i) => {
            const name = escapeHtml(resolveBorrowItemName(i));
            const qty = escapeHtml(String(i.quantity ?? 0));
            return `<li>${name} (x${qty})</li>`;
        }).join('');
        itemsList.innerHTML = itemsHtml || '<li class="list-none text-gray-500">No items recorded.</li>';
    }

    const manpowerList = document.getElementById('mbi-manpower');
    if (manpowerList) {
        const rows = manpowerItems.map((i) => {
            const role = escapeHtml(resolveBorrowItemName(i));
            const qty = escapeHtml(String(i.quantity ?? 0));
            return `<li>${role} (x${qty})</li>`;
        }).join('');
        manpowerList.innerHTML = rows || '<li class="list-none text-gray-500">No manpower requested.</li>';
    }

    // rejection block
    const rejBlock = document.getElementById('mbi-rejection-block');
    const rejSubject = document.getElementById('mbi-rejection-subject');
    const rejSummary = document.getElementById('mbi-rejection-summary');
    const rejReason = document.getElementById('mbi-rejection-reason');

    if (rejBlock) {
        const status = (data.status || '').toLowerCase();
        const subjectValue = typeof data.reject_category === 'string' ? data.reject_category.trim() : '';
        const detailValue = typeof data.reject_reason === 'string' ? data.reject_reason.trim() : '';
        const hasReason = subjectValue !== '' || detailValue !== '';

        if (status === 'rejected' && hasReason) {
            rejBlock.classList.remove('hidden');
            if (rejSummary) {
                rejSummary.textContent = 'Tap to view details';
            }
            if (rejSubject) {
                rejSubject.textContent = subjectValue ? `Subject: ${subjectValue}` : 'Subject: Not provided';
            }
            if (rejReason) {
                rejReason.textContent = detailValue || 'No detailed reason provided.';
            }
        } else {
            rejBlock.classList.add('hidden');
            if (rejSummary) rejSummary.textContent = 'Tap to view details';
            if (rejSubject) rejSubject.textContent = '';
            if (rejReason) rejReason.textContent = '';
        }
    }

    // delivery reason block
    const deliveryReasonBlock = document.getElementById('mbi-delivery-reason-block');
    const deliveryReasonContent = document.getElementById('mbi-delivery-reason-content');
    const deliveryReasonToggle = document.getElementById('mbi-delivery-reason-toggle');
    const deliveryReasonToggleText = document.getElementById('mbi-delivery-reason-toggle-text');
    const deliveryReasonToggleIcon = document.getElementById('mbi-delivery-reason-toggle-icon');
    
    if (data.delivery_reason_type) {
        if (deliveryReasonBlock) deliveryReasonBlock.classList.remove('hidden');
        
        let reasonHtml = '';
        const reasonType = (data.delivery_reason_type || '').toLowerCase();
        
        if (reasonType === 'missing') {
            reasonHtml = '<div class="font-medium">Missing</div>';
        } else if (reasonType === 'damaged') {
            reasonHtml = '<div class="font-medium">Damaged</div>';
        } else if (reasonType === 'others' && data.delivery_reason_details) {
            try {
                const details = typeof data.delivery_reason_details === 'string' 
                    ? JSON.parse(data.delivery_reason_details) 
                    : data.delivery_reason_details;
                
                const subject = escapeHtml(details.subject || '');
                const explanation = escapeHtml(details.explanation || '');
                
                reasonHtml = `
                    <div class="space-y-2">
                        <div>
                            <div class="font-medium mb-1">Subject:</div>
                            <div>${subject}</div>
                        </div>
                        <div>
                            <div class="font-medium mb-1">Explanation:</div>
                            <div id="mbi-delivery-reason-explanation" class="whitespace-pre-wrap">${explanation}</div>
                        </div>
                    </div>
                `;
                
                // Check if explanation is long and needs expand/collapse
                if (explanation.length > 150) {
                    const explanationEl = document.getElementById('mbi-delivery-reason-explanation');
                    if (explanationEl) {
                        explanationEl.classList.add('line-clamp-3');
                    }
                }
            } catch (e) {
                console.warn('Failed to parse delivery reason details', e);
                reasonHtml = '<div>Others (details unavailable)</div>';
            }
        } else {
            reasonHtml = `<div class="font-medium">${escapeHtml(reasonType.charAt(0).toUpperCase() + reasonType.slice(1))}</div>`;
        }
        
        if (deliveryReasonContent) {
            deliveryReasonContent.innerHTML = reasonHtml;
        }
        
        // Setup expand/collapse toggle
        if (deliveryReasonToggle && deliveryReasonToggleText && deliveryReasonToggleIcon) {
            const explanationEl = document.getElementById('mbi-delivery-reason-explanation');
            const needsToggle = explanationEl && explanationEl.textContent && explanationEl.textContent.length > 150;
            
            if (needsToggle) {
                deliveryReasonToggle.classList.remove('hidden');
                
                // Remove old click handler by replacing the button
                const toggleParent = deliveryReasonToggle.parentNode;
                const toggleClone = deliveryReasonToggle.cloneNode(true);
                toggleParent.replaceChild(toggleClone, deliveryReasonToggle);
                
                // Get fresh references
                const freshToggle = document.getElementById('mbi-delivery-reason-toggle');
                const freshToggleText = document.getElementById('mbi-delivery-reason-toggle-text');
                const freshToggleIcon = document.getElementById('mbi-delivery-reason-toggle-icon');
                
                if (freshToggle && freshToggleText && freshToggleIcon) {
                    let isExpanded = false;
                    freshToggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        isExpanded = !isExpanded;
                        const el = document.getElementById('mbi-delivery-reason-explanation');
                        if (el) {
                            if (isExpanded) {
                                el.classList.remove('line-clamp-3');
                                freshToggleText.textContent = 'Show less';
                                freshToggleIcon.classList.remove('fa-chevron-down');
                                freshToggleIcon.classList.add('fa-chevron-up');
                            } else {
                                el.classList.add('line-clamp-3');
                                freshToggleText.textContent = 'Show more';
                                freshToggleIcon.classList.remove('fa-chevron-up');
                                freshToggleIcon.classList.add('fa-chevron-down');
                            }
                        }
                    });
                }
            } else {
                deliveryReasonToggle.classList.add('hidden');
            }
        }
    } else {
        if (deliveryReasonBlock) deliveryReasonBlock.classList.add('hidden');
        if (deliveryReasonContent) deliveryReasonContent.innerHTML = '';
    }

    // delivery buttons are now placed on the table action column; modal no longer contains report/confirm buttons
}

// ---------- view (fetch details & open modal) ----------
async function viewBorrowDetails(requestInput) {
    try {
        const id = typeof requestInput === 'object' ? requestInput?.id : requestInput;
        const type = typeof requestInput === 'object' ? (requestInput?.type || null) : null;
        const cached = findCachedRequest(id, type);
        const endpoint = (type || cached?.type || '').toLowerCase() === 'walkin'
            ? `/user/my-borrowed-items/walk-in/${encodeURIComponent(id)}`
            : `/user/my-borrowed-items/${encodeURIComponent(id)}`;

        const res = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            const err = await res.json().catch(()=>null);
            throw err?.message || `HTTP ${res.status}`;
        }
        const data = await res.json();
        fillBorrowModal(data);
        window.dispatchEvent(new CustomEvent("open-modal", { detail: "borrowDetailsModal" }));
    } catch (err) {
        console.error(err);
        showError("Failed to load details");
    }
}

// ---------- confirm / report actions ----------
async function postJson(url, body = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(body)
    });
    const json = await res.json().catch(()=>null);
    if (!res.ok) throw new Error(json?.message || `HTTP ${res.status}`);
    return json;
}

async function confirmDelivery(id, options = {}) {
    const {
        button = null,
        closeModals = ['borrowDetailsModal'],
        silent = false,
        items = null,
        type = null,
    } = options || {};

    const spinnerMarkup = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" aria-hidden="true"></span>';
    let buttonSnapshot = null;

    if (button) {
        buttonSnapshot = {
            html: button.innerHTML,
            disabled: button.disabled,
        };
        button.disabled = true;
        button.innerHTML = spinnerMarkup;
    }

    try {
        const payload = {};
        if (Array.isArray(items)) {
            payload.items = items;
        }

        const cached = findCachedRequest(id, type);
        const endpoint = (type || cached?.type || '').toLowerCase() === 'walkin'
            ? `/user/my-borrowed-items/walk-in/${encodeURIComponent(id)}/confirm-delivery`
            : `/user/my-borrowed-items/${encodeURIComponent(id)}/confirm-delivery`;

        await postJson(endpoint, payload);
        if (!silent) {
            showSuccess('Thank you — receipt confirmed.');
        }
        closeModals.forEach((modalName) => {
            if (!modalName) return;
            window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
        });
        await loadMyBorrowedItems();
        return true;
    } catch (e) {
        console.error('confirmDelivery failed', e);
        showError(e?.message || 'Failed to confirm receipt.');
        return false;
    } finally {
        if (button && buttonSnapshot) {
            button.disabled = buttonSnapshot.disabled;
            button.innerHTML = buttonSnapshot.html;
        }
    }
}

async function reportNotReceived(id, reason = null, type = null) {
    try {
        const cached = findCachedRequest(id, type);
        const isWalkIn = (type || cached?.type || '').toLowerCase() === 'walkin';

        const body = { reason: reason ? reason : null };
        const endpoint = isWalkIn
            ? `/user/my-borrowed-items/walk-in/${encodeURIComponent(id)}/report-not-received`
            : `/user/my-borrowed-items/${encodeURIComponent(id)}/report-not-received`;

        await postJson(endpoint, body);
        showSuccess('Report submitted — admin will be notified.');
        confirmReportState.requestId = null;
        confirmReportState.origin = null;
        confirmReportState.requestType = null;
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'borrowDetailsModal' }));
        await loadMyBorrowedItems();
    } catch (e) {
        console.error('reportNotReceived failed', e);
        showError('Failed to submit report.');
    }
}

async function submitReturnProof(id, formElement, submitButton) {
    if (!id || !formElement) {
        showError('Invalid request. Please refresh and try again.');
        return;
    }

    const spinnerMarkup = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" aria-hidden="true"></span>';
    let originalButtonHtml = null;
    let originalDisabled = false;

    if (submitButton) {
        originalButtonHtml = submitButton.innerHTML;
        originalDisabled = submitButton.disabled;
        submitButton.disabled = true;
        submitButton.innerHTML = spinnerMarkup;
    }

    const formData = new FormData(formElement);
    const proofPond = getReturnProofPond();

    if (proofPond) {
        const files = proofPond.getFiles();
        if (!files.length) {
            showError('Please upload your return slip before submitting.');
            if (submitButton) {
                submitButton.disabled = originalDisabled;
                submitButton.innerHTML = originalButtonHtml ?? 'Submit';
            }
            return;
        }
        const file = files[0].file;
        if (file) {
            formData.delete('return_proof');
            formData.append('return_proof', file, file.name);
        }
    }

    try {
        const res = await fetch(`/user/my-borrowed-items/${encodeURIComponent(id)}/mark-returned`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (!res.ok) {
            const payload = await res.json().catch(() => null);
            const message = payload?.message || `HTTP ${res.status}`;
            throw new Error(message);
        }

        await res.json().catch(() => null);
        showSuccess('Return submitted for review. Thank you!');
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'markReturnModal' }));
        markReturnState.requestId = null;
        formElement.reset();
        if (proofPond) {
            proofPond.removeFiles();
        }
        await loadMyBorrowedItems();
    } catch (error) {
        console.error('submitReturnProof failed', error);
        showError(error?.message || 'Failed to submit return proof.');
    } finally {
        if (submitButton) {
            submitButton.disabled = originalDisabled;
            submitButton.innerHTML = originalButtonHtml ?? 'Submit';
        }
    }
}

// wire confirm/report buttons
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('mbi-confirm-received-btn');
    const reportBtn = document.getElementById('mbi-report-not-received-btn');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const id = confirmBtn.dataset.requestId;
            if (!id) return;
            openConfirmReceiveModal(id);
        });
    }

    // wire confirm report modal buttons
    const modalReportConfirmBtn = document.getElementById('confirmReportNotReceivedConfirmBtn');

    if (modalReportConfirmBtn) {
        modalReportConfirmBtn.addEventListener('click', async () => {
            const id = confirmReportState.requestId;
            if (!id) return;
            modalReportConfirmBtn.disabled = true;
            try {
                const reasonInput = document.getElementById('confirmReportNotReceivedReason');
                const reasonValue = reasonInput?.value?.trim() || null;
                await reportNotReceived(id, reasonValue, confirmReportState.requestType);
                confirmReportState.requestId = null;
                confirmReportState.origin = null;
                confirmReportState.requestType = null;
                if (reasonInput) {
                    reasonInput.value = '';
                }
                confirmReceiveState.requestId = null;
                confirmReceiveState.draft = null;
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmReportNotReceivedModal' }));
            } finally {
                modalReportConfirmBtn.disabled = false;
            }
        });
    }

    const modalReportCancelBtn = document.getElementById('confirmReportNotReceivedCancelBtn');

    const markReturnForm = document.getElementById('markReturnForm');
    const markReturnSubmitBtn = document.getElementById('markReturnSubmitBtn');
    const markReturnCancelBtn = document.getElementById('markReturnCancelBtn');

    if (markReturnForm) {
        markReturnForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const currentId = markReturnState.requestId;
            if (!currentId) {
                showError('Unable to identify the borrow request.');
                return;
            }
            await submitReturnProof(currentId, markReturnForm, markReturnSubmitBtn);
        });
    }

    if (markReturnCancelBtn) {
        markReturnCancelBtn.addEventListener('click', () => {
            markReturnState.requestId = null;
            if (markReturnForm) {
                markReturnForm.reset();
            }
            const proofPond = getReturnProofPond();
            if (proofPond) {
                proofPond.removeFiles();
            }
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'markReturnModal' }));
        });
    }

    const modalConfirmBtn = document.getElementById('confirmReceiveConfirmBtn');
    const modalReportBtn = document.getElementById('confirmReceiveReportBtn');

    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener('click', async () => {
            const id = confirmReceiveState.requestId;
            const type = confirmReceiveState.requestType;
            if (!id) {
                return;
            }

            const { items, error } = collectConfirmReceiveInputs();
            if (error) {
                showError(error);
                return;
            }

            const success = await confirmDelivery(id, {
                button: modalConfirmBtn,
                closeModals: ['confirmReceiveModal', 'borrowDetailsModal'],
                items,
                type,
            });
            if (success) {
                confirmReceiveState.requestId = null;
                confirmReceiveState.requestType = null;
                confirmReceiveState.draft = null;
            }
        });
    }

    if (modalReportBtn) {
        modalReportBtn.addEventListener('click', () => {
            const id = confirmReceiveState.requestId;
            const type = confirmReceiveState.requestType;
            if (!id) {
                showError('Select a request before reporting.');
                return;
            }
            captureConfirmReceiveDraft(id, type);
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmReceiveModal' }));
            openConfirmReportNotReceivedModal(id, { origin: 'confirm', type });
        });
    }

    if (modalReportCancelBtn) {
        modalReportCancelBtn.addEventListener('click', () => {
            const id = confirmReportState.requestId;
            const origin = confirmReportState.origin;
            const requestType = confirmReportState.requestType;
            confirmReportState.requestId = null;
            confirmReportState.origin = null;
            confirmReportState.requestType = null;

            const reasonInput = document.getElementById('confirmReportNotReceivedReason');
            if (reasonInput) {
                reasonInput.value = '';
            }

            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmReportNotReceivedModal' }));

            if (origin === 'confirm' && Number.isFinite(Number(id))) {
                openConfirmReceiveModal({ id, type: requestType });
            }
        });
    }
});

// ---------- boot ----------
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('myBorrowedItemsTableBody')) return;
    loadMyBorrowedItems();
    setInterval(loadMyBorrowedItems, 10000);

    const mbiSearch = document.getElementById('my-borrowed-items-live-search');
    if (mbiSearch) {
        mbiSearch.addEventListener('input', () => renderItems());
        mbiSearch.addEventListener('focus', function(){ this.placeholder = 'Type to Search'; });
        mbiSearch.addEventListener('blur', function(){ this.placeholder = 'Search Request ID'; });
    }
});

// Listen for cross-tab dispatch notifications from admin page
// Listen for cross-tab notifications from admin page
window.addEventListener('storage', async function (ev) {
    try {
        if (!ev || !ev.key) return;

        // old key kept for backwards compatibility (if any)
        if (ev.key === 'borrow_request_dispatched') {
            // refresh everything
            if (typeof loadMyBorrowedItems === 'function') {
                loadMyBorrowedItems();
            }
            return;
        }

        if (ev.key === 'borrow_request_updated') {
            const payload = ev.newValue ? JSON.parse(ev.newValue) : null;
            if (!payload || !payload.borrow_request_id) return;

            // Refresh list
            if (typeof loadMyBorrowedItems === 'function') {
                loadMyBorrowedItems();
            }

            // If modal is open for this request, refresh it using the root dataset id
            const modalRoot = document.getElementById('borrowDetailsModalRoot');
            const currentId = modalRoot?.dataset.requestId || '';
            if (currentId && String(currentId) === String(payload.borrow_request_id)) {
                // fetch fresh details and re-fill modal
                try {
                    const id = payload.borrow_request_id;
                    const res = await fetch(`/user/my-borrowed-items/${encodeURIComponent(id)}`, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json().catch(()=>null);
                        if (data) {
                            // reuse existing fillBorrowModal utility to re-populate
                            if (typeof fillBorrowModal === 'function') {
                                fillBorrowModal(data);
                            } else {
                                // fallback: manually update some fields
                                const borrowEl = document.getElementById('mbi-borrow-date');
                                if (borrowEl) borrowEl.textContent = data.borrow_date ? formatDate(data.borrow_date) : '';

                                const returnEl = document.getElementById('mbi-return-date');
                                if (returnEl) returnEl.textContent = data.return_date ? formatDate(data.return_date) : '';
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Failed to refresh modal for updated borrow request', e);
                }
            }
            return;
        }
    } catch (e) {
        console.warn('Failed to handle storage event', e);
    }
});

// Export helpers
window.loadMyBorrowedItems = loadMyBorrowedItems;
window.fillBorrowModal = fillBorrowModal;
window.formatDate = formatDate;
