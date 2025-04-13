<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Printify API rate limiter with exponential backoff retry strategy
 */
class PrintifyRateLimiter implements RateLimiterInterface {
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var array API request counts by endpoint
     */
    private $request_counts;
    
    /**
     * @var array Failed request counts by endpoint
     */
    private $failed_requests;
    
    /**
     * @var array Retry timestamps by endpoint
     */
    private $retry_after;
    
    /**
     * @var array Rate limits by endpoint (requests per minute)
     */
    private $rate_limits;
    
    /**
     * @var int Maximum number of retries
     */
    private $max_retries;
    
    /**
     * @var int Base delay for exponential backoff in seconds
     */
    private $base_delay;
    
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->request_counts = [];
        $this->failed_requests = [];
        $this->retry_after = [];
        
        // Load configuration
        $this->loadConfig();
        
        // Load stored rate limit data
        $this->loadState();
    }
    
    /**
     * Load rate limiter configuration
     */
    private function loadConfig() {
        // Default rate limits (requests per minute by endpoint)
        $this->rate_limits = [
            'default' => get_option('wpwps_default_rate_limit', 60),
            'products' => get_option('wpwps_products_rate_limit', 30),
            'orders' => get_option('wpwps_orders_rate_limit', 30),
            'catalog' => get_option('wpwps_catalog_rate_limit', 20),
        ];
        
        $this->max_retries = get_option('wpwps_max_retries', 5);
        $this->base_delay = get_option('wpwps_base_delay', 2);
    }
    
    /**
     * Check if request should be rate limited
     *
     * @param string $endpoint API endpoint
     * @return bool Whether the request should be limited
     */
    public function shouldLimit($endpoint) {
        $endpoint_key = $this->getEndpointKey($endpoint);
        
        // Check if we're in a retry waiting period
        if (isset($this->retry_after[$endpoint_key])) {
            $retry_timestamp = $this->retry_after[$endpoint_key];
            
            if (time() < $retry_timestamp) {
                $this->logger->log_info(
                    'rate_limit',
                    sprintf('Rate limited %s - retry after %s', $endpoint, date('H:i:s', $retry_timestamp))
                );
                return true; // Yes, limit this request
            }
            
            // Retry period has passed
            unset($this->retry_after[$endpoint_key]);
        }
        
        // Check request counts against rate limits
        $rate_limit = $this->getRateLimit($endpoint_key);
        
        if (isset($this->request_counts[$endpoint_key])) {
            $count = $this->request_counts[$endpoint_key]['count'];
            $timestamp = $this->request_counts[$endpoint_key]['timestamp'];
            
            // Reset counter if it's been more than a minute
            if (time() - $timestamp >= 60) {
                $this->request_counts[$endpoint_key] = [
                    'count' => 0,
                    'timestamp' => time(),
                ];
                $this->saveState();
                return false; // Don't limit
            }
            
            // If we've reached the limit, apply rate limiting
            if ($count >= $rate_limit) {
                $this->logger->log_info(
                    'rate_limit',
                    sprintf('Rate limited %s - exceeded %d requests per minute', $endpoint, $rate_limit)
                );
                return true; // Yes, limit this request
            }
        }
        
        return false; // Don't limit
    }
    
    /**
     * Record a successful API request
     *
     * @param string $endpoint API endpoint
     * @param array $response_headers Response headers
     * @return void
     */
    public function recordSuccess($endpoint, $response_headers = []) {
        $endpoint_key = $this->getEndpointKey($endpoint);
        
        // Update request count
        if (!isset($this->request_counts[$endpoint_key])) {
            $this->request_counts[$endpoint_key] = [
                'count' => 1,
                'timestamp' => time(),
            ];
        } else {
            // Reset if it's been more than a minute
            if (time() - $this->request_counts[$endpoint_key]['timestamp'] >= 60) {
                $this->request_counts[$endpoint_key] = [
                    'count' => 1,
                    'timestamp' => time(),
                ];
            } else {
                $this->request_counts[$endpoint_key]['count']++;
            }
        }
        
        // Reset failure count on success
        if (isset($this->failed_requests[$endpoint_key])) {
            unset($this->failed_requests[$endpoint_key]);
        }
        
        // Check for rate limit headers from API
        if (!empty($response_headers)) {
            $this->processRateLimitHeaders($endpoint_key, $response_headers);
        }
        
        $this->saveState();
    }
    
    /**
     * Record a failed API request
     *
     * @param string $endpoint API endpoint
     * @param string $error_message Error message
     * @param int $status_code HTTP status code
     * @return void
     */
    public function recordFailure($endpoint, $error_message, $status_code = 0) {
        $endpoint_key = $this->getEndpointKey($endpoint);
        
        // Check if this is a rate limiting error
        $is_rate_limit_error = $status_code == 429 || 
                              strpos(strtolower($error_message), 'rate limit') !== false ||
                              strpos(strtolower($error_message), 'too many requests') !== false;
        
        // Increment failure count
        if (!isset($this->failed_requests[$endpoint_key])) {
            $this->failed_requests[$endpoint_key] = [
                'count' => 1,
                'timestamp' => time(),
                'last_error' => $error_message,
                'status_code' => $status_code
            ];
        } else {
            $this->failed_requests[$endpoint_key]['count']++;
            $this->failed_requests[$endpoint_key]['last_error'] = $error_message;
            $this->failed_requests[$endpoint_key]['status_code'] = $status_code;
        }
        
        // Calculate retry delay using exponential backoff
        $retry_count = $this->failed_requests[$endpoint_key]['count'];
        $delay = $this->calculateBackoff($retry_count, $is_rate_limit_error);
        
        // Set retry timestamp
        $this->retry_after[$endpoint_key] = time() + $delay;
        
        $this->logger->log_warning(
            'rate_limit',
            sprintf(
                'API request to %s failed with status %d: %s. Retry after %d seconds (attempt %d/%d)',
                $endpoint,
                $status_code,
                $error_message,
                $delay,
                $retry_count,
                $this->max_retries
            )
        );
        
        $this->saveState();
    }
    
    /**
     * Get retry delay for endpoint
     *
     * @param string $endpoint API endpoint
     * @return int Seconds to wait before retry
     */
    public function getRetryDelay($endpoint) {
        $endpoint_key = $this->getEndpointKey($endpoint);
        
        if (isset($this->retry_after[$endpoint_key])) {
            $delay = max(0, $this->retry_after[$endpoint_key] - time());
            return $delay;
        }
        
        return 0;
    }
    
    /**
     * Calculate exponential backoff delay
     *
     * @param int $retry_count The number of retries so far
     * @param bool $is_rate_limit_error Whether this is a rate limiting error
     * @return int Seconds to wait before retry
     */
    private function calculateBackoff($retry_count, $is_rate_limit_error) {
        // If we've exceeded max retries, use a longer penalty
        if ($retry_count > $this->max_retries) {
            return 3600; // 1 hour cooldown
        }
        
        // Rate limit errors get a longer base delay
        $base = $is_rate_limit_error ? 30 : $this->base_delay;
        
        // Exponential backoff with jitter
        // Formula: base * 2^(retry_count - 1) + random(0, min(15, delay))
        $delay = $base * pow(2, min($retry_count - 1, 8));
        
        // Add random jitter to prevent thundering herd problem
        $delay = $delay + rand(0, min($delay, 15));
        
        return $delay;
    }
    
    /**
     * Get endpoint key for rate limiting
     *
     * @param string $endpoint API endpoint
     * @return string Endpoint key
     */
    private function getEndpointKey($endpoint) {
        // Extract category from URL path
        if (preg_match('#/([^/]+)/#', $endpoint, $matches)) {
            $category = $matches[1];
            
            if (in_array($category, ['products', 'orders', 'shops', 'catalog'])) {
                return $category;
            }
        }
        
        return 'default';
    }
    
    /**
     * Get rate limit for endpoint
     *
     * @param string $endpoint_key Endpoint key
     * @return int Rate limit (requests per minute)
     */
    private function getRateLimit($endpoint_key) {
        if (isset($this->rate_limits[$endpoint_key])) {
            return $this->rate_limits[$endpoint_key];
        }
        
        return $this->rate_limits['default'];
    }
    
    /**
     * Process rate limit headers from API response
     *
     * @param string $endpoint_key Endpoint key
     * @param array $headers Response headers
     */
    private function processRateLimitHeaders($endpoint_key, $headers) {
        // Normalize header array keys to lowercase for case-insensitive matching
        $normalized_headers = array_change_key_case($headers, CASE_LOWER);
        
        // Check for Retry-After header
        if (isset($normalized_headers['retry-after']) && is_numeric($normalized_headers['retry-after'])) {
            $retry_seconds = (int)$normalized_headers['retry-after'];
            if ($retry_seconds > 0) {
                $this->retry_after[$endpoint_key] = time() + $retry_seconds;
                $this->logger->log_info(
                    'rate_limit',
                    sprintf('API requested retry after %d seconds for %s', $retry_seconds, $endpoint_key)
                );
            }
        }
        
        // Log remaining rate limit if available
        if (isset($normalized_headers['x-ratelimit-remaining'])) {
            $this->logger->log_debug(
                'rate_limit',
                sprintf('Remaining rate limit for %s: %s', $endpoint_key, $normalized_headers['x-ratelimit-remaining'])
            );
        }
    }
    
    /**
     * Save rate limiter state to database
     */
    private function saveState() {
        $state = [
            'request_counts' => $this->request_counts,
            'failed_requests' => $this->failed_requests,
            'retry_after' => $this->retry_after,
            'updated' => time()
        ];
        
        update_option('wpwps_rate_limiter_state', $state, false);
    }
    
    /**
     * Load rate limiter state from database
     */
    private function loadState() {
        $state = get_option('wpwps_rate_limiter_state', []);
        
        if (isset($state['request_counts'])) {
            $this->request_counts = $state['request_counts'];
        }
        
        if (isset($state['failed_requests'])) {
            $this->failed_requests = $state['failed_requests'];
        }
        
        if (isset($state['retry_after'])) {
            $this->retry_after = $state['retry_after'];
        }
        
        // Clean up expired entries
        $this->cleanupState();
    }
    
    /**
     * Clean up expired entries from state
     */
    private function cleanupState() {
        $now = time();
        
        // Clean up old request counts
        foreach ($this->request_counts as $key => $data) {
            if ($now - $data['timestamp'] > 3600) { // 1 hour
                unset($this->request_counts[$key]);
            }
        }
        
        // Clean up old failed requests
        foreach ($this->failed_requests as $key => $data) {
            if ($now - $data['timestamp'] > 86400) { // 24 hours
                unset($this->failed_requests[$key]);
            }
        }
        
        // Clean up expired retry timestamps
        foreach ($this->retry_after as $key => $timestamp) {
            if ($now > $timestamp) {
                unset($this->retry_after[$key]);
            }
        }
    }
}
