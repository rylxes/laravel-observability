<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Observability Dashboard</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap');

        :root {
            --bg-primary: #f2efe7;
            --bg-card: #fffdf7;
            --bg-soft: #f5f1e8;
            --ink-primary: #1b2a2f;
            --ink-muted: #52656b;
            --accent: #0c8d87;
            --accent-dark: #066863;
            --warm: #ef7e2f;
            --danger: #b42318;
            --ok: #157f45;
            --border: #d8d0c2;
            --shadow: rgba(12, 30, 35, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: radial-gradient(circle at 0% 0%, #fff9ec 0%, #f2efe7 45%, #e8efe9 100%);
            color: var(--ink-primary);
            font-family: 'Space Grotesk', sans-serif;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .shell {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            gap: 1rem;
        }

        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 10px 30px var(--shadow);
            overflow: hidden;
        }

        .hero {
            position: relative;
            padding: 1.5rem;
            border-radius: 20px;
            background:
                linear-gradient(120deg, rgba(12, 141, 135, 0.22), rgba(239, 126, 47, 0.18)),
                linear-gradient(180deg, #fffdf7 0%, #f6f2e9 100%);
            border: 1px solid rgba(12, 141, 135, 0.35);
        }

        .hero::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -70px;
            width: 210px;
            height: 210px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(12, 141, 135, 0.2), rgba(12, 141, 135, 0));
            pointer-events: none;
        }

        .hero-top {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            z-index: 1;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(1.35rem, 3vw, 1.9rem);
        }

        .hero p {
            margin: 0.35rem 0 0;
            color: var(--ink-muted);
            max-width: 68ch;
        }

        .control-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #9aa9ad;
        }

        .dot.ok {
            background: var(--ok);
        }

        .dot.warn {
            background: var(--warm);
        }

        .dot.bad {
            background: var(--danger);
        }

        .btn,
        select {
            font-family: inherit;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--ink-primary);
            border-radius: 10px;
            padding: 0.45rem 0.75rem;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .btn.primary {
            background: linear-gradient(120deg, var(--accent), var(--accent-dark));
            color: #fff;
            border: 0;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.75rem;
        }

        .metric {
            padding: 1rem;
            background: var(--bg-soft);
            border: 1px solid var(--border);
            border-radius: 12px;
            display: grid;
            gap: 0.3rem;
            transform: translateY(6px);
            opacity: 0;
            animation: pop-in 0.45s ease forwards;
        }

        .metric:nth-child(2) { animation-delay: 0.04s; }
        .metric:nth-child(3) { animation-delay: 0.08s; }
        .metric:nth-child(4) { animation-delay: 0.12s; }
        .metric:nth-child(5) { animation-delay: 0.16s; }
        .metric:nth-child(6) { animation-delay: 0.2s; }

        .metric .label {
            color: var(--ink-muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric .value {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .body {
            padding: 1rem;
            display: grid;
            gap: 1rem;
        }

        .grid-2 {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            margin-bottom: 0.75rem;
        }

        .section-head h2 {
            margin: 0;
            font-size: 1rem;
        }

        .hint {
            margin: 0;
            color: var(--ink-muted);
            font-size: 0.82rem;
            font-family: 'IBM Plex Mono', monospace;
        }

        .list,
        .table-wrap {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .list {
            padding: 0.8rem;
            display: grid;
            gap: 0.5rem;
        }

        .bottleneck {
            border: 1px solid var(--border);
            border-left-width: 5px;
            border-radius: 10px;
            padding: 0.65rem 0.7rem;
            background: #fffdf7;
        }

        .bottleneck.warning {
            border-left-color: var(--warm);
        }

        .bottleneck.error,
        .bottleneck.critical {
            border-left-color: var(--danger);
        }

        .bottleneck.info {
            border-left-color: var(--accent);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        th,
        td {
            padding: 0.55rem 0.65rem;
            border-bottom: 1px solid #ede8dc;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f9f6ef;
            color: var(--ink-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .mono {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
        }

        .status-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 0.15rem 0.5rem;
            font-size: 0.74rem;
            border: 1px solid var(--border);
            background: #fff;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }

        .status-pill.error,
        .status-pill.critical {
            border-color: rgba(180, 35, 24, 0.35);
            color: #8f1b12;
            background: #fff2f1;
        }

        .status-pill.warning {
            border-color: rgba(239, 126, 47, 0.35);
            color: #985520;
            background: #fff7ee;
        }

        .status-pill.info {
            border-color: rgba(12, 141, 135, 0.35);
            color: #066863;
            background: #ecfbf9;
        }

        .empty {
            padding: 1rem;
            color: var(--ink-muted);
            text-align: center;
            font-size: 0.88rem;
        }

        .footer-note {
            color: var(--ink-muted);
            font-size: 0.8rem;
            font-family: 'IBM Plex Mono', monospace;
            text-align: right;
            padding-top: 0.25rem;
        }

        @media (min-width: 920px) {
            .grid-2 {
                grid-template-columns: 1.1fr 0.9fr;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 0.9rem;
            }

            .hero,
            .body {
                padding: 0.85rem;
            }

            table {
                font-size: 0.82rem;
            }

            th,
            td {
                padding: 0.5rem;
            }
        }

        @keyframes pop-in {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <div class="hero-top">
                <div>
                    <h1>Observability Control Room</h1>
                    <p>Live view of latency, errors, slow paths, and active alerts. API endpoints stay available for your internal integrations.</p>
                </div>
                <div class="control-row">
                    <span class="badge" id="health-badge"><span class="dot" id="health-dot"></span><span id="health-text">checking...</span></span>
                    <label class="badge" for="days-select">Window
                        <select id="days-select" style="margin-left: 0.45rem;">
                            <option value="1">24h</option>
                            <option value="7">7d</option>
                            <option value="30">30d</option>
                        </select>
                    </label>
                    <button type="button" class="btn primary" id="refresh-btn">Refresh now</button>
                </div>
            </div>
        </section>

        <section class="panel body">
            <div class="metrics" id="metrics-grid">
                <article class="metric"><span class="label">Requests</span><span class="value" id="m-total">-</span></article>
                <article class="metric"><span class="label">Avg Latency</span><span class="value" id="m-avg-latency">-</span></article>
                <article class="metric"><span class="label">P95 Latency</span><span class="value" id="m-p95">-</span></article>
                <article class="metric"><span class="label">P99 Latency</span><span class="value" id="m-p99">-</span></article>
                <article class="metric"><span class="label">Avg Memory</span><span class="value" id="m-memory">-</span></article>
                <article class="metric"><span class="label">Error Rate</span><span class="value" id="m-error-rate">-</span></article>
            </div>

            <div class="grid-2">
                <section>
                    <div class="section-head">
                        <h2>Route Performance</h2>
                        <p class="hint" id="route-count">Top routes by traffic</p>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Route</th>
                                    <th>Requests</th>
                                    <th>Avg</th>
                                    <th>Error Rate</th>
                                </tr>
                            </thead>
                            <tbody id="routes-body">
                                <tr><td colspan="4" class="empty">Loading route metrics...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <div class="section-head">
                        <h2>Bottlenecks</h2>
                        <p class="hint">Automatic threshold findings</p>
                    </div>
                    <div class="list" id="bottlenecks-list">
                        <div class="empty">No bottlenecks detected yet.</div>
                    </div>
                </section>
            </div>

            <section>
                <div class="section-head">
                    <h2>Recent Traces</h2>
                    <p class="hint">Latest 25 requests</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Latency</th>
                                <th>Queries</th>
                                <th>At</th>
                            </tr>
                        </thead>
                        <tbody id="traces-body">
                            <tr><td colspan="6" class="empty">Loading traces...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <div class="section-head">
                    <h2>Alerts</h2>
                    <p class="hint">Latest 30 alerts</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Severity</th>
                                <th>Title</th>
                                <th>Source</th>
                                <th>Created</th>
                                <th>State</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="alerts-body">
                            <tr><td colspan="6" class="empty">Loading alerts...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="footer-note" id="last-sync">last sync: pending...</p>
        </section>
    </div>

    <script>
        const endpoints = @json($apiEndpoints);
        const refreshIntervalMs = {{ (int) $refreshIntervalSeconds }} * 1000;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const state = {
            loading: false,
            timer: null,
        };

        const metricMap = {
            total_requests: document.getElementById('m-total'),
            avg_response_time_ms: document.getElementById('m-avg-latency'),
            p95_response_time_ms: document.getElementById('m-p95'),
            p99_response_time_ms: document.getElementById('m-p99'),
            avg_memory_mb: document.getElementById('m-memory'),
            error_rate: document.getElementById('m-error-rate'),
        };

        const refreshButton = document.getElementById('refresh-btn');
        const daysSelect = document.getElementById('days-select');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function fmtMs(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return '-';
            }
            return `${Number(value).toFixed(2)} ms`;
        }

        function fmtMb(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return '-';
            }
            return `${Number(value).toFixed(2)} MB`;
        }

        function fmtPercent(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return '-';
            }
            return `${Number(value).toFixed(2)}%`;
        }

        function fmtNumber(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return '-';
            }
            return Number(value).toLocaleString();
        }

        async function fetchJson(url, options = {}) {
            if (!url) {
                throw new Error('Endpoint is not available.');
            }

            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    ...options.headers,
                },
                ...options,
            });

            if (!response.ok) {
                throw new Error(`Request failed (${response.status})`);
            }

            return response.json();
        }

        function updateHealthBadge(status, detail = '') {
            const healthDot = document.getElementById('health-dot');
            const healthText = document.getElementById('health-text');

            healthDot.className = 'dot';

            if (status === 'healthy') {
                healthDot.classList.add('ok');
                healthText.textContent = 'healthy';
                return;
            }

            if (status === 'warning') {
                healthDot.classList.add('warn');
                healthText.textContent = detail || 'degraded';
                return;
            }

            healthDot.classList.add('bad');
            healthText.textContent = detail || 'unreachable';
        }

        function renderMetrics(metrics) {
            metricMap.total_requests.textContent = fmtNumber(metrics.total_requests);
            metricMap.avg_response_time_ms.textContent = fmtMs(metrics.avg_response_time_ms);
            metricMap.p95_response_time_ms.textContent = fmtMs(metrics.p95_response_time_ms);
            metricMap.p99_response_time_ms.textContent = fmtMs(metrics.p99_response_time_ms);
            metricMap.avg_memory_mb.textContent = fmtMb(metrics.avg_memory_mb);
            metricMap.error_rate.textContent = fmtPercent(metrics.error_rate);
        }

        function renderRoutes(routes) {
            const routeBody = document.getElementById('routes-body');
            const routeCount = document.getElementById('route-count');
            routeCount.textContent = `${routes.length} route(s) analyzed`;

            if (!routes.length) {
                routeBody.innerHTML = '<tr><td colspan="4" class="empty">No route metrics recorded yet.</td></tr>';
                return;
            }

            routeBody.innerHTML = routes.map((item) => `
                <tr>
                    <td class="mono">${escapeHtml(item.route || '-')}</td>
                    <td>${fmtNumber(item.requests)}</td>
                    <td>${fmtMs(item.avg_duration_ms)}</td>
                    <td>${fmtPercent(item.error_rate)}</td>
                </tr>
            `).join('');
        }

        function renderBottlenecks(bottlenecks) {
            const list = document.getElementById('bottlenecks-list');

            if (!bottlenecks.length) {
                list.innerHTML = '<div class="empty">No bottlenecks detected in this window.</div>';
                return;
            }

            list.innerHTML = bottlenecks.map((item) => {
                const targets = Array.isArray(item.data) && item.data.length
                    ? `<div class="mono">${escapeHtml(item.data.join(', '))}</div>`
                    : '';

                return `
                    <article class="bottleneck ${escapeHtml(item.severity || 'info')}">
                        <strong>${escapeHtml(item.message || item.type || 'Bottleneck')}</strong>
                        ${targets}
                    </article>
                `;
            }).join('');
        }

        function renderTraces(traces) {
            const traceBody = document.getElementById('traces-body');

            if (!traces.length) {
                traceBody.innerHTML = '<tr><td colspan="6" class="empty">No traces available yet.</td></tr>';
                return;
            }

            traceBody.innerHTML = traces.slice(0, 25).map((trace) => `
                <tr>
                    <td>${escapeHtml(trace.method || '-')}</td>
                    <td class="mono">${escapeHtml(trace.url || '-')}</td>
                    <td>${trace.status_code ? `<span class="status-pill ${trace.status_code >= 500 ? 'error' : trace.status_code >= 400 ? 'warning' : 'info'}">${trace.status_code}</span>` : '-'}</td>
                    <td>${fmtMs(trace.duration_ms)}</td>
                    <td>${fmtNumber(trace.query_count)}</td>
                    <td class="mono">${escapeHtml(trace.created_at || '-')}</td>
                </tr>
            `).join('');
        }

        function alertStatePill(alert) {
            if (alert.resolved) {
                return '<span class="status-pill info">resolved</span>';
            }

            return `<span class="status-pill ${escapeHtml(alert.severity || 'warning')}">open</span>`;
        }

        function renderAlerts(alerts) {
            const alertBody = document.getElementById('alerts-body');

            if (!alerts.length) {
                alertBody.innerHTML = '<tr><td colspan="6" class="empty">No alerts found.</td></tr>';
                return;
            }

            alertBody.innerHTML = alerts.slice(0, 30).map((alert) => {
                const resolveButton = alert.resolved
                    ? '<span class="hint">-</span>'
                    : `<button type="button" class="btn" data-resolve-id="${escapeHtml(alert.id)}">Resolve</button>`;

                return `
                    <tr>
                        <td><span class="status-pill ${escapeHtml(alert.severity || 'warning')}">${escapeHtml(alert.severity || 'warning')}</span></td>
                        <td>${escapeHtml(alert.title || '-')}</td>
                        <td class="mono">${escapeHtml(alert.source || '-')}</td>
                        <td class="mono">${escapeHtml(alert.created_at || '-')}</td>
                        <td>${alertStatePill(alert)}</td>
                        <td>${resolveButton}</td>
                    </tr>
                `;
            }).join('');
        }

        async function resolveAlert(alertId) {
            const template = endpoints.resolveAlert || '';
            if (!template) {
                return;
            }

            const targetUrl = template.replace('__ALERT_ID__', encodeURIComponent(String(alertId)));

            await fetchJson(targetUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
        }

        async function loadHealth() {
            try {
                const health = await fetchJson(endpoints.health);
                updateHealthBadge(health.status === 'healthy' ? 'healthy' : 'warning', health.status || 'degraded');
            } catch (_error) {
                updateHealthBadge('error', 'offline');
            }
        }

        async function loadDashboard() {
            if (!endpoints.dashboard) {
                throw new Error('Dashboard endpoint is not available.');
            }

            const days = Number(daysSelect.value || 1);
            const separator = endpoints.dashboard.includes('?') ? '&' : '?';
            const url = `${endpoints.dashboard}${separator}days=${encodeURIComponent(days)}`;
            const dashboard = await fetchJson(url);

            renderMetrics(dashboard.overall_metrics || {});
            renderRoutes(Array.isArray(dashboard.route_performance) ? dashboard.route_performance : []);
            renderBottlenecks(Array.isArray(dashboard.bottlenecks) ? dashboard.bottlenecks : []);
        }

        async function loadTraces() {
            const traces = await fetchJson(endpoints.traces);
            renderTraces(Array.isArray(traces) ? traces : []);
        }

        async function loadAlerts() {
            const alerts = await fetchJson(endpoints.alerts);
            renderAlerts(Array.isArray(alerts) ? alerts : []);
        }

        async function refreshAll() {
            if (state.loading) {
                return;
            }

            state.loading = true;
            refreshButton.disabled = true;

            try {
                await Promise.all([
                    loadHealth(),
                    loadDashboard(),
                    loadTraces(),
                    loadAlerts(),
                ]);

                document.getElementById('last-sync').textContent = `last sync: ${new Date().toLocaleString()}`;
            } catch (error) {
                document.getElementById('last-sync').textContent = `last sync failed: ${error.message}`;
            } finally {
                state.loading = false;
                refreshButton.disabled = false;
            }
        }

        function startAutoRefresh() {
            if (state.timer) {
                clearInterval(state.timer);
            }

            state.timer = setInterval(refreshAll, refreshIntervalMs);
        }

        refreshButton.addEventListener('click', refreshAll);
        daysSelect.addEventListener('change', refreshAll);

        document.getElementById('alerts-body').addEventListener('click', async (event) => {
            const button = event.target.closest('[data-resolve-id]');
            if (!button) {
                return;
            }

            const alertId = button.getAttribute('data-resolve-id');
            button.disabled = true;

            try {
                await resolveAlert(alertId);
                await Promise.all([loadAlerts(), loadDashboard()]);
            } catch (_error) {
                button.disabled = false;
            }
        });

        refreshAll();
        startAutoRefresh();
    </script>
</body>
</html>
