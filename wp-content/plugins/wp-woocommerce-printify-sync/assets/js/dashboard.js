(function($) {
    'use strict';

    const WPWPS_Dashboard = {
        charts: {},
        refreshInterval: 60000, // 1 minute
        
        init: function() {
            this.initCharts();
            this.initRefresh();
            this.initActions();
        },

        initCharts: function() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenue-chart');
            if (revenueCtx) {
                this.charts.revenue = new Chart(revenueCtx, {
                    type: 'line',
                    data: this.getRevenueChartData(),
                    options: this.getChartOptions('Revenue')
                });
            }

            // Orders Chart
            const ordersCtx = document.getElementById('orders-chart');
            if (ordersCtx) {
                this.charts.orders = new Chart(ordersCtx, {
                    type: 'bar',
                    data: this.getOrdersChartData(),
                    options: this.getChartOptions('Orders')
                });
            }
        },

        initRefresh: function() {
            setInterval(() => this.refreshData(), this.refreshInterval);
        },

        initActions: function() {
            $('.wpwps-sync-now').on('click', (e) => {
                e.preventDefault();
                this.syncNow();
            });
        },

        refreshData: function() {
            $.ajax({
                url: wpwpsDashboard.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_refresh_dashboard',
                    nonce: wpwpsDashboard.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data.stats);
                        this.updateCharts(response.data.charts);
                        this.updateOrders(response.data.recentOrders);
                        this.updateApiStatus(response.data.apiStatus);
                    }
                }
            });
        },

        syncNow: function() {
            const $button = $('.wpwps-sync-now');
            $button.prop('disabled', true).addClass('updating-message');

            $.ajax({
                url: wpwpsDashboard.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_sync_now',
                    nonce: wpwpsDashboard.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.refreshData();
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('updating-message');
                }
            });
        },

        updateStats: function(stats) {
            Object.entries(stats).forEach(([key, value]) => {
                $(`.stat-value[data-stat="${key}"]`).text(value);
            });
        },

        updateCharts: function(chartData) {
            if (this.charts.revenue) {
                this.charts.revenue.data = this.getRevenueChartData(chartData.revenue);
                this.charts.revenue.update();
            }
            if (this.charts.orders) {
                this.charts.orders.data = this.getOrdersChartData(chartData.orders);
                this.charts.orders.update();
            }
        },

        updateOrders: function(orders) {
            const $tbody = $('.recent-orders tbody');
            $tbody.empty();

            orders.forEach(order => {
                $tbody.append(this.getOrderRow(order));
            });
        },

        updateApiStatus: function(status) {
            Object.entries(status).forEach(([key, value]) => {
                const $item = $(`.status-item[data-api="${key}"]`);
                $item.removeClass('success error warning')
                     .addClass(value ? 'success' : 'error');
            });
        },

        showNotice: function(type, message) {
            const $notice = $('<div>')
                .addClass(`notice notice-${type} is-dismissible`)
                .append($('<p>').text(message));

            $('.wrap > h1').after($notice);

            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 3000);
        },

        getChartOptions: function(label) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: label
                    }
                }
            };
        }
    };

    $(document).ready(() => WPWPS_Dashboard.init());

})(jQuery);