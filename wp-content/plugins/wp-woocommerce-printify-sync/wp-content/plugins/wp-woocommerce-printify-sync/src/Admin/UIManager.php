<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class UIManager
{
    /**
     * Register hooks for UI components
     */
    public function __construct()
    {
        // UI related AJAX handlers
        add_action('wp_ajax_wpwps_toggle_sidebar', [$this, 'toggleSidebar']);
        add_action('wp_ajax_wpwps_dismiss_notification', [$this, 'dismissNotification']);
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Enqueue custom admin assets for our enhanced UI
     */
    public function enqueueAdminAssets($hook): void
    {
        // Only load on our plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        // Enqueue our custom Bootstrap CSS overrides
        wp_enqueue_style(
            'wpwps-bootstrap-custom',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-bootstrap-custom.css',
            ['wpwps-bootstrap-css'],
            WPWPS_VERSION
        );
        
        // Enqueue our custom Bootstrap behaviors
        wp_enqueue_script(
            'wpwps-bootstrap-custom',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-bootstrap-custom.js',
            ['jquery', 'wpwps-bootstrap-js'],
            WPWPS_VERSION,
            true
        );
        
        // Add notification data to JS
        wp_localize_script('wpwps-bootstrap-custom', 'wpwpsUI', [
            'notifications' => $this->getNotifications(),
            'current_user' => [
                'name' => wp_get_current_user()->display_name,
                'avatar' => get_avatar_url(wp_get_current_user()->ID),
                'role' => $this->getCurrentUserRole()
            ],
            'sidebar_state' => get_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', true) ?: 'expanded'
        ]);
    }
    
    /**
     * Get current user's primary role
     * 
     * @return string The user's primary role
     */
    public function getCurrentUserRole(): string
    {
        $user = wp_get_current_user();
        return !empty($user->roles) ? ucfirst($user->roles[0]) : 'Administrator';
    }
    
    /**
     * Get notifications for the current user
     * 
     * @return array List of notifications
     */
    public function getNotifications(): array
    {
        // Here we would typically fetch real notifications from the database
        // For now, we'll return sample data
        return [
            [
                'id' => 1,
                'title' => 'New support ticket',
                'message' => 'A new support ticket has been created',
                'time' => '5 min ago',
                'read' => false,
                'icon' => 'fas fa-ticket-alt',
                'icon_color' => 'text-warning'
            ],
            [
                'id' => 2,
                'title' => 'Printify API rate limit',
                'message' => 'Approaching API rate limit (80%)',
                'time' => '1 hour ago',
                'read' => true,
                'icon' => 'fas fa-exclamation-triangle',
                'icon_color' => 'text-danger'
            ],
            [
                'id' => 3,
                'title' => 'Sync completed',
                'message' => '24 products successfully synced',
                'time' => 'Yesterday',
                'read' => true,
                'icon' => 'fas fa-sync',
                'icon_color' => 'text-success'
            ]
        ];
    }
    
    /**
     * Toggle sidebar collapsed state
     */
    public function toggleSidebar(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $current_state = get_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', true) ?: 'expanded';
        $new_state = $current_state === 'expanded' ? 'collapsed' : 'expanded';
        
        update_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', $new_state);
        
        wp_send_json_success(['state' => $new_state]);
    }
    
    /**
     * Dismiss a notification
     */
    public function dismissNotification(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $notification_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(['message' => 'Invalid notification ID']);
            return;
        }
        
        // In a real implementation, you would mark the notification as read in the database
        // For now, we'll just return success
        wp_send_json_success(['message' => 'Notification dismissed']);
    }
}
