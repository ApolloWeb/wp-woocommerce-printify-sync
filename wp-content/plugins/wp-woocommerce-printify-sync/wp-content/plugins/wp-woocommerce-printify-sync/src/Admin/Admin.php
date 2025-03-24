<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Admin
{
    private $settings;
    private $productSync;
    private $orderSync;
    private $ticketSystem;
    private $dashboard;

    public function __construct(
        Settings $settings,
        ProductSync $productSync,
        OrderSync $orderSync,
        TicketSystem $ticketSystem,
        Dashboard $dashboard
    ) {
        $this->settings = $settings;
        $this->productSync = $productSync;
        $this->orderSync = $orderSync;
        $this->ticketSystem = $ticketSystem;
        $this->dashboard = $dashboard;
    }

    public function registerMenuPages(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this->dashboard, 'render'],
            'dashicons-shirt',
            56
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard'
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this->settings, 'render']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-product-sync',
            [$this->productSync, 'render']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Order Sync', 'wp-woocommerce-printify-sync'),
            __('Order Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-order-sync',
            [$this->orderSync, 'render']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-tickets',
            [$this->ticketSystem, 'render']
        );
    }

    public function enqueueAssets($hook): void
    {
        // Only load on our plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }

        // Register and enqueue Bootstrap 5
        wp_register_style(
            'wpwps-bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            '5.2.3'
        );
        
        wp_register_script(
            'wpwps-bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.2.3',
            true
        );

        // Register and enqueue Font Awesome 6
        wp_register_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Register and enqueue Chart.js
        wp_register_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js',
            [],
            '4.3.0',
            true
        );

        // Common assets
        wp_enqueue_style('wpwps-bootstrap-css');
        wp_enqueue_script('wpwps-bootstrap-js');
        wp_enqueue_style('wpwps-fontawesome');
        wp_enqueue_style('wpwps-common', WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-common', WPWPS_PLUGIN_URL . 'assets/js/wpwps-common.js', ['jquery'], WPWPS_VERSION, true);

        // Page-specific assets
        $current_page = str_replace('toplevel_page_', '', $hook);
        
        switch ($current_page) {
            case 'wpwps-dashboard':
                wp_enqueue_script('wpwps-chartjs');
                wp_enqueue_script('wpwps-dashboard', WPWPS_PLUGIN_URL . 'assets/js/wpwps-dashboard.js', ['jquery', 'wpwps-chartjs'], WPWPS_VERSION, true);
                break;
                
            case 'wpwps-settings':
                wp_enqueue_script('wpwps-settings', WPWPS_PLUGIN_URL . 'assets/js/wpwps-settings.js', ['jquery'], WPWPS_VERSION, true);
                break;
                
            case 'wpwps-product-sync':
                wp_enqueue_script('wpwps-product-sync', WPWPS_PLUGIN_URL . 'assets/js/wpwps-product-sync.js', ['jquery'], WPWPS_VERSION, true);
                break;
                
            case 'wpwps-order-sync':
                wp_enqueue_script('wpwps-order-sync', WPWPS_PLUGIN_URL . 'assets/js/wpwps-order-sync.js', ['jquery'], WPWPS_VERSION, true);
                break;
                
            case 'wpwps-tickets':
                wp_enqueue_script('wpwps-tickets', WPWPS_PLUGIN_URL . 'assets/js/wpwps-tickets.js', ['jquery'], WPWPS_VERSION, true);
                break;
        }
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('wpwps-common', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-nonce'),
            'plugin_url' => WPWPS_PLUGIN_URL
        ]);
    }
}
