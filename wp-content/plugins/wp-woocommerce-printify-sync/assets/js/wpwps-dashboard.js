document.addEventListener('DOMContentLoaded', function() {
    // Initialize Chart.js with defaults
    Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.color = '#666';
    Chart.defaults.elements.line.tension = 0.4; // Smooth lines
    Chart.defaults.elements.point.radius = 2;
    Chart.defaults.elements.point.hoverRadius = 6;

    // Initialize toast notifications
    WPWPSToast.init();

    let salesChart;
    const updateInterval = 30000; // 30 seconds
    let activityLog = [];

    // Enhanced widget animations with staggered entry
    function animateWidgets() {
        const widgets = document.querySelectorAll('.card');
        widgets.forEach((widget, index) => {
            widget.style.opacity = '0';
            widget.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                widget.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                widget.style.opacity = '1';
                widget.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Smooth stat updates with number animation
    function updateStatWithAnimation(element, newValue) {
        if (!element) return;
        
        const startValue = parseInt(element.textContent) || 0;
        const endValue = parseInt(newValue);
        const duration = 1000;
        const stepTime = 20;
        const steps = duration / stepTime;
        const valueIncrement = (endValue - startValue) / steps;
        let currentStep = 0;

        const updateValue = () => {
            currentStep++;
            const currentValue = Math.round(startValue + (valueIncrement * currentStep));
            element.textContent = currentValue;

            if (currentStep < steps) {
                requestAnimationFrame(updateValue);
            } else {
                element.textContent = endValue;
            }
        };

        updateValue();
    }

    // Initialize sales chart with smooth animations
    function initSalesChart(data) {
        const ctx = document.getElementById('sales-chart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(150, 88, 138, 0.2)');
        gradient.addColorStop(1, 'rgba(150, 88, 138, 0)');

        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: [
                    {
                        label: wpwps_dashboard.i18n?.revenue || 'Revenue',
                        data: data.revenue,
                        borderColor: '#96588a',
                        backgroundColor: gradient,
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: wpwps_dashboard.i18n?.orders || 'Orders',
                        data: data.orders,
                        borderColor: '#4B49AC',
                        backgroundColor: '#4B49AC20',
                        fill: true,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: value => '$' + value.toLocaleString()
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            boxWidth: 6
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.98)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: 'rgba(0, 0, 0, 0.05)',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: context => {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y;
                                if (label === 'Revenue') {
                                    return `${label}: $${value.toLocaleString()}`;
                                }
                                return `${label}: ${value.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Update dashboard data with loading states
    function updateDashboard() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => card.classList.add('updating'));

        $.ajax({
            url: wpwps_dashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_dashboard_data',
                nonce: wpwps_dashboard.nonce
            },
            success: function(response) {
                if (!response.success) {
                    WPWPSToast.error('Error', 'Failed to update dashboard');
                    return;
                }

                updateWidgets(response.data);
                updateSalesChart(response.data.sales_data);
                logActivity(response.data);

                cards.forEach(card => {
                    setTimeout(() => card.classList.remove('updating'), 500);
                });
            },
            error: function(xhr, status, error) {
                WPWPSToast.error('Error', 'Failed to update dashboard: ' + error);
                cards.forEach(card => card.classList.remove('updating'));
            }
        });
    }

    // Update widget values
    function updateWidgets(data) {
        // Email Queue
        updateStatWithAnimation(document.getElementById('email-queued'), data.email_queue.queued);
        updateStatWithAnimation(document.getElementById('email-sent'), data.email_queue.sent_24h);
        updateStatWithAnimation(document.getElementById('email-failed'), data.email_queue.failed_24h);

        // Import Queue
        updateStatWithAnimation(document.getElementById('import-pending'), data.import_queue.pending);
        updateStatWithAnimation(document.getElementById('import-running'), data.import_queue.running);
        updateStatWithAnimation(document.getElementById('import-completed'), data.import_queue.completed_24h);

        // Sync Status
        const $syncStatus = $('#sync-status');
        $syncStatus.text(data.sync_status.status.charAt(0).toUpperCase() + data.sync_status.status.slice(1));
        $syncStatus.attr('data-status', data.sync_status.status);
        updateSyncStatusBadge($syncStatus);

        $('#sync-last').text(data.sync_status.last_sync);

        if (data.sync_status.status === 'running') {
            $('#sync-progress').css('width', data.sync_status.progress + '%');
        }

        // API Health
        updateHealthIndicator('#api-health-printify', data.api_health.printify.healthy);
        updateHealthIndicator('#api-health-webhook', data.api_health.webhook.healthy);

        if (data.api_health.printify.rate_limit) {
            $('#api-rate-limit').text(data.api_health.printify.rate_limit);
        }

        // Store errors for modal display
        if (!data.api_health.printify.healthy) {
            $('#api-health-printify').attr('data-error', data.api_health.printify.error);
        }
    }

    // Update sync status badge colors
    function updateSyncStatusBadge($badge) {
        $badge.removeClass('bg-success bg-warning bg-info bg-danger');
        
        switch ($badge.attr('data-status')) {
            case 'running':
                $badge.addClass('bg-info');
                break;
            case 'completed':
                $badge.addClass('bg-success');
                break;
            case 'failed':
                $badge.addClass('bg-danger');
                break;
            case 'queued':
                $badge.addClass('bg-warning');
                break;
            default:
                $badge.addClass('bg-secondary');
        }
    }

    // Update health indicator icons
    function updateHealthIndicator(selector, isHealthy) {
        const $indicator = $(selector);
        $indicator.html(isHealthy ? 
            '<i class="fas fa-check-circle text-success"></i>' : 
            '<i class="fas fa-exclamation-circle text-danger"></i>'
        );
        $indicator.attr('data-healthy', isHealthy.toString());
    }

    // Update sales chart data
    function updateSalesChart(data) {
        if (!salesChart) {
            initSalesChart(data);
            return;
        }

        salesChart.data.labels = data.dates;
        salesChart.data.datasets[0].data = data.revenue;
        salesChart.data.datasets[1].data = data.orders;
        salesChart.update();
    }

    // Log activity and update feed
    function logActivity(data) {
        const now = new Date();
        
        // Check for state changes
        checkStateChange('sync', data.sync_status.status);
        checkStateChange('import', data.import_queue.running > 0 ? 'running' : 'idle');
        checkAPIHealth(data.api_health);

        updateActivityFeed();
    }

    // Check for state changes
    function checkStateChange(type, newState) {
        const key = `last_${type}_state`;
        const lastState = localStorage.getItem(key);

        if (lastState !== newState) {
            localStorage.setItem(key, newState);
            if (lastState) { // Don't log on first load
                addActivityLog(type, lastState, newState);
            }
        }
    }

    // Check API health changes
    function checkAPIHealth(health) {
        ['printify', 'webhook'].forEach(api => {
            const key = `last_${api}_health`;
            const lastHealth = localStorage.getItem(key);
            const currentHealth = health[api].healthy.toString();

            if (lastHealth !== currentHealth) {
                localStorage.setItem(key, currentHealth);
                if (lastHealth) { // Don't log on first load
                    addActivityLog('api', api, currentHealth === 'true' ? 'recovered' : 'failed');
                }
            }
        });
    }

    // Add entry to activity log
    function addActivityLog(type, from, to) {
        const now = new Date();
        activityLog.unshift({
            type: type,
            from: from,
            to: to,
            time: now
        });

        // Keep only last 50 entries
        if (activityLog.length > 50) {
            activityLog.pop();
        }
    }

    // Update activity feed display
    function updateActivityFeed() {
        const $feed = $('#activity-feed');
        $feed.empty();

        activityLog.slice(0, 10).forEach(entry => {
            const time = new Date(entry.time);
            let message = '';
            let icon = '';
            let className = '';

            switch (entry.type) {
                case 'sync':
                    icon = 'fa-sync';
                    message = `Sync status changed from ${entry.from} to ${entry.to}`;
                    className = entry.to === 'completed' ? 'text-success' : 
                               entry.to === 'failed' ? 'text-danger' : 'text-info';
                    break;
                case 'import':
                    icon = 'fa-upload';
                    message = `Import process ${entry.to}`;
                    className = entry.to === 'running' ? 'text-info' : 'text-success';
                    break;
                case 'api':
                    icon = 'fa-plug';
                    message = `${entry.from} API ${entry.to}`;
                    className = entry.to === 'recovered' ? 'text-success' : 'text-danger';
                    break;
            }

            $feed.append(`
                <div class="activity-item d-flex align-items-center mb-2">
                    <div class="activity-icon me-2">
                        <i class="fas ${icon} ${className}"></i>
                    </div>
                    <div class="activity-content flex-grow-1">
                        <small class="text-muted">${time.toLocaleTimeString()}</small>
                        <div>${message}</div>
                    </div>
                </div>
            `);
        });
    }

    // Show error details in modal with animation
    document.querySelectorAll('.badge[data-error]').forEach(badge => {
        badge.addEventListener('click', function() {
            const error = this.getAttribute('data-error');
            if (error) {
                const errorDetails = document.getElementById('error-details');
                errorDetails.style.opacity = '0';
                errorDetails.textContent = error;
                
                const modal = new bootstrap.Modal('#error-details-modal');
                modal.show();
                
                setTimeout(() => {
                    errorDetails.style.transition = 'opacity 0.3s ease';
                    errorDetails.style.opacity = '1';
                }, 150);
            }
        });
    });

    // Add hover effect to activity items
    document.querySelectorAll('.activity-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Activity feed enhancements
    const activityFeedModal = new bootstrap.Modal('#activity-details-modal');
    let activityPage = 1;

    // Handle activity feed click
    document.querySelector('.activity-feed').addEventListener('click', function() {
        loadActivityHistory();
        activityFeedModal.show();
    });

    // Load more activity history
    document.querySelector('#load-more-activity').addEventListener('click', function() {
        activityPage++;
        loadActivityHistory(true);
    });

    function loadActivityHistory(append = false) {
        const feedContainer = document.querySelector('.activity-feed-full');
        if (!append) {
            feedContainer.innerHTML = `
                <div class="placeholder-glow">
                    <div class="placeholder w-100 mb-2"></div>
                    <div class="placeholder w-75 mb-2"></div>
                    <div class="placeholder w-100 mb-2"></div>
                </div>
            `;
        }

        // Load all activities from localStorage
        const activities = activityLog.slice((activityPage - 1) * 20, activityPage * 20).map(entry => {
            const time = new Date(entry.time);
            let message = '';
            let icon = '';
            let className = '';

            switch (entry.type) {
                case 'sync':
                    icon = 'fa-sync';
                    message = `Sync status changed from ${entry.from} to ${entry.to}`;
                    className = entry.to === 'completed' ? 'text-success' : 
                               entry.to === 'failed' ? 'text-danger' : 'text-info';
                    break;
                case 'import':
                    icon = 'fa-upload';
                    message = `Import process ${entry.to}`;
                    className = entry.to === 'running' ? 'text-info' : 'text-success';
                    break;
                case 'api':
                    icon = 'fa-plug';
                    message = `${entry.from} API ${entry.to}`;
                    className = entry.to === 'recovered' ? 'text-success' : 'text-danger';
                    break;
            }

            return `
                <div class="activity-item d-flex align-items-center mb-2" data-time="${entry.time}">
                    <div class="activity-icon me-2">
                        <i class="fas ${icon} ${className}"></i>
                    </div>
                    <div class="activity-content flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>${message}</div>
                            <small class="text-muted">${formatTimeAgo(time)}</small>
                        </div>
                        <small class="text-muted">${time.toLocaleString()}</small>
                    </div>
                </div>
            `;
        });

        if (!append) {
            feedContainer.innerHTML = activities.join('');
        } else {
            feedContainer.insertAdjacentHTML('beforeend', activities.join(''));
        }

        // Hide load more button if no more activities
        document.querySelector('#load-more-activity').style.display = 
            activityLog.length > activityPage * 20 ? 'block' : 'none';
    }

    // Format time ago
    function formatTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60,
            second: 1
        };

        for (let [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
            }
        }
        return 'Just now';
    }

    // Initialize notifications
    const notifications = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    notifications.forEach(notification => {
        notification.addEventListener('click', function(e) {
            // Prevent dropdown from showing if clicking notification bell
            if (this.querySelector('.fa-bell')) {
                e.preventDefault();
                e.stopPropagation();
                const notificationsModal = new bootstrap.Modal('#notifications-modal');
                notificationsModal.show();
            }
        });
    });

    // Handle help button click
    document.querySelector('.nav-help').addEventListener('click', function(e) {
        e.preventDefault();
        const helpModal = new bootstrap.Modal('#help-modal');
        helpModal.show();
    });

    // Initialize dashboard
    animateWidgets();
    updateDashboard();
    setInterval(updateDashboard, updateInterval);

    // Initialize tooltips and popovers
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
});

jQuery(document).ready(function($) {
    function updateWidget(widgetId, data) {
        const widget = $(`#${widgetId}`);
        widget.addClass('updating');
        
        // Animate old values out
        widget.find('.stat-value').fadeOut(300, function() {
            $(this).html(data.value).fadeIn(300);
        });
        
        // Update trend indicators
        if (data.trend) {
            const trendClass = data.trend > 0 ? 'trend-up' : 'trend-down';
            widget.find('.trend')
                .removeClass('trend-up trend-down')
                .addClass(trendClass)
                .html(Math.abs(data.trend) + '%');
        }
        
        setTimeout(() => {
            widget.removeClass('updating');
        }, 1000);
    }
    
    function initializeWidgets() {
        $('.card').each(function() {
            $(this).hover(
                function() { $(this).find('.widget-actions').fadeIn(200); },
                function() { $(this).find('.widget-actions').fadeOut(200); }
            );
        });
    }
    
    // Initialize widgets on load
    initializeWidgets();
});