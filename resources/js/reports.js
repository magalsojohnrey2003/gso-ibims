// resources/js/reports.js
import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const reportTypeEl = document.getElementById('reportType');
    const periodEl = document.getElementById('period');
    const chartTypeEl = document.getElementById('chartType');
    const generateBtn = document.getElementById('generateBtn');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const downloadXlsxBtn = document.getElementById('downloadXlsxBtn');
    const customRangeRow = document.getElementById('customRangeRow');
    const fromEl = document.getElementById('from');
    const toEl = document.getElementById('to');
    const thresholdEl = document.getElementById('threshold');
    const genSpinner = document.getElementById('genSpinner');

    const reportTitleEl = document.getElementById('reportTitle');
    const headEl = document.getElementById('reportTableHead');
    const bodyEl = document.getElementById('reportTableBody');
    const summaryBoxes = document.getElementById('summaryBoxes');
    const canvasEl = document.getElementById('reportChart');
    const chartMessage = document.getElementById('chartMessage');
    const chartMessageTitle = document.getElementById('chartMessageTitle');
    const chartMessageBody = document.getElementById('chartMessageBody');

    // Routes & CSRF
    const dataUrl = window.reportRoutes?.data ?? '';
    const exportPdfUrl = window.reportRoutes?.pdf ?? '';
    const exportXlsxUrl = window.reportRoutes?.xlsx ?? '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // quick bail if not on page
    if (!reportTypeEl || !periodEl || !chartTypeEl || !generateBtn || !canvasEl || !headEl || !bodyEl) {
        // Not the reports page or DOM not present
        return;
    }

    // state
    let chart = null;
    let lastFetched = { columns: [], rows: [], meta: {}, extra: {} };

    const palette = [
        '#2563EB', '#10B981', '#F59E0B', '#EF4444', '#7C3AED',
        '#06B6D4', '#F97316', '#8B5CF6', '#84CC16', '#EC4899'
    ];

    // ---------- helpers ----------
    const slugify = (s = '') => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '');
    const prettify = (k = '') => String(k)
        .replace(/_/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .split(' ')
        .map(w => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');

    function normalizeRows(rows, columns) {
        if (!Array.isArray(rows)) return [];
        return rows.map(row => {
            if (Array.isArray(row)) return row;
            if (row && typeof row === 'object') {
                // If columns are keys that match object keys, use them.
                if (Array.isArray(columns) && columns.length > 0) {
                    // attempt best-effort extraction using columns as keys or labels
                    const out = [];
                    for (const c of columns) {
                        // try direct key first
                        if (Object.prototype.hasOwnProperty.call(row, c)) {
                            out.push(row[c]);
                        } else {
                            // try matching by normalized slug of keys vs column label
                            const matchedKey = Object.keys(row).find(k => slugify(k) === slugify(c) || slugify(k) === slugify(String(c).replace(/\s+/g, '')));
                            if (matchedKey) {
                                out.push(row[matchedKey]);
                            } else {
                                // fallback: try by index order (not ideal)
                                out.push(row[c] ?? '');
                            }
                        }
                    }
                    return out;
                }
                // fallback: map object values in natural key order
                return Object.values(row);
            }
            return [row];
        });
    }

    /**
     * Build a reliable set of column labels and keys for object rows.
     * - If server provided columns and rows are objects: try to map labels -> object keys (via slug matching)
     * - If server didn't provide columns: infer from object keys or array length
     *
     * Returns: { labels: string[], keys: (string[]|null), rowType: 'array'|'object' }
     * If keys === null then rows are arrays (or we will read by index)
     */
    function deriveColumnsAndKeys(columnsFromServer = [], rows = []) {
        // no rows -> rely on server columns if present
        if (!Array.isArray(rows) || rows.length === 0) {
            return {
                labels: Array.isArray(columnsFromServer) && columnsFromServer.length ? columnsFromServer : [],
                keys: null,
                rowType: 'array'
            };
        }

        const first = rows[0];

        // rows are arrays
        if (Array.isArray(first)) {
            const colCount = Math.max(first.length, (Array.isArray(columnsFromServer) ? columnsFromServer.length : 0));
            const labels = (Array.isArray(columnsFromServer) && columnsFromServer.length)
                ? columnsFromServer
                : Array.from({ length: colCount }, (_, i) => `Column ${i + 1}`);
            return { labels, keys: null, rowType: 'array' };
        }

        // rows are objects
        if (typeof first === 'object') {
            const rowKeys = Object.keys(first);
            // If server provided columns, attempt to map them to rowKeys
            if (Array.isArray(columnsFromServer) && columnsFromServer.length) {
                const keySlugs = rowKeys.map(k => slugify(k));
                const mapping = columnsFromServer.map(label => {
                    const ls = slugify(label);
                    // exact slug match
                    let idx = keySlugs.indexOf(ls);
                    if (idx === -1) {
                        // try partial matching (contains)
                        idx = keySlugs.findIndex(ks => ks.includes(ls) || ls.includes(ks));
                    }
                    return idx === -1 ? null : rowKeys[idx];
                });

                const mappedCount = mapping.filter(Boolean).length;
                // if at least one mapped, use mapping (and fallbacks)
                if (mappedCount > 0) {
                    const labels = columnsFromServer.map((lab, i) => {
                        if (mapping[i]) return lab ?? prettify(mapping[i]);
                        // fallback label: if there's a rowKey at same index use prettified key, else keep label
                        const fallbackKey = rowKeys[i] ?? null;
                        return lab ?? (fallbackKey ? prettify(fallbackKey) : `Column ${i + 1}`);
                    });
                    return { labels, keys: mapping, rowType: 'object' };
                }
                // else mapping didn't work well -> fall back to rowKeys
                return {
                    labels: rowKeys.map(k => prettify(k)),
                    keys: rowKeys,
                    rowType: 'object'
                };
            }

            // no server columns -> use object keys
            return {
                labels: rowKeys.map(k => prettify(k)),
                keys: rowKeys,
                rowType: 'object'
            };
        }

        // unknown -> fallback
        return {
            labels: Array.isArray(columnsFromServer) ? columnsFromServer : [],
            keys: null,
            rowType: 'array'
        };
    }

    // ---------- TABLE RENDERING (robust) ----------
    function renderTable(columns = [], rows = []) {
        // reset
        headEl.innerHTML = '';
        bodyEl.innerHTML = '';

        // derive labels/keys
        const { labels, keys, rowType } = deriveColumnsAndKeys(columns, rows);

        // If no labels and no rows -> no data
        if ((!labels || labels.length === 0) && (!rows || rows.length === 0)) {
            headEl.innerHTML = '<tr><th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">No data</th></tr>';
            bodyEl.innerHTML = '';
            return;
        }

        // Build header row
        const trHead = document.createElement('tr');
        for (const lbl of labels) {
            const th = document.createElement('th');
            th.className = 'px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase';
            th.textContent = lbl ?? '';
            trHead.appendChild(th);
        }
        headEl.appendChild(trHead);

        // Build body rows
        if (!Array.isArray(rows) || rows.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = Math.max(1, labels.length);
            td.className = 'px-6 py-4 text-sm text-gray-500';
            td.textContent = 'No records found.';
            tr.appendChild(td);
            bodyEl.appendChild(tr);
            return;
        }

        const normalized = normalizeRows(rows, labels.length ? labels : columns);

        normalized.forEach((r, i) => {
            const tr = document.createElement('tr');
            tr.className = (i % 2 === 1) ? 'bg-gray-50 dark:bg-gray-800' : '';
            // r is now an array of values aligned to labels (normalizeRows attempts to align)
            for (const cell of r) {
                const td = document.createElement('td');
                td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100';
                td.textContent = (cell === null || typeof cell === 'undefined') ? '' : String(cell);
                tr.appendChild(td);
            }
            bodyEl.appendChild(tr);
        });
    }

    // ---------- SUMMARY ----------
    function renderSummary(meta = {}, extra = {}) {
        summaryBoxes.innerHTML = '';
        const rows = [
            { label: 'Report', value: meta.title ?? (reportTypeEl.selectedOptions[0]?.text ?? '-') },
            { label: 'Period', value: (meta.start && meta.end) ? `${meta.start} → ${meta.end}` : (meta.start ?? '-') },
            { label: 'Generated', value: meta.generated_at ?? '-' }
        ];

        if (extra.kpis && Array.isArray(extra.kpis)) {
            extra.kpis.forEach(k => rows.push({ label: k.label, value: k.value }));
        }

        for (const r of rows) {
            const box = document.createElement('div');
            box.className = 'p-3 border rounded-md bg-gray-50 dark:bg-gray-700';
            box.innerHTML = `<div class="text-xs text-gray-500 dark:text-gray-300">${r.label}</div>
                             <div class="text-lg font-semibold text-gray-800 dark:text-gray-100">${r.value}</div>`;
            summaryBoxes.appendChild(box);
        }
    }

    // ---------- Chart helpers (unchanged) ----------
    function prepareChartData(columns = [], rows = []) {
        const normalized = normalizeRows(rows, columns);
        if (!Array.isArray(normalized) || normalized.length === 0) return { labels: [], values: [] };

        const colCount = Math.max(...normalized.map(r => Array.isArray(r) ? r.length : 0));
        const numericScore = Array.from({ length: colCount }, () => 0);
        normalized.forEach(r => {
            for (let i = 0; i < colCount; i++) {
                const raw = (r[i] ?? '');
                const candidate = String(raw).replace(/,/g, '').trim();
                const n = Number(candidate);
                if (candidate !== '' && isFinite(n)) numericScore[i]++;
            }
        });

        let valueIndex = numericScore.indexOf(Math.max(...numericScore));
        if (Math.max(...numericScore) === 0) valueIndex = Math.min(colCount - 1, 1);

        let labelIndex = 0;
        let minScore = Infinity;
        for (let i = 0; i < colCount; i++) {
            if (numericScore[i] < minScore) { minScore = numericScore[i]; labelIndex = i; }
        }

        const pairs = [];
        normalized.forEach(r => {
            const rawLabel = (r[labelIndex] ?? '');
            const rawVal = (r[valueIndex] ?? '');
            const candidate = String(rawVal).replace(/,/g, '').trim();
            const n = Number(candidate);
            if (candidate !== '' && isFinite(n)) {
                pairs.push({ label: String(rawLabel), value: n });
            }
        });

        // fallback: try second column
        if (pairs.length === 0 && normalized.length > 0) {
            const tryIndex = Math.min(1, colCount - 1);
            normalized.forEach(r => {
                const n = Number(String((r[tryIndex] ?? '')).replace(/,/g, '').trim());
                if (isFinite(n)) pairs.push({ label: String((r[0] ?? '')), value: n });
            });
        }

        return { labels: pairs.map(p => p.label), values: pairs.map(p => p.value) };
    }

    function hexToRgba(hex, alpha = 1) {
        const h = hex.replace('#','');
        const bigint = parseInt(h, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r},${g},${b},${alpha})`;
    }

    function renderChart(columns = [], rows = [], type = 'bar') {
        if (!canvasEl) return;

        const prepared = prepareChartData(columns, rows);
        const labels = prepared.labels;
        const values = prepared.values;

        if (!Array.isArray(rows) || rows.length === 0) {
            if (chart) { chart.destroy(); chart = null; }
            canvasEl.style.display = 'none';
            chartMessage.classList.remove('hidden');
            chartMessageTitle.textContent = 'No data for chart';
            chartMessageBody.textContent = 'There are no rows for this report/period.';
            return;
        }

        if (!labels.length || !values.length) {
            if (chart) { chart.destroy(); chart = null; }
            canvasEl.style.display = 'none';
            chartMessage.classList.remove('hidden');
            chartMessageTitle.textContent = 'No numeric data to plot';
            chartMessageBody.textContent = 'The table contains data but nothing numeric was found to plot. Try another report or adjust the period/threshold.';
            return;
        }

        chartMessage.classList.add('hidden');
        canvasEl.style.display = '';

        const colors = labels.map((_, i) => palette[i % palette.length]);

        if (chart) chart.destroy();

        let datasets;
        if (type === 'pie' || type === 'doughnut') {
            datasets = [{
                label: reportTypeEl.selectedOptions[0]?.text ?? 'Report',
                data: values,
                backgroundColor: colors,
                borderColor: colors.map(c => '#ffffff'),
                borderWidth: 1,
            }];
        } else {
            datasets = [{
                label: reportTypeEl.selectedOptions[0]?.text ?? 'Report',
                data: values,
                backgroundColor: hexToRgba(palette[0], 0.85),
                borderColor: palette[0],
                borderWidth: 1,
                tension: 0.3,
                borderRadius: 6,
            }];
        }

        const isDark = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');
        const textColor = isDark ? '#E5E7EB' : '#0F172A';

        const config = {
            type: (['pie','doughnut'].includes(type)) ? type : (type === 'line' ? 'line' : 'bar'),
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: ['pie','doughnut'].includes(type) },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: (['pie','doughnut'].includes(type)) ? {} : {
                    x: { ticks: { color: textColor }, grid: { display: false } },
                    y: { ticks: { color: textColor, precision: 0 }, beginAtZero: true }
                }
            }
        };

        chart = new Chart(canvasEl.getContext('2d'), config);
    }

    // ---------- fetch ----------
    async function fetchReport(showSpinner = true) {
        if (!dataUrl) {
            console.error('reports.js: dataUrl missing. Make sure window.reportRoutes.data set in Blade.');
            return;
        }

        try {
            if (showSpinner) genSpinner?.classList.remove('hidden');
            generateBtn.disabled = true;
            downloadPdfBtn.disabled = true;
            downloadXlsxBtn.disabled = true;

            const params = buildParams();

            const res = await fetch(dataUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(params)
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({ message: 'Failed to fetch report' }));
                window.showToast('error', err.message || 'Failed to fetch report');
                return;
            }

            const json = await res.json();

            lastFetched = { columns: json.columns ?? [], rows: json.rows ?? [], meta: json.meta ?? {}, extra: json.extra ?? {} };

            const periodLabel = (periodEl.value === 'custom') ? `${params.from || ''} → ${params.to || ''}` : periodEl.options[periodEl.selectedIndex].text;
            reportTitleEl.textContent = `${reportTypeEl.selectedOptions[0].text} (${periodLabel})`;

            // render table & chart & summary
            renderTable(lastFetched.columns, lastFetched.rows);
            renderChart(lastFetched.columns, lastFetched.rows, chartTypeEl.value);
            renderSummary(lastFetched.meta, lastFetched.extra);
        } catch (err) {
            console.error('fetchReport error', err);
            window.showToast('error', 'Error fetching report. Please try again.');
        } finally {
            genSpinner?.classList.add('hidden');
            generateBtn.disabled = false;
            downloadPdfBtn.disabled = false;
            downloadXlsxBtn.disabled = false;
        }
    }

    function buildParams() {
        return {
            report_type: reportTypeEl.value,
            period: periodEl.value,
            from: fromEl?.value || '',
            to: toEl?.value || '',
            threshold: thresholdEl?.value || '',
        };
    }

    function toQuery(params) {
        return new URLSearchParams(params).toString();
    }

    function exportFile(kind = 'pdf') {
        const params = buildParams();
        const baseUrl = kind === 'pdf' ? exportPdfUrl : exportXlsxUrl;
        if (!baseUrl) {
            window.showToast('error', 'Export URL not configured.');
            return;
        }
        window.location.href = baseUrl + '?' + toQuery(params);
    }

    // Events
    chartTypeEl.addEventListener('change', () => {
        if (!lastFetched.columns || !lastFetched.rows) return;
        renderChart(lastFetched.columns, lastFetched.rows, chartTypeEl.value);
    });

    generateBtn.addEventListener('click', (e) => { e.preventDefault(); fetchReport(true); });
    downloadPdfBtn.addEventListener('click', (e) => { e.preventDefault(); exportFile('pdf'); });
    downloadXlsxBtn.addEventListener('click', (e) => { e.preventDefault(); exportFile('xlsx'); });

    // initial load (no spinner)
    fetchReport(false);
});
