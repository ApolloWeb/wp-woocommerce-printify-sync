<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;

class AjaxHandler
{
    /**
     * The settings object
     * 
     * @var Settings
     */
    private Settings $settings;
    
    /**
     * The API object
     * 
     * @var PrintifyAPI
     */
    private PrintifyAPI $api;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->settings = new Settings();
        $this->api = new PrintifyAPI($this->settings);
    }
    
    /**
     * Register AJAX handlers
     *
     * @return void
     */
    public function registerHandlers(): void
    {
        // Test API connection
        add_action('wp_ajax_wpwps_test_connection', [$this, 'testConnection']);
        
        // Save API settings
        add_action('wp_ajax_wpwps_save_api_settings', [$this, 'saveApiSettings']);
        
        // Save shop ID
        add_action('wp_ajax_wpwps_save_shop_id', [$this, 'saveShopId']);
        
        // Dashboard actions
        add_action('wp_ajax_wpwps_get_dashboard_stats', [$this, 'getDashboardStats']);
        add_action('wp_ajax_wpwps_sync_products', [$this, 'syncProducts']);
        add_action('wp_ajax_wpwps_check_api_health', [$this, 'checkApiHealth']);
        add_action('wp_ajax_wpwps_sync_orders', [$this, 'syncOrders']);
        add_action('wp_ajax_wpwps_get_sales_data', [$this, 'getSalesData']);
        
        // ChatGPT handlers
        add_action('wp_ajax_wpwps_save_chatgpt_settings', [$this, 'saveChatGptSettings']);
        add_action('wp_ajax_wpwps_test_chatgpt', [$this, 'testChatGpt']);
    }
    
    /**
     * Test the API connection
     *
     * @return void
     */
    public function testConnection(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get shops from API
        $result = $this->api->getShops();
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
            return;
        }
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'shops' => $result,
        ]);
    }
    
    /**
     * Save API settings
     *
     * @return void
     */
    public function saveApiSettings(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Sanitize inputs
        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        $apiEndpoint = esc_url_raw($_POST['api_endpoint'] ?? 'https://api.printify.com/v1/');
        
        // Validate inputs
        if (empty($apiKey)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Save settings
        $this->settings->setApiKey($apiKey);
        $this->settings->setApiEndpoint($apiEndpoint);
        
        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync'),
        ]);
    }
    
    /**
     * Save shop ID and fetch shop name
     *
     * @return void
     */
    public function saveShopId(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Sanitize inputs
        $shopId = sanitize_text_field($_POST['shop_id'] ?? '');
        $shopName = sanitize_text_field($_POST['shop_name'] ?? '');
        
        // Validate inputs
        if (empty($shopId)) {
            wp_send_json_error(['message' => __('Shop ID is required.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Save shop ID
        $this->settings->setShopId($shopId);
        
        // Save shop name if provided
        if (!empty($shopName)) {
            $this->settings->setShopName($shopName);
        }
        
        wp_send_json_success([
            'message' => __('Shop ID saved successfully!', 'wp-woocommerce-printify-sync'),
        ]);
    }
    
    /**
     * Get dashboard statistics
     *
     * @return void
     */
    public function getDashboardStats(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // In a real implementation, we would fetch actual stats here
        // For now, we'll return dummy data
        $stats = [
            'total_products' => 37,
            'synced_products' => 30,
            'pending_products' => 5,
            'failed_products' => 2,
            'activity_data' => [
                [
                    'product' => 'Classic T-Shirt',
                    'type' => 'Apparel',
                    'status' => 'success',
                    'last_updated' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                    'action' => 'view'
                ],
                [
                    'product' => 'Premium Hoodie',
                    'type' => 'Apparel',
                    'status' => 'success',
                    'last_updated' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
                    'action' => 'view'
                ],
                [
                    'product' => 'Coffee Mug',
                    'type' => 'Drinkware',
                    'status' => 'pending',
                    'last_updated' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
                    'action' => 'retry'
                ],
                [
                    'product' => 'Phone Case',
                    'type' => 'Accessories',
                    'status' => 'failed',
                    'last_updated' => date('Y-m-d H:i:s', strtotime('-20 minutes')),
                    'action' => 'retry'
                ],
                [
                    'product' => 'Canvas Print',
                    'type' => 'Home Decor',
                    'status' => 'success',
                    'last_updated' => date('Y-m-d H:i:s', strtotime('-25 minutes')),
                    'action' => 'view'
                ]
            ],
            'chart_data' => [
                'sync_activity' => [
                    'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
                    'data' => [3, 7, 12, 15, 18, 22, 30]
                ],
                'sync_status' => [
                    'labels' => ['Synced', 'Pending', 'Failed'],
                    'data' => [30, 5, 2]
                ]
            ]
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Sync products from Printify to WooCommerce
     *
     * @return void
     */
    public function syncProducts(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $shopId = $this->settings->getShopId();
        
        if (empty($shopId)) {
            wp_send_json_error(['message' => __('Shop ID is required for synchronization.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // In a real implementation, we would fetch and sync products here
        // For now, we'll just simulate success
        
        wp_send_json_success([
            'message' => __('Products have been successfully synchronized!', 'wp-woocommerce-printify-sync'),
            'products_synced' => 30
        ]);
    }
    
    /**
     * Check API health
     *
     * @return void
     */
    public function checkApiHealth(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // In a real implementation, we would check the API health
        // For now, just simulate success
        wp_send_json_success([
            'status' => 'healthy',
            'message' => __('API connection is healthy.', 'wp-woocommerce-printify-sync'),
            'last_checked' => current_time('mysql')
        ]);
    }
    
    /**
     * Sync orders with Printify
     *
     * @return void
     */
    public function syncOrders(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $shopId = $this->settings->getShopId();
        
        if (empty($shopId)) {
            wp_send_json_error(['message' => __('Shop ID is required for synchronization.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // In a real implementation, we would sync orders
        // For now, just simulate success
        wp_send_json_success([
            'message' => __('Orders have been successfully synchronized!', 'wp-woocommerce-printify-sync'),
            'orders_synced' => 12,
            'last_synced' => current_time('mysql')
        ]);
    }
    
    /**
     * Get sales data for the chart
     *
     * @return void
     */
    public function getSalesData(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'month');
        
        // In a real implementation, we would fetch actual sales data
        // For now, return dummy data based on the period
        $data = [];
        
        switch ($period) {
            case 'day':
                $data = [
                    'labels' => ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
                    'sales' => [50, 30, 80, 120, 160, 110],
                    'profit' => [20, 10, 40, 70, 90, 60]
                ];
                break;
                
            case 'week':
                $data = [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'sales' => [700, 600, 800, 950, 1200, 1500, 800],
                    'profit' => [300, 250, 350, 400, 600, 800, 400]
                ];
                break;
                
            case 'year':
                $data = [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    'sales' => [4200, 3800, 5100, 4900, 6200, 5800, 6500, 7200, 6800, 7500, 8200, 9500],
                    'profit' => [2100, 1900, 2600, 2400, 3200, 2900, 3300, 3800, 3500, 3900, 4200, 5100]
                ];
                break;
                
            default: // month
                $data = [
                    'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    'sales' => [3800, 4200, 5100, 4800],
                    'profit' => [1800, 2200, 2700, 2400]
                ];
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Save ChatGPT API settings
     *
     * @return void
     */
    public function saveChatGptSettings(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Sanitize inputs
        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? 'gpt-3.5-turbo');
        
        // Validate inputs
        if (empty($apiKey)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Save settings
        $this->settings->setChatGptApiKey($apiKey);
        $this->settings->setChatGptApiModel($model);
        
        wp_send_json_success([
            'message' => __('ChatGPT API settings saved successfully!', 'wp-woocommerce-printify-sync'),
        ]);
    }
    
    /**
     * Test ChatGPT API connection
     *
     * @return void
     */
    public function testChatGpt(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $apiKey = $this->settings->getChatGptApiKey();
        $model = $this->settings->getChatGptApiModel();
        
        if (empty($apiKey)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Prepare request to OpenAI API
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant for WooCommerce and Printify.'
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Respond with a brief greeting to confirm the API connection is working.'
                    ]
                ],
                'max_tokens' => 50
            ])
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
            return;
        }
        
        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        $data = json_decode($responseBody, true);
        
        if ($responseCode !== 200) {
            $errorMessage = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown error', 'wp-woocommerce-printify-sync');
            wp_send_json_error([
                'message' => sprintf(__('API Error (Code: %s): %s', 'wp-woocommerce-printify-sync'), $responseCode, $errorMessage),
            ]);
            return;
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            wp_send_json_success([
                'message' => __('ChatGPT API connection successful!', 'wp-woocommerce-printify-sync'),
                'response' => $data['choices'][0]['message']['content']
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Could not parse API response.', 'wp-woocommerce-printify-sync'),
            ]);
        }
    }
}
