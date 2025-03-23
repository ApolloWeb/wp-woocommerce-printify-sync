<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;

/**
 * API Rate Limiter with exponential backoff and retry capabilities
 */
class ApiRateLimiter {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * @var array Current API rate limits
     */
    private $limits = [];
    
    /**
     * @var array Retry queue
     */
    private $retry_queue = [];
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger, Settings $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
        
        // Load retry queue if exists
        $this->retry_queue = get_option('wpwps_api_retry_queue', []);
        
        // Set up scheduled retry handler
        add_action('wpwps_process_api_retry_queue', [$this, 'processRetryQueue']);
    }
    
    /**
     * Initialize the rate limiter
     */
    public function init(): void {
        // Reset daily counters at midnight UTC
        $this->maybeResetDailyCounters();
        
        // Schedule retry processing if needed and not already scheduled
        if (!empty($this->retry_queue) && !wp_next_scheduled('wpwps_process_api_retry_queue')) {
            $this->scheduleRetryProcessing();
        }
    }
    
    /**
     * Check if a request is allowed based on rate limits
     *
     * @param string $endpoint API endpoint
     * @return bool Whether the request is allowed
     */
    public function isRequestAllowed(string $endpoint): bool {
        // Check if we're rate limited globally
        if ($this->isGloballyRateLimited()) {
            return false;
        }
        
        // Check daily limit
        $daily_limit = (int) $this->settings->get('api_daily_limit', 5000);
        $daily_count = (int) get_option('wpwps_api_calls_today', 0);
        
        if ($daily_count >= $daily_limit) {
            $this->logger->log("API daily limit of {$daily_limit} requests reached", 'warning');
            update_option('wpwps_api_rate_limited', true);
            return false;
        }
        
        // Check per-minute limit
        $per_minute_limit = (int) $this->settings->get('api_per_minute_limit', 60);
        $minute_count = $this->getRequestCountInLastMinute();
        
        if ($minute_count >= $per_minute_limit) {
            $this->logger->log("API per-minute limit of {$per_minute_limit} requests reached", 'warning');
            return false;
        }
        
        // Endpoint-specific limits could be added here in the future
        
        return true;
    }
    
    /**
     * Record a successful API request
     *
     * @param string $endpoint API endpoint
     * @param array $response API response
     */
    public function recordSuccess(string $endpoint, array $response): void {
        // Increment daily counter
        $daily_count = (int) get_option('wpwps_api_calls_today', 0);
        update_option('wpwps_api_calls_today', $daily_count + 1);
        
        // Record per-minute request
        $this->recordRequestTime();
        
        // Update rate limit info from response headers if available
        if (isset($response['headers'])) {
            $this->updateRateLimits($response['headers']);
        }
    }
    
    /**
     * Handle a rate limit error
     *
     * @param string $endpoint API endpoint
     * @param array $request_data Original request data
     * @param int $status_code HTTP status code
     * @param array $headers Response headers
     */
    public function handleRateLimitError(string $endpoint, array $request_data, int $status_code, array $headers = []): void {
        $this->logger->log("Rate limit reached for {$endpoint} (HTTP {$status_code})", 'warning');
        
        // Update rate limit info from headers if available
        if (!empty($headers)) {
            $this->updateRateLimits($headers);
        }
        
        // Determine retry delay based on status code and headers
        $retry_delay = $this->calculateRetryDelay($status_code, $headers);
        
        // Mark as globally rate limited if needed
        if ($status_code === 429) { // Too Many Requests
            $this->markAsRateLimited($retry_delay);
        }
        
        // Add to retry queue
        $this->addToRetryQueue($endpoint, $request_data, $retry_delay);
    }
    
    /**
     * Record an API failure
     *
     * @param string $endpoint API endpoint
     * @param array $request_data Original request data
     * @param int $status_code HTTP status code
     * @param string $error_message Error message
     */
    public function recordFailure(string $endpoint, array $request_data, int $status_code, string $error_message): void {
        // Determine if this is a rate limit error
        if ($status_code === 429 || $status_code === 403) {
            $this->handleRateLimitError($endpoint, $request_data, $status_code);
            return;
        }
        
        // Log other errors
        $this->logger->log("API error for {$endpoint}: {$error_message} (HTTP {$status_code})", 'error');
        
        // For server errors (5xx), add to retry queue with backoff
        if ($status_code >= 500 && $status_code < 600) {
            $retry_delay = $this->calculateRetryDelay($status_code);
            $this->addToRetryQueue($endpoint, $request_data, $retry_delay);
        }
    }
    
    /**
     * Add a request to the retry queue
     *
     * @param string $endpoint API endpoint
     * @param array $request_data Request data
     * @param int $retry_delay Delay in seconds
     */
    public function addToRetryQueue(string $endpoint, array $request_data, int $retry_delay = 60): void {
        $retry_time = time() + $retry_delay;
        
        $retry_item = [
            'id' => uniqid('retry_'),
            'endpoint' => $endpoint,
            'data' => $request_data,
            'retry_time' => $retry_time,
            'attempts' => isset($request_data['_retry_attempts']) ? $request_data['_retry_attempts'] + 1 : 1
        ];
        
        // Add to queue
        $this->retry_queue[] = $retry_item;
        
        // Sort queue by retry time
        usort($this->retry_queue, function($a, $b) {
            return $a['retry_time'] <=> $b['retry_time'];
        });
        
        // Update stats
        update_option('wpwps_api_retry_queue', $this->retry_queue);
        update_option('wpwps_api_retry_queue_count', count($this->retry_queue));
        update_option('wpwps_api_next_retry', empty($this->retry_queue) ? 0 : $this->retry_queue[0]['retry_time']);
        
        // Schedule retry processing if not already scheduled
        if (!wp_next_scheduled('wpwps_process_api_retry_queue')) {
            $this->scheduleRetryProcessing();
        }
        
        $this->logger->log(sprintf(
            "Added request to retry queue for endpoint: %s (retry in %d seconds, attempt %d)",
            $endpoint, 
            $retry_delay,
            $retry_item['attempts']
        ), 'info');
    }
    
    /**
     * Process the retry queue
     */
    public function processRetryQueue(): void {
        // Check if we're still globally rate limited
        if ($this->isGloballyRateLimited()) {
            $this->logger->log('Skipping retry queue processing due to active rate limiting', 'info');
            
            // Reschedule for later
            $this->scheduleRetryProcessing(true);
            return;
        }
        
        // Get the retry queue
        if (empty($this->retry_queue)) {
            $this->retry_queue = get_option('wpwps_api_retry_queue', []);
        }
        
        if (empty($this->retry_queue)) {
            $this->logger->log('No items in retry queue', 'debug');
            return;
        }
        
        $now = time();
        $processed = 0;
        $max_process = 10; // Maximum number of items to process in one batch
        $remaining_queue = [];
        
        $this->logger->log(sprintf('Processing retry queue - %d items', count($this->retry_queue)), 'info');
        
        foreach ($this->retry_queue as $item) {
            // Skip if it's not time to retry yet
            if ($item['retry_time'] > $now) {
                $remaining_queue[] = $item;
                continue;
            }
            
            // Skip if we've reached the max processing limit
            if ($processed >= $max_process) {
                $remaining_queue[] = $item;
                continue;
            }
            
            // Check if we can make a request
            if (!$this->isRequestAllowed($item['endpoint'])) {
                // Add back to queue with increased delay
                $item['retry_time'] = $now + $this->calculateRetryDelay(429, [], $item['attempts']);
                $remaining_queue[] = $item;
                continue;
            }
            
            // Process this item (dispatch to appropriate handler)
            $success = $this->dispatchRetry($item);
            
            if (!$success && $item['attempts'] < 5) { // Max 5 retries
                // Add back to queue with increased delay
                $item['attempts']++;
                $item['retry_time'] = $now + $this->calculateRetryDelay(500, [], $item['attempts']);
                $remaining_queue[] = $item;
            } else if (!$success) {
                // Log final failure
                $this->logger->log(sprintf(
                    "Giving up on retry for endpoint: %s after %d attempts",
                    $item['endpoint'],
                    $item['attempts']
                ), 'error');
            }
            
            $processed++;
        }
        
        // Update the queue
        $this->retry_queue = $remaining_queue;
        update_option('wpwps_api_retry_queue', $this->retry_queue);
        update_option('wpwps_api_retry_queue_count', count($this->retry_queue));
        update_option('wpwps_api_next_retry', empty($this->retry_queue) ? 0 : $this->retry_queue[0]['retry_time']);
        
        $this->logger->log(sprintf(
            'Retry queue processing complete - processed %d items, %d remaining',
            $processed,
            count($remaining_queue)
        ), 'info');
        
        // Schedule next run if there are still items
        if (!empty($remaining_queue)) {
            $this->scheduleRetryProcessing(true);
        }
    }
    
    /**
     * Dispatch a retry request to the appropriate handler
     *
     * @param array $item Retry queue item
     * @return bool Success
     */
    private function dispatchRetry(array $item): bool {
        try {
            $endpoint = $item['endpoint'];
            $data = $item['data'];
            
            // Add retry attempt count to data
            $data['_retry_attempts'] = $item['attempts'];
            
            // Determine the method based on the endpoint
            if (strpos($endpoint, '/products') !== false) {
                // Product API
                $api = new PrintifyApiClient($this->settings, $this->logger, $this);
                return $api->retry($endpoint, $data);
            } else if (strpos($endpoint, '/orders') !== false) {
                // Order API
                $api = new PrintifyApiClient($this->settings, $this->logger, $this);
                return $api->retry($endpoint, $data);
            }
            
            // Unknown endpoint
            $this->logger->log("Unknown endpoint for retry: {$endpoint}", 'error');
            return false;
        } catch (\Exception $e) {
            $this->logger->log("Error during retry: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Check if API is globally rate limited
     *
     * @return bool Whether API is globally rate limited
     */
    private function isGloballyRateLimited(): bool {
        $rate_limited = get_option('wpwps_api_rate_limited', false);
        
        if (!$rate_limited) {
            return false;
        }
        
        // Check if rate limit has expired
        $rate_limit_expiry = get_option('wpwps_api_rate_limit_expiry', 0);
        
        if ($rate_limit_expiry > 0 && $rate_limit_expiry <= time()) {
            // Rate limit has expired, clear it
            $this->clearRateLimit();
            return false;
        }
        
        return true;
    }
    
    /**
     * Mark API as rate limited for a period
     *
     * @param int $delay Delay in seconds
     */
    private function markAsRateLimited(int $delay): void {
        update_option('wpwps_api_rate_limited', true);
        update_option('wpwps_api_rate_limit_expiry', time() + $delay);
        
        $this->logger->log(sprintf(
            "API rate limited for %s",
            human_time_diff(time(), time() + $delay)
        ), 'warning');
    }
    
    /**
     * Clear rate limit status
     */
    private function clearRateLimit(): void {
        update_option('wpwps_api_rate_limited', false);
        update_option('wpwps_api_rate_limit_expiry', 0);
        
        $this->logger->log("API rate limit cleared", 'info');
    }
    
    /**
     * Calculate retry delay with exponential backoff
     *
     * @param int $status_code HTTP status code
     * @param array $headers Response headers
     * @param int $attempts Number of attempts so far
     * @return int Delay in seconds
     */
    private function calculateRetryDelay(int $status_code, array $headers = [], int $attempts = 1): int {
        // If Retry-After header is present, use that
        if (isset($headers['retry-after'])) {
            return (int) $headers['retry-after'];
        }
        
        // Rate limit specific delay
        if ($status_code === 429) {
            // Use reset time from headers if available
            if (isset($headers['x-ratelimit-reset'])) {
                $reset_time = (int) $headers['x-ratelimit-reset'];
                return max(60, $reset_time - time());
            }
            
            // Default rate limit delay with exponential backoff
            $base_delay = 60; // 1 minute
            return $base_delay * (2 ** ($attempts - 1));
        }
        
        // For other errors, use simple exponential backoff
        $base_delay = 30; // 30 seconds
        $max_delay = 3600; // 1 hour
        
        $delay = $base_delay * (2 ** ($attempts - 1));
        return min($delay, $max_delay);
    }
    
    /**
     * Schedule retry queue processing
     * 
     * @param bool $force Force rescheduling even if already scheduled
     */
    private function scheduleRetryProcessing(bool $force = false): void {
        if ($force) {
            wp_clear_scheduled_hook('wpwps_process_api_retry_queue');
        }
        
        if (!wp_next_scheduled('wpwps_process_api_retry_queue')) {
            // Schedule retry based on next item's retry time
            $next_retry = empty($this->retry_queue) ? time() + 60 : $this->retry_queue[0]['retry_time'];
            
            // Ensure it's not in the past
            $next_retry = max(time() + 30, $next_retry);
            
            wp_schedule_single_event($next_retry, 'wpwps_process_api_retry_queue');
            
            $this->logger->log(sprintf(
                "Scheduled retry queue processing for %s",
                date('Y-m-d H:i:s', $next_retry)
            ), 'debug');
        }
    }
    
    /**
     * Get number of API requests in the last minute
     * 
     * @return int Request count
     */
    private function getRequestCountInLastMinute(): int {
        $request_times = get_option('wpwps_api_request_times', []);
        $one_minute_ago = time() - 60;
        
        // Filter out requests older than 1 minute
        $request_times = array_filter($request_times, function($time) use ($one_minute_ago) {
            return $time >= $one_minute_ago;
        });
        
        // Update stored times
        update_option('wpwps_api_request_times', $request_times);
        
        return count($request_times);
    }
    
    /**
     * Record the time of an API request
     */
    private function recordRequestTime(): void {
        $request_times = get_option('wpwps_api_request_times', []);
        $request_times[] = time();
        
        // Keep only last 5 minutes of data to prevent option from growing too large
        $five_minutes_ago = time() - 300;
        $request_times = array_filter($request_times, function($time) use ($five_minutes_ago) {
            return $time >= $five_minutes_ago;
        });
        
        update_option('wpwps_api_request_times', $request_times);
    }
    
    /**
     * Update rate limit information from response headers
     * 
     * @param array $headers Response headers
     */
    private function updateRateLimits(array $headers): void {
        $limits = [];
        
        // Extract rate limit headers
        $headers_to_check = [
            'x-ratelimit-limit',
            'x-ratelimit-remaining',
            'x-ratelimit-reset',
            'retry-after'
        ];
        
        foreach ($headers_to_check as $header) {
            if (isset($headers[$header])) {
                $limits[$header] = $headers[$header];
            }
        }
        
        if (!empty($limits)) {
            $this->limits = $limits;
            update_option('wpwps_api_rate_limits', $limits);
        }
    }
    
    /**
     * Reset daily API counter if needed
     */
    private function maybeResetDailyCounters(): void {
        $last_reset = get_option('wpwps_api_daily_reset', 0);
        $now = time();
        
        // Calculate the start of today (midnight UTC)
        $today_start = strtotime('today midnight UTC');
        
        if ($last_reset < $today_start) {
            // Reset counters
            update_option('wpwps_api_calls_today', 0);
            update_option('wpwps_api_daily_reset', $now);
            update_option('wpwps_api_rate_limited', false);
            update_option('wpwps_api_rate_limit_expiry', 0);
            
            $this->logger->log('Daily API counters reset', 'info');
        }
    }
}
