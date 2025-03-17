<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\API;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class EnhancedRateLimiter
{
    use TimeStampTrait;

    private const CACHE_GROUP = 'printify_rate_limit';
    
    // Rate limits per endpoint type
    private const LIMITS = [
        'products' => ['requests' => 60, 'window' => 60], // 60 requests per minute
        'orders' => ['requests' => 120, 'window' => 60],  // 120 requests per minute
        'webhooks' => ['requests' => 30, 'window' => 60], // 30 requests per minute
        'default' => ['requests' => 40, 'window' => 60]   // 40 requests per minute
    ];

    // Backoff strategy
    private const BACKOFF_STRATEGY = [
        1 => 1,    // 1st retry - wait 1 second
        2 => 2,    // 2nd retry - wait 2 seconds
        3 => 4,    // 3rd retry - wait 4 seconds
        4 => 8,    // 4th retry - wait 8 seconds
        5 => 16    // 5th retry - wait 16 seconds
    ];

    private CacheInterface $cache;
    private LoggerInterface $logger;
    private ConfigService $config;

    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function executeWithRetry(callable $operation, string $endpoint, array $params = []): mixed
    {
        $maxRetries = $this->config->get('api_max_retries', 5);
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                if ($this->checkRateLimit($endpoint)) {
                    return $operation();
                }

                $this->handleRateLimitExceeded($endpoint, $attempt);
                $attempt++;

            } catch (\Exception $e) {
                $lastException = $e;
                $shouldRetry = $this->shouldRetryException($e);
                
                if (!$shouldRetry || $attempt >= $maxRetries - 1) {
                    throw $e;
                }

                $this->handleRetry($endpoint, $attempt, $e);
                $attempt++;
            }
        }

        throw new RateLimitException(
            'Max retries exceeded',
            $lastException ? $lastException->getCode() : 429
        );
    }

    private function checkRateLimit(string $endpoint): bool
    {
        $limits = $this->getLimitsForEndpoint($endpoint);
        $key = $this->getCacheKey($endpoint);
        $current = $this->getCurrentRequests($key);

        if ($current >= $limits['requests']) {
            $this->logRateLimitExceeded($endpoint, $current, $limits);
            return false;
        }

        $this->incrementRequests($key, $limits['window']);
        return true;
    }

    private function getLimitsForEndpoint(string $endpoint): array
    {
        foreach (self::LIMITS as $type => $limits) {
            if (strpos($endpoint, $type) !== false) {
                return $limits;
            }
        }
        return self::LIMITS['default'];
    }

    private function getCacheKey(string $endpoint): string
    {
        return sprintf(
            'rate_limit:%s:%s:%s',
            md5($endpoint),
            $this->getCurrentUser(),
            floor(time() / 60)
        );
    }

    private function getCurrentRequests(string $key): int
    {
        return (int) $this->cache->get($key, self::CACHE_GROUP) ?: 0;
    }

    private function incrementRequests(string $key, int $window): void
    {
        $current = $this->getCurrentRequests($key);
        $this->cache->set(
            $key,
            $current + 1,
            $window,
            self::CACHE_GROUP
        );
    }

    private function handleRateLimitExceeded(string $endpoint, int $attempt): void
    {
        $backoffTime = $this->calculateBackoffTime($attempt);
        
        $this->logger->warning('Rate limit exceeded, backing off', [
            'endpoint' => $endpoint,
            'attempt' => $attempt + 1,
            'backoff_time' => $backoffTime,
            'timestamp' => $this->getCurrentTime()
        ]);

        usleep($backoffTime * 1000000); // Convert to microseconds
    }

    private function calculateBackoffTime(int $attempt): int
    {
        if (isset(self::BACKOFF_STRATEGY[$attempt + 1])) {
            return self::BACKOFF_STRATEGY[$attempt + 1];
        }
        
        // For attempts beyond our strategy, use exponential backoff
        return min(pow(2, $attempt), 32); // Max 32 seconds
    }

    private function shouldRetryException(\Exception $e): bool
    {
        $retryableCodes = [
            408, // Request Timeout
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504  // Gateway Timeout
        ];

        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return true;
        }

        if ($e instanceof \GuzzleHttp\Exception\RequestException) {
            return in_array($e->getCode(), $retryableCodes);
        }

        return false;
    }

    private function handleRetry(string $endpoint, int $attempt, \Exception $e): void
    {
        $backoffTime = $this->calculateBackoffTime($attempt);
        
        $this->logger->warning('Request failed, retrying', [
            'endpoint' => $endpoint,
            'attempt' => $attempt + 1,
            'backoff_time' => $backoffTime,
            'error' => $e->getMessage(),
            'timestamp' => $this->getCurrentTime()
        ]);

        usleep($backoffTime * 1000000);
    }

    private function logRateLimitExceeded(string $endpoint, int $current, array $limits): void
    {
        $this->logger->warning('Rate limit exceeded', [
            'endpoint' => $endpoint,
            'current_requests' => $current,
            'limit' => $limits['requests'],
            'window' => $limits['window'],
            'user' => $this->getCurrentUser(),
            'timestamp' => $this->getCurrentTime()
        ]);
    }
}