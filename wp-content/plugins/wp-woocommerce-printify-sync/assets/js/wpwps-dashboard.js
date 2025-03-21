/**
 * Dashboard page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    
    // Revenue chart instance
    let revenueChart;

    // Initialize revenue chart
    initializeRevenueChart('week');

    // Handle chart filter buttons
    $('.chart-filters button').on('click', function() {
        const filter = $(this).data('filter');
        
        // Toggle active class
        $('.chart-filters button').removeClass('active');
        $(this).addClass('active');
        
        // Update chart data
        loadChartData(filter);
    });

    // Test API connection button
    $('#wpwps-test-api').on('click', function() {
        const button = $(this);
        
        // Show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_printify_connection',
                nonce: wpwps_data.nonce,
                api_key: 'current', // Use the current API key
                api_endpoint: 'current' // Use the current API endpoint
            },
            success: function(response) {
                if (response.success) {
                    // Connection successful
                    $('.status-unknown').replaceWith('<span class="status-healthy"><i class="fas fa-check-circle"></i></span>');
                } else {
                    // Connection failed
                    $('.status-unknown').replaceWith('<span class="status-error"><i class="fas fa-times-circle"></i></span>');
                }
            },
            error: function() {
                // Connection error
                $('.status-unknown').replaceWith('<span class="status-error"><i class="fas fa-times-circle"></i></span>');
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false);
                button.html('Test Connection');
            }
        });
    });

    // Load recent activities
    loadRecentActivities();

    // Function to initialize revenue chart
    function initializeRevenueChart(period) {
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], // Will be populated from AJAX
                datasets: [
                    {
                        label: wpwps_dashboard.revenue_label,
                        backgroundColor: 'rgba(126, 59, 208, 0.1)',
                        borderColor: 'rgba(126, 59, 208, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(126, 59, 208, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(126, 59, 208, 1)',
                        data: [], // Will be populated from AJAX
                        fill: true
                    },
                    {
                        label: wpwps_dashboard.profit_label,
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(40, 167, 69, 1)',
                        data: [], // Will be populated from AJAX
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.raw;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                    }
                }
            }
        });
        
        // Load initial data
        loadChartData(period);
    }

    // Function to load chart data
    function loadChartData(period) {
        const chartContainer = $('.chart-container');
        
        // Show loading indicator
        chartContainer.addClass('loading');
        chartContainer.append('<div class="chart-loading"><i class="fas fa-spinner fa-spin"></i> ' + wpwps_dashboard.loading + '</div>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'wpwps_get_revenue_data',
                nonce: wpwps_data.nonce,
                period: period
            },
            success: function(response) {
                // Remove loading indicator
                chartContainer.removeClass('loading');
                $('.chart-loading').remove();
                
                if (response.success) {
                    // Update chart data
                    revenueChart.data.labels = response.data.labels;
                    revenueChart.data.datasets[0].data = response.data.revenue;
                    revenueChart.data.datasets[1].data = response.data.profit;
                    revenueChart.update();
                } else {
                    // Show error message
                    chartContainer.html('<div class="chart-error">' + wpwps_dashboard.error_loading + '</div>');
                }
            },
            error: function() {
                // Remove loading indicator
                chartContainer.removeClass('loading');
                $('.chart-loading').remove();
                
                // Show error message
                chartContainer.html('<div class="chart-error">' + wpwps_dashboard.error_loading + '</div>');
            }
        });
    }

    // Function to load recent activities
    function loadRecentActivities() {
        const activitiesList = $('.activity-list');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'wpwps_get_activities',
                nonce: wpwps_data.nonce,
                limit: 5
            },
            success: function(response) {
                if (response.success && response.data.activities.length > 0) {
                    let activitiesHtml = '';
                    
                    // Build activities HTML
                    $.each(response.data.activities, function(i, activity) {
                        activitiesHtml += '<div class="activity-item">';
                        activitiesHtml += '<div class="activity-time">' + activity.relative_time + '</div>';
                        activitiesHtml += '<div class="activity-content"><i class="' + activity.icon + '"></i> ' + activity.message + '</div>';
                        activitiesHtml += '</div>';
                    });
                    
                    // Update activities list
                    activitiesList.html(activitiesHtml);
                } else {
                    // No activities
                    activitiesList.html('<div class="activity-item"><div class="activity-content">' + wpwps_dashboard.no_data + '</div></div>');
                }
            },
            error: function() {
                // Error loading activities
                activitiesList.html('<div class="activity-item"><div class="activity-content text-danger">' + wpwps_dashboard.error_loading + '</div></div>');
            }
        });
    }
});
