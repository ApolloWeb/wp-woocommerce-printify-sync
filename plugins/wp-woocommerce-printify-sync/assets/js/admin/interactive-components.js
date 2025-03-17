class PrintifyInteractiveUI {
    constructor() {
        this.initializeComponents();
        this.bindEvents();
    }

    initializeComponents() {
        this.initializeCharts();
        this.initializeDashboard();
        this.initializeNotifications();
    }

    initializeCharts() {
        // Progress Donut Chart
        const donutCtx = document.getElementById('sync-progress-donut');
        if (donutCtx) {
            this.donutChart = new Chart(donutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Failed'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#46b450', '#00a0d2', '#dc3232']
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Timeline Chart
        const timelineCtx = document.getElementById('sync-timeline');
        if (timelineCtx) {
            this.timelineChart = new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Sync Operations',
                        data: [],
                        borderColor: '#00a0d2',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    initializeDashboard() {
        // Draggable Dashboard Widgets
        $('.wpwps-dashboard-widgets').sortable({
            handle: '.widget-header',
            placeholder: 'widget-placeholder',
            update: (event, ui) => {
                this.saveDashboardLayout();
            }
        });

        // Collapsible Widgets
        $(document).on('click', '.widget-toggle', (e) => {
            const $widget = $(e.target).closest('.dashboard-widget');
            $widget.toggleClass('collapsed');
            this.saveWidgetState($widget);
        });
    }

    initializeNotifications() {
        // Initialize notification center
        const notificationCenter = new NotificationCenter({
            container: '.wpwps-notification-center',
            maxNotifications: 5,
            groupSimilar: true
        });

        // Handle notification interactions
        $(document).on('click', '.notification-action', (e) => {
            const $button = $(e.target);
            const action = $button.data('action');
            const notificationId = $button.closest('.wpwps-notification').data('id');
            
            this.handleNotificationAction(action, notificationId);
        });
    }

    bindEvents() {
        // WebSocket event handlers
        window.socket.on('sync_progress', (data) => {
            this.updateProgressVisualization(data);
        });

        window.socket.on('notification', (data) => {
            this.handleNewNotification(data);
        });

        // UI event handlers
        $(document).on('click', '.refresh-data', () => {
            this.refreshDashboardData();
        });

        $(document).on('change', '.date-range-filter', (e) => {
            this.updateTimelineChart($(e.target).val());
        });
    }

    updateProgressVisualization(data) {
        // Update donut chart
        if (this.donutChart) {
            this.donutChart.data.datasets[0].data = [
                data.completed,
                data.in_progress,
                data.failed
            ];
            this.donutChart.update();
        }

        // Update progress bars
        $('.task-progress').each((_, element) => {
            const $element = $(element);
            const taskId = $element.data('task-id');
            
            if (data.tasks && data.tasks[taskId]) {
                const task = data.tasks[taskId];
                this.updateTaskProgress($element, task);
            }
        });
    }

    updateTaskProgress($element, task) {
        const percentage = (task.current / task.total) * 100;
        
        $element.find('.progress-bar')
            .css('width', `${percentage}%`)
            .attr('aria-valuenow', percentage);
            
        $element.find('.progress-text')
            .text(`${task.current}/${task.total}`);
            
        if (task.status === 'completed') {
            $element.addClass('completed');
        } else if (task.status === 'failed') {
            $element.addClass('failed');
        }
    }

    handleNewNotification(data) {
        const notification = new NotificationItem(data);
        notification.show();
    }

    saveDashboardLayout() {
        const layout = $('.wpwps-dashboard-widgets').sortable('toArray');
        
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_save_dashboard_layout',
                layout: layout,
                nonce: wpwpsAdmin.nonce
            }
        });
    }

    saveWidgetState($widget) {
        const widgetId = $widget.attr('id');
        const collapsed = $widget.hasClass('collapsed');
        
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_save_widget_state',
                widget_id: widgetId,
                collapsed: collapsed,
                nonce: wpwpsAdmin.nonce
            }
        });
    }

    refreshDashboardData() {
        // Show loading state
        $('.dashboard-widget').addClass('loading');
        
        $.ajax({
            url: wpwpsAdmin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpwps_refresh_dashboard',
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateDashboardData(response.data);
                }
            },
            complete: () => {
                $('.dashboard-widget').removeClass('loading');
            }
        });
    }
}

// Initialize the interactive UI
jQuery(document).ready(function($) {
    window.printifyUI = new PrintifyInteractiveUI();
});