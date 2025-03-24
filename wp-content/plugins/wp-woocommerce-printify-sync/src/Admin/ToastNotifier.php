<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Handles toast notifications for the admin interface
 */
class ToastNotifier {
    /**
     * Add a toast notification to be displayed on the next page load
     *
     * @param string $message The notification message
     * @param string $type The notification type (success, error, warning, info)
     * @param int $duration The duration in milliseconds
     * @return void
     */
    public static function add(string $message, string $type = 'info', int $duration = 3000): void {
        $notifications = get_option('wpwps_toast_notifications', []);
        $notifications[] = [
            'message' => $message,
            'type' => $type,
            'duration' => $duration,
            'time' => time()
        ];
        update_option('wpwps_toast_notifications', $notifications);
    }

    /**
     * Get all pending notifications and clear them
     *
     * @return array An array of notification data
     */
    public static function getAndClear(): array {
        $notifications = get_option('wpwps_toast_notifications', []);
        update_option('wpwps_toast_notifications', []);
        
        // Only return notifications from the last 5 minutes to avoid old ones
        $recent_notifications = array_filter($notifications, function($notification) {
            return (time() - $notification['time']) < 300; // 5 minutes
        });
        
        return $recent_notifications;
    }
    
    /**
     * Output the JavaScript to display queued toast notifications
     *
     * @return void
     */
    public static function output(): void {
        $notifications = self::getAndClear();
        
        if (empty($notifications)) {
            return;
        }
        
        add_action('admin_footer', function() use ($notifications) {
            ?>
            <div id="wpwps-toast-container"></div>
            <script>
            jQuery(document).ready(function($) {
                const container = $('#wpwps-toast-container');
                const notifications = <?php echo json_encode($notifications); ?>;
                
                function showToast(message, type = 'info', duration = 3000) {
                    // Create toast element
                    const toast = $('<div class="wpwps-toast">')
                        .addClass('wpwps-toast-' + type)
                        .text(message)
                        .appendTo(container);
                    
                    // Add icon based on type
                    let icon = 'info-circle';
                    if (type === 'success') icon = 'check-circle';
                    if (type === 'error') icon = 'times-circle';
                    if (type === 'warning') icon = 'exclamation-circle';
                    
                    $('<i class="fas fa-' + icon + '">').prependTo(toast);
                    
                    // Show toast
                    setTimeout(() => {
                        toast.addClass('wpwps-toast-show');
                    }, 100);
                    
                    // Add close button
                    $('<button type="button" class="wpwps-toast-close">')
                        .html('&times;')
                        .appendTo(toast)
                        .on('click', function() {
                            removeToast(toast);
                        });
                    
                    // Auto-remove after duration
                    setTimeout(() => {
                        removeToast(toast);
                    }, duration);
                }
                
                function removeToast(toast) {
                    toast.removeClass('wpwps-toast-show');
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }
                
                // Display each notification
                notifications.forEach(notification => {
                    setTimeout(() => {
                        showToast(
                            notification.message, 
                            notification.type, 
                            notification.duration
                        );
                    }, 300);
                });
            });
            </script>
            <?php
        });
    }
}
