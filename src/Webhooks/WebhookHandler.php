<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSyncServiceInterface;
use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Webhook Handler Class
 */
class WebhookHandler implements WebhookHandlerInterface {
    /**
     * @var ProductSyncServiceInterface
     */
    private $product_sync;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Constructor
     *
     * @param ProductSyncServiceInterface $product_sync
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductSyncServiceInterface $product_sync,
        LoggerInterface $logger
    ) {
        $this->product_sync = $product_sync;
        $this->logger = $logger;
    }
    
    /**
     * Process a webhook event
     *
     * @param string $event Event type
     * @param array  $data  Event data
     * @return bool True on success, false on failure
     */
    public function process_event($event, $data) {
        $this->logger->log_info('webhook', sprintf('Processing webhook event: %s', $event), [
            'event' => $event,
            'data' => $data
        ]);
        
        switch ($event) {
            case 'product.update':
                return $this->handle_product_update($data);
            
            case 'product.delete':
                return $this->handle_product_delete($data);
                
            case 'order.created':
            case 'order.update':
                return $this->handle_order_update($data);
                
            case 'shipping.update':
                return $this->handle_shipping_update($data);
                
            default:
                $this->logger->log_warning('webhook', sprintf('Unhandled webhook event: %s', $event));
                return false;
        }
    }
    
    /**
     * Handle product update webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_product_update($data) {
        if (!isset($data['product']['id'])) {
            $this->logger->log_error('webhook', 'Invalid product data in webhook');
            return false;
        }
        
        $printify_product_id = $data['product']['id'];
        
        // Schedule product import
        $this->product_sync->schedule_product_import($printify_product_id);
        
        $this->logger->log_info('webhook', sprintf('Scheduled product update for ID: %s', $printify_product_id));
        return true;
    }
    
    /**
     * Handle product delete webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_product_delete($data) {
        if (!isset($data['product']['id'])) {
            $this->logger->log_error('webhook', 'Invalid product data in webhook');
            return false;
        }
        
        $printify_product_id = $data['product']['id'];
        
        // Get WooCommerce product ID
        $wc_product_id = $this->get_woocommerce_product_id($printify_product_id);
        
        if ($wc_product_id) {
            // Delete the product
            wp_delete_post($wc_product_id, true);
            $this->logger->log_info('webhook', sprintf('Deleted product ID: %s', $wc_product_id));
            return true;
        }
        
        $this->logger->log_warning('webhook', sprintf('Product not found for deletion: %s', $printify_product_id));
        return false;
    }
    
    /**
     * Handle order update webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_order_update($data) {
        // Order handling logic will be implemented here
        $this->logger->log_info('webhook', 'Order update webhook received');
        return true;
    }
    
    /**
     * Handle shipping update webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_shipping_update($data) {
        // Shipping update logic will be implemented here
        $this->logger->log_info('webhook', 'Shipping update webhook received');
        return true;
    }
    
    /**
     * Get WooCommerce product ID by Printify ID
     *
     * @param string $printify_id Printify Product ID
     * @return int|false WooCommerce product ID or false
     */
    private function get_woocommerce_product_id($printify_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
            '_printify_product_id',
            $printify_id
        );
        
        $result = $wpdb->get_var($query);
        
        return $result ? (int)$result : false;
    }
}
