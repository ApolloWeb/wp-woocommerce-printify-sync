<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @package    ApolloWeb\WPWooCommercePrintifySync
 * @since      1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\APIClient;
use ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Utilities\Encryption;
use ApolloWeb\WPWooCommercePrintifySync\Utilities\Logger;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {

    /**
     * The ID of this plugin.
     *
     * @var string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->plugin_name = 'wp-woocommerce-printify-sync';
        $this->version = WPWPS_VERSION;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WPWPS_PLUGIN_URL . 'admin/css/admin.css',
            array('wp-components'),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-api-handler',
            WPWPS_PLUGIN_URL . 'admin/js/api-handler.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_enqueue_script(
            $this->plugin_name . '-product-sync',
            WPWPS_PLUGIN_URL . 'admin/js/product-sync.js',
            array('jquery', $this->plugin_name . '-api-handler'),
            $this->version,
            false
        );

        wp_enqueue_script(
            $this->plugin_name . '-settings',
            WPWPS_PLUGIN_URL . 'admin/js/settings.js',
            array('jquery', $this->plugin_name . '-api-handler'),
            $this->version,
            false
        );

        wp_enqueue_script(
            $this->plugin_name,
            WPWPS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'wp-util', 'wp-components', 'wp-element'),
            $this->version,
            false
        );

        // Pass Ajax URL and nonce to JavaScript
        wp_localize_script(
            $this->plugin_name . '-api-handler',
            'wpwps_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wpwps_nonce'),
                'user'     => 'ApolloWeb',
                'date'     => '2025-03-01 13:59:13'
            )
        );
    }

    /**
     * Add plugin admin menu.
     */
    public function add_plugin_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-update',
            58 // After WooCommerce
        );

        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            array($this, 'display_dashboard_page')
        );

        // Product Sync submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-product-sync',
            array($this, 'display_product_sync_page')
        );

        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            array($this, 'display_settings_page')
        );

        // Logs submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-logs',
            array($this, 'display_logs_page')
        );
    }

    /**
     * Display the dashboard page.
     */
    public function display_dashboard_page() {
        require_once WPWPS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display the product sync page.
     */
    public function display_product_sync_page() {
        require_once WPWPS_PLUGIN_DIR . 'admin/partials/product-sync.php';
    }

    /**
     * Display the settings page.
     */
    public function display_settings_page() {
        require_once WPWPS_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Display the logs page.
     */
    public function display_logs_page() {
        require_once WPWPS_PLUGIN_DIR . 'admin/partials/logs.php';
    }

    /**
     * Save plugin settings via Ajax.
     */
    public function save_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-woocommerce-printify-sync'));
        }

        // Check for required permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'));
        }

        // Get and validate API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(__('API key cannot be empty.', 'wp-woocommerce-printify-sync'));
        }

        // Test API key
        $api_client = new APIClient($api_key);
        $test_result = $api_client->test_connection();
        
        if (!$test_result['success']) {
            wp_send_json_error($test_result['message']);
        }

        // Encrypt and store API key
        $encryption = new Encryption();
        $encrypted_key = $encryption->encrypt($api_key);
        update_option('wpwps_api_key_encrypted', $encrypted_key);
        
        // Get shop ID
        $shop_id = isset($_POST['shop_id']) ? intval($_POST['shop_id']) : 0;
        update_option('wpwps_shop_id', $shop_id);
        
        // Get additional settings
        $sync_frequency = isset($_POST['sync_frequency']) ? sanitize_text_field($_POST['sync_frequency']) : 'twicedaily';
        update_option('wpwps_sync_frequency', $sync_frequency);
        
        // Register webhooks with Printify
        $webhook_handler = new \ApolloWeb\WPWooCommercePrintifySync\API\WebhookHandler();
        $webhook_registration = $webhook_handler->register_printify_webhooks($api_key);
        
        if (!$webhook_registration['success']) {
            Logger::log('Settings', 'Error registering webhooks: ' . $webhook_registration['message'], 'error');
        }
        
        // Update cron job schedule if needed
        if (wp_next_scheduled('wpwps_scheduled_product_sync')) {
            wp_clear_scheduled_hook('wpwps_scheduled_product_sync');
        }
        wp_schedule_event(time(), $sync_frequency, 'wpwps_scheduled_product_sync');
        
        wp_send_json_success(__('Settings saved successfully.', 'wp-woocommerce-printify-sync'));
    }

    /**
     * Trigger product synchronization via Ajax.
     */
    public function sync_products() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-woocommerce-printify-sync'));
        }

        // Check for required permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'));
        }

        // Get sync type
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : 'all';

        // Initialize product sync
        $product_sync = new ProductSync();
        
        // Start sync process in background using Action Scheduler
        if ($sync_type === 'all') {
            $result = $product_sync->schedule_full_sync();
        } else {
            $product_ids = isset($_POST['product_ids']) ? array_map('intval', (array)$_POST['product_ids']) : array();
            $result = $product_sync->schedule_partial_sync($product_ids);
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Product synchronization scheduled successfully. Check the logs for details.', 'wp-woocommerce-printify-sync'),
                'job_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to schedule product synchronization.', 'wp-woocommerce-printify-sync'));
        }
    }

    /**
     * Get the status of ongoing sync jobs via Ajax.
     */
    public function get_sync_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-woocommerce-printify-sync'));
        }

        // Check for required permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'));
        }

        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        
        // Get logs for the job
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwps_sync_logs';
        
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT time, message, status FROM $table_name WHERE id = %d ORDER BY time DESC",
                $job_id
            ),
            ARRAY_A
        );
        
        // Get job status from Action Scheduler
        $action_status = 'unknown';
        if (function_exists('as_get_scheduled_actions')) {
            $actions = as_get_scheduled_actions(array(
                'hook' => 'wpwps_process_sync',
                'args' => array($job_id),
                'status' => 'any',
            ));
            
            if (!empty($actions)) {
                $action = current($actions);
                $action_status = $action->get_status();
            }
        }
        
        $progress = array(
            'status' => $action_status,
            'logs' => $logs,
            'last_update' => '2025-03-01 13:59:13',
            'user' => 'ApolloWeb'
        );
        
        wp_send_json_success($progress);
    }
}