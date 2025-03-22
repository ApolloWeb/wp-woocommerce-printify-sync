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
        
        // Add subtle animation and gradient
        const gradientFill = ctx.createLinearGradient(0, 0, 0, 350);
        gradientFill.addColorStop(0, 'rgba(126, 59, 208, 0.3)');
        gradientFill.addColorStop(1, 'rgba(126, 59, 208, 0.02)');
        
        const profitGradient = ctx.createLinearGradient(0, 0, 0, 350);
        profitGradient.addColorStop(0, 'rgba(40, 167, 69, 0.3)');
        profitGradient.addColorStop(1, 'rgba(40, 167, 69, 0.02)');
        
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    borderColor: '#96588a',
                    backgroundColor: gradientFill,
                    borderWidth: 2,
                    pointBackgroundColor: '#96588a',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4, // Smooth curves
                    data: []
                }, {
                    label: 'Profit',
                    borderColor: '#28a745',
                    backgroundColor: profitGradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'Inter',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        bodyFont: {
                            family: 'Inter'
                        },
                        titleFont: {
                            family: 'Inter',
                            weight: 'bold'
                        },
                        borderColor: 'rgba(0, 0, 0, 0.05)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        boxShadow: '0 4px 15px rgba(0, 0, 0, 0.1)',
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + WPWPS.formatCurrency(context.raw, wpwps_data.currency_symbol);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter'
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter'
                            },
                            callback: function(value) {
                                return WPWPS.formatCurrency(value, wpwps_data.currency_symbol);
                            }
                        }
                    }
                }
            }
        });
    }

    // Load chart data with animation
    function loadChartData(period = 'week') {
        // Add loading animation
        const chartContainer = $('.chart-container');
        chartContainer.append('<div class="chart-loading"><i class="fas fa-circle-notch fa-spin fa-2x mb-2"></i><p>Loading data...</p></div>');
        
        $.ajax({
            url: wpwps_data.ajax_url,
            data: {
                action: 'wpwps_get_revenue_data',
                period: period,
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                $('.chart-loading').fadeOut(300, function() {
                    $(this).remove();
                });
                
                if (response.success) {
                    updateChart(response.data);
                } else {
                    chartContainer.append('<div class="chart-error"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Failed to load chart data</p></div>');
                    WPWPS.showToast('Failed to load chart data', 'error');
                }
            },
            error: function() {
                $('.chart-loading').fadeOut(300, function() {
                    $(this).remove();
                });
                chartContainer.append('<div class="chart-error"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Failed to load chart data</p></div>');
                WPWPS.showToast('Failed to load chart data', 'error');
            }
        });
    }

    // Update chart with smooth animation
    function updateChart(data) {
        revenueChart.data.labels = data.labels;
        revenueChart.data.datasets[0].data = data.revenue;
        revenueChart.data.datasets[1].data = data.profit;
        revenueChart.update('active');
    }

    // Period selector
    $('.chart-filters button').on('click', function() {
        const period = $(this).data('filter');
        $('.chart-filters button').removeClass('active');
        $(this).addClass('active');
        $('.chart-error').remove();
        loadChartData(period);
    });

    // Animation for cards on hover
    $('.wpwps-card').hover(
        function() {
            $(this).addClass('card-hover');
        },
        function() {
            $(this).removeClass('card-hover');
        }
    );

    // Sync products button animation
    $('.sync-products').on('click', function() {
        const button = $(this);
        button.html('<i class="fas fa-sync-alt fa-spin"></i>');
        
        // Simulate syncing (replace with actual AJAX call)
        setTimeout(function() {
            button.html('<i class="fas fa-sync-alt"></i>');
            WPWPS.showToast('Products sync initiated', 'success');
        }, 1500);
    });

    // Test API connection with enhanced UI feedback
    $('#test-api-connection').on('click', function() {
        const button = $(this);
        
        // Disable button and show spinner
        button.prop('disabled', true);
        button.html('<i class="fas fa-circle-notch fa-spin"></i> Testing...');
        
        // Add pulse effect to status indicator
        $('.status-unknown').addClass('wpwps-pulse');
        
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
                    WPWPS.showToast('API connection successful', 'success');
                } else {
                    $('.status-unknown').replaceWith('<span class="status-error"><i class="fas fa-times-circle"></i></span>');
                    WPWPS.showToast('API connection failed', 'error');
                }
            },
            error: function() {
                $('.status-unknown').replaceWith('<span class="status-error"><i class="fas fa-times-circle"></i></span>');
                WPWPS.showToast('API connection failed', 'error');
            },
            complete: function() {
                button.prop('disabled', false);
                button.html('<i class="fas fa-plug"></i> Test Connection');
                $('.wpwps-pulse').removeClass('wpwps-pulse');
            }
        });
    });

    // Initialize everything
    initCharts();
    loadChartData();
    
    // Add toast container styles
    $('head').append(`
        <style>
            .wpwps-toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .wpwps-toast {
                background-color: white;
                color: white;
                border-radius: 10px;
                padding: 12px 15px;
                min-width: 300px;
                max-width: 400px;
                display: flex;
                align-items: center;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                font-family: 'Inter', sans-serif;
            }
            
            .toast-icon {
                margin-right: 12px;
                font-size: 1.25rem;
            }
            
            .toast-content {
                flex: 1;
                font-size: 0.9rem;
            }
            
            .toast-close {
                background: none;
                border: none;
                color: white;
                opacity: 0.7;
                cursor: pointer;
                transition: opacity 0.2s ease;
            }
            
            .toast-close:hover {
                opacity: 1;
            }
            
            .pulse-animation {
                animation: pulse 1.5s infinite;
            }
            
            @keyframes pulse {
                0% {
                    transform: scale(1);
                    opacity: 1;
                }
                50% {
                    transform: scale(1.05);
                    opacity: 0.8;
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            
            .card-hover {
                transform: translateY(-5px);
            }
        </style>
    `);
});
