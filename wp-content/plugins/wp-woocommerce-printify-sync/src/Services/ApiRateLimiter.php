<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ApiRateLimiter
{
    private const RATE_LIMIT_KEY = 'wpwps_api_rate_limit';
    private const MAX_RETRIES = 3;
    private const INITIAL_BACKOFF = 1; // seconds
    
    private string $currentTime;
    private string $currentUser;
    private array $rateLimits;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:14:34
        $this->currentUser = $currentUser; // ApolloWeb
        $this->rateLimits = get_option(self::RATE_LIMIT_KEY, []);
    }

    public function executeWithRetry(callable $apiCall, string $endpoint): mixed
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $this->waitIfNeeded($endpoint);
                $response = $apiCall();
                $this->updateRateLimit($endpoint, $response);
                return $response;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                
                if ($this->shouldRetry($e)) {
                    $backoffTime = $this->calculateBackoff($attempts);
                    $this->logRetryAttempt($endpoint, $attempts, $backoffTime, $e);
                    sleep($backoffTime);
                    continue;
                }
                
                break;
            }
        }

        $this->logFailedAttempts($endpoint, $attempts, $lastException);
        throw $lastException;
    }

    private function waitIfNeeded(string $endpoint): void
    {
        if (!isset($this->rateLimits[$endpoint])) {
            return;
        }

        $limit = $this->rateLimits[$endpoint];
        $currentTime = time();

        if ($currentTime < $limit['reset_at']) {
            if ($limit['remaining'] <= 0) {
                $sleepTime = $limit['reset_at'] - $currentTime;
                $this->logRateLimitWait($endpoint, $sleepTime);
                sleep($sleepTime);
            }
        }
    }

    private function updateRateLimit(string $endpoint, $response): void
    {
        // Extract rate limit headers from response
        $headers = wp_remote_retrieve_headers($response);
        
        $this->rateLimits[$endpoint] = [
            'limit' => (int) ($headers['X-RateLimit-Limit'] ?? 100),
            'remaining' => (int) ($headers['X-RateLimit-Remaining'] ?? 99),
            'reset_at' => (int) ($headers['X-RateLimit-Reset'] ?? (time() + 60)),
            'last_updated' => $this->currentTime
        ];

        update_option(self::RATE_LIMIT_KEY, $this->rateLimits);
    }

    private function shouldRetry(\Exception $e): bool
    {
        $retryableCodes = [429, 500, 502, 503, 504];
        return in_array($e->getCode(), $retryableCodes);
    }

    private function calculateBackoff(int $attempt): int
    {
        return self::INITIAL_BACKOFF * (2 ** ($attempt - 1)) + rand(0, 1000) / 1000;
    }

    private function logRetryAttempt(string $endpoint, int $attempt, int $backoff, \Exception $e): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_api_retry_log',
            [
                'endpoint' => $endpoint,
                'attempt' => $attempt,
                'backoff_time' => $backoff,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%s', '%d', '%d', '%d', '%s', '%s', '%s']
        );
    }

    private function logRateLimitWait(string $endpoint, int $waitTime): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_rate_limit_log',
            [
                'endpoint' => $endpoint,
                'wait_time' => $waitTime,
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%s', '%d', '%s', '%s']
        );
    }

    private function logFailedAttempts(string $endpoint, int $attempts, ?\Exception $lastException): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_api_failure_log',
            [
                'endpoint' => $endpoint,
                'attempts' => $attempts,
                'final_error' => $lastException ? $lastException->getMessage() : '',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );
    }
}