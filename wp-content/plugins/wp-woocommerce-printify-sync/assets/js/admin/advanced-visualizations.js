class AdvancedVisualizations {
    constructor() {
        this.charts = new Map();
        this.initializeAdvancedCharts();
        this.bindInteractiveFeatures();
    }

    initializeAdvancedCharts() {
        // Initialize Heatmap
        this.initializeHeatmap('sync-activity-heatmap');
        
        // Initialize Treemap
        this.initializeTreemap('category-sync-treemap');
        
        // Initialize Funnel
        this.initializeFunnel('order-funnel');
        
        // Initialize Gauge
        this.initializeGauge('api-health-gauge');
    }

    initializeHeatmap(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const chart = echarts.init(element);
        this.charts.set(elementId, chart);

        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_heatmap_data',
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateHeatmap(chart, response.data);
                }
            }
        });
    }

    updateHeatmap(chart, data) {
        const hours = [...Array(24).keys()];
        const days = [...new Set(data.map(item => item.date))];

        const option = {
            tooltip: {
                position: 'top',
                formatter: (params) => {
                    return `${params.data[0]}, ${params.data[1]}:00<br>
                            Activity: ${params.data[2]}`;
                }
            },
            grid: {
                top: '10%',
                left: '5%',
                right: '5%',
                bottom: '10%'
            },
            xAxis: {
                type: 'category',
                data: days,
                splitArea: {
                    show: true
                }
            },
            yAxis: {
                type: 'category',
                data: hours,
                splitArea: {
                    show: true
                }
            },
            visualMap: {
                min: 0,
                max: Math.max(...data.map(item => item.value)),
                calculable: true,
                orient: 'horizontal',
                left: 'center',
                bottom: '0%'
            },
            series: [{
                name: 'Sync Activity',
                type: 'heatmap',
                data: data.map(item => [item.date, item.hour, item.value]),
                label: {
                    show: true
                },
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }]
        };

        chart.setOption(option);
    }

    initializeTreemap(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const chart = echarts.init(element);
        this.charts.set(elementId, chart);

        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_treemap_data',
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateTreemap(chart, response.data);
                }
            }
        });
    }

    updateTreemap(chart, data) {
        const option = {
            tooltip: {
                formatter: '{b}: {c}'
            },
            series: [{
                type: 'treemap',
                data: data,
                levels: [{
                    itemStyle: {
                        borderColor: '#fff',
                        borderWidth: 1,
                        gapWidth: 1
                    }
                }],
                label: {
                    show: true,
                    formatter: '{b}\n{c}'
                }
            }]
        };

        chart.setOption(option);
    }

    initializeFunnel(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const chart = echarts.init(element);
        this.charts.set(elementId, chart);

        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_funnel_data',
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateFunnel(chart, response.data);
                }
            }
        });
    }

    updateFunnel(chart, data) {
        const option = {
            tooltip: {
                trigger: 'item',
                formatter: '{b}: {c}'
            },
            series: [{
                name: 'Order Funnel',
                type: 'funnel',
                left: '10%',
                top: 60,
                bottom: 60,
                width: '80%',
                min: 0,
                max: Math.max(...data.map(item => item.value)),
                minSize: '0%',
                maxSize: '100%',
                sort: 'descending',
                gap: 2,
                label: {
                    show: true,
                    position: 'inside'
                },
                labelLine: {
                    length: 10,
                    lineStyle: {
                        width: 1,
                        type: 'solid'
                    }
                },
                itemStyle: {
                    borderColor: '#fff',
                    borderWidth: 1
                },
                emphasis: {
                    label: {
                        fontSize: 20
                    }
                },
                data: data
            }]
        };

        chart.setOption(option);
    }

    initializeGauge(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const chart = echarts.init(element);
        this.charts.set(elementId, chart);

        this.updateGauge(chart);
        setInterval(() => this.updateGauge(chart), 60000); // Update every minute
    }

    updateGauge(chart) {
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_get_gauge_data',
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    const option = {
                        series: [{
                            type: 'gauge',
                            detail: { formatter: '{value}%' },
                            data: [{
                                value: response.data.success_rate,
                                name: 'Success Rate'
                            }]
                        }]
                    };

                    chart.setOption(option);
                }
            }
        });
    }

    bindInteractiveFeatures() {
        // Handle window resize
        window.addEventListener('resize', () => {
            this.charts.forEach(chart => chart.resize());
        });

        // Handle theme changes
        document.addEventListener('wpwps_theme_changed', (e) => {
            const theme = e.detail.theme;
            this.charts.forEach(chart => {
                chart.dispose();
                chart = echarts.init(chart.getDom(), theme);
            });
        });

        // Handle chart interactions
        this.charts.forEach((chart, id) => {
            chart.on('click', (params) => {
                this.handleChartClick(id, params);
            });
        });
    }

    handleChartClick(chartId, params) {
        // Trigger custom event for other components
        const event = new CustomEvent('wpwps_chart_interaction', {
            detail: {
                chartId,
                data: params.data,
                dataIndex: params.dataIndex,
                seriesIndex: params.seriesIndex
            }
        });
        document.dispatchEvent(event);
    }
}

// Initialize advanced visualizations
jQuery(document).ready(function($) {
    window.advancedVisualizations = new AdvancedVisualizations();
});