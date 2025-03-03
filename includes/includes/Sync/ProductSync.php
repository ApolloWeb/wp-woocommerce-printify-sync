<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Sync;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi;

class ProductSync
{
    public static function register()
    {
        add_action('init', [__CLASS__, 'syncProducts']);
    }

    public static function syncProducts()
    {
        $apiKey = get_option('printify_api_key');
        $defaultShopId = get_option('default_shop');

        if (!$apiKey || !$defaultShopId) {
            return;
        }

        $printifyApi = new PrintifyApi($apiKey);
        $products = $printifyApi->getProducts($defaultShopId);

        if (is_wp_error($products)) {
            // Handle error
            return;
        }

        // Process products
        foreach ($products['data'] as $product) {
            // Sync product with WooCommerce
        }
    }
}