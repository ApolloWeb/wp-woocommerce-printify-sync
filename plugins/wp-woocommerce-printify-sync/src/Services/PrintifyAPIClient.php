<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\APIClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Exceptions\APIException;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class PrintifyAPIClient implements APIClientInterface
{
    use TimeStampTrait;

    private const BASE_URL = 'https://api.printify.com/v1';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1; // seconds

    private string $apiKey;
    private LoggerInterface $logger;
    private array $rateLimits = [];
    private array $lastResponse = [];

    public function __construct(string $apiKey, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    public function getRateLimit(): array
    {
        return $this->rateLimits;
    }

    public function isRateLimited(): bool
    {
        return isset($this->rateLimits['remaining']) && $this->rateLimits['remaining'] <= 0;
    }

    private function request(string $method, string $endpoint, array $options = []): array
    {
        $url = self::BASE_URL . '/' . ltrim($endpoint, '/');
        $attempts = 0;

        do {
            try {
                $response = wp_remote_request($url, $this->prepareRequestArgs($method, $options));
                
                if (is_wp_error($response)) {
                    throw new APIException($response->get_error_message());
                }

                $this->updateRateLimits($response);
                $this->lastResponse = $response;

                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new APIException('Invalid JSON response');
                }

                $statusCode = wp_remote_retrieve_response_code($response);
                if ($statusCode >= 400) {
                    throw new APIException(
                        $data['message'] ?? 'API request failed',
                        $statusCode
                    );
                }

                $this->logSuccess($method, $endpoint, $statusCode);
                return $data;

            } catch (APIException $e) {
                $attempts++;
                $this->logError($method, $endpoint, $e);

                if ($this->shouldRetry($e, $attempts)) {
                    sleep(self::RETRY_DELAY * $attempts);
                    continue;
                }

                throw $e;
            }
        } while ($attempts < self::MAX_RETRIES);

        throw new APIException('Max retry attempts reached');
    }

    private function prepareRequestArgs(string $method, array $options): array
    {
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ];

        if (isset($options['json'])) {
            $args['body'] = json_encode($options['json']);
        }

        if (isset($options['query'])) {
            $args['query'] = http_build_query($options['query']);
        }

        return $args;
    }

    private function updateRateLimits(array $response): void
    {
        $headers = wp_remote_retrieve_headers($response);
        
        $this->rateLimits = [
            'limit' => (int)($headers['X-RateLimit-Limit'] ?? 0),
            'remaining' => (int)($headers['X-RateLimit-Remaining'] ?? 0),
            'reset' => (int)($headers['X-RateLimit-Reset'] ?? 0)
        ];
    }

    private function shouldRetry(APIException $e, int $attempts): bool
    {
        if ($attempts >= self::MAX_RETRIES) {
            return false;
        }

        $statusCode = $e->getCode();
        return $statusCode === 429 || ($statusCode >= 500 && $statusCode < 600);
    }

    private function logSuccess(string $method, string $endpoint, int $statusCode): void
    {
        $this->logger->info('API request successful', $this->addTimeStampData([
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'rate_limit_remaining' => $this->rateLimits['remaining']
        ]));
    }

    private function logError(string $method, string $endpoint, APIException $e): void
    {
        $this->logger->error('API request failed', $this->addTimeStampData([
            'method' => $method,
            'endpoint' => $endpoint,
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage()
        ]));
    }
}