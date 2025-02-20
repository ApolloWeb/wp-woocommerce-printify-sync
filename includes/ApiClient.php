<?php

namespace ApolloWeb\WooCommercePrintifySync;

class APIClient
{
    private $apiUrl = 'https://api.printify.com/v1/';
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function makeRequest($endpoint, $method = 'GET', $body = null)
    {
        $url = $this->apiUrl . $endpoint;
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ];

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getShops()
    {
        return $this->makeRequest('shops');
    }

    public function getProducts($shopId)
    {
        return $this->makeRequest("shops/{$shopId}/products.json");
    }

    public function getProductDetails($productId)
    {
        return $this->makeRequest("products/{$productId}.json");
    }

    public function getStockLevels($productId)
    {
        return $this->makeRequest("products/{$productId}/stock_levels.json");
    }

    public function getVariants($productId)
    {
        return $this->makeRequest("products/{$productId}/variants.json");
    }

    public function getTags($productId)
    {
        return $this->makeRequest("products/{$productId}/tags.json");
    }

    public function getCategories($productId)
    {
        return $this->makeRequest("products/{$productId}/categories.json");
    }
}