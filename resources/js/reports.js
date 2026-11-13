import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;
Chart.defaults.animation = prefersReducedMotion ? false : { duration: 500, easing: 'easeOutQuart' };

const isDarkTheme = () => document.documentElement.classList.contains('dark')
    || document.documentElement.dataset.theme === 'dark';

function getTheme() {
    const dark = isDarkTheme();

    return {
        dark,
        text: dark ? '#cbd5e1' : '#475569',
        grid: dark ? 'rgba(148, 163, 184, 0.16)' : 'rgba(100, 116, 139, 0.16)',
        surface: dark ? '#1e293b' : '#ffffff',
        tooltipBg: dark ? '#f8fafc' : '#0f172a',
        tooltipText: dark ? '#0f172a' : '#f8fafc',
    };
}

const ACCENTS = {
    period: { light: '#0f766e', dark: '#2dd4bf' },
    'document-type': { light: '#17325c', dark: '#7aa2e3' },
};

function withAlpha(hex, alpha) {
    const value = hex.replace('#', '');
    const r = parseInt(value.slice(0, 2), 16);
    const g = parseInt(value.slice(2, 4), 16);
    const b = parseInt(value.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function readJson(canvas, key) {
    try {
        const parsed = JSON.parse(canvas.dataset[key] ?? '[]');

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

const numberFormat = new Intl.NumberFormat();

function sharedOptions(theme) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: theme.tooltipBg,
                titleColor: theme.tooltipText,
                bodyColor: theme.tooltipText,
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
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

function buildLine(canvas, labels, values, theme) {
    const color = ACCENTS.period[theme.dark ? 'dark' : 'light'];
    const options = sharedOptions(theme);

    options.interaction = { mode: 'index', intersect: false };
    options.plugins.tooltip.callbacks = {
        label: (item) => `${numberFormat.format(item.parsed.y)} ${item.parsed.y === 1 ? 'request' : 'requests'}`,
    };

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Requests',
                data: values,
                borderColor: color,
                borderWidth: 2.5,
                tension: 0.3,
                fill: true,
                backgroundColor: (context) => {
                    const { chart } = context;

                    if (!chart.chartArea) {
                        return withAlpha(color, 0.12);
                    }

                    const gradient = chart.ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
                    gradient.addColorStop(0, withAlpha(color, 0.28));
                    gradient.addColorStop(1, withAlpha(color, 0));

                    return gradient;
                },
                pointRadius: labels.length > 20 ? 0 : 3,
                pointHoverRadius: 5,
                pointBackgroundColor: theme.surface,
                pointBorderColor: color,
                pointBorderWidth: 2,
                spanGaps: false,
            }],
        },
        options,
    });
}

function buildBar(canvas, labels, values, theme) {
    const color = ACCENTS['document-type'][theme.dark ? 'dark' : 'light'];
    const options = sharedOptions(theme);

    options.plugins.tooltip.callbacks = {
        label: (item) => `${numberFormat.format(item.parsed.y)} ${item.parsed.y === 1 ? 'request' : 'requests'}`,
    };

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Requests',
                data: values,
                backgroundColor: withAlpha(color, theme.dark ? 0.85 : 0.9),
                hoverBackgroundColor: color,
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 48,
            }],
        },
        options,
    });
}

function createReportChart(canvas) {
    const type = canvas.dataset.reportChart;
    const labels = readJson(canvas, 'labels');
    const values = readJson(canvas, 'values');
    const theme = getTheme();

    return type === 'period'
        ? buildLine(canvas, labels, values, theme)
        : buildBar(canvas, labels, values, theme);
}

const canvases = [...document.querySelectorAll('[data-report-chart]')];
let reportCharts = canvases.map(createReportChart);
let themeTimer;

new MutationObserver(() => {
    clearTimeout(themeTimer);
    themeTimer = setTimeout(() => {
        const previousAnimation = Chart.defaults.animation;

        Chart.defaults.animation = false;
        reportCharts.forEach((chart) => chart.destroy());
        reportCharts = canvases.map(createReportChart);
        Chart.defaults.animation = previousAnimation;
    }, 50);
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
