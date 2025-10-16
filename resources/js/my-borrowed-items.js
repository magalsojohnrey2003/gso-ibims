// resources/js/my-borrowed-items.js
const LIST_ROUTE = window.LIST_ROUTE || '/user/my-borrowed-items/list';
const RETURN_PAGE_ROUTE = window.RETURN_PAGE_ROUTE || '/user/return-items';
const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let ITEMS_CACHE = [];
let currentPage = 1;
const perPage = 5;

// ---------- helpers ----------
export function formatDate(dateStr) {
    if (!dateStr) return "N/A";
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric"
    });
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
    const label = st ? (st.charAt(0).toUpperCase() + st.slice(1)) : '—';
    return `<span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">${escapeHtml(label)}</span>`;
}

function showAlert(type, msg) {
    const tpl = document.getElementById(`alert-${type}-template`);
    const container = document.getElementById('userAlertContainer');
    if (!tpl || !container) {
        console[type === 'error' ? 'error' : 'log'](msg);
        return;
    }
    const frag = tpl.content.cloneNode(true);
    const span = frag.querySelector('[data-alert-message]');
    if (span) span.textContent = msg;
    const appended = container.appendChild(frag);
    setTimeout(() => {
        if (container.contains(appended)) appended.remove();
    }, 5000);
}
function showError(m){ showAlert('error', m); }
function showSuccess(m){ showAlert('success', m); }

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
        currentPage = 1;
        renderItems();
    } catch (e) {
        console.error('loadMyBorrowedItems error', e);
        showError("Failed to load borrowed items.");
    }
}

// ---------- pagination ----------
function paginate(data){
    const start = (currentPage - 1) * perPage;
    return data.slice(start, start + perPage);
}

function renderPagination(total) {
    const nav = document.getElementById("paginationNav");
    if (!nav) return;
    nav.innerHTML = "";
    const totalPages = Math.ceil(total / perPage) || 0;
    if (totalPages <= 1) return;

    function createBtn(label, page, disabled = false, active = false) {
        const btn = document.createElement("button");
        btn.textContent = label;
        btn.className =
            "px-3 py-1 rounded-md text-sm transition " +
            (active
                ? "bg-purple-600 text-white"
                : "bg-gray-100 text-gray-700 hover:bg-purple-100") +
            (disabled ? " opacity-50 cursor-not-allowed" : "");
        if (!disabled) {
            btn.addEventListener("click", () => {
                currentPage = page;
                renderItems();
                window.scrollTo(0, 0);
            });
        }
        return btn;
    }

    nav.appendChild(createBtn("Prev", Math.max(1, currentPage - 1), currentPage === 1));
    for (let i = 1; i <= totalPages; i++) {
        nav.appendChild(createBtn(i, i, false, i === currentPage));
    }
    nav.appendChild(createBtn("Next", Math.min(totalPages, currentPage + 1), currentPage === totalPages));
}

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
            const pageRoute = (typeof window.RETURN_PAGE_ROUTE === 'string' && window.RETURN_PAGE_ROUTE.length)
                ? window.RETURN_PAGE_ROUTE
                : RETURN_PAGE_ROUTE;
            const sep = pageRoute.includes('?') ? '&' : '?';
            window.location.href = `${pageRoute}${sep}from_request=${encodeURIComponent(id)}`;
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

    const pageData = paginate(ITEMS_CACHE);

    if (!pageData.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-gray-500">No borrowed items</td></tr>`;
        renderPagination(0);
        return;
    }

    pageData.forEach(req => {
        const tr = document.createElement("tr");
        tr.className = "transition hover:bg-purple-50 hover:shadow-md";
        tr.dataset.id = req.id;

        const tdId = `<td class="px-4 py-3">${escapeHtml(String(req.id))}</td>`;
        const tdBorrowDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.borrow_date))}</td>`;
        const tdReturnDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.return_date))}</td>`;

        const badgeHtml = getBadgeHtml(req.status || '');
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
        if (deliveryStatus === 'dispatched' || deliveryStatus === 'delivered' || req.status === 'return_pending') {
            wrapper.appendChild(createButtonFromTemplate("btn-return-template", req.id));
        }

        tdActions.appendChild(wrapper);

        tr.innerHTML = tdId + tdBorrowDate + tdReturnDate + tdStatus;
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });

    renderPagination(ITEMS_CACHE.length);
}

// ---------- modal population ----------
export function fillBorrowModal(data) {
    if (!data) return;
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '—';
    };

    setText('mbi-location', data.location ?? '—'); 
    setText('mbi-short-status', `Borrow Request #${data.id}`);
    setText('mbi-request-id', data.id ?? '—');
    setText('mbi-borrow-date', formatDate(data.borrow_date));
    setText('mbi-return-date', formatDate(data.return_date));

    // status badge
    const sb = document.getElementById('mbi-status-badge');
    if (sb) {
        const st = (data.status || '').toLowerCase();
        try {
            sb.innerHTML = getBadgeHtml(st);
        } catch (err) {
            const stText = st ? st.toUpperCase() : '—';
            sb.className = 'inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700';
            sb.textContent = stText;
        }
    }

    const itemsList = document.getElementById('mbi-items');
    if (itemsList) {
        const itemsHtml = (data.items || []).map(i => {
            const name = escapeHtml(i.item?.name ?? 'Unknown');
            const qty = escapeHtml(String(i.quantity ?? 0));
            const assigned = (typeof i.assigned_manpower !== 'undefined' && (i.assigned_manpower !== null && i.assigned_manpower !== 0))
                ? `${escapeHtml(String(i.assigned_manpower))}${i.manpower_role ? ' (' + escapeHtml(i.manpower_role) + ')' : ''}${i.manpower_notes ? ' — ' + escapeHtml(i.manpower_notes) : ''}`
                : 'Not assigned';
            return `<li>${name} (x${qty}) — Assigned manpower: ${assigned}</li>`;
        }).join('');
        itemsList.innerHTML = itemsHtml ? itemsHtml : '<li>None</li>';
    }

    // rejection block
    const rejBlock = document.getElementById('mbi-rejection-block');
    const rejReason = document.getElementById('mbi-rejection-reason');
    if (data.status === 'rejected' && data.rejection_reason) {
        rejBlock.classList.remove('hidden');
        if (rejReason) rejReason.textContent = data.rejection_reason;
    } else {
        rejBlock.classList.add('hidden');
        if (rejReason) rejReason.textContent = '';
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

            // If the details modal is open and showing the same request, refresh it too
            // We detect by checking the modal detail element 'mbi-request-id'
            const currentShown = document.getElementById('mbi-request-id')?.textContent || '';
            if (String(currentShown) === String(payload.borrow_request_id)) {
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
