import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const numberFormat = new Intl.NumberFormat();
const palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#94a3b8'];

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;
Chart.defaults.animation = prefersReducedMotion ? false : { duration: 450, easing: 'easeOutQuart' };

function isDarkTheme() {
    return document.documentElement.classList.contains('dark')
        || document.documentElement.dataset.theme === 'dark';
}

function getTheme() {
    return {
        dark: isDarkTheme(),
        text: isDarkTheme() ? '#cbd5e1' : '#475569',
        grid: isDarkTheme() ? 'rgba(148, 163, 184, 0.16)' : 'rgba(100, 116, 139, 0.16)',
    };
}

function readJson(canvas, key) {
    try {
        const parsed = JSON.parse(canvas.dataset[key] ?? '[]');

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function sharedOptions(theme) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: theme.dark ? '#f8fafc' : '#0f172a',
                titleColor: theme.dark ? '#0f172a' : '#f8fafc',
                bodyColor: theme.dark ? '#0f172a' : '#f8fafc',
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: (item) => numberFormat.format(item.parsed.y ?? item.parsed),
                },
            },
        },
        scales: {
            x: {
                border: { display: false },
                grid: { display: false },
                ticks: {
                    color: theme.text,
                    autoSkip: true,
                    maxTicksLimit: 8,
                    maxRotation: 0,
                },
            },
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: theme.grid },
                ticks: {
                    color: theme.text,
                    precision: 0,
                    maxTicksLimit: 6,
                    padding: 8,
                },
            },
        },
    };
}

function createSparkline(canvas, theme) {
    return new Chart(canvas, {
        type: 'line',
        data: {
            labels: readJson(canvas, 'values').map((_, index) => index + 1),
            datasets: [{
                data: readJson(canvas, 'values'),
                borderColor: theme.dark ? '#60a5fa' : '#2563eb',
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 0,
                tension: 0.35,
                spanGaps: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: prefersReducedMotion ? false : { duration: 350 },
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false, grid: { display: false }, border: { display: false } },
                y: { display: false, grid: { display: false }, border: { display: false } },
            },
            elements: { line: { fill: false } },
        },
    });
}

function createBar(canvas, theme, horizontal = false) {
    const options = sharedOptions(theme);
    const values = readJson(canvas, 'values');

    if (horizontal) {
        options.indexAxis = 'y';
        options.scales.x.ticks.precision = 0;
        options.scales.y.grid.display = false;
        options.plugins.tooltip.callbacks.label = (item) => numberFormat.format(item.parsed.x);
    } else {
        options.plugins.tooltip.callbacks.label = (item) => `${numberFormat.format(item.parsed.y)} requests`;
    }

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: readJson(canvas, 'labels'),
            datasets: [{
                label: 'Requests',
                data: values,
                backgroundColor: values.map((_, index) => palette[index % palette.length]),
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: horizontal ? 30 : 48,
            }],
        },
        options,
    });
}

function createDoughnut(canvas, half = false) {
    const labels = readJson(canvas, 'labels');
    const values = readJson(canvas, 'values');
    const total = values.reduce((sum, value) => sum + (Number(value) || 0), 0);

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: palette.slice(0, values.length),
                borderColor: isDarkTheme() ? '#0f172a' : '#ffffff',
                borderWidth: 3,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: half ? '78%' : '66%',
            rotation: half ? -90 : 0,
            circumference: half ? 180 : 360,
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: !half,
                    callbacks: {
                        label: (item) => `${labels[item.dataIndex]}: ${numberFormat.format(item.raw)} (${total === 0 ? 0 : Math.round((item.raw / total) * 100)}%)`,
                    },
                },
            },
        },
    });
}

function createReportChart(canvas) {
    const theme = getTheme();

    switch (canvas.dataset.reportChart) {
        case 'sparkline':
            return createSparkline(canvas, theme);
        case 'approval-gauge':
            return createDoughnut(canvas, true);
        case 'citizens':
        case 'blockchain':
            return createDoughnut(canvas);
        case 'verification-status':
            return createBar(canvas, theme, true);
        case 'trend':
            return createBar(canvas, theme);
        case 'permit-type':
            return createBar(canvas, theme, true);
        default:
            return createBar(canvas, theme);
    }
}

const canvases = [...document.querySelectorAll('[data-report-chart]')];
let reportCharts = [];
let themeTimer;

function recreateCharts() {
    reportCharts.forEach((chart) => chart.destroy());
    reportCharts = canvases.map(createReportChart);
}

recreateCharts();

new MutationObserver(() => {
    clearTimeout(themeTimer);
    themeTimer = setTimeout(recreateCharts, 50);
}).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class', 'data-theme'],
});

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function presetStart(preset, today) {
    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    if (preset === 'month') {
        start.setDate(1);
    } else {
        start.setDate(start.getDate() - 29);
    }

    return start;
}

document.querySelectorAll('[data-report-preset]').forEach((button) => {
    button.addEventListener('click', () => {
        const fromInput = document.getElementById('report-from');
        const toInput = document.getElementById('report-to');

        if (!fromInput || !toInput) {
            return;
        }

        const today = new Date();
        fromInput.value = formatDate(presetStart(button.dataset.reportPreset, today));
        toInput.value = formatDate(today);
        fromInput.form.requestSubmit();
    });
});
