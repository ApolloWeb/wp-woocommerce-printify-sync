<?php
/**
 * Admin Dashboard Helper
 * 
 * This class helps manage the admin dashboard functionalities.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminDashboardHelper {

    /**
     * Register admin dashboard functionalities.
     */
    public static function register() {
        // Add the admin dashboard page.
        add_action('admin_menu', [__CLASS__, 'addDashboardPage']);
        // Enqueue dashboard assets.
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueAssets']);
        // AJAX endpoint to load dashboard data.
        add_action('wp_ajax_printify_sync_dashboard_data', [__CLASS__, 'getDashboardData']);
    }

    /**
     * Add the dashboard page.
     */
    public static function addDashboardPage() {
        add_menu_page(
            'Printify Sync Dashboard',
            'Printify Sync',
            'manage_options',
            'wp-woocommerce-printify-sync',
            [__CLASS__, 'renderDashboard'],
            'dashicons-chart-area',
            2
        );
    }

    /**
     * Enqueue dashboard assets.
     *
     * @param string $hook Current page hook.
     */
    public static function enqueueAssets($hook) {
        if ($hook !== 'toplevel_page_wp-woocommerce-printify-sync') {
            return;
        }

        // Enqueue Chart.js.
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        
        // Enqueue custom dashboard script.
        wp_enqueue_script(
            'printify-sync-dashboard',
            plugins_url('assets/js/admin-dashboard.js', PRINTIFY_SYNC_PATH),
            ['chart-js', 'jquery'],
            '1.0.0',
            true
        );
        
        // Enqueue custom widgets script.
        wp_enqueue_script(
            'printify-sync-widgets',
            plugins_url('assets/js/admin-widgets.js', PRINTIFY_SYNC_PATH),
            ['chart-js', 'jquery'],
            '1.0.0',
            true
        );
        
        // Localize script with AJAX URL and nonce.
        wp_localize_script('printify-sync-dashboard', 'PrintifySyncDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('printify_sync_nonce')
        ]);
        
        // Enqueue custom dashboard styles.
        wp_enqueue_style(
            'printify-sync-dashboard',
            plugins_url('assets/css/admin-dashboard.css', PRINTIFY_SYNC_PATH),
            [],
            '1.0.0'
        );
    }

    /**
     * Render the admin dashboard.
     */
    public static function renderDashboard() {
        include PRINTIFY_SYNC_PATH . 'templates/admin/admin-dashboard.php';
    }

    /**
     * Get dashboard data (AJAX handler).
     *
     * Replace the below queries with your actual queries/data.
     */
    public static function getDashboardData() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }
        
        // Replace the demo data below with your real widget queries.
        $data = [
            'stats'   => [
                'active_shops'      => (int) get_option('printify_sync_active_shops', 5),
                'synced_products'   => (int) get_option('printify_sync_synced_products', 248),
                'recent_orders'     => (int) get_option('printify_sync_recent_orders', 18),
                'last_sync'         => get_option('printify_sync_last_sync', '2 hrs ago')
            ],
            'charts'  => [
                'sales' => [
                    // Replace with your dynamic labels/data for day/week/month/year as needed.
                    'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
                    'data'   => [floatval(get_option('printify_sync_sale_day_1', 450)),
                                 floatval(get_option('printify_sync_sale_day_2', 520)),
                                 floatval(get_option('printify_sync_sale_day_3', 480)),
                                 floatval(get_option('printify_sync_sale_day_4', 580)),
                                 floatval(get_option('printify_sync_sale_day_5', 600)),
                                 floatval(get_option('printify_sync_sale_day_6', 550)),
                                 floatval(get_option('printify_sync_sale_day_7', 620))]
                ]
            ],
            'sync_success_rate' => get_option('printify_sync_success_rate', 98.2)
        ];
        
        wp_send_json_success($data);
    }
}