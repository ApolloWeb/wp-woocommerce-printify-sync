<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AssetManager {
    private $plugin_url;
    private $version;
    
    public function __construct() {
        $this->plugin_url = WPWPS_PLUGIN_URL;
        $this->version = WPWPS_VERSION;
    }

    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueuePageAssets']);
    }

    /**
     * Register all plugin assets.
     */
    public function registerAssets()
    {
        // Register vendor scripts
        wp_register_script('wpwps-bootstrap', $this->plugin_url . 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js', ['jquery'], '5.1.3', true);
        wp_register_script('wpwps-chartjs', $this->plugin_url . 'assets/vendor/chart.js/chart.min.js', [], '3.7.0', true);
        
        // Register plugin scripts
        wp_register_script('wpwps-utils', $this->plugin_url . 'assets/js/wpwps-utils.js', ['jquery'], $this->version, true);
        wp_register_script('wpwps-dashboard', $this->plugin_url . 'assets/js/wpwps-dashboard.js', ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs', 'wpwps-utils'], $this->version, true);
        wp_register_script('wpwps-orders', $this->plugin_url . 'assets/js/wpwps-orders.js', ['jquery', 'wpwps-bootstrap', 'wpwps-utils'], $this->version, true);
        wp_register_script('wpwps-settings', $this->plugin_url . 'assets/js/wpwps-settings.js', ['jquery', 'wpwps-bootstrap', 'wpwps-utils'], $this->version, true);
        wp_register_script('wpwps-webhooks', $this->plugin_url . 'assets/js/wpwps-webhooks.js', ['jquery', 'wpwps-utils'], $this->version, true);
        wp_register_script('wpwps-logs', $this->plugin_url . 'assets/js/wpwps-logs.js', ['jquery', 'wpwps-utils'], $this->version, true);
        wp_register_script('wpwps-email-testing', $this->plugin_url . 'assets/js/email-testing.js', ['jquery', 'wpwps-utils'], $this->version, true);
        
        // Register styles
        wp_register_style('wpwps-bootstrap', $this->plugin_url . 'assets/vendor/bootstrap/css/bootstrap.min.css', [], '5.1.3');
        wp_register_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css', [], '6.1.1');
        wp_register_style('wpwps-global', $this->plugin_url . 'assets/css/wpwps-global.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], $this->version);
        wp_register_style('wpwps-components', $this->plugin_url . 'assets/css/wpwps-components.css', ['wpwps-global'], $this->version);
        wp_register_style('wpwps-dashboard', $this->plugin_url . 'assets/css/wpwps-dashboard.css', ['wpwps-global', 'wpwps-components'], $this->version);
        wp_register_style('wpwps-orders', $this->plugin_url . 'assets/css/wpwps-orders.css', ['wpwps-global', 'wpwps-components'], $this->version);
        wp_register_style('wpwps-settings', $this->plugin_url . 'assets/css/wpwps-settings.css', ['wpwps-global', 'wpwps-components'], $this->version);
        wp_register_style('wpwps-logs', $this->plugin_url . 'assets/css/wpwps-logs.css', ['wpwps-global', 'wpwps-components'], $this->version);
        wp_register_style('wpwps-email-settings', $this->plugin_url . 'assets/css/email-settings.css', ['wpwps-global', 'wpwps-components'], $this->version);
    }

    /**
     * Enqueue page-specific assets.
     *
     * @param string $hook The current admin page.
     */
    public function enqueuePageAssets($hook) {
        $page_scripts = [
            'wpwps-dashboard' => ['wpwps-dashboard'],
            'wpwps-orders' => ['wpwps-orders'],
            'wpwps-settings' => ['wpwps-settings', 'wpwps-webhooks'],
            'wpwps-logs' => ['wpwps-logs'],
            'wpwps-tickets' => ['wpwps-tickets'],
            'wpwps-products' => ['wpwps-products'],
            'wpwps-shipping' => ['wpwps-shipping'],
        ];

        foreach ($page_scripts as $page => $scripts) {
            if (strpos($hook, $page) !== false) {
                foreach ($scripts as $script) {
                    wp_enqueue_script($script);
                    wp_localize_script($script, 'wpwps_data', $this->getLocalizationData($page));
                }
                wp_enqueue_style('wpwps-main');
            }
        }
    }

    private function getLocalizationData($page) {
        $data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_ajax_nonce'),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'logs_url' => admin_url('admin.php?page=wpwps-logs'),
        ];

        $data['orders'] = [
            'order_details' => __('Order Details', 'wp-woocommerce-printify-sync'),
            'order_info' => __('Order Information', 'wp-woocommerce-printify-sync'),
            'order_number' => __('Order Number', 'wp-woocommerce-printify-sync'),
            'order_date' => __('Order Date', 'wp-woocommerce-printify-sync'),
            'order_status' => __('Order Status', 'wp-woocommerce-printify-sync'),
            'printify_id' => __('Printify ID', 'wp-woocommerce-printify-sync'),
            'customer_info' => __('Customer Information', 'wp-woocommerce-printify-sync'),
            'customer_name' => __('Name', 'wp-woocommerce-printify-sync'),
            'customer_email' => __('Email', 'wp-woocommerce-printify-sync'),
            'customer_phone' => __('Phone', 'wp-woocommerce-printify-sync'),
            'items' => __('Order Items', 'wp-woocommerce-printify-sync'),
            'product' => __('Product', 'wp-woocommerce-printify-sync'),
            'sku' => __('SKU', 'wp-woocommerce-printify-sync'),
            'quantity' => __('Quantity', 'wp-woocommerce-printify-sync'),
            'price' => __('Price', 'wp-woocommerce-printify-sync'),
            'total' => __('Total', 'wp-woocommerce-printify-sync'),
            'shipping' => __('Shipping', 'wp-woocommerce-printify-sync'),
            'tax' => __('Tax', 'wp-woocommerce-printify-sync'),
            'discount' => __('Discount', 'wp-woocommerce-printify-sync'),
            'last_synced' => __('Last Synced', 'wp-woocommerce-printify-sync'),
            'billing_address' => __('Billing Address', 'wp-woocommerce-printify-sync'),
            'shipping_address' => __('Shipping Address', 'wp-woocommerce-printify-sync'),
            'tracking_info' => __('Tracking Information', 'wp-woocommerce-printify-sync'),
            'carrier' => __('Carrier', 'wp-woocommerce-printify-sync'),
            'tracking_number' => __('Tracking Number', 'wp-woocommerce-printify-sync'),
            'shipped_date' => __('Shipped Date', 'wp-woocommerce-printify-sync'),
            'subtotal' => __('Subtotal', 'wp-woocommerce-printify-sync'),
            'status_processing' => __('Processing', 'wp-woocommerce-printify-sync'),
            'status_completed' => __('Completed', 'wp-woocommerce-printify-sync'),
            'status_on_hold' => __('On Hold', 'wp-woocommerce-printify-sync'),
            'status_cancelled' => __('Cancelled', 'wp-woocommerce-printify-sync'),
            'status_refunded' => __('Refunded', 'wp-woocommerce-printify-sync'),
        ];

        return apply_filters('wpwps_localize_script_' . $page, $data);
    }
}
