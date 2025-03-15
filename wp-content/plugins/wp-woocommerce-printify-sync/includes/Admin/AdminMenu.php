<?php
/**
 * Admin menu class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Core\View;

/**
 * Class AdminMenu
 */
class AdminMenu
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Register admin menu
     *
     * @return void
     */
    public function registerMenu(): void
    {
        // Main menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpps-dashboard',
            [$this, 'renderDashboardPage'],
            'none', // We'll use custom icon via CSS
            56
        );
        
        // Dashboard submenu
        add_submenu_page(
            'wpps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpps-dashboard',
            [$this, 'renderDashboardPage']
        );
        
        // Products submenu
        add_submenu_page(
            'wpps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpps-products',
            [$this, 'renderProductsPage']
        );
        
        // Settings submenu
        add_submenu_page(
            'wpps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpps-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page
     * @return void
     */
    public function enqueueAdminAssets(string $hook): void
    {
        // Only load on our plugin pages
        if (strpos($hook, 'wpps-') === false) {
            return;
        }
        
        // Enqueue Font Awesome from CDN
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );
        
        // Enqueue our admin CSS
        wp_enqueue_style(
            'wpps-admin-styles',
            WPPS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPPS_VERSION
        );
        
        // Enqueue our admin JS
        wp_enqueue_script(
            'wpps-admin-script',
            WPPS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WPPS_VERSION,
            true
        );
        
        // Add custom CSS for the menu icon
        add_action('admin_head', [$this, 'addMenuIconCSS']);
    }
    
    /**
     * Add custom CSS for menu icon
     *
     * @return void
     */
    public function addMenuIconCSS(): void
    {
        echo '<style>
            #adminmenu .toplevel_page_wpps-dashboard .wp-menu-image::before {
                font-family: "Font Awesome 5 Free";
                content: "\f553"; /* fa-tshirt */
                font-weight: 900;
                font-size: 18px;
            }
        </style>';
    }
    
    /**
     * Render dashboard page
     *
     * @return void
     */
    public function renderDashboardPage(): void
    {
        // Prepare data for the view
        $data = [
            'title' => __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'),
            'isConnected' => $this->isPrintifyConnected(),
            'totalProducts' => $this->getTotalProducts(),
            'totalOrders' => $this->getTotalOrders(),
            'totalRevenue' => $this->getTotalRevenue(),
            'recentOrders' => $this->getRecentOrders(),
            'lastSync' => $this->getLastSyncTime(),
            'syncErrors' => $this->getSyncErrors()
        ];
        
        // Render the view
        View::display('admin/dashboard', $data);
    }
    
    /**
     * Render products page
     *
     * @return void
     */
    public function renderProductsPage(): void
    {
        // Prepare data for the view
        $data = [
            'title' => __('Printify Products', 'wp-woocommerce-printify-sync')
        ];
        
        // Render the view
        View::display('admin/products', $data);
    }
    
    /**
     * Render settings page
     *
     * @return void
     */
    public function renderSettingsPage(): void
    {
        // Prepare data for the view
        $data = [
            'title' => __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            'settings' => get_option('wpps_settings', [])
        ];
        
        // Render the view
        View::display('admin/settings', $data);
    }
    
    /**
     * Check if Printify API is connected
     *
     * @return bool Connection status
     */
    private function isPrintifyConnected(): bool
    {
        $settings = get_option('wpps_settings', []);
        return !empty($settings['printify_api_key']);
    }
    
    /**
     * Get total products count
     *
     * @return int Count of products
     */
    private function getTotalProducts(): int
    {
        // Sample implementation - replace with actual logic
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_wpps_printify_id',
                    'compare' => 'EXISTS'
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];
        
        $products = new \WP_Query($args);
        return $products->found_posts;
    }
    
    /**
     * Get total orders count
     *
     * @return int Count of orders
     */
    private function getTotalOrders(): int
    {
        // Sample implementation - replace with actual logic
        return wc_orders_count('completed');
    }
    
    /**
     * Get total revenue
     *
     * @return float Total revenue
     */
    private function getTotalRevenue(): float
    {
        // Sample implementation - replace with actual logic
        return 1250.75;
    }
    
    /**
     * Get recent orders
     *
     * @return array List of recent orders
     */
    private function getRecentOrders(): array
    {
        // Sample implementation - replace with actual logic
        $orders = wc_get_orders([
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        return $orders;
    }
    
    /**
     * Get last sync time
     *
     * @return string Formatted last sync time
     */
    private function getLastSyncTime(): string
    {
        // Sample implementation - replace with actual logic
        $lastSync = get_option('wpps_last_sync', 0);
        
        if ($lastSync) {
            return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $lastSync);
        }
        
        return __('Never', 'wp-woocommerce-printify-sync');
    }
    
    /**
     * Get sync errors
     *
     * @return array List of sync errors
     */
    private function getSyncErrors(): array
    {
        // Sample implementation - replace with actual logic
        return [];
    }
}