<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

class PrintifyAPI
{
    private $apiKey;
    private $httpClient;
    private $apiUrl;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = get_option('printify_sync_api_url', 'https://api.printify.com/v1/');
        $this->httpClient = new HttpClient();
    }

    public function fetchShops()
    {
        $url = 'https://api.printify.com/v1/shops.json';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ];

        $response = $this->httpClient->get($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode($response, true);
    }

    public function fetchProducts()
    {
        $url = 'https://api.printify.com/v1/products.json';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ];

        $response = $this->httpClient->get($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode($response, true);
    }

    public function updateWebhook($webhook_id, $url, $event)
    {
        $endpoint = 'https://api.printify.com/v1/webhooks/' . $webhook_id . '.json';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'url' => $url,
                'event' => $event,
            ]),
        ];

        $response = $this->httpClient->get($endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode($response, true);
    }

    public function getShops()
    {
        return $this->get('shops.json');
    }

    // Product Methods
    public function getProducts($shop_id)
    {
        return $this->get("shops/{$shop_id}/products.json");
    }

    public function getProduct($shop_id, $product_id)
    {
        return $this->get("shops/{$shop_id}/products/{$product_id}.json");
    }

    public function createProduct($shop_id, $data)
    {
        return $this->post("shops/{$shop_id}/products.json", $data);
    }

    public function updateProduct($shop_id, $product_id, $data)
    {
        return $this->put("shops/{$shop_id}/products/{$product_id}.json", $data);
    }

    public function deleteProduct($shop_id, $product_id)
    {
        return $this->delete("shops/{$shop_id}/products/{$product_id}.json");
    }

    public function publishProduct($shop_id, $product_id)
    {
        return $this->post("shops/{$shop_id}/products/{$product_id}/publish.json");
    }

    // Order Methods
    public function getOrders($shop_id, $params = [])
    {
        return $this->get("shops/{$shop_id}/orders.json", $params);
    }

    public function getOrder($shop_id, $order_id)
    {
        return $this->get("shops/{$shop_id}/orders/{$order_id}.json");
    }

    public function createOrder($shop_id, $data)
    {
        return $this->post("shops/{$shop_id}/orders.json", $data);
    }

    public function calculateShipping($shop_id, $data)
    {
        return $this->post("shops/{$shop_id}/orders/calculator.json", $data);
    }

    public function cancelOrder($shop_id, $order_id)
    {
        return $this->post("shops/{$shop_id}/orders/{$order_id}/cancel.json");
    }

    // Webhook Methods
    public function getWebhooks($shop_id)
    {
        return $this->get("shops/{$shop_id}/webhooks.json");
    }

    public function createWebhook($shop_id, $data)
    {
        return $this->post("shops/{$shop_id}/webhooks.json", $data);
    }

    public function deleteWebhook($shop_id, $webhook_id)
    {
        return $this->delete("shops/{$shop_id}/webhooks/{$webhook_id}.json");
    }

    // Upload Methods
    public function uploadImage($shop_id, $file_path)
    {
        $file_data = file_get_contents($file_path);
        return $this->post("shops/{$shop_id}/uploads/images.json", [
            'file' => base64_encode($file_data)
        ]);
    }

    // Helper Methods for HTTP Requests
    private function get($endpoint, $params = [])
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->request('GET', $url);
    }

    private function post($endpoint, $data = [])
    {
        return $this->request('POST', $this->buildUrl($endpoint), $data);
    }

    private function put($endpoint, $data = [])
    {
        return $this->request('PUT', $this->buildUrl($endpoint), $data);
    }

    private function delete($endpoint)
    {
        return $this->request('DELETE', $this->buildUrl($endpoint));
    }

    private function request($method, $url, $data = null)
    {
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        if ($data !== null) {
            $args['body'] = json_encode($data);
        }

        // Use get() method instead of undefined request() method
        $response = ($method === 'GET') ? 
            $this->httpClient->get($url, $args) : 
            $this->httpClient->post($url, $args);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $body;
    }

    private function buildUrl($endpoint, $params = [])
    {
        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
}