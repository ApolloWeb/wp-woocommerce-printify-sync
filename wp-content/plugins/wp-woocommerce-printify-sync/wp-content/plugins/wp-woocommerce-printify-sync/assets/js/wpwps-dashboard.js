(function($) {
    'use strict';

    const WPWPSDashboard = {
        charts: {},

        init: function() {
            this.initSalesChart();
            this.initRefreshHandlers();
            this.initAutoRefresh();
        },

        initSalesChart: function() {
            const ctx = document.getElementById('wpwps-sales-chart');
            if (!ctx) return;

            this.charts.sales = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: window.wpwpsDashboard.salesData.dates,
                    datasets: [{
                        label: 'Revenue',
                        data: window.wpwpsDashboard.salesData.daily.map(d => d.revenue),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        initRefreshHandlers: function() {
            $('.wpwps-refresh-widget').on('click', function(e) {
                e.preventDefault();
                const widget = $(this).closest('.wpwps-widget');
                WPWPSDashboard.refreshWidget(widget.data('widget-type'));
            });
        },

        initAutoRefresh: function() {
            setInterval(() => {
                this.refreshAllWidgets();
            }, 300000); // Refresh every 5 minutes
        },

        refreshWidget: function(widgetType) {
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_get_dashboard_data',
                    nonce: wpwps.nonce,
                    widget: widgetType
                },
                success: function(response) {
                    if (response.success) {
                        WPWPSDashboard.updateWidget(widgetType, response.data);
                    }
                }
            });
        },

        refreshAllWidgets: function() {
            this.refreshWidget('email_queue');
            this.refreshWidget('sync_status');
            this.refreshWidget('sales');
        },

        updateWidget: function(widgetType, data) {
            switch(widgetType) {
                case 'sales':
                    this.updateSalesChart(data);
                    break;
                case 'email_queue':
                case 'sync_status':
                    this.updateWidgetHTML(widgetType, data);
                    break;
            }
        },

        updateSalesChart: function(data) {
            if (this.charts.sales) {
                this.charts.sales.data.labels = data.dates;
                this.charts.sales.data.datasets[0].data = data.daily.map(d => d.revenue);
                this.charts.sales.update();
            }
        },

        updateWidgetHTML: function(widgetType, data) {
            const widget = $(`.wpwps-widget-${widgetType}`);
            if (widget.length) {
                widget.html(data.html);
            }
        }
    };

    $(document).ready(function() {
        WPWPSDashboard.init();
    });

})(jQuery);
