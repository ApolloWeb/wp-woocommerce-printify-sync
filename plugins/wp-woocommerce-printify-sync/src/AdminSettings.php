<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\ServiceProvider;

class AdminSettings extends ServiceProvider
{
    public function register()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function boot()
    {
        // Boot implementation
    }

    public function addAdminMenu()
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync',
            [$this, 'renderDashboard'],
            '', // Remove dashicon, will be replaced by Font Awesome
            56
        );

        add_submenu_page(
            'printify-sync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync',
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            'printify-sync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync-settings',
            [$this, 'renderSettings']
        );

        add_submenu_page(
            'printify-sync',
            __('Import Products', 'wp-woocommerce-printify-sync'),
            __('Import Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync-import',
            [$this, 'renderImport']
        );
    }

    public function registerSettings()
    {
        register_setting('printify_settings', 'printify_sync_api_url');
        register_setting('printify_settings', 'printify_sync_api_key');
        register_setting('printify_settings', 'printify_sync_shop_id');
    }

    public function renderDashboard()
    {
        $stats = [
            'status' => $this->getSystemStatus(),
            'products' => $this->getProductCount(),
            'orders' => $this->getOrderCount(),
            'synced_today' => $this->getSyncedTodayCount()
        ];

        $recent_imports = $this->getRecentImports();
        $recent_orders = $this->getRecentOrders();

        include plugin_dir_path(dirname(__FILE__)) . 'templates/admin/dashboard.php';
    }

    private function getSystemStatus()
    {
        $api_key = get_option('printify_sync_api_key', '');
        if (empty($api_key)) {
            return [
                'status' => 'error',
                'message' => __('API key not configured', 'wp-woocommerce-printify-sync')
            ];
        }

        return [
            'status' => 'success',
            'message' => __('System running correctly', 'wp-woocommerce-printify-sync')
        ];
    }

    private function getProductCount()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_printify_product_id'");
    }

    private function getOrderCount()
    {
        // Placeholder for now
        return 0;
    }

    private function getSyncedTodayCount()
    {
        // Placeholder for now
        return 0;
    }

    private function getRecentImports()
    {
        // Placeholder data - in production this would fetch from database
        return [
            [
                'id' => '123456',
                'title' => 'Sample T-Shirt',
                'date' => current_time('mysql'),
                'status' => 'success'
            ],
            [
                'id' => '123457',
                'title' => 'Sample Hoodie',
                'date' => current_time('mysql'),
                'status' => 'success'
            ]
        ];
    }

    private function getRecentOrders()
    {
        // Placeholder data - in production this would fetch from WooCommerce orders
        return [
            [
                'id' => '1001',
                'customer' => 'John Doe',
                'total' => '$29.99',
                'date' => current_time('mysql'),
                'status' => 'processing',
                'printify_status' => 'pending'
            ]
        ];
    }

    public function renderSettings()
    {
        $api_key = get_option('printify_sync_api_key', '');
        $shop_id = get_option('printify_sync_shop_id', '');
        $shops = $this->getShops();
        
        include plugin_dir_path(dirname(__FILE__)) . 'templates/admin/settings.php';
    }

    public function renderImport()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'templates/admin/product-import.php';
    }

    private function getShops()
    {
        $api_key = get_option('printify_sync_api_key', '');
        $api_url = get_option('printify_sync_api_url', 'https://api.printify.com/v1/');
        
        if (empty($api_key)) {
            return [];
        }

        try {
            $api = new PrintifyAPI($api_key);
            return $api->getShops() ?: [];
        } catch (\Exception $e) {
            error_log('Printify API Error: ' . $e->getMessage());
            return [];
        }
    }
}