<?php
/**
 * User: ApolloWeb
 * Timestamp: 2025-02-20 04:04:31
 */

namespace ApolloWeb\WooCommercePrintifySync\Includes;

class APIClient
{
    private $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    private function request($endpoint, $method = 'GET', $body = null)
    {
        $url = "https://api.printify.com/v1/" . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
        ];

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function get_shops()
    {
        return $this->request('shops.json');
    }

    public function get_products($shop_id)
    {
        return $this->request("shops/{$shop_id}/products.json");
    }

    public function get_product_details($product_id)
    {
        return $this->request("products/{$product_id}.json");
    }

    public function get_stock_levels($product_id)
    {
        return $this->request("products/{$product_id}/stock_levels.json");
    }

    public function get_variants($product_id)
    {
        return $this->request("products/{$product_id}/variants.json");
    }

    public function get_tags($product_id)
    {
        return $this->request("products/{$product_id}/tags.json");
    }

    public function get_categories($product_id)
    {
        return $this->request("products/{$product_id}/categories.json");
    }
}