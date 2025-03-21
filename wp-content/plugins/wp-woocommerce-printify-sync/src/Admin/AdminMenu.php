<?php
/**
 * Admin Menu.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Dashboard;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Products;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Orders;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Shipping;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;

/**
 * Admin Menu class.
 */
class AdminMenu {
    /**
     * Admin pages.
     *
     * @var array
     */
    private $pages = [];

    /**
     * Initialize admin menu.
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Initialize admin pages.
        $this->pages = [
            'dashboard' => new Dashboard(),
            'settings' => new Settings(),
            'products' => new Products(),
            'orders' => new Orders(),
            'shipping' => new Shipping(),
        ];
        
        // Initialize each page.
        foreach ($this->pages as $page) {
            $page->init();
        }
    }

    /**
     * Register admin menu.
     *
     * @return void
     */
    public function registerMenu() {
        $capability = 'manage_woocommerce';
        
        // Main menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-dashboard',
            [$this->pages['dashboard'], 'render'],
            'dashicons-tag',
            58 // After WooCommerce
        );
        
        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-dashboard',
            [$this->pages['dashboard'], 'render']
        );
        
        // Products submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-products',
            [$this->pages['products'], 'render']
        );
        
        // Orders submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-orders',
            [$this->pages['orders'], 'render']
        );
        
        // Shipping submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-shipping',
            [$this->pages['shipping'], 'render']
        );
        
        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-settings',
            [$this->pages['settings'], 'render']
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueueAssets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        // Register and enqueue common styles
        wp_register_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        wp_register_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );

        wp_register_style(
            'wpwps-admin',
            WPWPS_ASSETS_URL . 'css/wpwps-admin.css',
            ['font-awesome', 'bootstrap'],
            WPWPS_VERSION
        );
        
        wp_enqueue_style('wpwps-admin');
        
        // Register and enqueue common scripts
        wp_register_script(
            'bootstrap-bundle',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );
        
        wp_register_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js',
            [],
            '4.3.0',
            true
        );
        
        wp_enqueue_script('bootstrap-bundle');
        wp_enqueue_script('chart-js');
        
        // Load page-specific assets
        $page = str_replace('wpwps-printify_page_wpwps-', '', $hook);
        $page = str_replace('toplevel_page_wpwps-', '', $page);
        
        if (in_array($page, ['dashboard', 'settings', 'products', 'orders', 'shipping'], true)) {
            wp_enqueue_style(
                "wpwps-{$page}",
                WPWPS_ASSETS_URL . "css/wpwps-{$page}.css",
                ['wpwps-admin'],
                WPWPS_VERSION
            );
            
            wp_enqueue_script(
                "wpwps-{$page}",
                WPWPS_ASSETS_URL . "js/wpwps-{$page}.js",
                ['jquery', 'bootstrap-bundle'],
                WPWPS_VERSION,
                true
            );
            
            // Add localized data for scripts
            wp_localize_script("wpwps-{$page}", 'wpwps', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce("wpwps_{$page}_nonce"),
            ]);
        }
    }
}
