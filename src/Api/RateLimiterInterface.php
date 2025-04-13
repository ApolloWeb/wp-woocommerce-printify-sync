<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

/**
 * Interface for API rate limiting
 */
interface RateLimiterInterface {
    /**
     * Check if request should be rate limited
     *
     * @param string $endpoint API endpoint
     * @return bool Whether the request should be processed or limited
     */
    public function shouldLimit($endpoint);
    
    /**
     * Record a successful API request
     *
     * @param string $endpoint API endpoint
     * @param array $response_headers Response headers
     * @return void
     */
    public function recordSuccess($endpoint, $response_headers = []);
    
    /**
     * Record a failed API request and determine retry strategy
     *
     * @param string $endpoint API endpoint
     * @param string $error_message Error message
     * @param int $status_code HTTP status code
     * @return void
     */
    public function recordFailure($endpoint, $error_message, $status_code = 0);
    
    /**
     * Get delay before retry for endpoint
     *
     * @param string $endpoint API endpoint
     * @return int Seconds to wait before retry
     */
    public function getRetryDelay($endpoint);
}
