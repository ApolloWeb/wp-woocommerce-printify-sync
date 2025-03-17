jQuery(document).ready(function($) {
    const progressTracker = {
        init: function() {
            this.progressBars = {};
            this.initProgressBars();
            this.initNotifications();
            this.startPolling();
        },

        initProgressBars: function() {
            $('.wpwps-progress-bar').each((_, element) => {
                const $element = $(element);
                const taskId = $element.data('task-id');
                
                this.progressBars[taskId] = {
                    element: $element,
                    progress: $element.find('.progress-bar'),
                    status: $element.find('.progress-status'),
                    percentage: $element.find('.progress-percentage'),
                };

                this.updateProgressBar(taskId);
            });
        },

        initNotifications: function() {
            // Handle notification dismissal
            $(document).on('click', '.wpwps-notification .dismiss', function(e) {
                e.preventDefault();
                const $notification = $(this).closest('.wpwps-notification');
                const notificationId = $notification.data('id');
                
                progressTracker.dismissNotification(notificationId, $notification);
            });

            // Handle notification actions
            $(document).on('click', '.wpwps-notification .action-button', function(e) {
                e.preventDefault();
                const $button = $(this);
                const action = $button.data('action');
                const notificationId = $button.closest('.wpwps-notification').data('id');
                
                progressTracker.handleNotificationAction(action, notificationId);
            });
        },

        startPolling: function() {
            setInterval(() => this.pollProgress(), 5000); // Poll every 5 seconds
        },

        pollProgress: function() {
            const taskIds = Object.keys(this.progressBars);
            
            if (!taskIds.length) {
                return;
            }

            $.ajax({
                url: wpwpsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpwps_get_progress',
                    task_ids: taskIds,
                    nonce: wpwpsAdmin.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        Object.keys(response.data).forEach(taskId => {
                            this.updateProgressBar(taskId, response.data[taskId]);
                        });
                    }
                }
            });
        },

        updateProgressBar: function(taskId, data) {
            const bar = this.progressBars[taskId];
            if (!bar) return;

            if (data) {
                const percentage = Math.round(data.percentage);
                bar.progress.css('width', percentage + '%');
                bar.percentage.text(percentage + '%');
                
                const status = this.formatStatus(data);
                bar.status.text(status);

                if (data.status === 'completed') {
                    bar.element.addClass('completed');
                } else if (data.status === 'failed') {
                    bar.element.addClass('failed');
                }
            }
        },

        formatStatus: function(data) {
            return `${data.completed}/${data.total} completed` + 
                   (data.failed ? ` (${data.failed} failed)` : '');
        },

        dismissNotification: function(notificationId, $element) {
            $.ajax({
                url: wpwpsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpwps_dismiss_notification',
                    notification_id: notificationId,
                    nonce: wpwpsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $element.slideUp(200, function() {
                            $(this).remove();
                        });
                    }
                }
            });
        },

        handleNotificationAction: function(action, notificationId) {
            $.ajax({
                url: wpwpsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpwps_notification_action',
                    notification_action: action,
                    notification_id: notificationId,
                    nonce: wpwpsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    };

    progressTracker.init();
});