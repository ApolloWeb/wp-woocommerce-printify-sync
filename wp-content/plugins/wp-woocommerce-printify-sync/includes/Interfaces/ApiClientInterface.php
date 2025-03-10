<?php
/**
 * API Client Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Interfaces
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

/**
 * ApiClientInterface Interface
 */
interface ApiClientInterface {
    /**
     * Make a request to the API
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array  $data Request data
     * @param array  $query_params Query parameters
     * @param int    $retry_count Current retry count
     * @return mixed
     */
    public function request($endpoint, $method = 'GET', $data = [], $query_params = [], $retry_count = 0);
    
    /**
     * Get shops from the API
     *
     * @return mixed
     */
    public function getShops();
    
    /**
     * Get products from the API
     *
     * @param array $params Query parameters
     * @return mixed
     */
    public function getProducts($params = []);
    
    /**
     * Get a single product from the API
     *
     * @param string $product_id Product ID
     * @return mixed
     */
    public function getProduct($product_id);
    
    /**
     * Create a product via the API
     *
     * @param array $data Product data
     * @return mixed
     */
    public function createProduct($data);
    
    /**
     * Update a product via the API
     *
     * @param string $product_id Product ID
     * @param array  $data Product data
     * @return mixed
     */
    public function updateProduct($product_id, $data);
    
    /**
     * Delete a product via the API
     *
     * @param string $product_id Product ID
     * @return mixed
     */
    public function deleteProduct($product_id);
    
    /**
     * Get orders from the API
     *
     * @param array $params Query parameters
     * @return mixed
     */
    public function getOrders($params = []);
    
    /**
     * Get a single order from the API
     *
     * @param string $order_id Order ID
     * @return mixed
     */
    public function getOrder($order_id);
    
    /**
     * Create an order via the API
     *
     * @param array $data Order data
     * @return mixed
     */
    public function createOrder($data);
}