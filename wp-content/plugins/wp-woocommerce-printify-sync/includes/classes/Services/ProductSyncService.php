<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Services;

use ApolloWeb\WpWooCommercePrintifySync\Helpers\Logger;
use ApolloWeb\WpWooCommercePrintifySync\Services\ApiClient;

class ProductSyncService
{
    protected $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function syncProducts()
    {
        $products = $this->apiClient->fetchPrintifyProducts();

        foreach ($products as $product) {
            // Logic to sync product with WooCommerce
            Logger::log('Syncing product: ' . $product['name']);
            $this->syncProductWithWooCommerce($product);
        }
    }

    protected function syncProductWithWooCommerce($product)
    {
        // Example logic to sync product with WooCommerce
        $wc_product = [
            'name' => $product['name'],
            'type' => 'simple',
            'regular_price' => $product['price'],
            'description' => $product['description'],
            'short_description' => $product['short_description'],
            'categories' => $product['categories'],
            'images' => $product['images'],
            'status' => 'publish',
        ];

        // Example function to create or update WooCommerce product
        $this->createOrUpdateWooCommerceProduct($wc_product);
        Logger::log('Product synced: ' . $product['name']);
    }

    protected function createOrUpdateWooCommerceProduct($wc_product)
    {
        // Add logic to create or update WooCommerce product
        // Example: wc_create_product($wc_product);
        Logger::log('Creating/updating WooCommerce product: ' . $wc_product['name']);
    }
}