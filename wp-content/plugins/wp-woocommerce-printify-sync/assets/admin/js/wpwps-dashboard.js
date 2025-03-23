(function($) {
    'use strict';

    const WPWPSDashboard = {
        charts: {},

        init: function() {
            this.initCharts();
            this.initRefreshHandlers();
            this.startAutoRefresh();
        },

        initCharts: function() {
            // Orders Chart
            const ordersCtx = document.getElementById('ordersChart');
            if (ordersCtx) {
                this.charts.orders = new Chart(ordersCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Orders',
                            borderColor: '#96588a',
                            data: []
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        refreshData: function() {
            WPWPS.api.get('dashboard_stats')
                .then(response => {
                    if (response.success) {
                        this.updateCharts(response.data);
                        this.updateStats(response.data);
                    }
                });
        },

        startAutoRefresh: function() {
            setInterval(() => this.refreshData(), 60000); // Refresh every minute
            this.refreshData(); // Initial load
        }
    };

    $(document).ready(() => WPWPSDashboard.init());

})(jQuery);
