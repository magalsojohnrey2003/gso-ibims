// resources/js/borrowList.js
let selectedBorrowDates = [];
let borrowMonth = new Date().getMonth();
let borrowYear = new Date().getFullYear();
let blockedBorrowDates = [];
const MAX_BORROW_DAYS = 3; // inclusive max days

/* ---------- Helpers ---------- */
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

/* ---------- Calendar & selection logic (updated) ---------- */

window.clearBorrowSelection = function() {
    // Only clear currently-selected days (do not repaint all available cells)
    if (!selectedBorrowDates.length) return;

    const days = document.querySelectorAll("#borrowAvailabilityCalendar div[data-date]");
    days.forEach(day => {
        const dStr = day.dataset.date;
        if (selectedBorrowDates.includes(dStr)) {
            day.classList.remove("bg-blue-500", "text-white", "ring-2", "ring-purple-400", "text-purple-700");
            // revert to available style if it's not blocked/past
            if (!day.classList.contains("bg-red-500") && !day.classList.contains("bg-gray-300")) {
                day.classList.add("bg-green-200");
            }
        }
    });

    selectedBorrowDates = [];
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

        html += `<div data-date="${dateStr}" onclick="selectBorrowDate('${dateStr}', this)" class="${classes}">${d}</div>`;
    }

    html += "</div>";
    const container = document.getElementById("borrowAvailabilityCalendar");
    if (container) container.innerHTML = html;

    // If hidden inputs already have values (e.g. old validation), highlight range but DO NOT jump month when rendering after navigation
    const borrowHidden = document.querySelector("input[name=borrow_date]");
    const returnHidden = document.querySelector("input[name=return_date]");
    if (borrowHidden && returnHidden && borrowHidden.value && returnHidden.value) {
        highlightBorrowRange(borrowHidden.value, returnHidden.value, { jumpToMonth: false });
    } else {
        if (selectedBorrowDates.length) {
            highlightBorrowRange(selectedBorrowDates[0], selectedBorrowDates[selectedBorrowDates.length - 1], { jumpToMonth: false });
        }
    }
}

window.selectBorrowDate = function(dateStr, el) {
    if (!el) return;

    // ignore if blocked or past
    if (el.classList.contains("bg-red-500") || el.classList.contains("bg-gray-300")) return;

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');

    const clickedDt = parseYMD(dateStr);

    // If click inside existing selection -> clear selection
    if (selectedBorrowDates.length) {
        const existingStart = selectedBorrowDates[0];
        const existingEnd = selectedBorrowDates[selectedBorrowDates.length - 1];
        if (parseYMD(existingStart) <= clickedDt && clickedDt <= parseYMD(existingEnd)) {
            clearBorrowSelection();
            return;
        }
    }

    // Build forward range from clicked date up to MAX_BORROW_DAYS OR until blocked/past day
    const newSel = [];
    for (let i = 0; i < MAX_BORROW_DAYS; i++) {
        const d = addDaysYmd(dateStr, i);
        if (!d) break;
        const dObj = parseYMD(d);
        const today = new Date(); today.setHours(0,0,0,0);
        if (dObj < today) break;
        if (blockedBorrowDates.includes(d)) break;
        newSel.push(d);
    }

    if (!newSel.length) return;

    selectedBorrowDates = newSel;

    // Update hidden inputs + displays
    const start = selectedBorrowDates[0];
    const end = selectedBorrowDates[selectedBorrowDates.length - 1];
    if (borrowHidden) borrowHidden.value = start;
    if (returnHidden) returnHidden.value = end;
    if (borrowDisplay) borrowDisplay.value = formatLong(start);
    if (returnDisplay) returnDisplay.value = formatLong(end);

    // If the selection end falls in a different month/year, switch the visible calendar to that month
    const endDateObj = parseYMD(end);
    if (endDateObj.getMonth() !== borrowMonth || endDateObj.getFullYear() !== borrowYear) {
        borrowMonth = endDateObj.getMonth();
        borrowYear = endDateObj.getFullYear();
        const itemId = getFirstItemIdFromList();
        if (itemId) {
            // load availability for the new month then render — this will call highlightBorrowRange afterwards
            loadBorrowCalendar(itemId, borrowMonth, borrowYear);
        } else {
            // render plain calendar for the month that contains the end date
            renderBorrowCalendar(borrowMonth, borrowYear);
        }
        // return early because renderBorrowCalendar() will highlight range after rendering
        return;
    }

    // otherwise highlight in the currently-rendered month DOM
    const days = document.querySelectorAll("#borrowAvailabilityCalendar div[data-date]");
    days.forEach(day => {
        const dStr = day.dataset.date;
        if (!dStr) return;
        if (selectedBorrowDates.includes(dStr)) {
            day.classList.remove("bg-green-200", "ring-2", "ring-purple-400");
            day.classList.add("bg-blue-500", "text-white");
        } else if (!day.classList.contains("bg-red-500") && !day.classList.contains("bg-gray-300")) {
            day.classList.remove("bg-blue-500", "text-white");
            const todayYmd = ymdFromDate(new Date());
            if (dStr === todayYmd) {
                day.classList.add("bg-green-200", "ring-2", "ring-purple-400");
            } else {
                day.classList.add("bg-green-200");
                day.classList.remove("ring-2", "ring-purple-400");
            }
        }
    });
};


/**
 * Highlight a range of days.
 * options = { jumpToMonth: true } by default.
 * When jumpToMonth === false the calendar won't change visible month.
 */
function highlightBorrowRange(start, end, options = { jumpToMonth: true }) {
    if (!start || !end) return;
    selectedBorrowDates = datesBetweenYmd(start, end);

    const endDate = parseYMD(end);
    if (options && options.jumpToMonth && (endDate.getMonth() !== borrowMonth || endDate.getFullYear() !== borrowYear)) {
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

    // update hidden and display inputs
    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const borrowDisplay = document.getElementById('borrow_date_display');
    const returnDisplay = document.getElementById('return_date_display');

    if (selectedBorrowDates.length > 0) {
        const startStr = selectedBorrowDates[0];
        const endDateStr = selectedBorrowDates[selectedBorrowDates.length - 1];
        if (borrowHidden) borrowHidden.value = startStr;
        if (returnHidden) returnHidden.value = endDateStr;
        if (borrowDisplay) borrowDisplay.value = formatLong(startStr);
        if (returnDisplay) returnDisplay.value = formatLong(endDateStr);
    }
}

/* ---------- Init ---------- */
document.addEventListener("DOMContentLoaded", function() {
    const borrowHidden = document.querySelector("input[name=borrow_date]");
    const returnHidden = document.querySelector("input[name=return_date]");
    if (borrowHidden && returnHidden && borrowHidden.value && returnHidden.value) {
        const borrowDisplay = document.getElementById('borrow_date_display');
        const returnDisplay = document.getElementById('return_date_display');
        if (borrowDisplay) borrowDisplay.value = formatLong(borrowHidden.value);
        if (returnDisplay) returnDisplay.value = formatLong(returnHidden.value);
        const endDate = parseYMD(returnHidden.value);
        if (endDate) { borrowMonth = endDate.getMonth(); borrowYear = endDate.getFullYear(); }
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
