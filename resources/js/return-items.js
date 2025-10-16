import ReturnSelectedItemsModal from './return-selected-items';

const LIST_ROUTE = window.RETURN_LIST_ROUTE || '/user/return-items/list';
const SUBMIT_ROUTE = window.RETURN_SUBMIT_ROUTE || '/user/return-items/request';
const CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector("meta[name='csrf-token']")?.getAttribute('content') || '';
const BORROW_SHOW_BASE = window.BORROW_SHOW_BASE || '/user/my-borrowed-items';

const PAGE_SIZE = 9;

const CONDITION_COLORS = {
    good: 'bg-green-100 text-green-800',
    needs_cleaning: 'bg-yellow-100 text-yellow-700',
    minor_damage: 'bg-orange-100 text-orange-700',
    major_damage: 'bg-red-100 text-red-700',
    missing: 'bg-gray-200 text-gray-700',
};

const CONDITION_LABELS = {
    good: 'Good',
    needs_cleaning: 'Needs Cleaning',
    minor_damage: 'Minor Damage',
    major_damage: 'Major Damage',
    missing: 'Missing',
};

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
    ? window.showToast.bind(window)
    : (type, message) => {
        const fallback = message || '';
        console[type === 'error' ? 'error' : 'log'](fallback);
    };
function formatDateRange(borrowDate, returnDate) {
    const format = (input) => {
        if (!input) return 'N/A';
        const d = new Date(input);
        if (Number.isNaN(d.valueOf())) return input;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    };
    return `${format(borrowDate)} - ${format(returnDate)}`;
}

function isOverdue(request) {
    if (!request?.return_date) return false;
    const due = new Date(request.return_date);
    if (Number.isNaN(due.valueOf())) return false;
    const now = new Date();
    return now > due;
}

function dueWithin(request, days) {
    if (!request?.return_date) return false;
    const due = new Date(request.return_date);
    if (Number.isNaN(due.valueOf())) return false;
    const now = new Date();
    const diff = (due - now) / (1000 * 60 * 60 * 24);
    return diff >= 0 && diff <= days;
}
class ReturnItemsPage {
    constructor() {
        this.requests = [];
        this.filtered = [];
        this.page = 1;
        this.searchTerm = '';
        this.dueFilter = 'all';
        this.searchIndex = [];
        this.focusedRequestId = null;
        this.selectedRequests = new Map();
        this.requestDetailsCache = new Map();
        this.sentinelObserver = null;

        this.elements = {
            root: document.getElementById('returnItemsApp'),
            grid: document.getElementById('returnItemsGrid'),
            sentinel: document.getElementById('returnListSentinel'),
            loadMore: document.getElementById('loadMoreBtn'),
            totalCount: document.getElementById('totalCount'),
            search: document.getElementById('returnSearch'),
            suggestions: document.getElementById('returnSearchSuggestions'),
            clearSearch: document.getElementById('clearSearch'),
            filterDue: document.getElementById('filterDue'),
            selectVisible: document.getElementById('selectVisibleBtn'),
            fab: document.getElementById('fabReturn'),
            fabLabel: document.getElementById('fabLabel'),
            fabCount: document.getElementById('fabCount'),
            previewPanel: document.getElementById('previewPanel'),
            previewBadge: document.getElementById('previewBadge'),
            previewContent: document.getElementById('previewContent'),
            previewEmpty: document.getElementById('previewEmpty'),
            previewRequestId: document.getElementById('previewRequestId'),
            previewDates: document.getElementById('previewDates'),
            previewBorrower: document.getElementById('previewBorrower'),
            previewItems: document.getElementById('previewItems'),
        };

        this.wizard = new ReturnSelectedItemsModal({
            modalName: 'return-selected-items',
            submitRoute: SUBMIT_ROUTE,
            csrfToken: CSRF_TOKEN,
            showToast,
            onSubmitSuccess: (ids) => this.handleSubmitSuccess(ids),
            fetchRequestDetails: (id) => this.fetchRequestDetails(id),
        });
    }

    async init() {
        this.bindEvents();
        await this.loadRequests();
        this.setupIntersectionObserver();
    }

    bindEvents() {
        this.elements.search?.addEventListener('input', (event) => {
            this.searchTerm = event.target.value.trim();
            this.updateSuggestions();
            this.applyFilters();
        });

        this.elements.clearSearch?.addEventListener('click', () => {
            if (!this.elements.search) return;
            this.elements.search.value = '';
            this.searchTerm = '';
            this.hideSuggestions();
            this.applyFilters();
        });

        this.elements.filterDue?.addEventListener('change', (event) => {
            this.dueFilter = event.target.value;
            this.applyFilters();
        });

        this.elements.selectVisible?.addEventListener('click', () => {
            this.selectVisibleRequests();
        });

        this.elements.loadMore?.addEventListener('click', () => {
            if (this.page * PAGE_SIZE < this.filtered.length) {
                this.page += 1;
                this.renderList();
            }
        });

        this.elements.fab?.addEventListener('click', () => {
            if (!this.selectedRequests.size) {
                showToast('info', 'Select at least one request to return.');
                return;
            }
            this.wizard.open(this.cloneSelectionState());
        }); 
    }
    
    async loadRequests() {
        const grid = this.elements.grid;
        if (grid) {
            grid.innerHTML = '<div class="rounded-xl border border-dashed border-gray-200 p-6 text-sm text-gray-500 bg-gray-50">Loading borrowed items...</div>';
        }

        try {
            const response = await fetch(LIST_ROUTE, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error(`Failed to load (${response.status})`);
            const data = await response.json();
            const list = Array.isArray(data) ? data : Array.isArray(data?.data) ? data.data : [];
            this.requests = list
            .filter((req) => {
                const st = String(req.status || '').toLowerCase();
                const ds = String(req.delivery_status || '').toLowerCase();
                return st === 'approved' && (ds === 'dispatched' || ds === 'delivered');
            })
            .map((req) => this.normalizeRequest(req));

            this.buildSearchIndex();
            this.applyFilters();
        } catch (error) {
            console.error(error);
            if (grid) {
                grid.innerHTML = '<div class="rounded-xl border border-dashed border-red-200 p-6 text-sm text-red-600 bg-red-50">Failed to load borrowed items.</div>';
            }
        }
    }

    normalizeRequest(request) {
        const items = Array.isArray(request.items) ? request.items : [];
        return {
            ...request,
            items: items.map((item, index) => this.normalizeItem(item, index)),
        };
    }

    normalizeItem(item, fallbackIndex) {
        const instances = Array.isArray(item.instances)
            ? item.instances
            : Array.isArray(item.borrowed_instances)
            ? item.borrowed_instances
            : [];
        const serials = instances.map((inst, idx) => {
            const id = inst?.id ?? inst?.item_instance_id ?? inst?.serial ?? inst?.property_number ?? `${fallbackIndex}-${idx}`;
            return {
                id: String(id),
                serial: inst?.serial ?? inst?.property_number ?? inst?.serial_number ?? `Serial ${idx + 1}`,
                property_number: inst?.property_number ?? inst?.serial ?? inst?.serial_number ?? null,
            };
        });

        return {
            id: String(item.item_id ?? item.id ?? fallbackIndex),
            name: item.item?.name ?? item.name ?? 'Item',
            quantity: Number((item.quantity ?? item.total_quantity ?? serials.length) ?? 0),
            condition: String(item.condition || 'good').toLowerCase(),
            serials,
        };
    }

    buildSearchIndex() {
        const terms = new Set();
        this.requests.forEach((req) => {
            const requestNo = String(req.code || req.reference || req.id || '').trim();
            if (requestNo) terms.add(requestNo);
            if (req.borrower?.name) terms.add(req.borrower.name);
            (req.items || []).forEach((item) => {
                if (item.name) terms.add(item.name.toLowerCase());
                item.serials.forEach((serial) => {
                    if (serial.serial) terms.add(serial.serial);
                    if (serial.property_number) terms.add(serial.property_number);
                });
            });
        });
        this.searchIndex = Array.from(terms).slice(0, 200);
    }

    updateSuggestions() {
        const list = this.elements.suggestions;
        if (!list) return;
        const term = this.searchTerm.toLowerCase();
        if (!term) {
            this.hideSuggestions();
            return;
        }
        const matches = this.searchIndex
            .filter((entry) => String(entry).toLowerCase().includes(term))
            .slice(0, 6);
        if (!matches.length) {
            this.hideSuggestions();
            return;
        }
        list.innerHTML = matches
            .map((entry) => `<button type="button" class="w-full px-4 py-2 text-left text-sm hover:bg-purple-50" data-suggestion="${entry}">${entry}</button>`)
            .join('');
        list.classList.remove('hidden');
        list.querySelectorAll('[data-suggestion]').forEach((button) => {
            button.addEventListener('click', () => {
                if (this.elements.search) this.elements.search.value = button.dataset.suggestion || '';
                this.searchTerm = button.dataset.suggestion || '';
                this.hideSuggestions();
                this.applyFilters();
            });
        });
    }

    hideSuggestions() {
        if (this.elements.suggestions) {
            this.elements.suggestions.classList.add('hidden');
            this.elements.suggestions.innerHTML = '';
        }
    }

    applyFilters() {
        const term = this.searchTerm.toLowerCase();
        this.filtered = this.requests.filter((req) => {
            const matchesTerm = term ? this.matchesTerm(req, term) : true;
            let matchesDue = true;
            if (this.dueFilter === 'due_3') matchesDue = dueWithin(req, 3);
            if (this.dueFilter === 'overdue') matchesDue = isOverdue(req);
            return matchesTerm && matchesDue;
        });
        this.page = 1;
        this.renderList();
        this.updateCounts();
        this.updatePreview();
        this.updateFab();
    }

    matchesTerm(request, term) {
        const requestNo = String(request.code || request.reference || request.id || '').toLowerCase();
        if (requestNo.includes(term)) return true;
        if (request.borrower?.name && request.borrower.name.toLowerCase().includes(term)) return true;
        return (request.items || []).some((item) => {
            if (item.name.toLowerCase().includes(term)) return true;
            return item.serials.some((serial) => {
                const pn = serial.serial || serial.property_number;
                return pn ? pn.toLowerCase().includes(term) : false;
            });
        });
    }
    renderList() {
        const grid = this.elements.grid;
        if (!grid) return;
        grid.innerHTML = '';

        const slice = this.filtered.slice(0, this.page * PAGE_SIZE);
        if (!slice.length) {
            grid.innerHTML = '<div class="rounded-xl border border-dashed border-gray-200 p-6 text-sm text-gray-500 bg-gray-50">No borrow requests match your filters.</div>';
            return;
        }

        slice.forEach((request) => {
            grid.appendChild(this.buildRequestCard(request));
        });

        if (this.page * PAGE_SIZE < this.filtered.length) {
            this.elements.loadMore?.classList.remove('hidden');
        } else {
            this.elements.loadMore?.classList.add('hidden');
        }
    }

    buildRequestCard(request) {
        const card = document.createElement('article');
        card.className = 'group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-purple-300 hover:shadow-lg cursor-pointer';
        card.dataset.requestId = String(request.id);

        const header = document.createElement('div');
        header.className = 'flex items-start justify-between gap-3';

        const titleWrap = document.createElement('div');
        titleWrap.className = 'space-y-1';
        const title = document.createElement('div');
        title.className = 'text-sm font-semibold text-gray-900';
        title.textContent = `Request #${request.id}`;
        const dates = document.createElement('div');
        dates.className = 'text-xs text-gray-500';
        dates.textContent = formatDateRange(request.borrow_date, request.return_date);
        titleWrap.appendChild(title);
        titleWrap.appendChild(dates);

        const statusWrap = document.createElement('div');
        statusWrap.className = 'text-right text-xs text-gray-500 space-y-1';
        if (isOverdue(request)) {
            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700';
            badge.textContent = 'Overdue';
            statusWrap.appendChild(badge);
        } else if (dueWithin(request, 7)) {
            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-[11px] font-semibold text-yellow-700';
            statusWrap.appendChild(badge);
        }
        if (request.borrower?.name) {
            const borrower = document.createElement('div');
            borrower.textContent = request.borrower.name;
            statusWrap.appendChild(borrower);
        }

        header.appendChild(titleWrap);
        header.appendChild(statusWrap);
        card.appendChild(header);

        const itemsList = document.createElement('ul');
        itemsList.className = 'mt-4 space-y-2 text-sm';
        (request.items || []).forEach((item) => {
            const li = document.createElement('li');
            const serialPreview = item.serials.length
                ? item.serials.slice(0, 2).map((serial) => serial.property_number || serial.serial).join(', ') + (item.serials.length > 2 ? '?' : '')
                : '';
            li.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-gray-800">${item.name}</div>
                        <div class="text-xs text-gray-500">Qty: ${item.quantity}${serialPreview ? ` - Serials: ${serialPreview}` : ''}</div>
                    </div>
                </div>`;
            itemsList.appendChild(li);
        });
        card.appendChild(itemsList);

        const action = document.createElement('div');
        action.className = 'mt-4 flex items-center justify-between text-xs text-gray-500';
        action.innerHTML = `
            <div>${(request.items || []).length} item(s)</div>
            <div class="flex items-center gap-2">
                <span class="select-indicator hidden text-purple-600 font-semibold">Selected</span>
                <input type="checkbox" class="rounded border-gray-300">
            </div>`;
        card.appendChild(action);

        const checkbox = action.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('click', (event) => {
            event.stopPropagation();
            this.toggleSelection(request.id, checkbox.checked);
        });

        card.addEventListener('click', () => {
            const alreadySelected = this.selectedRequests.has(String(request.id));
            this.toggleSelection(request.id, !alreadySelected);
        });

        if (this.selectedRequests.has(String(request.id))) {
            card.classList.add('ring-2', 'ring-purple-500', 'shadow-lg');
            action.querySelector('.select-indicator')?.classList.remove('hidden');
            checkbox.checked = true;
        }

        return card;
    }
    toggleSelection(requestId, shouldSelect) {
        const id = String(requestId);
        const request = this.requests.find((req) => String(req.id) === id);
        if (!request) return;

        if (shouldSelect) {
            if (!this.selectedRequests.has(id)) {
                this.selectedRequests.set(id, this.createSelectionEntry(request));
            }
            this.focusedRequestId = id;
        } else {
            this.selectedRequests.delete(id);
            if (this.focusedRequestId === id) {
                this.focusedRequestId = this.selectedRequests.keys().next().value || null;
            }
        }

        this.renderList();
        this.updatePreview();
        this.updateFab();
    }

    createSelectionEntry(request) {
        const items = new Map();
        (request.items || []).forEach((item) => {
            const serialSet = new Set(item.serials.map((serial) => String(serial.id)));
            items.set(String(item.id), {
                id: String(item.id),
                name: item.name,
                quantity: item.quantity,
                condition: 'good',
                remarks: '',
                selected: true,
                serials: {
                    all: item.serials,
                    selected: serialSet,
                },
                file: null,
            });
        });

        return {
            request,
            items,
        };
    }

    selectVisibleRequests() {
        const slice = this.filtered.slice(0, this.page * PAGE_SIZE);
        slice.forEach((request) => {
            const id = String(request.id);
            if (!this.selectedRequests.has(id)) {
                this.selectedRequests.set(id, this.createSelectionEntry(request));
            }
        });
        if (slice.length) {
            this.focusedRequestId = String(slice[0].id);
        }
        this.renderList();
        this.updatePreview();
        this.updateFab();
    }

    updateCounts() {
        if (this.elements.totalCount) {
            this.elements.totalCount.textContent = String(this.filtered.length);
        }
    }

    updateFab() {
        const fab = this.elements.fab;
        if (!fab) return;
        const count = this.getSelectedItemCount();
        if (count > 0) {
            fab.classList.remove('hidden');
            if (this.elements.fabLabel) this.elements.fabLabel.textContent = `Return ${count} item${count === 1 ? '' : 's'}`;
            if (this.elements.fabCount) this.elements.fabCount.textContent = String(count);
        } else {
            fab.classList.add('hidden');
        }
    }

    getSelectedItemCount() {
        let total = 0;
        this.selectedRequests.forEach((entry) => {
            entry.items.forEach((itemState) => {
                if (itemState.selected) total += 1;
            });
        });
        return total;
    }

    updatePreview() {
        const panel = this.elements.previewPanel;
        const badge = this.elements.previewBadge;
        const content = this.elements.previewContent;
        const empty = this.elements.previewEmpty;
        const itemsContainer = this.elements.previewItems;

        if (!panel || !badge || !content || !empty || !itemsContainer) return;

        if (!this.selectedRequests.size) {
            badge.textContent = 'No selection';
            content.classList.add('hidden');
            empty.classList.remove('hidden');
            itemsContainer.innerHTML = '';
            return;
        }

        const id = this.focusedRequestId || Array.from(this.selectedRequests.keys())[0];
        this.focusedRequestId = id;
        const entry = this.selectedRequests.get(id);
        if (!entry) return;

        badge.textContent = `Request #${id}`;
        empty.classList.add('hidden');
        content.classList.remove('hidden');

        if (this.elements.previewRequestId) this.elements.previewRequestId.textContent = `#${id}`;
        if (this.elements.previewDates) this.elements.previewDates.textContent = formatDateRange(entry.request.borrow_date, entry.request.return_date);
        if (this.elements.previewBorrower) this.elements.previewBorrower.textContent = entry.request.borrower?.name || '';

        itemsContainer.innerHTML = '';
        entry.items.forEach((itemState, itemId) => {
            const li = document.createElement('li');
            li.className = 'flex items-start justify-between gap-3';
            const count = itemState.serials.all.length ? itemState.serials.selected.size : itemState.quantity;
            const selectedBadge = itemState.selected ? 'text-purple-600 font-semibold' : 'text-gray-400';
            li.innerHTML = `
                <div>
                    <div class="${selectedBadge}">${itemState.name}</div>
                    <div class="text-xs text-gray-500">Qty: ${itemState.quantity} - Selected: ${count}</div>
                </div>
                <button type="button" class="text-xs text-purple-600 hover:text-purple-800" data-preview-toggle="${itemId}">
                    ${itemState.selected ? 'Deselect' : 'Select'}
                </button>`;
            itemsContainer.appendChild(li);
        });

        itemsContainer.querySelectorAll('[data-preview-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const itemId = button.getAttribute('data-preview-toggle');
                const entry = this.selectedRequests.get(this.focusedRequestId);
                if (!entry) return;
                const itemState = entry.items.get(itemId);
                if (!itemState) return;
                itemState.selected = !itemState.selected;
                this.renderList();
                this.updatePreview();
                this.updateFab();
            });
        });
    }
    setupIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;
        if (!this.elements.sentinel) return;
        this.sentinelObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && this.page * PAGE_SIZE < this.filtered.length) {
                    this.page += 1;
                    this.renderList();
                }
            });
        }, { rootMargin: '120px' });
        this.sentinelObserver.observe(this.elements.sentinel);
    }

    cloneSelectionState() {
        const cloned = [];
        this.selectedRequests.forEach((entry, requestId) => {
            const items = [];
            entry.items.forEach((itemState) => {
                if (!itemState.selected) return;
                items.push({
                    itemId: itemState.id,
                    name: itemState.name,
                    quantity: itemState.quantity,
                    condition: itemState.condition,
                    remarks: itemState.remarks,
                    serials: Array.from(itemState.serials.selected),
                    allSerials: itemState.serials.all,
                    selected: true,
                });
            });
            if (items.length) {
                cloned.push({
                    requestId,
                    request: entry.request,
                    items,
                });
            }
        });
        return cloned;
    }

    handleSubmitSuccess(processedIds = []) {
        if (!Array.isArray(processedIds)) processedIds = [];
        if (processedIds.length) {
            processedIds.forEach((id) => {
                this.requests = this.requests.filter((req) => String(req.id) !== String(id));
                this.filtered = this.filtered.filter((req) => String(req.id) !== String(id));
                this.selectedRequests.delete(String(id));
            });
        }
        this.applyFilters();
    }

    async fetchRequestDetails(requestId) {
        const id = String(requestId);
        if (this.requestDetailsCache.has(id)) {
            return this.requestDetailsCache.get(id);
        }
        try {
            const response = await fetch(`${BORROW_SHOW_BASE}/${id}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Failed to load request details');
            const data = await response.json();
            this.requestDetailsCache.set(id, data);
            return data;
        } catch (error) {
            console.error(error);
            throw error;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const page = new ReturnItemsPage();
    page.init();
});


