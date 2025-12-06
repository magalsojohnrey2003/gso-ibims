const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const LIST_ROUTE = window.LIST_ROUTE || '/admin/borrow-requests/list';
const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];
const MANPOWER_PLACEHOLDER = '__SYSTEM_MANPOWER_PLACEHOLDER__';
const MANPOWER_ROLES_ENDPOINT = window.MANPOWER_ROLES_ENDPOINT || '/admin/manpower-roles';
const DEFAULT_ASSIST_ROLE_NAME = 'Assist';
const DEFAULT_ASSIST_DEFAULT_QTY = 10;

let BORROW_CACHE = [];
let BR_SEARCH_TERM = '';
let BR_STATUS_FILTER = '';
const REJECTION_REASONS_ENDPOINT = window.REJECTION_REASONS_ENDPOINT || '/admin/rejection-reasons';
const OTHER_REJECTION_REASON_KEY = '__other__';

const BORROWING_STATUS_META = {
    good: {
        label: 'Good Standing',
        badgeClass: 'bg-emerald-100 text-emerald-700',
        icon: 'fa-circle-check',
    },
    fair: {
        label: 'Fair Standing',
        badgeClass: 'bg-amber-100 text-amber-700',
        icon: 'fa-circle-exclamation',
        alertClass: 'border-amber-200 bg-amber-50 text-amber-800',
        alertIcon: 'fa-circle-exclamation',
        alertMessage: 'Borrower flagged for review. Verify outstanding incidents before validating this request.',
    },
    risk: {
        label: 'At Risk',
        badgeClass: 'bg-red-100 text-red-700',
        icon: 'fa-triangle-exclamation',
        alertClass: 'border-red-200 bg-red-50 text-red-700',
        alertIcon: 'fa-triangle-exclamation',
        alertMessage: 'Borrower marked At Risk. Coordinate with management before approving this request.',
    },
};

const BORROWER_STATUS_BADGE_BASE = 'inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full';
const BORROWER_STATUS_ALERT_BASE = 'rounded-lg border px-3 py-2 text-sm flex items-start gap-2';

const BORROWER_INCIDENT_REMINDER = {
    alertClass: 'border-amber-200 bg-amber-50 text-amber-800',
    alertIcon: 'fa-circle-exclamation',
    alertMessage: 'Borrower has recorded damage incidents. Review the history before validating.',
};

let REJECTION_REASONS_CACHE = [];
let MANPOWER_ROLES_CACHE = [];
const rejectionFlowState = {
    requestId: null,
    selectedReasonId: null,
    prevModal: null,
};

const assignManpowerState = {
    requestId: null,
    manpowerRows: [],
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
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonSelectModal' }));

    const targetIdRaw = assignManpowerState.requestId || rejectionFlowState.requestId;
    const targetIdNumeric = Number(targetIdRaw);
    const targetId = Number.isNaN(targetIdNumeric) ? targetIdRaw : targetIdNumeric;

    resetRejectionFlow();

    if (targetId) {
        setTimeout(() => {
            openAssignManpowerModal(targetId);
        }, 120);
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

async function fetchManpowerRoles(force = false) {
    if (!force && MANPOWER_ROLES_CACHE.length) {
        return MANPOWER_ROLES_CACHE;
    }

    try {
        const res = await fetch(MANPOWER_ROLES_ENDPOINT, { headers: { Accept: 'application/json' } });
        if (!res.ok) {
            const payload = await res.json().catch(() => null);
            const message = payload?.message || `Failed to load manpower roles (status ${res.status})`;
            throw new Error(message);
        }

        const data = await res.json().catch(() => []);
        const normalized = Array.isArray(data) ? data : [];
        MANPOWER_ROLES_CACHE = normalized
            .map((role) => ({
                id: Number(role?.id ?? 0),
                name: typeof role?.name === 'string' ? role.name.trim() : '',
            }))
            .filter((role) => role.id && role.name);
        MANPOWER_ROLES_CACHE.sort((a, b) => a.name.localeCompare(b.name));
        return MANPOWER_ROLES_CACHE;
    } catch (error) {
        console.error('Failed to load manpower roles', error);
        throw error;
    }
}

function findRoleByName(name) {
    if (!name) return null;
    const normalized = String(name).toLowerCase();
    return MANPOWER_ROLES_CACHE.find((role) => role.name.toLowerCase() === normalized) || null;
}

function generateManpowerRowKey() {
    return `mp-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
}

function isRoleInUse(roleId, exceptKey = null) {
    if (!roleId) return false;
    return assignManpowerState.manpowerRows.some((row) => row.manpower_role_id === roleId && row.key !== exceptKey);
}

function getAvailableRoles(exceptKey = null) {
    return MANPOWER_ROLES_CACHE.filter((role) => !isRoleInUse(role.id, exceptKey));
}

function createNewManpowerRow(role = null, quantity = 1) {
    const initialQuantity = Number.isFinite(quantity) && quantity >= 0 ? quantity : 1;
    return {
        key: generateManpowerRowKey(),
        borrow_request_item_id: null,
        manpower_role_id: role ? Number(role.id) : null,
        manpower_role_name: role ? role.name : '',
        quantity: initialQuantity,
        original_quantity: null,
        quantity_reason: null,
        isNew: true,
    };
}

function initializeManpowerRows(manpowerItems = []) {
    assignManpowerState.manpowerRows = Array.isArray(manpowerItems)
        ? manpowerItems.map((item) => {
            const qty = Number(item?.quantity) || 0;
            const reasonRaw = typeof item?.quantity_reason === 'string' ? item.quantity_reason.trim() : '';
            const reason = reasonRaw !== '' ? reasonRaw : null;
            const roleName = resolveBorrowItemName(item) || 'Manpower';
            const roleId = item?.manpower_role_id ? Number(item.manpower_role_id) : null;
            return {
                key: `existing-${item?.id ?? generateManpowerRowKey()}`,
                borrow_request_item_id: item?.id ?? item?.borrow_request_item_id ?? null,
                manpower_role_id: roleId,
                manpower_role_name: roleName,
                quantity: qty,
                original_quantity: qty,
                quantity_reason: reason,
                isNew: false,
            };
        })
        : [];

    if (!assignManpowerState.manpowerRows.length) {
        const assistRole = findRoleByName(DEFAULT_ASSIST_ROLE_NAME);
        if (assistRole) {
            assignManpowerState.manpowerRows.push(createNewManpowerRow(assistRole, DEFAULT_ASSIST_DEFAULT_QTY));
        }
    }
}

function updateRowQuantity(rowKey, rawValue) {
    const row = assignManpowerState.manpowerRows.find((entry) => entry.key === rowKey);
    if (!row) return;
    let numeric = parseInt(rawValue, 10);
    if (!Number.isFinite(numeric) || numeric < 0) {
        numeric = 0;
    }
    if (numeric > 99) {
        numeric = 99;
    }
    row.quantity = numeric;
}

function removeManpowerRow(rowKey) {
    const idx = assignManpowerState.manpowerRows.findIndex((entry) => entry.key === rowKey);
    if (idx === -1) return;
    assignManpowerState.manpowerRows.splice(idx, 1);
    renderManpowerRows();
}

function handleRoleSelectChange(rowKey, selectEl) {
    const row = assignManpowerState.manpowerRows.find((entry) => entry.key === rowKey);
    if (!row) return;

    const raw = selectEl.value;
    if (!raw) {
        row.manpower_role_id = null;
        row.manpower_role_name = '';
        updateAddRoleButtonState();
        return;
    }

    const roleId = Number(raw);
    if (Number.isNaN(roleId)) {
        selectEl.value = row.manpower_role_id ? String(row.manpower_role_id) : '';
        return;
    }

    if (isRoleInUse(roleId, rowKey)) {
        showError('That manpower role is already added.');
        selectEl.value = row.manpower_role_id ? String(row.manpower_role_id) : '';
        return;
    }

    const role = MANPOWER_ROLES_CACHE.find((entry) => entry.id === roleId) || null;
    row.manpower_role_id = role ? role.id : null;
    row.manpower_role_name = role ? role.name : '';
    renderManpowerRows();
}

function updateAddRoleButtonState() {
    const addBtn = document.getElementById('assignManpowerAddRoleBtn');
    if (!addBtn) return;
    const available = getAvailableRoles();
    const disabled = available.length === 0;
    addBtn.disabled = disabled;
    addBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    addBtn.classList.toggle('opacity-50', disabled);
    addBtn.classList.toggle('cursor-not-allowed', disabled);
}

function renderManpowerRows() {
    const container = document.getElementById('assignManpowerItemsContainer');
    const emptyState = document.getElementById('assignManpowerEmptyState');
    if (!container) return;

    container.innerHTML = '';

    if (!assignManpowerState.manpowerRows.length) {
        if (emptyState) emptyState.classList.remove('hidden');
        updateAddRoleButtonState();
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    assignManpowerState.manpowerRows.forEach((row) => {
        const rowEl = document.createElement('div');
        rowEl.dataset.rowKey = row.key;
        rowEl.dataset.borrowRequestItemId = row.borrow_request_item_id ? String(row.borrow_request_item_id) : '';
        rowEl.dataset.originalQty = row.original_quantity != null ? String(row.original_quantity) : '';
        rowEl.dataset.isNewRole = row.isNew ? '1' : '0';
        rowEl.dataset.rowLabel = row.manpower_role_name || 'Manpower';
        rowEl.dataset.isManpower = '1';
        rowEl.className = 'grid gap-3 items-start md:grid-cols-[minmax(0,1.4fr)_130px_minmax(0,1fr)] border-b border-gray-200 pb-3 last:border-b-0 last:pb-0';

        const infoDiv = document.createElement('div');
        infoDiv.className = 'space-y-2';

        if (row.isNew) {
            const roleLabel = document.createElement('label');
            roleLabel.className = 'text-xs font-semibold text-gray-600 uppercase tracking-wide block';
            roleLabel.textContent = 'Manpower Role';

            const roleSelect = document.createElement('select');
            roleSelect.className = 'assign-manpower-role gov-input text-sm';
            roleSelect.dataset.roleSelect = '1';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select role';
            roleSelect.appendChild(placeholder);

            MANPOWER_ROLES_CACHE.forEach((role) => {
                const option = document.createElement('option');
                option.value = String(role.id);
                option.textContent = role.name;
                if (row.manpower_role_id === role.id) {
                    option.selected = true;
                } else if (isRoleInUse(role.id, row.key)) {
                    option.disabled = true;
                }
                roleSelect.appendChild(option);
            });

            roleSelect.addEventListener('change', (event) => {
                event.target.classList.remove('border-red-500');
                handleRoleSelectChange(row.key, event.target);
            });

            infoDiv.appendChild(roleLabel);
            infoDiv.appendChild(roleSelect);
        } else {
            const nameEl = document.createElement('p');
            nameEl.className = 'font-medium text-gray-900';
            nameEl.textContent = row.manpower_role_name || 'Manpower';

            const metaEl = document.createElement('span');
            metaEl.className = 'text-xs text-gray-500 block';
            metaEl.textContent = `Requested: x${row.original_quantity ?? row.quantity ?? 0}`;

            infoDiv.appendChild(nameEl);
            infoDiv.appendChild(metaEl);
        }

        rowEl.appendChild(infoDiv);

        const qtyDiv = document.createElement('div');
        qtyDiv.className = 'space-y-1';

        const qtyLabel = document.createElement('label');
        qtyLabel.className = 'text-xs font-semibold text-gray-500 block';
        qtyLabel.textContent = 'Assign Manpower';

        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.min = '0';
        qtyInput.value = String(row.quantity ?? 0);
        qtyInput.className = 'assign-qty-input gov-input text-sm';
        qtyInput.max = '99';
        qtyInput.setAttribute('inputmode', 'numeric');
        qtyInput.setAttribute('pattern', '[0-9]*');

        qtyInput.addEventListener('input', (event) => {
            event.target.classList.remove('border-red-500');
            handleQtyInputChange(event);
            updateRowQuantity(row.key, event.target.value);
        });
        qtyInput.addEventListener('change', (event) => {
            event.target.classList.remove('border-red-500');
            handleQtyInputChange(event);
            updateRowQuantity(row.key, event.target.value);
        });

        qtyDiv.appendChild(qtyLabel);
        qtyDiv.appendChild(qtyInput);

        if (!row.isNew && row.original_quantity != null) {
            const hint = document.createElement('p');
            hint.className = 'text-[11px] text-gray-400';
            hint.textContent = `Max ${row.original_quantity}`;
            qtyDiv.appendChild(hint);
        }

        rowEl.appendChild(qtyDiv);

        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'space-y-1';

        if (row.isNew) {
            const removeWrapper = document.createElement('div');
            removeWrapper.className = 'flex items-start justify-end md:justify-start pt-6';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-role-btn inline-flex items-center justify-center rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition';
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
            removeBtn.setAttribute('aria-label', 'Remove manpower role');
            removeBtn.addEventListener('click', () => {
                removeManpowerRow(row.key);
            });

            removeWrapper.appendChild(removeBtn);
            actionsDiv.appendChild(removeWrapper);
        } else {
            const reasonWrapper = document.createElement('div');
            reasonWrapper.className = 'assign-qty-reason-wrapper hidden space-y-1';

            const reasonLabel = document.createElement('label');
            reasonLabel.className = 'text-xs font-semibold text-gray-500 block';
            reasonLabel.textContent = 'Adjustment Reason';

            const reasonSelect = document.createElement('select');
            reasonSelect.className = 'assign-qty-reason gov-input text-sm';
            reasonSelect.innerHTML = buildReasonOptions(MANPOWER_REASONS);
            if (row.quantity_reason && !MANPOWER_REASONS.includes(row.quantity_reason)) {
                const customOption = document.createElement('option');
                customOption.value = row.quantity_reason;
                customOption.textContent = row.quantity_reason;
                reasonSelect.appendChild(customOption);
            }
            if (row.quantity_reason) {
                reasonSelect.value = row.quantity_reason;
                reasonWrapper.classList.remove('hidden');
            }

            reasonSelect.addEventListener('change', () => {
                reasonSelect.classList.remove('border-red-500');
                const currentRow = assignManpowerState.manpowerRows.find((entry) => entry.key === row.key);
                if (currentRow) {
                    const selected = reasonSelect.value?.trim();
                    currentRow.quantity_reason = selected !== '' ? selected : null;
                }
            });

            const reasonHint = document.createElement('p');
            reasonHint.className = 'text-[11px] text-gray-400';
            reasonHint.textContent = 'Required when reducing the request.';

            reasonWrapper.appendChild(reasonLabel);
            reasonWrapper.appendChild(reasonSelect);
            reasonWrapper.appendChild(reasonHint);
            actionsDiv.appendChild(reasonWrapper);
        }

        rowEl.appendChild(actionsDiv);

        container.appendChild(rowEl);

        handleQtyInputChange({ target: qtyInput });
        updateRowQuantity(row.key, qtyInput.value);
    });

    updateAddRoleButtonState();
    adjustAssignManpowerModalLayout();
}

function adjustAssignManpowerModalLayout() {
    const panel = document.getElementById('assignManpowerModalPanel');
    if (!panel) return;

    const viewport = window.innerHeight || document.documentElement.clientHeight || 0;
    if (!viewport) return;

    const minHeight = Math.max(Math.floor(viewport * 0.65), 520);
    const maxHeight = Math.max(Math.floor(viewport * 0.92), minHeight + 40);

    panel.style.minHeight = `${minHeight}px`;
    panel.style.maxHeight = `${maxHeight}px`;
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
    initializeManpowerRows(manpower);
    renderManpowerRows();
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
        const borrowerCellContent = escapeHtml(borrowerName);

        const tdId = `<td class="px-4 py-3">${escapeHtml(requestCode || String(req.id ?? ''))}</td>`;
        const tdBorrower = `<td class="px-4 py-3">${borrowerCellContent}</td>`;
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
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
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
    const rows = [];
    let error = null;

    const physicalContainer = document.getElementById('assignPhysicalItemsContainer');
    if (physicalContainer) {
        physicalContainer.querySelectorAll('[data-borrow-request-item-id]').forEach((row) => {
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

            const numericId = Number(itemId);
            rows.push({
                borrow_request_item_id: Number.isFinite(numericId) ? numericId : itemId,
                quantity,
                quantity_reason: quantityReason,
                original_quantity: Number.isFinite(originalQty) ? originalQty : null,
            });
        });
    }

    const manpowerContainer = document.getElementById('assignManpowerItemsContainer');
    if (manpowerContainer) {
        manpowerContainer.querySelectorAll('[data-row-key]').forEach((row) => {
            const rowKey = row.dataset.rowKey;
            const stateRow = assignManpowerState.manpowerRows.find((entry) => entry.key === rowKey) || null;
            const qtyInput = row.querySelector('.assign-qty-input');
            let quantity = parseInt(qtyInput?.value || '0', 10);
            if (!Number.isFinite(quantity) || quantity < 0) quantity = 0;

            if (stateRow) {
                stateRow.quantity = quantity;
            }

            const originalQtySource = stateRow && Number.isFinite(stateRow.original_quantity)
                ? stateRow.original_quantity
                : parseInt(row.dataset.originalQty || '', 10);

            const originalQty = Number.isFinite(originalQtySource) ? originalQtySource : null;
            if (quantity > 99) {
                quantity = 99;
                if (qtyInput) qtyInput.value = '99';
                if (stateRow) {
                    stateRow.quantity = 99;
                }
            }

            const isNew = stateRow?.isNew || row.dataset.isNewRole === '1';
            const reasonSelect = row.querySelector('.assign-qty-reason');
            const requiresReason = !isNew && Number.isFinite(originalQty) && quantity < originalQty;
            const quantityReason = requiresReason ? (reasonSelect?.value?.trim() || '') : null;

            if (requiresReason && !quantityReason && !error) {
                const label = row.dataset.rowLabel || 'this manpower entry';
                error = `Please select a reason for reducing ${label}.`;
                if (reasonSelect) {
                    reasonSelect.classList.add('border-red-500');
                }
            }

            if (isNew) {
                const roleSelect = row.querySelector('select.assign-manpower-role');
                const roleId = Number(roleSelect?.value || '0');
                if (!roleId) {
                    if (!error) {
                        error = 'Please select a manpower role for each added row.';
                    }
                    roleSelect?.classList.add('border-red-500');
                    return;
                }
                if (quantity <= 0) {
                    if (!error) {
                        error = 'Please provide a manpower quantity greater than zero.';
                    }
                    if (qtyInput) {
                        qtyInput.classList.add('border-red-500');
                        qtyInput.focus();
                    }
                    return;
                }

                rows.push({
                    borrow_request_item_id: null,
                    manpower_role_id: roleId,
                    quantity,
                    quantity_reason: quantityReason,
                    original_quantity: null,
                });
                return;
            }

            const numericId = stateRow?.borrow_request_item_id
                ? Number(stateRow.borrow_request_item_id)
                : Number(row.dataset.borrowRequestItemId || '0');

            rows.push({
                borrow_request_item_id: Number.isFinite(numericId)
                    ? numericId
                    : (stateRow?.borrow_request_item_id ?? row.dataset.borrowRequestItemId ?? null),
                quantity,
                quantity_reason: quantityReason,
                original_quantity: Number.isFinite(originalQty) ? originalQty : null,
            });
        });
    }

    return { rows, error };
}

function handleQtyInputChange(event) {
    const input = event?.target;
    if (!input) return;
    const row = input.closest('[data-borrow-request-item-id]');
    if (!row) return;

    const isManpower = row.dataset.isManpower === '1';
    const original = parseInt(row.dataset.originalQty || '0', 10);
    const rawValue = String(input.value ?? '');
    const digitsOnly = rawValue.replace(/\D/g, '').slice(0, 2);
    let value = digitsOnly === '' ? 0 : parseInt(digitsOnly, 10);

    if (!Number.isFinite(value) || value < 0) {
        value = 0;
    }

    if (isManpower) {
        if (value > 99) {
            value = 99;
        }
    } else if (Number.isFinite(original) && original >= 0 && value > original) {
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

    const rowKey = row.dataset.rowKey || null;
    if (rowKey) {
        const stateRow = assignManpowerState.manpowerRows.find((entry) => entry.key === rowKey);
        if (stateRow) {
            stateRow.quantity = value;
            if (reasonWrapper.classList.contains('hidden')) {
                stateRow.quantity_reason = null;
            } else {
                const currentReason = reasonSelect.value?.trim();
                stateRow.quantity_reason = currentReason !== '' ? currentReason : null;
            }
        }
    }
}

async function openAssignManpowerModal(id) {
    const req = BORROW_CACHE.find((r) => r.id === id);
    if (!req) {
        showError('Request not found.');
        return;
    }

    const requestIdInput = document.getElementById('assignManpowerRequestId');
    const locationEl = document.getElementById('assignManpowerLocation');
    const letterPreview = document.getElementById('assignLetterPreview');
    const letterFallback = document.getElementById('assignLetterFallback');
    const damageCountEl = document.getElementById('assignBorrowerDamageCount');
    const lastIncidentEl = document.getElementById('assignBorrowerLastIncident');
    const statusAlertEl = document.getElementById('assignBorrowerStatusAlert');

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

    const borrower = req.user || null;
    const statusKey = borrower?.borrowing_status ? String(borrower.borrowing_status).toLowerCase() : 'good';
    const statusMeta = BORROWING_STATUS_META[statusKey] || BORROWING_STATUS_META.good;
    const incidentCount = Number(borrower?.damage_incidents_count || 0);
    const latestIncidentRaw = borrower?.latest_damage_incident_at || null;
    const latestIncidentLabel = latestIncidentRaw ? formatDate(latestIncidentRaw) : 'None recorded';
    const normalizedIncidentLabel = latestIncidentLabel && latestIncidentLabel !== 'N/A' ? latestIncidentLabel : 'None recorded';

    if (damageCountEl) {
        damageCountEl.textContent = String(incidentCount);
    }

    if (lastIncidentEl) {
        lastIncidentEl.textContent = normalizedIncidentLabel;
    }

    if (statusAlertEl) {
        const alertSource = statusMeta.alertMessage ? statusMeta : null;
        const activeAlert = alertSource || (incidentCount > 0 ? BORROWER_INCIDENT_REMINDER : null);
        if (activeAlert) {
            statusAlertEl.className = `${BORROWER_STATUS_ALERT_BASE} ${activeAlert.alertClass}`.trim();
            statusAlertEl.innerHTML = `<i class="fas ${activeAlert.alertIcon || 'fa-circle-exclamation'} mt-0.5"></i><span>${activeAlert.alertMessage}</span>`;
        } else {
            statusAlertEl.className = `${BORROWER_STATUS_ALERT_BASE} hidden`;
            statusAlertEl.innerHTML = '';
        }
    }

    assignManpowerState.requestId = id;
    assignManpowerState.manpowerRows = [];

    try {
        await fetchManpowerRoles(false);
    } catch (error) {
        console.error('Unable to preload manpower roles', error);
        showError('Failed to load manpower roles. You can still adjust existing assignments.');
    }

    renderAssignmentSections(req);
    window.requestAnimationFrame(() => adjustAssignManpowerModalLayout());

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
    const { physical: physicalItems, manpower: manpowerItems } = splitItemsByType(itemsArray);
    const itemsLabel = physicalItems.length === 1 ? '1 item' : `${physicalItems.length} items`;
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
        const itemsHtml = physicalItems.map((item) => {
            const displayName = escapeHtml(resolveBorrowItemName(item));
            const qty = escapeHtml(String(item.quantity ?? 0));
            const reason = item.quantity_reason ? ` - Reason: ${escapeHtml(item.quantity_reason)}` : '';
            return `<li>${displayName} (x${qty})${reason}</li>`;
        }).join('');
        itemsEl.innerHTML = itemsHtml || '<li class="list-none text-gray-500">No items recorded.</li>';
    }

    const manpowerSection = document.getElementById('requestManpowerSection');
    const manpowerList = document.getElementById('requestManpowerList');
    if (manpowerSection && manpowerList) {
        if (!manpowerItems.length) {
            manpowerSection.classList.add('hidden');
            manpowerList.innerHTML = '';
        } else {
            const manpowerHtml = manpowerItems.map((item) => {
                const roleName = item.manpower_role || item.role_name || resolveBorrowItemName(item) || 'Manpower';
                const qtyValue = Number(item.assigned_manpower ?? item.quantity ?? 0);
                const safeRole = escapeHtml(roleName);
                const safeQty = escapeHtml(String(qtyValue > 0 ? qtyValue : (item.quantity ?? 0)));
                return `<li>Manpower - ${safeRole} (x${safeQty || '0'})</li>`;
            }).join('');
            manpowerList.innerHTML = manpowerHtml || '<li class="list-none text-gray-500">No manpower assigned.</li>';
            manpowerSection.classList.remove('hidden');
        }
    }

    const statusCard = document.getElementById('requestItemStatusCard');
    const statusList = document.getElementById('requestItemStatusList');
    if (statusList) {
        if (!physicalItems.length) {
            statusList.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No items recorded.</p>';
        } else {
            const statusRows = physicalItems.map((item) => {
                const displayName = escapeHtml(resolveBorrowItemName(item));
                const requestedQty = Number(item.requested_quantity ?? item.quantity ?? 0);
                const approvedQty = Number(item.approved_quantity ?? item.quantity ?? 0);
                const receivedRaw = item.received_quantity;
                const receivedQty = Number.isFinite(Number(receivedRaw)) ? Number(receivedRaw) : 0;
                const receivedDisplay = receivedRaw === null || receivedRaw === undefined
                    ? '0'
                    : String(receivedQty);

                const approvalBadgeClass = approvedQty >= requestedQty
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-400/60 dark:bg-emerald-900/20 dark:text-emerald-200'
                    : 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-400/60 dark:bg-amber-900/20 dark:text-amber-200';
                const deliveryBadgeClass = receivedQty >= approvedQty
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-400/60 dark:bg-emerald-900/20 dark:text-emerald-200'
                    : 'border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-400/60 dark:bg-indigo-900/20 dark:text-indigo-200';
                const neutralBadgeClass = 'border-gray-200 bg-white text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200';

                return `
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">${displayName}</div>
                            </div>
                            <div class="flex flex-col items-start md:items-end gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Approval</span>
                                    <span class="flex items-center gap-1">
                                        <span class="inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-sm font-semibold transition-colors duration-150 ${approvalBadgeClass}" title="Item Approved">${escapeHtml(String(approvedQty))}</span>
                                        <span class="text-xs text-gray-400">/</span>
                                        <span class="inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-sm font-semibold transition-colors duration-150 ${neutralBadgeClass}" title="Item Requested">${escapeHtml(String(requestedQty))}</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Delivery</span>
                                    <span class="flex items-center gap-1">
                                        <span class="inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-sm font-semibold transition-colors duration-150 ${deliveryBadgeClass}" title="Item Received">${escapeHtml(receivedDisplay)}</span>
                                        <span class="text-xs text-gray-400">/</span>
                                        <span class="inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-sm font-semibold transition-colors duration-150 ${approvalBadgeClass}" title="Item Approved">${escapeHtml(String(approvedQty))}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            statusList.innerHTML = statusRows;
        }
    }
    if (statusCard) {
        statusCard.classList.toggle('hidden', !physicalItems.length);
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
        const rejectBtn = document.getElementById('assignManpowerRejectBtn');
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

        if (rejectBtn) {
            rejectBtn.addEventListener('click', () => {
                const requestId = document.getElementById('assignManpowerRequestId')?.value;
                if (!requestId) {
                    showError('Request identifier is missing. Please refresh the page.');
                    return;
                }

                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'assignManpowerModal' }));
                resetRejectionFlow();
                openRejectionFlow(Number(requestId));
            });
        }
    });
})();

(function bindAddManpowerRoleButton() {
    document.addEventListener('DOMContentLoaded', () => {
        const addBtn = document.getElementById('assignManpowerAddRoleBtn');
        if (!addBtn) return;

        addBtn.addEventListener('click', async () => {
            if (!assignManpowerState.requestId) {
                return;
            }

            if (!MANPOWER_ROLES_CACHE.length) {
                try {
                    await fetchManpowerRoles(true);
                } catch (error) {
                    console.error('Unable to load manpower roles', error);
                    showError('Unable to load manpower roles at this time.');
                    return;
                }
            }

            const available = getAvailableRoles();
            if (!available.length) {
                showError('All manpower roles have already been added.');
                return;
            }

            const defaultRole = available[0] || null;
            const newRow = createNewManpowerRow(defaultRole);
            assignManpowerState.manpowerRows.push(newRow);
            renderManpowerRows();

            window.requestAnimationFrame(() => {
                const selector = document.querySelector(`[data-row-key="${newRow.key}"] select.assign-manpower-role`);
                if (selector && !defaultRole) {
                    selector.focus();
                }
            });
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

    adjustAssignManpowerModalLayout();
});

window.loadBorrowRequests = loadBorrowRequests;
window.openAssignManpowerModal = openAssignManpowerModal;
window.viewRequest = viewRequest;
window.formatDate = formatDate;
window.handleRejectReasonCustomClose = handleCustomRejectionBack;

window.addEventListener('realtime:borrow-request-submitted', () => {
    loadBorrowRequests();
});

window.addEventListener('realtime:borrow-request-status-updated', () => {
    loadBorrowRequests();
});

window.addEventListener('resize', adjustAssignManpowerModalLayout);
