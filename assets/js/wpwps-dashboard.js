/**
 * WP WooCommerce Printify Sync - Dashboard JavaScript
 */
(function($) {
    'use strict';

    // Charts configuration
    let salesChart = null;
    let productStatusChart = null;

    // DOM ready
    $(function() {
        // Initialize components
        initToasts();
        initCharts();
        loadDashboardData();
        setupEventListeners();

        // Show welcome toast
        showToast('Welcome to Printify Sync', 'Your dashboard is ready.', 'success');
    });

    /**
     * Initialize toast notification system
     */
    function initToasts() {
        // Set default toast options
        window.toastOptions = {
            animation: true,
            autohide: true,
            delay: 5000
        };
    }

    /**
     * Initialize charts
     */
    function initCharts() {
        // Sales chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        
        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Sales',
                        data: [],
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderColor: '#0d6efd',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Orders',
                        data: [],
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        borderColor: '#198754',
                        borderWidth: 2,
                        tension: 0.3,
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
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });

        // Product status chart
        const productStatusCtx = document.getElementById('productStatusChart').getContext('2d');
        
        productStatusChart = new Chart(productStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Synced', 'Pending', 'Error'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#0d6efd', '#ffc107', '#dc3545'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '65%'
            }
        });
    }

    /**
     * Load dashboard data via AJAX
     */
    function loadDashboardData() {
        // Simulate loading with short timeout
        setTimeout(function() {
            // Update statistics
            updateStatistics({
                products: 132,
                orders: 57,
                tickets: 8,
                syncStatus: 'synced',
                lastSync: '2023-04-15 14:32:45'
            });
            
            // Update charts
            updateSalesChart('month');
            updateProductStatusChart({
                synced: 98,
                pending: 22,
                error: 12
            });
            
            // Update recent orders list
            updateRecentOrders([
                { id: 1053, date: '2023-04-15', status: 'completed', total: '$59.99' },
                { id: 1052, date: '2023-04-14', status: 'processing', total: '$124.50' },
                { id: 1051, date: '2023-04-14', status: 'processing', total: '$42.25' },
                { id: 1050, date: '2023-04-13', status: 'completed', total: '$85.75' }
            ]);
            
            // Update activity list
            updateActivityList([
                { title: 'Product synced', description: 'T-shirt SKU#1234 was synced', time: '15 mins ago' },
                { title: 'New order received', description: 'Order #1053 was received and sent to Printify', time: '2 hours ago' },
                { title: 'Shipping updated', description: 'Shipping rates refreshed from provider', time: '5 hours ago' },
                { title: 'API connection', description: 'Successfully connected to Printify API', time: '1 day ago' }
            ]);
            
            // Update system status
            updateSystemStatus({
                apiStatus: 'connected',
                chatgptStatus: 'connected',
                nextSync: 'In 5 hours 23 minutes',
                emailQueue: '2 pending',
                dbStatus: 'healthy'
            });

        }, 1000);
    }

    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Chart period selection
        $('.chart-period').on('click', function(e) {
            e.preventDefault();
            const period = $(this).data('period');
            
            // Update active state
            $('.chart-period').removeClass('active');
            $(this).addClass('active');
            
            // Update dropdown text
            let periodText = 'This Month';
            if (period === 'week') periodText = 'This Week';
            if (period === 'year') periodText = 'This Year';
            $('#chartPeriodDropdown').text(periodText);
            
            // Update chart data
            updateSalesChart(period);
            
            // Show toast notification
            showToast('Chart Updated', 'Sales chart period changed to ' + periodText, 'info');
        });
        
        // Refresh activity button
        $('#refresh-activity').on('click', function() {
            $(this).html('<i class="fas fa-spinner fa-spin"></i>');
            
            setTimeout(function() {
                updateActivityList([
                    { title: 'Data refreshed', description: 'Dashboard data refreshed', time: 'Just now' },
                    { title: 'Product synced', description: 'T-shirt SKU#1234 was synced', time: '15 mins ago' },
                    { title: 'New order received', description: 'Order #1053 was received and sent to Printify', time: '2 hours ago' },
                    { title: 'Shipping updated', description: 'Shipping rates refreshed from provider', time: '5 hours ago' }
                ]);
                
                $('#refresh-activity').html('<i class="fas fa-sync-alt"></i> Refresh');
                showToast('Refreshed', 'Activity list has been refreshed', 'success');
            }, 800);
        });
        
        // Quick action buttons
        $('#sync-products').on('click', function() {
            actionButtonClick($(this), 'Syncing...', 'Products Synced', 'Products have been synchronized with Printify');
        });
        
        $('#sync-orders').on('click', function() {
            actionButtonClick($(this), 'Syncing...', 'Orders Synced', 'Orders have been synchronized with Printify');
        });
        
        $('#check-shipping').on('click', function() {
            actionButtonClick($(this), 'Updating...', 'Shipping Updated', 'Shipping rates have been updated');
        });
        
        $('#check-connection').on('click', function() {
            actionButtonClick($(this), 'Checking...', 'Connection OK', 'Connection to Printify API is stable');
        });
    }

    /**
     * Handle action button clicks
     */
    function actionButtonClick(button, loadingText, successTitle, successMessage) {
        const originalHtml = button.html();
        const originalClass = button.find('i').attr('class');
        
        // Show loading state
        button.prop('disabled', true);
        button.find('i').attr('class', 'fas fa-spinner fa-spin');
        
        // Simulate processing
        setTimeout(function() {
            // Restore button
            button.prop('disabled', false);
            button.find('i').attr('class', originalClass);
            
            // Show success notification
            showToast(successTitle, successMessage, 'success');
        }, 1500);
    }

    /**
     * Update statistics cards
     */
    function updateStatistics(data) {
        // Update counters with animation
        animateCounter('#products-count', data.products);
        animateCounter('#orders-count', data.orders);
        animateCounter('#tickets-count', data.tickets);
        
        // Update progress bars
        $('#products-progress').css('width', '75%').attr('aria-valuenow', 75);
        $('#orders-progress').css('width', '45%').attr('aria-valuenow', 45);
        $('#tickets-progress').css('width', '20%').attr('aria-valuenow', 20);
        
        // Update sync status
        if (data.syncStatus === 'synced') {
            $('#sync-status-icon').html('<i class="fas fa-check-circle text-success"></i>');
            $('#sync-status-text').text('Synced').addClass('text-success');
        } else if (data.syncStatus === 'syncing') {
            $('#sync-status-icon').html('<i class="fas fa-sync fa-spin text-primary"></i>');
            $('#sync-status-text').text('Syncing...').addClass('text-primary');
        } else {
            $('#sync-status-icon').html('<i class="fas fa-exclamation-circle text-danger"></i>');
            $('#sync-status-text').text('Error').addClass('text-danger');
        }
        
        // Update last sync time
        $('#last-sync-time').text('Last sync: ' + data.lastSync);
    }

    /**
     * Update sales chart data
     */
    function updateSalesChart(period) {
        let labels = [];
        let salesData = [];
        let ordersData = [];
        
        // Generate sample data based on period
        if (period === 'week') {
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            salesData = [520, 450, 480, 790, 1200, 950, 850];
            ordersData = [12, 9, 10, 15, 22, 18, 16];
        } else if (period === 'month') {
            labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            salesData = [1850, 2300, 2100, 2500];
            ordersData = [35, 42, 38, 47];
        } else if (period === 'year') {
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            salesData = [5200, 5500, 6800, 7800, 8200, 7900, 8500, 9100, 9200, 8500, 9800, 10500];
            ordersData = [110, 115, 135, 145, 150, 140, 155, 165, 170, 160, 180, 190];
        }
        
        // Update chart data
        salesChart.data.labels = labels;
        salesChart.data.datasets[0].data = salesData;
        salesChart.data.datasets[1].data = ordersData;
        salesChart.update();
    }

    /**
     * Update product status chart
     */
    function updateProductStatusChart(data) {
        // Update chart data
        productStatusChart.data.datasets[0].data = [data.synced, data.pending, data.error];
        productStatusChart.update();
        
        // Update counters
        $('#synced-count').text(data.synced);
        $('#pending-count').text(data.pending);
        $('#error-count').text(data.error);
    }

    /**
     * Update recent orders list
     */
    function updateRecentOrders(orders) {
        if (orders.length === 0) {
            return;
        }
        
        let html = '';
        orders.forEach(function(order) {
            let statusClass = '';
            if (order.status === 'completed') statusClass = 'bg-success';
            if (order.status === 'processing') statusClass = 'bg-info';
            if (order.status === 'pending') statusClass = 'bg-warning';
            if (order.status === 'failed') statusClass = 'bg-danger';
            
            html += `
                <tr>
                    <td>
                        <a href="#" class="fw-500 text-decoration-none">#${order.id}</a>
                    </td>
                    <td>${order.date}</td>
                    <td><span class="badge ${statusClass}">${order.status}</span></td>
                    <td>${order.total}</td>
                </tr>
            `;
        });
        
        $('#recent-orders').html(html);
    }

    /**
     * Update activity list
     */
    function updateActivityList(activities) {
        if (activities.length === 0) {
            return;
        }
        
        let html = '';
        activities.forEach(function(activity) {
            html += `
                <div class="list-group-item list-group-item-action p-3">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${activity.title}</h6>
                        <small class="text-muted">${activity.time}</small>
                    </div>
                    <p class="mb-1 text-muted">${activity.description}</p>
                </div>
            `;
        });
        
        $('#activity-list').html(html);
    }

    /**
     * Update system status
     */
    function updateSystemStatus(data) {
        // Update API status
        if (data.apiStatus === 'connected') {
            $('#api-status').removeClass('bg-danger').addClass('bg-success').text('Connected');
        } else {
            $('#api-status').removeClass('bg-success').addClass('bg-danger').text('Disconnected');
        }
        
        // Update ChatGPT status
        if (data.chatgptStatus === 'connected') {
            $('#chatgpt-status').removeClass('bg-danger').addClass('bg-success').text('Connected');
        } else {
            $('#chatgpt-status').removeClass('bg-success').addClass('bg-danger').text('Disconnected');
        }
        
        // Update other status information
        $('#next-sync').text(data.nextSync);
        $('#email-queue').text(data.emailQueue);
        
        // Update database status
        if (data.dbStatus === 'healthy') {
            $('#db-status').removeClass('bg-warning bg-danger').addClass('bg-success').text('Healthy');
        } else if (data.dbStatus === 'warning') {
            $('#db-status').removeClass('bg-success bg-danger').addClass('bg-warning').text('Warning');
        } else {
            $('#db-status').removeClass('bg-success bg-warning').addClass('bg-danger').text('Error');
        }
    }

    /**
     * Display a toast notification
     * 
     * @param {string} title    The title of the toast
     * @param {string} message  The message to display
     * @param {string} type     The type of toast (success, danger, warning, info)
     */
    function showToast(title, message, type = 'info') {
        // Generate a unique ID for the toast
        const toastId = 'toast-' + Date.now();
        
        // Create toast HTML
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <span class="bg-${type} rounded me-2" style="width: 15px; height: 15px;"></span>
                    <strong class="me-auto">${title}</strong>
                    <small class="text-muted">${getCurrentTime()}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `;
        
        // Add toast to container
        $('.toast-container').append(toastHtml);
        
        // Initialize and show the toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, window.toastOptions);
        
        // Show the toast
        toast.show();
        
        // Remove the toast element when hidden
        $(toastElement).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    /**
     * Get the current time in HH:MM:SS format
     */
    function getCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        return `${hours}:${minutes}:${seconds}`;
    }

    /**
     * Animate a counter from 0 to a target number
     */
    function animateCounter(selector, target) {
        const $element = $(selector);
        const start = parseInt($element.text()) || 0;
        const duration = 1000;
        const increment = target > start;
        
        const range = increment ? target - start : start - target;
        const stepTime = Math.abs(Math.floor(duration / range));
        
        let current = start;
        const step = function() {
            current = increment ? current + 1 : current - 1;
            $element.text(current);
            
            if ((increment && current < target) || (!increment && current > target)) {
                setTimeout(step, stepTime);
            }
        };
        
        step();
    }

})(jQuery);
