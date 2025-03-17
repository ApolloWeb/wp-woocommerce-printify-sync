(function($) {
    'use strict';

    const WPWPS_Dashboard = {
        charts: {},
        
        init: function() {
            this.loadStats();
            this.initCharts();
            this.initRefresh();
        },

        loadStats: function() {
            $.ajax({
                url: wpwpsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_get_ticket_stats',
                    _ajax_nonce: wpwpsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data);
                    } else {
                        this.showError(wpwpsAdmin.i18n.error_loading);
                    }
                },
                error: () => {
                    this.showError(wpwpsAdmin.i18n.error_loading);
                }
            });
        },

        updateStats: function(data) {
            $('#open-tickets').text(data.open);
            $('#pending-tickets').text(data.pending);
            $('#avg-response-time').text(this.formatTime(data.response_time));
            $('#resolution-rate').text(this.calculateResolutionRate(data) + '%');
            
            this.updateCharts(data);
        },

        initCharts: function() {
            this.charts.categories = new Chart($('#categories-chart'), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#2271b1',
                            '#8c8f94',
                            '#d63638',
                            '#00a32a'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            this.charts.responseTime = new Chart($('#response-time-chart'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Response Time (hours)',
                        data: [],
                        borderColor: '#2271b1',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },

        updateCharts: function(data) {
            // Update category chart
            this.charts.categories.data.labels = data.categories.map(c => c.name);
            this.charts.categories.data.datasets[0].data = data.categories.map(c => c.count);
            this.charts.categories.update();

            // Update other charts...
        },

        initRefresh: function() {
            setInterval(() => this.loadStats(), 30000); // Refresh every 30 seconds
        },

        formatTime: function(hours) {
            if (hours < 1) {
                return Math.round(hours * 60) + ' min';
            }
            return hours.toFixed(1) + ' hrs';
        },

        calculateResolutionRate: function(data) {
            return Math.round((data.resolved / data.total) * 100);
        },

        showError: function(message) {
            // Implementation
        }
    };

    $(document).ready(function() {
        WPWPS_Dashboard.init();
    });

})(jQuery);