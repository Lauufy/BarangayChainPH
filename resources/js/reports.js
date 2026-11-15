import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
).matches;
const numberFormat = new Intl.NumberFormat();
const palette = ["#2563eb", "#10b981", "#f59e0b", "#ef4444", "#94a3b8"];

// Meaning-based colours, so "Approved" is always green and "Rejected" always red.
// Labels not listed here fall back to the palette above.
const labelColors = {
    approved: "#10b981",
    verified: "#10b981",
    successful: "#10b981",
    valid: "#10b981",
    "in progress": "#f59e0b",
    pending: "#f59e0b",
    processing: "#2563eb",
    rejected: "#ef4444",
    failed: "#ef4444",
    "invalid / hash mismatch": "#ef4444",
    cancelled: "#94a3b8",
    unverified: "#94a3b8",
    revoked: "#64748b",
    other: "#94a3b8",
};

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;
Chart.defaults.animation = prefersReducedMotion
    ? false
    : { duration: 450, easing: "easeOutQuart" };

function isDarkTheme() {
    return (
        document.documentElement.classList.contains("dark") ||
        document.documentElement.dataset.theme === "dark"
    );
}

function getTheme() {
    return {
        dark: isDarkTheme(),
        text: isDarkTheme() ? "#cbd5e1" : "#475569",
        grid: isDarkTheme()
            ? "rgba(148, 163, 184, 0.16)"
            : "rgba(100, 116, 139, 0.16)",
    };
}

function readJson(canvas, key) {
    try {
        const parsed = JSON.parse(canvas.dataset[key] ?? "[]");

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

// One colour per label: by meaning when known, otherwise from the palette
function colorsFor(labels) {
    return labels.map(
        (label, index) =>
            labelColors[String(label).toLowerCase()] ??
            palette[index % palette.length],
    );
}

function sharedOptions(theme) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: theme.dark ? "#f8fafc" : "#0f172a",
                titleColor: theme.dark ? "#0f172a" : "#f8fafc",
                bodyColor: theme.dark ? "#0f172a" : "#f8fafc",
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: (item) =>
                        numberFormat.format(item.parsed.y ?? item.parsed),
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
                    maxTicksLimit: 12,
                    maxRotation: 0,
                },
            },
            y: {
                beginAtZero: true,
                // Chart.js v4: dashed grid lines are set on the axis border
                border: { display: false, dash: [4, 4] },
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
        type: "line",
        data: {
            labels: readJson(canvas, "values").map((_, index) => index + 1),
            datasets: [
                {
                    data: readJson(canvas, "values"),
                    borderColor: theme.dark ? "#60a5fa" : "#2563eb",
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    tension: 0.35,
                    spanGaps: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: prefersReducedMotion ? false : { duration: 350 },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
            },
            scales: {
                x: {
                    display: false,
                    grid: { display: false },
                    border: { display: false },
                },
                y: {
                    display: false,
                    grid: { display: false },
                    border: { display: false },
                },
            },
            elements: { line: { fill: false } },
        },
    });
}

function createBar(canvas, theme, horizontal = false) {
    const options = sharedOptions(theme);
    const labels = readJson(canvas, "labels");
    const values = readJson(canvas, "values");

    if (horizontal) {
        options.indexAxis = "y";
        options.scales.x.ticks.precision = 0;
        options.scales.x.border = { display: false, dash: [4, 4] };
        options.scales.x.grid = { color: theme.grid };
        options.scales.y.border = { display: false };
        options.scales.y.grid = { display: false };
        options.plugins.tooltip.callbacks.label = (item) =>
            numberFormat.format(item.parsed.x);
    } else {
        options.plugins.tooltip.callbacks.label = (item) =>
            `${numberFormat.format(item.parsed.y)} requests`;
        // Keep the axis readable when every value is 0
        options.scales.y.suggestedMax = Math.max(
            4,
            ...values.map((value) => Number(value) || 0),
        );
    }

    // Time series (vertical bars) use one blue, categories (horizontal bars) get a colour each
    const barColor = theme.dark ? "#3b82f6" : "#2563eb";

    return new Chart(canvas, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: "Requests",
                    data: values,
                    backgroundColor: horizontal ? colorsFor(labels) : barColor,
                    hoverBackgroundColor: horizontal
                        ? undefined
                        : theme.dark
                          ? "#60a5fa"
                          : "#1d4ed8",
                    borderRadius: horizontal ? 6 : 8,
                    borderSkipped: false,
                    maxBarThickness: horizontal ? 30 : 18,
                },
            ],
        },
        options,
    });
}

function createDoughnut(canvas, half = false) {
    const labels = readJson(canvas, "labels");
    const values = readJson(canvas, "values");
    const total = values.reduce((sum, value) => sum + (Number(value) || 0), 0);

    // Gauge has no labels: first slice is the value, second is the remainder
    const colors = labels.length
        ? colorsFor(labels)
        : [palette[1], isDarkTheme() ? "rgba(148, 163, 184, 0.2)" : "#e2e8f0"];

    return new Chart(canvas, {
        type: "doughnut",
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: colors,
                    borderColor: isDarkTheme() ? "#0f172a" : "#ffffff",
                    borderWidth: 3,
                    borderRadius: half ? 0 : 4,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: half ? "78%" : "72%",
            rotation: half ? -90 : 0,
            circumference: half ? 180 : 360,
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: !half,
                    callbacks: {
                        label: (item) =>
                            `${labels[item.dataIndex]}: ${numberFormat.format(item.raw)} (${total === 0 ? 0 : Math.round((item.raw / total) * 100)}%)`,
                    },
                },
            },
        },
    });
}

function createReportChart(canvas) {
    const theme = getTheme();

    switch (canvas.dataset.reportChart) {
        case "sparkline":
            return createSparkline(canvas, theme);
        case "approval-gauge":
            return createDoughnut(canvas, true);
        case "citizens":
        case "blockchain":
            return createDoughnut(canvas);
        case "verification-status":
            return createBar(canvas, theme, true);
        case "trend":
            return createBar(canvas, theme);
        case "permit-type":
            return createBar(canvas, theme, true);
        default:
            return createBar(canvas, theme);
    }
}

let reportCharts = [];
let themeTimer;

// Look for canvases every time, because the admin layout swaps page content every 5 seconds
function recreateCharts() {
    reportCharts.forEach((chart) => chart.destroy());
    reportCharts = [...document.querySelectorAll("[data-report-chart]")]
        .map((canvas) => {
            try {
                return createReportChart(canvas);
            } catch (error) {
                console.error(
                    "Chart failed:",
                    canvas.dataset.reportChart,
                    error,
                );
                return null;
            }
        })
        .filter(Boolean);
}

recreateCharts();

new MutationObserver(() => {
    clearTimeout(themeTimer);
    themeTimer = setTimeout(recreateCharts, 50);
}).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class", "data-theme"],
});

// Fired by the admin layout after it refreshes #admin-page-content
document.addEventListener("admin:content-swapped", recreateCharts);

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function presetStart(preset, today) {
    const start = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    );

    if (preset === "month") {
        start.setDate(1);
    } else {
        start.setDate(start.getDate() - 29);
    }

    return start;
}

// Delegated, so the preset buttons still work after the page content is refreshed
document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-report-preset]");

    if (!button) {
        return;
    }

    const fromInput = document.getElementById("report-from");
    const toInput = document.getElementById("report-to");

    if (!fromInput || !toInput) {
        return;
    }

    const today = new Date();
    fromInput.value = formatDate(
        presetStart(button.dataset.reportPreset, today),
    );
    toInput.value = formatDate(today);
    fromInput.form.requestSubmit();
});
