<x-app-layout>
 
    <x-title level="h2"
         size="2xl"
         weight="bold"
         icon="home"
         variant="s"
         iconStyle="circle"
         iconBg="gov-accent"
         iconColor="white">
    Dashboard
</x-title>
    <div class="py-10">
        
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
          
            {{-- Quick Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-box-open text-indigo-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">My Borrowed Items (Active)</p>
                        <p class="text-2xl font-bold">{{ $myBorrowedCount ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-hourglass-half text-yellow-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Pending Requests</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $pendingReq ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-undo text-green-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Returned Items</p>
                        <p class="text-2xl font-bold text-green-600">{{ $returnedReq ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Declined Requests</p>
                        <p class="text-2xl font-bold text-red-600">{{ $rejectedReq ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Two-column: Trends + Available Items --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Personal Borrow Trends --}}
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">📈 My Borrow Trends</h3>
                        <select id="personalTrendFilter" class="border rounded px-3 py-1 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month" selected>Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>
                    <div class="h-56">
                        <canvas id="personalBorrowChart"></canvas>
                        <p id="personalBorrowEmpty" class="text-gray-500 text-sm mt-2 hidden"></p>
                    </div>
                </div>

                {{-- Available Items Preview --}}
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">📦 Available Items</h3>
                        <a href="{{ route('borrow.items') }}" class="text-sm underline">See all items</a>
                    </div>
                    <div id="available-items" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if(isset($availableItemsPreview) && count($availableItemsPreview))
                            @foreach($availableItemsPreview as $item)
                                <div class="p-3 border rounded flex items-center gap-3">
                                    <div class="w-14 h-14 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                        @if(!empty($item['photo']))
                                            <img src="{{ asset('storage/'.$item['photo']) }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $item['name'] ?? 'Unknown' }}</div>
                                        <div class="text-xs opacity-70">{{ $item['category'] ?? '' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500">No items preview available.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="card p-6">
                <h3 class="text-lg font-semibold mb-4">🕒 Recent Activity</h3>
                <div id="user-activity" class="divide-y divide-gray-200 text-sm"></div>
                <div class="flex justify-center mt-6">
                    <nav id="recentActivityPagination" class="inline-flex items-center space-x-2"></nav>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    (function () {
        const base = window.location.pathname.replace(/\/$/, '');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // ====== Pagination Helpers ======
        let currentPageAct = 1;
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

        // ====== Borrow Requests ======
        let requestsData = [];
        async function loadRequests() {
            const res = await fetch(`${base}/my-requests`);
            if (!res.ok) return;
            requestsData = await res.json();
            renderRequests();
        }

        function renderRequests() {
            const tbody = document.getElementById("my-requests-body");
            if (!Array.isArray(requestsData) || requestsData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-gray-500">You have no borrow requests yet.</td></tr>`;
                return;
            }
            tbody.innerHTML = "";
            const pageData = paginate(requestsData, currentPageReq);
            pageData.forEach(r => {
                const itemsHtml = (r.items || []).map(i => `<div>${i.item?.name ?? 'Unknown'} × ${i.quantity}</div>`).join('');
                const borrowDate = r.borrow_date ? new Date(r.borrow_date).toLocaleDateString() : '-';
                const returnDate = r.return_date ? new Date(r.return_date).toLocaleDateString() : '-';
                tbody.innerHTML += `
                    <tr class="border-t">
                        <td class="py-3">${r.id}</td>
                        <td class="py-3">${itemsHtml}</td>
                        <td class="py-3"><div>${borrowDate}</div><div class="text-xs opacity-70">→ ${returnDate}</div></td>
                        <td class="py-3">${r.status ? r.status.charAt(0).toUpperCase()+r.status.slice(1) : ''}</td>
                    </tr>`;
            });
            renderPagination(requestsData.length, "myBorrowPagination", currentPageReq, (p)=>{currentPageReq=p;}, renderRequests);
        }

        // ====== Activity ======
        let activityData = [];
        async function loadActivity() {
            const res = await fetch(`${base}/activity`);
            if (!res.ok) return;
            activityData = await res.json();
            renderActivity();
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
        const ctx = document.getElementById("personalBorrowChart").getContext("2d");
        const personalEmptyEl = document.getElementById("personalBorrowEmpty");

        const personalChart = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Items borrowed', data: [], borderColor: 'rgba(99,102,241,1)', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.25 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        async function fetchTrends(range) {
            try {
                const res = await fetch(`${base}/borrow-trends?range=${encodeURIComponent(range)}`);
                if (!res.ok) return;
                const data = await res.json(); // { "Jan": 2, "Feb": 4, ... }
                const labels = Object.keys(data || {});
                const values = Object.values(data || {});
                personalChart.data.labels = labels;
                personalChart.data.datasets[0].data = values;
                personalChart.update();
                if (!labels.length || values.reduce((a,b)=>a+Number(b||0),0) === 0) {
                    personalEmptyEl.classList.remove('hidden');
                } else {
                    personalEmptyEl.classList.add('hidden');
                }
            } catch (err) {
                console.error("Trend fetch error", err);
                personalEmptyEl.textContent = "Unable to load trend data.";
                personalEmptyEl.classList.remove("hidden");
            }
        }

        document.getElementById("personalTrendFilter").addEventListener("change", e => {
            fetchTrends(e.target.value);
        });

        // Init
        (async function init(){
            await Promise.all([loadActivity(), fetchTrends("month") ]);
        })();

        // ====== SHOW LOGIN TOAST (per-dashboard) ======
       // ====== SHOW LOGIN TOAST (per-dashboard, editor-friendly) ======
document.addEventListener('DOMContentLoaded', function () {
    try {
        // read server-provided flash from the DOM-safe location (#toast dataset)
        var toastEl = document.getElementById('toast');
        var loginMessage = '';
        var status = '';

        if (toastEl && toastEl.dataset) {
            // dataset attributes automatically become camelCased: data-login-message -> dataset.loginMessage
            loginMessage = toastEl.dataset.loginMessage || '';
            status = toastEl.dataset.loginStatus || '';
        }

        // Only show when status is 'login-success' or a loginMessage exists
        if (loginMessage && (status === 'login-success' || loginMessage.length)) {
            // instruct the global toast positioning to be centered in the viewport
            if (toastEl) {
                try { toastEl.setAttribute('data-center', 'true'); } catch (e) {}
            }

            if (typeof showToast === 'function') {
                showToast(loginMessage, 'success');
            } else {
                // fallback if showToast not available
                var ph = document.querySelector('.page-header');
                if (ph) {
                    var fallback = document.createElement('div');
                    fallback.className = 'card p-4 mb-4';
                    fallback.innerText = loginMessage;
                    ph.parentNode.insertBefore(fallback, ph.nextSibling);
                    setTimeout(function () { try { fallback.remove(); } catch (e) {} }, 3800);
                }
            }
        }
    } catch (err) {
        // keep silent in production but log while debugging
        if (console && console.error) console.error('Dashboard toast init error', err);
    }
});


    })();
    </script>

    <style>
      .card { border-radius: .75rem; box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</x-app-layout>
