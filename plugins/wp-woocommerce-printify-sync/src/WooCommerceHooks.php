<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class WooCommerceHooks implements ServiceProvider
{
    private $printify_api;

    public function boot()
    {
        add_action('woocommerce_update_product', [$this, 'onProductUpdate'], 10, 1);
        add_action('woocommerce_new_order', [$this, 'onNewOrder'], 10, 1);

        $api_key = get_option('printify_api_key');
        $this->printify_api = new PrintifyAPI($api_key);
    }

    public function onProductUpdate($product_id)
    {
        $product = wc_get_product($product_id);
        $external_product_id = get_post_meta($product_id, '_printify_external_product_id', true);

        if ($external_product_id) {
            $product_data = [
                'title' => $product->get_name(),
                'description' => $product->get_description(),
                'sku' => $product->get_sku(),
                'images' => array_map(function ($image_id) {
                    $image_url = wp_get_attachment_url($image_id);
                    return ['src' => $image_url];
                }, $product->get_gallery_image_ids()),
                'categories' => array_map(function ($category) {
                    return ['name' => $category->name];
                }, wp_get_post_terms($product_id, 'product_cat')),
                'tags' => array_map(function ($tag) {
                    return ['name' => $tag->name];
                }, wp_get_post_terms($product_id, 'product_tag')),
                'attributes' => $product->get_attributes(),
            ];

            $this->printify_api->updateProduct($external_product_id, $product_data);
        }
    }

    public function onNewOrder($order_id)
    {
        $order = wc_get_order($order_id);
        $items = [];

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $external_product_id = get_post_meta($product_id, '_printify_external_product_id', true);

            if ($external_product_id) {
                $items[] = [
                    'external_product_id' => $external_product_id,
                    'quantity' => $item->get_quantity(),
                ];
            }
        }

        if (!empty($items)) {
            $order_data = [
                'order_number' => $order->get_order_number(),
                'items' => $items,
                'shipping_address' => [
                    'first_name' => $order->get_shipping_first_name(),
                    'last_name' => $order->get_shipping_last_name(),
                    'address_1' => $order->get_shipping_address_1(),
                    'address_2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'postcode' => $order->get_shipping_postcode(),
                    'country' => $order->get_shipping_country(),
                ],
            ];

            $this->printify_api->createOrder($order_data);
        }
    }
}