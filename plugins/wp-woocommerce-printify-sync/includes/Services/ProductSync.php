<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ApiClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\ImageProcessor;

class ProductSync {
    private $api_client;
    private $logger;
    private $image_processor;
    private $cache;

    public function __construct(
        ApiClientInterface $api_client,
        LoggerInterface $logger,
        ImageProcessor $image_processor,
        CacheInterface $cache
    ) {
        $this->api_client = $api_client;
        $this->logger = $logger;
        $this->image_processor = $image_processor;
        $this->cache = $cache;
    }

    /**
     * Import a product from Printify to WooCommerce
     */
    public function importProduct($printify_id, $update_existing = true) {
        try {
            $this->logger->info("Starting product import", ['printify_id' => $printify_id]);
            
            // Check cache first
            $cache_key = 'printify_product_' . $printify_id;
            $product_data = $this->cache->get($cache_key);
            
            if (!$product_data) {
                $product_data = $this->api_client->getProduct($printify_id);
                $this->cache->set($cache_key, $product_data, 3600); // Cache for 1 hour
            }

            // Check if product already exists
            $existing_id = $this->getExistingProductId($printify_id);
            
            if ($existing_id && !$update_existing) {
                throw new \Exception("Product already exists and update_existing is false");
            }

            // Prepare product data
            $wc_product_data = $this->mapPrintifyToWooCommerce($product_data);
            
            // Create or update product
            if ($existing_id) {
                $product = wc_get_product($existing_id);
                $this->updateProduct($product, $wc_product_data);
                $product_id = $existing_id;
            } else {
                $product_id = $this->createProduct($wc_product_data);
            }

            // Process images
            $this->processProductImages($product_id, $product_data['images']);
            
            // Update metadata
            $this->updateProductMetadata($product_id, $print