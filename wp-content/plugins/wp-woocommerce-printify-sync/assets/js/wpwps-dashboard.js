/**
 * Dashboard JavaScript
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Initialize charts if the page has our elements
    if (document.getElementById('sync-activity-chart')) {
        initCharts();
    }
    
    // Initialize dummy data for demonstration
    updateDashboardStats();
    
    // Button click handlers
    $('#refresh-activity').on('click', function() {
        updateDashboardStats();
        
        const btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> ' + wpwps_i18n.refreshing);
        
        setTimeout(function() {
            btn.prop('disabled', false);
            btn.html('<i class="fas fa-sync-alt me-1"></i> ' + wpwps_i18n.refresh);
            
            // Show success alert
            showAlert('success', '<i class="fas fa-check-circle me-2"></i>' + wpwps_i18n.data_refreshed);
        }, 1500);
    });
    
    $('#sync-products').on('click', function() {
        // Show confirmation modal
        const syncConfirmModal = new bootstrap.Modal(document.getElementById('syncConfirmModal'));
        syncConfirmModal.show();
    });
    
    $('#confirm-sync').on('click', function() {
        // Hide confirmation modal
        const syncConfirmModal = bootstrap.Modal.getInstance(document.getElementById('syncConfirmModal'));
        syncConfirmModal.hide();
        
        // Show progress modal
        const syncProgressModal = new bootstrap.Modal(document.getElementById('syncProgressModal'));
        syncProgressModal.show();
        
        // Simulate sync progress
        simulateSyncProgress();
    });
    
    // Time filter for sales & profit chart
    $('.time-filter button').on('click', function() {
        $('.time-filter button').removeClass('active');
        $(this).addClass('active');
        
        const period = $(this).data('period');
        updateSalesChart(period);
    });
    
    // Health check handlers
    $('#check-api-health').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> ' + wpwps_i18n.checking);
        
        setTimeout(function() {
            btn.prop('disabled', false);
            btn.html('<i class="fas fa-sync-alt me-1"></i> ' + wpwps_i18n.check_api);
            
            // Simulate API health check
            $('#api-health-status').text(wpwps_i18n.api_ok);
            $('#api-last-check').text(wpwps_i18n.last_checked_now);
            
            showAlert('success', '<i class="fas fa-check-circle me-2"></i>' + wpwps_i18n.api_check_completed);
        }, 1200);
    });
    
    $('#configure-webhooks').on('click', function() {
        showAlert('info', '<i class="fas fa-info-circle me-2"></i>' + wpwps_i18n.webhook_feature_coming);
    });
    
    $('#sync-orders').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> ' + wpwps_i18n.syncing);
        
        setTimeout(function() {
            btn.prop('disabled', false);
            btn.html('<i class="fas fa-sync-alt me-1"></i> ' + wpwps_i18n.sync_orders);
            
            // Simulate order sync
            $('#order-sync-status').text(wpwps_i18n.orders_synced);
            $('#order-last-sync').text(wpwps_i18n.last_checked_now);
            
            showAlert('success', '<i class="fas fa-check-circle me-2"></i>' + wpwps_i18n.orders_sync_completed);
        }, 1800);
    });
    
    /**
     * Initialize charts
     */
    function initCharts() {
        // Set Chart.js defaults
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        Chart.defaults.font.size = 13;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        Chart.defaults.plugins.tooltip.titleColor = '#fff';
        Chart.defaults.plugins.tooltip.bodyColor = '#fff';
        Chart.defaults.plugins.tooltip.borderWidth = 0;
        Chart.defaults.plugins.tooltip.displayColors = true;
        Chart.defaults.plugins.tooltip.boxWidth = 10;
        Chart.defaults.plugins.tooltip.boxHeight = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 4;
        
        // Sync Activity Chart
        const activityCtx = document.getElementById('sync-activity-chart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
                datasets: [{
                    label: wpwps_i18n.products_synced,
                    data: [3, 7, 12, 15, 18, 22, 30],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Sync Status Chart
        const statusCtx = document.getElementById('sync-status-chart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    wpwps_i18n.synced,
                    wpwps_i18n.pending,
                    wpwps_i18n.failed
                ],
                datasets: [{
                    data: [30, 5, 2],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Sales & Profit Chart
        const salesCtx = document.getElementById('sales-profit-chart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: wpwps_i18n.sales,
                        data: [1200, 1900, 2300, 2800, 3200, 3800],
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: wpwps_i18n.profit,
                        data: [500, 800, 1100, 1400, 1800, 2100],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
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
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.raw;
                            }
                        }
                    }
                }
            }
        });
        
        // Order Status Chart
        const orderStatusCtx = document.getElementById('order-status-chart').getContext('2d');
        const orderStatusChart = new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    wpwps_i18n.processing,
                    wpwps_i18n.completed,
                    wpwps_i18n.cancelled,
                    wpwps_i18n.shipped
                ],
                datasets: [{
                    data: [8, 25, 2, 15],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',  // Processing - yellow
                        'rgba(40, 167, 69, 0.8)',  // Completed - green
                        'rgba(220, 53, 69, 0.8)',  // Cancelled - red
                        'rgba(23, 162, 184, 0.8)'  // Shipped - blue
                    ],
                    borderColor: [
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Store the charts in global variables for later access
        window.wpwpsCharts = {
            activityChart: activityChart,
            statusChart: statusChart,
            salesChart: salesChart,
            orderStatusChart: orderStatusChart
        };
    }
    
    /**
     * Update sales chart based on time period
     * 
     * @param {string} period The time period (day, week, month, year)
     */
    function updateSalesChart(period) {
        let labels, salesData, profitData;
        
        switch(period) {
            case 'day':
                labels = ['12am', '4am', '8am', '12pm', '4pm', '8pm'];
                salesData = [50, 30, 80, 120, 160, 110];
                profitData = [20, 10, 40, 70, 90, 60];
                break;
                
            case 'week':
                labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                salesData = [700, 600, 800, 950, 1200, 1500, 800];
                profitData = [300, 250, 350, 400, 600, 800, 400];
                break;
                
            case 'month':
                labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                salesData = [3800, 4200, 5100, 4800];
                profitData = [1800, 2200, 2700, 2400];
                break;
                
            case 'year':
                labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                salesData = [4200, 3800, 5100, 4900, 6200, 5800, 6500, 7200, 6800, 7500, 8200, 9500];
                profitData = [2100, 1900, 2600, 2400, 3200, 2900, 3300, 3800, 3500, 3900, 4200, 5100];
                break;
                
            default:
                labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                salesData = [1200, 1900, 2300, 2800, 3200, 3800];
                profitData = [500, 800, 1100, 1400, 1800, 2100];
        }
        
        // Update the chart data
        const chart = window.wpwpsCharts.salesChart;
        chart.data.labels = labels;
        chart.data.datasets[0].data = salesData;
        chart.data.datasets[1].data = profitData;
        chart.update();
    }
    
    /**
     * Update dashboard statistics with dummy data
     */
    function updateDashboardStats() {
        // Update counters with animation
        animateCounter('total-products', 37);
        animateCounter('synced-products', 30);
        animateCounter('pending-products', 5);
        animateCounter('failed-products', 2);
        
        // Populate activity table with dummy data
        const activityData = [
            {
                product: 'Classic T-Shirt',
                type: 'Apparel',
                status: 'success',
                last_updated: '2023-06-15 14:30:22',
                action: 'view'
            },
            {
                product: 'Premium Hoodie',
                type: 'Apparel',
                status: 'success',
                last_updated: '2023-06-15 14:25:18',
                action: 'view'
            },
            {
                product: 'Coffee Mug',
                type: 'Drinkware',
                status: 'pending',
                last_updated: '2023-06-15 14:20:45',
                action: 'retry'
            },
            {
                product: 'Phone Case',
                type: 'Accessories',
                status: 'failed',
                last_updated: '2023-06-15 14:15:30',
                action: 'retry'
            },
            {
                product: 'Canvas Print',
                type: 'Home Decor',
                status: 'success',
                last_updated: '2023-06-15 14:10:12',
                action: 'view'
            }
        ];
        
        updateActivityTable(activityData);
    }
    
    /**
     * Animate counter from 0 to target value
     * 
     * @param {string} elementId The element ID
     * @param {number} targetValue The target value
     */
    function animateCounter(elementId, targetValue) {
        const element = document.getElementById(elementId);
        const duration = 1000; // ms
        const frameDuration = 1000 / 60; // 60fps
        const totalFrames = Math.round(duration / frameDuration);
        let frame = 0;
        
        const counter = setInterval(() => {
            frame++;
            const progress = frame / totalFrames;
            const currentValue = Math.round(targetValue * progress);
            
            element.textContent = currentValue;
            
            if (frame === totalFrames) {
                clearInterval(counter);
            }
        }, frameDuration);
    }
    
    /**
     * Update activity table with data
     * 
     * @param {Array} activityData The activity data
     */
    function updateActivityTable(activityData) {
        const tableBody = document.getElementById('activity-table-body');
        
        // Clear table
        tableBody.innerHTML = '';
        
        // Add rows
        activityData.forEach(item => {
            const row = document.createElement('tr');
            
            // Status badge
            let statusBadge = '';
            if (item.status === 'success') {
                statusBadge = '<span class="badge bg-success">Success</span>';
            } else if (item.status === 'pending') {
                statusBadge = '<span class="badge bg-info">Pending</span>';
            } else if (item.status === 'failed') {
                statusBadge = '<span class="badge bg-warning">Failed</span>';
            }
            
            // Action button
            let actionButton = '';
            if (item.action === 'view') {
                actionButton = '<button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button>';
            } else if (item.action === 'retry') {
                actionButton = '<button class="btn btn-sm btn-outline-warning"><i class="fas fa-redo"></i> Retry</button>';
            }
            
            row.innerHTML = `
                <td>${item.product}</td>
                <td>${item.type}</td>
                <td>${statusBadge}</td>
                <td>${item.last_updated}</td>
                <td>${actionButton}</td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    /**
     * Simulate sync progress
     */
    function simulateSyncProgress() {
        const progressBar = document.getElementById('sync-progress-bar');
        const statusMessage = document.getElementById('sync-status-message');
        let progress = 0;
        
        const interval = setInterval(() => {
            progress += 10;
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            
            // Update status message
            if (progress === 10) {
                statusMessage.textContent = wpwps_i18n.fetching_products;
            } else if (progress === 30) {
                statusMessage.textContent = wpwps_i18n.processing_products;
            } else if (progress === 60) {
                statusMessage.textContent = wpwps_i18n.updating_products;
            } else if (progress === 90) {
                statusMessage.textContent = wpwps_i18n.finalizing;
            }
            
            if (progress >= 100) {
                clearInterval(interval);
                
                // Hide progress modal
                setTimeout(() => {
                    const syncProgressModal = bootstrap.Modal.getInstance(document.getElementById('syncProgressModal'));
                    syncProgressModal.hide();
                    
                    // Show success alert
                    showAlert('success', '<i class="fas fa-check-circle me-2"></i>' + wpwps_i18n.sync_complete);
                    
                    // Update dashboard stats
                    updateDashboardStats();
                }, 500);
            }
        }, 500);
    }
    
    /**
     * Show a Bootstrap alert
     * 
     * @param {string} type The alert type (success, info, warning, danger)
     * @param {string} message The message to show
     */
    function showAlert(type, message) {
        // Remove any existing alerts
        $('.wpwps-alert').remove();
        
        // Create alert
        const alert = `
            <div class="wpwps-alert alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Add alert before the first row
        $('.row').first().before(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.wpwps-alert').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
});

// Expanded translations object
const wpwps_i18n = {
    products_synced: 'Products Synced',
    synced: 'Synced',
    pending: 'Pending',
    failed: 'Failed',
    processing: 'Processing',
    completed: 'Completed',
    cancelled: 'Cancelled',
    shipped: 'Shipped',
    sales: 'Sales',
    profit: 'Profit',
    refreshing: 'Refreshing...',
    refresh: 'Refresh',
    checking: 'Checking...',
    check_api: 'Check API Connection',
    syncing: 'Syncing...',
    sync_orders: 'Sync Orders Now',
    api_ok: 'API Connection OK',
    last_checked_now: 'Last checked: Just now',
    orders_synced: 'Orders in Sync',
    data_refreshed: 'Dashboard data has been refreshed!',
    api_check_completed: 'API health check completed successfully!',
    orders_sync_completed: 'Orders synchronization completed!',
    webhook_feature_coming: 'Webhook configuration will be available in the next update!',
    sync_complete: 'Products have been successfully synchronized!',
    fetching_products: 'Fetching products from Printify...',
    processing_products: 'Processing product data...',
    updating_products: 'Updating WooCommerce products...',
    finalizing: 'Finalizing synchronization...'
};
