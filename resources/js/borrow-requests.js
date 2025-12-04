const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const LIST_ROUTE = window.LIST_ROUTE || '/admin/borrow-requests/list';
const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];
const MANPOWER_PLACEHOLDER = '__SYSTEM_MANPOWER_PLACEHOLDER__';

let BORROW_CACHE = [];
let BR_SEARCH_TERM = '';
let BR_STATUS_FILTER = '';
const REJECTION_REASONS_ENDPOINT = window.REJECTION_REASONS_ENDPOINT || '/admin/rejection-reasons';
const OTHER_REJECTION_REASON_KEY = '__other__';

let REJECTION_REASONS_CACHE = [];
const rejectionFlowState = {
    requestId: null,
    selectedReasonId: null,
    prevModal: null,
};

function formatDate(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const month = SHORT_MONTHS[date.getMonth()];
    if (!month) return value;
    const day = date.getDate();
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
}

function isManpowerEntry(item) {
    if (!item) return false;
    if (item.is_manpower) return true;
    const baseName = item?.item?.name || item?.name || '';
    return baseName === MANPOWER_PLACEHOLDER;
}

function resolveBorrowItemName(item) {
    if (!item || typeof item !== 'object') {
        return 'Unknown';
    }

    const provided = typeof item.display_name === 'string' ? item.display_name.trim() : '';
    if (provided) {
        return provided;
    }

    const roleName = (item.manpower_role || item.manpower_role_name || item.role_name || '').trim();
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

function computeOverdueDays(req) {
    if (!req) return 0;
    // Only consider overdue when delivery_status (or status) is 'delivered'
    const delivery = String(req.delivery_status || '').toLowerCase();
    const status = String(req.status || '').toLowerCase();
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

function humanizeStatus(status) {
    if (!status) return 'Pending';
    return String(status)
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
        .join(' ');
}

function showError(message) {
    window.showToast(message, 'error');
}

function showSuccess(message) {
    window.showToast(message, 'success');
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setRejectionReasonsCache(reasons) {
    REJECTION_REASONS_CACHE = Array.isArray(reasons) ? reasons.slice() : [];
    REJECTION_REASONS_CACHE.sort((a, b) => {
        const usageA = Number(a?.usage_count ?? 0);
        const usageB = Number(b?.usage_count ?? 0);
        if (usageA !== usageB) {
            return usageB - usageA;
        }
        const subjectA = (a?.subject ?? '').toLowerCase();
        const subjectB = (b?.subject ?? '').toLowerCase();
        if (subjectA < subjectB) return -1;
        if (subjectA > subjectB) return 1;
        return 0;
    });
}

async function fetchRejectionReasons(force = false) {
    if (!force && REJECTION_REASONS_CACHE.length) {
        return REJECTION_REASONS_CACHE;
    }

    try {
        const res = await fetch(REJECTION_REASONS_ENDPOINT, { headers: { Accept: 'application/json' } });
        let payload = null;
        try {
            payload = await res.json();
        } catch (error) {
            payload = null;
        }

        if (!res.ok) {
            throw new Error(payload?.message || `Failed to load rejection reasons (status ${res.status})`);
        }

        const reasons = Array.isArray(payload) ? payload : [];
        setRejectionReasonsCache(reasons);
        return REJECTION_REASONS_CACHE;
    } catch (error) {
        throw error;
    }
}

function getRejectionReasonById(reasonId) {
    if (reasonId === null || reasonId === undefined) return null;
    const numeric = Number(reasonId);
    if (Number.isNaN(numeric)) return null;
    return REJECTION_REASONS_CACHE.find((reason) => Number(reason.id) === numeric) || null;
}

function resetRejectionFlow() {
    rejectionFlowState.requestId = null;
    rejectionFlowState.selectedReasonId = null;
    rejectionFlowState.prevModal = null;
}

async function openRejectionFlow(requestId) {
    rejectionFlowState.requestId = requestId;
    try {
        const reasons = await fetchRejectionReasons(REJECTION_REASONS_CACHE.length === 0);
        if (!reasons.length) {
            rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
            openRejectionCustomModal(requestId, { fromSelection: false });
            return;
        }
        openRejectionSelectModal(requestId);
    } catch (error) {
        console.error('Failed to load rejection reasons', error);
        showError(error?.message || 'Failed to load rejection reasons. Please try again.');
    }
}

function openRejectionSelectModal(requestId, options = {}) {
    if (!REJECTION_REASONS_CACHE.length) {
        openRejectionCustomModal(requestId, { fromSelection: false });
        return;
    }

    const preserveSelection = Boolean(options.preserveSelection);
    if (!preserveSelection) {
        rejectionFlowState.selectedReasonId = null;
    }
    rejectionFlowState.requestId = requestId;
    rejectionFlowState.prevModal = null;

    renderRejectionReasonList();
    updateRejectConfirmButton();

    if (rejectionFlowState.selectedReasonId === OTHER_REJECTION_REASON_KEY) {
        const otherRadio = document.getElementById('rejectReasonOtherOption');
        if (otherRadio) {
            otherRadio.checked = true;
        }
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rejectReasonSelectModal' }));
}

function renderRejectionReasonList() {
    const container = document.getElementById('rejectReasonOptions');
    if (!container) return;

    container.innerHTML = '';

    if (!REJECTION_REASONS_CACHE.length) {
        const empty = document.createElement('p');
        empty.className = 'text-sm text-gray-500';
        empty.textContent = 'No rejection reasons saved yet.';
        container.appendChild(empty);
        return;
    }

    REJECTION_REASONS_CACHE.forEach((reason) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-start justify-between gap-3 border border-gray-200 rounded-lg p-3 hover:border-purple-300 transition';
        const radioId = `rejectReasonOption${reason.id}`;
        wrapper.innerHTML = `
            <label class="flex items-start gap-3 flex-1 cursor-pointer" for="${radioId}">
                <input type="radio" id="${radioId}" class="mt-1 text-purple-600 focus:ring-purple-500" name="rejectReasonChoice" value="${escapeHtml(String(reason.id))}">
                <div>
                    <div class="text-sm font-semibold text-gray-900">${escapeHtml(reason.subject || 'Untitled reason')}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Used ${escapeHtml(String(reason.usage_count ?? 0))} time(s)</div>
                </div>
            </label>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="text-sm text-indigo-600 hover:text-indigo-700" data-action="view" data-reason-id="${escapeHtml(String(reason.id))}">View</button>
                <button type="button" class="text-sm text-red-500 hover:text-red-600" data-action="remove" data-reason-id="${escapeHtml(String(reason.id))}">Remove</button>
            </div>
        `;
        container.appendChild(wrapper);
    });

    if (rejectionFlowState.selectedReasonId) {
        const selector = `input[name="rejectReasonChoice"][value="${rejectionFlowState.selectedReasonId}"]`;
        const radio = container.querySelector(selector);
        if (radio) {
            radio.checked = true;
        }
    }
}

function updateRejectConfirmButton() {
    const confirmBtn = document.getElementById('rejectReasonSelectConfirmBtn');
    if (!confirmBtn) return;

    const selected = rejectionFlowState.selectedReasonId;
    confirmBtn.disabled = !selected;
    if (selected === OTHER_REJECTION_REASON_KEY) {
        confirmBtn.textContent = 'Next';
    } else {
        confirmBtn.textContent = 'Confirm Reject';
    }
}

function openRejectionCustomModal(requestId, options = {}) {
    const subjectInput = document.getElementById('rejectReasonSubjectInput');
    const detailInput = document.getElementById('rejectReasonDetailInput');

    rejectionFlowState.requestId = requestId;
    rejectionFlowState.prevModal = options.fromSelection ? 'select' : null;
    if (options.fromSelection) {
        rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
    }

    if (subjectInput) subjectInput.value = options.subject ?? '';
    if (detailInput) detailInput.value = options.detail ?? '';

    if (subjectInput) subjectInput.focus();

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rejectReasonCustomModal' }));
}

function openRejectionDetailModal(reason) {
    const subjectEl = document.getElementById('rejectReasonViewSubject');
    const detailEl = document.getElementById('rejectReasonViewDetail');

    if (subjectEl) subjectEl.textContent = reason?.subject ?? '';
    if (detailEl) detailEl.textContent = reason?.detail ?? '';

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rejectReasonViewModal' }));
}

function openRejectionDeleteModal(reasonId) {
    const reason = getRejectionReasonById(reasonId);
    if (!reason) {
        showError('The selected rejection reason is no longer available.');
        return;
    }

    const nameEl = document.getElementById('rejectReasonDeleteName');
    const usageEl = document.getElementById('rejectReasonDeleteUsage');
    const confirmBtn = document.getElementById('rejectReasonDeleteConfirmBtn');

    if (nameEl) nameEl.textContent = reason.subject || 'Untitled reason';
    if (usageEl) usageEl.textContent = `Used ${Number(reason.usage_count ?? 0)} time(s)`;
    if (confirmBtn) confirmBtn.dataset.reasonId = reason.id;

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rejectReasonDeleteModal' }));
}

async function deleteRejectionReason(reasonId, button) {
    if (!reasonId) return;

    if (button) button.disabled = true;
    try {
        const res = await fetch(`${REJECTION_REASONS_ENDPOINT}/${encodeURIComponent(reasonId)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                Accept: 'application/json',
            },
        });
        let payload = null;
        try {
            payload = await res.json();
        } catch (error) {
            payload = null;
        }

        if (!res.ok) {
            throw new Error(payload?.message || `Failed to remove rejection reason (status ${res.status})`);
        }

        const numericId = Number(reasonId);
        const removedIndex = REJECTION_REASONS_CACHE.findIndex((reason) => Number(reason.id) === numericId);
        if (removedIndex > -1) {
            REJECTION_REASONS_CACHE.splice(removedIndex, 1);
        }

        if (REJECTION_REASONS_CACHE.length) {
            const nextIndex = Math.min(removedIndex, REJECTION_REASONS_CACHE.length - 1);
            const nextReason = REJECTION_REASONS_CACHE[nextIndex];
            rejectionFlowState.selectedReasonId = nextReason ? String(nextReason.id) : null;
        } else {
            rejectionFlowState.selectedReasonId = null;
        }

        setRejectionReasonsCache(REJECTION_REASONS_CACHE);
        renderRejectionReasonList();
        updateRejectConfirmButton();

        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonDeleteModal' }));
        showSuccess(payload?.message || 'Rejection reason removed successfully.');

        if (!REJECTION_REASONS_CACHE.length && rejectionFlowState.requestId) {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonSelectModal' }));
            openRejectionCustomModal(rejectionFlowState.requestId, { fromSelection: false });
        }
    } catch (error) {
        console.error('Failed to remove rejection reason', error);
        showError(error?.message || 'Failed to remove rejection reason. Please try again.');
    } finally {
        if (button) button.disabled = false;
    }
}

async function submitRejectionDecision({ requestId, reasonId, subject, detail, button, modalsToClose = [] }) {
    if (!requestId) {
        showError('No borrow request selected.');
        return;
    }

    const modalList = Array.isArray(modalsToClose) ? modalsToClose.filter(Boolean) : [];
    let closedModals = [];

    const closeModalsBeforeUpdate = () => {
        closedModals = modalList.slice();
        closedModals.forEach((modalName) => {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
        });
    };

    const reopenModalsOnFailure = () => {
        if (!closedModals.length) return;
        closedModals.forEach((modalName) => {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: modalName }));
        });
    };

    try {
        closeModalsBeforeUpdate();
        await updateRequest(Number(requestId), 'rejected', {
            button,
            rejectReasonId: reasonId != null ? Number(reasonId) : null,
            rejectSubject: subject,
            rejectDetail: detail,
        });

        if (reasonId != null) {
            const idx = REJECTION_REASONS_CACHE.findIndex((reason) => Number(reason.id) === Number(reasonId));
            if (idx > -1) {
                const updated = { ...REJECTION_REASONS_CACHE[idx] };
                updated.usage_count = Number(updated.usage_count ?? 0) + 1;
                REJECTION_REASONS_CACHE[idx] = updated;
                setRejectionReasonsCache(REJECTION_REASONS_CACHE);
            }
        }

        resetRejectionFlow();
    } catch (error) {
        reopenModalsOnFailure();
        console.error('Failed to reject borrow request', error);
        showError(error?.message || 'Failed to reject the borrow request.');
    }
}

async function saveCustomRejectionReason(button) {
    if (!rejectionFlowState.requestId) {
        showError('No borrow request selected.');
        return;
    }

    const subjectInput = document.getElementById('rejectReasonSubjectInput');
    const detailInput = document.getElementById('rejectReasonDetailInput');

    const subject = subjectInput?.value?.trim() || '';
    const detail = detailInput?.value?.trim() || '';

    if (!subject) {
        showError('Please enter a subject for the rejection reason.');
        if (subjectInput) subjectInput.focus();
        return;
    }

    if (!detail) {
        showError('Please provide the detailed rejection reason.');
        if (detailInput) detailInput.focus();
        return;
    }

    if (button) button.disabled = true;

    try {
        const res = await fetch(REJECTION_REASONS_ENDPOINT, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ subject, detail }),
        });

        let payload = null;
        try {
            payload = await res.json();
        } catch (error) {
            payload = null;
        }

        if (!res.ok) {
            throw new Error(payload?.message || `Failed to save rejection reason (status ${res.status})`);
        }

        const reasonData = payload?.reason ?? null;
        if (reasonData) {
            const idx = REJECTION_REASONS_CACHE.findIndex((reason) => Number(reason.id) === Number(reasonData.id));
            if (idx > -1) {
                REJECTION_REASONS_CACHE[idx] = reasonData;
            } else {
                REJECTION_REASONS_CACHE.push(reasonData);
            }
            setRejectionReasonsCache(REJECTION_REASONS_CACHE);
        }

        const subjectForUpdate = reasonData?.subject ?? subject;
        const detailForUpdate = reasonData?.detail ?? detail;

        await submitRejectionDecision({
            requestId: rejectionFlowState.requestId,
            reasonId: reasonData?.id ?? null,
            subject: subjectForUpdate,
            detail: detailForUpdate,
            button,
            modalsToClose: ['rejectReasonCustomModal', 'rejectReasonSelectModal'],
        });

        if (subjectInput) subjectInput.value = '';
        if (detailInput) detailInput.value = '';
    } catch (error) {
        console.error('Failed to save rejection reason', error);
        showError(error?.message || 'Failed to save rejection reason. Please try again.');
    } finally {
        if (button) button.disabled = false;
    }
}

function handleCustomRejectionBack() {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonCustomModal' }));

    if (rejectionFlowState.prevModal === 'select' && REJECTION_REASONS_CACHE.length) {
        rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
        openRejectionSelectModal(rejectionFlowState.requestId, { preserveSelection: true });
        const otherRadio = document.getElementById('rejectReasonOtherOption');
        if (otherRadio) {
            otherRadio.checked = true;
        }
        updateRejectConfirmButton();
    } else {
        resetRejectionFlow();
    }
}

async function loadBorrowRequests() {
    try {
        const res = await fetch(LIST_ROUTE, { headers: { Accept: 'application/json' } });
        if (!res.ok) {
            const errorPayload = await res.json().catch(() => null);
            throw new Error(errorPayload?.message || `HTTP ${res.status}`);
        }
        const json = await res.json();
        BORROW_CACHE = Array.isArray(json) ? json : (Array.isArray(json?.data) ? json.data : []);
        renderBorrowRequests();
    } catch (error) {
        console.error('Failed to load borrow requests', error);
        showError(error?.message || 'Failed to load borrow requests.');
    }
}

// Pagination removed - displaying all results with scrolling

function createButtonFromTemplate(templateId, id, options = {}) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    const btn = frag.querySelector('button,[data-action]');
    if (!btn) return frag;

    const action = (btn.getAttribute('data-action') || '').toLowerCase().trim();

    if (action === 'view') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); viewRequest(id); });
    } else if (action === 'print') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); const url = `/admin/borrow-requests/${encodeURIComponent(id)}/print`; window.open(url, '_blank'); });
    } else if (action === 'validate') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openAssignManpowerModal(id); });
    } else if (action === 'approve') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); approveRequest(id); });
    } else if (action === 'reject') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openConfirmModal(id, 'rejected'); });
    } else if (['dispatch', 'deliver', 'deliver_items'].includes(action)) {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openDeliverItemsModal(id); });
    } else {
        console.warn('Unknown button action in template', templateId, 'action=', action);
    }

    return frag;
}

const QUANTITY_REASONS = [
    'Temporarily in use',
    'Meeting use',
    'Under maintenance',
];

const MANPOWER_REASONS = [
    'Task completion',
    'Overestimated need',
    'Schedule conflict',
];

function buildReasonOptions(options) {
    const initial = ['<option value="">Select reason</option>'];
    return initial.concat(options.map((label) => `<option value="${escapeHtml(label)}">${escapeHtml(label)}</option>`)).join('');
}

function splitItemsByType(items = []) {
    const result = { physical: [], manpower: [] };
    items.forEach((item) => {
        if (item && isManpowerEntry(item)) {
            result.manpower.push(item);
        } else {
            result.physical.push(item);
        }
    });
    return result;
}

function buildAssignmentMetadata(item, originalQty, config) {
    const parts = [`Requested: x${originalQty}`];
    if (config.isManpower && item?.manpower_role) {
        parts.push(`Role: ${item.manpower_role}`);
    }
    if (!config.isManpower && item?.item && Object.prototype.hasOwnProperty.call(item.item, 'available_qty')) {
        parts.push(`Available: ${item.item.available_qty ?? 0}`);
    }
    if (item?.quantity_reason) {
        parts.push(`Last reason: ${item.quantity_reason}`);
    }
    return parts.map((part) => escapeHtml(String(part))).join(' • ');
}

function renderAssignmentContainer(containerId, rows, config = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';

    if (!rows.length) {
        const empty = document.createElement('p');
        empty.className = 'py-4 text-sm text-center text-gray-500';
        empty.textContent = config.emptyText || 'No data available.';
        container.appendChild(empty);
        return;
    }

    rows.forEach((item) => {
        const rowEl = document.createElement('div');
        const itemId = item?.borrow_request_item_id || item?.id || null;
        if (!itemId) return;

        const originalQty = Number(item?.quantity) || 0;
        const label = resolveBorrowItemName(item) || (config.isManpower ? 'Manpower role' : 'Unlabeled item');
        const isManpower = isManpowerEntry(item);

        rowEl.dataset.borrowRequestItemId = String(itemId);
        rowEl.dataset.originalQty = String(originalQty);
        rowEl.dataset.rowLabel = label;
        rowEl.dataset.isManpower = isManpower ? '1' : '0';

        const infoDiv = document.createElement('div');
        infoDiv.innerHTML = `
            <p class="font-medium text-gray-900">${escapeHtml(label)}</p>
            <span class="text-xs text-gray-500">${buildAssignmentMetadata(item, originalQty, config)}</span>
        `;

        const qtyDiv = document.createElement('div');
        const qtyLabel = document.createElement('label');
        qtyLabel.className = 'text-xs font-semibold text-gray-500 block mb-1';
        qtyLabel.textContent = 'Approved Qty';
        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.min = '0';
        qtyInput.value = String(originalQty);
        qtyInput.className = 'assign-qty-input gov-input text-sm';
        const qtyHint = document.createElement('p');
        qtyHint.className = 'text-[11px] text-gray-400 mt-1';
        qtyHint.textContent = `Max ${originalQty}`;
        qtyDiv.appendChild(qtyLabel);
        qtyDiv.appendChild(qtyInput);
        qtyDiv.appendChild(qtyHint);

        const reasonDiv = document.createElement('div');
        reasonDiv.className = 'assign-qty-reason-wrapper hidden';
        const reasonLabel = document.createElement('label');
        reasonLabel.className = 'text-xs font-semibold text-gray-500 block mb-1';
        reasonLabel.textContent = 'Adjustment Reason';
        const reasonSelect = document.createElement('select');
        reasonSelect.className = 'assign-qty-reason gov-input text-sm';
        reasonSelect.innerHTML = buildReasonOptions(config.reasonOptions || []);
        if (item?.quantity_reason && !(config.reasonOptions || []).includes(item.quantity_reason)) {
            const customOption = document.createElement('option');
            customOption.value = item.quantity_reason;
            customOption.textContent = item.quantity_reason;
            reasonSelect.appendChild(customOption);
        }
        if (item?.quantity_reason) {
            reasonSelect.value = item.quantity_reason;
        }
        const reasonHint = document.createElement('p');
        reasonHint.className = 'text-[11px] text-gray-400 mt-1';
        reasonHint.textContent = 'Required when reducing the request.';
        reasonDiv.appendChild(reasonLabel);
        reasonDiv.appendChild(reasonSelect);
        reasonDiv.appendChild(reasonHint);

        reasonSelect.addEventListener('change', () => {
            reasonSelect.classList.remove('border-red-500');
        });

        rowEl.appendChild(infoDiv);
        rowEl.appendChild(qtyDiv);
        rowEl.appendChild(reasonDiv);

        container.appendChild(rowEl);

        qtyInput.addEventListener('input', handleQtyInputChange);
        qtyInput.addEventListener('change', handleQtyInputChange);
        handleQtyInputChange({ target: qtyInput });
    });
}

function renderAssignmentSections(request) {
    const itemsArray = Array.isArray(request?.items) ? request.items : [];
    const { physical, manpower } = splitItemsByType(itemsArray);
    renderAssignmentContainer('assignPhysicalItemsContainer', physical, {
        emptyText: 'No physical items recorded.',
        reasonOptions: QUANTITY_REASONS,
        isManpower: false,
    });
    renderAssignmentContainer('assignManpowerItemsContainer', manpower, {
        emptyText: 'No manpower requested.',
        reasonOptions: MANPOWER_REASONS,
        isManpower: true,
    });
}

function buildStatusBadge(status, deliveryStatus) {
    const statusKey = String(status || '').toLowerCase();
    const deliveryKey = String(deliveryStatus || '').toLowerCase();

    // Helper to get icon HTML
    const getIcon = (iconClass) => `<i class="fas ${iconClass} text-xs"></i>`;
    if (deliveryKey === 'dispatched') {
        return {
            label: 'Dispatched',
            classes: 'bg-indigo-100 text-indigo-700',
            icon: getIcon('fa-truck')
        };
    }
    if (deliveryKey === 'delivered') {
        return {
            label: 'Delivered',
            classes: 'bg-blue-100 text-blue-800',
            icon: getIcon('fa-truck')
        };
    }
    if (deliveryKey === 'not_received') {
        return {
            label: 'Not Received',
            classes: 'bg-red-100 text-red-700',
            icon: getIcon('fa-triangle-exclamation')
        };
    }
    if (statusKey === 'validated') {
        return { 
            label: 'Validated', 
            classes: 'bg-blue-100 text-blue-700',
            icon: getIcon('fa-check-circle')
        };
    }
    if (statusKey === 'approved' || statusKey === 'qr_verified') {
        return { 
            label: 'Approved', 
            classes: 'bg-green-100 text-green-700',
            icon: getIcon('fa-check-circle')
        };
    }
    if (statusKey === 'rejected') {
        return { 
            label: 'Rejected', 
            classes: 'bg-red-100 text-red-700',
            icon: getIcon('fa-times-circle')
        };
    }
    if (statusKey === 'returned') {
        return { 
            label: 'Returned', 
            classes: 'bg-emerald-100 text-emerald-700',
            icon: getIcon('fa-arrow-left')
        };
    }
    if (statusKey === 'pending') {
        return { 
            label: 'Pending', 
            classes: 'bg-yellow-100 text-yellow-700',
            icon: getIcon('fa-clock')
        };
    }
    return { 
        label: humanizeStatus(statusKey || 'pending'), 
        classes: 'bg-gray-100 text-gray-700',
        icon: getIcon('fa-question-circle')
    };
}

function formatBorrowRequestCode(req) {
    if (!req) return '';
    const formatted = typeof req.formatted_request_id === 'string' ? req.formatted_request_id.trim() : '';
    if (formatted) return formatted;
    const rawId = req.id ?? null;
    if (!rawId) return '';
    return `BR-${String(rawId).padStart(4, '0')}`;
}

function renderBorrowRequests() {
    const tbody = document.getElementById('borrowRequestsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    // Apply filters
    const normalizedSearch = (BR_SEARCH_TERM || '').toLowerCase().trim();
    const normalizedStatus = (BR_STATUS_FILTER || '').toLowerCase().trim();
    const filtered = BORROW_CACHE.filter((req) => {
        const borrowerName = [req.user?.first_name, req.user?.last_name].filter(Boolean).join(' ').toLowerCase();
        const idText = `${String(req.id ?? '')} ${formatBorrowRequestCode(req)}`.toLowerCase();
        const statusText = String(req.status ?? '').toLowerCase();
        const matchesSearch = !normalizedSearch || borrowerName.includes(normalizedSearch) || idText.includes(normalizedSearch);

        // Normal status filtering
        let matchesStatus = false;
        if (!normalizedStatus) {
            matchesStatus = true;
        } else if (normalizedStatus === 'overdue') {
            matchesStatus = isOverdue(req);
        } else if (normalizedStatus === 'approved' && statusText === 'qr_verified') {
            matchesStatus = true;
        } else {
            matchesStatus = statusText.includes(normalizedStatus);
        }

        return matchesSearch && matchesStatus;
    });

    if (!filtered.length) {
        const template = document.getElementById('borrow-requests-empty-state-template');
        tbody.innerHTML = '';
        if (template?.content?.firstElementChild) {
            tbody.appendChild(template.content.firstElementChild.cloneNode(true));
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="py-10 text-center text-gray-500">No requests found.</td></tr>';
        }
        return;
    }

    filtered.forEach((req) => {
        const tr = document.createElement('tr');
        tr.className = 'transition hover:bg-purple-50 hover:shadow-md';
        tr.dataset.requestId = String(req.id ?? '');

        const borrowerName = [req.user?.first_name, req.user?.last_name].filter(Boolean).join(' ').trim() || 'Unknown';
        const requestCode = formatBorrowRequestCode(req);

        const tdId = `<td class="px-4 py-3">${escapeHtml(requestCode || String(req.id ?? ''))}</td>`;
        const tdBorrower = `<td class="px-4 py-3">${escapeHtml(borrowerName)}</td>`;
        const tdBorrowDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.borrow_date))}</td>`;
        // Return date with overdue label when applicable
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

        const { label: statusLabel, classes: statusClasses, icon } = buildStatusBadge(req.status, req.delivery_status);
        const tdStatus = `<td class="px-4 py-3"><span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full ${statusClasses}">${icon || ''}${escapeHtml(statusLabel)}</span></td>`;

        const tdActions = document.createElement('td');
        tdActions.className = 'px-4 py-3';
        const wrapper = document.createElement('div');
        wrapper.className = 'flex justify-center gap-2';

        const statusKeyRaw = String(req.status || '').toLowerCase();
        const statusKey = statusKeyRaw === 'qr_verified' ? 'approved' : statusKeyRaw;
        const deliveryKey = String(req.delivery_status || '').toLowerCase();

        if (statusKey === 'pending') {
            wrapper.appendChild(createButtonFromTemplate('btn-validate-template', req.id));
            wrapper.appendChild(createButtonFromTemplate('btn-reject-template', req.id));
        } else if (statusKey === 'validated') {
            const pendingTag = document.createElement('span');
            pendingTag.className = 'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border border-sky-200 bg-sky-50 text-sky-700';
            pendingTag.innerHTML = '<i class="fas fa-clock text-[0.7rem]"></i><span>Pending Submission</span>';
            wrapper.appendChild(pendingTag);
        } else if (statusKey === 'approved' && !['dispatched','delivered'].includes(deliveryKey)) {
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
            wrapper.appendChild(createButtonFromTemplate('btn-deliver-template', req.id));
        } else if (deliveryKey === 'dispatched') {
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
        } else if (deliveryKey === 'delivered' || ['returned', 'rejected'].includes(statusKey)) {
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
        } else {
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
        }

        tdActions.appendChild(wrapper);

        tr.innerHTML = tdId + tdBorrower + tdBorrowDate + tdReturnDate + tdStatus;
        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });
}

function collectManpowerAssignments() {
    const containerIds = ['assignPhysicalItemsContainer', 'assignManpowerItemsContainer'];
    const rows = [];
    let error = null;

    containerIds.forEach((containerId) => {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.querySelectorAll('[data-borrow-request-item-id]').forEach((row) => {
            const itemId = row.dataset.borrowRequestItemId;
            if (!itemId) return;

            const originalQty = parseInt(row.dataset.originalQty || '0', 10);
            const qtyInput = row.querySelector('.assign-qty-input');
            let quantity = parseInt(qtyInput?.value || '0', 10);
            if (!Number.isFinite(quantity) || quantity < 0) quantity = 0;
            if (Number.isFinite(originalQty) && originalQty >= 0 && quantity > originalQty) {
                quantity = originalQty;
                if (qtyInput) qtyInput.value = String(quantity);
            }

            const reasonSelect = row.querySelector('.assign-qty-reason');
            const requiresReason = Number.isFinite(originalQty) && quantity < originalQty;
            const quantityReason = requiresReason ? (reasonSelect?.value?.trim() || '') : null;

            if (requiresReason && !quantityReason && !error) {
                const label = row.dataset.rowLabel || 'this entry';
                error = `Please select a reason for reducing ${label}.`;
                if (reasonSelect) {
                    reasonSelect.classList.add('border-red-500');
                }
            }

            rows.push({
                borrow_request_item_id: itemId,
                quantity,
                quantity_reason: quantityReason,
                original_quantity: originalQty,
            });
        });
    });

    return { rows, error };
}

function handleQtyInputChange(event) {
    const input = event?.target;
    if (!input) return;
    const row = input.closest('[data-borrow-request-item-id]');
    if (!row) return;

    const original = parseInt(row.dataset.originalQty || '0', 10);
    let value = parseInt(input.value || '0', 10);
    if (!Number.isFinite(value) || value < 0) value = 0;
    if (Number.isFinite(original) && original >= 0 && value > original) {
        value = original;
    }
    input.value = String(value);

    const reasonWrapper = row.querySelector('.assign-qty-reason-wrapper');
    const reasonSelect = row.querySelector('.assign-qty-reason');
    if (!reasonWrapper || !reasonSelect) return;

    if (Number.isFinite(original) && value < original) {
        reasonWrapper.classList.remove('hidden');
    } else {
        reasonWrapper.classList.add('hidden');
        reasonSelect.value = '';
    }
    reasonSelect.classList.remove('border-red-500');
}

function openAssignManpowerModal(id) {
    const req = BORROW_CACHE.find((r) => r.id === id);
    if (!req) {
        showError('Request not found.');
        return;
    }

    const requestIdInput = document.getElementById('assignManpowerRequestId');
    const locationEl = document.getElementById('assignManpowerLocation');
    const letterPreview = document.getElementById('assignLetterPreview');
    const letterFallback = document.getElementById('assignLetterFallback');

    if (!requestIdInput) return;

    requestIdInput.value = id;

    if (locationEl) {
        locationEl.textContent = req.location && req.location.trim() !== '' ? req.location : '--';
    }

    // Populate borrow dates
    const borrowDateEl = document.getElementById('assignBorrowDate');
    const returnDateEl = document.getElementById('assignReturnDate');
    if (borrowDateEl) {
        borrowDateEl.textContent = req.borrow_date ? formatDate(req.borrow_date) : '--';
    }
    if (returnDateEl) {
        returnDateEl.textContent = req.return_date ? formatDate(req.return_date) : '--';
    }

    renderAssignmentSections(req);

    // Handle letter URL - prioritize letter_url from backend, fallback to letter_path
    let letterUrl = req.letter_url || '';
    
    // If we have a letter_url, convert it to a relative URL to avoid localhost/port issues
    if (letterUrl) {
        // Extract just the path part if it's a full URL
        if (letterUrl.startsWith('http')) {
            try {
                const urlObj = new URL(letterUrl);
                letterUrl = urlObj.pathname.replace(/^\/+/, '/');
            } catch (e) {
                console.warn('Failed to parse letter URL:', letterUrl);
            }
        }
    } else if (req.letter_path) {
        // If we only have a path, construct the storage URL
        if (req.letter_path.startsWith('http')) {
            try {
                const urlObj = new URL(req.letter_path);
                letterUrl = urlObj.pathname;
            } catch (e) {
                letterUrl = req.letter_path;
            }
        } else {
            // Remove any leading 'storage/' or 'public/' prefixes and construct proper URL
            let cleanPath = req.letter_path.replace(/^(storage\/|public\/)/, '');
            letterUrl = '/storage/' + cleanPath;
        }
    }
    
    // Debug logging
    console.log('Letter debug info:', {
        letter_url: req.letter_url,
        letter_path: req.letter_path,
        computed_url: letterUrl,
        request_id: id
    });
    
    if (letterPreview && letterFallback) {
        if (letterUrl) {
            // Reset states first
            letterPreview.classList.add('hidden');
            letterFallback.classList.add('hidden');
            
            // Check if it's an image (by URL extension or type)
            const isImage = /\.(jpg|jpeg|png|webp|gif|bmp|svg)$/i.test(letterUrl) || /\.(jpg|jpeg|png|webp|gif|bmp|svg)$/i.test(req.letter_path || '');
            
            console.log('Is image?', isImage, 'URL:', letterUrl);
            
            if (isImage) {
                letterPreview.onerror = () => {
                    console.error('Failed to load image:', letterUrl);
                    letterPreview.classList.add('hidden');
                    letterFallback.classList.remove('hidden');
                    letterFallback.textContent = 'Letter uploaded (cannot preview)';
                };
                letterPreview.onload = () => {
                    console.log('Image loaded successfully:', letterUrl);
                    letterPreview.classList.remove('hidden');
                    letterFallback.classList.add('hidden');
                };
                // Set src and show preview (onload will hide fallback if successful)
                letterFallback.textContent = 'Loading letter...';
                letterFallback.classList.remove('hidden');
                letterPreview.src = letterUrl;
            } else {
                // PDF or other file type
                letterPreview.classList.add('hidden');
                letterFallback.classList.remove('hidden');
                letterFallback.textContent = 'Letter uploaded (PDF or non-image file)';
            }
        } else {
            letterPreview.src = '';
            letterPreview.classList.add('hidden');
            letterFallback.classList.remove('hidden');
            letterFallback.textContent = 'No letter uploaded';
        }
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'assignManpowerModal' }));
}

function fillRequestModal(req) {
    if (!req) return;
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (!el) return;
        const normalized = value === undefined || value === null || value === '' ? '—' : value;
        el.textContent = normalized;
    };
    const pickText = (...values) => {
        for (const value of values) {
            if (typeof value === 'string') {
                const trimmed = value.trim();
                if (trimmed.length) return trimmed;
            }
        }
        return '';
    };

    const fullName = [req.user?.first_name, req.user?.last_name].filter(Boolean).join(' ').trim() || 'Unknown';
    const requestCode = formatBorrowRequestCode(req) || (req.id ? `#${req.id}` : '—');
    setText('requestTitle', 'Borrow Request Details');
    setText('requestShortStatus', `Borrow Request ${requestCode}`);
    setText('requestSummaryCode', requestCode);
    setText('requestSummaryBorrower', fullName);

    const itemsArray = Array.isArray(req.items) ? req.items : [];
    const itemsLabel = itemsArray.length === 1 ? '1 item' : `${itemsArray.length} items`;
    setText('requestSummaryItems', itemsLabel);

    const purposeValue = pickText(req.purpose, req.purpose_description, req.purpose_text, req.purpose_detail);
    setText('requestSummaryPurpose', purposeValue || '—');

    const statusContainer = document.getElementById('requestSummaryStatus');
    if (statusContainer) {
        const { label, classes, icon } = buildStatusBadge(req.status, req.delivery_status);
        statusContainer.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full ${classes}">${icon || ''}<span>${escapeHtml(label)}</span></span>`;
    }

    setText('requestScheduleBorrow', formatDate(req.borrow_date));
    setText('requestScheduleReturn', formatDate(req.return_date));

    // Show overdue alert inside details modal when applicable
    try {
        const overdueAlert = document.getElementById('requestScheduleOverdueAlert');
        const overdueText = document.getElementById('requestScheduleOverdueAlertText');
        if (overdueAlert && overdueText) {
            const days = computeOverdueDays(req);
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

    const rawUsageValue = pickText(req.time_of_usage);
    const hasUsage = rawUsageValue.length > 0;
    const scheduleTimeEl = document.getElementById('requestScheduleTime');
    if (scheduleTimeEl) {
        scheduleTimeEl.textContent = hasUsage ? formatUsageRange(rawUsageValue) : '—';
    }
    const scheduleTimeRow = document.getElementById('requestScheduleTimeRow');
    if (scheduleTimeRow) {
        scheduleTimeRow.classList.toggle('hidden', !hasUsage);
    }

    let municipality = pickText(
        req.municipality,
        req.municipality_name,
        req.municipality_label,
        req.location_municipality,
        req.delivery_municipality
    );
    let barangay = pickText(
        req.barangay,
        req.barangay_name,
        req.location_barangay,
        req.delivery_barangay
    );
    let specificArea = pickText(
        req.specific_area,
        req.location_specific_area,
        req.delivery_specific_area,
        req.address_specific_area
    );
    const compositeLocation = pickText(
        req.delivery_location,
        req.location,
        req.address,
        req.full_location
    );

    if (compositeLocation) {
        const parts = compositeLocation.split(',').map((part) => part.trim()).filter(Boolean);

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

    setText('requestLocationMunicipality', municipality || '—');
    setText('requestLocationBarangay', barangay || '—');
    setText('requestLocationArea', specificArea || '—');

    const itemsEl = document.getElementById('requestItemsList');
    if (itemsEl) {
        const itemsHtml = itemsArray.map((item) => {
            const isManpower = isManpowerEntry(item);
            const displayName = escapeHtml(resolveBorrowItemName(item));
            const qty = escapeHtml(String(item.quantity ?? 0));
            const reason = item.quantity_reason ? ` - Reason: ${escapeHtml(item.quantity_reason)}` : '';
            const roleBadge = isManpower && item.manpower_role_id
                ? `<span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-purple-100 text-purple-700"><i class="fas fa-user-shield text-xs"></i>${escapeHtml(item.manpower_role || displayName || 'Manpower')}</span>`
                : '';
            return `<li>${displayName} (x${qty})${reason}${roleBadge}</li>`;
        }).join('');
        itemsEl.innerHTML = itemsHtml || '<li class="list-none text-gray-500">No items recorded.</li>';
    }

    const rejectionCard = document.getElementById('rejectionReasonCard');
    if (rejectionCard) {
        const subjectEl = document.getElementById('rejectionReasonSubject');
        const detailEl = document.getElementById('rejectionReasonDetail');
        const statusValue = String(req.status || '').toLowerCase();
        const subjectValue = typeof req.reject_category === 'string' ? req.reject_category.trim() : '';
        const detailValue = typeof req.reject_reason === 'string' ? req.reject_reason.trim() : '';
        const hasReason = subjectValue !== '' || detailValue !== '';

        if (statusValue === 'rejected' && hasReason) {
            if (subjectEl) subjectEl.textContent = subjectValue || 'No subject provided';
            if (detailEl) detailEl.textContent = detailValue || 'No detailed reason provided.';
            rejectionCard.classList.remove('hidden');
        } else {
            if (subjectEl) subjectEl.textContent = '';
            if (detailEl) detailEl.textContent = '';
            rejectionCard.classList.add('hidden');
        }
    }
}

async function approveRequest(id) {
    const req = BORROW_CACHE.find((r) => r.id === id);
    if (!req) {
        showError('Request not found.');
        return;
    }

    // Check if request is validated before approving
    if (req.status !== 'validated') {
        showError('Only validated requests can be approved.');
        return;
    }

    try {
        // Use the same updateRequest function to set status to approved
        // This will trigger the same backend logic as QR scan
        await updateRequest(Number(id), 'approved', {});
        showSuccess('Request approved successfully.');
    } catch (error) {
        console.error('Failed to approve request', error);
        showError(error?.message || 'Failed to approve request. Please try again.');
    }
}

function viewRequest(id) {
    const req = BORROW_CACHE.find((r) => r.id === id);
    if (!req) {
        showError('Request not found.');
        return;
    }
    fillRequestModal(req);
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'requestDetailsModal' }));
}

function openConfirmModal(id, status) {
    const confirmBtn = document.getElementById('confirmActionConfirmBtn');
    const iconEl = document.getElementById('confirmActionIcon');
    const titleEl = document.getElementById('confirmActionTitle');
    const messageEl = document.getElementById('confirmActionMessage');

    const action = String(status || '').toLowerCase();
    let title = 'Confirm Action';
    let message = 'Are you sure you want to continue?';
    let iconClass = 'fas fa-exclamation-circle text-yellow-500';

    if (action === 'rejected') {
        resetRejectionFlow();
        openRejectionFlow(id);
        return;
    } else if (action === 'dispatch' || action === 'dispatched') {
        title = 'Dispatch Items';
        message = 'Confirm that the items are now on their way to the borrower.';
        iconClass = 'fas fa-truck text-indigo-600';
    }

    if (iconEl) iconEl.className = iconClass;
    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;
    if (confirmBtn) {
        confirmBtn.dataset.requestId = id;
        confirmBtn.dataset.status = action;
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirmActionModal' }));
}

async function openDeliverItemsModal(id) {
    const req = BORROW_CACHE.find((r) => r.id === id);
    if (!req) {
        showError('Request not found.');
        return;
    }

    // Check if request is approved before allowing dispatch
    if (req.status !== 'approved') {
        showError('Only approved requests can be dispatched.');
        return;
    }

    // Directly dispatch without modal - removed complex validation logic
    openConfirmModal(id, 'dispatch');
}

async function updateRequest(id, status, options = {}) {
    const {
        assignments = null,
        manpowerTotal = null,
        manpowerReason = null,
        button = null,
        silent = false,
        rejectReasonId = null,
        rejectSubject = null,
        rejectDetail = null,
    } = options || {};

    const manpowerReasonProvided = Object.prototype.hasOwnProperty.call(options || {}, 'manpowerReason');
    const rejectSubjectProvided = Object.prototype.hasOwnProperty.call(options || {}, 'rejectSubject');
    const rejectDetailProvided = Object.prototype.hasOwnProperty.call(options || {}, 'rejectDetail');

    if (button) button.disabled = true;
    try {
        const body = { status };
        if (assignments) body.manpower_assignments = assignments;
        if (Number.isFinite(manpowerTotal)) body.manpower_total = manpowerTotal;
        if (manpowerReasonProvided) body.manpower_reason = manpowerReason;

        if (rejectReasonId !== null && rejectReasonId !== undefined && rejectReasonId !== '') {
            const numericReasonId = Number(rejectReasonId);
            if (!Number.isNaN(numericReasonId)) {
                body.reject_reason_id = numericReasonId;
            }
        }

        if (rejectSubjectProvided) {
            const normalizedSubject = typeof rejectSubject === 'string' ? rejectSubject.trim() : '';
            body.reject_subject = normalizedSubject;
        }

        if (rejectDetailProvided) {
            const normalizedDetail = typeof rejectDetail === 'string' ? rejectDetail.trim() : '';
            body.reject_detail = normalizedDetail;
        }

        const res = await fetch(`/admin/borrow-requests/${id}/update-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            throw new Error(data?.message || `Update failed (status ${res.status})`);
        }

        if (!silent) {
            const statusMessages = {
                'pending': 'Request status set to pending.',
                'validated': 'Request validated successfully.',
                'approved': 'Request approved successfully.',
                'rejected': 'Request rejected.',
                'returned': 'Request marked as returned.',
                'return_pending': 'Request marked as return pending.',
            };
            const message = statusMessages[status] || data?.message || 'Borrow request status updated successfully.';
            showSuccess(message);
        }
        await loadBorrowRequests();

        try {
            const payload = { borrow_request_id: Number(id), new_status: status, timestamp: Date.now() };
            localStorage.setItem('borrow_request_updated', JSON.stringify(payload));
            setTimeout(() => { try { localStorage.removeItem('borrow_request_updated'); } catch (e) {} }, 1000);
        } catch (storageError) {
            console.warn('Could not set storage event', storageError);
        }

        return data;
    } catch (error) {
        console.error('Failed to update request', error);
            if (!silent) showError(error?.message || 'Failed to update request status. Please try again.');
        throw error;
    } finally {
        if (button) button.disabled = false;
    }
}

(function bindAssignManpowerSubmit() {
    document.addEventListener('DOMContentLoaded', () => {
        const submitBtn = document.getElementById('assignManpowerConfirmBtn');
        if (!submitBtn) return;

        submitBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                const requestId = document.getElementById('assignManpowerRequestId')?.value;
                if (!requestId) {
                    showError('Request identifier is missing. Please refresh the page.');
                    return;
                }

                const { rows, error } = collectManpowerAssignments();
                if (error) {
                    showError(error);
                    return;
                }

                submitBtn.disabled = true;
                await updateRequest(Number(requestId), 'validated', {
                    assignments: rows,
                    button: submitBtn,
                });
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'assignManpowerModal' }));
            } catch (error) {
                console.error(error);
                if (error?.message) {
                    showError(error.message);
                }
            } finally {
                submitBtn.disabled = false;
            }
        });
    });
})();

(function bindRejectionReasonModals() {
    document.addEventListener('DOMContentLoaded', () => {
        const optionsContainer = document.getElementById('rejectReasonOptions');
        if (optionsContainer) {
            optionsContainer.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                if (target.name !== 'rejectReasonChoice') return;
                rejectionFlowState.selectedReasonId = target.value;
                updateRejectConfirmButton();
            });

            optionsContainer.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                const action = target.dataset.action;
                if (!action) return;

                event.preventDefault();
                event.stopPropagation();

                const reasonId = target.dataset.reasonId;
                if (!reasonId) return;

                if (action === 'view') {
                    const reason = getRejectionReasonById(reasonId);
                    if (!reason) {
                        showError('The selected rejection reason is no longer available.');
                        return;
                    }
                    openRejectionDetailModal(reason);
                } else if (action === 'remove') {
                    openRejectionDeleteModal(reasonId);
                }
            });
        }

        const otherOption = document.getElementById('rejectReasonOtherOption');
        if (otherOption) {
            otherOption.addEventListener('change', (event) => {
                const input = event.target;
                if (!(input instanceof HTMLInputElement)) return;
                if (input.checked) {
                    rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
                    updateRejectConfirmButton();
                }
            });
        }

        const createNewBtn = document.getElementById('rejectReasonCreateNewBtn');
        if (createNewBtn) {
            createNewBtn.addEventListener('click', () => {
                if (!rejectionFlowState.requestId) {
                    showError('No borrow request selected.');
                    return;
                }
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonSelectModal' }));
                openRejectionCustomModal(rejectionFlowState.requestId, { fromSelection: true });
            });
        }

        const selectConfirmBtn = document.getElementById('rejectReasonSelectConfirmBtn');
        if (selectConfirmBtn) {
            selectConfirmBtn.addEventListener('click', async () => {
                if (!rejectionFlowState.requestId) {
                    showError('No borrow request selected.');
                    return;
                }

                if (!rejectionFlowState.selectedReasonId) {
                    showError('Please select a rejection reason before continuing.');
                    return;
                }

                if (rejectionFlowState.selectedReasonId === OTHER_REJECTION_REASON_KEY) {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonSelectModal' }));
                    openRejectionCustomModal(rejectionFlowState.requestId, { fromSelection: true });
                    return;
                }

                const reason = getRejectionReasonById(rejectionFlowState.selectedReasonId);
                if (!reason) {
                    showError('The selected rejection reason is no longer available.');
                    return;
                }

                selectConfirmBtn.disabled = true;
                try {
                    await submitRejectionDecision({
                        requestId: rejectionFlowState.requestId,
                        reasonId: reason.id,
                        subject: reason.subject,
                        detail: reason.detail,
                        button: selectConfirmBtn,
                        modalsToClose: ['rejectReasonSelectModal'],
                    });
                } finally {
                    selectConfirmBtn.disabled = false;
                }
            });
        }

        const selectCancelBtn = document.getElementById('rejectReasonSelectCancelBtn');
        if (selectCancelBtn) {
            selectCancelBtn.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonSelectModal' }));
                resetRejectionFlow();
            });
        }

        const customConfirmBtn = document.getElementById('rejectReasonCustomConfirmBtn');
        if (customConfirmBtn) {
            customConfirmBtn.addEventListener('click', async () => {
                await saveCustomRejectionReason(customConfirmBtn);
            });
        }

        const customBackBtn = document.getElementById('rejectReasonCustomBackBtn');
        if (customBackBtn) {
            customBackBtn.addEventListener('click', () => {
                handleCustomRejectionBack();
            });
        }

        const deleteConfirmBtn = document.getElementById('rejectReasonDeleteConfirmBtn');
        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', async () => {
                const reasonId = deleteConfirmBtn.dataset.reasonId;
                if (!reasonId) {
                    showError('Unable to remove the selected reason.');
                    return;
                }
                await deleteRejectionReason(reasonId, deleteConfirmBtn);
            });
        }

        const deleteCancelBtn = document.getElementById('rejectReasonDeleteCancelBtn');
        if (deleteCancelBtn) {
            deleteCancelBtn.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonDeleteModal' }));
            });
        }
    });
})();

(function bindConfirmAction() {
    document.addEventListener('DOMContentLoaded', () => {
        const confirmBtn = document.getElementById('confirmActionConfirmBtn');
        if (!confirmBtn) return;

        confirmBtn.addEventListener('click', async () => {
            const id = confirmBtn.dataset.requestId;
            const actionStatus = String(confirmBtn.dataset.status || '').toLowerCase();
            if (!id || !actionStatus) {
                showError('Invalid request or action. Please try again.');
                return;
            }

            const modalName = 'confirmActionModal';
            let modalClosed = false;
            confirmBtn.disabled = true;
            try {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
                modalClosed = true;

                if (actionStatus === 'dispatch' || actionStatus === 'dispatched') {
                    const res = await fetch(`/admin/borrow-requests/${encodeURIComponent(id)}/dispatch`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({}),
                    });
                    const payload = await res.json().catch(() => null);
                    if (!res.ok) {
                        throw new Error(payload?.message || `Failed to dispatch items (status ${res.status})`);
                    }
                    showSuccess(payload?.message || 'Items dispatched successfully.');
                    await loadBorrowRequests();
                } else {
                    await updateRequest(Number(id), actionStatus, { button: confirmBtn });
                }
            } catch (error) {
                console.error('Confirm action failed', error);
                if (modalClosed) {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: modalName }));
                }
                if (actionStatus === 'dispatch' || actionStatus === 'dispatched') {
                    showError(error?.message || 'Failed to perform action. Please try again.');
                }
            } finally {
                confirmBtn.disabled = false;
            }
        });
    });
})();

// ---------- boot ----------
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('borrowRequestsTableBody')) {
        loadBorrowRequests();
        setInterval(loadBorrowRequests, 10000);
    }

    // Wire up search and status filter
    const brSearch = document.getElementById('borrow-requests-live-search');
    if (brSearch) {
        brSearch.addEventListener('input', (e) => { BR_SEARCH_TERM = e.target.value || ''; renderBorrowRequests(); });
        brSearch.addEventListener('focus', function(){ this.placeholder = 'Type to Search'; });
        brSearch.addEventListener('blur', function(){ this.placeholder = 'Search Borrower and Request'; });
    }
    const brStatus = document.getElementById('borrow-requests-status-filter');
    if (brStatus) {
        // remove native arrow via inline styles as safety (Edge)
        brStatus.style.appearance = 'none';
        brStatus.style.webkitAppearance = 'none';
        brStatus.style.MozAppearance = 'none';
        brStatus.addEventListener('change', (e) => { BR_STATUS_FILTER = e.target.value || ''; renderBorrowRequests(); });
    }
});

window.loadBorrowRequests = loadBorrowRequests;
window.openAssignManpowerModal = openAssignManpowerModal;
window.viewRequest = viewRequest;
window.formatDate = formatDate;

window.addEventListener('realtime:borrow-request-submitted', () => {
    loadBorrowRequests();
});

window.addEventListener('realtime:borrow-request-status-updated', () => {
    loadBorrowRequests();
});
