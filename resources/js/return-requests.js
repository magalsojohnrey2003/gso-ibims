// resources/js/return-requests.js
// Vite module for Return Requests page — fetch, render, modal, process (accept/reject)

const LIST_ROUTE = window.LIST_ROUTE || '/return-requests/list';
const PROCESS_BASE = window.PROCESS_BASE || '/admin/return-requests';
const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let RETURN_CACHE = [];
let CURRENT_PAGE = 1;
const PER_PAGE = 10;

/* ------------------ helpers ------------------ */
function cloneButtonTemplate(templateId, id) {
    const tpl = document.getElementById(templateId);
    if (!tpl) return document.createDocumentFragment();
    const frag = tpl.content.cloneNode(true);
    const btn = frag.querySelector('[data-action]') || frag.querySelector('button');
    if (btn) {
        const action = btn.getAttribute('data-action');
        if (action === 'view') {
            btn.addEventListener('click', () => openModal(id));
        } else if (action === 'accept') {
            btn.addEventListener('click', () => {
                showConfirmAction({
                    title: 'Accept Return Request',
                    message: `Are you sure you want to accept return request #${id}?`,
                    iconClass: 'fa-check-circle text-green-500',
                    confirmText: 'Accept',
                    onConfirm: async () => {
                        await performProcessReturn(id, 'approved', btn);
                    }
                });
            });
        } else if (action === 'reject') {
            btn.addEventListener('click', () => {
                showConfirmAction({
                    title: 'Reject Return Request',
                    message: `Are you sure you want to reject return request #${id}?`,
                    iconClass: 'fa-xmark-circle text-red-500',
                    confirmText: 'Reject',
                    onConfirm: async () => {
                        await performProcessReturn(id, 'rejected', btn);
                    }
                });
            });
        }
    }
    return frag;
}

function cloneBadgeFor(status) {
    const key = String(status || '').toLowerCase().replace(/\s+/g, '_');
    const tpl = document.getElementById(`badge-${key}-template`);
    if (tpl) return tpl.content.cloneNode(true);
    // fallback simple span if template missing
    const frag = document.createDocumentFragment();
    const span = document.createElement('span');
    span.className = 'px-2 py-1 rounded text-white text-xs font-semibold bg-gray-500';
    span.textContent = status ?? 'N/A';
    frag.appendChild(span);
    return frag;
}

function showSuccess(msg) {
    const tpl = document.getElementById('alert-success-template');
    const container = document.getElementById('alertContainer');
    if (!tpl || !container) return console.warn('Missing alert-success-template or container');
    const frag = tpl.content.cloneNode(true);
    const span = frag.querySelector('[data-alert-message]');
    if (span) span.textContent = msg;
    const appended = container.appendChild(frag);
    // auto remove after 5s
    setTimeout(() => {
        if (container.contains(appended)) appended.remove();
    }, 5000);
}

function showError(msg) {
    const tpl = document.getElementById('alert-error-template');
    const container = document.getElementById('alertContainer');
    if (!tpl || !container) return console.warn('Missing alert-error-template or container');
    const frag = tpl.content.cloneNode(true);
    const span = frag.querySelector('[data-alert-message]');
    if (span) span.textContent = msg;
    const appended = container.appendChild(frag);
    setTimeout(() => {
        if (container.contains(appended)) appended.remove();
    }, 5000);
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

/* ------------------ rendering ------------------ */
function renderTable() {
    const tbody = document.getElementById('requestsBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!Array.isArray(RETURN_CACHE) || RETURN_CACHE.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-gray-500">No return requests.</td></tr>`;
        document.getElementById('paginationContainer').innerHTML = '';
        return;
    }

    const total = RETURN_CACHE.length;
    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
    CURRENT_PAGE = Math.min(Math.max(1, CURRENT_PAGE), totalPages);
    const start = (CURRENT_PAGE - 1) * PER_PAGE;
    const pageData = RETURN_CACHE.slice(start, start + PER_PAGE);

    pageData.forEach(req => {
        const tr = document.createElement('tr');
        tr.id = `row-${req.id}`;
        tr.className = 'hover:bg-gray-50 transition';

        // Return ID
        const tdId = document.createElement('td');
        tdId.className = 'px-4 py-2';
        tdId.textContent = req.id;

        // User (first_name)
        const tdUser = document.createElement('td');
        tdUser.className = 'px-4 py-2';
        tdUser.textContent = (req.user && req.user.first_name) ? req.user.first_name : 'Unknown';

        // Condition
        const tdCondition = document.createElement('td');
        tdCondition.className = 'px-4 py-2 capitalize';
        tdCondition.textContent = req.condition ?? 'N/A';

        // Status (badge)
        const tdStatus = document.createElement('td');
        tdStatus.className = 'px-4 py-2';
        tdStatus.appendChild(cloneBadgeFor(req.status));

        // Actions
        const tdActions = document.createElement('td');
        tdActions.className = 'px-4 py-2';
        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-wrap justify-center gap-2';

        // Only show accept/reject if pending (adjust to your business rules)
        if (String(req.status).toLowerCase() === 'pending') {
            const acceptFrag = cloneButtonTemplate('btn-accept-template', req.id);
            const rejectFrag = cloneButtonTemplate('btn-reject-template', req.id);
            if (acceptFrag) wrapper.appendChild(acceptFrag);
            if (rejectFrag) wrapper.appendChild(rejectFrag);
        }

        const viewFrag = cloneButtonTemplate('btn-view-template', req.id);
        if (viewFrag) wrapper.appendChild(viewFrag);

        tdActions.appendChild(wrapper);

        tr.appendChild(tdId);
        tr.appendChild(tdUser);
        tr.appendChild(tdCondition);
        tr.appendChild(tdStatus);
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });

    renderPagination(Math.max(1, Math.ceil(RETURN_CACHE.length / PER_PAGE)));
}

function renderPagination(totalPages) {
    const nav = document.getElementById('paginationContainer');
    if (!nav) return;
    nav.innerHTML = '';
    if (totalPages <= 1) return;

    const createBtn = (label, page, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.className = 'px-3 py-1 rounded-md text-sm ' + (active ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-purple-50');
        btn.disabled = disabled;
        if (!disabled) btn.addEventListener('click', () => { CURRENT_PAGE = page; renderTable(); });
        return btn;
    };

    nav.appendChild(createBtn('Prev', Math.max(1, CURRENT_PAGE - 1), CURRENT_PAGE === 1));

    const maxButtons = 7;
    let start = Math.max(1, CURRENT_PAGE - Math.floor(maxButtons / 2));
    let end = start + maxButtons - 1;
    if (end > totalPages) { end = totalPages; start = Math.max(1, end - maxButtons + 1); }

    for (let i = start; i <= end; i++) {
        nav.appendChild(createBtn(i, i, false, i === CURRENT_PAGE));
    }

    nav.appendChild(createBtn('Next', Math.min(totalPages, CURRENT_PAGE + 1), CURRENT_PAGE === totalPages));
}

/* ------------------ network ------------------ */
async function loadReturnRequests() {
    const tbody = document.getElementById('requestsBody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-gray-500">Loading...</td></tr>`;
    try {
        const res = await fetch(LIST_ROUTE, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) {
            const text = await res.text().catch(() => '');
            throw new Error(text || `HTTP ${res.status}`);
        }
        const data = await res.json();
        RETURN_CACHE = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
        CURRENT_PAGE = 1;
        renderTable();
    } catch (err) {
        console.error('Load error', err);
        if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-red-600">Failed to load return requests.</td></tr>`;
    }
}

// --- Helpers (must come first) ---
function setTextById(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = (value === null || value === undefined) ? '—' : String(value);
}

function populateItemsList(listId, items) {
    const ul = document.getElementById(listId);
    if (!ul) return;
    ul.innerHTML = ''; // clear
    if (!Array.isArray(items) || items.length === 0) {
        const li = document.createElement('li');
        li.textContent = 'No items';
        ul.appendChild(li);
        return;
    }
    items.forEach(i => {
        const li = document.createElement('li');
        const name = (i.item && i.item.name) ? i.item.name : 'Unknown';
        li.textContent = `${name} — Qty: ${i.quantity ?? 0}`;
        ul.appendChild(li);
    });
}

/* ------------------ modal (view) ------------------ */
// helper: clone a condition badge template if present, otherwise return a simple pill fragment
function cloneConditionBadge(condition) {
    const key = String(condition || '').toLowerCase().replace(/\s+/g, '_');
    const tpl = document.getElementById(`badge-condition-${key}-template`);
    if (tpl) return tpl.content.cloneNode(true);

    const frag = document.createDocumentFragment();
    const span = document.createElement('span');
    span.className = 'inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700';
    span.textContent = condition ? String(condition) : '—';
    frag.appendChild(span);
    return frag;
}

/**
 * openModal(id)
 * Fetches /admin/return-requests/{id} and populates the modal,
 * rendering condition_groups as an accordion with progressive "Show more".
 */
async function openModal(id) {
    try {
        const res = await fetch(`${PROCESS_BASE}/${encodeURIComponent(id)}`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        if (!res.ok) {
            const txt = await res.text().catch(() => null);
            throw new Error(txt || `HTTP ${res.status}`);
        }
        const data = await res.json();

        // Basic summary fields
        setTextById('rrm-short-status', `Return Request #${data.id}`);
        setTextById('rrm-request-id', data.id ?? '—');
        setTextById('rrm-user', data.user ?? '—');
        setTextById('rrm-return-date', data.return_date ? formatDate(data.return_date) : '—');

        // Status badge
        const statusBadgeContainer = document.getElementById('rrm-status-badge');
        if (statusBadgeContainer) {
            statusBadgeContainer.innerHTML = '';
            try {
                const frag = cloneBadgeFor(data.status);
                statusBadgeContainer.appendChild(frag);
            } catch (err) {
                statusBadgeContainer.textContent = data.status ?? '—';
            }
        }

        // --------------------------
        // Condition card + grouped breakdown inside it
        // --------------------------
        const condContainer = document.getElementById('rrm-condition');
        if (condContainer) {
            condContainer.innerHTML = '';

            // overall condition badge (re-uses existing helper)
            try {
                condContainer.appendChild(cloneConditionBadge(data.condition));
            } catch (err) {
                const span = document.createElement('span');
                span.textContent = data.condition ?? '—';
                condContainer.appendChild(span);
            }

            // top-level toggle
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.id = 'rrm-condition-toggle';
            toggle.className = 'text-xs text-purple-600 mt-2 underline';
            toggle.textContent = 'Show details';
            condContainer.appendChild(toggle);

            // groups container (hidden by default)
            const groupsContainer = document.createElement('div');
            groupsContainer.id = 'rrm-condition-groups';
            groupsContainer.className = 'mt-3 space-y-3 hidden';
            condContainer.appendChild(groupsContainer);

            // label/class maps (visual)
            const labelMap = {
                'good': 'Good',
                'minor_damage': 'Minor damage',
                'major_damage': 'Major damage',
                'missing': 'Missing',
                'needs_cleaning': 'Needs cleaning'
            };
            const classMap = {
                'good': 'bg-green-100 text-green-700',
                'minor_damage': 'bg-yellow-100 text-yellow-700',
                'major_damage': 'bg-red-100 text-red-700',
                'missing': 'bg-red-200 text-red-800',
                'needs_cleaning': 'bg-orange-100 text-orange-700'
            };

            // Prefer structured condition_groups from backend
            let groups = Array.isArray(data.condition_groups) ? data.condition_groups : null;

            // Fallback: if no condition_groups, build groups from return_items (old shape)
            if ((!groups || !groups.length) && Array.isArray(data.return_items)) {
                const map = {};
                data.return_items.forEach(r => {
                    const cond = r.condition || 'good';
                    if (!map[cond]) map[cond] = { condition: cond, count: 0, items: [] };
                    const qty = Number(r.quantity || 1);
                    map[cond].count += qty;
                    map[cond].items.push({
                        id: r.id,
                        item_name: r.item_name,
                        serial: r.serial,
                        remarks: r.remarks,
                        photo_url: r.photo_url,
                        quantity: qty
                    });
                });
                groups = Object.keys(map).map(k => map[k]);
            }

            // If still no groups, show a fallback
            if (!groups || !groups.length) {
                const no = document.createElement('div');
                no.className = 'text-gray-500';
                no.textContent = 'No breakdown available.';
                groupsContainer.appendChild(no);
            } else {
                // For each group, render header + detailsPanel
                groups.forEach((g) => {
                    // header row
                    const row = document.createElement('div');
                    row.className = 'p-2 bg-gray-50 border rounded flex items-start justify-between gap-3';

                    const left = document.createElement('div');
                    left.className = 'flex items-center gap-3';

                    const title = document.createElement('div');
                    title.className = 'text-sm font-medium';
                    title.textContent = labelMap[g.condition] ?? g.condition;

                    const countBadge = document.createElement('span');
                    countBadge.className = `ml-2 inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full ${classMap[g.condition] || 'bg-gray-100 text-gray-700'}`;
                    countBadge.textContent = `${g.count ?? 0}`;

                    left.appendChild(title);
                    left.appendChild(countBadge);
                    row.appendChild(left);

                    // details toggle + panel
                    const right = document.createElement('div');
                    right.className = 'flex items-center gap-2';

                    // detailsPanel (collapse)
                    const detailsPanel = document.createElement('div');
                    detailsPanel.className = 'mt-2 p-2 bg-white border rounded text-sm text-gray-700 hidden w-full';

                    // items list (ul)
                    const ul = document.createElement('ul');
                    ul.className = 'list-disc pl-5 space-y-1';

                    // progressive reveal parameters
                    const initialLimit = 3;
                    const step = 3;
                    const total = Array.isArray(g.items) ? g.items.length : 0;
                    let visibleCount = Math.min(initialLimit, total);

                    // populate li elements (initially hide those beyond visibleCount)
                    (g.items || []).forEach((it, idx) => {
                        const li = document.createElement('li');
                        li.className = 'text-sm';
                        const parts = [];
                        if (it.item_name) parts.push(it.item_name);
                        if (it.quantity && Number(it.quantity) > 1) parts.push(`x${it.quantity}`);
                        if (it.serial) parts.push(`S/N: ${it.serial}`);
                        if (it.remarks) parts.push(it.remarks);
                        li.textContent = parts.join(' — ');

                        if (it.photo_url) {
                            const a = document.createElement('a');
                            a.href = it.photo_url;
                            a.target = '_blank';
                            a.rel = 'noopener noreferrer';
                            a.className = 'ml-2 text-xs underline';
                            a.textContent = 'photo';
                            li.appendChild(a);
                        }

                        if (idx >= visibleCount) li.classList.add('hidden');
                        ul.appendChild(li);
                    });

                    detailsPanel.appendChild(ul);

                    // showMore control for progressive reveal
                    if (total > initialLimit) {
                        const ctrlDiv = document.createElement('div');
                        ctrlDiv.className = 'mt-2 flex items-center gap-2';

                        const showMoreBtn = document.createElement('button');
                        showMoreBtn.type = 'button';
                        showMoreBtn.className = 'text-xs text-purple-600 underline';
                        const remainingInit = Math.max(0, total - visibleCount);
                        showMoreBtn.textContent = `Show ${Math.min(step, remainingInit)} more`;

                        showMoreBtn.addEventListener('click', () => {
                            if (visibleCount < total) {
                                const newVisible = Math.min(visibleCount + step, total);
                                for (let i = visibleCount; i < newVisible; i++) {
                                    const li = ul.children[i];
                                    if (li) li.classList.remove('hidden');
                                }
                                visibleCount = newVisible;
                                const remaining = total - visibleCount;
                                if (remaining > 0) {
                                    showMoreBtn.textContent = `Show ${Math.min(step, remaining)} more`;
                                } else {
                                    showMoreBtn.textContent = 'Hide';
                                }
                            } else {
                                // collapse back to initialLimit
                                for (let i = initialLimit; i < total; i++) {
                                    const li = ul.children[i];
                                    if (li) li.classList.add('hidden');
                                }
                                visibleCount = Math.min(initialLimit, total);
                                const remaining = total - visibleCount;
                                showMoreBtn.textContent = `Show ${Math.min(step, remaining)} more`;
                            }
                        });

                        ctrlDiv.appendChild(showMoreBtn);
                        detailsPanel.appendChild(ctrlDiv);
                    }

                    // details toggle button (shows/hides the whole detailsPanel)
                    const detailsBtn = document.createElement('button');
                    detailsBtn.type = 'button';
                    detailsBtn.className = 'text-xs underline';
                    detailsBtn.textContent = total > 0 ? `Show ${total} ${total > 1 ? 'rows' : 'row'}` : 'Show rows';
                    detailsBtn.addEventListener('click', () => {
                        const wasHidden = detailsPanel.classList.contains('hidden');
                        detailsPanel.classList.toggle('hidden', !wasHidden);
                        detailsBtn.textContent = wasHidden ? 'Hide' : `Show ${total} ${total > 1 ? 'rows' : 'row'}`;
                    });

                    right.appendChild(detailsBtn);
                    row.appendChild(right);

                    groupsContainer.appendChild(row);
                    groupsContainer.appendChild(detailsPanel);
                });
            }

            // Top-level toggle for showing/hiding all groups
            toggle.onclick = () => {
                const wasHidden = groupsContainer.classList.contains('hidden');
                groupsContainer.classList.toggle('hidden', !wasHidden);
                toggle.textContent = wasHidden ? 'Hide details' : 'Show details';
            };
        }

        // Keep old details area hidden to avoid duplication (safe even if element removed)
        const detailsContainer = document.getElementById('rrm-return-items-list-container');
        const detailsList = document.getElementById('rrm-return-items-list');
        if (detailsList) detailsList.innerHTML = '';
        if (detailsContainer) detailsContainer.classList.add('hidden');

        // Borrow-request items (high-level) - unchanged
        const itemsList = document.getElementById('rrm-items');
        if (itemsList) {
            itemsList.innerHTML = '';
            const items = Array.isArray(data.items) ? data.items : [];
            if (!items.length) {
                const li = document.createElement('li'); li.textContent = '—'; itemsList.appendChild(li);
            } else {
                items.forEach(it => {
                    const li = document.createElement('li');
                    const name = (it?.item?.name) ? it.item.name : 'Unknown';
                    const qty = it?.quantity ?? 1;
                    li.textContent = `${name} (x${qty})`;
                    itemsList.appendChild(li);
                });
            }
        }

        // Damage reason block (unchanged)
        const damageBlock = document.getElementById('rrm-damage-block');
        const damageReasonEl = document.getElementById('rrm-damage-reason');
        if (data.damage_reason) {
            damageBlock?.classList.remove('hidden');
            if (damageReasonEl) damageReasonEl.textContent = data.damage_reason;
        } else {
            damageBlock?.classList.add('hidden');
            if (damageReasonEl) damageReasonEl.textContent = '';
        }

        // open modal
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'returnRequestModal' }));

    } catch (err) {
        console.error('Failed loading return request details', err);
        if (typeof showError === 'function') showError('Failed to load details');
    }
}



/* ------------------ confirm modal helper ------------------ */
/**
 * showConfirmAction(options)
 * options: { title, message, iconClass, confirmText, onConfirm (async function) }
 */
function showConfirmAction({ title = 'Confirm', message = 'Are you sure?', iconClass = 'fa-exclamation-circle text-yellow-500', confirmText = 'Confirm', onConfirm = null } = {}) {
    const iconEl = document.getElementById('confirmActionIcon');
    const titleEl = document.getElementById('confirmActionTitle');
    const msgEl = document.getElementById('confirmActionMessage');
    const confirmBtn = document.getElementById('confirmActionConfirmBtn');

    if (!confirmBtn) {
        // fallback to native confirm if modal missing
        if (window.confirm) return window.confirm(message) ? (onConfirm ? onConfirm() : null) : null;
        return null;
    }

    // set content
    if (iconEl) {
        // remove previous classes and set new ones
        iconEl.className = 'fas ' + (String(iconClass).trim() || 'fa-exclamation-circle') ;
    }
    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;
    confirmBtn.textContent = confirmText;

    // remove any previously attached handler to avoid duplicates
    if (confirmBtn._confirmHandler) {
        try { confirmBtn.removeEventListener('click', confirmBtn._confirmHandler); } catch (e) {}
        confirmBtn._confirmHandler = null;
    }

    const handler = async (ev) => {
        try {
            confirmBtn.disabled = true;
            if (typeof onConfirm === 'function') {
                await onConfirm();
            }
        } catch (err) {
            console.error('Confirm action failed', err);
            showError('Action failed');
        } finally {
            confirmBtn.disabled = false;
            // close modal after performing action
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirmActionModal' }));
        }
    };

    confirmBtn._confirmHandler = handler;
    confirmBtn.addEventListener('click', handler);

    // open modal
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirmActionModal' }));
}

/* ------------------ perform process (accept/reject) ------------------ */
async function performProcessReturn(id, status, btn = null) {
    try {
        const row = document.getElementById(`row-${id}`);
        if (row) row.querySelectorAll('button').forEach(b => b.disabled = true);

        const res = await fetch(`${PROCESS_BASE}/${id}/process`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ status })
        });

        if (!res.ok) {
            let errText = '';
            try { const j = await res.json(); errText = j.message || JSON.stringify(j); } catch (e) { errText = await res.text().catch(()=> ''); }
            throw new Error(errText || `HTTP ${res.status}`);
        }

        showSuccess(`Return request #${id} ${status === 'approved' ? 'approved' : 'rejected'} successfully.`);
        await loadReturnRequests();
    } catch (err) {
        console.error('Process error', err);
        showError('Failed to process request.');
        const row = document.getElementById(`row-${id}`);
        if (row) row.querySelectorAll('button').forEach(b => b.disabled = false);
    }
}

/* ------------------ boot ------------------ */
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('requestsBody')) return;
    loadReturnRequests();
    // optional polling:
    // setInterval(loadReturnRequests, 10000);
});

/* expose for debugging if needed */
window.loadReturnRequests = loadReturnRequests;
window.openReturnRequestModal = openModal;
export { loadReturnRequests, openModal };

// --- Real-time events wiring ---
window.addEventListener('realtime:return-request-submitted', (ev) => {
    console.log('Realtime: return request submitted', ev.detail);
    if (typeof loadReturnRequests === 'function') loadReturnRequests();
});

window.addEventListener('realtime:return-request-processed', (ev) => {
    console.log('Realtime: return request processed', ev.detail);
    if (typeof loadReturnRequests === 'function') loadReturnRequests();
});