jQuery(document).ready(function($) {
    const dashboard = {
        init: function() {
            this.initCharts();
            this.initRealTimeUpdates();
            this.initFilters();
            this.loadInitialData();
        },

        initCharts: function() {
            // Performance Chart
            const perfCtx = document.getElementById('performance-chart');
            if (perfCtx) {
                this.perfChart = new Chart(perfCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Orders Processed',
                            data: [],
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            // Error Rate Chart
            const errorCtx = document.getElementById('error-rate-chart');
            if (errorCtx) {
                this.errorChart = new Chart(errorCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Errors',
                            data: [],
                            backgroundColor: 'rgb(255, 99, 132)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        initRealTimeUpdates: function() {
            setInterval(() => this.updateDashboard(), 60000); // Update every minute
        },

        initFilters: function() {
            $('.wpwps-date-filter').on('change', () => this.loadFilteredData());
            $('.wpwps-status-filter').on('change', () => this.loadFilteredData());
        },

        loadInitialData: function() {
            this.showLoader();
            $.ajax({
                url: wpwpsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpwps_get_dashboard_data',
                    nonce: wpwpsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboardData(response.data);
                    }
                },
                complete: () => this.hideLoader()
            });
        },

        updateDashboard: function() {
            $.ajax({
                url: wpwpsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpwps_get_dashboard_updates',
                    nonce: wpwpsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateMetrics(response.data);
                    }
                }
            });
        },

        updateDashboardData: function(data) {
            // Update statistics
            this.updateStats(data.stats);
            
            // Update charts
            this.updatePerformanceChart(data.performance);
            this.updateErrorChart(data.errors);
            
            // Update recent orders table
            this.updateOrdersTable(data.recent_orders);
            
            // Update API health indicators
            this.updateApiHealth(data.api_health);
        },

        updateStats: function(stats) {
            Object.keys(stats).forEach(key => {
                $(`.wpwps-stat-${key}`).text(stats[key]);
            });
        },

        updatePerformanceChart: function(data) {
            if (this.perfChart) {
                this.perfChart.data.labels = data.map(d => d.date);
                this.perfChart.data.datasets[0].data = data.map(d => d.total_orders);
                this.perfChart.update();
            }
        },

        updateErrorChart: function(data) {
            if (this.errorChart) {
                this.errorChart.data.labels = data.map(d => d.type);
                this.errorChart.data.datasets[0].data = data.map(d => d.count);
                this.errorChart.update();
            }
        },

        updateOrdersTable: function(orders) {
            const tbody = $('#recent-orders tbody');
            tbody.empty();
            
            orders.forEach(order => {
                tbody.append(`
                    <tr>
                        <td>#${order.order_id}</td>
                        <td>${order.status}</td>
                        <td>${order.created_at}</td>
                        <td>${this.getActionButtons(order)}</td>
                    </tr>
                `);
            });
        },

        updateApiHealth: function(health) {
            $('.api-uptime').text(health.uptime + '%');
            $('.api-response-time').text(health.response_time + 'ms');
            $('.api-error-count').text(health.error_count);
            
            // Update health indicator color
            const indicator = $('.api-health-indicator');
            indicator.removeClass('good warning critical');
            indicator.addClass(this.getHealthClass(health.uptime));
        },

        getHealthClass: function(uptime) {
            if (uptime >= 99) return 'good';
            if (uptime >= 95) return 'warning';
            return 'critical';
        },

        getActionButtons: function(order) {
            return `
                <button class="button view-order" data-id="${order.order_id}">
                    ${wpwpsAdmin.i18n.view}
                </button>
                ${order.status === 'failed' ? `
                    <button class="button retry-order" data-id="${order.order_id}">
                        ${wpwpsAdmin.i18n.retry}
                    </button>
                ` : ''}
            `;
        },

        showLoader: function() {
            $('.wpwps-loader').show();
        },

        hideLoader: function() {
            $('.wpwps-loader').hide();
        }
    };

    dashboard.init();
});