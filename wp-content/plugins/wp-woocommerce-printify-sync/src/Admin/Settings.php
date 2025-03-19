<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\Contracts\PrintifyAPIInterface;

class Settings {
    private PrintifyAPIInterface $api;

    public function __construct(PrintifyAPIInterface $api) {
        $this->api = $api;  // Fixed: Changed 'this' to '$this'
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Add AJAX handlers
        add_action('wp_ajax_save_api_settings', [$this, 'handleSaveApiSettings']);
        add_action('wp_ajax_test_endpoint', [$this, 'handleTestEndpoint']);
        add_action('wp_ajax_test_connection', [$this, 'handleTestConnection']);
        add_action('wp_ajax_save_settings', [$this, 'handleSaveSettings']);
        add_action('wp_ajax_get_shops', [$this, 'handleGetShops']);
    }

    public function addSettingsPage(): void {
        $dashboard = new Dashboard();
        $dashboard->init();
        
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync',
            [$dashboard, 'renderPage'],
            'dashicons-tshirt' // Changed from dashicons-store
        );

        add_submenu_page(
            'printify-sync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-settings',
            [$this, 'renderSettingsPage']
        );

        // Add Products page
        $products = new Products($this->api);
        $products->init();
        
        add_submenu_page(
            'printify-sync',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-products',
            [$products, 'renderPage']
        );
    }

    public function enqueueAssets(string $hook): void {
        if (!in_array($hook, ['toplevel_page_printify-sync', 'printify_page_printify-sync-settings'])) {
            return;
        }

        // Bootstrap 5 CSS
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            [],
            '5.1.3'
        );

        // Font Awesome
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );

        // Plugin CSS
        wp_enqueue_style(
            'printify-sync-admin',
            PRINTIFY_SYNC_URL . 'assets/css/admin.css',
            ['bootstrap', 'fontawesome'],
            PRINTIFY_SYNC_VERSION
        );

        // Bootstrap 5 JS and Popper
        wp_enqueue_script(
            'bootstrap-bundle',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.1.3',
            true
        );

        // Plugin JS
        wp_enqueue_script(
            'printify-sync-settings',
            PRINTIFY_SYNC_URL . 'assets/js/wpwps-admin.js',
            ['jquery', 'bootstrap-bundle'],
            PRINTIFY_SYNC_VERSION,
            true
        );

        wp_localize_script('printify-sync-settings', 'printifySettings', [
            'nonce' => wp_create_nonce('printify_settings'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'hasShop' => !empty(get_option('printify_shop_id')),
            'selectedShop' => get_option('printify_shop_id'),
            'i18n' => [
                'loadShops' => __('Load Shops', 'wp-woocommerce-printify-sync'),
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'saving' => __('Saving...', 'wp-woocommerce-printify-sync'),
                'select' => __('Select Shop', 'wp-woocommerce-printify-sync'),
                'selected' => __('Selected', 'wp-woocommerce-printify-sync'),
                'apiKeyRequired' => __('Please enter your API key first', 'wp-woocommerce-printify-sync'),
                'noShops' => __('No shops found', 'wp-woocommerce-printify-sync'),
                'loadError' => __('Error loading shops', 'wp-woocommerce-printify-sync'),
                'saveError' => __('Error saving shop', 'wp-woocommerce-printify-sync'),
                'shopSaved' => __('Shop selected successfully!', 'wp-woocommerce-printify-sync'),
                'saveApi' => __('Save API Settings', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function renderSettingsPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $api_key = get_option('printify_api_key', '');
        $api_endpoint = get_option('printify_api_endpoint', 'https://api.printify.com/v1');
        $shop_id = get_option('printify_shop_id', '');
        
        require PRINTIFY_SYNC_PATH . 'templates/admin/settings.php';
    }

    public function handleGetShops(): void {
        check_ajax_referer('printify_settings', 'security');

        try {
            // Get shops from API
            $shops = $this->api->getShops();

            wp_send_json_success([
                'shops' => $shops
            ]);
        } catch (\Exception $e) {
            error_log('Error getting shops: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage(),
                'shops' => []
            ]);
        }
    }
}
