<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAB PV Monitoring</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%2306132f'/%3E%3Cpath d='M36 6L14 34h16l-4 24 24-30H34l2-22z' fill='%2320d6ff'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        :root {
            --bg-main: #03091d;
            --bg-deep: #050f2b;
            --bg-card: #1b213a;
            --bg-card-2: #161d33;
            --line: rgba(123, 156, 214, 0.22);
            --text-main: #e6efff;
            --text-muted: #7f90b6;
            --accent-cyan: #20d6ff;
            --accent-green: #23d978;
            --accent-yellow: #ffc44a;
            --accent-red: #ff5f8f;
            --accent-orange: #ff9a3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(1200px 600px at 0% -10%, rgba(23, 78, 188, 0.28), transparent 60%),
                radial-gradient(1000px 520px at 100% -20%, rgba(5, 140, 154, 0.2), transparent 62%),
                linear-gradient(160deg, var(--bg-main), #050c25 35%, var(--bg-deep));
            color: var(--text-main);
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            padding: 18px;
        }

        .container {
            width: min(1600px, 100%);
            margin: 0 auto;
        }

        .panel {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(26, 35, 64, 0.9), rgba(18, 25, 49, 0.85));
            box-shadow: inset 0 1px 0 rgba(196, 219, 255, 0.06), 0 24px 40px rgba(2, 8, 24, 0.5);
        }

        .topbar {
            height: 78px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: var(--accent-cyan);
            background: linear-gradient(145deg, rgba(20, 53, 117, 0.8), rgba(7, 23, 63, 0.8));
            border: 1px solid rgba(49, 131, 253, 0.4);
        }

        .brand-title {
            font-family: "Orbitron", sans-serif;
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 30px;
        }

        .live-badge {
            border-radius: 999px;
            border: 1px solid rgba(40, 208, 120, 0.4);
            background: rgba(16, 112, 65, 0.35);
            color: #37e789;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 0 0 5px rgba(12, 55, 34, 0.24);
        }

        .live-badge.offline {
            border-color: rgba(255, 95, 143, 0.4);
            background: rgba(117, 34, 56, 0.35);
            color: #ff7b9f;
            box-shadow: 0 0 0 5px rgba(75, 22, 38, 0.24);
        }

        .live-badge.offline .live-dot {
            background: #ff7b9f;
            box-shadow: 0 0 12px #ff7b9f;
            animation: none;
            opacity: 0.6;
        }

        .offline-text {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .live-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #26e57e;
            box-shadow: 0 0 12px #26e57e;
            animation: blink 1.8s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .stat-card {
            padding: 22px 24px;
            min-height: 142px;
            position: relative;
            overflow: hidden;
        }

        .stat-card.offline {
            opacity: 0.65;
            filter: grayscale(0.3);
        }

        .stat-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.03), transparent 60%);
            pointer-events: none;
        }

        .stat-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 700;
            font-size: 28px;
            letter-spacing: 0.8px;
        }

        .icon-chip {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 24px;
            border: 1px solid transparent;
        }

        .stat-card.voltage .icon-chip {
            color: #54bcff;
            background: rgba(66, 142, 255, 0.12);
            border-color: rgba(66, 142, 255, 0.26);
        }

        .stat-card.current .icon-chip {
            color: var(--accent-cyan);
            background: rgba(0, 201, 255, 0.12);
            border-color: rgba(0, 201, 255, 0.24);
        }

        .stat-card.lux .icon-chip {
            color: var(--accent-yellow);
            background: rgba(255, 196, 74, 0.14);
            border-color: rgba(255, 196, 74, 0.25);
        }

        .stat-card.temp .icon-chip {
            color: var(--accent-red);
            background: rgba(255, 95, 143, 0.13);
            border-color: rgba(255, 95, 143, 0.26);
        }

        .stat-value {
            font-family: "Orbitron", sans-serif;
            font-size: 52px;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1;
        }

        .unit {
            color: var(--text-muted);
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 28px;
            font-weight: 700;
            margin-left: 4px;
        }

        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid transparent;
            padding: 4px 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .stat-change small {
            margin-left: 3px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 500;
        }

        .positive {
            color: #3ae38a;
            background: rgba(20, 99, 63, 0.32);
            border-color: rgba(62, 207, 128, 0.26);
        }

        .negative {
            color: #ff7b9f;
            background: rgba(117, 34, 56, 0.35);
            border-color: rgba(247, 98, 139, 0.24);
        }

        .neutral {
            color: #c2d3f4;
            background: rgba(90, 113, 156, 0.2);
            border-color: rgba(130, 154, 203, 0.22);
        }

        .section {
            padding: 24px;
            margin-bottom: 16px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .section-title {
            font-family: "Orbitron", sans-serif;
            font-size: 30px;
            letter-spacing: 0.7px;
        }

        .legend-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .chart-shell {
            height: 330px;
            border-radius: 12px;
            border: 1px solid rgba(107, 152, 236, 0.2);
            background: linear-gradient(180deg, rgba(22, 31, 58, 0.68), rgba(17, 24, 45, 0.7));
            padding: 14px 12px 6px;
        }

        .toolbar {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn {
            border-radius: 7px;
            border: 1px solid rgba(102, 145, 216, 0.25);
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 700;
            background: rgba(42, 60, 103, 0.18);
            padding: 8px 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            color: #31ec92;
            border-color: rgba(49, 236, 146, 0.4);
            background: rgba(21, 109, 70, 0.35);
            box-shadow: 0 0 0 3px rgba(18, 71, 49, 0.35);
        }

        .env-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            border-radius: 8px;
            border: 1px solid rgba(115, 160, 236, 0.3);
            background: #111a35;
            color: var(--text-main);
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 600;
            min-width: 215px;
            outline: none;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stat-value {
                font-size: 44px;
            }
        }

        @media (max-width: 760px) {
            body {
                padding: 12px;
            }

            .topbar {
                height: auto;
                padding: 16px;
                flex-direction: column;
                align-items: flex-start;
            }

            .brand-title {
                font-size: 26px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 40px;
            }

            .section-title {
                font-size: 24px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .chart-shell {
                height: 280px;
            }

            .filter-select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $voltage = $latest?->voltage;
        $current = $latest?->current;
        $lux = $latest?->lux;
        $temperature = $latest?->temperature;

        $voltageChange = $latest?->voltage_change_percent;
        $currentChange = $latest?->current_change_percent;
        $luxChange = $latest?->lux_change_percent;
        $temperatureChange = $latest?->temperature_change_percent;
    @endphp

    <div class="container">
        <header class="panel topbar">
            <div class="brand">
                <div class="brand-icon" aria-label="Electricity Icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M13.2 2L5 13.2H11L9.8 22L18 10.8H12L13.2 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 class="brand-title">LAB PV</h1>
            </div>
            <div class="live-badge {{ $isOffline ? 'offline' : '' }}" id="statusBadge">
                <span class="live-dot"></span>
                <div>
                    <div>{{ $isOffline ? 'OFFLINE' : 'LIVE MONITORING' }}</div>
                    @if ($isOffline)
                        <div class="offline-text">Last update {{ $lastUpdateTime }}</div>
                    @endif
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <article class="panel stat-card voltage {{ $isOffline ? 'offline' : '' }}">
                <div class="stat-head">
                    <p class="stat-label">VOLTAGE</p>
                    <div class="icon-chip">V</div>
                </div>
                <p class="stat-value" id="metric-voltage">{{ $isOffline ? '--' : ($voltage !== null ? number_format($voltage, 2) : '--') }}<span class="unit">V</span></p>
                <span class="stat-change {{ $voltageChange > 0 ? 'positive' : ($voltageChange < 0 ? 'negative' : 'neutral') }}" id="change-voltage">
                    {{ $isOffline ? '--' : ($voltageChange !== null ? ($voltageChange >= 0 ? '↑ ' : '↓ ') . number_format(abs($voltageChange), 1) . '%' : '--') }}
                    <small>vs last hour</small>
                </span>
            </article>

            <article class="panel stat-card current {{ $isOffline ? 'offline' : '' }}">
                <div class="stat-head">
                    <p class="stat-label">CURRENT</p>
                    <div class="icon-chip">A</div>
                </div>
                <p class="stat-value" id="metric-current">{{ $isOffline ? '--' : ($current !== null ? number_format($current, 2) : '--') }}<span class="unit">A</span></p>
                <span class="stat-change {{ $currentChange > 0 ? 'positive' : ($currentChange < 0 ? 'negative' : 'neutral') }}" id="change-current">
                    {{ $isOffline ? '--' : ($currentChange !== null ? ($currentChange >= 0 ? '↑ ' : '↓ ') . number_format(abs($currentChange), 1) . '%' : '--') }}
                    <small>vs last hour</small>
                </span>
            </article>

            <article class="panel stat-card lux {{ $isOffline ? 'offline' : '' }}">
                <div class="stat-head">
                    <p class="stat-label">LUX</p>
                    <div class="icon-chip">L</div>
                </div>
                <p class="stat-value" id="metric-lux">{{ $isOffline ? '--' : ($lux !== null ? number_format($lux, 2) : '--') }}<span class="unit">Lux</span></p>
                <span class="stat-change {{ $luxChange > 0 ? 'positive' : ($luxChange < 0 ? 'negative' : 'neutral') }}" id="change-lux">
                    {{ $isOffline ? '--' : ($luxChange !== null ? ($luxChange >= 0 ? '↑ ' : '↓ ') . number_format(abs($luxChange), 1) . '%' : '--') }}
                    <small>vs last hour</small>
                </span>
            </article>

            <article class="panel stat-card temp {{ $isOffline ? 'offline' : '' }}">
                <div class="stat-head">
                    <p class="stat-label">TEMP</p>
                    <div class="icon-chip">T</div>
                </div>
                <p class="stat-value" id="metric-temperature">{{ $isOffline ? '--' : ($temperature !== null ? number_format($temperature, 2) : '--') }}<span class="unit">C</span></p>
                <span class="stat-change {{ $temperatureChange > 0 ? 'positive' : ($temperatureChange < 0 ? 'negative' : 'neutral') }}" id="change-temperature">
                    {{ $isOffline ? '--' : ($temperatureChange !== null ? ($temperatureChange >= 0 ? '↑ ' : '↓ ') . number_format(abs($temperatureChange), 1) . '%' : '--') }}
                    <small>vs last hour</small>
                </span>
            </article>
        </section>

        <section class="panel section">
            <div class="section-header">
                <h2 class="section-title">POWER OUTPUT</h2>
                <div class="legend-wrap">
                    <span><i class="legend-dot" style="background: var(--accent-cyan);"></i>Voltage</span>
                    <span><i class="legend-dot" style="background: var(--accent-green);"></i>Current</span>
                </div>
            </div>
            <div class="chart-shell">
                <canvas id="powerChart"></canvas>
            </div>
            <div class="toolbar" id="powerTabs">
                <button class="tab-btn" data-period="1H">1H</button>
                <button class="tab-btn active" data-period="24H">24H</button>
                <button class="tab-btn" data-period="7D">7D</button>
            </div>
        </section>

        <section class="panel section">
            <div class="section-header">
                <h2 class="section-title">ENVIRONMENT</h2>
                <div class="legend-wrap">
                    <span><i class="legend-dot" style="background: #ffd31f;"></i>Lux</span>
                    <span><i class="legend-dot" style="background: var(--accent-orange);"></i>Temp</span>
                </div>
            </div>
            <div class="env-grid">
                <div class="chart-shell">
                    <canvas id="environmentChart"></canvas>
                </div>
            </div>
            <div class="toolbar" id="envTabs">
                <button class="tab-btn" data-period="1H">1H</button>
                <button class="tab-btn active" data-period="24H">24H</button>
                <button class="tab-btn" data-period="7D">7D</button>
            </div>
        </section>

        <section class="panel section">
            <div class="section-header">
                <h2 class="section-title">PARAMETER TRACKER</h2>
                <div class="filter-group">
                    <select class="filter-select" id="parameterSelect">
                        <option value="voltage">Tegangan (V)</option>
                        <option value="current">Arus (A)</option>
                        <option value="temperature">Suhu (C)</option>
                        <option value="lux" selected>Intensitas Cahaya (Lux)</option>
                        <option value="power">Daya (W)</option>
                    </select>
                </div>
            </div>
            <div class="chart-shell">
                <canvas id="parameterChart"></canvas>
            </div>
            <div class="toolbar" id="paramTabs">
                <button class="tab-btn" data-period="1H">1H</button>
                <button class="tab-btn active" data-period="24H">24H</button>
                <button class="tab-btn" data-period="7D">7D</button>
            </div>
        </section>
    </div>

    <script>
        let powerChart;
        let environmentChart;
        let parameterChart;

        let powerPeriod = '24H';
        let envPeriod = '24H';
        let parameterPeriod = '24H';
        let selectedParameter = 'lux';
        const POLLING_INTERVAL_MS = 3000; // 3 seconds
        let isRefreshing = false;

        document.addEventListener('DOMContentLoaded', () => {
            initPowerChart();
            initEnvironmentChart();
            initParameterChart();
            bindTabHandlers();
            bindParameterHandler();

            refreshAllData();
            setInterval(refreshAllData, POLLING_INTERVAL_MS);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    refreshAllData();
                }
            });
        });

        function fetchJson(url) {
            const separator = url.includes('?') ? '&' : '?';
            return fetch(url + separator + '_ts=' + Date.now(), {
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache',
                },
            }).then((response) => response.json());
        }

        function chartBaseOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(75, 106, 168, 0.22)' },
                        ticks: {
                            color: '#7687ad',
                            maxTicksLimit: 20,
                        },
                        title: {
                            display: true,
                            text: 'Waktu',
                            color: '#c2d3f4',
                            font: { size: 14, weight: 'bold' },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(75, 106, 168, 0.22)' },
                        ticks: { color: '#7687ad' },
                    },
                },
            };
        }

        function initPowerChart() {
            const options = chartBaseOptions();
            options.scales.y.title = {
                display: true,
                text: 'Tegangan (V) & Arus (A)',
                color: '#c2d3f4',
                font: { size: 14, weight: 'bold' },
            };
            
            powerChart = new Chart(document.getElementById('powerChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Voltage',
                            data: [],
                            borderColor: '#20d6ff',
                            backgroundColor: 'rgba(32, 214, 255, 0.12)',
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                        },
                        {
                            label: 'Current',
                            data: [],
                            borderColor: '#23d978',
                            backgroundColor: 'rgba(35, 217, 120, 0.1)',
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                        }
                    ],
                },
                options: options,
            });
        }

        function initEnvironmentChart() {
            const options = chartBaseOptions();
            options.scales.y.title = {
                display: true,
                text: 'Lux & Suhu (°C)',
                color: '#c2d3f4',
                font: { size: 14, weight: 'bold' },
            };
            
            environmentChart = new Chart(document.getElementById('environmentChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Lux',
                            data: [],
                            borderColor: '#ffd31f',
                            backgroundColor: 'rgba(255, 211, 31, 0.1)',
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                        },
                        {
                            label: 'Temp',
                            data: [],
                            borderColor: '#ff9a3c',
                            backgroundColor: 'rgba(255, 154, 60, 0.1)',
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                        },
                    ],
                },
                options: options,
            });
        }

        function initParameterChart() {
            const options = chartBaseOptions();
            options.scales.y.title = {
                display: true,
                text: 'Nilai Parameter',
                color: '#c2d3f4',
                font: { size: 14, weight: 'bold' },
            };
            
            parameterChart = new Chart(document.getElementById('parameterChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Parameter',
                            data: [],
                            borderColor: '#54bcff',
                            backgroundColor: 'rgba(84, 188, 255, 0.1)',
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                        },
                    ],
                },
                options: options,
            });
        }

        function bindTabHandlers() {
            bindTabs('powerTabs', (period) => {
                powerPeriod = period;
                loadPowerChart();
            });

            bindTabs('envTabs', (period) => {
                envPeriod = period;
                loadEnvironmentChart();
            });

            bindTabs('paramTabs', (period) => {
                parameterPeriod = period;
                loadParameterChart();
            });
        }

        function bindTabs(containerId, callback) {
            const container = document.getElementById(containerId);
            const buttons = container.querySelectorAll('.tab-btn');

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    buttons.forEach((b) => b.classList.remove('active'));
                    button.classList.add('active');
                    callback(button.dataset.period);
                });
            });
        }

        function bindParameterHandler() {
            document.getElementById('parameterSelect').addEventListener('change', (event) => {
                selectedParameter = event.target.value;
                loadParameterChart();
            });
        }

        function refreshAllData() {
            if (isRefreshing) {
                return;
            }

            isRefreshing = true;
            refreshLatestMetrics();
            loadPowerChart();
            loadEnvironmentChart();
            loadParameterChart();

            setTimeout(() => {
                isRefreshing = false;
            }, 1500);
        }

        function loadPowerChart() {
            fetchJson('/api/pv/power-output?period=' + powerPeriod)
                .then((response) => {
                    if (!response.success) {
                        return;
                    }

                    powerChart.data.labels = response.labels;
                    powerChart.data.datasets[0].data = response.voltage;
                    powerChart.data.datasets[1].data = response.current;
                    powerChart.update();
                });
        }

        function loadEnvironmentChart() {
            fetchJson('/api/pv/environment?period=' + envPeriod)
                .then((response) => {
                    if (!response.success) {
                        return;
                    }

                    environmentChart.data.labels = response.labels;
                    environmentChart.data.datasets[0].data = response.lux;
                    environmentChart.data.datasets[1].data = response.temperature;
                    environmentChart.update();
                });
        }

        function loadParameterChart() {
            fetchJson('/api/pv/chart?period=' + parameterPeriod + '&parameter=' + selectedParameter)
                .then((response) => {
                    if (!response.success) {
                        return;
                    }

                    const labelMap = {
                        voltage: 'Tegangan (V)',
                        current: 'Arus (A)',
                        temperature: 'Suhu (C)',
                        lux: 'Lux',
                        power: 'Daya (W)',
                    };

                    parameterChart.data.labels = response.labels;
                    parameterChart.data.datasets[0].label = labelMap[selectedParameter] || 'Parameter';
                    parameterChart.data.datasets[0].data = response.data;
                    parameterChart.options.scales.y.title.text = labelMap[selectedParameter] || 'Nilai Parameter';
                    parameterChart.update();
                });
        }

        function refreshLatestMetrics() {
            fetchJson('/api/pv/latest')
                .then((response) => {
                    if (!response.success || !response.data) {
                        return;
                    }

                    const latest = response.data;
                    const isOffline = response.isOffline;
                    const lastUpdateTime = response.lastUpdateTime;

                    // Update status badge
                    const statusBadge = document.getElementById('statusBadge');
                    if (isOffline) {
                        statusBadge.classList.add('offline');
                        statusBadge.innerHTML = '<span class="live-dot"></span><div><div>OFFLINE</div><div class="offline-text">Last update ' + lastUpdateTime + '</div></div>';
                    } else {
                        statusBadge.classList.remove('offline');
                        statusBadge.innerHTML = '<span class="live-dot"></span>LIVE MONITORING';
                    }

                    // Update metrics based on offline status
                    if (isOffline) {
                        updateMetric('voltage', null, 'V', 2);
                        updateMetric('current', null, 'A', 2);
                        updateMetric('lux', null, 'Lux', 2);
                        updateMetric('temperature', null, 'C', 2);

                        updateChange('voltage', null);
                        updateChange('current', null);
                        updateChange('lux', null);
                        updateChange('temperature', null);

                        // Add offline class to stat cards
                        document.querySelectorAll('.stat-card').forEach(card => {
                            card.classList.add('offline');
                        });
                    } else {
                        // Remove offline class from stat cards
                        document.querySelectorAll('.stat-card').forEach(card => {
                            card.classList.remove('offline');
                        });

                        updateMetric('voltage', Number(latest.voltage), 'V', 2);
                        updateMetric('current', Number(latest.current), 'A', 2);
                        updateMetric('lux', Number(latest.lux), 'Lux', 2);
                        updateMetric('temperature', Number(latest.temperature), 'C', 2);

                        updateChange('voltage', Number(latest.voltage_change_percent));
                        updateChange('current', Number(latest.current_change_percent));
                        updateChange('lux', Number(latest.lux_change_percent));
                        updateChange('temperature', Number(latest.temperature_change_percent));
                    }
                });
        }

        function updateMetric(name, value, unit, decimal = 1) {
            const el = document.getElementById('metric-' + name);
            if (!Number.isFinite(value)) {
                el.innerHTML = '--<span class="unit">' + unit + '</span>';
                return;
            }

            el.innerHTML = value.toFixed(decimal) + '<span class="unit">' + unit + '</span>';
        }

        function updateChange(name, value) {
            const el = document.getElementById('change-' + name);
            if (!Number.isFinite(value)) {
                el.className = 'stat-change neutral';
                el.innerHTML = '--<small>vs last hour</small>';
                return;
            }

            const absValue = Math.abs(value).toFixed(1);
            if (value > 0) {
                el.className = 'stat-change positive';
                el.innerHTML = '↑ ' + absValue + '%<small>vs last hour</small>';
                return;
            }

            if (value < 0) {
                el.className = 'stat-change negative';
                el.innerHTML = '↓ ' + absValue + '%<small>vs last hour</small>';
                return;
            }

            el.className = 'stat-change neutral';
            el.innerHTML = '0.0%<small>vs last hour</small>';
        }
    </script>
</body>
</html>
