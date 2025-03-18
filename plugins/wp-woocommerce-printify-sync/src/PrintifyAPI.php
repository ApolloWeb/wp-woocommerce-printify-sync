<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

class PrintifyAPI
{
    private $apiKey;
    private $httpClient;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
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

    public function createWebhook($url, $event)
    {
        $endpoint = 'https://api.printify.com/v1/webhooks.json';
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
}