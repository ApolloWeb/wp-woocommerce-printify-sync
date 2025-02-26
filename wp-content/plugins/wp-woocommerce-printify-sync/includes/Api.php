<?php

namespace ApolloWeb\WooCommercePrintifySync;

class Api
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getShops()
    {
        $endpoint = 'https://api.printify.com/v2/shops.json';
        return $this->makeRequest($endpoint);
    }

    public function getProducts($shopId)
    {
        $endpoint = "https://api.printify.com/v2/shops/{$shopId}/products.json?limit=5";
        return $this->makeRequest($endpoint);
    }

    private function makeRequest($endpoint)
    {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'timeout' => 10,
        ];
        $response = wp_remote_get($endpoint, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('printify_api_error', __('Unexpected response from Printify API', 'wp-woocommerce-printify-sync'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', __('Error decoding Printify API response', 'wp-woocommerce-printify-sync'));
        }

        return $data;
    }
}