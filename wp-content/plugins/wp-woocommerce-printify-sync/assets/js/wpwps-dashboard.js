/**
 * Dashboard JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

(function($) {
    'use strict';

    // Chart instance
    let chart = null;

    /**
     * Show alert message.
     *
     * @param {string} message Alert message.
     * @param {string} type    Alert type.
     */
    function showAlert(message, type = 'success') {
        const alert = $('<div class="wpwps-alert wpwps-alert-' + type + '">' + message + '</div>');
        $('.wpwps-alert-container').html(alert);

        // Scroll to alert
        $('html, body').animate({
            scrollTop: $('.wpwps-alert-container').offset().top - 50
        }, 500);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Initialize dashboard chart.
     */
    function initChart() {
        const ctx = document.getElementById('wpwps-chart');
        
        if (!ctx) {
            return;
        }
        
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: wpwps.i18n.sales,
                    data: [],
                    backgroundColor: 'rgba(126, 59, 208, 0.2)',
                    borderColor: 'rgba(126, 59, 208, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(126, 59, 208, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10,
                        bodyFont: {
                            size: 14
                        },
                        titleFont: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                }
            }
        });
        
        // Load initial data
        loadChartData('30d', 'sales');
    }

    /**
     * Load chart data via AJAX.
     *
     * @param {string} dateRange Date range.
     * @param {string} chartType Chart type.
     */
    function loadChartData(dateRange, chartType) {
        $('.chart-loading').removeClass('d-none');
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_dashboard_data',
                nonce: wpwps.nonce,
                date_range: dateRange,
                chart_type: chartType
            },
            success: function(response) {
                if (response.success) {
                    updateChart(response.data.chart_data, chartType);
                } else {
                    showAlert(response.data.message, 'danger');
                }
            },
            error: function() {
                showAlert(wpwps.i18n.error, 'danger');
            },
            complete: function() {
                $('.chart-loading').addClass('d-none');
            }
        });
    }

    /**
     * Update chart with new data.
     *
     * @param {object} data      Chart data.
     * @param {string} chartType Chart type.
     */
    function updateChart(data, chartType) {
        if (!chart) {
            return;
        }
        
        chart.data.labels = data.labels;
        chart.data.datasets[0].data = data.values;
        
        if (chartType === 'sales') {
            chart.data.datasets[0].label = wpwps.i18n.sales;
            chart.data.datasets[0].backgroundColor = 'rgba(126, 59, 208, 0.2)';
            chart.data.datasets[0].borderColor = 'rgba(126, 59, 208, 1)';
            chart.data.datasets[0].pointBackgroundColor = 'rgba(126, 59, 208, 1)';
        } else {
            chart.data.datasets[0].label = wpwps.i18n.orders;
            chart.data.datasets[0].backgroundColor = 'rgba(40, 167, 69, 0.2)';
            chart.data.datasets[0].borderColor = 'rgba(40, 167, 69, 1)';
            chart.data.datasets[0].pointBackgroundColor = 'rgba(40, 167, 69, 1)';
        }
        
        chart.update();
    }

    /**
     * Initialize functions on document ready.
     */
    $(document).ready(function() {
        // Initialize chart
        initChart();
        
        // Date range filter
        $('.date-range').on('click', function() {
            const $this = $(this);
            const dateRange = $this.data('range');
            const chartType = $('.chart-type.active').data('type');
            
            $('.date-range').removeClass('active');
            $this.addClass('active');
            
            loadChartData(dateRange, chartType);
        });
        
        // Chart type filter
        $('.chart-type').on('click', function() {
            const $this = $(this);
            const chartType = $this.data('type');
            const dateRange = $('.date-range.active').data('range');
            
            $('.chart-type').removeClass('active').addClass('btn-outline-primary').removeClass('btn-primary');
            $this.addClass('active').removeClass('btn-outline-primary').addClass('btn-primary');
            
            loadChartData(dateRange, chartType);
        });
    });

})(jQuery);
