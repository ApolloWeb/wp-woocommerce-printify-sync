<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * High-level API wrapper with business logic methods
 */
class Api {
    /**
     * PrintifyAPI instance
     *
     * @var PrintifyAPI
     */
    private $api;
    
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new PrintifyAPI();
        $this->logger = new Logger('api');
    }
    
    /**
     * Get all shops
     *
     * @return array|WP_Error List of shops or error
     */
    public function getShops() {
        return $this->api->withRateLimit([$this->api, 'request'], ['shops.json']);
    }
    
    /**
     * Get shop by ID
     *
     * @param int $shop_id Shop ID
     * @return array|WP_Error Shop data or error
     */
    public function getShop($shop_id) {
        return $this->api->withRateLimit([$this->api, 'request'], ["shops/{$shop_id}.json"]);
    }
    
    /**
     * Get products for a shop with pagination
     *
     * @param int $shop_id Shop ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array|WP_Error Products data or error
     */
    public function getProducts($shop_id, $page = 1, $limit = 20) {
        $endpoint = "shops/{$shop_id}/products.json?page={$page}&limit={$limit}";
        return $this->api->withRateLimit([$this->api, 'request'], [$endpoint]);
    }
    
    /**
     * Get a specific product
     *
     * @param int $shop_id Shop ID
     * @param string $product_id Product ID
     * @return array|WP_Error Product data or error
     */
    public function getProduct($shop_id, $product_id) {
        $endpoint = "shops/{$shop_id}/products/{$product_id}.json";
        return $this->api->withRateLimit([$this->api, 'request'], [$endpoint]);
    }
    
    /**
     * Get product images
     *
     * @param int $shop_id Shop ID
     * @param string $product_id Product ID
     * @return array|WP_Error Product images data or error
     */
    public function getProductImages($shop_id, $product_id) {
        $product = $this->getProduct($shop_id, $product_id);
        
        if (is_wp_error($product)) {
            return $product;
        }
        
        return $product['images'] ?? [];
    }
    
    /**
     * Get product variants
     *
     * @param int $shop_id Shop ID
     * @param string $product_id Product ID
     * @return array|WP_Error Product variants data or error
     */
    public function getProductVariants($shop_id, $product_id) {
        $product = $this->getProduct($shop_id, $product_id);
        
        if (is_wp_error($product)) {
            return $product;
        }
        
        return $product['variants'] ?? [];
    }
    
    /**
     * Get total product count for a shop
     *
     * @param int $shop_id Shop ID
     * @return int|WP_Error Total product count or error
     */
    public function getTotalProductCount($shop_id) {
        $result = $this->getProducts($shop_id, 1, 1);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['pagination']['total'] ?? 0;
    }
}