import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    LineController,
    BarController,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Legend,
    Tooltip,
);

Chart.defaults.animation = false;
Chart.defaults.font.family = getComputedStyle(document.documentElement).fontFamily;

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (reducedMotion) {
    Chart.defaults.animation = false;
    Chart.defaults.transitions = {
        active: { animation: { duration: 0 } },
        resize: { animation: { duration: 0 } },
        show: { animation: { duration: 0 } },
        hide: { animation: { duration: 0 } },
    };
}

function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

function readChartData() {
    const node = document.getElementById('admin-analytics-chart-data');
    if (! node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent);
    } catch {
        return null;
    }
}

const data = readChartData();
if (data) {
    const ink = cssVar('--ink');
    const muted = cssVar('--muted');
    const teal = cssVar('--brand-teal');
    const clay = cssVar('--clay');
    const sage = cssVar('--sage');

    const trend = document.getElementById('analytics-trend-chart');
    if (trend instanceof HTMLCanvasElement) {
        new Chart(trend, {
            type: 'line',
            data: {
                labels: data.days,
                datasets: [
                    {
                        label: 'Created',
                        data: data.created,
                        borderColor: teal,
                        backgroundColor: teal,
                        borderDash: [],
                        pointStyle: 'circle',
                        tension: 0,
                    },
                    {
                        label: 'Closed',
                        data: data.closed,
                        borderColor: clay,
                        backgroundColor: clay,
                        borderDash: [6, 4],
                        pointStyle: 'rect',
                        tension: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: ink, usePointStyle: true } },
                },
                scales: {
                    x: { ticks: { color: muted }, grid: { color: cssVar('--line') } },
                    y: {
                        beginAtZero: true,
                        ticks: { color: muted, precision: 0 },
                        grid: { color: cssVar('--line') },
                    },
                },
            },
        });
    }

    const workload = document.getElementById('analytics-workload-chart');
    if (workload instanceof HTMLCanvasElement) {
        const instances = Array.isArray(data.instances) ? data.instances : [];
        new Chart(workload, {
            type: 'bar',
            data: {
                labels: instances.map((instance) => `${instance.sub_core} — ${instance.module}`),
                datasets: [
                    {
                        label: 'Open',
                        data: instances.map((instance) => instance.open),
                        backgroundColor: sage,
                        borderColor: sage,
                    },
                    {
                        label: 'High or urgent',
                        data: instances.map((instance) => instance.high_or_urgent),
                        backgroundColor: clay,
                        borderColor: clay,
                        borderWidth: 1,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: ink, usePointStyle: true } },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: false,
                        ticks: { color: muted, precision: 0 },
                        grid: { color: cssVar('--line') },
                    },
                    y: { ticks: { color: muted }, grid: { display: false } },
                },
            },
        });
    }
}
