<?php
namespace ApolloWeb\WooCommercePrintifySync;

use ApolloWeb\WooCommercePrintifySync\Admin\Helpers\AdminHelper;

/**
 * Admin controller for WP WooCommerce Printify Sync
 */
class Admin {
    /**
     * Constructor
     */
    public function __construct() {
        // Register admin menu
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        
        // Register admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_save_api_key', [$this, 'ajaxSaveApiKey']);
        add_action('wp_ajax_wpwps_test_connection', [$this, 'ajaxTestConnection']);
        add_action('wp_ajax_wpwps_get_shops', [$this, 'ajaxGetShops']);
        add_action('wp_ajax_wpwps_select_shop', [$this, 'ajaxSelectShop']);
        add_action('wp_ajax_wpwps_import_products', [$this, 'ajaxImportProducts']);
        add_action('wp_ajax_wpwps_get_import_progress', [$this, 'ajaxGetImportProgress']);
        add_action('wp_ajax_wpwps_clear_products', [$this, 'ajaxClearProducts']);
    }
    
    /**
     * Register admin menu and submenus
     */
    public function registerAdminMenu() {
        // Main menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync',
            [$this, 'renderSettingsPage'],
            'dashicons-store',
            58
        );
        
        // Settings submenu
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync',
            [$this, 'renderSettingsPage']
        );
        
        // Shops submenu
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            __('Shops', 'wp-woocommerce-printify-sync'),
            __('Shops', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync-shops',
            [$this, 'renderShopsPage']
        );
        
        // Products submenu
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync-products',
            [$this, 'renderProductsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueueAssets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wp-woocommerce-printify-sync') === false) {
            return;
        }
        
        // Common CSS
        wp_enqueue_style(
            'wpwps-admin-style',
            AdminHelper::getAssetUrl('admin/assets/css/admin-styles.css'),
            [],
            WPWPS_VERSION
        );
        
        // Common JS
        wp_enqueue_script(
            'wpwps-admin',
            AdminHelper::getAssetUrl('admin/assets/js/admin.js'),
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-nonce'),
            'current_page' => AdminHelper::getCurrentPageSlug($hook),
            'texts' => [
                'saving' => __('Saving...', 'wp-woocommerce-printify-sync'),
                'testing' => __('Testing connection...', 'wp-woocommerce-printify-sync'),
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'importing' => __('Importing...', 'wp-woocommerce-printify-sync'),
                'clearing' => __('Clearing...', 'wp-woocommerce-printify-sync'),
                'confirm_clear' => __('Are you sure you want to delete all imported Printify products? This action cannot be undone.', 'wp-woocommerce-printify-sync'),
                'confirm_import' => __('Are you sure you want to import products from Printify? This might take a while.', 'wp-woocommerce-printify-sync'),
                'error' => __('Error:', 'wp-woocommerce-printify-sync'),
                'success' => __('Success:', 'wp-woocommerce-printify-sync'),
            ]
        ]);
        
        // Page specific assets
        if (strpos($hook, 'wp-woocommerce-printify-sync-shops') !== false) {
            wp_enqueue_script(
                'wpwps-shops',
                AdminHelper::getAssetUrl('admin/assets/js/shops.js'),
                ['jquery', 'wpwps-admin'],
                WPWPS_VERSION,
                true
            );
        }
        
        if (strpos($hook, 'wp-woocommerce-printify-sync-products') !== false) {
            wp_enqueue_script(
                'wpwps-products',
                AdminHelper::getAssetUrl('admin/assets/js/products.js'),
                ['jquery', 'wpwps-admin'],
                WPWPS_VERSION,
                true
            );
        }
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        $api = new PrintifyAPI();
        
        $this->renderTemplate('settings-page', [
            'api_key' => $api->getApiKey(),
            'current_date' => '2025-03-01 08:32:04',
            'current_user' => 'ApolloWeb'
        ]);
    }
    
    /**
     * Render shops page
     */
    public function renderShopsPage() {
        $this->renderTemplate('shops-section', [
            'selected_shop' => get_option('wpwps_selected_shop', ''),
            'current_date' => '2025-03-01 08:32:04',
            'current_user' => 'ApolloWeb'
        ]);
    }
    
    /**
     * Render products page
     */
    public function renderProductsPage() {
        $this->renderTemplate('products-section', [
            'shop_id' => get_option('wpwps_selected_shop', ''),
            'current_date' => '2025-03-01 08:32:04',
            'current_user' => 'ApolloWeb'
        ]);
    }
    
    /**
     * Render a template with data
     *
     * @param string $template Template name without .php extension
     * @param array $data Data to pass to the template
     * @return void
     */
    private function renderTemplate($template, $data = []) {
        $template_file = plugin_dir_path(dirname(__FILE__)) . 'admin/templates/' . $template . '.php';
        
        if (file_exists($template_file)) {
            // Extract data to make variables available in template
            extract($data);
            
            // Include the template
            include $template_file;
        } else {
            echo '<div class="error"><p>' . 
                 sprintf(
                     __('Template "%s" not found.', 'wp-woocommerce-printify-sync'), 
                     esc_html($template)
                 ) . 
                 '</p></div>';
        }
    }
    
    /**
     * AJAX handler for saving API key
     */
    public function ajaxSaveApiKey() {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $api = new PrintifyAPI();
        $result = $api->setApiKey($api_key);
        
        if ($result) {
            wp_send_json_success(['message' => __('API key saved successfully', 'wp-woocommerce-printify-sync')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save API key', 'wp-woocommerce-printify-sync')]);
        }
    }
    
    /**
     * AJAX handler for testing API connection
     */
    public function ajaxTestConnection() {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
        }
        
        $api = new PrintifyAPI();
        $result = $api->testConnection();
        
        if ($result === true) {
            wp_send_json_success(['message' => __('Connection successful! Your Printify API key is working.', 'wp-woocommerce-printify-sync')]);
        } else {
            wp_send_json_error([
                'message' => __('Connection failed. Please check your API key and try again.', 'wp-woocommerce-printify-sync'),
                'error' => $result->get_error_message()
            ]);
        }
    }
}