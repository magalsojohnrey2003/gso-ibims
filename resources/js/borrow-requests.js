// resources/js/borrow-requests.js
// Handles fetching, rendering and modal population for borrow requests.


const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const LIST_ROUTE = window.LIST_ROUTE || '/admin/borrow-requests/list';

let BORROW_CACHE = [];
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

function humanizeStatus(status) {
    if (!status) return '—';
    return String(status)
        .split('_')
        .map(s => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase())
        .join(' ');
}

function showAlert(type, msg) {
    const tpl = document.getElementById(`alert-${type}-template`);
    const container = document.getElementById('adminAlertContainer');
    if (!tpl || !container) return;
    const frag = tpl.content.cloneNode(true);
    const span = frag.querySelector('[data-alert-message]');
    if (span) span.textContent = msg;
    const appended = container.appendChild(frag);
    // remove after 5 seconds
    setTimeout(() => {
        if (container.contains(appended)) appended.remove();
    }, 5000);
}
function showError(m){ showAlert('error', m); }
function showSuccess(m){ showAlert('success', m); }

// ---------- data fetch ----------
export async function loadBorrowRequests() {
    try {
        const res = await fetch(LIST_ROUTE, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            const err = await res.json().catch(()=>null);
            throw err?.message || `HTTP ${res.status}`;
        }
        const json = await res.json();
        // support both array responses or paginated { data: [...] }
        BORROW_CACHE = Array.isArray(json) ? json : (Array.isArray(json.data) ? json.data : []);
        currentPage = 1;
        renderBorrowRequests();
    } catch (e) {
        console.error(e);
        showError("Failed to load borrow requests.");
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
    const totalPages = Math.ceil(total / perPage);
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
                renderBorrowRequests();
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

// ---------- buttons from template ----------
// --- inside existing file, replace createButtonFromTemplate's accept mapping and add new helpers ---

function createButtonFromTemplate(templateId, id) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    // find the first interactive element inside the template
    const btn = frag.querySelector('button,[data-action]');
    if (!btn) return frag;

    // prefer data-action attribute when present, otherwise fallback to button text or other attribute
    const action = (btn.getAttribute('data-action') || '').toLowerCase().trim();

    // Map multiple synonyms (validate/accept) and (deliver/dispatch) to handlers
    if (action === 'view') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); viewRequest(id); });
    } else if (action === 'accept' || action === 'validate') {
        // "Validate" / "Accept" -> open assign manpower modal
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openAssignManpowerModal(id); });
    } else if (action === 'reject') {
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openConfirmModal(id, 'rejected', btn); });
    } else if (action === 'dispatch' || action === 'deliver' || action === 'deliver_items') {
        // Deliver -> open confirm modal with status 'delivered' so confirm handler runs deliver branch
        btn.addEventListener('click', (ev) => { ev.stopPropagation(); openConfirmModal(id, 'delivered', btn); });
    }else {
        // unknown action: keep fragment but make button inert (prevents silent failures)
        console.warn('Unknown button action in template', templateId, 'action=', action);
    }

    return frag;
}

// Collect rows in assign manpower modal (includes qty + not_in_inventory flag)
function collectManpowerAssignments() {
    const container = document.getElementById('assignManpowerItemsContainer');
    if (!container) return [];
    const rows = [];
    container.querySelectorAll('div[data-borrow-request-item-id]').forEach(row => {
        const bri = row.dataset.borrowRequestItemId;
        const assignedVal = parseInt(row.querySelector('.assign-manpower-input')?.value || '0', 10) || 0;
        const qtyVal = parseInt(row.querySelector('.assign-qty-input')?.value || '0', 10) || 0;
        const roleVal = row.querySelector('.assign-manpower-role')?.value || null;
        const notesVal = row.querySelector('.assign-manpower-notes')?.value || null;
        const notInInventory = !!row.querySelector('.assign-not-inventory')?.checked;
        rows.push({
            borrow_request_item_id: bri,
            assigned_manpower: assignedVal,
            quantity: qtyVal,
            manpower_role: roleVal,
            manpower_notes: notesVal,
            not_in_inventory: notInInventory
        });
    });
    return rows;
}

// Open assign-manpower modal and populate fields from BORROW_CACHE
function openAssignManpowerModal(id) {
    const req = BORROW_CACHE.find(r => r.id === id);
    if (!req) {
        showError('Request not found');
        return;
    }

    const container = document.getElementById('assignManpowerItemsContainer');
    const requestIdInput = document.getElementById('assignManpowerRequestId');
    const requestedTotalEl = document.getElementById('assignRequestedTotal');
    const warningEl = document.getElementById('assignManpowerWarning');
    const forceCheckbox = document.getElementById('assignForceOverride');

    if (!container || !requestIdInput || !requestedTotalEl) return;

    container.innerHTML = '';
    requestIdInput.value = id;
    requestedTotalEl.textContent = req.manpower_count ?? '—';
    warningEl.classList.add('hidden');
    warningEl.textContent = '';
    if (forceCheckbox) forceCheckbox.checked = false;

    // roles - keep consistent with backend choices
    const ROLE_OPTIONS = ['', 'Setup', 'Operator', 'Driver', 'Other'];

        (req.items || []).forEach(it => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center border-b pb-2 py-2';

        const nameCol = document.createElement('div');
        nameCol.className = 'col-span-4';
        nameCol.innerHTML = `<div class="font-medium">${escapeHtml(it.item?.name ?? 'Unknown')}</div><div class="text-xs text-gray-500">Requested: ${escapeHtml(String(it.quantity || 0))}</div>`;

        // hidden input for borrow_request_item_id (kept for form-style structure)
        const hiddenInput = `<input type="hidden" name="borrow_request_item_id" value="${escapeHtml(String(it.id || (it.borrow_request_item_id ?? '')))}" />`;

        // quantity edit (admin may only reduce here)
        const qtyCol = document.createElement('div');
        qtyCol.className = 'col-span-2';
        qtyCol.innerHTML = `
            ${hiddenInput}
            <label class="text-xs text-gray-600">Qty</label>
            <input type="number" min="0" max="${escapeHtml(String(it.quantity ?? 0))}" class="w-full border rounded px-2 py-1 assign-qty-input" 
                value="${escapeHtml(String(it.quantity ?? 0))}" />
        `;

        // assigned manpower
        const assignedInput = document.createElement('div');
        assignedInput.className = 'col-span-2';
        assignedInput.innerHTML = `
            <label class="text-xs text-gray-600">Manpower</label>
            <input type="number" min="0" class="w-full border rounded px-2 py-1 assign-manpower-input" 
                value="${escapeHtml(String(it.assigned_manpower ?? 0))}" />
        `;

        // role select
        const roleSelect = document.createElement('div');
        roleSelect.className = 'col-span-2';
        const ROLE_OPTIONS = ['', 'Setup', 'Operator', 'Driver', 'Other'];
        const roleHtml = ROLE_OPTIONS.map(opt => `<option value="${escapeHtml(opt)}" ${it.manpower_role === opt ? 'selected' : ''}>${escapeHtml(opt || '—')}</option>`).join('');
        roleSelect.innerHTML = `
            <label class="text-xs text-gray-600">Role</label>
            <select class="w-full border rounded px-2 py-1 assign-manpower-role">${roleHtml}</select>
        `;

        // notes + not in inventory checkbox
        const notesInput = document.createElement('div');
        notesInput.className = 'col-span-2';
        notesInput.innerHTML = `
            <label class="text-xs text-gray-600">Notes</label>
            <input type="text" class="w-full border rounded px-2 py-1 assign-manpower-notes" value="${escapeHtml(it.manpower_notes ?? '')}" placeholder="notes (optional)" />
            <div class="text-xs mt-1"><label><input type="checkbox" class="assign-not-inventory" ${it.manpower_notes && String(it.manpower_notes).includes('[NOT IN INVENTORY]') ? 'checked' : ''} /> Not in physical inventory</label></div>
        `;

        row.appendChild(nameCol);
        row.appendChild(qtyCol);
        row.appendChild(assignedInput);
        row.appendChild(roleSelect);
        row.appendChild(notesInput);

        // attach data attribute with borrow_request_item_id for retrieval
        row.dataset.borrowRequestItemId = it.id ?? (it.borrow_request_item_id ?? '');

        container.appendChild(row);
    });

    // open modal
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'assignManpowerModal' }));
}

// wire the confirm button for the assignManpowerModal
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('assignManpowerConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async (ev) => {
        const id = confirmBtn.dataset.requestId;
        const status = confirmBtn.dataset.status;
        if (!id || !status) { showError('Invalid action.'); return; }
        confirmBtn.disabled = true;
        try {
            if (status === 'delivered') {
                // deliver endpoint
                const res = await fetch(`/admin/borrow-requests/${encodeURIComponent(id)}/deliver`, {
                    method: 'POST',
                    headers: {
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json().catch(() => null);
                if (!res.ok) throw new Error(data?.message || `Delivery failed (status ${res.status})`);
                await loadBorrowRequests();
                showSuccess('Items marked as delivered successfully.');
            } else if (status === 'validated') {
                // from assign manpower modal
                const assignments = collectManpowerAssignments();
                await updateRequest(Number(id), 'validated', assignments, confirmBtn);
            } else {
                await updateRequest(Number(id), status, confirmBtn);
            }

            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmActionModal' }));
        } catch (err) {
            console.error(err);
            showError(err?.message || 'Failed to update.');
        } finally {
            confirmBtn.disabled = false;
        }
    });
});

// wire the Save & Approve button for the assignManpowerModal
// Save & Approve handler — replaced to first save assignments as 'validated' then optionally approve
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('assignManpowerConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async (ev) => {
        ev.preventDefault();
        confirmBtn.disabled = true;
        try {
            const requestId = document.getElementById('assignManpowerRequestId')?.value;
            if (!requestId) { showError('Missing request'); return; }

            const container = document.getElementById('assignManpowerItemsContainer');
            const forceCheckbox = document.getElementById('assignForceOverride');
            const requestedTotal = parseInt(document.getElementById('assignRequestedTotal')?.textContent || '0', 10) || 0;

            const assignments = collectManpowerAssignments();
            let totalAssigned = assignments.reduce((s, a) => s + (Number(a.assigned_manpower) || 0), 0);

            const warningEl = document.getElementById('assignManpowerWarning');
            if (totalAssigned > requestedTotal && !forceCheckbox.checked) {
                if (warningEl) {
                    warningEl.textContent = `Assigned manpower total (${totalAssigned}) exceeds requested (${requestedTotal}). Check 'Allow assignments to exceed requested total' to proceed.`;
                    warningEl.classList.remove('hidden');
                }
                return;
            } else {
                if (warningEl) { warningEl.classList.add('hidden'); warningEl.textContent = ''; }
            }

            // 1) Save assignments first using status 'validated' so server will persist manpower fields
            //   - silent: true (don't show an extra toast for this intermediate step)
            await updateRequest(Number(requestId), 'validated', assignments, confirmBtn, !!forceCheckbox.checked, true);

            // 2) Then approve to proceed with allocation if admin intended Save & Approve
            // Note: we call approved with no assignments (they are already saved). This triggers the approved logic on server.
            await updateRequest(Number(requestId), 'approved', null, confirmBtn, false, false);

            // close modal on success
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'assignManpowerModal' }));
        } catch (err) {
            console.error(err);
            // updateRequest already shows errors
        } finally {
            confirmBtn.disabled = false;
        }
    });
});

// updateRequest now accepts optional manpower_assignments and force_assign flag
async function updateRequest(id, status, manpower_assignments = null, btn = null, force_assign = false, silent = false) {
    if (btn) btn.disabled = true;
    try {
        const body = { status };
        if (manpower_assignments) body.manpower_assignments = manpower_assignments;
        if (force_assign) body.force_assign = true;

        const res = await fetch(`/admin/borrow-requests/${id}/update-status`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": CSRF_TOKEN,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(body)
        });
        const data = await res.json().catch(()=>null);
        if (!res.ok) {
            throw new Error(data?.message || `Update failed (status ${res.status})`);
        }
        // update local cache
        const r = BORROW_CACHE.find(x => x.id === id);
        if (r) r.status = status;

        // refresh the entire list from server for freshest data
        await loadBorrowRequests();

        // publish a cross-tab notification so user pages can refresh immediately
        try {
            const payload = { borrow_request_id: Number(id), new_status: status, timestamp: Date.now() };
            localStorage.setItem('borrow_request_updated', JSON.stringify(payload));
            // remove after short time so it can fire again later if needed
            setTimeout(() => { try { localStorage.removeItem('borrow_request_updated'); } catch(e){} }, 1000);
        } catch (e) {
            console.warn('Could not set storage event', e);
        }

        if (!silent) showSuccess(`Borrow request ${humanizeStatus(status)} successfully!`);
        return data;
    } catch (err) {
        console.error(err);
        showError(err?.message || "Failed to update status");
        throw err;
    } finally {
        if (btn) btn.disabled = false;
    }
}

// ---------- render table ----------
function renderBorrowRequests() {
    const tbody = document.getElementById("borrowRequestsTableBody");
    if (!tbody) return;
    tbody.innerHTML = "";

    const pageData = paginate(BORROW_CACHE);

    if (!pageData.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="py-4 text-gray-500">No requests found</td></tr>`;
        renderPagination(0);
        return;
    }

    pageData.forEach(req => {
        const tr = document.createElement('tr');
        tr.className = "transition hover:bg-purple-50 hover:shadow-md";

        const tdBorrower = `<td class="px-4 py-3">${escapeHtml(req.user?.first_name ?? 'Unknown')}</td>`;
        const tdId = `<td class="px-4 py-3">${escapeHtml(String(req.id))}</td>`;
        const tdBorrowDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.borrow_date))}</td>`;
        const tdReturnDate = `<td class="px-4 py-3">${escapeHtml(formatDate(req.return_date))}</td>`;

        // Status as Title Case + light background colored pill (matches other pages)
        const st = (req.status || '').toLowerCase();
        const label = humanizeStatus(st);
        const badgeBase = 'inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full ';
        const badgeColors =
            st === 'approved' ? 'bg-green-100 text-green-700' :
            st === 'pending' ? 'bg-yellow-100 text-yellow-700' :
            st === 'return_pending' ? 'bg-blue-100 text-blue-700' :
            st === 'rejected' ? 'bg-red-100 text-red-700' :
            'bg-gray-100 text-gray-700';
        const tdStatus = `<td class="px-4 py-3"><span class="${badgeBase + badgeColors}">${escapeHtml(label)}</span></td>`;

        const tdActions = document.createElement("td");
        tdActions.className = "px-4 py-3";
        const wrapper = document.createElement("div");
        wrapper.className = "flex justify-center gap-2";

                const viewTpl = req.status === "pending" ? "btn-view-details-template" : "btn-view-template";
        wrapper.appendChild(createButtonFromTemplate(viewTpl, req.id));

        // Show Validate + Reject when pending
        // Show Validate + Reject when pending
if (String(req.status).toLowerCase() === 'pending') {
    // use the template id that exists in the blade
    wrapper.appendChild(createButtonFromTemplate("btn-validate-template", req.id)); // Validate
    wrapper.appendChild(createButtonFromTemplate("btn-reject-template", req.id));
}
// Show Deliver Items when approved (or validated) — hide Reject once dispatched
else if (['approved','validated'].includes(String(req.status).toLowerCase())) {
    const deliveryStatus = (req.delivery_status || '').toLowerCase();
    if (deliveryStatus !== 'dispatched') {
        // match the template id in blade
        wrapper.appendChild(createButtonFromTemplate("btn-deliver-template", req.id)); // Deliver Items
        // Admin may still reject before dispatch
        wrapper.appendChild(createButtonFromTemplate("btn-reject-template", req.id));
    } else {
        // already dispatched — do not show Reject (only view)
    }
}
// delivered or other statuses: only view (already appended)


        // if delivered -> only view (already appended)
 else if (req.status === "delivered") {
                // Step 3: delivered — only view (no actions). Keep the view button which is already appended earlier.
                // optionally add other actions here if needed later.
            } else {
                // Other statuses: default behavior - keep view and maybe reject for safety if you want
                // e.g., for 'rejected' or 'return_pending' - no admin actions
            }

        tdActions.appendChild(wrapper);

        tr.innerHTML = tdBorrower + tdId + tdBorrowDate + tdReturnDate + tdStatus;
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });

    renderPagination(BORROW_CACHE.length);
}

// ---------- modal population ----------
export function fillRequestModal(req) {
    if (!req) return;
    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.textContent = text ?? '—';
    };

    setText('requestTitle', `Borrow Request Details`);
    setText('requestShortStatus', `Borrow Request #${req.id}`);
    setText('borrowerName', req.user?.first_name ?? 'Unknown');

    // render items as a neat <ul> with each item on its own line
    const itemsEl = document.getElementById('itemsList');
    if (itemsEl) {
        const itemsHtml = (req.items || []).map(i => {
            const name = escapeHtml(i.item?.name ?? 'Unknown');
            const qty = escapeHtml(String(i.quantity ?? 0));
            const assigned = (typeof i.assigned_manpower !== 'undefined' && (i.assigned_manpower !== null && i.assigned_manpower !== 0))
                ? `${escapeHtml(String(i.assigned_manpower))}${i.manpower_role ? ' (' + escapeHtml(i.manpower_role) + ')' : ''}${i.manpower_notes ? ' — ' + escapeHtml(i.manpower_notes) : ''}`
                : 'Not assigned';
            return `<li>${name} (x${qty}) — Assigned manpower: ${assigned}</li>`;
        }).join('');
        itemsEl.innerHTML = itemsHtml ? `<ul class="list-disc list-inside text-gray-600">${itemsHtml}</ul>` : '<div class="text-gray-600">No items</div>';
    }

    setText('itemsList', items || 'No items');
    setText('requestLocation', req.location ?? '—');
    // Manpower (new)
    setText('manpowerCount', req.manpower_count ?? '—');

    setText('borrowDate', formatDate(req.borrow_date));
    setText('returnDate', formatDate(req.return_date));

    // status badge (Title Case)
    const statusBadge = document.getElementById('statusBadge');
    if (statusBadge) {
        const st = (req.status || '').toLowerCase();
        const label = humanizeStatus(st);
        statusBadge.textContent = label;
        statusBadge.className =
            'inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full ' +
            (st === 'approved'
                ? 'bg-green-100 text-green-700'
                : st === 'pending'
                ? 'bg-yellow-100 text-yellow-700'
                : st === 'return_pending'
                ? 'bg-blue-100 text-blue-700'
                : st === 'rejected'
                ? 'bg-red-100 text-red-700'
                : 'bg-gray-100 text-gray-700');
    }

    // status info
    const info = req.status === 'approved' ? 'This request has been approved and items have been borrowed.' :
                 req.status === 'pending' ? 'This request is pending review.' :
                 req.status === 'rejected' ? 'This request was rejected.' : 'Status information not available.';
    setText('statusInfo', info);
}

function viewRequest(id) {
    const req = BORROW_CACHE.find(r => r.id === id);
    if (!req) return;
    fillRequestModal(req);
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'requestDetailsModal' }));
}

// ---------- confirmation modal helpers ----------
// ---------- confirmation modal helpers ----------
function openConfirmModal(id, status, btnRef = null) {
    // determine title/message/icon based on status
    let title = 'Confirm Action';
    let message = 'Are you sure?';
    let icon = 'fas fa-exclamation-circle text-yellow-500';

    const s = String(status || '').toLowerCase();
    if (s === 'approved' || s === 'validate' || s === 'validated') {
        title = 'Approve request';
        message = 'Are you sure you want to approve this borrow request?';
        icon = 'fas fa-check-circle text-green-700';
    } else if (s === 'rejected') {
        title = 'Reject request';
        message = 'Are you sure you want to reject this borrow request?';
        icon = 'fas fa-times-circle text-red-600';
    } else if (s === 'delivered' || s === 'dispatch' || s === 'dispatched') {
        title = 'Deliver items';
        message = 'Confirm you want to mark these items as delivered to the borrower?';
        icon = 'fas fa-truck text-indigo-600';
    }

    const iconEl = document.getElementById('confirmActionIcon');
    if (iconEl) {
        iconEl.className = icon;
    }

    const titleEl = document.getElementById('confirmActionTitle');
    if (titleEl) titleEl.textContent = title;
    const msgEl = document.getElementById('confirmActionMessage');
    if (msgEl) msgEl.textContent = message;

    // attach data to confirm button
    const confirmBtn = document.getElementById('confirmActionConfirmBtn');
    if (confirmBtn) {
        confirmBtn.dataset.requestId = id;
        confirmBtn.dataset.status = status;
    }

    // open modal
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirmActionModal' }));
}


// when confirm button clicked in modal, call updateRequest and close modal
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmActionConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async (ev) => {
        const id = confirmBtn.dataset.requestId;
        const status = (confirmBtn.dataset.status || '').toLowerCase();
        if (!id || !status) {
            showError('Invalid action.');
            return;
        }

        confirmBtn.disabled = true;
        try {
            if (status === 'delivered') {
                // Use the dispatch route which matches your server controller.
                // (Some earlier code used '/deliver' which does not exist; /dispatch is the correct one.)
                const url = `/admin/borrow-requests/${encodeURIComponent(id)}/dispatch`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({})
                });

                const payload = await res.json().catch(() => null);
                if (!res.ok) {
                    // surface server message if present
                    const msg = payload?.message || `Failed to mark as delivered (status ${res.status})`;
                    throw new Error(msg);
                }

                await loadBorrowRequests();
                showSuccess('Items marked as delivered successfully.');

                // Notify other browser contexts (user page) so they can reload immediately
                try {
                    window.dispatchEvent(new CustomEvent('realtime:borrow-request-status-updated', {
                        detail: { borrow_request_id: id, new_status: 'dispatched' }
                    }));
                } catch (e) {
                    // non-fatal
                    console.warn('Could not dispatch realtime event', e);
                }
                
            } else {
                // generic status updates like 'rejected' (delegates to updateRequest)
                await updateRequest(Number(id), status, confirmBtn);
            }

            // close modal after success
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmActionModal' }));
        } catch (err) {
            console.error('Confirm action failed', err);
            showError(err?.message || 'Failed to perform action.');
        } finally {
            confirmBtn.disabled = false;
        }
    });
});

// ---------- utilities ----------
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ---------- boot ----------
document.addEventListener('DOMContentLoaded', () => {
    // only run on pages that have the table
    if (!document.getElementById('borrowRequestsTableBody')) return;
    loadBorrowRequests();
    // poll updates (optional)
    setInterval(loadBorrowRequests, 10000);
});

// Export a couple helpers to window for manual debugging if needed:
window.loadBorrowRequests = loadBorrowRequests;
window.fillRequestModal = fillRequestModal;
window.formatDate = formatDate;

// ---------- real-time listeners ----------
window.addEventListener('realtime:borrow-request-submitted', (ev) => {
    console.log('Realtime: new borrow request received', ev.detail);
    loadBorrowRequests();  // refresh the list
});

window.addEventListener('realtime:borrow-request-status-updated', (ev) => {
    console.log('Realtime: borrow request status updated', ev.detail);
    loadBorrowRequests();  // refresh the list
});

