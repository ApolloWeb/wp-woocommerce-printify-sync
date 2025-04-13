<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

/**
 * Webhook Handler Interface
 */
interface WebhookHandlerInterface {
    /**
     * Process a webhook event
     *
     * @param string $event Event type
     * @param array  $data  Event data
     * @return bool True on success, false on failure
     */
    public function process_event($event, $data);
    
    /**
     * Handle product update webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_product_update($data);
    
    /**
     * Handle product delete webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_product_delete($data);
    
    /**
     * Handle order update webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_order_update($data);
    
    /**
     * Handle shipping update webhook
     *
     * @param array $data Event data
     * @return bool True on success
     */
    public function handle_shipping_update($data);
}
