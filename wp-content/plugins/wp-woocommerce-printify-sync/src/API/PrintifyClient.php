<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\API\Exceptions\PrintifyApiException;

class PrintifyClient {
    private const DEFAULT_URL = 'https://api.printify.com/v1';
    private const RATE_LIMIT = 600; // Requests per minute
    private const RATE_WINDOW = 60; // Window in seconds
    
    private string $apiKey;
    private string $shopId;
    private string $baseUrl;

    public function __construct(string $apiKey, ?string $shopId = null, ?string $baseUrl = null) {
        $this->apiKey = $apiKey;
        $this->shopId = $shopId;
        $this->baseUrl = $this->normalizeUrl($baseUrl ?? self::DEFAULT_URL);
    }

    private function normalizeUrl(string $url): string {
        // Remove trailing slashes and ensure v1 is in the path
        $url = rtrim($url, '/');
        if (!str_ends_with($url, '/v1')) {
            $url = rtrim($url, '/v1') . '/v1';
        }
        return $url;
    }

    public function getShops(): array {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Getting shops from Printify API');
        }
        return $this->request('GET', 'shops.json');
    }

    public function getProducts(int $page = 1, int $limit = 100): array {
        return $this->request('GET', "shops/{$this->shopId}/products.json", [
            'page' => $page,
            'limit' => $limit
        ]);
    }

    private function checkRateLimit(): bool {
        $transient_key = 'printify_api_requests';
        $requests = get_transient($transient_key);
        
        if (!$requests) {
            $requests = ['count' => 0, 'timestamp' => time()];
        }

        // Reset counter if window has passed
        if ((time() - $requests['timestamp']) > self::RATE_WINDOW) {
            $requests = ['count' => 0, 'timestamp' => time()];
        }

        // Check if we've hit the limit
        if ($requests['count'] >= self::RATE_LIMIT) {
            $wait_time = self::RATE_WINDOW - (time() - $requests['timestamp']);
            throw new PrintifyApiException(
                sprintf('Rate limit exceeded. Please wait %d seconds.', $wait_time),
                429
            );
        }

        // Increment counter and save
        $requests['count']++;
        set_transient($transient_key, $requests, self::RATE_WINDOW);
        
        return true;
    }

    private function request(string $method, string $endpoint, array $params = []): array {
        try {
            $this->checkRateLimit();
            
            // Remove leading slash from endpoint if present
            $endpoint = ltrim($endpoint, '/');
            $url = $this->baseUrl . '/' . $endpoint;
            
            if ($method === 'GET' && !empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Printify API Request - Method: {$method}, URL: {$url}");
            }

            $args = [
                'method' => $method,
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'WP WooCommerce Printify Sync/1.0.0'
                ],
                'timeout' => 30,
                'sslverify' => true
            ];

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Request args: " . print_r($args, true));
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                throw new PrintifyApiException($response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $statusCode = wp_remote_retrieve_response_code($response);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Response status: {$statusCode}");
                error_log("Response body: {$body}");
            }

            $data = json_decode($body, true);

            if ($statusCode >= 400) {
                throw new PrintifyApiException(
                    $data['message'] ?? 'Unknown API error',
                    $statusCode,
                    $data['errors'] ?? []
                );
            }

            return $data;
        } catch (PrintifyApiException $e) {
            if ($e->getCode() === 429) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Printify API rate limit hit: ' . $e->getMessage());
                }
                throw $e;
            }
            throw $e;
        }
    }
}
