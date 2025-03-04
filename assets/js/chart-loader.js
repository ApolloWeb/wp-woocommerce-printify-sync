/**
 * Chart.js loader for Printify Sync Dashboard
 * Initializes charts and populates dashboard widgets with dummy data
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

(function($) {
    'use strict';

    // Demo data for dashboard widgets
    const demoData = {
        stats: {
            active_shops: 3,
            synced_products: 245,
            recent_orders: 18,
            last_sync: '2 hrs ago',
            shop_growth: '+6%',
            product_growth: '+12%',
            order_growth: '+8%'
        },
        sales: {
            day: {
                labels: ['12am', '3am', '6am', '9am', '12pm', '3pm', '6pm', '9pm'],
                sales: [25, 10, 5, 40, 120, 80, 95, 60],
                orders: [3, 1, 1, 5, 12, 8, 10, 6]
            },
            week: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                sales: [520, 680, 495, 750, 620, 780, 540],
                orders: [15, 18, 14, 20, 17, 22, 16]
            },
            month: {
                labels: ['1', '5', '10', '15', '20', '25', '30'],
                sales: [1520, 1380, 1790, 1450, 1890, 1740, 1550],
                orders: [45, 40, 52, 42, 55, 50, 46]
            },
            year: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                sales: [9500, 8200, 9800, 7600, 10400, 11200, 9800, 10600, 11500, 12800, 13700, 12500],
                orders: [210, 190, 215, 185, 225, 240, 220, 230, 245, 260, 280, 250]
            }
        },
        products: [
            { name: 'Customized T-Shirt', status: 'synced', last_sync: '2025-03-04 12:30:25' },
            { name: 'Logo Hoodie', status: 'synced', last_sync: '2025-03-04 11:15:10' },
            { name: 'Coffee Mug', status: 'failed', last_sync: '2025-03-04 10:05:42' },
            { name: 'Phone Case', status: 'synced', last_sync: '2025-03-04 09:45:15' },
            { name: 'Canvas Print', status: 'syncing', last_sync: '2025-03-04 08:30:00' }
        ],
        orders: [
            { id: '#12345', customer: 'John Smith', total: '$78.50', status: 'processing' },
            { id: '#12344', customer: 'Amy Johnson', total: '$124.95', status: 'completed' },
            { id: '#12343', customer: 'Mark Williams', total: '$45.00', status: 'processing' },
            { id: '#12342', customer: 'Susan Brown', total: '$89.99', status: 'failed' },
            { id: '#12341', customer: 'David Miller', total: '$134.50', status: 'completed' }
        ],
        sync_status: {
            progress: 75,
            last_full_sync: '2025-03-03 22:45:10',
            next_scheduled_sync: '2025-03-04 23:00:00',
            pending_sync: 24
        },
        top_selling: [
            { name: 'Vintage T-Shirt', sales: 125, revenue: '$3,750' },
            { name: 'Custom Mug', sales: 93, revenue: '$1,395' },
            { name: 'Tote Bag', sales: 87, revenue: '$1,305' },
            { name: 'Phone Case', sales: 76, revenue: '$1,140' }
        ],
        notifications: [
            { type: 'success', message: 'Bulk product sync completed successfully', time: '2 hours ago' },
            { type: 'warning', message: 'API rate limit approaching (80%)', time: '3 hours ago' },
            { type: 'error', message: 'Failed to sync product #1242', time: '5 hours ago' },
            { type: 'info', message: 'New Printify API version available', time: '1 day ago' }
        ]
    };

    // Chart instances
    let salesChart = null;
    let currentPeriod = 'week'; // Default period

    /**
     * Initialize the dashboard
     */
    function initDashboard() {
        updateStatBoxes();
        initSalesChart(currentPeriod);
        setupFilterButtons();
        populateRecentProducts();
        populateRecentOrders();
        updateSyncStatus();
        populateTopSelling();
        populateNotifications();
    }

    /**
     * Update stat boxes with data
     */
    function updateStatBoxes() {
        const stats = demoData.stats;
        $('#stat-active-shops').text(stats.active_shops);
        $('#stat-synced-products').text(stats.synced_products);
        $('#stat-recent-orders').text(stats.recent_orders);
        $('#stat-last-sync').text(stats.last_sync);
        
        $('.stat-box:nth-child(1) .change').html('<i class="fas fa-arrow-up"></i> ' + stats.shop_growth);
        $('.stat-box:nth-child(2) .change').html('<i class="fas fa-arrow-up"></i> ' + stats.product_growth);
        $('.stat-box:nth-child(3) .change').html('<i class="fas fa-arrow-up"></i> ' + stats.order_growth);
    }

    /**
     * Initialize the sales chart
     * @param {string} period - The time period to display
     */
    function initSalesChart(period) {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        const data = demoData.sales[period];
        
        // Destroy existing chart if it exists
        if (salesChart) {
            salesChart.destroy();
        }
        
        // Create new chart
        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Sales ($)',
                        data: data.sales,
                        borderColor: '#7f54b3', // WooCommerce purple
                        backgroundColor: 'rgba(127, 84, 179, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Orders',
                        data: data.orders,
                        borderColor: '#46b450', // WooCommerce green
                        backgroundColor: 'rgba(70, 180, 80, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Setup filter buttons for chart periods
     */
    function setupFilterButtons() {
        $('.filter-btn').on('click', function() {
            const period = $(this).data('period');
            
            // Update active button state
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update the chart
            currentPeriod = period;
            initSalesChart(period);
        });
        
        // Set default active button
        $('.filter-btn[data-period="week"]').addClass('active');
    }

    /**
     * Populate recent products table
     */
    function populateRecentProducts() {
        const $table = $('.recent-products-table tbody');
        if (!$table.length) return;
        
        $table.empty();
        
        demoData.products.forEach(product => {
            let statusClass = 'success';
            if (product.status === 'failed') {
                statusClass = 'error';
            } else if (product.status === 'syncing') {
                statusClass = 'warning';
            }
            
            $table.append(`
                <tr>
                    <td>${product.name}</td>
                    <td><span class="status-badge ${statusClass}">${product.status}</span></td>
                    <td>${product.last_sync}</td>
                </tr>
            `);
        });
    }

    /**
     * Populate recent orders table
     */
    function populateRecentOrders() {
        const $table = $('.recent-orders-table tbody');
        if (!$table.length) return;
        
        $table.empty();
        
        demoData.orders.forEach(order => {
            let statusClass = 'success';
            if (order.status === 'failed') {
                statusClass = 'error';
            } else if (order.status === 'processing') {
                statusClass = 'warning';
            }
            
            $table.append(`
                <tr>
                    <td>${order.id}</td>
                    <td>${order.customer}</td>
                    <td>${order.total}</td>
                    <td><span class="status-badge ${statusClass}">${order.status}</span></td>
                </tr>
            `);
        });
    }

    /**
     * Update sync status widget
     */
    function updateSyncStatus() {
        const status = demoData.sync_status;
        $('.sync-progress-bar').css('width', status.progress + '%');
        $('.sync-progress-bar span').text(status.progress + '% Complete');
        
        $('.sync-details .last-full-sync').text(status.last_full_sync);
        $('.sync-details .next-scheduled-sync').text(status.next_scheduled_sync);
        $('.sync-details .pending-sync').text(status.pending_sync);
    }

    /**
     * Populate top selling products
     */
    function populateTopSelling() {
        const $list = $('.top-selling-list');
        if (!$list.length) return;
        
        $list.empty();
        
        demoData.top_selling.forEach((product, index) => {
            $list.append(`
                <div class="top-selling-item">
                    <span class="rank">#${index + 1}</span>
                    <span class="product-name">${product.name}</span>
                    <div class="product-stats">
                        <span class="sales">${product.sales} units</span>
                        <span class="revenue">${product.revenue}</span>
                    </div>
                </div>
            `);
        });
    }

    /**
     * Populate notifications
     */
    function populateNotifications() {
        const $list = $('.notification-list');
        if (!$list.length) return;
        
        $list.empty();
        
        demoData.notifications.forEach(notification => {
            $list.append(`
                <div class="notification-item ${notification.type}">
                    <div class="notification-icon">
                        <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <p>${notification.message}</p>
                        <span class="notification-time">${notification.time}</span>
                    </div>
                </div>
            `);
        });
    }

    /**
     * Get appropriate icon for notification type
     */
    function getNotificationIcon(type) {
        switch(type) {
            case 'success': return 'check-circle';
            case 'warning': return 'exclamation-triangle';
            case 'error': return 'times-circle';
            case 'info': return 'info-circle';
            default: return 'bell';
        }
    }

    /**
     * Check if Chart.js is available and display appropriate messages
     */
    function checkDependencies() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded. Please check your configuration.');
            $('.chart-container').html(
                '<div class="error-message">' +
                '<p><strong>Chart.js library not found.</strong></p>' +
                '<p>Please check that Chart.js is properly loaded.</p>' +
                '</div>'
            );
            return false;
        }
        
        return true;
    }

    // Initialize on document ready
    $(document).ready(function() {
        if (checkDependencies()) {
            initDashboard();
        }
    });

})(jQuery);