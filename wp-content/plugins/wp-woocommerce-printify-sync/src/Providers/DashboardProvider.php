<?php
/**
 * Dashboard Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Providers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

/**
 * Dashboard Provider class for admin dashboard
 */
class DashboardProvider extends ServiceProvider
{
    /**
     * Register the provider
     *
     * @return void
     */
    public function register()
    {
        // Add admin menu items
        add_action('admin_menu', [$this, 'registerMenuPages']);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Add AJAX handlers
        add_action('wp_ajax_wpwps_get_dashboard_data', [$this, 'getDashboardData']);
    }
    
    /**
     * Register admin menu pages
     *
     * @return void
     */
    public function registerMenuPages()
    {
        // Main plugin menu
        add_menu_page(
            __('Printify Sync', WPWPS_TEXT_DOMAIN),
            __('Printify Sync', WPWPS_TEXT_DOMAIN),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            56 // Position after WooCommerce
        );
        
        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', WPWPS_TEXT_DOMAIN),
            __('Dashboard', WPWPS_TEXT_DOMAIN),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard']
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook The current admin page
     * @return void
     */
    public function enqueueAssets($hook)
    {
        // Only load on plugin pages
        if (strpos($hook, 'wpwps') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'wpwps-bootstrap',
            WPWPS_PLUGIN_URL . 'assets/core/css/bootstrap.min.css',
            [],
            WPWPS_PLUGIN_VERSION
        );
        
        wp_enqueue_style(
            'wpwps-fontawesome',
            WPWPS_PLUGIN_URL . 'assets/core/css/all.min.css',
            [],
            WPWPS_PLUGIN_VERSION
        );
        
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            ['wpwps-bootstrap', 'wpwps-fontawesome'],
            WPWPS_PLUGIN_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'wpwps-bootstrap',
            WPWPS_PLUGIN_URL . 'assets/core/js/bootstrap.bundle.min.js',
            ['jquery'],
            WPWPS_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wpwps-chartjs',
            WPWPS_PLUGIN_URL . 'assets/core/js/chart.min.js',
            [],
            WPWPS_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-admin.js',
            ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs'],
            WPWPS_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_nonce'),
            'i18n' => [
                'error' => __('An error occurred', WPWPS_TEXT_DOMAIN),
                'success' => __('Success', WPWPS_TEXT_DOMAIN)
            ]
        ]);
    }
    
    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function renderDashboard()
    {
        // Check user capabilities
        if (!$this->userCan('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', WPWPS_TEXT_DOMAIN));
        }
        
        $data = $this->getDashboardData();
        
        // Render the dashboard using the View helper
        $view = new View();
        echo $view->render('wpwps-dashboard', $data);
    }
    
    /**
     * Get data for the dashboard
     *
     * @return array
     */
    public function getDashboardData()
    {
        // AJAX handler
        if (defined('DOING_AJAX') && DOING_AJAX) {
            check_ajax_referer('wpwps_nonce', 'nonce');
            
            if (!$this->userCan('manage_woocommerce')) {
                wp_send_json_error(['message' => __('You do not have permission to perform this action.', WPWPS_TEXT_DOMAIN)]);
                wp_die();
            }
        }
        
        // Get Printify sync status
        $sync_status = get_transient('wpwps_sync_status') ?: [
            'last_sync' => false,
            'sync_in_progress' => false,
            'products_synced' => 0,
            'sync_errors' => 0
        ];
        
        // Get API health status
        $api_health = get_transient('wpwps_api_health') ?: [
            'status' => 'unknown',
            'last_checked' => false
        ];
        
        // Get order stats
        $order_stats = $this->getOrderStats();
        
        // Get product stats
        $product_stats = $this->getProductStats();
        
        // Get email queue status
        $email_queue = $this->getEmailQueueStats();
        
        return [
            'sync_status' => $sync_status,
            'api_health' => $api_health,
            'order_stats' => $order_stats,
            'product_stats' => $product_stats,
            'email_queue' => $email_queue,
            'is_configured' => $this->isPluginConfigured()
        ];
    }
    
    /**
     * Get order statistics
     *
     * @return array
     */
    private function getOrderStats()
    {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'in_production' => 0,
            'cancelled' => 0
        ];
        
        // Query orders with Printify meta
        $query = new \WC_Order_Query([
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $order_ids = $query->get_orders();
        $stats['total'] = count($order_ids);
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            
            if ($order) {
                $status = $order->get_status();
                $printify_status = get_post_meta($order_id, '_printify_order_status', true);
                
                if ($status === 'pending' || $status === 'on-hold') {
                    $stats['pending']++;
                } elseif ($status === 'processing') {
                    $stats['processing']++;
                } elseif ($status === 'completed') {
                    $stats['completed']++;
                } elseif ($status === 'cancelled') {
                    $stats['cancelled']++;
                }
                
                if ($printify_status === 'in-production') {
                    $stats['in_production']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get product statistics
     *
     * @return array
     */
    private function getProductStats()
    {
        $stats = [
            'total' => 0,
            'synced' => 0,
            'unsynced' => 0
        ];
        
        // Query products with Printify meta
        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $stats['total'] = $query->found_posts;
        
        foreach ($query->posts as $product_id) {
            $is_synced = get_post_meta($product_id, '_printify_is_synced', true);
            
            if ($is_synced === '1') {
                $stats['synced']++;
            } else {
                $stats['unsynced']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get email queue statistics
     *
     * @return array
     */
    private function getEmailQueueStats()
    {
        // In a real implementation, this would query the email queue table
        return [
            'total' => 0,
            'pending' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }
    
    /**
     * Check if the plugin is configured
     *
     * @return bool
     */
    private function isPluginConfigured()
    {
        $api_key = get_option('wpwps_printify_api_key');
        $shop_id = get_option('wpwps_printify_shop_id');
        
        return !empty($api_key) && !empty($shop_id);
    }
}