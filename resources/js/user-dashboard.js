// resources/js/user-dashboard.js
// Note: this file expects Chart to be available globally (we load Chart.js CDN in Blade)

(function () {
    const base = window.location.pathname.replace(/\/$/, '');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // ====== Pagination Helpers ======
    let currentPageReq = 1, currentPageAct = 1;
    const perPage = 3;

    function paginate(data, page) {
        const start = (page - 1) * perPage;
        return data.slice(start, start + perPage);
    }

    function renderPagination(total, navId, currentPage, setPage, renderFunc) {
        const nav = document.getElementById(navId);
        if (!nav) return;
        nav.innerHTML = "";
        const totalPages = Math.ceil(total / perPage);
        if (totalPages <= 1) return;

        function btn(label, page, disabled = false, active = false) {
            const el = document.createElement("button");
            el.textContent = label;
            el.className = "px-3 py-1 rounded-md text-sm transition " +
                (active ? "bg-purple-600 text-white" : "bg-gray-100 text-gray-700 hover:bg-purple-100") +
                (disabled ? " opacity-50 cursor-not-allowed" : "");
            if (!disabled) el.addEventListener("click", () => { setPage(page); renderFunc(); });
            return el;
        }

        nav.appendChild(btn("Prev", Math.max(1, currentPage - 1), currentPage === 1));
        for (let i = 1; i <= totalPages; i++) {
            nav.appendChild(btn(i, i, false, i === currentPage));
        }
        nav.appendChild(btn("Next", Math.min(totalPages, currentPage + 1), currentPage === totalPages));
    }
    
    // ====== Activity ======
    let activityData = [];
    async function loadActivity() {
        try {
            const res = await fetch(`${base}/activity`);
            if (!res.ok) return;
            activityData = await res.json();
            renderActivity();
        } catch (err) {
            console.error('Failed loading activity', err);
        }
    }

    function renderActivity() {
        const container = document.getElementById("user-activity");
        if (!Array.isArray(activityData) || activityData.length === 0) {
            container.innerHTML = '<p class="text-gray-500">No recent activity.</p>';
            return;
        }
        container.innerHTML = "";
        const pageData = paginate(activityData, currentPageAct);
        pageData.forEach(a => {
            container.innerHTML += `<div class="py-2"><div>${a.action ?? ''}</div><div class="text-xs opacity-70">${a.time ?? ''}</div></div>`;
        });
        renderPagination(activityData.length, "recentActivityPagination", currentPageAct, (p)=>{currentPageAct=p;}, renderActivity);
    }

    // ====== Borrow Trends (Chart.js) ======
    // Using global Chart from CDN
    const personalEmptyEl = document.getElementById("personalBorrowEmpty");
    let personalChart;
    let initialData = {};

    function initChart() {
        const canvas = document.getElementById("personalBorrowChart");
        if (!canvas) return;
        const ctx = canvas.getContext("2d");
        
        // Get initial trend data
        const myTrends = initialData.myBorrowTrends || {};
        const labels = Object.keys(myTrends);
        const values = Object.values(myTrends);
        
        // Theme detection
        const isDark = document.body.classList.contains('theme-dark');
        const textColor = isDark ? '#f3f4f6' : '#1f2937';
        const gridColor = isDark ? '#374151' : '#e5e7eb';
        
        personalChart = new Chart(ctx, {
            type: 'line',
            data: { 
                labels: labels, 
                datasets: [{ 
                    label: 'Borrow Requests', 
                    data: values, 
                    borderColor: 'rgba(99,102,241,1)', 
                    backgroundColor: 'rgba(99,102,241,0.1)', 
                    fill: true, 
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(99,102,241,1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: textColor }
                    }
                }
            }
        });
        
        // Show/hide empty state
        if (!labels.length || values.reduce((a,b)=>a+Number(b||0),0) === 0) {
            personalEmptyEl?.classList.remove('hidden');
        } else {
            personalEmptyEl?.classList.add('hidden');
        }
    }

    async function fetchTrends(range) {
        try {
            const res = await fetch(`${base}/dashboard/borrow-trends?range=${encodeURIComponent(range)}`);
            if (!res.ok) return;
            const data = await res.json(); // { "2025-09-01": 1, ... } etc.
            const labels = Object.keys(data || {});
            const values = Object.values(data || {});
            if (personalChart) {
                personalChart.data.labels = labels;
                personalChart.data.datasets[0].data = values;
                personalChart.update();
            }
            if (!labels.length || values.reduce((a,b)=>a+Number(b||0),0) === 0) {
                personalEmptyEl?.classList.remove('hidden');
            } else {
                personalEmptyEl?.classList.add('hidden');
            }
        } catch (err) {
            console.error("Trend fetch error", err);
            if (personalEmptyEl) {
                personalEmptyEl.textContent = "Unable to load trend data.";
                personalEmptyEl.classList.remove("hidden");
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const dataScript = document.getElementById('user-dashboard-data');
        const activityContainer = document.getElementById('user-activity');
        if (!dataScript && !activityContainer) {
            return;
        }

        if (dataScript && dataScript.textContent) {
            try {
                initialData = JSON.parse(dataScript.textContent || '{}') || {};
            } catch (e) {
                console.warn('Failed to parse user dashboard data', e);
                initialData = {};
            }
        }

        initChart();
        document.getElementById("personalTrendFilter")?.addEventListener("change", e => {
            fetchTrends(e.target.value);
        });

        if (typeof loadActivity === 'function' && activityContainer) {
            loadActivity();
            setInterval(loadActivity, 30000);
        }
    });
})();
