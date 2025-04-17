<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

/**
 * Interface for Printify API integration
 */
interface PrintifyApiInterface {
    /**
     * Get products from Printify API
     *
     * @param array $params Optional parameters for filtering/pagination
     * @return array|false
     */
    public function getProducts($params = []);
    
    /**
     * Get a single product by ID
     *
     * @param int $product_id
     * @return array|false
     */
    public function getProduct($product_id);
    
    /**
     * Create a webhook subscription
     *
     * @param string $event
     * @param string $url
     * @return bool
     */
    public function createWebhook($event, $url);
    
    /**
     * Handle API errors
     *
     * @param mixed $response
     * @return void
     */
    public function handleError($response);
}
