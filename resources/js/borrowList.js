// resources/js/borrowList.js
let selectedBorrowDates = []; // still used as computed highlighted date list
let borrowMonth = new Date().getMonth();
let borrowYear = new Date().getFullYear();
let blockedBorrowDates = [];

// Expose to window for external access
window.borrowMonth = borrowMonth;
window.borrowYear = borrowYear;
const MAX_BORROW_DAYS = 3; // inclusive max days

// Use neutral for available, blue for borrow, amber for return, gray for range, red for booked
const DAY_CLASS_BASE = 'relative flex h-12 items-center justify-center rounded-lg border text-sm font-medium transition select-none';
const DAY_STATE_CLASSES = {
    available: 'bg-green-100 border-green-300 text-green-900 hover:bg-green-50 cursor-pointer focus:outline-none focus:ring-1 focus:ring-green-300',
    blocked: 'bg-red-100 border-red-300 text-red-700 cursor-not-allowed opacity-60 line-through',
    past: 'bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed',
    borrow: 'bg-blue-100 border-blue-300 text-blue-900 ring-2 ring-blue-400 cursor-pointer',
    return: 'bg-amber-100 border-amber-300 text-amber-900 ring-2 ring-amber-400 cursor-pointer',
    range: 'bg-gray-100 border-gray-300 text-gray-900 cursor-pointer',
    inactive: 'bg-white border-transparent text-gray-300 cursor-not-allowed'
};

function composeDayClass(state) {
    const mapping = DAY_STATE_CLASSES[state] || DAY_STATE_CLASSES.inactive;
    return `${DAY_CLASS_BASE} ${mapping}`;
}

function applyDayState(el, state) {
    const resolvedState = state || el.dataset.baseState || 'inactive';
    el.className = composeDayClass(resolvedState);
    const interactiveStates = ['available', 'borrow', 'return', 'range'];
    el.disabled = !interactiveStates.includes(resolvedState);
}

/* ---------- Helpers (unchanged) ---------- */
function parseYMD(dateStr) {
    const parts = (dateStr || '').split('-').map(Number);
    if (parts.length !== 3) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
}
function ymdFromDate(dt) {
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}
function formatLong(dateStr) {
    const dt = parseYMD(dateStr);
    if (!dt) return '';
    return dt.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
}

function formatUsageRange(startValue, endValue) {
    const toLabel = (value) => {
        if (!value) return '--';
        const [hour, minute] = value.split(':').map(Number);
        const date = new Date();
        date.setHours(hour, minute, 0, 0);
        return date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit' });
    };
    return `${toLabel(startValue)} - ${toLabel(endValue)}`;
}
function daysDiffInclusive(startStr, endStr) {
    const s = parseYMD(startStr), e = parseYMD(endStr);
    if (!s || !e) return 0;
    const diffMs = e.getTime() - s.getTime();
    return Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
}
function datesBetweenYmd(startStr, endStr) {
    const start = parseYMD(startStr);
    const end = parseYMD(endStr);
    const arr = [];
    if (!start || !end || start > end) return arr;
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        arr.push(ymdFromDate(new Date(d)));
    }
    return arr;
}
function addDaysYmd(dateStr, n) {
    const dt = parseYMD(dateStr);
    if (!dt) return null;
    dt.setDate(dt.getDate() + n);
    return ymdFromDate(dt);
}

/* Get item id from first form in list. Handles trailing slashes */
function getFirstItemIdFromList() {
    const firstItemForm = document.querySelector('#borrowListItems form');
    if (!firstItemForm) return null;
    const action = firstItemForm.getAttribute('action') || '';
    const parts = action.split('/').filter(Boolean);
    return parts.length ? parts[parts.length - 1] : null;
}

/* ---------- New selection state ---------- */
// `borrowPick` is the first selected date (string yyyy-mm-dd) or '' if none.
// `returnPick` is the second selected date or '' if none.
let borrowPick = '';
let returnPick = '';

/* Clear selection (both borrow and return) */
window.clearBorrowSelection = function() {
    borrowPick = '';
    returnPick = '';
    selectedBorrowDates = [];

    const usageBorrowDisplay = document.getElementById('usageBorrowDisplay');
    const usageReturnDisplay = document.getElementById('usageReturnDisplay');
    if (usageBorrowDisplay) usageBorrowDisplay.textContent = 'Select on calendar';
    if (usageReturnDisplay) usageReturnDisplay.textContent = 'Select on calendar';

    const days = document.querySelectorAll('#borrowAvailabilityCalendar [data-date]');
    days.forEach((day) => {
        applyDayState(day, day.dataset.baseState);
    });

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');
    if (borrowHidden) borrowHidden.value = '';
    if (returnHidden) returnHidden.value = '';
    if (borrowDisplay) borrowDisplay.textContent = '--';
    if (returnDisplay) returnDisplay.textContent = '--';

    // Update read-only inputs on the Item Usage card
    const borrowInput = document.getElementById('borrow_date_display_input');
    const returnInput = document.getElementById('return_date_display_input');
    if (borrowInput) borrowInput.value = 'Select on calendar';
    if (returnInput) returnInput.value = 'Select on calendar';

    const datesLine = document.getElementById('currentSelectionDates');
    if (datesLine) datesLine.textContent = '';

    window.dispatchEvent(new CustomEvent('borrow:dates-updated', {
        detail: { borrow: null, return: null }
    }));
};

window.changeBorrowMonth = function(step) {
    borrowMonth += step;
    if (borrowMonth > 11) { borrowMonth = 0; borrowYear++; }
    if (borrowMonth < 0)  { borrowMonth = 11; borrowYear--; }
    
    // Update window references
    window.borrowMonth = borrowMonth;
    window.borrowYear = borrowYear;

    // Always use loadBorrowCalendar which now handles multiple items
    window.loadBorrowCalendar(null, borrowMonth, borrowYear);
};

window.loadBorrowCalendar = function loadBorrowCalendar(itemId, month, year) {
    // Collect all items from the borrow list with their quantities
    const itemEntries = document.querySelectorAll('[data-item-entry]');
    const items = [];
    
    itemEntries.forEach(entry => {
        const itemId = entry.dataset.itemId;
        const quantity = parseInt(entry.dataset.itemQuantity || entry.querySelector('.borrow-quantity-input')?.value || '0', 10);
        if (itemId && quantity > 0) {
            items.push({
                item_id: parseInt(itemId, 10),
                quantity: quantity
            });
        }
    });

    if (items.length === 0) {
        blockedBorrowDates = [];
        renderBorrowCalendar(month, year);
        return;
    }

    // Use the new multiple items availability endpoint
    const itemsJson = encodeURIComponent(JSON.stringify(items));
    fetch(`/user/availability?items=${itemsJson}`)
        .then(res => {
            if (!res.ok) throw new Error('Network response not ok');
            return res.json();
        })
        .then(blockedDates => {
            blockedBorrowDates = Array.isArray(blockedDates) ? blockedDates : [];
            renderBorrowCalendar(month, year);
        })
        .catch(err => {
            blockedBorrowDates = [];
            renderBorrowCalendar(month, year);
            console.error('Failed to load availability', err);
        });
};

function renderBorrowCalendar(month, year) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const monthStart = new Date(year, month, 1);
    const monthEnd = new Date(year, month + 1, 0);

    const monthTitleEl = document.getElementById('borrowCalendarMonth');
    if (monthTitleEl) {
        monthTitleEl.textContent = `${monthStart.toLocaleString('default', { month: 'long' })} ${year}`;
    }

    const container = document.getElementById('borrowAvailabilityCalendar');
    if (!container) return;
    container.innerHTML = '';

    const leadingBlank = monthStart.getDay();
    for (let i = 0; i < leadingBlank; i++) {
        const blank = document.createElement('div');
        blank.className = 'h-12';
        container.appendChild(blank);
        }

    for (let day = 1; day <= monthEnd.getDate(); day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dateObj = parseYMD(dateStr);
        const isBlocked = blockedBorrowDates.includes(dateStr);
        const isPast = dateObj < today;

        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = day;
        button.dataset.date = dateStr;

        let baseState = 'available';
        if (isBlocked) {
            baseState = 'blocked';
        } else if (isPast) {
            baseState = 'past';
        }
        button.dataset.baseState = baseState;
        applyDayState(button, baseState);

        if (baseState === 'available') {
            button.addEventListener('click', () => handleCalendarClick(dateStr, button));
        } else {
            button.disabled = true;
        }

        container.appendChild(button);
    }

    const totalCells = leadingBlank + monthEnd.getDate();
    const trailingBlank = (7 - (totalCells % 7)) % 7;
    for (let i = 0; i < trailingBlank; i++) {
        const blank = document.createElement('div');
        blank.className = 'h-12';
        container.appendChild(blank);
    }

    const borrowHidden = document.querySelector('input[name=borrow_date]');
    const returnHidden = document.querySelector('input[name=return_date]');
    if (borrowHidden && returnHidden && borrowHidden.value && returnHidden.value) {
        borrowPick = borrowHidden.value;
        returnPick = returnHidden.value;
        highlightBorrowRange(borrowPick, returnPick, { jumpToMonth: false });
    } else if (borrowPick && returnPick) {
        highlightBorrowRange(borrowPick, returnPick, { jumpToMonth: false });
    } else if (borrowPick) {
        highlightBorrowRange(borrowPick, borrowPick, { jumpToMonth: false });
    } else {
        highlightBorrowRange('', '', { jumpToMonth: false });
    }
}

/**
 * Called when user clicks a day cell. Implements two-step selection:
 *  - first click sets borrowPick
 *  - second click sets returnPick (must be >= borrowPick and <= MAX_BORROW_DAYS and not include blocked days)
 * Clicking again when both picks exist resets to a new borrowPick.
 */
window.handleCalendarClick = function(dateStr, el) {
    if (!el || el.dataset.baseState !== 'available') return;

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');

    const updateDisplays = () => {
        if (borrowHidden) borrowHidden.value = borrowPick || '';
        if (returnHidden) returnHidden.value = returnPick || '';
        if (borrowDisplay) borrowDisplay.textContent = borrowPick ? formatLong(borrowPick) : '—';
        if (returnDisplay) returnDisplay.textContent = returnPick ? formatLong(returnPick) : '—';

        const borrowInput = document.getElementById('borrow_date_display_input');
        const returnInput = document.getElementById('return_date_display_input');
        if (borrowInput) borrowInput.value = borrowPick ? formatLong(borrowPick) : 'Select on calendar';
        if (returnInput) returnInput.value = returnPick ? formatLong(returnPick) : 'Select on calendar';
    };

    if (!borrowPick) {
        borrowPick = dateStr;
        returnPick = '';
        updateDisplays();
        highlightBorrowRange(borrowPick, borrowPick);
        return;
    }

    if (borrowPick && !returnPick) {
        const clicked = parseYMD(dateStr);
        const start = parseYMD(borrowPick);
        if (clicked < start) {
            borrowPick = dateStr;
            returnPick = '';
            updateDisplays();
            highlightBorrowRange(borrowPick, borrowPick);
            return;
        }

        const daysCount = daysDiffInclusive(borrowPick, dateStr);
        if (daysCount > MAX_BORROW_DAYS) {
            window.showToast('warning', `Return date must be within ${MAX_BORROW_DAYS} days from borrow date (inclusive).`);
            return;
        }

        const between = datesBetweenYmd(borrowPick, dateStr);
        for (let d of between) {
            const today = new Date(); today.setHours(0, 0, 0, 0);
            if (parseYMD(d) < today) {
                window.showToast('warning', 'Selected range includes a past date. Please choose valid dates.');
                return;
            }
            if (blockedBorrowDates.includes(d)) {
                window.showToast('warning', 'Selected range includes blocked dates. Please choose another range.');
                return;
            }
        }

        returnPick = dateStr;
        updateDisplays();
        highlightBorrowRange(borrowPick, returnPick);
        return;
    }

    borrowPick = dateStr;
    returnPick = '';
    updateDisplays();
    highlightBorrowRange(borrowPick, borrowPick);
};

/**
 * Highlight a range of days.
 * options = { jumpToMonth: true } by default.
 * When jumpToMonth === false the calendar won't change visible month.
 */
function highlightBorrowRange(start, end, options = { jumpToMonth: true }) {
    if (!start || !end) {
        // If only start provided, compute single-day highlight
        if (start && !end) {
            selectedBorrowDates = [start];
        } else {
            selectedBorrowDates = [];
        }
    } else {
        selectedBorrowDates = datesBetweenYmd(start, end);
    }

    const endDate = parseYMD(end || start);
    if (options && options.jumpToMonth && endDate && (endDate.getMonth() !== borrowMonth || endDate.getFullYear() !== borrowYear)) {
        borrowMonth = endDate.getMonth();
        borrowYear = endDate.getFullYear();
        window.borrowMonth = borrowMonth;
        window.borrowYear = borrowYear;
        if (typeof window.loadBorrowCalendar === 'function') {
            window.loadBorrowCalendar(null, borrowMonth, borrowYear);
        }
        return;
    }

    const days = document.querySelectorAll('#borrowAvailabilityCalendar [data-date]');
    days.forEach((day) => {
        const dStr = day.dataset.date;
        if (!dStr) return;

        const baseState = day.dataset.baseState || 'inactive';
        let state = baseState;

        if (borrowPick && dStr === borrowPick) {
            state = 'borrow';
        }
        if (returnPick && dStr === returnPick) {
            state = borrowPick === returnPick ? 'borrow' : 'return';
        } else if (
            borrowPick &&
            returnPick &&
            selectedBorrowDates.includes(dStr) &&
            dStr !== borrowPick &&
            dStr !== returnPick &&
            baseState === 'available'
        ) {
            state = 'range';
        }

        applyDayState(day, state);
    });

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');

    if (borrowPick) {
        if (borrowHidden) borrowHidden.value = borrowPick;
        if (borrowDisplay) borrowDisplay.textContent = formatLong(borrowPick);
    } else {
        if (borrowHidden) borrowHidden.value = '';
        if (borrowDisplay) borrowDisplay.textContent = '—';
    }
    if (returnPick) {
        if (returnHidden) returnHidden.value = returnPick;
        if (returnDisplay) returnDisplay.textContent = formatLong(returnPick);
    } else {
        if (returnHidden) returnHidden.value = '';
        if (returnDisplay) returnDisplay.textContent = '—';
    }

    // Update Item Usage read-only inputs
    const borrowInput = document.getElementById('borrow_date_display_input');
    const returnInput = document.getElementById('return_date_display_input');
    if (borrowInput) borrowInput.value = borrowPick ? formatLong(borrowPick) : 'Select on calendar';
    if (returnInput) returnInput.value = returnPick ? formatLong(returnPick) : 'Select on calendar';

    // Update Current Selection dates line
    const datesLine = document.getElementById('currentSelectionDates');
    if (datesLine) {
        if (borrowPick && returnPick) {
            const b = parseYMD(borrowPick);
            const r = parseYMD(returnPick);
            const fmt = (d) => d.toLocaleString('default', { month: 'short', day: 'numeric', year: 'numeric' });
            datesLine.textContent = `Dates: ${fmt(b)} – ${fmt(r)}`;
        } else if (borrowPick && !returnPick) {
            const b = parseYMD(borrowPick);
            const fmt = (d) => d.toLocaleString('default', { month: 'short', day: 'numeric', year: 'numeric' });
            datesLine.textContent = `Dates: ${fmt(b)}`;
        } else {
            datesLine.textContent = '';
        }
    }

    window.dispatchEvent(new CustomEvent('borrow:dates-updated', {
        detail: {
            borrow: borrowPick || null,
            return: returnPick || null
        }
    }));
}

/* ---------- Editable item quantities ---------- */

function clampQuantityValue(input) {
    const maxAllowed = Math.max(1, parseInt(input.dataset.itemMax || input.getAttribute('max') || '1', 10) || 1);
    let value = parseInt(input.value, 10);

    if (!Number.isFinite(value) || value < 1) {
        value = 1;
    }
    if (value > maxAllowed) {
        value = maxAllowed;
    }

    input.value = String(value);
    return value;
}

function dispatchQuantityChange(container, quantity) {
    if (!container) return;
    container.dataset.itemQuantity = String(quantity);

    const detail = {
        itemId: container.dataset.itemId || null,
        quantity,
        total: parseInt(container.dataset.itemTotal || container.dataset.itemQuantity || '0', 10) || null,
        name: container.dataset.itemName || null,
    };

    window.dispatchEvent(new CustomEvent('borrow:item-quantity-changed', { detail }));
    
    // Reload calendar when quantity changes to update booked dates
    const availabilityContainer = document.getElementById('borrowAvailabilityCalendar');
    if (availabilityContainer && typeof window.loadBorrowCalendar === 'function') {
        window.loadBorrowCalendar(null, borrowMonth, borrowYear);
    }
}

function bindEditableItemQuantities() {
    const inputs = document.querySelectorAll('.borrow-quantity-input');
    inputs.forEach((input) => {
        const container = input.closest('[data-item-entry]');
        const maxAllowed = Math.max(1, parseInt(input.dataset.itemMax || input.getAttribute('max') || '1', 10) || 1);

        // ensure dataset is initialised
        const initial = clampQuantityValue(input);
        if (container) {
            container.dataset.itemQuantity = String(initial);
        }

        let previous = initial;

        input.addEventListener('focus', () => {
            previous = clampQuantityValue(input);
            input.select?.();
        });

        input.addEventListener('input', () => {
            let raw = String(input.value ?? '').replace(/[^\d]/g, '');
            if (raw.length > 3) raw = raw.slice(0, 3);
            if (raw === '') {
                input.value = '';
                return;
            }

            let value = parseInt(raw, 10);
            if (!Number.isFinite(value) || value < 1) value = 1;
            if (value > maxAllowed) value = maxAllowed;

            if (String(value) !== raw) {
                input.value = String(value);
            }

            previous = value;
            dispatchQuantityChange(container, value);
        });

        input.addEventListener('blur', () => {
            const value = clampQuantityValue(input);
            if (value !== previous) {
                previous = value;
                dispatchQuantityChange(container, value);
            }
        });
    });
}

function initializeManpowerField() {
    const manpowerInput = document.getElementById('manpower_count');
    if (!manpowerInput) return;

    manpowerInput.setAttribute('max', '99');
    manpowerInput.setAttribute('min', '1');
    manpowerInput.setAttribute('maxlength', '2');

    const sanitize = () => {
        let v = manpowerInput.value?.toString() ?? '';
        v = v.replace(/[^\d]/g, '');
        if (v.length > 2) v = v.slice(0, 2);
        if (v === '') {
            manpowerInput.value = '';
            return;
        }
        let n = parseInt(v, 10) || 0;
        if (n < 1) n = 1;
        if (n > 99) n = 99;
        manpowerInput.value = String(n);
    };

    manpowerInput.addEventListener('input', sanitize);
    manpowerInput.addEventListener('blur', sanitize);
}

function initializeUsageControls() {
    const startSelect = document.getElementById('usage_start');
    const endSelect = document.getElementById('usage_end');
    const hiddenInput = document.getElementById('time_of_usage');
    const display = document.getElementById('usageCurrentDisplay');

    if (!startSelect || !endSelect || !hiddenInput) return;

    const optionValues = Array.from(startSelect.options).map((option) => option.value);

    const ensureOrder = () => {
        const startIndex = optionValues.indexOf(startSelect.value);
        let endIndex = optionValues.indexOf(endSelect.value);
        if (startIndex === -1) return;
        if (endIndex <= startIndex) {
            endIndex = Math.min(startIndex + 1, optionValues.length - 1);
            endSelect.value = optionValues[endIndex];
        }
    };

    const updateUsage = () => {
        ensureOrder();
        const start = startSelect.value;
        const end = endSelect.value;
        hiddenInput.value = `${start}-${end}`;
        const label = formatUsageRange(start, end);
        if (display) {
            display.textContent = `Time of Usage: ${label}`;
        }
        window.dispatchEvent(new CustomEvent('borrow:usage-updated', {
            detail: { start, end, label },
        }));
    };

    if (hiddenInput.value && hiddenInput.value.includes('-')) {
        const [savedStart, savedEnd] = hiddenInput.value.split('-');
        if (optionValues.includes(savedStart)) {
            startSelect.value = savedStart;
        }
        if (optionValues.includes(savedEnd)) {
            endSelect.value = savedEnd;
        }
    }

    startSelect.addEventListener('change', updateUsage);
    endSelect.addEventListener('change', updateUsage);

    updateUsage();
}

/* ---------- Init ---------- */
document.addEventListener("DOMContentLoaded", function() {
    bindEditableItemQuantities();
    initializeManpowerField();
    initializeUsageControls();

    const borrowHidden = document.querySelector("input[name=borrow_date]");
    const returnHidden = document.querySelector("input[name=return_date]");
    if (borrowHidden && returnHidden && borrowHidden.value && returnHidden.value) {
        borrowPick = borrowHidden.value;
        returnPick = returnHidden.value;
        const borrowDisplay = document.getElementById('borrow_date_display');
        const returnDisplay = document.getElementById('return_date_display');
        if (borrowDisplay) borrowDisplay.textContent = formatLong(borrowHidden.value);
        if (returnDisplay) returnDisplay.textContent = formatLong(returnHidden.value);
        const endDate = parseYMD(returnHidden.value);
        if (endDate) { 
            borrowMonth = endDate.getMonth(); 
            borrowYear = endDate.getFullYear();
            window.borrowMonth = borrowMonth;
            window.borrowYear = borrowYear;
        }
    } else if (borrowHidden && borrowHidden.value) {
        borrowPick = borrowHidden.value;
        const borrowDisplay = document.getElementById('borrow_date_display');
        if (borrowDisplay) borrowDisplay.textContent = formatLong(borrowHidden.value);
        const bDate = parseYMD(borrowHidden.value);
        if (bDate) { 
            borrowMonth = bDate.getMonth(); 
            borrowYear = bDate.getFullYear();
            window.borrowMonth = borrowMonth;
            window.borrowYear = borrowYear;
        }
    }

    // Auto-load availability for all items in the list
    const availabilityContainer = document.getElementById('borrowAvailabilityCalendar');
    if (availabilityContainer && typeof window.loadBorrowCalendar === 'function') {
        window.loadBorrowCalendar(null, borrowMonth, borrowYear);
    }

    // Initialize Item Usage read-only inputs and dates line
    const usageBorrowDisplay = document.getElementById('usageBorrowDisplay');
    const usageReturnDisplay = document.getElementById('usageReturnDisplay');
    if (usageBorrowDisplay) {
        usageBorrowDisplay.textContent = borrowPick ? formatLong(borrowPick) : 'Select on calendar';
    }
    if (usageReturnDisplay) {
        usageReturnDisplay.textContent = returnPick ? formatLong(returnPick) : 'Select on calendar';
    }
    const borrowInput = document.getElementById('borrow_date_display_input');
    const returnInput = document.getElementById('return_date_display_input');
    if (borrowInput) borrowInput.value = borrowPick ? formatLong(borrowPick) : 'Select on calendar';
    if (returnInput) returnInput.value = returnPick ? formatLong(returnPick) : 'Select on calendar';

    const datesLine = document.getElementById('currentSelectionDates');
    if (datesLine) {
        if (borrowPick && returnPick) {
            const b = parseYMD(borrowPick);
            const r = parseYMD(returnPick);
            const fmt = (d) => d.toLocaleString('default', { month: 'short', day: 'numeric', year: 'numeric' });
            datesLine.textContent = `Dates: ${fmt(b)} – ${fmt(r)}`;
        } else if (borrowPick && !returnPick) {
            const b = parseYMD(borrowPick);
            const fmt = (d) => d.toLocaleString('default', { month: 'short', day: 'numeric', year: 'numeric' });
            datesLine.textContent = `Dates: ${fmt(b)}`;
        } else {
            datesLine.textContent = '';
        }
    }

    // Letter preview is now handled by borrow-list-wizard.js using FilePond
});
