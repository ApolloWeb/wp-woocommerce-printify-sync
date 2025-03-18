<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

class HttpClient
{
    private $maxRetries;
    private $backoffFactor;

    public function __construct($maxRetries = 3, $backoffFactor = 2)
    {
        $this->maxRetries = $maxRetries;
        $this->backoffFactor = $backoffFactor;
    }

    public function get($url, $args = [])
    {
        $retries = 0;

        while ($retries <= $this->maxRetries) {
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                $retries++;
                $this->backoff($retries);
                continue;
            }

            $rateLimitRemaining = wp_remote_retrieve_header($response, 'x-ratelimit-remaining');
            $rateLimitReset = wp_remote_retrieve_header($response, 'x-ratelimit-reset');

            if ($rateLimitRemaining === '0') {
                $waitTime = $rateLimitReset - time();
                if ($waitTime > 0) {
                    sleep($waitTime);
                }
                $retries++;
                continue;
            }

            if (wp_remote_retrieve_response_code($response) === 200) {
                return wp_remote_retrieve_body($response);
            } else {
                $retries++;
                $this->backoff($retries);
            }
        }

        return new \WP_Error('http_request_failed', __('HTTP request failed after multiple retries', 'wp-woocommerce-printify-sync'));
    }

    private function backoff($retries)
    {
        $waitTime = pow($this->backoffFactor, $retries);
        sleep($waitTime);
    }
}