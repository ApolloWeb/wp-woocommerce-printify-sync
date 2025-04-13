<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

/**
 * Printify API Interface
 */
interface PrintifyApiInterface {
    /**
     * Get products from Printify
     *
     * @param string $shop_id Shop ID
     * @param array  $params  Query parameters
     * @return array|WP_Error Products array or WP_Error
     */
    public function get_products($shop_id, $params = []);
    
    /**
     * Get a single product from Printify
     *
     * @param string $shop_id    Shop ID
     * @param string $product_id Product ID
     * @return array|WP_Error Product array or WP_Error
     */
    public function get_product($shop_id, $product_id);
    
    /**
     * Register external product ID with Printify
     *
     * @param string $shop_id          Shop ID
     * @param string $printify_id      Printify Product ID
     * @param string $external_id      WooCommerce Product ID
     * @return array|WP_Error Response or WP_Error
     */
    public function register_external_product($shop_id, $printify_id, $external_id);
    
    /**
     * Get print providers from Printify
     *
     * @return array|WP_Error Print providers array or WP_Error
     */
    public function get_print_providers();
    
    /**
     * Get all available webhooks
     *
     * @return array|WP_Error Webhooks array or WP_Error
     */
    public function get_webhooks();
    
    /**
     * Register a webhook with Printify
     *
     * @param string $event Event to subscribe to
     * @param string $url   URL to send webhook to
     * @return array|WP_Error Response or WP_Error
     */
    public function register_webhook($event, $url);
    
    /**
     * Delete a webhook
     *
     * @param string $webhook_id Webhook ID
     * @return array|WP_Error Response or WP_Error
     */
    public function delete_webhook($webhook_id);
}
