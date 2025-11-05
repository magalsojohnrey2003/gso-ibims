const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const LIST_ROUTE = window.LIST_ROUTE || '/admin/borrow-requests/list';

let BORROW_CACHE = [];
let currentPage = 1;
const PER_PAGE = 5;

const REJECTION_STATE = {
    requestId: null,
    category: null,
};

function formatDate(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function humanizeStatus(status) {
    if (!status) return 'Pending';
    return String(status)
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
        .join(' ');
}

function showAlert(type, message) {
    const tpl = document.getElementById(`alert-${type}-template`);
    const container = document.getElementById('adminAlertContainer');
    if (!tpl || !container) return;
    const frag = tpl.content.cloneNode(true);
    const span = frag.querySelector('[data-alert-message]');
    if (span) span.textContent = message;
    const node = container.appendChild(frag);
    setTimeout(() => {
        if (container.contains(node)) node.remove();
    }, 5000);
}

function showError(message) {
    showAlert('error', message);
}

function showSuccess(message) {
    showAlert('success', message);
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

function paginate(data) {
    const start = (currentPage - 1) * PER_PAGE;
    return data.slice(start, start + PER_PAGE);
}

function renderPagination(totalItems) {
    const nav = document.getElementById('paginationNav');
    if (!nav) return;
    nav.innerHTML = '';
    const totalPages = Math.ceil(totalItems / PER_PAGE);
    if (totalPages <= 1) return;

    const createBtn = (label, page, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.className = [
            'px-3 py-1 rounded-md text-sm transition',
            active ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-purple-100',
            disabled ? 'opacity-50 cursor-not-allowed' : '',
        ].join(' ');
        if (!disabled) {
            btn.addEventListener('click', () => {
                currentPage = page;
                renderBorrowRequests();
                window.scrollTo(0, 0);
            });
        }
        return btn;
    };

    nav.appendChild(createBtn('Prev', Math.max(1, currentPage - 1), currentPage === 1));
    for (let page = 1; page <= totalPages; page += 1) {
        nav.appendChild(createBtn(page, page, false, page === currentPage));
    }
    nav.appendChild(createBtn('Next', Math.min(totalPages, currentPage + 1), currentPage === totalPages));
}

function createButtonFromTemplate(templateId, id) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    const btn = frag.querySelector('button,[data-action]');
    if (!btn) return frag;

    const action = (btn.getAttribute('data-action') || '').toLowerCase().trim();

    if (action === 'view') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); viewRequest(id); });
    } else if (action === 'accept' || action === 'validate') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openAssignManpowerModal(id); });
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

function buildStatusBadge(status, deliveryStatus) {
    const statusKey = String(status || '').toLowerCase();
    const deliveryKey = String(deliveryStatus || '').toLowerCase();

    // Helper to get icon HTML
    const getIcon = (iconClass) => `<i class="fas ${iconClass} text-xs"></i>`;

    if (deliveryKey === 'dispatched') {
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

function renderBorrowRequests() {
    const tbody = document.getElementById('borrowRequestsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const pageData = paginate(BORROW_CACHE);
    if (!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-gray-500">No requests found.</td></tr>';
        renderPagination(0);
        return;
    }

    pageData.forEach((req) => {
        const tr = document.createElement('tr');
        tr.className = 'transition hover:bg-purple-50 hover:shadow-md';

        const borrowerName = [req.user?.first_name, req.user?.last_name].filter(Boolean).join(' ').trim() || 'Unknown';

        const tdBorrower = `<td class="px-4 py-3">${escapeHtml(borrowerName)}</td>`;
        const tdId = `<td class="px-4 py-3">${escapeHtml(String(req.id ?? ''))}</td>`;
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
        } else if (['validated', 'approved'].includes(statusKey)) {
            wrapper.appendChild(createButtonFromTemplate('btn-deliver-template', req.id));
        } else if (deliveryKey === 'dispatched' || ['returned', 'rejected'].includes(statusKey)) {
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
        } else {
            wrapper.appendChild(createButtonFromTemplate('btn-view-template', req.id));
        }

        tdActions.appendChild(wrapper);

        tr.innerHTML = tdBorrower + tdId + tdBorrowDate + tdReturnDate + tdStatus;
        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });

    renderPagination(BORROW_CACHE.length);
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

    const container = document.getElementById('assignManpowerItemsContainer');
    const requestIdInput = document.getElementById('assignManpowerRequestId');
    const requestedTotalEl = document.getElementById('assignRequestedTotal');
    const manpowerInput = document.getElementById('assignManpowerInput');
    const manpowerReasonWrapper = document.getElementById('assignManpowerReasonWrapper');
    const manpowerReasonSelect = document.getElementById('assignManpowerReason');
    const locationEl = document.getElementById('assignManpowerLocation');
    const letterPreview = document.getElementById('assignLetterPreview');
    const letterFallback = document.getElementById('assignLetterFallback');
    const letterHint = document.getElementById('assignLetterHint');
    const letterWrapper = document.getElementById('assignLetterPreviewWrapper');

    if (!container || !requestIdInput || !requestedTotalEl || !manpowerInput) return;

    container.innerHTML = '';
    requestIdInput.value = id;

    const requestedTotal = Number(req.manpower_count ?? 0);
    requestedTotalEl.textContent = Number.isFinite(requestedTotal) && requestedTotal > 0 ? String(requestedTotal) : '--';
    requestedTotalEl.dataset.original = String(requestedTotal);

    manpowerInput.value = Number.isFinite(requestedTotal) ? String(requestedTotal) : '0';
    manpowerInput.dataset.original = String(requestedTotal);
    manpowerInput.removeEventListener('input', handleManpowerInputChange);
    manpowerInput.addEventListener('input', handleManpowerInputChange);
    handleManpowerInputChange({ target: manpowerInput });

    if (manpowerReasonSelect) {
        manpowerReasonSelect.innerHTML = buildReasonOptions(MANPOWER_REASONS);
        manpowerReasonSelect.value = '';
    }
    if (manpowerReasonWrapper) {
        manpowerReasonWrapper.classList.add('hidden');
    }

    if (locationEl) {
        locationEl.textContent = req.location && req.location.trim() !== '' ? req.location : '--';
    }

    // Handle letter URL - check if it's a full URL or needs to be converted from path
    let letterUrl = req.letter_url || '';
    if (!letterUrl && req.letter_path) {
        // If we only have a path, construct the storage URL
        if (req.letter_path.startsWith('http')) {
            letterUrl = req.letter_path;
        } else {
            letterUrl = '/storage/' + req.letter_path.replace(/^storage\//, '');
        }
    }
    
    const letterName = (req.letter_path || '').split('/').pop() || 'Uploaded letter';
    const letterType = letterUrl ? detectLetterType(letterUrl) : 'other';
    if (letterWrapper) {
        if (letterUrl) {
            letterWrapper.dataset.letterUrl = letterUrl;
            letterWrapper.dataset.letterName = letterName;
            letterWrapper.dataset.letterType = letterType;
            letterWrapper.setAttribute('aria-disabled', 'false');
            letterWrapper.setAttribute('tabindex', '0');
            letterWrapper.classList.remove('cursor-not-allowed');
            letterWrapper.classList.add('cursor-pointer');
            if (letterHint) letterHint.classList.remove('hidden');
        } else {
            letterWrapper.dataset.letterUrl = '';
            letterWrapper.dataset.letterName = '';
            letterWrapper.dataset.letterType = '';
            letterWrapper.setAttribute('aria-disabled', 'true');
            letterWrapper.setAttribute('tabindex', '-1');
            letterWrapper.classList.remove('cursor-pointer');
            letterWrapper.classList.add('cursor-not-allowed');
            if (letterHint) letterHint.classList.add('hidden');
        }
    }

    if (letterPreview) {
        letterPreview.classList.add('hidden');
        letterPreview.removeAttribute('src');
    }
    if (letterFallback) {
        letterFallback.textContent = letterUrl ? 'Letter uploaded' : 'No letter uploaded';
        if (letterUrl && letterType === 'image') {
            letterFallback.classList.add('hidden');
        } else {
            letterFallback.classList.remove('hidden');
        }
    }

    if (letterUrl && letterPreview) {
        if (letterType === 'image') {
            letterPreview.src = letterUrl;
            letterPreview.onload = () => {
                letterPreview.classList.remove('hidden');
                if (letterFallback) letterFallback.classList.add('hidden');
            };
            letterPreview.onerror = () => {
                letterPreview.classList.add('hidden');
                if (letterFallback) {
                    letterFallback.textContent = 'Letter uploaded (preview unavailable)';
                    letterFallback.classList.remove('hidden');
                }
            };
        } else {
            if (letterFallback) {
                letterFallback.textContent = letterType === 'pdf'
                    ? 'PDF uploaded — click to preview'
                    : 'Letter uploaded (preview unavailable)';
                letterFallback.classList.remove('hidden');
            }
        }
    }

    (req.items || []).forEach((item) => {
        const originalQty = Number(item.quantity ?? 0);
        const row = document.createElement('div');
        row.className = 'space-y-2 border border-gray-200 rounded-lg p-3 bg-white';
        row.dataset.borrowRequestItemId = item.id ?? item.borrow_request_item_id ?? '';
        row.dataset.originalQty = String(originalQty);

        const reasonOptions = buildReasonOptions(QUANTITY_REASONS);

        row.innerHTML = `
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-medium text-gray-900">${escapeHtml(item.item?.name ?? 'Unknown')}</div>
                    <div class="text-xs text-gray-500">Requested: ${escapeHtml(String(originalQty))}</div>
                </div>
                <div class="w-28">
                    <label class="text-xs text-gray-600">Quantity</label>
                    <input type="number" min="0" max="${escapeHtml(String(originalQty))}" value="${escapeHtml(String(originalQty))}" class="assign-qty-input w-full rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-purple-500 focus:ring-purple-200" />
                </div>
            </div>
            <div class="assign-qty-reason-wrapper hidden">
                <label class="text-xs font-medium text-gray-600">Reason for reduction</label>
                <select class="assign-qty-reason mt-1 w-full rounded-md border border-gray-300 px-2 py-1 text-xs focus:border-purple-500 focus:ring-purple-200">${reasonOptions}</select>
            </div>
        `;

        container.appendChild(row);
    });

    container.querySelectorAll('.assign-qty-input').forEach((input) => {
        input.removeEventListener('input', handleQtyInputChange);
        input.addEventListener('input', handleQtyInputChange);
        handleQtyInputChange({ target: input });
    });

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'assignManpowerModal' }));
}

function fillRequestModal(req) {
    if (!req) return;
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = value ?? '—';
    };

    const fullName = [req.user?.first_name, req.user?.last_name].filter(Boolean).join(' ').trim() || 'Unknown';
    setText('requestTitle', 'Borrow Request Details');
    setText('requestShortStatus', `Borrow Request #${req.id}`);
    setText('borrowerName', fullName);
    setText('manpowerCount', req.manpower_count ?? '—');
    setText('requestLocation', req.location ?? '—');
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
            const condition = item.quantity_reason ? ` — Reason: ${escapeHtml(item.quantity_reason)}` : '';
            return `<li>${name} (x${qty})${condition}</li>`;
        }).join('');
        itemsEl.innerHTML = itemsHtml ? `<ul class="list-disc list-inside text-gray-600">${itemsHtml}</ul>` : '<div class="text-gray-500">No items recorded.</div>';
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

function resetRejectionState() {
    REJECTION_STATE.requestId = null;
    REJECTION_STATE.category = null;
    document.querySelectorAll('input[name="rejectionReason"]').forEach((radio) => {
        radio.checked = false;
    });
    const subjectInput = document.getElementById('customRejectionSubject');
    const detailsInput = document.getElementById('customRejectionDetails');
    if (subjectInput) subjectInput.value = '';
    if (detailsInput) detailsInput.value = '';
}

function openRejectionReasonModal(id, preserveSelection = false) {
    REJECTION_STATE.requestId = id;
    if (!preserveSelection) {
        REJECTION_STATE.category = null;
        document.querySelectorAll('input[name="rejectionReason"]').forEach((radio) => {
            radio.checked = false;
        });
    }
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rejectionReasonModal' }));
}

function openCustomRejectionModal() {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectionReasonModal' }));
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'customRejectionModal' }));
    const subjectInput = document.getElementById('customRejectionSubject');
    if (subjectInput) {
        subjectInput.focus();
        subjectInput.select();
    }
}

function returnToRejectionReasonModal() {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'customRejectionModal' }));
    openRejectionReasonModal(REJECTION_STATE.requestId ?? null, true);
    document.querySelectorAll('input[name="rejectionReason"]').forEach((radio) => {
        if ((radio.value || '').toLowerCase() === 'other') {
            radio.checked = true;
        }
    });
}

async function processRejection(category, reason, button) {
    const requestId = REJECTION_STATE.requestId;
    if (!requestId) {
        showError('Unable to determine which request to reject. Please refresh and try again.');
        return;
    }

    const trimmedCategory = (category || '').trim();
    const trimmedReason = (reason || '').trim();
    if (!trimmedCategory || !trimmedReason) {
        showError('Please provide a rejection reason before continuing.');
        return;
    }

    try {
        await updateRequest(Number(requestId), 'rejected', {
            rejectionCategory: trimmedCategory,
            rejectionReason: trimmedReason,
            button: button || null,
        });
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectionReasonModal' }));
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'customRejectionModal' }));
        resetRejectionState();
    } catch (error) {
        console.error('Reject request failed', error);
        throw error;
    }
}

function detectLetterType(url, providedType = '') {
    if (providedType && typeof providedType === 'string') {
        const normalized = providedType.toLowerCase();
        if (['image', 'pdf'].includes(normalized)) return normalized;
    }
    if (!url) return 'other';
    const cleanUrl = url.split('?')[0].toLowerCase();
    if (cleanUrl.match(/\.(png|jpe?g|gif|webp)$/)) return 'image';
    if (cleanUrl.endsWith('.pdf')) return 'pdf';
    return 'other';
}

function openLetterPreviewModal({ url, name, type } = {}) {
    if (!url) {
        showError('No uploaded letter available to preview.');
        return;
    }

    const letterType = detectLetterType(url, type);
    const filename = (name || url.split('/').pop() || 'Uploaded letter').split('?')[0];

    const imageEl = document.getElementById('letterPreviewImage');
    const frameEl = document.getElementById('letterPreviewFrame');
    const placeholderEl = document.getElementById('letterPreviewPlaceholder');
    const primaryLink = document.getElementById('letterPreviewDownloadPrimary');
    const footerLink = document.getElementById('letterPreviewDownloadFooter');
    const filenameEl = document.getElementById('letterPreviewFilename');

    if (imageEl) {
        imageEl.classList.add('hidden');
        imageEl.removeAttribute('src');
    }
    if (frameEl) {
        frameEl.classList.add('hidden');
        if (frameEl.contentWindow) {
            frameEl.src = 'about:blank';
        } else {
            frameEl.removeAttribute('src');
        }
    }
    if (placeholderEl) {
        placeholderEl.classList.add('hidden');
    }
    if (primaryLink) {
        primaryLink.classList.add('hidden');
        primaryLink.href = '#';
    }
    if (footerLink) {
        footerLink.classList.add('hidden');
        footerLink.href = '#';
    }
    if (filenameEl) {
        filenameEl.textContent = filename;
    }

    if (letterType === 'image' && imageEl) {
        imageEl.onload = () => {
            imageEl.classList.remove('hidden');
            if (placeholderEl) placeholderEl.classList.add('hidden');
        };
        imageEl.onerror = () => {
            imageEl.classList.add('hidden');
            if (placeholderEl) placeholderEl.classList.remove('hidden');
            if (primaryLink) {
                primaryLink.href = url;
                primaryLink.classList.remove('hidden');
            }
            if (footerLink) {
                footerLink.href = url;
                footerLink.classList.remove('hidden');
            }
        };
        imageEl.src = url;
        if (footerLink) {
            footerLink.href = url;
            footerLink.classList.remove('hidden');
        }
    } else if (letterType === 'pdf' && frameEl) {
        frameEl.src = url;
        frameEl.classList.remove('hidden');
        if (primaryLink) {
            primaryLink.href = url;
            primaryLink.classList.remove('hidden');
        }
        if (footerLink) {
            footerLink.href = url;
            footerLink.classList.remove('hidden');
        }
    } else {
        if (placeholderEl) {
            placeholderEl.classList.remove('hidden');
        }
        if (primaryLink) {
            primaryLink.href = url;
            primaryLink.classList.remove('hidden');
        }
        if (footerLink) {
            footerLink.href = url;
            footerLink.classList.remove('hidden');
        }
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'letterPreviewModal' }));
}

function openConfirmModal(id, status) {
    const confirmBtn = document.getElementById('confirmActionConfirmBtn');
    const iconEl = document.getElementById('confirmActionIcon');
    const titleEl = document.getElementById('confirmActionTitle');
    const messageEl = document.getElementById('confirmActionMessage');

    const action = String(status || '').toLowerCase();
    if (action === 'rejected') {
        openRejectionReasonModal(id);
        return;
    }
    let title = 'Confirm Action';
    let message = 'Are you sure you want to continue?';
    let iconClass = 'fas fa-exclamation-circle text-yellow-500';

    if (action === 'delivered') {
        title = 'Deliver Items';
        message = 'Confirm that the items have been delivered to the borrower.';
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

    // Validate that available quantity is at least 98% of total quantity for all items
    let needsReasonModal = false;
    if (req.items && req.items.length > 0) {
        for (const item of req.items) {
            const itemData = item.item;
            if (!itemData) continue;

            const totalQty = Number(itemData.total_qty ?? 0);
            const availableQty = Number(itemData.available_qty ?? 0);

            // If total quantity is 0 or unavailable, skip check for this item
            if (totalQty === 0) continue;

            // Check if available quantity is below 98% threshold
            const percentage = (availableQty / totalQty) * 100;
            if (percentage < 98 || availableQty === 0) {
                showError('Failed to dispatch.');
                return;
            }

            // If available quantity is less than total, some items are missing/damaged - need reason modal
            if (availableQty < totalQty) {
                needsReasonModal = true;
            }
        }
    } else {
        showError('Failed to dispatch.');
        return;
    }

    // If all items are in good condition (available == total, 100%), show simple confirmation modal
    if (!needsReasonModal) {
        openConfirmModal(id, 'delivered');
        return;
    }

    const infoContainer = document.getElementById('deliverItemsInfo');
    const confirmBtn = document.getElementById('deliverItemsConfirmBtn');
    const othersFields = document.getElementById('deliverItemsOthersFields');
    const subjectInput = document.getElementById('deliveryReasonSubject');
    const explanationInput = document.getElementById('deliveryReasonExplanation');

    if (!infoContainer || !confirmBtn) return;

    // Populate item information
    infoContainer.innerHTML = '';
    if (req.items && req.items.length > 0) {
        req.items.forEach((item) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
            
            // Get available quantity from item data
            const availableQty = item.item?.available_qty ?? '—';
            const totalQty = item.item?.total_qty ?? '—';

            itemDiv.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-gray-900">${escapeHtml(item.item?.name || 'Unknown Item')}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Available Quantity: <span class="font-semibold">${escapeHtml(String(availableQty))}</span>
                            ${totalQty !== '—' ? ` / ${escapeHtml(String(totalQty))} total` : ''}
                        </div>
                    </div>
                </div>
            `;
            infoContainer.appendChild(itemDiv);
        });
    } else {
        infoContainer.innerHTML = '<div class="text-gray-500 text-sm">No items found.</div>';
    }

    // Reset form
    const reasonRadios = document.querySelectorAll('input[name="deliveryReason"]');
    reasonRadios.forEach(radio => {
        radio.checked = false;
    });
    if (subjectInput) subjectInput.value = '';
    if (explanationInput) explanationInput.value = '';
    if (othersFields) othersFields.classList.add('hidden');

    // Store request ID for confirmation
    confirmBtn.dataset.requestId = id;

    // Show modal
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'deliverItemsModal' }));
}

// Handle reason radio button changes
(function bindDeliverItemsReasonChange() {
    document.addEventListener('DOMContentLoaded', () => {
        const reasonRadios = document.querySelectorAll('input[name="deliveryReason"]');
        const othersFields = document.getElementById('deliverItemsOthersFields');
        
        reasonRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (othersFields) {
                    if (e.target.value === 'others') {
                        othersFields.classList.remove('hidden');
                    } else {
                        othersFields.classList.add('hidden');
                        const subjectInput = document.getElementById('deliveryReasonSubject');
                        const explanationInput = document.getElementById('deliveryReasonExplanation');
                        if (subjectInput) subjectInput.value = '';
                        if (explanationInput) explanationInput.value = '';
                    }
                }
            });
        });
    });
})();

async function updateRequest(id, status, options = {}) {
    const {
        assignments = null,
        manpowerTotal = null,
        manpowerReason = null,
        button = null,
        silent = false,
        rejectionCategory = undefined,
        rejectionReason = undefined,
    } = options || {};

    const manpowerReasonProvided = Object.prototype.hasOwnProperty.call(options || {}, 'manpowerReason');

    if (button) button.disabled = true;
    try {
        const body = { status };
        if (assignments) body.manpower_assignments = assignments;
        if (Number.isFinite(manpowerTotal)) body.manpower_total = manpowerTotal;
        if (manpowerReasonProvided) body.manpower_reason = manpowerReason;
        if (typeof rejectionCategory === 'string' && rejectionCategory.trim() !== '') {
            body.rejection_category = rejectionCategory.trim();
        }
        if (typeof rejectionReason === 'string' && rejectionReason.trim() !== '') {
            body.rejection_reason = rejectionReason.trim();
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

                const manpowerInput = document.getElementById('assignManpowerInput');
                const manpowerReasonSelect = document.getElementById('assignManpowerReason');
                const requestedTotalEl = document.getElementById('assignRequestedTotal');
                const requestedTotal = parseInt(requestedTotalEl?.dataset.original || requestedTotalEl?.textContent || '0', 10) || 0;

                let manpowerValue = parseInt(manpowerInput?.value || '0', 10);
                if (!Number.isFinite(manpowerValue) || manpowerValue < 0) manpowerValue = 0;
                if (manpowerValue > requestedTotal) {
                    manpowerValue = requestedTotal;
                    if (manpowerInput) manpowerInput.value = String(manpowerValue);
                }

                const manpowerReduced = manpowerValue < requestedTotal;
                const manpowerReason = manpowerReduced ? (manpowerReasonSelect?.value?.trim() || '') : null;
                if (manpowerReduced && !manpowerReason) {
                    showError('Please select a reason for reducing manpower quantity.');
                    return;
                }

                const assignments = collectManpowerAssignments();
                for (const item of assignments) {
                    const requiresReason = Number.isFinite(item.original_quantity) && item.quantity < item.original_quantity;
                    if (requiresReason && !item.quantity_reason) {
                        showError('Please select a reason for each item with reduced quantity.');
                        return;
                    }
                }

                const payloadAssignments = assignments.map(({ borrow_request_item_id, quantity, quantity_reason }) => ({
                    borrow_request_item_id,
                    quantity,
                    quantity_reason,
                }));

                await updateRequest(Number(requestId), 'validated', {
                    assignments: payloadAssignments,
                    manpowerTotal: manpowerValue,
                    manpowerReason,
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

(function bindRejectionModals() {
    document.addEventListener('DOMContentLoaded', () => {
        const reasonConfirmBtn = document.getElementById('rejectionReasonConfirmBtn');
        const reasonCancelBtn = document.getElementById('rejectionReasonCancelBtn');
        const customConfirmBtn = document.getElementById('customRejectionConfirmBtn');
        const customBackBtn = document.getElementById('customRejectionBackBtn');

        if (reasonConfirmBtn) {
            reasonConfirmBtn.addEventListener('click', async () => {
                const radios = Array.from(document.querySelectorAll('input[name="rejectionReason"]'));
                const selected = radios.find((radio) => radio.checked);
                if (!selected) {
                    showError('Please select a rejection reason before confirming.');
                    return;
                }
                const value = (selected.value || '').trim();
                if (value.toLowerCase() === 'other') {
                    REJECTION_STATE.category = 'Other';
                    openCustomRejectionModal();
                    return;
                }
                REJECTION_STATE.category = value;
                try {
                    await processRejection(value, value, reasonConfirmBtn);
                } catch (error) {
                    // Errors surface via updateRequest/processRejection
                }
            });
        }

        if (reasonCancelBtn) {
            reasonCancelBtn.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'rejectionReasonModal' }));
                resetRejectionState();
            });
        }

        if (customConfirmBtn) {
            customConfirmBtn.addEventListener('click', async () => {
                const subjectInput = document.getElementById('customRejectionSubject');
                const detailsInput = document.getElementById('customRejectionDetails');
                const subject = subjectInput?.value?.trim() || '';
                const details = detailsInput?.value?.trim() || '';

                if (!subject) {
                    showError('Please provide a short subject for the rejection.');
                    if (subjectInput) subjectInput.focus();
                    return;
                }
                if (!details) {
                    showError('Please provide a detailed rejection reason.');
                    if (detailsInput) detailsInput.focus();
                    return;
                }

                const combined = `${subject}: ${details}`;
                try {
                    await processRejection('Other', combined, customConfirmBtn);
                } catch (error) {
                    // Errors surface via updateRequest/processRejection
                }
            });
        }

        if (customBackBtn) {
            customBackBtn.addEventListener('click', () => {
                returnToRejectionReasonModal();
            });
        }

        window.addEventListener('rejection-flow-reset', () => {
            resetRejectionState();
        });
    });
})();

(function bindLetterPreviewTrigger() {
    document.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.getElementById('assignLetterPreviewWrapper');
        if (!wrapper) return;

        const triggerPreview = () => {
            const url = wrapper.dataset.letterUrl || '';
            if (!url) return;
            const name = wrapper.dataset.letterName || '';
            const type = wrapper.dataset.letterType || '';
            openLetterPreviewModal({ url, name, type });
        };

        wrapper.addEventListener('click', () => {
            if (wrapper.getAttribute('aria-disabled') === 'true') return;
            triggerPreview();
        });

        wrapper.addEventListener('keydown', (event) => {
            if (wrapper.getAttribute('aria-disabled') === 'true') return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                triggerPreview();
            }
        });
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
                if (status === 'delivered') {
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
                    showSuccess(payload?.message || 'Items dispatched and marked as delivered successfully.');
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

(function bindDeliverItemsConfirm() {
    document.addEventListener('DOMContentLoaded', () => {
        const confirmBtn = document.getElementById('deliverItemsConfirmBtn');
        if (!confirmBtn) return;

        confirmBtn.addEventListener('click', async () => {
            const id = confirmBtn.dataset.requestId;
                if (!id) {
                showError('Invalid request ID. Please refresh the page.');
                return;
            }

            // Get selected reason
            const selectedReason = document.querySelector('input[name="deliveryReason"]:checked');
            const reasonType = selectedReason?.value || null;
            
                if (!reasonType) {
                showError('Please select a delivery reason before dispatching items.');
                return;
            }

            // Get subject and explanation if "others" is selected
            let subject = null;
            let explanation = null;
            if (reasonType === 'others') {
                subject = document.getElementById('deliveryReasonSubject')?.value?.trim() || '';
                explanation = document.getElementById('deliveryReasonExplanation')?.value?.trim() || '';
                
                if (!subject || !explanation) {
                    showError('Please provide both subject and explanation when selecting "Others" as the delivery reason.');
                    return;
                }
            }

            confirmBtn.disabled = true;
            try {
                const body = {
                    delivery_reason_type: reasonType,
                };
                
                if (reasonType === 'others') {
                    body.delivery_reason_subject = subject;
                    body.delivery_reason_explanation = explanation;
                }

                    const res = await fetch(`/admin/borrow-requests/${encodeURIComponent(id)}/dispatch`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                        },
                    body: JSON.stringify(body),
                    });
                    const payload = await res.json().catch(() => null);
                    if (!res.ok) {
                        throw new Error(payload?.message || `Failed to dispatch items (status ${res.status})`);
                    }
                    showSuccess(payload?.message || 'Items dispatched and marked as delivered successfully.');
                    await loadBorrowRequests();
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'deliverItemsModal' }));
            } catch (error) {
                console.error('Deliver items failed', error);
                showError(error?.message || 'Failed to dispatch items. Please try again.');
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
