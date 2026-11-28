import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
).matches;
const numberFormat = new Intl.NumberFormat();
const palette = [
    "#2563eb",
    "#10b981",
    "#f59e0b",
    "#8b5cf6",
    "#06b6d4",
    "#ef4444",
    "#94a3b8",
];

// Meaning-based colours: "Approved" is always green, "Rejected" always red.
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
    invalid: "#ef4444",
    "invalid / hash mismatch": "#ef4444",
    cancelled: "#94a3b8",
    unverified: "#94a3b8",
    revoked: "#64748b",
    other: "#cbd5e1",
};

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;
if (prefersReducedMotion) {
    Chart.defaults.animation = false;
} else {
    Chart.defaults.animation.duration = 500;
}

/* ───────────── helpers ───────────── */

function isDarkTheme() {
    return (
        document.documentElement.classList.contains("dark") ||
        document.documentElement.dataset.theme === "dark"
    );
}

function getTheme() {
    const dark = isDarkTheme();

    return {
        dark,
        text: dark ? "#cbd5e1" : "#475569",
        strong: dark ? "#f1f5f9" : "#0f172a",
        grid: dark ? "rgba(148, 163, 184, 0.16)" : "rgba(100, 116, 139, 0.14)",
        surface: dark ? "#0f172a" : "#ffffff",
        empty: dark ? "rgba(148, 163, 184, 0.2)" : "#e2e8f0",
    };
}

function readJson(canvas, key, fallback = []) {
    try {
        const parsed = JSON.parse(canvas.dataset[key] ?? "null");

        return parsed ?? fallback;
    } catch {
        return fallback;
    }
}

function colorFor(label, index) {
    return (
        labelColors[String(label).toLowerCase()] ??
        palette[index % palette.length]
    );
}

function colorsFor(labels) {
    return labels.map((label, index) => colorFor(label, index));
}

function withAlpha(hex, alpha) {
    const value = hex.replace("#", "");
    const r = parseInt(value.slice(0, 2), 16);
    const g = parseInt(value.slice(2, 4), 16);
    const b = parseInt(value.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function areaGradient(color) {
    return (context) => {
        const { chart } = context;
        const { ctx, chartArea } = chart;

        if (!chartArea) {
            return withAlpha(color, 0.15);
        }

        const gradient = ctx.createLinearGradient(
            0,
            chartArea.top,
            0,
            chartArea.bottom,
        );
        gradient.addColorStop(0, withAlpha(color, 0.3));
        gradient.addColorStop(1, withAlpha(color, 0));

        return gradient;
    };
}

function formatValue(value, unit) {
    const formatted = numberFormat.format(
        Number.isInteger(value) ? value : Math.round(value * 100) / 100,
    );

    return unit ? `${formatted} ${unit}` : formatted;
}

/* ───────────── shared options ───────────── */

function baseOptions(theme, unit, { legend = false } = {}) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        layout: { padding: { top: 4, right: 4 } },
        plugins: {
            legend: legend
                ? {
                      position: "top",
                      align: "end",
                      labels: {
                          color: theme.text,
                          usePointStyle: true,
                          pointStyle: "circle",
                          boxWidth: 8,
                          boxHeight: 8,
                          padding: 14,
                      },
                  }
                : { display: false },
            tooltip: {
                backgroundColor: theme.dark ? "#f8fafc" : "#0f172a",
                titleColor: theme.dark ? "#0f172a" : "#f8fafc",
                bodyColor: theme.dark ? "#0f172a" : "#f8fafc",
                padding: 10,
                cornerRadius: 8,
                boxPadding: 4,
                usePointStyle: true,
                callbacks: {
                    label: (item) => {
                        const value =
                            item.parsed.y ?? item.parsed.x ?? item.parsed;
                        const name = item.dataset.label
                            ? `${item.dataset.label}: `
                            : "";

                        return `${name}${formatValue(value, unit)}`;
                    },
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
                    maxTicksLimit: 10,
                    maxRotation: 0,
                },
            },
            y: {
                beginAtZero: true,
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

function horizontalise(options, theme, unit) {
    options.indexAxis = "y";
    options.interaction = { mode: "nearest", axis: "y", intersect: false };
    options.layout.padding.right = 44;
    options.scales.x.beginAtZero = true;
    options.scales.x.border = { display: false, dash: [4, 4] };
    options.scales.x.grid = { color: theme.grid };
    options.scales.x.ticks = {
        color: theme.text,
        precision: 0,
        maxTicksLimit: 6,
    };
    options.scales.y.border = { display: false };
    options.scales.y.grid = { display: false };
    options.scales.y.ticks = {
        color: theme.text,
        autoSkip: false,
        callback(value) {
            const label = this.getLabelForValue(value);
            const limit = Math.max(10, Math.floor(this.chart.width / 18));
            return label.length > limit
                ? label.slice(0, limit - 1) + "\u2026"
                : label;
        },
    };
    options.plugins.tooltip.callbacks.label = (item) =>
        formatValue(item.parsed.x, unit);
}

/* ───────────── plugins ───────────── */

// Writes the value at the end of each horizontal bar.
const valueLabels = {
    id: "valueLabels",
    afterDatasetsDraw(chart, _args, options) {
        if (!options?.enabled) {
            return;
        }

        const { ctx } = chart;
        ctx.save();
        ctx.font = `600 12px ${Chart.defaults.font.family}`;
        ctx.fillStyle = options.color;
        ctx.textBaseline = "middle";

        chart.getDatasetMeta(0).data.forEach((bar, index) => {
            const value = chart.data.datasets[0].data[index];

            if (value === null || value === undefined) {
                return;
            }

            ctx.fillText(
                formatValue(Number(value), options.unit),
                bar.x + 8,
                bar.y,
            );
        });
        ctx.restore();
    },
};

// Total (or custom text) in the middle of a doughnut.
const centerText = {
    id: "centerText",
    afterDraw(chart, _args, options) {
        if (!options?.enabled) {
            return;
        }

        const { ctx, chartArea } = chart;
        const x = (chartArea.left + chartArea.right) / 2;
        const y = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillStyle = options.strong;
        ctx.font = `700 24px ${Chart.defaults.font.family}`;
        ctx.fillText(options.value, x, y - 8);
        ctx.fillStyle = options.muted;
        ctx.font = `500 11px ${Chart.defaults.font.family}`;
        ctx.fillText(options.label, x, y + 14);
        ctx.restore();
    },
};

/* ───────────── chart builders ───────────── */

function createSparkline(canvas, theme) {
    const values = readJson(canvas, "values");

    return new Chart(canvas, {
        type: "line",
        data: {
            labels: values.map((_, index) => index + 1),
            datasets: [
                {
                    data: values,
                    borderColor: theme.dark ? "#60a5fa" : "#2563eb",
                    backgroundColor: areaGradient(
                        theme.dark ? "#60a5fa" : "#2563eb",
                    ),
                    fill: true,
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
                x: { display: false },
                y: { display: false },
            },
        },
    });
}

// Time series as a smooth area line (single series or several).
function createLine(canvas, theme) {
    const labels = readJson(canvas, "labels");
    const unit = canvas.dataset.unit ?? "";
    const datasets = readJson(canvas, "datasets", null) ?? [
        {
            label: canvas.dataset.seriesLabel ?? "",
            data: readJson(canvas, "values"),
        },
    ];
    const multi = datasets.length > 1;
    const sparse = labels.length <= 2;

    const options = baseOptions(theme, unit, { legend: multi });
    options.scales.y.suggestedMax = Math.max(
        4,
        ...datasets.flatMap((set) =>
            set.data.map((value) => Number(value) || 0),
        ),
    );

    return new Chart(canvas, {
        type: "line",
        data: {
            labels,
            datasets: datasets.map((set, index) => {
                const color =
                    set.color ??
                    colorFor(set.label ?? "", index === 0 ? 0 : index);

                return {
                    label: set.label,
                    data: set.data,
                    borderColor: color,
                    backgroundColor: multi
                        ? withAlpha(color, 0.08)
                        : areaGradient(color),
                    fill: true,
                    borderWidth: 2.5,
                    tension: 0.35,
                    spanGaps: true,
                    pointRadius: sparse ? 4 : 0,
                    pointHoverRadius: 6,
                    pointBackgroundColor: theme.surface,
                    pointBorderColor: color,
                    pointBorderWidth: 2,
                };
            }),
        },
        options,
    });
}

// Vertical bars. Several datasets render grouped, or stacked with data-stacked.
function createBar(canvas, theme) {
    const labels = readJson(canvas, "labels");
    const unit = canvas.dataset.unit ?? "";
    const stacked = canvas.dataset.stacked === "true";
    const datasets = readJson(canvas, "datasets", null) ?? [
        {
            label: canvas.dataset.seriesLabel ?? "",
            data: readJson(canvas, "values"),
        },
    ];
    const multi = datasets.length > 1;

    const options = baseOptions(theme, unit, { legend: multi });
    options.scales.y.suggestedMax = Math.max(
        4,
        ...datasets.flatMap((set) =>
            set.data.map((value) => Number(value) || 0),
        ),
    );

    if (stacked) {
        options.scales.x.stacked = true;
        options.scales.y.stacked = true;
        options.scales.y.suggestedMax = undefined;
    }

    return new Chart(canvas, {
        type: "bar",
        data: {
            labels,
            datasets: datasets.map((set, index) => {
                const color = set.color ?? colorFor(set.label ?? "", index);

                return {
                    label: set.label,
                    data: set.data,
                    backgroundColor: color,
                    hoverBackgroundColor: withAlpha(color, 0.85),
                    borderRadius: stacked ? 4 : 8,
                    borderSkipped: false,
                    maxBarThickness: multi ? 22 : 28,
                };
            }),
        },
        options,
    });
}

// Ranked categories: one colour per bar with the value written at the bar's end.
function createHorizontalBar(canvas, theme) {
    const labels = readJson(canvas, "labels");
    const values = readJson(canvas, "values");
    const unit = canvas.dataset.unit ?? "";
    const options = baseOptions(theme, unit);

    horizontalise(options, theme, unit);
    options.plugins.valueLabels = { enabled: true, color: theme.text, unit };

    return new Chart(canvas, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: canvas.dataset.uniform
                        ? labels.map(() => (theme.dark ? "#3b82f6" : "#2563eb"))
                        : colorsFor(labels),
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 26,
                },
            ],
        },
        options,
        plugins: [valueLabels],
    });
}

// Part-of-whole with a total in the centre and a legend underneath.
function createDoughnut(canvas, theme) {
    const labels = readJson(canvas, "labels");
    const values = readJson(canvas, "values").map(
        (value) => Number(value) || 0,
    );
    const total = values.reduce((sum, value) => sum + value, 0);
    const centerLabel = canvas.dataset.centerLabel ?? "Total";

    return new Chart(canvas, {
        type: "doughnut",
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: colorsFor(labels),
                    borderColor: theme.surface,
                    borderWidth: 3,
                    borderRadius: 4,
                    spacing: 1,
                    hoverOffset: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
            layout: { padding: 4 },
            plugins: {
                legend: {
                    display: !canvas.hasAttribute("data-external-legend"),
                    position: "bottom",
                    labels: {
                        color: theme.text,
                        usePointStyle: true,
                        pointStyle: "circle",
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 14,
                        generateLabels: (chart) =>
                            chart.data.labels.map((label, index) => ({
                                text: `${label} (${numberFormat.format(values[index])})`,
                                fillStyle:
                                    chart.data.datasets[0].backgroundColor[
                                        index
                                    ],
                                strokeStyle:
                                    chart.data.datasets[0].backgroundColor[
                                        index
                                    ],
                                fontColor: theme.text,
                                pointStyle: "circle",
                                hidden: !chart.getDataVisibility(index),
                                index,
                            })),
                    },
                },
                tooltip: {
                    backgroundColor: theme.dark ? "#f8fafc" : "#0f172a",
                    titleColor: theme.dark ? "#0f172a" : "#f8fafc",
                    bodyColor: theme.dark ? "#0f172a" : "#f8fafc",
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: (item) =>
                            `${item.label}: ${numberFormat.format(item.raw)} (${total === 0 ? 0 : Math.round((item.raw / total) * 100)}%)`,
                    },
                },
                centerText: {
                    enabled: true,
                    value: numberFormat.format(total),
                    label: centerLabel,
                    strong: theme.strong,
                    muted: theme.text,
                },
            },
        },
        plugins: [centerText],
    });
}

function createReportChart(canvas) {
    const theme = getTheme();

    switch (canvas.dataset.reportChart) {
        case "sparkline":
            return createSparkline(canvas, theme);
        case "trend":
        case "registrations":
            return createLine(canvas, theme);
        case "activity":
        case "staff-comparison":
            return createBar(canvas, theme);
        case "status":
        case "citizens":
        case "blockchain":
        case "documents":
            return createDoughnut(canvas, theme);
        case "permit-type":
        case "processing-by-type":
        case "staff-workload":
        case "verification-status":
            return createHorizontalBar(canvas, theme);
        default:
            return createBar(canvas, theme);
    }
}

/* ───────────── lifecycle ───────────── */

let reportCharts = [];
let themeTimer;

function showChartError(canvas) {
    const frame = canvas.closest("[data-chart-frame]");

    if (!frame) {
        return;
    }

    frame.querySelector("[data-chart-skeleton]")?.remove();
    canvas.hidden = true;

    if (!frame.querySelector("[data-chart-error]")) {
        const message = document.createElement("p");
        message.dataset.chartError = "";
        message.setAttribute("role", "alert");
        message.className =
            "flex h-full items-center justify-center text-sm text-rose-700 dark:text-rose-300";
        message.textContent = "This chart could not be displayed.";
        frame.appendChild(message);
    }
}

// Canvases are looked up every time because the admin layout swaps page content.
function recreateCharts() {
    reportCharts.forEach((chart) => chart.destroy());
    reportCharts = [...document.querySelectorAll("[data-report-chart]")]
        .map((canvas) => {
            try {
                const chart = createReportChart(canvas);
                canvas
                    .closest("[data-chart-frame]")
                    ?.querySelector("[data-chart-skeleton]")
                    ?.remove();

                return chart;
            } catch (error) {
                console.error(
                    "Chart failed:",
                    canvas.dataset.reportChart,
                    error,
                );
                showChartError(canvas);

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

/* ───────────── date presets ───────────── */

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function presetRange(preset, now) {
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    switch (preset) {
        case "today":
            break;
        case "week": {
            // Monday as the first day of the week
            const offset = (start.getDay() + 6) % 7;
            start.setDate(start.getDate() - offset);
            break;
        }
        case "month":
            start.setDate(1);
            break;
        case "quarter":
            start.setMonth(Math.floor(start.getMonth() / 3) * 3, 1);
            break;
        case "year":
            start.setMonth(0, 1);
            break;
        default: // last30
            start.setDate(start.getDate() - 29);
    }

    return [start, now];
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

    const [from, to] = presetRange(button.dataset.reportPreset, new Date());
    fromInput.value = formatDate(from);
    toInput.value = formatDate(to);
    fromInput.form.requestSubmit();
});

/* ───────────── sortable tables ───────────── */

function cellValue(row, index) {
    const text = (row.children[index]?.textContent ?? "").trim();
    const number = parseFloat(text.replace(/[^0-9.\-]/g, ""));

    return text !== "" &&
        text !== "—" &&
        !Number.isNaN(number) &&
        /\d/.test(text)
        ? number
        : text.toLowerCase();
}

// Click a column heading inside [data-report-sortable] to sort by it.
document.addEventListener("click", (event) => {
    const head = event.target.closest("[data-report-sortable] th");

    if (!head) {
        return;
    }

    const table = head.closest("table");
    const body = table.tBodies[0];
    const index = [...head.parentElement.children].indexOf(head);
    const direction =
        head.getAttribute("aria-sort") === "ascending"
            ? "descending"
            : "ascending";
    const factor = direction === "ascending" ? 1 : -1;

    [...head.parentElement.children].forEach((th) =>
        th.removeAttribute("aria-sort"),
    );
    head.setAttribute("aria-sort", direction);

    [...body.rows]
        .sort((a, b) => {
            const left = cellValue(a, index);
            const right = cellValue(b, index);

            if (typeof left === "number" && typeof right === "number") {
                return (left - right) * factor;
            }

            return String(left).localeCompare(String(right)) * factor;
        })
        .forEach((row) => body.appendChild(row));
});
