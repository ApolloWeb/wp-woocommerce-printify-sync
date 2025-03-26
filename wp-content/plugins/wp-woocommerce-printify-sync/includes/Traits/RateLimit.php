<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Traits;

trait RateLimit {
    private $max_retries;
    private $retry_delay;
    private $rate_limit_buffer;
    private $rate_limit_remaining;
    private $rate_limit_reset;

    public function setMaxRetries(int $retries): void {
        $this->max_retries = $retries;
    }

    public function setRetryDelay(int $delay): void {
        $this->retry_delay = $delay;
    }

    public function setRateLimitBuffer(int $buffer): void {
        $this->rate_limit_buffer = $buffer;
    }

    public function getRateLimitInfo(): array {
        return [
            'remaining' => $this->rate_limit_remaining,
            'reset' => $this->rate_limit_reset,
            'buffer' => $this->rate_limit_buffer
        ];
    }

    protected function updateRateLimits(array $headers): void {
        $this->rate_limit_remaining = isset($headers['x-ratelimit-remaining']) 
            ? (int) $headers['x-ratelimit-remaining'] 
            : null;
        
        $this->rate_limit_reset = isset($headers['x-ratelimit-reset']) 
            ? (int) $headers['x-ratelimit-reset'] 
            : null;
    }

    protected function shouldRetry(int $attempt, int $response_code): bool {
        if ($attempt >= $this->max_retries) {
            return false;
        }

        // Retry on rate limit exceeded or server errors
        if ($response_code === 429 || $response_code >= 500) {
            if ($response_code === 429 && $this->rate_limit_reset) {
                // Wait until rate limit resets
                sleep(max(0, $this->rate_limit_reset - time()));
            } else {
                // Exponential backoff
                sleep($this->retry_delay * pow(2, $attempt));
            }
            return true;
        }

        return false;
    }

    protected function isRateLimitCritical(): bool {
        return $this->rate_limit_remaining !== null && 
               $this->rate_limit_remaining <= $this->rate_limit_buffer;
    }
}