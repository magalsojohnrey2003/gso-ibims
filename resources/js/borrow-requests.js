const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const LIST_ROUTE = window.LIST_ROUTE || '/admin/borrow-requests/list';
const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];

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
                <button type="button" class="text-sm text-red-500 hover:text-red-600" data-action="remove" data-reason-id="${escapeHtml(String(reason.id))}">?</button>
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

async function submitRejectionDecision({ requestId, reasonId, subject, detail, button }) {
    if (!requestId) {
        showError('No borrow request selected.');
        return;
    }

    try {
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

        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonCustomModal' }));
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectReasonSelectModal' }));
        resetRejectionFlow();
    } catch (error) {
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

function createButtonFromTemplate(templateId, id) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    const btn = frag.querySelector('button,[data-action]');
    if (!btn) return frag;

    const action = (btn.getAttribute('data-action') || '').toLowerCase().trim();

    if (action === 'view') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); viewRequest(id); });
    } else if (action === 'validate') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openAssignManpowerModal(id); });
    } else if (action === 'approve') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); approveRequest(id); });
    } else if (action === 'reject') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openConfirmModal(id, 'rejected'); });
    } else if (['dispatch', 'deliver', 'deliver_items'].includes(action)) {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openDeliverItemsModal(id); });
    } else if (action === 'mark-delivered' || action === 'mark_delivered') {
        btn.addEventListener('click', async (ev) => { ev.stopPropagation(); await markAsDelivered(id); });
    } else if (action === 'cancel-dispatch' || action === 'cancel_dispatch') {
        btn.addEventListener('click', async (ev) => { ev.stopPropagation(); await cancelDispatch(id); });
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
            classes: 'bg-emerald-100 text-emerald-700',
            icon: getIcon('fa-check-circle')
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
        const matchesStatus = !normalizedStatus || statusText.includes(normalizedStatus) || (normalizedStatus === 'approved' && statusText === 'qr_verified');
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
        const tdReturnDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.return_date))}</td>`;

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
            wrapper.appendChild(createButtonFromTemplate('btn-deliver-template', req.id));
        } else if (deliveryKey === 'dispatched') {
            wrapper.appendChild(createButtonFromTemplate('btn-mark-delivered-template', req.id));
            wrapper.appendChild(createButtonFromTemplate('btn-cancel-dispatch-template', req.id));
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
    const container = document.getElementById('assignManpowerItemsContainer');
    if (!container) return [];
    const rows = [];

    container.querySelectorAll('[data-borrow-request-item-id]').forEach((row) => {
        const itemId = row.dataset.borrowRequestItemId;
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

        rows.push({
            borrow_request_item_id: itemId,
            quantity,
            quantity_reason: quantityReason,
            original_quantity: originalQty,
        });
    });

    return rows;
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
}

function handleManpowerInputChange(event) {
    const input = event?.target || document.getElementById('assignManpowerInput');
    if (!input) return;
    const original = parseInt(input.dataset.original || input.dataset.requested || '0', 10);
    let value = parseInt(input.value || '0', 10);
    if (!Number.isFinite(value) || value < 0) value = 0;
    if (Number.isFinite(original) && original >= 0 && value > original) {
        value = original;
    }
    input.value = String(value);

    const wrapper = document.getElementById('assignManpowerReasonWrapper');
    const select = document.getElementById('assignManpowerReason');
    if (!wrapper || !select) return;

    if (Number.isFinite(original) && value < original) {
        wrapper.classList.remove('hidden');
    } else {
        wrapper.classList.add('hidden');
        select.value = '';
    }
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

    // Populate items list
    const itemsListEl = document.getElementById('assignItemsList');
    if (itemsListEl) {
        const ul = itemsListEl.querySelector('ul');
        if (ul) {
            ul.innerHTML = '';
            if (req.items && Array.isArray(req.items) && req.items.length > 0) {
                req.items.forEach(item => {
                    const li = document.createElement('li');
                    const itemName = item.item?.name || 'Unknown Item';
                    const quantity = item.quantity || 0;
                    li.textContent = `${itemName}(x${quantity})`;
                    ul.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = 'No items';
                li.className = 'list-none text-gray-500';
                ul.appendChild(li);
            }
        }
    }

    // Handle letter URL - prioritize letter_url from backend, fallback to letter_path
    let letterUrl = req.letter_url || '';
    
    // If we have a letter_url, convert it to a relative URL to avoid localhost/port issues
    if (letterUrl) {
        // Extract just the path part if it's a full URL
        if (letterUrl.startsWith('http')) {
            try {
                const urlObj = new URL(letterUrl);
                letterUrl = urlObj.pathname; // Get just the path (e.g., /storage/borrow-letters/file.jpg)
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
        el.textContent = value ?? '�';
    };

    const fullName = [req.user?.first_name, req.user?.last_name].filter(Boolean).join(' ').trim() || 'Unknown';
    setText('requestTitle', 'Borrow Request Details');
    setText('requestShortStatus', `Borrow Request ${formatBorrowRequestCode(req) || `#${req.id}`}`);
    setText('borrowerName', fullName);
    setText('manpowerCount', req.manpower_count ?? '�');
    setText('requestLocation', req.location ?? '�');
    setText('borrowDate', formatDate(req.borrow_date));
    setText('returnDate', formatDate(req.return_date));

    const statusBadge = document.getElementById('statusBadge');
    if (statusBadge) {
        const { label, classes, icon } = buildStatusBadge(req.status, req.delivery_status);
        statusBadge.className = `inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full ${classes}`;
        statusBadge.innerHTML = `${icon || ''}<span>${escapeHtml(label)}</span>`;
    }

    const itemsEl = document.getElementById('itemsList');
    if (itemsEl) {
        const itemsHtml = (req.items || []).map((item) => {
            const name = escapeHtml(item.item?.name ?? 'Unknown');
            const qty = escapeHtml(String(item.quantity ?? 0));
            const condition = item.quantity_reason ? ` - Reason: ${escapeHtml(item.quantity_reason)}` : '';
            return `<li>${name} (x${qty})${condition}</li>`;
        }).join('');
        itemsEl.innerHTML = itemsHtml ? `<ul class="list-disc list-inside text-gray-600">${itemsHtml}</ul>` : '<div class="text-gray-500">No items recorded.</div>';
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
    } else if (action === 'delivered') {
        title = 'Mark as Delivered';
        message = 'Confirm that the items have been handed over to the borrower.';
        iconClass = 'fas fa-check-circle text-emerald-600';
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
            submitBtn.disabled = true;
            try {
                const requestId = document.getElementById('assignManpowerRequestId')?.value;
                if (!requestId) {
                    showError('Request identifier is missing. Please refresh the page.');
                    return;
                }

                // Simply validate the request without any item assignments
                await updateRequest(Number(requestId), 'validated', {
                    button: submitBtn,
                });

                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'assignManpowerModal' }));
            } catch (error) {
                console.error(error);
                if (error?.message) showError(error.message);
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
            const status = String(confirmBtn.dataset.status || '').toLowerCase();
                if (!id || !status) {
                showError('Invalid request or action. Please try again.');
                return;
            }

            confirmBtn.disabled = true;
            try {
                // Handle 'delivered' status differently - call dispatch endpoint without reason
                if (status === 'dispatch' || status === 'dispatched') {
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
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmActionModal' }));
                } else if (status === 'delivered') {
                    const res = await fetch(`/admin/borrow-requests/${encodeURIComponent(id)}/deliver`, {
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
                        throw new Error(payload?.message || `Failed to mark delivered (status ${res.status})`);
                    }
                    showSuccess(payload?.message || 'Items marked as delivered successfully.');
                    await loadBorrowRequests();
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmActionModal' }));
                } else {
                    await updateRequest(Number(id), status, { button: confirmBtn });
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmActionModal' }));
                }
            } catch (error) {
                console.error('Confirm action failed', error);
                showError(error?.message || 'Failed to perform action. Please try again.');
            } finally {
                confirmBtn.disabled = false;
            }
        });
    });
})();

async function markAsDelivered(id) {
    if (!id) return;
    try {
        const res = await fetch(`/admin/borrow-requests/${encodeURIComponent(id)}/deliver`, {
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
            throw new Error(payload?.message || `Failed to mark delivered (status ${res.status})`);
        }
        showSuccess(payload?.message || 'Items marked as delivered successfully.');
        await loadBorrowRequests();
    } catch (error) {
        console.error('Mark as delivered failed', error);
        showError(error?.message || 'Failed to mark as delivered.');
    }
}

async function cancelDispatch(id) {
    if (!id) return;
    try {
        const res = await fetch(`/admin/borrow-requests/${encodeURIComponent(id)}/cancel-dispatch`, {
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
            throw new Error(payload?.message || `Failed to cancel dispatch (status ${res.status})`);
        }
        showSuccess(payload?.message || 'Dispatch canceled.');
        await loadBorrowRequests();
    } catch (error) {
        console.error('Cancel dispatch failed', error);
        showError(error?.message || 'Failed to cancel dispatch.');
    }
}

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
