class RealTimeCharts {
    constructor() {
        this.charts = new Map();
        this.streams = new Map();
        this.lastUpdate = new Map();
        this.updateInterval = 1000; // 1 second
        
        this.initializeCharts();
        this.initializeWebSocket();
        this.startPolling();
    }

    initializeCharts() {
        // Dynamic Line Chart for API Metrics
        this.initializeDynamicLineChart('api-metrics-chart', {
            title: 'API Response Time',
            yAxis: 'Response Time (ms)',
            datasets: ['Success', 'Error'],
            colors: ['#46b450', '#dc3232']
        });

        // Circular Progress for Sync Status
        this.initializeCircularProgress('sync-status-progress', {
            radius: 80,
            lineWidth: 15,
            colors: {
                success: '#46b450',
                pending: '#00a0d2',
                failed: '#dc3232'
            }
        });

        // Live Order Flow
        this.initializeLiveOrderFlow('order-flow-visualization', {
            width: 800,
            height: 400,
            nodeRadius: 20,
            statuses: ['pending', 'processing', 'printing', 'shipped', 'completed']
        });

        // Real-time Error Distribution
        this.initializeErrorDistribution('error-distribution-chart', {
            height: 300,
            barColor: '#dc3232',
            maxBars: 10
        });
    }

    initializeDynamicLineChart(elementId, config) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: config.datasets.map((label, index) => ({
                    label,
                    data: [],
                    borderColor: config.colors[index],
                    fill: false,
                    tension: 0.4
                }))
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'second',
                            displayFormats: {
                                second: 'HH:mm:ss'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: config.yAxis
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: config.title
                    },
                    streaming: {
                        duration: 20000,
                        refresh: 1000,
                        delay: 2000
                    }
                }
            }
        });

        this.charts.set(elementId, chart);
    }

    initializeCircularProgress(elementId, config) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const progress = new CircularProgressBar(element, {
            radius: config.radius,
            lineWidth: config.lineWidth,
            colors: config.colors,
            animated: true,
            animationDuration: 1000
        });

        this.charts.set(elementId, progress);
    }

    initializeLiveOrderFlow(elementId, config) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const flow = new OrderFlowVisualization(element, {
            width: config.width,
            height: config.height,
            nodeRadius: config.nodeRadius,
            statuses: config.statuses,
            animated: true
        });

        this.charts.set(elementId, flow);
    }

    initializeErrorDistribution(elementId, config) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const distribution = new ErrorDistributionChart(element, {
            height: config.height,
            barColor: config.barColor,
            maxBars: config.maxBars,
            animated: true
        });

        this.charts.set(elementId, distribution);
    }

    initializeWebSocket() {
        const socket = new WebSocket(wpwpsAdmin.wsUrl);

        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleRealtimeUpdate(data);
        };

        socket.onerror = (error) => {
            console.error('WebSocket error:', error);
            this.fallbackToPolling();
        };

        this.socket = socket;
    }

    handleRealtimeUpdate(data) {
        switch (data.channel) {
            case 'sync_status':
                this.updateSyncStatus(data.data);
                break;
            case 'api_metrics':
                this.updateApiMetrics(data.data);
                break;
            case 'order_updates':
                this.updateOrderFlow(data.data);
                break;
            case 'error_alerts':
                this.updateErrorDistribution(data.data);
                break;
        }
    }

    startPolling() {
        setInterval(() => {
            this.charts.forEach((chart, id) => {
                const lastUpdate = this.lastUpdate.get(id) || 0;
                if (Date.now() - lastUpdate > 5000) { // 5 seconds timeout
                    this.refreshChart(id);
                }
            });
        }, this.updateInterval);
    }

    refreshChart(chartId) {
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_realtime_data',
                chart: chartId,
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.handleRealtimeUpdate({
                        channel: chartId,
                        data: response.data
                    });
                    this.lastUpdate.set(chartId, Date.now());
                }
            }
        });
    }

    updateSyncStatus(data) {
        const progress = this.charts.get('sync-status-progress');
        if (progress) {
            progress.update({
                success: data.synced / data.total_products * 100,
                pending: data.pending / data.total_products * 100,
                failed: data.failed / data.total_products * 100
            });
        }
    }

    updateApiMetrics(data) {
        const chart = this.charts.get('api-metrics-chart');
        if (chart) {
            const timestamp = new Date();
            chart.data.labels.push(timestamp);
            chart.data.datasets[0].data.push({
                x: timestamp,
                y: data.avg_response_time
            });
            chart.data.datasets[1].data.push({
                x: timestamp,
                y: data.errors
            });
            
            // Keep only last 20 data points
            if (chart.data.labels.length > 20) {
                chart.data.labels.shift();
                chart.data.datasets.forEach(dataset => dataset.data.shift());
            }
            
            chart.update('quiet');
        }
    }

    updateOrderFlow(data) {
        const flow = this.charts.get('order-flow-visualization');
        if (flow) {
            flow.updateNodes(data.orders);
        }
    }

    updateErrorDistribution(data) {
        const distribution = this.charts.get('error-distribution-chart');
        if (distribution) {
            distribution.update(data);
        }
    }
}

// Initialize real-time charts
jQuery(document).ready(function($) {
    window.realTimeCharts = new RealTimeCharts();
});