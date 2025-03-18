<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class CronJobs
{
    public function __construct()
    {
        add_action('printify_import_products_cron', [$this, 'importProducts']);
    }

    public function importProducts()
    {
        $products = get_transient('printify_products');
        if (!$products) {
            return;
        }

        $chunk_size = 10;
        $chunks     = array_chunk($products, $chunk_size);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $product) {
                // Import product into WooCommerce
                $this->importProduct($product);
            }
        }
    }

    private function importProduct($product)
    {
        // Implement the logic to import a product into WooCommerce
        $product_data = [
            'name' => $product['title'],
            'description' => $product['description'],
            'sku' => $product['id'], // Printify ID as SKU
            'external_product_id' => $product['external_product_id'],
            'images' => array_map(function ($image) {
                return ['src' => $image['src']];
            }, $product['images']),
            'categories' => array_map(function ($category) {
                return ['name' => $category['name']];
            }, $product['categories']),
            'tags' => array_map(function ($tag) {
                return ['name' => $tag['name']];
            }, $product['tags']),
            'attributes' => $product['options'],
        ];

        // Use WooCommerce API to create or update product
        // Example: wc_create_product($product_data);
    }
}

new CronJobs();