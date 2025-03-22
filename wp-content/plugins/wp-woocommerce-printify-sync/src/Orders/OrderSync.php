<?php
/**
 * Order synchronization functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;
use WP_Error;

/**
 * Class for syncing orders between WooCommerce and Printify.
 */
class OrderSync
{
    /**
     * Printify API client.
     *
     * @var PrintifyAPIClient
     */
    private $api_client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Activity service.
     *
     * @var ActivityService
     */
    private $activity_service;

    /**
     * Constructor.
     *
     * @param PrintifyAPIClient $api_client      Printify API client.
     * @param Logger           $logger           Logger instance.
     * @param ActivityService  $activity_service Activity service.
     */
    public function __construct(
        PrintifyAPIClient $api_client,
        Logger $logger,
        ActivityService $activity_service
    ) {
        $this->api_client = $api_client;
        $this->logger = $logger;
        $this->activity_service = $activity_service;
    }

    /**
     * Initialize the order sync.
     *
     * @return void
     */
    public function init()
    {
        // Add hooks for order sync
        add_action('wpwps_sync_orders', [$this, 'syncOrders']);
        add_action('wpwps_sync_single_order', [$this, 'syncSingleOrder'], 10, 1);
        
        // WooCommerce order status changes
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChanged'], 10, 4);
    }

    /**
     * Sync all orders from Printify.
     *
     * @return array|WP_Error Result of the sync operation.
     */
    public function syncOrders()
    {
        $this->logger->info('Starting full order sync from Printify');

        // Check if shop ID is set
        $shop_id = $this->api_client->getShopId();
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set, cannot sync orders');
            return new WP_Error('missing_shop_id', 'Shop ID is not set. Please configure it in the settings.');
        }

        $page = 1;
        $per_page = 20;
        $total_synced = 0;
        $total_failed = 0;

        do {
            $this->logger->info("Fetching orders page {$page} from Printify");
            
            // Get orders from Printify
            $response = $this->api_client->getOrders($page, $per_page);
            
            if (is_wp_error($response)) {
                $this->logger->error('Error fetching orders: ' . $response->get_error_message());
                return $response;
            }

            // Check if we have orders
            if (empty($response['data'])) {
                $this->logger->info('No more orders found');
                break;
            }

            $orders = $response['data'];
            $this->logger->info('Found ' . count($orders) . ' orders on page ' . $page);

            // Process each order
            foreach ($orders as $order) {
                $result = $this->processSingleOrder($order);
                
                if (is_wp_error($result)) {
                    $this->logger->error("Failed to process order {$order['id']}: " . $result->get_error_message());
                    $total_failed++;
                } else {
                    $total_synced++;
                }
            }

            // Check if there are more pages
            $total_pages = isset($response['last_page']) ? (int) $response['last_page'] : 1;
            $page++;
        } while ($page <= $total_pages);

        $this->logger->info("Finished syncing {$total_synced} orders. Failed: {$total_failed}");

        $this->activity_service->log('order_sync', sprintf(
            __('Synced %1$d orders from Printify. %2$d failed.', 'wp-woocommerce-printify-sync'),
            $total_synced,
            $total_failed
        ));

        return [
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
            'message' => sprintf(
                __('Synced %1$d orders from Printify. %2$d failed.', 'wp-woocommerce-printify-sync'),
                $total_synced,
                $total_failed
            ),
        ];
    }

    /**
     * Sync a single order from Printify.
     *
     * @param string $order_id Printify order ID.
     * @return array|WP_Error Result of the sync operation.
     */
    public function syncSingleOrder($order_id)
    {
        $this->logger->info("Syncing single order {$order_id} from Printify");

        // Get order details from Printify
        $response = $this->api_client->getOrder($order_id);
        
        if (is_wp_error($response)) {
            $this->logger->error("Error fetching order {$order_id}: " . $response->get_error_message());
            return $response;
        }

        // Process the order
        $result = $this->processSingleOrder($response);
        
        if (is_wp_error($result)) {
            $this->logger->error("Failed to process order {$order_id}: " . $result->get_error_message());
            return $result;
        }

        $this->activity_service->log('order_sync', sprintf(
            __('Synced order %s from Printify', 'wp-woocommerce-printify-sync'),
            $order_id
        ));

        return [
            'order_id' => $order_id,
            'message' => sprintf(
                __('Order %s synced successfully.', 'wp-woocommerce-printify-sync'),
                $order_id
            ),
        ];
    }

    /**
     * Process a single order from Printify.
     *
     * @param array $order_data Order data from Printify.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function processSingleOrder($order_data)
    {
        if (empty($order_data['id'])) {
            return new WP_Error('invalid_order', 'Invalid order data received');
        }

        $printify_order_id = $order_data['id'];
        $this->logger->info("Processing order {$printify_order_id} from Printify");

        // Check if we already have this order in WooCommerce
        $wc_order_id = $this->getWooCommerceOrderIdByPrintifyId($printify_order_id);
        
        if ($wc_order_id) {
            // Update existing order
            $this->logger->info("Order {$printify_order_id} already exists in WooCommerce (ID: {$wc_order_id}), updating");
            return $this->updateWooCommerceOrder($wc_order_id, $order_data);
        } else {
            // Create new order in WooCommerce
            $this->logger->info("Order {$printify_order_id} not found in WooCommerce, creating new order");
            return $this->createWooCommerceOrder($order_data);
        }
    }

    /**
     * Get WooCommerce order ID by Printify order ID.
     *
     * @param string $printify_order_id Printify order ID.
     * @return int|false WooCommerce order ID or false if not found.
     */
    private function getWooCommerceOrderIdByPrintifyId($printify_order_id)
    {
        $orders = wc_get_orders([
            'meta_key' => '_printify_order_id',
            'meta_value' => $printify_order_id,
            'limit' => 1,
            'return' => 'ids',
        ]);

        return !empty($orders) ? $orders[0] : false;
    }

    /**
     * Create a new WooCommerce order from Printify order data.
     *
     * @param array $order_data Order data from Printify.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function createWooCommerceOrder($order_data)
    {
        // This would be a placeholder implementation
        // In a real implementation, you'd create an actual WooCommerce order
        
        $this->logger->info("Creating WooCommerce order for Printify order {$order_data['id']}");
        
        // Return true to indicate success
        return true;
    }

    /**
     * Update an existing WooCommerce order with Printify order data.
     *
     * @param int   $wc_order_id WooCommerce order ID.
     * @param array $order_data  Order data from Printify.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function updateWooCommerceOrder($wc_order_id, $order_data)
    {
        // This would be a placeholder implementation
        // In a real implementation, you'd update an actual WooCommerce order
        
        $this->logger->info("Updating WooCommerce order {$wc_order_id} with Printify order data {$order_data['id']}");
        
        // Return true to indicate success
        return true;
    }

    /**
     * Handle WooCommerce order status changes.
     *
     * @param int    $order_id    WooCommerce order ID.
     * @param string $status_from Old status.
     * @param string $status_to   New status.
     * @param object $order       WooCommerce order object.
     * @return void
     */
    public function handleOrderStatusChanged($order_id, $status_from, $status_to, $order)
    {
        $printify_order_id = $order->get_meta('_printify_order_id', true);
        
        if (!$printify_order_id) {
            // Not a Printify order, ignore
            return;
        }
        
        $this->logger->info("WooCommerce order {$order_id} status changed from {$status_from} to {$status_to}. Printify ID: {$printify_order_id}");
        
        // Handle various status changes
        // Implementation would depend on your specific requirements
    }
}
