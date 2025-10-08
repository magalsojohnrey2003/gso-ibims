// resources/js/admin-dashboard.js
// Admin Dashboard JS (charts, filters, activity log pagination)
// Uses Chart.js as an ES module (installed via npm)

// import Chart.js module and register components
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

// ----------------------------
// DOM-ready initialization
// ----------------------------
document.addEventListener('DOMContentLoaded', function () {

  // --- parse JSON block emitted by Blade (if present) ---
  var dashboardData = {};
  try {
    var dataEl = document.getElementById('dashboard-data');
    if (dataEl && dataEl.textContent) {
      dashboardData = JSON.parse(dataEl.textContent || '{}') || {};
    }
  } catch (e) {
    console.warn('Failed to parse #dashboard-data JSON', e);
    dashboardData = {};
  }

  // Guard: only continue if relevant DOM elements exist
  var hasBorrowCanvas = !!document.getElementById('borrowTrendsChart');
  var hasItemCanvas = !!document.getElementById('itemUsageChart');
  var hasActivity = !!document.getElementById('activity-log');
  if (!hasBorrowCanvas && !hasItemCanvas && !hasActivity) return;

  // Extract initial data (safe defaults)
  var initialBorrowLabels = Array.isArray(dashboardData.borrowLabels) ? dashboardData.borrowLabels : [];
  var initialBorrowData   = Array.isArray(dashboardData.borrowData)   ? dashboardData.borrowData   : [];
  var initialItemLabels   = Array.isArray(dashboardData.itemLabels)   ? dashboardData.itemLabels   : [];
  var initialItemData     = Array.isArray(dashboardData.itemData)     ? dashboardData.itemData     : [];
  var endpoints = (dashboardData.endpoints && typeof dashboardData.endpoints === 'object') ? dashboardData.endpoints : {};

  // Theme helper
  function getThemeColors() {
    if (document.body.classList.contains('theme-dark')) {
      return { text: '#f3f4f6', grid: '#374151' };
    } else if (document.body.classList.contains('theme-original')) {
      return { text: '#2d0a4e', grid: '#e9d5ff' };
    }
    return { text: '#1f2937', grid: '#e5e7eb' };
  }

  // Chart options factory
  function chartOptions() {
    var c = getThemeColors();
    return {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { ticks: { color: c.text }, grid: { color: c.grid } },
        y: { ticks: { color: c.text }, grid: { color: c.grid }, beginAtZero: true }
      },
      plugins: { legend: { labels: { color: c.text } } }
    };
  }

  // toggle empty state helper
  function toggleEmptyState(labels, data, elId) {
    try {
      var el = document.getElementById(elId);
      if (!el) return;
      var isEmpty = (!labels || !labels.length) || (!data || !data.length) || data.reduce(function (a, b) { return a + Number(b || 0); }, 0) === 0;
      if (isEmpty) el.classList.remove('hidden'); else el.classList.add('hidden');
    } catch (e) { /* ignore */ }
  }

  // --- Borrow Trends Chart ---
  var borrowChart = null;
  if (hasBorrowCanvas) {
    try {
      var borrowCanvas = document.getElementById('borrowTrendsChart');
      if (borrowCanvas && borrowCanvas.getContext) {
        var borrowCtx = borrowCanvas.getContext('2d');
        borrowChart = new Chart(borrowCtx, {
          type: 'line',
          data: {
            labels: initialBorrowLabels,
            datasets: [{
              label: 'Borrow Requests',
              data: initialBorrowData,
              borderColor: 'rgba(59,130,246,1)',
              backgroundColor: 'rgba(59,130,246,0.18)',
              fill: true,
              tension: 0.3
            }]
          },
          options: chartOptions()
        });
      }
    } catch (err) {
      console.error('Failed to init borrowTrendsChart', err);
      borrowChart = null;
    }
  }
  toggleEmptyState(initialBorrowLabels, initialBorrowData, 'borrowTrendsEmpty');

  // trend filter handler
  var trendFilterEl = document.getElementById('trendFilter');
  if (trendFilterEl) {
    trendFilterEl.addEventListener('change', function (e) {
      var range = e.target.value;
      updateBorrowTrends(range, borrowChart, endpoints.borrowTrends || null);
    });
  }

  // --- Most Borrowed Items Chart ---
  var usageChart = null;
  if (hasItemCanvas) {
    try {
      var usageCanvas = document.getElementById('itemUsageChart');
      if (usageCanvas && usageCanvas.getContext) {
        var usageCtx = usageCanvas.getContext('2d');
        usageChart = new Chart(usageCtx, {
          type: 'bar',
          data: {
            labels: initialItemLabels,
            datasets: [{
              label: 'Times Borrowed',
              data: initialItemData,
              backgroundColor: 'rgba(34,197,94,0.6)'
            }]
          },
          options: chartOptions()
        });
      }
    } catch (err) {
      console.error('Failed to init itemUsageChart', err);
      usageChart = null;
    }
  }
  toggleEmptyState(initialItemLabels, initialItemData, 'itemUsageEmpty');

  var itemCategoryFilterEl = document.getElementById('itemCategoryFilter');
  if (itemCategoryFilterEl) {
    itemCategoryFilterEl.addEventListener('change', function (e) {
      var cat = e.target.value;
      updateMostBorrowed(cat, usageChart, endpoints.mostBorrowed || null);
    });
  }

  // --- Activity Log (client-side pagination) ---
  var activityData = [];
  var currentPage = 1;
  var perPage = 3;

  function renderActivity() {
    var container = document.getElementById('activity-log');
    if (!container) return;
    container.innerHTML = '';

    var pageData = paginate(activityData, currentPage, perPage);
    if (!pageData.length) {
      container.innerHTML = "<p class='text-gray-500'>No recent activity.</p>";
      renderPagination(activityData.length);
      return;
    }

    pageData.forEach(function (log) {
      var el = document.createElement('div');
      el.className = 'py-3';
      var user = log.user || 'Unknown';
      var time = log.time || '';
      var items = log.items ? ' — ' + escapeHtml(log.items) : '';
      var action = log.action ? escapeHtml(log.action) : '';
      el.innerHTML = "\n                <div class=\"flex justify-between\">\n                    <div>\n                        <span class=\"font-semibold\">" + escapeHtml(user) + "</span>\n                        <span class=\"opacity-70 text-xs ml-2\">(" + escapeHtml(time) + ")</span>\n                        <div class=\"text-sm mt-1\">" + action + (items || '') + "</div>\n                    </div>\n                </div>";
      container.appendChild(el);
    });

    renderPagination(activityData.length);
  }

  async function loadActivity() {
    if (!endpoints.activity) {
      activityData = [];
      renderActivity();
      return;
    }
    try {
      var res = await fetch(endpoints.activity, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('Failed to fetch activity');
      var json = await res.json();
      var items = [];
      if (Array.isArray(json)) items = json;
      else if (json && Array.isArray(json.data)) items = json.data;
      else items = json.items || [];
      activityData = items;
      currentPage = 1;
      renderActivity();
    } catch (err) {
      console.error(err);
      var aContainer = document.getElementById('activity-log');
      if (aContainer) aContainer.innerHTML = "<p class='text-gray-500'>Unable to load activity.</p>";
    }
  }

  if (endpoints.activity) {
    loadActivity();
    setInterval(loadActivity, 30000);
  }

  // Pagination builder
  function renderPagination(total) {
    var nav = document.getElementById("activityPaginationNav");
    if (!nav) return;
    nav.innerHTML = "";
    var totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) return;

    function makeBtn(label, page, disabled, active) {
      if (disabled === void 0) { disabled = false; }
      if (active === void 0) { active = false; }
      var btn = document.createElement("button");
      btn.textContent = label;
      btn.className =
        "px-3 py-1 rounded-md text-sm transition " +
        (active
          ? "bg-purple-600 text-white"
          : "bg-gray-100 text-gray-700 hover:bg-purple-100") +
        (disabled ? " opacity-50 cursor-not-allowed" : "");
      if (!disabled) {
        btn.addEventListener("click", function () {
          currentPage = page;
          renderActivity();
          window.scrollTo(0, 0);
        });
      }
      return btn;
    }
    nav.appendChild(makeBtn("Prev", Math.max(1, currentPage - 1), currentPage === 1));
    for (var i = 1; i <= totalPages; i++) {
      nav.appendChild(makeBtn(i, i, false, i === currentPage));
    }
    nav.appendChild(makeBtn("Next", Math.min(totalPages, currentPage + 1), currentPage === totalPages));
  }

  // ----------------------------
  // Theme observer: update chart colors when theme changes
  var observer = new MutationObserver(function () { updateTheme(borrowChart, usageChart); });
  observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

  // End DOMContentLoaded handler
});

// ----------------------------
// Utility functions (shared)
// ----------------------------
async function updateBorrowTrends(range, chart, url) {
  var el = document.getElementById('borrowTrendsEmpty');
  try {
    var fetchUrl = url ? (url + (url.indexOf('?') === -1 ? '?' : '&') + "range=" + encodeURIComponent(range)) : ("/admin/dashboard/borrow-trends?range=" + encodeURIComponent(range));
    var res = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error();
    var data = await res.json();
    var labels = [];
    var values = [];
    if (Array.isArray(data)) {
      labels = data.map(function (d) { return d.label || d.name || ''; });
      values = data.map(function (d) { return d.value ?? d.total ?? 0; });
    } else if (data && typeof data === 'object') {
      labels = Object.keys(data);
      values = Object.values(data).map(function (v) { return Number(v || 0); });
    }
    if (chart) {
      chart.data.labels = labels;
      if (chart.data.datasets && chart.data.datasets[0]) chart.data.datasets[0].data = values;
      chart.update();
    }
    toggleEmptyState(labels, values, 'borrowTrendsEmpty');
  } catch (err) {
    if (el) {
      el.textContent = 'Unable to load data.';
      el.classList.remove('hidden');
    }
  }
}

async function updateMostBorrowed(cat, chart, url) {
  var el = document.getElementById('itemUsageEmpty');
  try {
    var fetchUrl = url ? (url + (url.indexOf('?') === -1 ? '?' : '&') + "category=" + encodeURIComponent(cat)) : (cat ? ("/admin/dashboard/most-borrowed?category=" + encodeURIComponent(cat)) : "/admin/dashboard/most-borrowed");
    var res = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error();
    var data = await res.json();
    var labels = (data || []).map ? (data || []).map(function (d) { return d.name || 'Unknown'; }) : [];
    var values = (data || []).map ? (data || []).map(function (d) { return Number(d.total || 0); }) : [];
    if (chart) {
      chart.data.labels = labels;
      if (chart.data.datasets && chart.data.datasets[0]) chart.data.datasets[0].data = values;
      chart.update();
    }
    toggleEmptyState(labels, values, 'itemUsageEmpty');
  } catch (err) {
    if (el) {
      el.textContent = 'Unable to load data.';
      el.classList.remove('hidden');
    }
  }
}

function paginate(arr, page, perPage) {
  if (!Array.isArray(arr)) return [];
  var start = (page - 1) * perPage;
  return arr.slice(start, start + perPage);
}

function updateTheme(chart1, chart2) {
  var colors = (typeof getThemeColors === 'function') ? getThemeColors() : { text: '#1f2937', grid: '#e5e7eb' };
  [chart1, chart2].forEach(function (chart) {
    if (!chart) return;
    try {
      if (chart.options.scales && chart.options.scales.x) chart.options.scales.x.ticks.color = colors.text;
      if (chart.options.scales && chart.options.scales.y) chart.options.scales.y.ticks.color = colors.text;
      if (chart.options.scales && chart.options.scales.x && chart.options.scales.x.grid) chart.options.scales.x.grid.color = colors.grid;
      if (chart.options.scales && chart.options.scales.y && chart.options.scales.y.grid) chart.options.scales.y.grid.color = colors.grid;
      if (chart.options.plugins && chart.options.plugins.legend) chart.options.plugins.legend.labels.color = colors.text;
      chart.update();
    } catch (e) { /* ignore */ }
  });
}

// small escape helper
function escapeHtml(unsafe) {
  if (unsafe === null || unsafe === undefined) return '';
  return String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
