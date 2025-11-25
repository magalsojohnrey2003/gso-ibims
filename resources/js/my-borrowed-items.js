// resources/js/my-borrowed-items.js
const LIST_ROUTE = window.LIST_ROUTE || '/user/my-borrowed-items/list';
const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let ITEMS_CACHE = [];

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
    return `BR-${String(rawId).padStart(4, '0')}`;
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
        'return_pending': 'fa-clock',
        'returned': 'fa-arrow-left',
        'rejected': 'fa-times-circle',
        'qr_verified': 'fa-check-circle',
        'validated': 'fa-check-circle',
        'dispatched': 'fa-truck',
        'delivered': 'fa-truck',
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
    };
    const icon = statusIcons[st] || 'fa-question-circle';
    const color = statusColors[st] || 'bg-gray-100 text-gray-700';
    const label = st ? (st.charAt(0).toUpperCase() + st.slice(1).replace(/_/g, ' ')) : '—';
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
function createButtonFromTemplate(templateId, id) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    const btn = frag.querySelector('[data-action]');
    if (!btn) return frag;
    const action = btn.getAttribute('data-action');

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
    }
    return frag;
}

// ---------- render table ----------
function renderItems() {
    const tbody = document.getElementById("myBorrowedItemsTableBody");
    if (!tbody) return;
    tbody.innerHTML = "";

    if (!ITEMS_CACHE.length) {
        appendEmptyState(tbody, 'my-borrowed-items-empty-state-template', 5, 'No borrowed items');
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
        appendEmptyState(tbody, 'my-borrowed-items-empty-state-template', 5, 'No items match your search');
        return;
    }

    list.forEach(req => {
        const tr = document.createElement("tr");
        tr.className = "transition hover:bg-purple-50 hover:shadow-md";
        tr.dataset.requestId = String(req.id);

        const requestCode = formatBorrowRequestCodeLocal(req) || `#${req.id ?? ''}`;
        const tdId = `<td class="px-4 py-3">${escapeHtml(requestCode)}</td>`;
        const tdBorrowDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.borrow_date))}</td>`;
        const tdReturnDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.return_date))}</td>`;

        // Display delivery progression overrides base status when in dispatched/delivered
        const effectiveStatus = (req.delivery_status && ['dispatched','delivered'].includes(req.delivery_status.toLowerCase()))
            ? req.delivery_status.toLowerCase()
            : (req.status || '');
        const badgeHtml = getBadgeHtml(effectiveStatus);
        const tdStatus = `<td class="px-4 py-3">${badgeHtml}</td>`;

        const tdActions = document.createElement("td");
        tdActions.className = "px-4 py-3";
        const wrapper = document.createElement("div");
        wrapper.className = "flex justify-center gap-2";

        wrapper.appendChild(createButtonFromTemplate("btn-view-template", req.id));

        if (req.status === "validated" || req.status === "approved") {
            wrapper.appendChild(createButtonFromTemplate("btn-print-template", req.id));
        }

        const deliveryStatus = (req.delivery_status || '').toLowerCase();
        tdActions.appendChild(wrapper);

        tr.innerHTML = tdId + tdBorrowDate + tdReturnDate + tdStatus;
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });
}

// ---------- modal population ----------
export function fillBorrowModal(data) {
    if (!data) return;
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
    setText('mbi-short-status', `Borrow Request ${requestCode}`);
    setText('mbi-summary-id', requestCode);

    const itemsArray = Array.isArray(data.items) ? data.items : [];
    const itemsLabel = itemsArray.length === 1 ? '1 item' : `${itemsArray.length} items`;
    setText('mbi-summary-items', itemsLabel);
    const itemsSummary = document.getElementById('mbi-items-summary');
    if (itemsSummary) itemsSummary.textContent = itemsLabel;

    const purposeValue = pickText(data.purpose, data.purpose_description, data.purpose_text, data.purpose_detail);
    setText('mbi-summary-purpose', purposeValue || '—');

    const delivery = (data.delivery_status || '').toLowerCase();
    const statusKey = ['dispatched', 'delivered'].includes(delivery) ? delivery : (data.status || '').toLowerCase();
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

    const itemsList = document.getElementById('mbi-items');
    if (itemsList) {
        const itemsHtml = itemsArray.map((i) => {
            const name = escapeHtml(i.item?.name ?? 'Unknown');
            const qty = escapeHtml(String(i.quantity ?? 0));
            return `<li>${name} (x${qty})</li>`;
        }).join('');
        itemsList.innerHTML = itemsHtml || '<li class="list-none text-gray-500">No items recorded.</li>';
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

    // Delivery buttons logic (confirm / report)
    const confirmBtn = document.getElementById('mbi-confirm-received-btn');
    const reportBtn = document.getElementById('mbi-report-not-received-btn');

    // hide by default
    if (confirmBtn) confirmBtn.classList.add('hidden');
    if (reportBtn) reportBtn.classList.add('hidden');

    // Show confirm/report only when delivery_status is dispatched and not yet delivered
    const deliveryStatus = (data.delivery_status || '').toLowerCase();
    const deliveredAt = data.delivered_at || null;

    if (deliveryStatus === 'dispatched' && !deliveredAt) {
        if (confirmBtn) {
            confirmBtn.dataset.requestId = data.id;
            confirmBtn.classList.remove('hidden');
        }
        if (reportBtn) {
            reportBtn.dataset.requestId = data.id;
            reportBtn.classList.remove('hidden');
        }
    } else {
        if (confirmBtn) confirmBtn.dataset.requestId = '';
        if (reportBtn) reportBtn.dataset.requestId = '';
    }
}

// ---------- view (fetch details & open modal) ----------
async function viewBorrowDetails(id) {
    try {
        const res = await fetch(`/user/my-borrowed-items/${id}`, { headers: { 'Accept': 'application/json' } });
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

async function confirmDelivery(id) {
    try {
        await postJson(`/user/my-borrowed-items/${encodeURIComponent(id)}/confirm-delivery`);
        showSuccess('Thank you — receipt confirmed.');
        window.dispatchEvent(new Event('close-modal'));
        // refresh
        await loadMyBorrowedItems();
    } catch (e) {
        console.error('confirmDelivery failed', e);
        showError('Failed to confirm receipt.');
    }
}

async function reportNotReceived(id) {
    try {
        const reason = window.prompt('Please provide a short reason (optional):', '');
        const body = { reason: reason || null };
        await postJson(`/user/my-borrowed-items/${encodeURIComponent(id)}/report-not-received`, body);
        showSuccess('Report submitted — admin will be notified.');
        window.dispatchEvent(new Event('close-modal'));
        await loadMyBorrowedItems();
    } catch (e) {
        console.error('reportNotReceived failed', e);
        showError('Failed to submit report.');
    }
}

// wire confirm/report buttons
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('mbi-confirm-received-btn');
    const reportBtn = document.getElementById('mbi-report-not-received-btn');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            const id = confirmBtn.dataset.requestId;
            if (!id) return;
            confirmBtn.disabled = true;
            try {
                await confirmDelivery(id);
            } finally {
                confirmBtn.disabled = false;
            }
        });
    }

    if (reportBtn) {
        reportBtn.addEventListener('click', async () => {
            const id = reportBtn.dataset.requestId;
            if (!id) return;
            reportBtn.disabled = true;
            try {
                await reportNotReceived(id);
            } finally {
                reportBtn.disabled = false;
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
