<?php
/**
<<<<<<< HEAD
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
=======
 * Admin Dashboard * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.2.2
 * @date 2025-03-04 00:33:55
 */namespace ApolloWeb\WPWooCommercePrintifySync\Admin;/**
 * Class AdminDashboard
 * Main dashboard controller
 */
class AdminDashboard {
    /**
     * Register dashboard
     */
    public static function register() {
        // Add admin dashboard page
        add_action('admin_menu', [self::class, 'addDashboardPage']);
    }
    
    /**
     * Add dashboard page
     */
    public static function addDashboardPage() {
        // NOTE: Don't add the dashboard page here, it should be added only in the main plugin file
        // This prevents duplicate dashboard pages from being registered
        
        // Instead, register any dashboard-specific AJAX handlers or other functionality here
        add_action('wp_ajax_printify_sync_dashboard_data', [self::class, 'getDashboardData']);
    }
    
    /**
     * Get dashboard data (AJAX handler)
     */
    public static function getDashboardData() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            exit;
        }
        
        // Current date and user for demo data
        $current_date = '2025-03-04 00:33:55';
        $current_user = 'ApolloWeb';
        
        // Demo data for dashboard
        $data = [
            'current_date' => $current_date,
            'current_user' => $current_user,
            'stats' => [
                'product_syncs' => 1248,
                'active_products' => 867,
                'orders_processed' => 342,
                'open_tickets' => 24
            ],
            'charts' => [
                'sales' => [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'data' => [4500, 5200, 4800, 5800, 6000, 5500]
                ],
                'categories' => [
                    'labels' => ['T-Shirts', 'Mugs', 'Posters', 'Phone Cases', 'Other'],
                    'data' => [45, 20, 15, 12, 8]
                ],
                'api_performance' => [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'data' => [320, 420, 380, 290, 310, 250, 270]
                ]
            ],
            'sync_success_rate' => 98.2
        ];
        
        wp_send_json_success($data);
        exit;
    }    /**
     * Render the dashboard
     */
    public function render() {
        include plugin_dir_path(__FILE__) . '../../templates/admin/admin-dashboard.php';
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
