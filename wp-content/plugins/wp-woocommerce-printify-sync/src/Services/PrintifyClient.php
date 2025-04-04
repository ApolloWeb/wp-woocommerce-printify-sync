<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class PrintifyClient {
    private string $api_key;
    private string $api_url = 'https://api.printify.com/v1/';
    private array $default_headers;

    public function __construct() 
    {
        $this->api_key = get_option('wpwps_printify_api_key', '');
        $this->default_headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function isConfigured(): bool 
    {
        return !empty($this->api_key);
    }

    public function getShops(): array 
    {
        return $this->request('GET', 'shops.json');
    }

    public function getProducts(int $shop_id, array $params = []): array 
    {
        return $this->request('GET', "shops/{$shop_id}/products.json", $params);
    }

    public function syncProduct(int $shop_id, array $product_data): array 
    {
        return $this->request('POST', "shops/{$shop_id}/products.json", [], $product_data);
    }

    public function createOrder(int $shop_id, array $order_data): array 
    {
        return $this->request('POST', "shops/{$shop_id}/orders.json", [], $order_data);
    }

    public function cancelOrder(int $shop_id, string $order_id): array 
    {
        return $this->request('POST', "shops/{$shop_id}/orders/{$order_id}/cancel.json");
    }

    private function request(string $method, string $endpoint, array $params = [], array $body = []): array 
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Printify API key not configured');
        }

        $url = $this->api_url . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'method' => $method,
            'headers' => $this->default_headers,
            'timeout' => 30,
        ];

        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from Printify API');
        }

        return $data;
    }
}
