<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiRequestHelper;

class WooCommerceApi
{
    public function getProducts()
    {
        $args = [
            'status' => 'publish',
            'limit'  => -1
        ];

        $products = wc_get_products($args);

        return $products;
    }

    public function updateProduct($productId, $data)
    {
        $product = wc_get_product($productId);

        if (!$product) {
            return new \WP_Error('product_not_found', 'Product not found');
        }

        foreach ($data as $key => $value) {
            $product->set_prop($key, $value);
        }

        $product->save();
        return $product;
    }

    // Add more methods as needed...
}