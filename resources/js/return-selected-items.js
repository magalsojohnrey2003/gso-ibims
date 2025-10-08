const CONDITION_OPTIONS = [
    { value: 'good', label: 'Good' },
    { value: 'needs_cleaning', label: 'Needs Cleaning' },
    { value: 'minor_damage', label: 'Minor Damage' },
    { value: 'major_damage', label: 'Major Damage' },
    { value: 'missing', label: 'Missing' },
];

const DAMAGE_CONDITIONS = new Set(['needs_cleaning', 'minor_damage', 'major_damage', 'missing']);
const SERIAL_CHUNK = 60;
const DEFAULT_CONDITION = 'good';

function escapeHtml(value) {
    if (value === undefined || value === null) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDateRange(borrowDate, returnDate) {
    const format = (input) => {
        if (!input) return 'N/A';
        const d = new Date(input);
        if (Number.isNaN(d.valueOf())) return input;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    };
    return `${format(borrowDate)} - ${format(returnDate)}`;
}

export default class ReturnSelectedItemsModal {
    constructor(options = {}) {
        this.modalName = options.modalName || 'return-selected-items';
        this.submitRoute = options.submitRoute;
        this.csrfToken = options.csrfToken || '';
        this.showToast = typeof options.showToast === 'function' ? options.showToast : () => {};
        this.onSubmitSuccess = typeof options.onSubmitSuccess === 'function' ? options.onSubmitSuccess : () => {};
        this.fetchRequestDetails = typeof options.fetchRequestDetails === 'function' ? options.fetchRequestDetails : null;

        this.steps = ['items', 'conditions', 'summary'];
        this.currentStep = 0;
        this.state = [];
        this.itemSearchTerm = '';
        this.root = document.querySelector('[data-return-modal]');

        if (!this.root) {
            console.warn('ReturnSelectedItemsModal: modal root not found.');
            return;
        }

        this.stepContainers = {};
        this.steps.forEach((step) => {
            this.stepContainers[step] = this.root.querySelector(`[data-return-step="${step}"]`);
        });

        this.progressBar = this.root.querySelector('#returnModalProgress');
        this.stepLabel = this.root.querySelector('#returnModalStepLabel');
        this.btnNext = this.root.querySelector('[data-return-next]');
        this.btnPrev = this.root.querySelector('[data-return-prev]');
        this.btnSubmit = this.root.querySelector('[data-return-submit]');
        this.btnClose = this.root.querySelector('[data-return-close]');

        this.isSubmitting = false;

        this.bindBaseEvents();
    }

    bindBaseEvents() {
        this.btnNext?.addEventListener('click', async () => {
            if (this.isSubmitting) return;
            await this.handleNext();
        });

        this.btnPrev?.addEventListener('click', async () => {
            if (this.isSubmitting) return;
            await this.goToStep(this.currentStep - 1);
        });

        this.btnCancel?.addEventListener('click', () => {
            if (this.isSubmitting) return;
            this.close();
        });

        this.btnClose?.addEventListener('click', () => {
            if (this.isSubmitting) return;
            this.close();
        });

        this.btnSubmit?.addEventListener('click', () => {
            if (this.isSubmitting) return;
            this.handleSubmit();
        });
    }
    async open(selectionState) {
        if (!this.root) return;
        this.state = this.prepareState(selectionState);
        if (!this.state.length) {
            this.showToast('info', 'Select at least one item before returning.');
            return;
        }

        this.itemSearchTerm = '';
        this.currentStep = 0;
        await this.goToStep(0);
        window.dispatchEvent(new CustomEvent('open-modal', { detail: this.modalName }));
    }

    close() {
        if (!this.root) return;
        window.dispatchEvent(new CustomEvent('close-modal', { detail: this.modalName }));
        this.resetState();
    }

    resetState() {
        this.state = [];
        this.currentStep = 0;
        this.itemSearchTerm = '';
        Object.values(this.stepContainers).forEach((container) => {
            if (container) container.innerHTML = '';
        });
        this.updateNavButtons();
        this.updateProgress();
    }

    prepareState(selectionState = []) {
        return selectionState
            .map((req) => {
                const requestId = String(req.requestId ?? req.id ?? '');
                const rawItems = Array.isArray(req.items) ? req.items : [];
                const items = rawItems
                    .filter((item) => item && item.selected !== false)
                    .map((item, idx) => {
                        const sourceSerials = Array.isArray(item.allSerials) ? item.allSerials : [];
                        const normalisedSerials = sourceSerials.map((serial, serialIndex) => {
                            const id = serial?.id ?? serial?.item_instance_id ?? serial?.serial ?? serial?.property_number ?? `${idx}-${serialIndex}`;
                            return {
                                id: String(id),
                                serial: serial?.serial ?? serial?.property_number ?? serial?.serial_number ?? `Serial ${serialIndex + 1}`,
                                property_number: serial?.property_number ?? serial?.serial ?? serial?.serial_number ?? null,
                            };
                        });
                        const selectedSerials = new Set((Array.isArray(item.serials) ? item.serials : []).map((value) => String(value)));
                        if (!selectedSerials.size && normalisedSerials.length) {
                            normalisedSerials.forEach((serial) => selectedSerials.add(String(serial.id)));
                        }
                        return {
                            id: String(item.itemId ?? item.id ?? idx),
                            name: item.name ?? 'Item',
                            quantity: Number((item.quantity ?? item.total_quantity ?? normalisedSerials.length) ?? 0),
                            condition: item.condition ? String(item.condition).toLowerCase() : DEFAULT_CONDITION,
                            remarks: item.remarks || '',
                            selected: true,
                            serials: {
                                all: normalisedSerials,
                                selected: selectedSerials,
                                expanded: false,
                                search: '',
                            },
                            groups: [],
                            file: null,
                        };
                    });

                return {
                    requestId,
                    request: req.request || req,
                    items,
                };
            })
            .filter((req) => req.items.length);
    }
    async handleNext() {
        const currentStepName = this.steps[this.currentStep];
        if (!this.validateStep(currentStepName)) return false;
        const nextIndex = this.currentStep + 1;
        if (nextIndex >= this.steps.length) return false;
        await this.goToStep(nextIndex);
        return true;
    }

    validateStep(stepName) {
        if (stepName === 'items') {
            if (!this.hasSelectedItems()) {
                this.showToast('info', 'Select at least one item to continue.');
                return false;
            }
        }

        if (stepName === 'serials') {
            for (const request of this.state) {
                for (const item of request.items) {
                    if (!item.selected) continue;

                    if (item.groups.length) {
                        // Iterate through each group for the item
                        item.groups.forEach((group) => {
                            const need = Number(group.qty) || 0;
                            const have = group.serials instanceof Set ? group.serials.size : 0;

                            // If the condition is 'good' and quantity needs serials, but none are selected
                            if (group.condition === 'good' && need > 0 && have !== need) {
                                this.showToast('info', `Select ${need} serial(s) for ${item.name}.`);
                                return false; // Stop if serials are needed
                            }

                            // If the condition is not 'good', allow without serials
                        });
                    } else if (item.serials.all.length && item.serials.selected.size === 0) {
                        // Allow proceeding even without serials if condition is 'damaged' or 'missing'
                        if (item.condition !== 'good') {
                            continue; // Allow proceeding without serials for non-good items
                        }
                        this.showToast('info', `Select at least one serial for ${item.name}.`);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    hasSelectedItems() {
        return this.state.some((req) => req.items.some((item) => item.selected));
    }

    async goToStep(index) {
        if (!this.root) return;
        if (index < 0 || index >= this.steps.length) return;
        this.currentStep = index;
        await this.renderCurrentStep();

        Object.entries(this.stepContainers).forEach(([step, el]) => {
            if (!el) return;
            if (step === this.steps[this.currentStep]) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });

        this.updateProgress();
        this.updateNavButtons();
    }

    async renderCurrentStep() {
        const stepName = this.steps[this.currentStep];
        if (stepName === 'items') {
            this.renderItemsStep();
        } else if (stepName === 'conditions') {
            await this.renderConditionsStep();
        } else if (stepName === 'summary') {
            this.renderSummaryStep();
        }
    }

    updateProgress() {
        const total = this.steps.length;
        if (this.progressBar) {
            const percent = ((this.currentStep + 1) / total) * 100;
            this.progressBar.style.width = `${percent}%`;
        }
        if (this.stepLabel) {
            this.stepLabel.textContent = `Step ${this.currentStep + 1} of ${total}`;
        }
    }

    updateNavButtons() {
        if (this.btnPrev) {
            if (this.currentStep === 0) {
                this.btnPrev.classList.add('hidden');
            } else {
                this.btnPrev.classList.remove('hidden');
            }
        }

        if (this.btnNext) {
            if (this.currentStep >= this.steps.length - 1) {
                this.btnNext.classList.add('hidden');
            } else {
                this.btnNext.classList.remove('hidden');
            }
        }

        if (this.btnSubmit) {
            if (this.currentStep === this.steps.length - 1) {
                this.btnSubmit.classList.remove('hidden');
            } else {
                this.btnSubmit.classList.add('hidden');
            }
        }
    }

    renderItemsStep() {
        const container = this.stepContainers.items;
        if (!container) return;

        const term = this.itemSearchTerm.toLowerCase();

        const blocks = this.state.map((request) => {
            const items = request.items.filter((item) => {
                if (!term) return true;
                const haystack = [item.name, ...item.serials.all.map((serial) => serial.serial || serial.property_number || '')]
                    .join(' ')
                    .toLowerCase();
                return haystack.includes(term);
            });

            if (!items.length) return '';

            const itemRows = items
                .map((item) => {
                    const serialCount = item.serials.all.length ? ` - Serials available: ${item.serials.all.length}` : '';
                    return `
                        <div class="flex items-start justify-between gap-3 py-2" data-modal-item="${item.id}" data-request-id="${request.requestId}">
                            <label class="flex items-start gap-3 text-sm text-gray-700 flex-1 cursor-pointer">
                                <input type="checkbox" class="mt-1" data-item-checkbox ${item.selected ? 'checked' : ''}>
                                <span>
                                    <span class="block font-medium text-gray-900">${escapeHtml(item.name)}</span>
                                    <span class="block text-xs text-gray-500">Qty: ${item.quantity}${serialCount}</span>
                                </span>
                            </label>
                        </div>`;
                })
                .join('');

            const requestMeta = request.request || {};
            const borrower = requestMeta.borrower?.name ? ` - ${escapeHtml(requestMeta.borrower.name)}` : '';
            const schedule = requestMeta.borrow_date || requestMeta.return_date
                ? `<div class="text-xs text-gray-500">${escapeHtml(formatDateRange(requestMeta.borrow_date, requestMeta.return_date))}${borrower}</div>`
                : '';

            return `
                <div class="return-modal-card" data-request-card="${request.requestId}">
                    <div class="return-modal-header">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Request #${escapeHtml(request.requestId)}</div>
                            ${schedule}
                        </div>
                        <div class="flex items-center gap-0">
                            <button type="button" class="text-xs text-purple-600 hover:text-purple-800" data-request-select-all>Select</button>
                            <button type="button" class="text-xs text-gray-500 hover:text-red-700" data-request-clear>Clear</button>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">${itemRows}</div>
                </div>`;
        });

        container.innerHTML = `
            <div class="return-modal-section">
                <div class="return-modal-toolbar">
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50" data-modal-select-all>Select All</button>
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50" data-modal-clear-all>Clear All</button>
                </div>
                ${blocks.join('') || '<div class="return-modal-empty">No items match your search.</div>'}
            </div>`;

        
        container.querySelectorAll('[data-modal-select-all]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.state.forEach((req) => {
                    req.items.forEach((item) => {
                        item.selected = true;
                    });
                });
                this.renderItemsStep();
            });
        });

        container.querySelectorAll('[data-modal-clear-all]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.state.forEach((req) => {
                    req.items.forEach((item) => {
                        item.selected = false;
                    });
                });
                this.renderItemsStep();
            });
        });

        container.querySelectorAll('[data-request-select-all]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const card = event.currentTarget.closest('[data-request-card]');
                if (!card) return;
                const requestId = card.getAttribute('data-request-card');
                const request = this.state.find((req) => req.requestId === requestId);
                if (!request) return;
                request.items.forEach((item) => {
                    if (this.itemMatchesFilter(item)) item.selected = true;
                });
                this.renderItemsStep();
            });
        });

        container.querySelectorAll('[data-request-clear]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const card = event.currentTarget.closest('[data-request-card]');
                if (!card) return;
                const requestId = card.getAttribute('data-request-card');
                const request = this.state.find((req) => req.requestId === requestId);
                if (!request) return;
                request.items.forEach((item) => {
                    if (this.itemMatchesFilter(item)) item.selected = false;
                });
                this.renderItemsStep();
            });
        });

        container.querySelectorAll('[data-modal-item]').forEach((row) => {
            const checkbox = row.querySelector('[data-item-checkbox]');
            if (!checkbox) return;
            checkbox.addEventListener('change', (event) => {
                const requestId = row.getAttribute('data-request-id');
                const itemId = row.getAttribute('data-modal-item');
                const request = this.state.find((req) => req.requestId === requestId);
                if (!request) return;
                const item = request.items.find((itm) => itm.id === itemId);
                if (!item) return;
                item.selected = Boolean(event.currentTarget.checked);
            });
        });
    }

    itemMatchesFilter(item) {
        if (!this.itemSearchTerm) return true;
        const term = this.itemSearchTerm.toLowerCase();
        const haystack = [item.name, ...item.serials.all.map((serial) => serial.serial || serial.property_number || '')]
            .join(' ')
            .toLowerCase();
        return haystack.includes(term);
    }
    async renderConditionsStep() {
        const container = this.stepContainers.conditions;
        if (!container) return;

        // Load serials so search works here
        try { await this.ensureSerialData(); } catch (_) {}

        const ensureGroups = (item) => {
            if (!Array.isArray(item.groups)) item.groups = [];
            item.groups = item.groups.filter((g) => g && typeof g === 'object' && g.condition && g.condition !== 'good');
            item.groups.forEach((g) => {
                if (!(g.serials instanceof Set)) g.serials = new Set();
                if (typeof g.search !== 'string') g.search = '';
                g.qty = Number(g.qty || 0);
            });
        };

        const cards = [];
        this.state.forEach((request) => {
            request.items.forEach((item) => {
                if (!item.selected) return;
                ensureGroups(item);
                const used = item.groups.reduce((acc, g) => acc + (Number(g.qty) || 0), 0);
                const remaining = Math.max(0, Number(item.quantity || 0) - used);

                const allSerials = Array.isArray(item.serials?.all) ? item.serials.all : [];
                const taken = (() => { const s = new Set(); (item.groups||[]).forEach(gr=>{ if (gr.serials instanceof Set) Array.from(gr.serials).forEach(id=>s.add(String(id))); }); return s; })();
                const groupRows = item.groups.map((g, idx) => {
                    const raw = String(g.search || '').trim().toLowerCase();
                    const term = raw;
                    const termSan = raw.replace(/[^a-z0-9]/g, '');
                    const pool = allSerials.filter((s) => !taken.has(String(s.id)) || g.serials.has(String(s.id)));
                    const filtered = pool.filter((s) => {
                        if (!termSan) return false;
                        const hay = `${s.serial ?? ''} ${s.property_number ?? ''}`.toLowerCase();
                        const haySan = hay.replace(/[^a-z0-9]/g, '');
                        return hay.includes(term) || haySan.includes(termSan);
                    }).slice(0, 20);
                    const results = filtered.map((s) => {
    const disabled = g.serials.size >= (Number(g.qty)||0) && !g.serials.has(String(s.id));
    const cls = disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-gray-50';
    const label = s.serial || s.property_number || s.id;
    return `<div class="flex items-center justify-between ${cls} rounded px-2 py-1 text-xs" data-cond-pick="${request.requestId}|${item.id}|${idx}|${s.id}">
                <span class="font-medium text-gray-800">${escapeHtml(label)}</span>
                <span class="text-[10px] text-gray-500">${g.serials.has(String(s.id)) ? 'Selected' : ''}</span>
            </div>`;
}).join('');


                    return `
                        <div class="space-y-1 relative" data-cond-row="${request.requestId}|${item.id}|${idx}">
                            <div class="flex items-center gap-2">
                                <select class="rounded-lg border border-gray-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" data-cond-select>
                                        ${CONDITION_OPTIONS
                                                .filter(o => o.value !== 'good')
                                                .filter(o => !item.groups.some(gr => gr.condition === o.value && gr !== g)) // exclude conditions already used by other groups
                                                .map(option => `<option value="${option.value}" ${option.value === g.condition ? 'selected' : ''}>${option.label}</option>`)
                                                .join('')}                                </select>
                                <input type="number" min="1" class="w-20 rounded-lg border border-gray-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" data-cond-qty value="${Number(g.qty) || 1}">
                                <input type="search" class="w-40 rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Search serial..." data-cond-search value="${escapeHtml(g.search)}">
                                <button type="button" class="rounded-lg border border-gray-200 px-2 py-1.5 text-xs text-gray-600 hover:bg-red-100" data-cond-remove>Remove</button>
                            </div>
<div class="absolute left-0 right-0 mt-1 max-h-48 overflow-auto bg-white rounded-lg shadow z-20 p-1" data-cond-results>
                                ${results}
                            </div>
                        </div>`;
                }).join('');

                const needsDetails = item.groups.length > 0;

                cards.push(`
                    <div class="return-modal-card" data-condition-item="${request.requestId}|${item.id}">
                        <div class="return-modal-header">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">${escapeHtml(item.name)}</div>
                                <div class="text-xs text-gray-500">Request #${escapeHtml(request.requestId)} - Qty: ${item.quantity}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="text-xs text-gray-600">Allocate quantities to conditions (excluding Good). Remaining will be marked as Good.</div>
                            <div class="space-y-2" data-cond-rows>
                                ${groupRows || '<div class="text-xs text-gray-500">No condition groups yet.</div>'}
                            </div>
                            <div class="flex items-center justify-between">
                                <button type="button" class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50" data-cond-add>Add Condition</button>
                                <div class="text-xs ${remaining === 0 ? 'text-green-700' : 'text-gray-600'}">Remaining Good: <strong>${remaining}</strong></div>
                            </div>
                            <div class="space-y-3 ${needsDetails ? '' : 'hidden'}" data-condition-details>
                                <div class="text-xs text-gray-600">
                                    ${item.groups.map((g) => {
                                        const label = (CONDITION_OPTIONS.find(o=>o.value===g.condition)?.label) || g.condition;
                                        const chips = Array.from(g.serials || []).map((sid) => {
                                            const s = (item.serials?.all || []).find((x)=> String(x.id)===String(sid));
                                            const name = s ? (s.serial || s.property_number || sid) : sid;
                                            return `<span class=\"inline-flex items-center gap-1 rounded-full bg-purple-100 text-purple-700 px-2 py-0.5 text-xs mr-1 mb-1\" data-chip=\"${request.requestId}|${item.id}|${g.condition}|${sid}\">${escapeHtml(name)} <button type=\"button\" data-chip-remove class=\"text-purple-700\">&times;</button></span>`;
                                        }).join('');
                                        return `<div class=\"mb-1\"><strong>${escapeHtml(label)}</strong>: <span>${chips || '<span class=\"text-gray-400\">None</span>'}</span></div>`;
                                    }).join('')}
                                </div>
                                <textarea class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" rows="3" placeholder="Add remarks (applies to non-good conditions)" data-condition-remarks>${escapeHtml(item.remarks)}</textarea>
                                <label class="block text-xs text-gray-500">
                                    Upload photo evidence
                                    <input type="file" accept="image/*" class="mt-1 block w-full text-xs" data-condition-file>
                                </label>
                            </div>
                        </div>
                    </div>`);
            });
        });

        container.innerHTML = cards.length
            ? `<div class="return-modal-section">${cards.join('')}</div>`
            : '<div class="return-modal-empty">No items selected. Go back to the previous step to choose items.</div>';

        container.querySelectorAll('[data-condition-item]').forEach((card) => {
            const [requestId, itemId] = card.getAttribute('data-condition-item').split('|');
            const request = this.state.find((req) => req.requestId === requestId);
            if (!request) return;
            const item = request.items.find((itm) => itm.id === itemId);
            if (!item) return;

            const rowsWrap = card.querySelector('[data-cond-rows]');
            const details = card.querySelector('[data-condition-details]');
            const remarks = card.querySelector('[data-condition-remarks]');
            const fileInput = card.querySelector('[data-condition-file]');
            const addBtn = card.querySelector('[data-cond-add]');

            const refresh = () => { this.renderConditionsStep(); };
            const remainingOf = (currentIdx = -1) => {
                const used = item.groups.reduce((acc, g, idx) => acc + (idx === currentIdx ? 0 : (Number(g.qty)||0)), 0);
                return Math.max(0, Number(item.quantity||0) - used);
            };

            addBtn?.addEventListener('click', () => {
                if (!Array.isArray(item.groups)) item.groups = [];
                const rem = remainingOf();
                if (rem <= 0) { this.showToast('info', 'No remaining quantity to allocate.'); return; }
                // Find the first available condition not yet used
                    const available = CONDITION_OPTIONS
                        .filter(o => o.value !== 'good')
                        .find(o => !item.groups.some(gr => gr.condition === o.value));

                    if (!available) {
                        this.showToast('info', 'All available conditions are already added.');
                        return;
                    }

const ng = { condition: available.value, qty: 1, serials: new Set(), search: '', focusSearch: true };
item.groups.push(ng);

                details?.classList.remove('hidden');
                refresh();
            });

            rowsWrap?.querySelectorAll('[data-cond-row]').forEach((row) => {
                const [rqId, itId, idxStr] = row.getAttribute('data-cond-row').split('|');
                const idx = Number(idxStr);
                const g = (item.groups || [])[idx];
                if (!g) return;
                const sel = row.querySelector('[data-cond-select]');
                const qty = row.querySelector('[data-cond-qty]');
                const search = row.querySelector('[data-cond-search]');
                const remove = row.querySelector('[data-cond-remove]');

                sel?.addEventListener('change', (e) => { g.condition = e.target.value; refresh(); });
                qty?.addEventListener('input', (e) => {
                    const max = Math.max(1, remainingOf(idx) + (Number(g.qty)||0));
                    let val = Number(e.target.value || 0);
                    if (!Number.isFinite(val) || val < 1) val = 1;
                    if (val > max) val = max;
                    g.qty = val;
                });
                qty?.addEventListener('change', () => refresh());
                search?.addEventListener('input', (e) => { g.search = e.target.value; if (g._timer) clearTimeout(g._timer); g._timer = setTimeout(() => { g.focusSearch = true; refresh(); }, 120); });
                search?.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const first = row.querySelector('[data-cond-results] [data-cond-pick]');
                        if (first) first.click();
                    }
                });
                if (g.focusSearch && search) {
                    setTimeout(() => { try { search.focus(); } catch(_){} } , 0);
                    g.focusSearch = false;
                }
                remove?.addEventListener('click', () => { item.groups.splice(idx, 1); if (!item.groups.length) details?.classList.add('hidden'); refresh(); });

                // Add serial from results; keep unique across groups
                row.parentElement?.querySelectorAll('[data-cond-results] [data-cond-pick]')?.forEach((el) => {
                    const [rq, it, gIdx, sid] = el.getAttribute('data-cond-pick').split('|');
                    if (String(gIdx) !== String(idx)) return;
                    el.addEventListener('click', () => {
                        const need = Number(g.qty) || 0;
                        if (need && g.serials.size >= need && !g.serials.has(String(sid))) return;
                        (item.groups || []).forEach((og) => { if (og !== g && og.serials instanceof Set) og.serials.delete(String(sid)); });
                        g.serials.add(String(sid));
                        // clear search and keep focus for rapid multiple entry
                        g.search = '';
                        g.focusSearch = true;
                        refresh();
                    });
                });
            });

            remarks?.addEventListener('input', (event) => { item.remarks = event.target.value; });
            fileInput?.addEventListener('change', (event) => { const file = event.target.files?.[0] ?? null; item.file = file; });

            // Chip removal from remarks summary
            card.querySelectorAll('[data-chip]').forEach((chip) => {
                const [rqId, itId, cond, sid] = chip.getAttribute('data-chip').split('|');
                const grp = (item.groups || []).find((gg) => String(gg.condition) === String(cond));
                const btn = chip.querySelector('[data-chip-remove]');
                if (grp && btn) btn.addEventListener('click', () => { grp.serials.delete(String(sid)); grp.focusSearch = true; refresh(); });
            });
        });
    }
    
    async ensureSerialData() {
        if (!this.fetchRequestDetails) return;
        for (const request of this.state) {
            const needsFetch = request.items.some((item) => item.selected && item.serials.all.length === 0);
            if (!needsFetch) continue;
            try {
                const data = await this.fetchRequestDetails(request.requestId);

                // Case 1: top-level borrowed_instances array
                if (Array.isArray(data?.borrowed_instances)) {
                    const byItem = new Map();
                    data.borrowed_instances.forEach((row) => {
                        const itemId = String(row?.item_id ?? row?.item?.id ?? "");
                        if (!itemId) return;
                        if (!byItem.has(itemId)) byItem.set(itemId, []);
                        byItem.get(itemId).push(row);
                    });

                    request.items.forEach((item) => {
                        if (!item.selected || item.serials.all.length) return;
                        const list = byItem.get(String(item.id)) || [];
                        if (!list.length) return;
                        item.serials.all = list.map((r, idx) => {
                            const id = r?.item_instance_id ?? r?.instance_id ?? r?.id ?? r?.serial ?? r?.property_number ?? `${item.id}-${idx}`;
                            return {
                                id: String(id),
                                serial: r?.serial ?? r?.property_number ?? r?.serial_number ?? `Serial ${idx + 1}`,
                                property_number: r?.property_number ?? r?.serial ?? r?.serial_number ?? null,
                            };
                        });
                        if (!Array.isArray(item.groups) || item.groups.length === 0) {
                            if (!item.serials.selected.size) {
                                item.serials.all.forEach((serial) => item.serials.selected.add(String(serial.id)));
                            }
                        }
                    });
                    continue;
                }

                // Case 2: nested arrays inside items/borrowed_items
                const pool = Array.isArray(data?.borrowed_items)
                    ? data.borrowed_items
                    : Array.isArray(data?.items)
                    ? data.items
                    : [];
                request.items.forEach((item) => {
                    if (!item.selected || item.serials.all.length) return;
                    const source = pool.find((entry) => String(entry.item_id ?? entry.id ?? entry?.item?.id ?? "") === String(item.id));
                    if (!source) return;
                    const instances = Array.isArray(source.instances)
                        ? source.instances
                        : Array.isArray(source.borrowed_instances)
                        ? source.borrowed_instances
                        : [];
                    if (!instances.length) return;
                    item.serials.all = instances.map((inst, idx) => {
                        const id = inst?.item_instance_id ?? inst?.id ?? inst?.serial ?? inst?.property_number ?? `${item.id}-${idx}`;
                        return {
                            id: String(id),
                            serial: inst?.serial ?? inst?.property_number ?? inst?.serial_number ?? `Serial ${idx + 1}`,
                            property_number: inst?.property_number ?? inst?.serial ?? inst?.serial_number ?? null,
                        };
                    });
                    if (!Array.isArray(item.groups) || item.groups.length === 0) {
                        if (!item.serials.selected.size) {
                            item.serials.all.forEach((serial) => item.serials.selected.add(String(serial.id)));
                        }
                    }
                });
            } catch (error) {
                console.error("Failed to fetch request details", error);
                this.showToast("error", "Unable to load serial numbers for one of the items.");
            }
        }
    }

    renderSummaryStep() {
        const container = this.stepContainers.summary;
        if (!container) return;

        const sections = [];
        let totalItems = 0;

        this.state.forEach((request) => {
            const selectedItems = request.items.filter((item) => item.selected);
            if (!selectedItems.length) return;

            const totalCount = selectedItems.reduce((acc, item) => acc + item.quantity, 0);  // Calculate total count
            totalItems += totalCount;  // Add to overall totalItems

            const rows = selectedItems
                .map((item) => {
                    // Initialize the condition counts
                    const conditionCount = {
                        good: 0,
                        needs_cleaning: 0,
                        minor_damage: 0,
                        major_damage: 0,
                        missing: 0,
                    };

                    // Count the quantities for each condition
                    item.groups.forEach((group) => {
                        if (group.condition === 'good') {
                            conditionCount.good += group.qty;
                        } else if (group.condition === 'needs_cleaning') {
                            conditionCount.needs_cleaning += group.qty;
                        } else if (group.condition === 'minor_damage') {
                            conditionCount.minor_damage += group.qty;
                        } else if (group.condition === 'major_damage') {
                            conditionCount.major_damage += group.qty;
                        } else if (group.condition === 'missing') {
                            conditionCount.missing += group.qty;
                        }
                    });

                    // Calculate the remaining quantity for Good condition
                    const remainingGood = item.quantity - (conditionCount.needs_cleaning + conditionCount.minor_damage + conditionCount.major_damage + conditionCount.missing);
                    if (remainingGood > 0) {
                        conditionCount.good = remainingGood;
                    }

                    // Build the condition preview text
                    const conditionPreviewParts = [];
                    if (conditionCount.good > 0) {
                        conditionPreviewParts.push(`Good - ${conditionCount.good}`);
                    }
                    if (conditionCount.needs_cleaning > 0) {
                        conditionPreviewParts.push(`Needs Cleaning - ${conditionCount.needs_cleaning}`);
                    }
                    if (conditionCount.minor_damage > 0) {
                        conditionPreviewParts.push(`Minor Damage - ${conditionCount.minor_damage}`);
                    }
                    if (conditionCount.major_damage > 0) {
                        conditionPreviewParts.push(`Major Damage - ${conditionCount.major_damage}`);
                    }
                    if (conditionCount.missing > 0) {
                        conditionPreviewParts.push(`Missing - ${conditionCount.missing}`);
                    }
                    const conditionPreview = `Condition: ${conditionPreviewParts.join(' ')}`;


                    // Handle serials (show N/A if no serials are selected)
                    const serials = item.serials.all.length ? Array.from(item.serials.selected) : [];
                    const serialPreview = serials.length
                        ? `<div class="text-xs text-gray-500">Serials: ${escapeHtml(serials.slice(0, 5).join(', '))}${serials.length > 5 ? '...' : ''}</div>`
                        : `<div class="text-xs text-gray-500">Serials: N/A</div>`;

                    return `
                        <div class="return-modal-summary-row">
                            <div>
                                <strong>${escapeHtml(item.name)}</strong>
                                <div class="text-xs text-gray-500">${conditionPreview}</div>
                                ${serialPreview}
                            </div>
                            <div class="return-modal-pill">${item.quantity} unit(s)</div>
                        </div>`;
                })
                .join('');

            const requestMeta = request.request || {};
            const borrower = requestMeta.borrower?.name ? ` - ${escapeHtml(requestMeta.borrower.name)}` : '';

            sections.push(`
                <div class="return-modal-summary-card">
                    <div class="text-sm font-semibold text-gray-900">Request #${escapeHtml(request.requestId)}${borrower}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(formatDateRange(requestMeta.borrow_date, requestMeta.return_date))}</div>
                    <div class="return-modal-summary-list">${rows}</div>
                </div>`);
        });

        container.innerHTML = sections.length
            ? `<div class="return-modal-summary">${sections.join('')}</div>`
            : '<div class="return-modal-empty">No items selected. Go back and choose at least one item.</div>';

        // Update the total count of items at the bottom
        const totalCountElement = container.querySelector('#totalCount');
        
        if (totalCountElement) {
            totalCountElement.textContent = `${totalItems} unit(s)`;
        }
    }


    setLoading(state) {
        this.isSubmitting = state;
        const disableTargets = [this.btnNext, this.btnPrev, this.btnSubmit, this.btnCancel, this.btnClose];
        disableTargets.forEach((btn) => {
            if (!btn) return;
            btn.disabled = state;
            btn.classList.toggle('opacity-50', state);
            btn.classList.toggle('cursor-not-allowed', state);
        });
    }

    async handleSubmit() {
        if (!this.validateStep('serials')) return;
        const formData = this.buildFormData();
        if (!formData) {
            this.showToast('error', 'Unable to build return request payload.');
            return;
        }

        this.setLoading(true);
        try {
            const response = await fetch(this.submitRoute, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (error) {
                payload = null;
            }

            if (!response.ok) {
                const message = payload?.message || payload?.error || `Failed to submit return (${response.status}).`;
                throw new Error(message);
            }

            const processed = payload?.processed_ids || payload?.created_ids || payload?.ids || [];
            if (processed.length) {
                this.showToast('success', `Return submitted for ${processed.length} request${processed.length === 1 ? '' : 's'}.`);
            } else {
                this.showToast('success', payload?.message || 'Return request submitted.');
            }

            this.onSubmitSuccess(processed);
            this.close();
        } catch (error) {
            console.error(error);
            this.showToast('error', error.message || 'Failed to submit return request.');
        } finally {
            this.setLoading(false);
        }
    }

    buildFormData() {
        if (!this.state.length) return null;
        const formData = new FormData();
        if (this.csrfToken) formData.append('_token', this.csrfToken);

        let requestIndex = 0;
        this.state.forEach((request) => {
            const selectedItems = request.items.filter((item) => item.selected);
            if (!selectedItems.length) return;
            formData.append(`borrow_requests[${requestIndex}][borrow_request_id]`, request.requestId);

            selectedItems.forEach((item, itemIndex) => {
                // If multi-condition groups exist, send separate rows per selected serial, per group
                if (Array.isArray(item.groups) && item.groups.length) {
                    let offset = 0;
                    item.groups.forEach((g) => {
                        const serialIds = (g.serials instanceof Set) ? Array.from(g.serials) : [];
                        if (serialIds.length) {
                            serialIds.forEach((sid) => {
                                const base = `borrow_requests[${requestIndex}][items][${itemIndex + offset}]`;
                                formData.append(`${base}[item_id]`, item.id);
                                formData.append(`${base}[condition]`, g.condition || 'good');
                                formData.append(`${base}[item_instance_id]`, sid);
                                formData.append(`${base}[quantity]`, '1');
                                if (item.remarks) formData.append(`${base}[remarks]`, item.remarks);
                                if (item.file instanceof File) formData.append(`${base}[photo]`, item.file);
                                offset += 1;
                            });
                        } else if (Number(g.qty) > 0) {
                            const base = `borrow_requests[${requestIndex}][items][${itemIndex + offset}]`;
                            formData.append(`${base}[item_id]`, item.id);
                            formData.append(`${base}[condition]`, g.condition || 'good');
                            formData.append(`${base}[quantity]`, String(Number(g.qty)));
                            if (item.remarks) formData.append(`${base}[remarks]`, item.remarks);
                            if (item.file instanceof File) formData.append(`${base}[photo]`, item.file);
                            offset += 1;
                        }
                    });
                } else {
                    // Fallback: if specific serials selected, send one row per instance; else send quantity row
                    const serialIds = item.serials.all.length ? Array.from(item.serials.selected) : [];
                    if (serialIds.length) {
                        let offset = 0;
                        serialIds.forEach((sid) => {
                            const base = `borrow_requests[${requestIndex}][items][${itemIndex + offset}]`;
                            formData.append(`${base}[item_id]`, item.id);
                            formData.append(`${base}[condition]`, item.condition);
                            formData.append(`${base}[item_instance_id]`, sid);
                            formData.append(`${base}[quantity]`, '1');
                            if (item.remarks) formData.append(`${base}[remarks]`, item.remarks);
                            if (item.file instanceof File) formData.append(`${base}[photo]`, item.file);
                            offset += 1;
                        });
                    } else {
                        const base = `borrow_requests[${requestIndex}][items][${itemIndex}]`;
                        formData.append(`${base}[item_id]`, item.id);
                        formData.append(`${base}[condition]`, item.condition);
                        formData.append(`${base}[quantity]`, String(item.quantity));
                        if (item.remarks) formData.append(`${base}[remarks]`, item.remarks);
                        if (item.file instanceof File) formData.append(`${base}[photo]`, item.file);
                    }
                }
            });

            requestIndex += 1;
        });

        if (requestIndex === 0) return null;
        return formData;
    }
}