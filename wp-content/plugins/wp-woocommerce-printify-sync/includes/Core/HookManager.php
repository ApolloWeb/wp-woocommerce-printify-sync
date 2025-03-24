<?php
/**
 * Centralized hook manager.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Plugin;

/**
 * Class HookManager
 */
class HookManager {
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin Plugin instance.
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Register all hooks
     *
     * @return void
     */
    public function registerHooks() {
        // Admin hooks
        if (is_admin()) {
            $this->registerAdminHooks();
        }

        // AJAX hooks
        $this->registerAjaxHooks();
        
        // WooCommerce hooks
        $this->registerWooCommerceHooks();
        
        // Webhook hooks
        $this->registerWebhookHooks();
        
        // Cron hooks
        $this->registerCronHooks();
    }

    /**
     * Register admin hooks
     *
     * @return void
     */
    private function registerAdminHooks() {
        // Activation redirect
        add_action('admin_init', function() {
            if (get_transient('wpwps_activation_redirect')) {
                delete_transient('wpwps_activation_redirect');
                if (!isset($_GET['activate-multi'])) {
                    wp_safe_redirect(admin_url('admin.php?page=wpwps-dashboard'));
                    exit;
                }
            }
        });
    }

    /**
     * Register AJAX hooks
     *
     * @return void
     */
    private function registerAjaxHooks() {
        // Test API connection
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        
        // Test ChatGPT connection
        add_action('wp_ajax_wpwps_test_chatgpt_connection', [$this, 'testChatGptConnection']);
        
        // Save settings
        add_action('wp_ajax_wpwps_save_settings', [$this, 'saveSettings']);
        
        // Import products
        add_action('wp_ajax_wpwps_import_products', [$this, 'importProducts']);
        
        // Import orders
        add_action('wp_ajax_wpwps_import_orders', [$this, 'importOrders']);
        
        // Send email
        add_action('wp_ajax_wpwps_send_ticket_response', [$this, 'sendTicketResponse']);
    }

    /**
     * Register WooCommerce hooks
     *
     * @return void
     */
    private function registerWooCommerceHooks() {
        // Add shipping methods
        add_filter('woocommerce_shipping_methods', [$this, 'addShippingMethods']);
        
        // Add order meta box
        add_action('add_meta_boxes', [$this, 'addOrderMetaBox']);
        
        // Save order meta box
        add_action('woocommerce_process_shop_order_meta', [$this, 'saveOrderMetaBox']);
    }

    /**
     * Register webhook hooks
     *
     * @return void
     */
    private function registerWebhookHooks() {
        // Register webhook endpoints
        add_action('rest_api_init', [$this, 'registerWebhookEndpoints']);
    }

    /**
     * Register cron hooks
     *
     * @return void
     */
    private function registerCronHooks() {
        // Stock sync
        add_action('wpwps_stock_sync', [$this, 'syncStockFromPrintify']);
        
        // Process email queue
        add_action('wpwps_process_email_queue', [$this, 'processEmailQueue']);
    }

    /**
     * Test Printify API connection
     *
     * @return void
     */
    public function testPrintifyConnection() {
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access.', 'wp-woocommerce-printify-sync')]);
        }
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $endpoint = isset($_POST['endpoint']) ? esc_url_raw($_POST['endpoint']) : 'https://api.printify.com/v1';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        $printifyApi = $this->plugin->getService('printifyApi');
        $result = $printifyApi->testConnection($api_key, $endpoint);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'shops' => $result,
        ]);
    }
    
    /**
     * Test ChatGPT API connection
     *
     * @return void
     */
    public function testChatGptConnection() {
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access.', 'wp-woocommerce-printify-sync')]);
        }
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get ChatGPT service and test connection
        $gptApi = new \ApolloWeb\WPWooCommercePrintifySync\Api\ChatGptApi($api_key);
        $result = $gptApi->testConnection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'response' => $result,
        ]);
    }
    
    /**
     * Save settings via AJAX
     *
     * @return void
     */
    public function saveSettings() {
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Process and save settings
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        if (empty($settings)) {
            wp_send_json_error(['message' => __('No settings provided.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Process each setting
        foreach ($settings as $key => $value) {
            $key = sanitize_key($key);
            
            // Handle different types of settings
            switch ($key) {
                case 'printify_api_key':
                    // Encrypt API key before saving
                    $encryption = new Encryption();
                    $value = $encryption->encrypt(sanitize_text_field($value));
                    break;
                    
                case 'printify_shop_id':
                    $value = absint($value);
                    break;
                    
                case 'gpt_api_key':
                    // Encrypt API key before saving
                    $encryption = new Encryption();
                    $value = $encryption->encrypt(sanitize_text_field($value));
                    break;
                    
                case 'gpt_temperature':
                    $value = floatval($value);
                    $value = max(0, min(1, $value)); // Between 0 and 1
                    break;
                    
                case 'gpt_monthly_cap':
                    $value = absint($value);
                    break;
                    
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            
            // Save setting
            update_option('wpwps_' . $key, $value);
        }
        
        wp_send_json_success(['message' => __('Settings saved successfully.', 'wp-woocommerce-printify-sync')]);
    }
    
    /**
     * Import products
     *
     * @return void
     */
    public function importProducts() {
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Unauthorized access.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get product importer
        $importer = new \ApolloWeb\WPWooCommercePrintifySync\Sync\ProductImporter($this->plugin);
        
        // Schedule import
        $import_id = $importer->scheduleImport();
        
        if (is_wp_error($import_id)) {
            wp_send_json_error(['message' => $import_id->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Product import scheduled successfully.', 'wp-woocommerce-printify-sync'),
            'import_id' => $import_id
        ]);
    }
    
    /**
     * Register webhook endpoints
     *
     * @return void
     */
    public function registerWebhookEndpoints() {
        register_rest_route('wpwps/v1', '/webhook/product', [
            'methods' => 'POST',
            'callback' => [$this, 'handleProductWebhook'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('wpwps/v1', '/webhook/order', [
            'methods' => 'POST',
            'callback' => [$this, 'handleOrderWebhook'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Handle product webhook
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function handleProductWebhook($request) {
        $data = $request->get_json_params();
        
        // Log webhook data
        $logger = new Logger('webhook-product');
        $logger->log('Webhook received: ' . json_encode($data));
        
        // Verify webhook signature if implemented
        
        // Process product update
        $product_handler = new \ApolloWeb\WPWooCommercePrintifySync\Sync\ProductWebhookHandler();
        $result = $product_handler->process($data);
        
        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Webhook processed successfully', 'wp-woocommerce-printify-sync')
        ], 200);
    }
    
    /**
     * Sync stock from Printify
     *
     * @return void
     */
    public function syncStockFromPrintify() {
        $stock_sync = new \ApolloWeb\WPWooCommercePrintifySync\Sync\StockSync($this->plugin);
        $stock_sync->sync();
    }
}
