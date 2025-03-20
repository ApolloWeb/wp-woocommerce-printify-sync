<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use WP_Error;

/**
 * PrintifyAPI Interface
 * 
 * Follows Interface Segregation Principle by defining a clear contract
 * for the Printify API implementation
 */
interface PrintifyAPIInterface
{
    /**
     * Get products for a shop
     * 
     * @param string $shopId The shop ID
     * @param array $params Additional query parameters
     * @return array|WP_Error Products or error
     */
    public function getProducts(string $shopId, array $params = []);
    
    /**
     * Get all products for a shop (handling pagination automatically)
     * 
     * @param string $shopId The shop ID
     * @param int $limit Items per page (max 50)
     * @return array|WP_Error All products or error
     */
    public function getAllProducts(string $shopId, int $limit = 50);
    
    /**
     * Get a specific product
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @return array|WP_Error Product or error
     */
    public function getProduct(string $shopId, string $productId);
}
