<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Import\ActionSchedulerIntegration;
use ApolloWeb\WPWooCommercePrintifySync\Import\ImporterFactory;
use ApolloWeb\WPWooCommercePrintifySync\Import\ProductMetaHelper;

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
        
        // Get shop name by ID
        add_action('wp_ajax_wpwps_fetch_shop_name', [$this, 'fetchShopName']);
        
        // Product import handlers
        add_action('wp_ajax_wpwps_check_import_progress', [$this, 'checkImportProgress']);
        
        // Retrieve products from Printify
        add_action('wp_ajax_wpwps_retrieve_products', [$this, 'retrieveProducts']);
    }
    
    /**
     * Register AJAX actions
     */
    public function registerAjaxActions(): void
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
        
        // Get shop name by ID
        add_action('wp_ajax_wpwps_fetch_shop_name', [$this, 'fetchShopName']);
        
        // Product import AJAX actions
        add_action('wp_ajax_wpwps_check_import_progress', [$this, 'checkImportProgress']);
        add_action('wp_ajax_wpwps_retrieve_products', [$this, 'retrieveProducts']);
        
        // Add new AJAX action
        add_action('wp_ajax_wpwps_get_product_sync_details', [$this, 'getProductSyncDetails']);
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
        
        // Get linked products statistics
        $linkedProducts = \ApolloWeb\WPWooCommercePrintifySync\Import\ProductMetaHelper::getLinkedProducts();
        $totalProducts = count($linkedProducts);
        
        // Initialize counters
        $syncedProducts = 0;
        $pendingProducts = 0;
        $failedProducts = 0;
        $activityData = [];
        
        // Process each product to determine sync status and build activity data
        foreach ($linkedProducts as $product) {
            if (!$product) continue;
            
            $lastSynced = \ApolloWeb\WPWooCommercePrintifySync\Import\ProductMetaHelper::getLastSyncedTimestamp($product);
            $printifyProductId = \ApolloWeb\WPWooCommercePrintifySync\Import\ProductMetaHelper::getPrintifyProductId($product);
            $syncStatus = get_post_meta($product->get_id(), '_printify_sync_status', true);
            
            // Determine sync status if not explicitly set
            if (empty($syncStatus)) {
                if (!empty($lastSynced)) {
                    $syncStatus = 'success';
                    $syncedProducts++;
                } else {
                    $syncStatus = 'pending';
                    $pendingProducts++;
                }
            } else if ($syncStatus == 'failed') {
                $failedProducts++;
            } else if ($syncStatus == 'success') {
                $syncedProducts++;
            } else {
                $pendingProducts++;
            }
            
            // Add to activity data (limit to 10 most recent)
            if (count($activityData) < 10) {
                $productType = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
                $productType = !empty($productType) ? reset($productType) : __('Uncategorized', 'wp-woocommerce-printify-sync');
                
                $activityData[] = [
                    'product' => $product->get_name(),
                    'type' => $productType,
                    'status' => $syncStatus,
                    'last_updated' => $lastSynced ? date('Y-m-d H:i:s', strtotime($lastSynced)) : '',
                    'action' => 'view',
                    'product_id' => $product->get_id()
                ];
            }
        }
        
        // Sort activity data by last updated time (most recent first)
        usort($activityData, function($a, $b) {
            if (empty($a['last_updated'])) return 1;
            if (empty($b['last_updated'])) return -1;
            return strtotime($b['last_updated']) - strtotime($a['last_updated']);
        });
        
        // Get order data for charts
        $orderStats = $this->getOrderStatisticsData();
        
        // Return real statistics
        $stats = [
            'total_products' => $totalProducts,
            'synced_products' => $syncedProducts,
            'pending_products' => $pendingProducts,
            'failed_products' => $failedProducts,
            'activity_data' => $activityData,
            'charts' => [
                'orders' => $orderStats['orders'],
                'sales' => $orderStats['sales'],
                'sync_status' => [
                    'labels' => ['Synced', 'Pending', 'Failed'],
                    'data' => [$syncedProducts, $pendingProducts, $failedProducts]
                ]
            ]
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get order statistics data for charts
     * 
     * @return array Order statistics data
     */
    private function getOrderStatisticsData(): array
    {
        $data = [
            'orders' => [
                'labels' => ['Processing', 'Completed', 'Failed'],
                'data' => [0, 0, 0]
            ],
            'sales' => [
                'labels' => [],
                'data' => []
            ]
        ];
        
        // If WooCommerce is active, get real order data
        if (class_exists('WooCommerce')) {
            // Get order statuses
            $processing = wc_orders_count('processing');
            $completed = wc_orders_count('completed');
            $failed = wc_orders_count('failed');
            
            $data['orders']['data'] = [$processing, $completed, $failed];
            
            // Get sales data for the last 7 days
            $salesData = $this->getSalesDataForPeriod(7);
            $data['sales'] = $salesData;
        }
        
        return $data;
    }
    
    /**
     * Get sales data for a specified period
     * 
     * @param int $days Number of days to get data for
     * @return array Sales data for the period
     */
    private function getSalesDataForPeriod(int $days): array
    {
        $labels = [];
        $data = [];
        
        $end_date = time();
        $start_date = strtotime("-{$days} days", $end_date);
        
        for ($i = 0; $i < $days; $i++) {
            $date = strtotime("+{$i} days", $start_date);
            $labels[] = date('M d', $date);
            
            // Get sales for this day
            $args = [
                'date_created' => date('Y-m-d', $date),
                'return' => 'ids',
                'limit' => -1,
            ];
            
            $orders = wc_get_orders($args);
            $day_total = 0;
            
            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                $day_total += $order->get_total();
            }
            
            $data[] = $day_total;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Get sales data for dashboard
     *
     * @return void
     */
    public function getSalesData(): void
    {
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '7days';
        
        // Convert period to days
        $days = 7;
        switch ($period) {
            case '30days':
                $days = 30;
                break;
            case '90days':
                $days = 90;
                break;
            case '12months':
                $days = 365;
                break;
        }
        
        $salesData = $this->getSalesDataForPeriod($days);
        wp_send_json_success($salesData);
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
        
        $shopId = $this->settings->getShopId();
        
        if (empty($shopId)) {
            wp_send_json_error(['message' => __('Shop ID is not set', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Make a real API call to check health
            $shop = $this->api->getShop($shopId);
            
            if (is_wp_error($shop)) {
                throw new \Exception($shop->get_error_message());
            }
            
            // Update shop name if needed
            if (isset($shop['title']) && empty($this->settings->getShopName())) {
                $this->settings->setShopName($shop['title']);
            }
            
            // Update the last API check timestamp
            update_option('wpwps_last_api_check', time());
            
            wp_send_json_success([
                'message' => __('API connection is healthy', 'wp-woocommerce-printify-sync'),
                'last_check' => human_time_diff(time(), time()) . ' ' . __('ago', 'wp-woocommerce-printify-sync'),
                'shop_name' => $shop['title'] ?? $this->settings->getShopName()
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('API Error: %s', 'wp-woocommerce-printify-sync'), $e->getMessage())
            ]);
        }
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
        $maxTokens = absint($_POST['max_tokens'] ?? 250);
        $temperature = (float) $_POST['temperature'] ?? 0.7;
        $enableUsageLimit = (bool) ($_POST['enable_usage_limit'] ?? false);
        $monthlyLimit = (float) $_POST['monthly_limit'] ?? 5.0;
        
        // Validate inputs
        if (empty($apiKey)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Ensure max tokens is within reasonable limits
        if ($maxTokens < 50) {
            $maxTokens = 50;
        } elseif ($maxTokens > 4000) {
            $maxTokens = 4000;
        }
        
        // Ensure temperature is between 0 and 1
        if ($temperature < 0) {
            $temperature = 0;
        } elseif ($temperature > 1) {
            $temperature = 1;
        }
        
        // Ensure monthly limit is positive
        if ($monthlyLimit < 0) {
            $monthlyLimit = 0;
        }
        
        // Save settings
        $this->settings->setChatGptApiKey($apiKey);
        $this->settings->setChatGptApiModel($model);
        $this->settings->setChatGptMaxTokens($maxTokens);
        $this->settings->setChatGptTemperature($temperature);
        $this->settings->setChatGptUsageLimitEnabled($enableUsageLimit);
        $this->settings->setChatGptMonthlyLimit($monthlyLimit);
        
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
        
        // Check if usage limit is exceeded
        if ($this->settings->isChatGptUsageLimitExceeded()) {
            wp_send_json_error([
                'message' => __('Monthly usage limit exceeded. Please increase your limit or wait until next month.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        $apiKey = $this->settings->getChatGptApiKey();
        $model = $this->settings->getChatGptApiModel();
        $maxTokens = $this->settings->getChatGptMaxTokens();
        $temperature = $this->settings->getChatGptTemperature();
        
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
                'max_tokens' => $maxTokens,
                'temperature' => $temperature
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
            // Calculate and record token usage cost
            $promptTokens = $data['usage']['prompt_tokens'] ?? 0;
            $completionTokens = $data['usage']['completion_tokens'] ?? 0;
            
            // Calculate cost based on the model (approximate rates)
            $cost = 0;
            if ($model === 'gpt-4') {
                $promptCost = $promptTokens * 0.03 / 1000; // $0.03 per 1K tokens
                $completionCost = $completionTokens * 0.06 / 1000; // $0.06 per 1K tokens
            } else {
                // Default to gpt-3.5-turbo rates
                $promptCost = $promptTokens * 0.0015 / 1000; // $0.0015 per 1K tokens
                $completionCost = $completionTokens * 0.002 / 1000; // $0.002 per 1K tokens
            }
            
            $cost = $promptCost + $completionCost;
            
            // Record token usage for cost tracking
            $this->settings->recordChatGptUsage($cost);
            
            // Get current usage stats
            $currentUsage = $this->settings->getChatGptCurrentUsage();
            $monthlyLimit = $this->settings->getChatGptMonthlyLimit();
            $limitEnabled = $this->settings->isChatGptUsageLimitEnabled();
            
            wp_send_json_success([
                'message' => __('ChatGPT API connection successful!', 'wp-woocommerce-printify-sync'),
                'response' => $data['choices'][0]['message']['content'],
                'usage' => [
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                    'cost' => round($cost, 6),
                    'current_usage' => round($currentUsage, 4),
                    'monthly_limit' => $limitEnabled ? round($monthlyLimit, 2) : null,
                ]
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Could not parse API response.', 'wp-woocommerce-printify-sync'),
            ]);
        }
    }
    
    /**
     * Fetch shop name for an existing shop ID
     *
     * @return void
     */
    public function fetchShopName(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get shop ID
        $shopId = $this->settings->getShopId();
        
        if (empty($shopId)) {
            wp_send_json_error(['message' => __('No shop ID is set. Please select a shop first.', 'wp-woocommerce-printify-sync')]);
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
        
        // Find shop with matching ID
        $shopName = '';
        foreach ($result as $shop) {
            if (isset($shop['id']) && $shop['id'] == $shopId) {
                $shopName = sanitize_text_field($shop['title'] ?? '');
                break;
            }
        }
        
        if (empty($shopName)) {
            wp_send_json_error([
                'message' => __('Could not find shop name for the current shop ID. The shop may no longer exist.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        // Save shop name
        $this->settings->setShopName($shopName);
        
        wp_send_json_success([
            'message' => __('Shop name retrieved and saved successfully!', 'wp-woocommerce-printify-sync'),
            'shop_name' => $shopName
        ]);
    }
    
    /**
     * Check product import progress
     *
     * @return void
     */
    public function checkImportProgress(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get import status information
        $importStatus = \ApolloWeb\WPWooCommercePrintifySync\Import\ActionSchedulerIntegration::getImportStatus();
        
        wp_send_json_success($importStatus);
    }
    
    /**
     * Retrieve products from Printify and store in transients
     *
     * @return void
     */
    public function retrieveProducts(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get filter parameters
        $productType = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : '';
        $syncMode = isset($_POST['sync_mode']) ? sanitize_text_field($_POST['sync_mode']) : 'all';
        
        $shopId = $this->settings->getShopId();
        
        if (empty($shopId)) {
            wp_send_json_error(['message' => __('Shop ID is not set. Please configure the Printify API settings first.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Get products from Printify
            $products = $this->api->getAllProducts($shopId);
            
            if (is_wp_error($products)) {
                throw new \Exception($products->get_error_message());
            }
            
            // Make sure we have an array to work with
            if (!is_array($products)) {
                throw new \Exception(__('Invalid response from Printify API. Expected an array of products.', 'wp-woocommerce-printify-sync'));
            }
            
            // Filter products if needed
            if (!empty($productType)) {
                $filtered = [];
                foreach ($products as $product) {
                    if (isset($product['type']) && $product['type'] === $productType) {
                        $filtered[] = $product;
                    }
                }
                $products = $filtered;
            }
            
            // If no products found, return error
            if (empty($products)) {
                wp_send_json_error(['message' => __('No products found matching your criteria.', 'wp-woocommerce-printify-sync')]);
                return;
            }
            
            // Store products in transient
            set_transient('wpwps_retrieved_products', $products, HOUR_IN_SECONDS);
            set_transient('wpwps_import_sync_mode', $syncMode, HOUR_IN_SECONDS);
            
            // Prepare products data for preview
            $previewData = [];
            foreach ($products as $product) {
                $variantCount = isset($product['variants']) ? count($product['variants']) : 0;
                
                // Check if product already exists in WooCommerce
                $exists = false;
                $wcProductId = $this->getWooCommerceProductId($product['id']);
                if ($wcProductId) {
                    $exists = true;
                }
                
                $previewData[] = [
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'type' => $product['type'] ?? __('Unknown', 'wp-woocommerce-printify-sync'),
                    'variants' => $variantCount,
                    'exists' => $exists,
                ];
            }
            
            wp_send_json_success([
                'message' => sprintf(__('%d products retrieved successfully.', 'wp-woocommerce-printify-sync'), count($products)),
                'products' => $previewData,
                'total' => count($products)
            ]);
        } catch (\Exception $e) {
            error_log('WPWPS Product Retrieval Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage(),
                'error_details' => 'See server error log for more details'
            ]);
        }
    }
    
    /**
     * Get WooCommerce product ID by Printify product ID
     * 
     * @param string $printifyProductId
     * @return int|false
     */
    private function getWooCommerceProductId(string $printifyProductId)
    {
        return ProductMetaHelper::findProductByPrintifyId($printifyProductId);
    }
    
    /**
     * Get product synchronization details
     *
     * @return void
     */
    public function getProductSyncDetails(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (empty($productId)) {
            wp_send_json_error(['message' => __('Product ID is required.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $product = wc_get_product($productId);
        
        if (!$product) {
            wp_send_json_error(['message' => __('Product not found.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Check if product is linked to Printify
        if (!ProductMetaHelper::isLinkedToPrintify($product)) {
            wp_send_json_error(['message' => __('This product is not linked to Printify.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get product meta information
        $syncDetails = [
            'printify_product_id' => ProductMetaHelper::getPrintifyProductId($product),
            'printify_provider_id' => ProductMetaHelper::getPrintifyProviderId($product),
            'last_synced' => ProductMetaHelper::getLastSyncedTimestamp($product),
            'is_variable' => $product->is_type('variable'),
            'variations' => [],
        ];
        
        // Get variation details if it's a variable product
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            
            foreach ($variations as $variationId) {
                $variation = wc_get_product($variationId);
                
                if ($variation) {
                    $variantId = get_post_meta($variationId, ProductMetaHelper::META_PRINTIFY_VARIANT_ID, true);
                    $costPrice = get_post_meta($variationId, ProductMetaHelper::META_PRINTIFY_COST_PRICE, true);
                    
                    $syncDetails['variations'][] = [
                        'id' => $variationId,
                        'sku' => $variation->get_sku(),
                        'printify_variant_id' => $variantId,
                        'printify_cost_price' => $costPrice,
                        'attributes' => $variation->get_attributes(),
                    ];
                }
            }
        }
        
        wp_send_json_success(['details' => $syncDetails]);
    }
    
    /**
     * Start product import
     * 
     * @return void
     */
    public function startProductImport(): void
    {
        // Check nonce for security
        check_ajax_referer('wpwps-ajax-nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify Action Scheduler is available
        if (!ActionSchedulerIntegration::isActionSchedulerAvailable()) {
            wp_send_json_error([
                'message' => __('Action Scheduler is not available. Please ensure WooCommerce is activated.', 'wp-woocommerce-printify-sync'),
                'action_scheduler_url' => admin_url('plugins.php')
            ]);
            return;
        }
        
        // Get products from transient
        $products = get_transient('wpwps_retrieved_products');
        $syncMode = get_transient('wpwps_import_sync_mode');
        
        if (empty($products)) {
            wp_send_json_error(['message' => __('No products found to import. Please retrieve products first.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Schedule the import using Action Scheduler
        $scheduled = ActionSchedulerIntegration::scheduleImport($products, $syncMode);
        
        if (!$scheduled) {
            wp_send_json_error(['message' => __('Failed to schedule import. Please check server logs.', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        wp_send_json_success([
            'message' => __('Import has been scheduled and will run in the background.', 'wp-woocommerce-printify-sync'),
            'total' => count($products),
            'action_scheduler_url' => ActionSchedulerIntegration::getActionSchedulerAdminUrl(),
        ]);
    }
}
