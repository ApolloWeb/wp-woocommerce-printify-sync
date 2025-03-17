class PrintifyVisualizations {
    constructor() {
        this.charts = new Map();
        this.initializeCharts();
        this.setupRefreshIntervals();
        this.bindEvents();
    }

    initializeCharts() {
        // Sync Status Pie Chart
        this.initializePieChart('sync-status-chart');
        
        // API Performance Line Chart
        this.initializeLineChart('api-performance-chart');
        
        // Order Metrics Bar Chart
        this.initializeBarChart('order-metrics-chart');
        
        // Error Distribution Radar Chart
        this.initializeRadarChart('error-distribution-chart');
    }

    initializePieChart(elementId) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        this.charts.set(elementId, new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#46b450',
                        '#dc3232',
                        '#ffb900',
                        '#00a0d2'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        }));
    }

    initializeLineChart(elementId) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        this.charts.set(elementId, new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Requests'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        }));
    }

    initializeBarChart(elementId) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        this.charts.set(elementId, new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        }));
    }

    initializeRadarChart(elementId) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        this.charts.set(elementId, new Chart(ctx, {
            type: 'radar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Error Distribution',
                    data: [],
                    fill: true,
                    backgroundColor: 'rgba(220, 50, 50, 0.2)',
                    borderColor: 'rgb(220, 50, 50)',
                    pointBackgroundColor: 'rgb(220, 50, 50)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(220, 50, 50)'
                }]
            },
            options: {
                elements: {
                    line: {
                        borderWidth: 3
                    }
                }
            }
        }));
    }

    setupRefreshIntervals() {
        // Refresh charts based on their defined intervals
        const intervals = {
            'sync-status-chart': 30000,
            'api-performance-chart': 60000,
            'order-metrics-chart': 300000,
            'error-distribution-chart': 600000
        };

        Object.entries(intervals).forEach(([chartId, interval]) => {
            setInterval(() => this.refreshChart(chartId), interval);
        });
    }

    bindEvents() {
        // Handle date range changes
        $('.chart-date-range').on('change', (e) => {
            const chartId = $(e.target).data('chart');
            const range = $(e.target).val();
            this.refreshChart(chartId, { range });
        });

        // Handle chart type switches
        $('.chart-type-switch').on('change', (e) => {
            const chartId = $(e.target).data('chart');
            const type = $(e.target).val();
            this.updateChartType(chartId, type);
        });

        // Handle data point clicks
        this.charts.forEach((chart, chartId) => {
            chart.canvas.onclick = (evt) => {
                const points = chart.getElementsAtEventForMode(
                    evt,
                    'nearest',
                    { intersect: true },
                    false
                );

                if (points.length) {
                    const firstPoint = points[0];
                    this.handleDataPointClick(chartId, firstPoint);
                }
            };
        });
    }

    refreshChart(chartId, params = {}) {
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_chart_data',
                chart: chartId,
                params: params,
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success && this.charts.has(chartId)) {
                    const chart = this.charts.get(chartId);
                    chart.data = response.data;
                    chart.update();
                }
            }
        });
    }

    updateChartType(chartId, newType) {
        if (this.charts.has(chartId)) {
            const chart = this.charts.get(chartId);
            chart.config.type = newType;
            chart.update();
        }
    }

    handleDataPointClick(chartId, point) {
        const label = point.label;
        const value = point.value;

        // Trigger custom event for other components
        $(document).trigger('wpwps_chart_point_clicked', {
            chartId,
            label,
            value,
            dataset: point.datasetIndex
        });
    }
}

// Initialize visualizations
jQuery(document).ready(function($) {
    window.printifyVisualizations = new PrintifyVisualizations();
});