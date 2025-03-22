/**
 * Dashboard page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Initialize Charts
    let revenueChart = null;
    
    function initCharts() {
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    borderColor: '#7e3bd0',
                    backgroundColor: 'rgba(126, 59, 208, 0.1)', 
                    data: []
                }, {
                    label: 'Profit',
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + wpwps_data.currency_symbol + context.raw;
                            }
                        }
                    }
                }
            }
        });
    }

    // Load chart data
    function loadChartData(period = 'week') {
        $.ajax({
            url: wpwps_data.ajax_url,
            data: {
                action: 'wpwps_get_revenue_data',
                period: period,
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateChart(response.data);
                }
            }
        });
    }

    // Update chart with new data
    function updateChart(data) {
        revenueChart.data.labels = data.labels;
        revenueChart.data.datasets[0].data = data.revenue;
        revenueChart.data.datasets[1].data = data.profit;
        revenueChart.update();
    }

    // Period selector
    $('#chart-period').on('change', function() {
        loadChartData($(this).val());
    });

    // Initialize everything
    initCharts();
    loadChartData();

    // Test API connection
    $('#test-api-connection').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_printify_connection',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.status-unknown').replaceWith('<span class="status-healthy"><i class="fas fa-check-circle"></i></span>');
                } else {
                    $('.status-unknown').replaceWith('<span class="status-error"><i class="fas fa-times-circle"></i></span>');
                }
            },
            error: function() {
                $('.status-unknown').replaceWith('<span class="status-error"><i class="fas fa-times-circle"></i></span>');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
