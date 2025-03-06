<?php
/**
 * Printify API Client Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API\Printify
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API\Printify;

interface PrintifyApiClientInterface {
    /**
     * Send request to Printify API
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response data
     */
    public function request(string $endpoint, array $args = []): array;
    
    /**
     * Get shops from Printify
     *
     * @return array Shops data
     */
    public function getShops(): array;
    
    /**
     * Get products from Printify shop
     *
     * @param int $shop_id Shop ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Products data
     */
    public function getProducts(int $shop_id, int $page = 1, int $limit = 10): array;
    
    // Additional method signatures...
}