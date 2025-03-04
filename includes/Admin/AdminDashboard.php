<?php
/**
 * Admin Dashboard
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminDashboard
 */
class AdminDashboard {
    /**
     * Render the dashboard
     */
    public function render() {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/admin-dashboard.php';
        
        if (file_exists($template_path)) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template_path;
            return;
        }
        
        // Fallback if template doesn't exist
        echo '<div class="wrap">';
        echo '<h1><i class="fas fa-tachometer-alt"></i> Printify Sync Dashboard</h1>';
        echo '<p>Welcome to the Printify Sync plugin for WooCommerce.</p>';
        
        echo '<div class="dashboard-info">';
        echo '<p><strong>Current User:</strong> ' . esc_html($current_user) . '</p>';
        echo '<p><strong>Current Time (UTC):</strong> ' . esc_html($current_datetime) . '</p>';
        echo '</div>';
        
        echo '</div>';
    }
}