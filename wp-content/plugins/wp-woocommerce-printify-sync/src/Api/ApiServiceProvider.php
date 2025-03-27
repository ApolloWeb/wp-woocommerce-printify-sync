<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Core\BaseServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompatibility;

/**
 * API Service Provider
 */
class ApiServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Register API endpoints
        add_action('rest_api_init', [$this, 'registerApiEndpoints']);
        
        // Register webhook handlers
        add_action('woocommerce_api_wp_woocommerce_printify_sync', [$this, 'handleWebhook']);
    }

    /**
     * Register API endpoints
     *
     * @return void
     */
    public function registerApiEndpoints(): void
    {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => function () {
                return true; // Allow webhook access - verified by signature
            }
        ]);

        register_rest_route('wpwps/v1', '/orders/sync/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'syncOrder'],
            'permission_callback' => function () {
                return current_user_can('manage_woocommerce');
            },
            'args' => [
                'id' => [
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        register_rest_route('wpwps/v1', '/orders/send/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'sendOrder'],
            'permission_callback' => function () {
                return current_user_can('manage_woocommerce');
            },
            'args' => [
                'id' => [
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }

    /**
     * Handle Printify webhook
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleWebhook($request): \WP_REST_Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        
        if (!$body || !isset($body['event']) || !isset($body['data'])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Invalid webhook data'
            ], 400);
        }

        $settings = get_option('wpwps_settings');
        $webhook_secret = isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '';
        
        // Verify webhook signature if secret is set
        if (!empty($webhook_secret)) {
            $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
            $computed_signature = hash_hmac('sha256', file_get_contents('php://input'), $webhook_secret);
            
            if (!hash_equals($computed_signature, $signature)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Invalid webhook signature'
                ], 401);
            }
        }

        $event = $body['event'];
        $data = $body['data'];

        // Process webhook based on event type
        switch ($event) {
            case 'order_updated':
                $this->processOrderUpdate($data);
                break;
                
            case 'shipment_created':
                $this->processShipmentCreated($data);
                break;
                
            default:
                // Log unhandled webhook event
                error_log('Unhandled Printify webhook event: ' . $event);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Webhook processed'
        ], 200);
    }

    /**
     * Process order update webhook
     *
     * @param array $data
     * @return void
     */
    private function processOrderUpdate(array $data): void
    {
        if (!isset($data['id']) || !isset($data['status'])) {
            return;
        }

        $printifyOrderId = $data['id'];
        $status = $data['status'];
        
        // Find WooCommerce order by Printify order ID
        $orderIds = HPOSCompatibility::getOrdersByMeta('_wpwps_printify_order_id', $printifyOrderId);
        
        if (empty($orderIds)) {
            error_log("Printify order not found: {$printifyOrderId}");
            return;
        }

        foreach ($orderIds as $orderId) {
            $order = HPOSCompatibility::getOrder($orderId);
            
            if (!$order) {
                continue;
            }

            // Update order meta with new status
            HPOSCompatibility::updateOrderMeta($order, '_wpwps_printify_order_status', $status);

            // Add order note
            $note = sprintf(
                __('Printify order status updated: %s', 'wp-woocommerce-printify-sync'),
                $status
            );
            $order->add_order_note($note);

            // Update WooCommerce order status if configured
            $settings = get_option('wpwps_settings');
            $mapStatuses = isset($settings['map_order_statuses']) && $settings['map_order_statuses'] === 'yes';
            
            if ($mapStatuses) {
                $this->updateWooOrderStatus($order, $status);
            }
        }
    }

    /**
     * Process shipment created webhook
     *
     * @param array $data
     * @return void
     */
    private function processShipmentCreated(array $data): void
    {
        if (!isset($data['order_id']) || !isset($data['tracking_number'])) {
            return;
        }

        $printifyOrderId = $data['order_id'];
        
        // Find WooCommerce order by Printify order ID
        $orderIds = HPOSCompatibility::getOrdersByMeta('_wpwps_printify_order_id', $printifyOrderId);
        
        if (empty($orderIds)) {
            error_log("Printify order not found for shipment: {$printifyOrderId}");
            return;
        }

        $trackingInfo = [
            'tracking_number' => $data['tracking_number'],
            'carrier' => $data['carrier'] ?? 'Unknown',
            'url' => $data['tracking_url'] ?? '',
        ];

        foreach ($orderIds as $orderId) {
            $order = HPOSCompatibility::getOrder($orderId);
            
            if (!$order) {
                continue;
            }

            // Get existing tracking info if any
            $existingTracking = HPOSCompatibility::getOrderMeta($order, '_wpwps_tracking_info', true);
            $existingTracking = is_array($existingTracking) ? $existingTracking : [];
            
            // Add new tracking info
            $existingTracking[] = $trackingInfo;
            HPOSCompatibility::updateOrderMeta($order, '_wpwps_tracking_info', $existingTracking);

            // Add order note with tracking info
            $note = sprintf(
                __('Printify shipment created - Tracking number: %s (Carrier: %s)', 'wp-woocommerce-printify-sync'),
                $trackingInfo['tracking_number'],
                $trackingInfo['carrier']
            );
            
            if (!empty($trackingInfo['url'])) {
                $note .= ' - <a href="' . esc_url($trackingInfo['url']) . '" target="_blank">' . __('Track Package', 'wp-woocommerce-printify-sync') . '</a>';
            }
            
            $order->add_order_note($note, true); // true to send note to customer

            // Update order status to completed if configured
            $settings = get_option('wpwps_settings');
            $completeOnShipment = isset($settings['complete_on_shipment']) && $settings['complete_on_shipment'] === 'yes';
            
            if ($completeOnShipment && $order->get_status() !== 'completed') {
                $order->update_status('completed', __('Order completed - Printify shipment created', 'wp-woocommerce-printify-sync'));
            }
        }
    }

    /**
     * Update WooCommerce order status based on Printify status
     *
     * @param \WC_Order $order
     * @param string $printifyStatus
     * @return void
     */
    private function updateWooOrderStatus(\WC_Order $order, string $printifyStatus): void
    {
        $statusMappings = [
            'pending' => false, // Don't change
            'on_hold' => false, // Don't change
            'canceled' => 'cancelled',
            'fulfilled' => 'completed',
            'processing' => 'processing',
            'ready_for_fulfillment' => 'processing',
        ];

        if (isset($statusMappings[$printifyStatus]) && $statusMappings[$printifyStatus] !== false) {
            $newStatus = $statusMappings[$printifyStatus];
            $currentStatus = $order->get_status();
            
            if ($currentStatus !== $newStatus) {
                $order->update_status(
                    $newStatus,
                    sprintf(__('Status updated from Printify: %s', 'wp-woocommerce-printify-sync'), $printifyStatus)
                );
            }
        }
    }

    /**
     * Sync order with Printify
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function syncOrder(\WP_REST_Request $request): \WP_REST_Response
    {
        $orderId = $request->get_param('id');
        $order = HPOSCompatibility::getOrder($orderId);
        
        if (!$order) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Order not found', 'wp-woocommerce-printify-sync')
            ], 404);
        }

        $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
        
        if (!$printifyOrderId) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Order not linked to Printify', 'wp-woocommerce-printify-sync')
            ], 400);
        }

        $settings = get_option('wpwps_settings');
        $apiKey = isset($settings['api_key']) ? $settings['api_key'] : '';
        $shopId = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        
        if (empty($apiKey) || empty($shopId)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('API credentials not configured', 'wp-woocommerce-printify-sync')
            ], 400);
        }

        $apiClient = new PrintifyClient($apiKey, $shopId);
        
        try {
            // Get order from Printify
            $endpoint = "shops/{$shopId}/orders/{$printifyOrderId}.json";
            $response = $apiClient->makeRequest($endpoint);
            
            if (!$response) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => __('Failed to get order from Printify', 'wp-woocommerce-printify-sync')
                ], 400);
            }

            // Update order status
            $status = $response['status'] ?? 'unknown';
            HPOSCompatibility::updateOrderMeta($order, '_wpwps_printify_order_status', $status);
            
            $order->add_order_note(
                sprintf(__('Printify order status updated: %s', 'wp-woocommerce-printify-sync'), $status)
            );

            // Update WooCommerce order status if configured
            $mapStatuses = isset($settings['map_order_statuses']) && $settings['map_order_statuses'] === 'yes';
            
            if ($mapStatuses) {
                $this->updateWooOrderStatus($order, $status);
            }

            // Get shipping info
            if (isset($response['shipments']) && !empty($response['shipments'])) {
                $trackingInfo = [];
                
                foreach ($response['shipments'] as $shipment) {
                    if (!isset($shipment['tracking_number'])) {
                        continue;
                    }
                    
                    $trackingInfo[] = [
                        'tracking_number' => $shipment['tracking_number'],
                        'carrier' => $shipment['carrier'] ?? 'Unknown',
                        'url' => $shipment['tracking_url'] ?? '',
                    ];
                }
                
                if (!empty($trackingInfo)) {
                    HPOSCompatibility::updateOrderMeta($order, '_wpwps_tracking_info', $trackingInfo);
                }
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => __('Order synced successfully', 'wp-woocommerce-printify-sync'),
                'status' => $status
            ], 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send order to Printify
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function sendOrder(\WP_REST_Request $request): \WP_REST_Response
    {
        $orderId = $request->get_param('id');
        $order = HPOSCompatibility::getOrder($orderId);
        
        if (!$order) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Order not found', 'wp-woocommerce-printify-sync')
            ], 404);
        }

        // Check if order has already been sent to Printify
        $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
        
        if ($printifyOrderId) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Order already sent to Printify', 'wp-woocommerce-printify-sync')
            ], 400);
        }

        // Initialize WooCommerce service provider
        $wooCommerceService = $this->getContainer()->make('woocommerce');
        
        // Check if service exists
        if (!$wooCommerceService) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('WooCommerce service not found', 'wp-woocommerce-printify-sync')
            ], 500);
        }

        // Send order to Printify
        $result = $wooCommerceService->sendOrderToPrintify($order);
        
        if (!$result) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Failed to send order to Printify', 'wp-woocommerce-printify-sync')
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Order sent to Printify successfully', 'wp-woocommerce-printify-sync'),
            'printify_order_id' => HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true),
            'status' => HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_status', true)
        ], 200);
    }
}