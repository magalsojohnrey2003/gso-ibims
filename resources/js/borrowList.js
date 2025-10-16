// resources/js/borrowList.js
let selectedBorrowDates = []; // still used as computed highlighted date list
let borrowMonth = new Date().getMonth();
let borrowYear = new Date().getFullYear();
let blockedBorrowDates = [];
const MAX_BORROW_DAYS = 3; // inclusive max days

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

    const days = document.querySelectorAll("#borrowAvailabilityCalendar div[data-date]");
    days.forEach(day => {
        day.classList.remove("bg-blue-500", "text-white", "ring-2", "ring-purple-400", "text-purple-700");
        // revert to available style if it's not blocked/past
        if (!day.classList.contains("bg-red-500") && !day.classList.contains("bg-gray-300")) {
            day.classList.add("bg-green-200");
        }
    });

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');
    if (borrowHidden) borrowHidden.value = '';
    if (returnHidden) returnHidden.value = '';
    if (borrowDisplay) borrowDisplay.value = '';
    if (returnDisplay) returnDisplay.value = '';
};

window.changeBorrowMonth = function(step) {
    borrowMonth += step;
    if (borrowMonth > 11) { borrowMonth = 0; borrowYear++; }
    if (borrowMonth < 0)  { borrowMonth = 11; borrowYear--; }

    const itemId = getFirstItemIdFromList();
    if (itemId) {
        loadBorrowCalendar(itemId, borrowMonth, borrowYear);
    } else {
        renderBorrowCalendar(borrowMonth, borrowYear);
    }
};

function loadBorrowCalendar(itemId, month, year) {
    if (!itemId) {
        blockedBorrowDates = [];
        renderBorrowCalendar(month, year);
        return;
    }

    fetch(`/user/availability/${itemId}`)
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
}

function renderBorrowCalendar(month, year) {
    const today = new Date(); today.setHours(0,0,0,0);
    const todayYmd = ymdFromDate(today);
    const monthStart = new Date(year, month, 1);
    const monthEnd = new Date(year, month + 1, 0);

    const monthTitleEl = document.getElementById('borrowCalendarMonth');
    if (monthTitleEl) monthTitleEl.innerText = `${monthStart.toLocaleString('default',{month:'long'})} ${year}`;

    let html = `<div class="grid grid-cols-7 gap-1 text-center">`;

    for (let i = 0; i < monthStart.getDay(); i++) html += `<div></div>`;

    for (let d = 1; d <= monthEnd.getDate(); d++) {
        const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dateObj = parseYMD(dateStr);
        const isBlocked = blockedBorrowDates.includes(dateStr);
        const isPast = dateObj < today;

        let classes = "p-2 rounded cursor-pointer transition-colors duration-150 inline-flex items-center justify-center ";
        if (isPast) classes += "bg-gray-300 text-gray-500 cursor-not-allowed";
        else if (isBlocked) classes += "bg-red-500 text-white";
        else classes += "bg-green-200 hover:bg-blue-400 hover:text-white";

        if (dateStr === todayYmd && !isPast && !isBlocked) {
            classes += " ring-2 ring-purple-400";
        }

        html += `<div data-date="${dateStr}" onclick="handleCalendarClick('${dateStr}', this)" class="${classes}">${d}</div>`;
    }

    html += "</div>";
    const container = document.getElementById("borrowAvailabilityCalendar");
    if (container) container.innerHTML = html;

    // If hidden inputs already have values (e.g. old validation), highlight saved range but DO NOT jump month when rendering after navigation
    const borrowHidden = document.querySelector("input[name=borrow_date]");
    const returnHidden = document.querySelector("input[name=return_date]");
    if (borrowHidden && returnHidden && borrowHidden.value && returnHidden.value) {
        borrowPick = borrowHidden.value;
        returnPick = returnHidden.value;
        highlightBorrowRange(borrowPick, returnPick, { jumpToMonth: false });
    } else {
        // If we had an in-memory selection (user clicked), highlight it as well
        if (borrowPick && returnPick) {
            highlightBorrowRange(borrowPick, returnPick, { jumpToMonth: false });
        } else if (borrowPick) {
            // If only borrow set, highlight just that day
            highlightBorrowRange(borrowPick, borrowPick, { jumpToMonth: false });
        }
    }
}

/**
 * Called when user clicks a day cell. Implements two-step selection:
 *  - first click sets borrowPick
 *  - second click sets returnPick (must be >= borrowPick and <= MAX_BORROW_DAYS and not include blocked days)
 * Clicking again when both picks exist resets to a new borrowPick.
 */
window.handleCalendarClick = function(dateStr, el) {
    if (!el) return;

    // ignore if blocked or past
    if (el.classList.contains("bg-red-500") || el.classList.contains("bg-gray-300")) return;

    // If neither selected yet -> set borrowPick
    if (!borrowPick) {
        borrowPick = dateStr;
        returnPick = '';
        // update displays
        const borrowHidden = document.getElementById('borrow_date');
        const returnHidden = document.getElementById('return_date');
        const borrowDisplay = document.getElementById('borrow_date_display');
        const returnDisplay = document.getElementById('return_date_display');
        if (borrowHidden) borrowHidden.value = borrowPick;
        if (returnHidden) returnHidden.value = '';
        if (borrowDisplay) borrowDisplay.value = formatLong(borrowPick);
        if (returnDisplay) returnDisplay.value = '';
        // highlight single day
        highlightBorrowRange(borrowPick, borrowPick);
        return;
    }

    // If borrow set and return not yet -> attempt to set returnPick
    if (borrowPick && !returnPick) {
        const clicked = parseYMD(dateStr);
        const start = parseYMD(borrowPick);
        // If clicked is before borrowPick, treat as new borrowPick (start over)
        if (clicked < start) {
            borrowPick = dateStr;
            returnPick = '';
            const borrowHidden = document.getElementById('borrow_date');
            const returnHidden = document.getElementById('return_date');
            const borrowDisplay = document.getElementById('borrow_date_display');
            const returnDisplay = document.getElementById('return_date_display');
            if (borrowHidden) borrowHidden.value = borrowPick;
            if (returnHidden) returnHidden.value = '';
            if (borrowDisplay) borrowDisplay.value = formatLong(borrowPick);
            if (returnDisplay) returnDisplay.value = '';
            highlightBorrowRange(borrowPick, borrowPick);
            return;
        }

        // Validate inclusive days count
        const daysCount = daysDiffInclusive(borrowPick, dateStr);
        if (daysCount > MAX_BORROW_DAYS) {
            alert(`Return date must be within ${MAX_BORROW_DAYS} days from borrow date (inclusive).`);
            return;
        }

        // Validate no blocked days inside the range
        const between = datesBetweenYmd(borrowPick, dateStr);
        for (let d of between) {
            const today = new Date(); today.setHours(0,0,0,0);
            if (parseYMD(d) < today) {
                alert('Selected range includes a past date. Please choose valid dates.');
                return;
            }
            if (blockedBorrowDates.includes(d)) {
                alert('Selected range includes blocked dates. Please choose another range.');
                return;
            }
        }

        // All good -> set returnPick and finalize highlight
        returnPick = dateStr;
        const borrowHidden = document.getElementById('borrow_date');
        const returnHidden = document.getElementById('return_date');
        const borrowDisplay = document.getElementById('borrow_date_display');
        const returnDisplay = document.getElementById('return_date_display');
        if (borrowHidden) borrowHidden.value = borrowPick;
        if (returnHidden) returnHidden.value = returnPick;
        if (borrowDisplay) borrowDisplay.value = formatLong(borrowPick);
        if (returnDisplay) returnDisplay.value = formatLong(returnPick);

        highlightBorrowRange(borrowPick, returnPick);
        return;
    }

    // If both already selected -> treat this click as "start a new selection"
    borrowPick = dateStr;
    returnPick = '';
    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');
    if (borrowHidden) borrowHidden.value = borrowPick;
    if (returnHidden) returnHidden.value = '';
    if (borrowDisplay) borrowDisplay.value = formatLong(borrowPick);
    if (returnDisplay) returnDisplay.value = '';
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
        const itemId = getFirstItemIdFromList();
        if (itemId) {
            loadBorrowCalendar(itemId, borrowMonth, borrowYear);
            return;
        } else {
            renderBorrowCalendar(borrowMonth, borrowYear);
            return;
        }
    }

    const days = document.querySelectorAll("#borrowAvailabilityCalendar div[data-date]");
    days.forEach(day => {
        const dStr = day.dataset.date;
        if (!dStr) return;

        if (selectedBorrowDates.includes(dStr)) {
            day.classList.remove("bg-green-200", "ring-2", "ring-purple-400");
            day.classList.add("bg-blue-500", "text-white");
        } else if (!day.classList.contains("bg-red-500") && !day.classList.contains("bg-gray-300")) {
            const todayYmd = ymdFromDate(new Date());
            if (dStr === todayYmd) {
                day.classList.remove("bg-blue-500", "text-white");
                day.classList.add("bg-green-200", "ring-2", "ring-purple-400");
            } else {
                day.classList.remove("bg-blue-500", "text-white", "ring-2", "ring-purple-400");
                day.classList.add("bg-green-200");
            }
        }
    });

    // update hidden and display inputs (if both present)
    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');

    if (borrowPick) {
        if (borrowHidden) borrowHidden.value = borrowPick;
        if (borrowDisplay) borrowDisplay.value = formatLong(borrowPick);
    }
    if (returnPick) {
        if (returnHidden) returnHidden.value = returnPick;
        if (returnDisplay) returnDisplay.value = formatLong(returnPick);
    }

    // If only borrowPick exists, ensure the single day is highlighted (already handled above)
}

/* ---------- Manpower roles UI & location integration ---------- */

function updateManpowerRolesVisibility() {
    const wrapper = document.getElementById('manpowerRolesWrapper');
    const manpowerInput = document.getElementById('manpower_count');
    const requestedDisplay = document.getElementById('manpowerRequestedDisplay');
    const totalSpan = document.getElementById('manpowerRolesTotal');
    const warningEl = document.getElementById('manpowerRolesWarning');

    const requested = Math.max(0, parseInt(manpowerInput?.value || '0', 10));
    if (requested > 0) {
        wrapper?.classList.remove('hidden');
    } else {
        wrapper?.classList.add('hidden');
        // clear rows
        const container = document.getElementById('manpowerRolesContainer');
        if (container) container.innerHTML = '';
        if (totalSpan) totalSpan.textContent = '0';
        if (warningEl) { warningEl.textContent = ''; warningEl.classList.add('hidden'); }
    }
    if (requestedDisplay) requestedDisplay.textContent = String(requested);
    // recalc totals
    recalcRolesTotal();
}

function addRoleRow(prefill = {}) {
    const tpl = document.getElementById('manpowerRoleRowTemplate');
    if (!tpl) return null;

    // PREVENT adding duplicate predefined roles (except "Other").
    const requested = Math.max(0, parseInt(document.getElementById('manpower_count')?.value || '0', 10));
    const container = document.getElementById('manpowerRolesContainer');

    // normalize role from prefill if provided
    const incomingRole = (prefill.role || '').trim();

    if (incomingRole && incomingRole !== 'Other' && container) {
        const exists = Array.from(container.querySelectorAll('.role-row')).some(r => {
            const sel = r.querySelector('.role-select');
            const other = r.querySelector('.role-other-input');
            const val = sel?.value || '';
            const otherVal = other?.value || '';
            // if existing row uses "Other", compare against its custom text
            if (val === 'Other') {
                return otherVal.trim() && otherVal.trim() === incomingRole;
            }
            return val === incomingRole;
        });

        if (exists) {
            // focus the first matching row instead of adding another
            const first = Array.from(container.querySelectorAll('.role-row')).find(r => {
                const sel = r.querySelector('.role-select');
                const other = r.querySelector('.role-other-input');
                const val = sel?.value || '';
                const otherVal = other?.value || '';
                if (val === 'Other') return otherVal.trim() && otherVal.trim() === incomingRole;
                return val === incomingRole;
            });
            if (first) {
                // flash/bounce visual hint
                first.classList.add('ring-2','ring-yellow-300');
                setTimeout(()=> first.classList.remove('ring-2','ring-yellow-300'), 900);
                // focus qty input so admin can increase
                const q = first.querySelector('.role-qty-input');
                if (q) q.focus();
            }
            return first || null;
        }
    }

    // Otherwise create a new role row
    const frag = tpl.content.cloneNode(true);
    const row = frag.querySelector('.role-row');

    const select = row.querySelector('.role-select');
    const otherInput = row.querySelector('.role-other-input');
    const qtyInput = row.querySelector('.role-qty-input');
    const notesInput = row.querySelector('.role-notes-input');
    const removeBtn = row.querySelector('.remove-role-btn');

    if (prefill.role) {
        const optionExists = Array.from(select.options).some(o => o.value === prefill.role);
        if (optionExists) select.value = prefill.role;
        else {
            select.value = 'Other';
            otherInput.classList.remove('hidden');
            otherInput.value = prefill.role;
        }
    }
    if (typeof prefill.qty !== 'undefined') qtyInput.value = String(prefill.qty);
    if (typeof prefill.notes !== 'undefined') notesInput.value = prefill.notes;

    select.addEventListener('change', () => {
        if (select.value === 'Other') {
            otherInput.classList.remove('hidden');
            otherInput.focus();
        } else {
            otherInput.classList.add('hidden');
            otherInput.value = '';
        }
        // recalc totals & enable/disable add btn
        recalcRolesTotal();
    });

    qtyInput.addEventListener('input', () => recalcRolesTotal());
    removeBtn.addEventListener('click', () => {
        row.remove();
        recalcRolesTotal();
    });

    container.appendChild(row);
    recalcRolesTotal();
    return row;
}

function recalcRolesTotal() {
    const container = document.getElementById('manpowerRolesContainer');
    const totalSpan = document.getElementById('manpowerRolesTotal');
    const requested = Math.max(0, parseInt(document.getElementById('manpower_count')?.value || '0', 10));
    const warningEl = document.getElementById('manpowerRolesWarning');
    const addRoleBtn = document.getElementById('addRoleBtn');

    if (!container) return;
    let total = 0;
    let rowCount = 0;
    const rows = Array.from(container.querySelectorAll('.role-row'));
    rows.forEach(row => {
        rowCount++;
        const qty = parseInt(row.querySelector('.role-qty-input')?.value || '0', 10) || 0;
        total += qty;
    });

    if (totalSpan) totalSpan.textContent = String(total);

    // Disable Add Role button when:
    //  - requested > 0 and total assigned manpower >= requested, OR
    //  - requested > 0 and number of role rows >= requested
    if (addRoleBtn) {
        const shouldDisable = requested > 0 && (total >= requested || rowCount >= requested);
        addRoleBtn.disabled = !!shouldDisable;
        addRoleBtn.classList.toggle('opacity-50', !!shouldDisable);
        addRoleBtn.classList.toggle('cursor-not-allowed', !!shouldDisable);
    }

    // If a single row already contains quantity >= requested, lock other rows' inputs (qty/select/notes)
    if (requested > 0) {
        const rowWithFullQty = rows.find(r => {
            const q = parseInt(r.querySelector('.role-qty-input')?.value || '0', 10) || 0;
            return q >= requested;
        });

        rows.forEach(r => {
            const qtyInput = r.querySelector('.role-qty-input');
            const select = r.querySelector('.role-select');
            const otherInput = r.querySelector('.role-other-input');
            const notes = r.querySelector('.role-notes-input');
            const removeBtn = r.querySelector('.remove-role-btn');

            if (rowWithFullQty && rowWithFullQty !== r) {
                // block other rows (but keep remove enabled so admin can free up slots)
                if (qtyInput) qtyInput.disabled = true;
                if (select) select.disabled = true;
                if (otherInput) otherInput.disabled = true;
                if (notes) notes.disabled = true;
                // visually indicate locked rows
                r.classList.add('opacity-60');
            } else {
                // normal state
                if (qtyInput) qtyInput.disabled = false;
                if (select) select.disabled = false;
                if (otherInput) otherInput.disabled = false;
                if (notes) notes.disabled = false;
                r.classList.remove('opacity-60');
            }

            // always keep remove button enabled so admin can adjust
            if (removeBtn) removeBtn.disabled = false;
        });
    } else {
        // no requested limit => ensure all rows enabled
        rows.forEach(r => {
            const qtyInput = r.querySelector('.role-qty-input');
            const select = r.querySelector('.role-select');
            const otherInput = r.querySelector('.role-other-input');
            const notes = r.querySelector('.role-notes-input');
            if (qtyInput) qtyInput.disabled = false;
            if (select) select.disabled = false;
            if (otherInput) otherInput.disabled = false;
            if (notes) notes.disabled = false;
            r.classList.remove('opacity-60');
        });
    }

    // Soft warning (no hard stop) if totals exceed requested
    if (requested > 0 && total > requested) {
        if (warningEl) {
            warningEl.textContent = `Total assigned manpower (${total}) exceeds requested (${requested}). Please adjust or remove extra roles.`;
            warningEl.classList.remove('hidden');
        }
    } else {
        if (warningEl) { warningEl.textContent = ''; warningEl.classList.add('hidden'); }
    }
}

/**
 * Build hidden inputs for roles so the existing HTML form posts them as regular fields.
 * Output names are:
 *    manpower_roles[0][role] = 'Setup' or custom
 *    manpower_roles[0][qty]  = 5
 *    manpower_roles[0][notes] = '...'
 */
function injectRoleHiddenInputs(form) {
    // remove prior inputs if any
    Array.from(form.querySelectorAll('input[name^="manpower_roles"], textarea[name^="manpower_roles"], select[name^="manpower_roles"]')).forEach(el => el.remove());

    const container = document.getElementById('manpowerRolesContainer');
    if (!container) return;

    const rows = Array.from(container.querySelectorAll('.role-row'));
    rows.forEach((row, idx) => {
        const select = row.querySelector('.role-select');
        const otherInput = row.querySelector('.role-other-input');
        const qtyInput = row.querySelector('.role-qty-input');
        const notesInput = row.querySelector('.role-notes-input');

        let roleVal = select?.value || '';
        if (roleVal === 'Other') {
            roleVal = otherInput?.value || '';
        }

        // Create hidden inputs
        const roleField = document.createElement('input');
        roleField.type = 'hidden';
        roleField.name = `manpower_roles[${idx}][role]`;
        roleField.value = roleVal;
        form.appendChild(roleField);

        const qtyField = document.createElement('input');
        qtyField.type = 'hidden';
        qtyField.name = `manpower_roles[${idx}][qty]`;
        qtyField.value = qtyInput?.value || '0';
        form.appendChild(qtyField);

        const notesField = document.createElement('input');
        notesField.type = 'hidden';
        notesField.name = `manpower_roles[${idx}][notes]`;
        notesField.value = notesInput?.value || '';
        form.appendChild(notesField);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // wire up manpower count -> show roles UI
    const manpowerInput = document.getElementById('manpower_count');
    const addRoleBtn = document.getElementById('addRoleBtn');
    const rolesContainer = document.getElementById('manpowerRolesContainer');
    const borrowForm = document.getElementById('borrowListForm');

    // if we referenced route() in blade, the string is already present — if not, fallback to first form.
    if (manpowerInput) {
        // enforce attributes (helps native browsers)
        manpowerInput.setAttribute('max', '99');
        manpowerInput.setAttribute('min', '1');
        manpowerInput.setAttribute('maxlength', '2');

        // clamp function: allow empty string (optional field), otherwise force 1..99 and max 2 digits
        const clampManpowerInput = () => {
            let v = manpowerInput.value?.toString() ?? '';
            // strip non-digits
            v = v.replace(/[^\d]/g, '');
            if (v.length > 2) v = v.slice(0,2);
            if (v !== '') {
                let n = parseInt(v, 10) || 0;
                if (n < 1) n = 1;
                if (n > 99) n = 99;
                v = String(n);
            }
            // apply if changed
            if (manpowerInput.value !== v) manpowerInput.value = v;
            // update visibility + totals UI
            updateManpowerRolesVisibility();
            // recalc roles (which will also enable/disable add button)
            recalcRolesTotal();
        };

        manpowerInput.addEventListener('input', clampManpowerInput);
        manpowerInput.addEventListener('blur', clampManpowerInput);

        // init visibility on load (use existing old value)
        updateManpowerRolesVisibility();
        // ensure button state computed on load
        recalcRolesTotal();
    }

  function safeAddRole() {
    const requested = Math.max(0, parseInt(document.getElementById('manpower_count')?.value || '0', 10));
    const container = document.getElementById('manpowerRolesContainer');
    const rowCount = container ? container.querySelectorAll('.role-row').length : 0;

    // If a requested limit exists and we've already added as many rows as requested, block adding
    if (requested > 0 && rowCount >= requested) {
        const btn = document.getElementById('addRoleBtn');
        if (btn) {
            // brief visual feedback
            btn.classList.add('opacity-80');
            setTimeout(()=>btn.classList.remove('opacity-80'), 250);
        }
        return;
    }

    addRoleRow({ role: '', qty: 0 });
}

if (addRoleBtn) {
    addRoleBtn.addEventListener('click', () => safeAddRole());
}


    // If the page had old role data (like from old input), you could prepopulate here
    // (left intentionally simple). You can extend by reading preloaded JSON in a data attribute.

    // Intercept form submit: inject hidden inputs for roles and perform client-side validation
    if (borrowForm) {
        borrowForm.addEventListener('submit', function (ev) {
            // validate totals (soft validation only)
            const requested = Math.max(0, parseInt(manpowerInput?.value || '0', 10));
            const total = parseInt(document.getElementById('manpowerRolesTotal')?.textContent || '0', 10) || 0;
            const warningEl = document.getElementById('manpowerRolesWarning');

            if (requested > 0 && total > requested) {
                // gentle warning but DO NOT block submission
                if (warningEl) {
                    warningEl.textContent = `Total assigned manpower (${total}) exceeds requested (${requested}). Admin may still submit but please verify.`;
                    warningEl.classList.remove('hidden');
                } else {
                    console.warn(`Total assigned manpower (${total}) exceeds requested (${requested}).`);
                }
                // proceed to inject hidden inputs and submit
            } else {
                if (warningEl) { warningEl.textContent = ''; warningEl.classList.add('hidden'); }
            }

            // build hidden role inputs
            injectRoleHiddenInputs(borrowForm);
            // location input is already present so it's sent automatically
            return true;
        });
    }

});


/* ---------- Init ---------- */
document.addEventListener("DOMContentLoaded", function() {
    
    const borrowHidden = document.querySelector("input[name=borrow_date]");
    const returnHidden = document.querySelector("input[name=return_date]");
    if (borrowHidden && returnHidden && borrowHidden.value && returnHidden.value) {
        borrowPick = borrowHidden.value;
        returnPick = returnHidden.value;
        const borrowDisplay = document.getElementById('borrow_date_display');
        const returnDisplay = document.getElementById('return_date_display');
        if (borrowDisplay) borrowDisplay.value = formatLong(borrowHidden.value);
        if (returnDisplay) returnDisplay.value = formatLong(returnHidden.value);
        const endDate = parseYMD(returnHidden.value);
        if (endDate) { borrowMonth = endDate.getMonth(); borrowYear = endDate.getFullYear(); }
    } else if (borrowHidden && borrowHidden.value) {
        borrowPick = borrowHidden.value;
        const borrowDisplay = document.getElementById('borrow_date_display');
        if (borrowDisplay) borrowDisplay.value = formatLong(borrowHidden.value);
        const bDate = parseYMD(borrowHidden.value);
        if (bDate) { borrowMonth = bDate.getMonth(); borrowYear = bDate.getFullYear(); }
    }

    // Live search (client-side filtering)
    const searchInput = document.getElementById('search');
    let itemCards = document.querySelectorAll('.borrow-item-card');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const q = e.target.value.trim().toLowerCase();
            if (q === '') {
                itemCards.forEach(card => card.classList.remove('hidden'));
                return;
            }
            itemCards.forEach(card => {
                const name = (card.dataset.name || '').toLowerCase();
                const category = (card.dataset.category || '').toLowerCase();
                const fullText = (card.innerText || '').toLowerCase();
                if (name.includes(q) || category.includes(q) || fullText.includes(q)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    }

    // Auto-load availability for first item or render plain calendar
    const availabilityContainer = document.getElementById('borrowAvailabilityCalendar');
    if (availabilityContainer) {
        const firstItemId = getFirstItemIdFromList();
        if (firstItemId) {
            loadBorrowCalendar(firstItemId, borrowMonth, borrowYear);
        } else {
            renderBorrowCalendar(borrowMonth, borrowYear);
        }
    }
});
