<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class PrintifyAPI
{
    use LoggerAwareTrait;

    private AppContext $context;
    private string $apiKey;
    private string $baseUrl;
    private int $retryAttempts = 3;
    private int $retryDelay = 5;

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        $this->apiKey = get_option('wpwps_printify_api_key', '');
        $this->baseUrl = $this->context->isProduction()
            ? 'https://api.printify.com/v1'
            : 'https://api.staging.printify.com/v1';
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [], $data);
    }

    private function request(
        string $method,
        string $endpoint,
        array $params = [],
        array $data = []
    ): array {
        $attempts = 0;
        $lastError = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->makeRequest($method, $endpoint, $params, $data);
                
                $this->log('info', 'API request successful', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'attempts' => $attempts + 1
                ]);

                return $response;

            } catch (\Exception $e) {
                $lastError = $e;
                $attempts++;

                if ($this->shouldRetry($e) && $attempts < $this->retryAttempts) {
                    sleep($this->getRetryDelay($attempts));
                    continue;
                }

                break;
            }
        }

        $this->log('error', 'API request failed after retries', [
            'endpoint' => $endpoint,
            'method' => $method,
            'attempts' => $attempts,
            'error' => $lastError->getMessage()
        ]);

        throw $lastError;
    }

    private function makeRequest(
        string $method,
        string $endpoint,
        array $params,
        array $data
    ): array {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ];

        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode >= 400) {
            throw new \Exception(
                "API request failed: " . $body,
                $statusCode
            );
        }

        return json_decode($body, true);
    }

    private function shouldRetry(\Exception $e): bool
    {
        $retryableCodes = [408, 429, 500, 502, 503, 504];
        return in_array($e->getCode(), $retryableCodes);
    }

    private function getRetryDelay(int $attempt): int
    {
        return $this->retryDelay * (2 ** ($attempt - 1));
    }
}