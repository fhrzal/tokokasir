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
import { compactRupiah, rupiah } from './format';

Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend);

// Token warna grafik (satu sumbu, garis bantu tipis, label memakai warna teks).
const INK = {
    surface: '#ffffff',
    muted: '#898781',
    secondary: '#52514e',
    grid: '#e1e0d9',
    axis: '#c3c2b7',
};

export const SERIES = ['#2a78d6', '#eb6834', '#1baf7a'];

Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('--font-sans') || 'system-ui';
Chart.defaults.color = INK.muted;

/**
 * Grafik batang/garis rupiah per hari.
 * series: [{ label, data, type: 'bar' | 'line' }]
 */
export default (config) => ({
    chart: null,

    init() {
        const datasets = config.series.map((s, i) => {
            const color = SERIES[i];

            if (s.type === 'line') {
                return {
                    type: 'line',
                    label: s.label,
                    data: s.data,
                    borderColor: color,
                    backgroundColor: color,
                    borderWidth: 2,
                    tension: 0.25,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBorderColor: INK.surface,
                    pointBorderWidth: 2,
                    order: 0,
                };
            }

            return {
                type: 'bar',
                label: s.label,
                data: s.data,
                backgroundColor: color,
                hoverBackgroundColor: color,
                maxBarThickness: 24,
                borderRadius: { topLeft: 4, topRight: 4 },
                borderSkipped: 'start',
                order: 1,
            };
        });

        this.chart = new Chart(this.$refs.canvas, {
            data: { labels: config.labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: datasets.length > 1,
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, color: INK.secondary, sort: (a, b) => a.datasetIndex - b.datasetIndex },
                    },
                    tooltip: {
                        backgroundColor: '#0b0b0b',
                        padding: 10,
                        cornerRadius: 8,
                        boxPadding: 4,
                        callbacks: {
                            label: (ctx) => ` ${ctx.dataset.label}: ${rupiah(ctx.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: INK.axis },
                        ticks: { color: INK.muted, maxRotation: 0, autoSkipPadding: 12 },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: INK.grid, lineWidth: 1 },
                        border: { display: false },
                        ticks: { color: INK.muted, maxTicksLimit: 5, callback: (v) => compactRupiah(v) },
                    },
                },
            },
        });
    },

    destroy() {
        this.chart?.destroy();
    },
});
