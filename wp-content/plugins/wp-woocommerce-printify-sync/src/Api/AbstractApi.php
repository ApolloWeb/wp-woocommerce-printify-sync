<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

abstract class AbstractApi
{
    protected string $currentTime = '2025-03-15 18:59:27';
    protected string $currentUser = 'ApolloWeb';
    
    protected string $apiKey;
    protected string $endpoint;
    protected int $retryAttempts = 3;
    protected int $retryDelay = 5;
    protected array $rateLimits = [];
    protected array $headers = [];

    public function __construct(string $apiKey, string $endpoint)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = rtrim($endpoint, '/');
        $this->initializeHeaders();
    }

    abstract protected function initializeHeaders(): void;

    protected function request(string $method, string $path, array $params = []): array
    {
        $url = $this->endpoint . '/' . ltrim($path, '/');
        $attempts = 0;

        do {
            try {
                $response = $this->makeRequest($method, $url, $params);
                $this->handleRateLimit($response);
                return $this->parseResponse($response);
            } catch (\Exception $e) {
                $attempts++;
                if ($attempts >= $this->retryAttempts) {
                    throw $e;
                }
                sleep($this->retryDelay * $attempts);
            }
        } while ($attempts < $this->retryAttempts);

        throw new \Exception('Maximum retry attempts reached');
    }

    protected function makeRequest(string $method, string $url, array $params = []): array
    {
        $args = [
            'method' => $method,
            'headers' => $this->headers,
            'timeout' => 30,
        ];

        if ($method === 'GET') {
            $url = add_query_arg($params, $url);
        } else {
            $args['body'] = json_encode($params);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        return [
            'body' => wp_remote_retrieve_body($response),
            'headers' => wp_remote_retrieve_headers($response),
            'code' => wp_remote_retrieve_response_code($response)
        ];
    }

    protected function parseResponse(array $response): array
    {
        $body = json_decode($response['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response');
        }

        if ($response['code'] >= 400) {
            throw new \Exception($body['message'] ?? 'API request failed');
        }

        return $body;
    }

    protected function handleRateLimit(array $response): void
    {
        $headers = $response['headers'];
        
        $this->rateLimits = [
            'limit' => (int) ($headers['x-ratelimit-limit'] ?? 0),
            'remaining' => (int) ($headers['x-ratelimit-remaining'] ?? 0),
            'reset' => (int) ($headers['x-ratelimit-reset'] ?? 0)
        ];

        if ($this->rateLimits['remaining'] <= 0) {
            $waitTime = $this->rateLimits['reset'] - time();
            if ($waitTime > 0) {
                sleep($waitTime);
            }
        }
    }

    protected function logRequest(string $method, string $url, array $params, array $response): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_api_log',
            [
                'api_type' => static::class,
                'method' => $method,
                'url' => $url,
                'params' => json_encode($params),
                'response_code' => $response['code'],
                'response_body' => $response['body'],
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );
    }
}