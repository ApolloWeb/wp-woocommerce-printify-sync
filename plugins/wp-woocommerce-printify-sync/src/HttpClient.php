<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:24:52

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
        $args['method'] = 'GET';
        return wp_remote_request($url, $args);
    }

    public function post($url, $args = [])
    {
        $args['method'] = 'POST';
        return wp_remote_request($url, $args);
    }

    public function put($url, $args = [])
    {
        $args['method'] = 'PUT';
        return wp_remote_request($url, $args);
    }

    public function delete($url, $args = [])
    {
        $args['method'] = 'DELETE';
        return wp_remote_request($url, $args);
    }

    private function backoff($retries)
    {
        $waitTime = pow($this->backoffFactor, $retries);
        sleep($waitTime);
    }
}