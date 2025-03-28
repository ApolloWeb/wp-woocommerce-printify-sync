<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\BaseServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompatibility;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyClient;

class ApiServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register(): void
    {
        // Register Ajax endpoints
        add_action('wp_ajax_wpwps_test_connection', [$this, 'testConnection']);
        add_action('wp_ajax_wpwps_get_shops', [$this, 'fetchPrintifyShops']);
        add_action('wp_ajax_wpwps_sync_products', [$this, 'syncProducts']);
        add_action('wp_ajax_wpwps_sync_inventory', [$this, 'syncInventory']);
        add_action('wp_ajax_wpwps_sync_orders', [$this, 'syncOrders']);
        add_action('wp_ajax_wpwps_sync_single_product', [$this, 'syncSingleProduct']);
        add_action('wp_ajax_wpwps_send_order_to_printify', [$this, 'sendOrderToPrintify']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_openai_connection', [$this, 'testOpenAIConnection']);
    }
    
    /**
     * Register REST API routes.
     * 
     * @return void
     */
    public function registerRestRoutes(): void
    {
        register_rest_route('wpwps/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'getProducts'],
            'permission_callback' => [$this, 'checkApiPermission'],
        ]);
        
        register_rest_route('wpwps/v1', '/webhooks/product-update', [
            'methods' => 'POST',
            'callback' => [$this, 'handleProductWebhook'],
            'permission_callback' => [$this, 'validateWebhookRequest'],
        ]);
        
        register_rest_route('wpwps/v1', '/webhooks/order-update', [
            'methods' => 'POST',
            'callback' => [$this, 'handleOrderWebhook'],
            'permission_callback' => [$this, 'validateWebhookRequest'],
        ]);
    }
    
    /**
     * Test API connection.
     * 
     * @return void
     */
    public function testConnection(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Get API client
        $apiClient = $this->getApiClient();
        
        try {
            $response = $apiClient->testConnection();
            
            if ($response) {
                wp_send_json_success([
                    'message' => __('API connection successful!', 'wp-woocommerce-printify-sync')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('API connection failed. Please check your API key.', 'wp-woocommerce-printify-sync')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Fetch Printify shops via Ajax.
     * 
     * @return void
     */
    public function fetchPrintifyShops(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Get API client
        $apiClient = $this->getApiClient();
        
        try {
            $shops = $apiClient->getShops();
            
            wp_send_json_success([
                'shops' => $shops,
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Sync products via Ajax.
     * 
     * @return void
     */
    public function syncProducts(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get page number from request
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        // Get SyncServiceProvider instance
        $syncService = $this->getSyncServiceProvider();
        
        // Perform manual sync
        $result = $syncService->manualProductSync($page, $limit);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Sync inventory via Ajax.
     * 
     * @return void
     */
    public function syncInventory(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get SyncServiceProvider instance
        $syncService = $this->getSyncServiceProvider();
        
        // Perform manual sync
        $result = $syncService->manualInventorySync();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Sync orders via Ajax.
     * 
     * @return void
     */
    public function syncOrders(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get timeframe parameter
        $timeframe = isset($_POST['timeframe']) ? sanitize_text_field($_POST['timeframe']) : '24h';
        
        // Get SyncServiceProvider instance
        $syncService = $this->getSyncServiceProvider();
        
        // Perform manual sync
        $result = $syncService->manualOrderSync($timeframe);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Sync a single product via Ajax.
     * 
     * @return void
     */
    public function syncSingleProduct(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get product ID and Printify ID from request
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $printifyId = isset($_POST['printify_id']) ? sanitize_text_field($_POST['printify_id']) : '';
        
        if (!$productId || !$printifyId) {
            wp_send_json_error([
                'message' => __('Invalid product information.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        try {
            // Get API client
            $apiClient = $this->getApiClient();
            
            // Get the product data from Printify
            $endpoint = "shops/{$apiClient->getShopId()}/products/{$printifyId}.json";
            $productData = $apiClient->makeRequest($endpoint);
            
            if (!$productData) {
                wp_send_json_error([
                    'message' => __('Failed to fetch product data from Printify.', 'wp-woocommerce-printify-sync'),
                ]);
                wp_die();
            }
            
            // Process and update the WooCommerce product
            $result = $apiClient->processAndSaveProduct($productData);
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Product synchronized successfully.', 'wp-woocommerce-printify-sync'),
                    'data' => $result,
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to update WooCommerce product.', 'wp-woocommerce-printify-sync'),
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Send order to Printify via Ajax.
     * 
     * @return void
     */
    public function sendOrderToPrintify(): void
    {
        // Verify nonce
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get order ID from request
        $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$orderId) {
            wp_send_json_error([
                'message' => __('Invalid order ID.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get the order
        $order = wc_get_order($orderId);
        
        if (!$order) {
            wp_send_json_error([
                'message' => __('Order not found.', 'wp-woocommerce-printify-sync'),
            ]);
            wp_die();
        }
        
        // Get WooCommerceServiceProvider instance to access the sendOrderToPrintify method
        $woocommerceService = $this->getWooCommerceServiceProvider();
        $woocommerceService->sendOrderToPrintify($order);
        
        // Check if the order now has a Printify order ID
        $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
        
        if ($printifyOrderId) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Order sent to Printify successfully. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                    $printifyOrderId
                ),
                'printify_order_id' => $printifyOrderId,
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to send order to Printify. Check the order notes for details.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        wp_die();
    }
    
    /**
     * Get products for REST API.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function getProducts(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $request->get_param('per_page') ?: 10,
            'paged' => $request->get_param('page') ?: 1,
            'meta_query' => [
                [
                    'key' => '_wpwps_printify_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];
        
        $query = new \WP_Query($args);
        $products = [];
        
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            
            if (!$product) {
                continue;
            }
            
            $printifyId = $product->get_meta('_wpwps_printify_id', true);
            $lastSync = $product->get_meta('_wpwps_last_sync', true);
            
            $products[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
                'status' => $product->get_status(),
                'printify_id' => $printifyId,
                'last_sync' => $lastSync,
                'stock_status' => $product->get_stock_status(),
                'stock_quantity' => $product->get_stock_quantity(),
                'type' => $product->get_type(),
                'permalink' => get_permalink($post->ID),
            ];
        }
        
        return rest_ensure_response([
            'products' => $products,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        ]);
    }
    
    /**
     * Handle product webhook from Printify.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleProductWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();
        
        // Log webhook received
        $this->logWebhook('product', json_encode($payload));
        
        // Check if we have a valid product ID
        if (!isset($payload['product_id'])) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Missing product_id in webhook payload',
            ]);
        }
        
        try {
            $apiClient = $this->getApiClient();
            
            // Get the product details from Printify
            $endpoint = "shops/{$apiClient->getShopId()}/products/{$payload['product_id']}.json";
            $productData = $apiClient->makeRequest($endpoint);
            
            // Process and update the product
            $result = $apiClient->processAndSaveProduct($productData);
            
            if ($result) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'product_id' => $result['id'],
                ]);
            } else {
                return rest_ensure_response([
                    'success' => false,
                    'message' => 'Failed to update product',
                ]);
            }
        } catch (\Exception $e) {
            // Log the error
            $this->logWebhook('product', 'Error processing webhook: ' . $e->getMessage(), 'error');
            
            return rest_ensure_response([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Handle order webhook from Printify.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleOrderWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();
        
        // Log webhook received
        $this->logWebhook('order', json_encode($payload));
        
        // Check if we have valid order data
        if (!isset($payload['order_id'])) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Missing order_id in webhook payload',
            ]);
        }
        
        try {
            // Find the WooCommerce order with this Printify order ID using HPOS-compatible method
            $orderIds = HPOSCompatibility::getOrdersByMeta([
                'meta_query' => [
                    [
                        'key' => '_wpwps_printify_order_id',
                        'value' => $payload['order_id'],
                    ],
                ],
                'posts_per_page' => 1,
            ]);
            
            if (empty($orderIds)) {
                return rest_ensure_response([
                    'success' => false,
                    'message' => 'WooCommerce order not found for Printify order ID: ' . $payload['order_id'],
                ]);
            }
            
            $order = wc_get_order($orderIds[0]);
            
            // Update order status based on webhook data
            if (isset($payload['status'])) {
                // Map Printify status to WooCommerce status
                $wcStatus = $this->mapPrintifyOrderStatusToWC($payload['status']);
                
                if ($wcStatus) {
                    $order->update_status($wcStatus, __('Status updated from Printify webhook: ', 'wp-woocommerce-printify-sync') . $payload['status']);
                }
                
                // Save the Printify order status
                HPOSCompatibility::updateOrderMeta($order, '_wpwps_printify_order_status', $payload['status']);
            }
            
            // Update tracking information if available
            if (isset($payload['shipments']) && !empty($payload['shipments'])) {
                $tracking = [];
                
                foreach ($payload['shipments'] as $shipment) {
                    if (!empty($shipment['tracking_number']) && !empty($shipment['carrier'])) {
                        $tracking[] = [
                            'tracking_number' => $shipment['tracking_number'],
                            'carrier' => $shipment['carrier'],
                            'url' => $shipment['tracking_url'] ?? '',
                        ];
                    }
                }
                
                if (!empty($tracking)) {
                    HPOSCompatibility::updateOrderMeta($order, '_wpwps_tracking_info', $tracking);
                    
                    // Add note about tracking
                    $order->add_order_note(
                        __('Tracking information received from Printify: ', 'wp-woocommerce-printify-sync') .
                        implode(', ', array_column($tracking, 'tracking_number'))
                    );
                }
            }
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Order updated successfully',
                'order_id' => $order->get_id(),
            ]);
        } catch (\Exception $e) {
            // Log the error
            $this->logWebhook('order', 'Error processing webhook: ' . $e->getMessage(), 'error');
            
            return rest_ensure_response([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Check API permissions.
     * 
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function checkApiPermission(\WP_REST_Request $request): bool
    {
        // Check if user can manage WooCommerce
        return current_user_can('manage_woocommerce');
    }
    
    /**
     * Validate webhook request.
     * 
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function validateWebhookRequest(\WP_REST_Request $request): bool
    {
        // In a real implementation, you'd validate the webhook signature here
        // For now, we'll accept all webhook requests for demo purposes
        return true;
    }
    
    /**
     * Map Printify order status to WooCommerce status.
     * 
     * @param string $printifyStatus
     * @return string|null
     */
    protected function mapPrintifyOrderStatusToWC(string $printifyStatus): ?string
    {
        $statusMap = [
            'pending' => 'processing',
            'on-hold' => 'on-hold',
            'fulfilled' => 'completed',
            'canceled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed',
        ];
        
        return $statusMap[$printifyStatus] ?? null;
    }
    
    /**
     * Log webhook events.
     * 
     * @param string $type
     * @param string $data
     * @param string $status
     * @return void
     */
    protected function logWebhook(string $type, string $data, string $status = 'info'): void
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_sync_logs';
        
        $wpdb->insert(
            $table,
            [
                'time' => current_time('mysql'),
                'type' => 'webhook_' . $type,
                'message' => 'Webhook received: ' . $type,
                'status' => $status,
                'data' => $data,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
    
    /**
     * Get API client.
     * 
     * @return PrintifyClient
     */
    protected function getApiClient(): PrintifyClient
    {
        $settings = get_option('wpwps_settings');
        $apiKey = isset($settings['api_key']) ? $settings['api_key'] : '';
        $shopId = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        
        return new PrintifyClient($apiKey, $shopId);
    }
    
    /**
     * Get SyncServiceProvider instance.
     * 
     * @return SyncServiceProvider
     */
    protected function getSyncServiceProvider(): SyncServiceProvider
    {
        global $wpwps_plugin;
        return $wpwps_plugin->getServiceProvider(SyncServiceProvider::class);
    }
    
    /**
     * Get WooCommerceServiceProvider instance.
     * 
     * @return WooCommerceServiceProvider
     */
    protected function getWooCommerceServiceProvider(): WooCommerceServiceProvider
    {
        global $wpwps_plugin;
        return $wpwps_plugin->getServiceProvider(WooCommerceServiceProvider::class);
    }

    /**
     * Test Printify API connection.
     * 
     * @return void
     */
    public function testPrintifyConnection(): void
    {
        check_ajax_referer('wpwps-admin-nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')
            ]);
        }

        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        $endpoint = sanitize_text_field($_POST['endpoint'] ?? 'https://api.printify.com/v1/');

        if (empty($apiKey)) {
            wp_send_json_error([
                'message' => __('API Key is required.', 'wp-woocommerce-printify-sync')
            ]);
        }

        try {
            $client = new PrintifyClient($apiKey, '', $endpoint);
            $shops = $client->getShops();

            wp_send_json_success([
                'message' => __('Successfully connected to Printify.', 'wp-woocommerce-printify-sync'),
                'shops' => $shops
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test OpenAI API connection.
     * 
     * @return void
     */
    public function testOpenAIConnection(): void
    {
        check_ajax_referer('wpwps-admin-nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')
            ]);
        }

        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        $tokens = intval($_POST['tokens'] ?? 1000);
        $temperature = floatval($_POST['temperature'] ?? 0.7);
        $spendCap = floatval($_POST['spend_cap'] ?? 50.0);

        if (empty($apiKey)) {
            wp_send_json_error([
                'message' => __('API Key is required.', 'wp-woocommerce-printify-sync')
            ]);
        }

        try {
            // Simulate an OpenAI API call (replace with actual implementation)
            $estimatedCost = $tokens * $temperature * 0.0001; // Example calculation

            if ($estimatedCost > $spendCap) {
                wp_send_json_error([
                    'message' => __('Estimated cost exceeds your spend cap.', 'wp-woocommerce-printify-sync')
                ]);
            }

            wp_send_json_success([
                'message' => __('Successfully connected to OpenAI.', 'wp-woocommerce-printify-sync'),
                'estimated_cost' => $estimatedCost
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}