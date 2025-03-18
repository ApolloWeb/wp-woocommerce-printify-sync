<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\ServiceProviderInterface;

class ActionScheduler implements ServiceProviderInterface
{
    /**
     * Register services to the container
     * 
     * @return void
     */
    public function register()
    {
        add_action('init', [$this, 'scheduleActions']);
        add_action('printify_import_products', [$this, 'importProducts']);
    }

    /**
     * Boot the service
     */
    public function boot()
    {
        // Boot implementation
    }

    public function scheduleActions()
    {
        if (!as_next_scheduled_action('printify_import_products')) {
            as_schedule_recurring_action(time(), 3600, 'printify_import_products');
        }
    }

    public function importProducts()
    {
        $products = get_transient('printify_products');
        if (!$products) {
            return;
        }

        foreach ($products as $product) {
            $this->importProduct($product);
        }
    }

    private function importProduct($product)
    {
        $image_handler = new ImageHandler();
        $product_data = [
            'name' => $product['title'],
            'description' => $product['description'],
            'sku' => $product['id'], // Printify ID as SKU
            'images' => array_map(function ($image) use ($image_handler) {
                return ['src' => $image_handler->addImageToMediaLibrary($image['src'])];
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
        $wc_product = wc_create_product($product_data);
        if ($product['images']) {
            $image_handler->setProductImage($wc_product->get_id(), $product['images'][0]['src']);
        }
    }
}