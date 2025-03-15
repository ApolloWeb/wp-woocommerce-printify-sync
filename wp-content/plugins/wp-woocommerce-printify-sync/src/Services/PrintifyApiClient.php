<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class PrintifyApiClient
{
    private string $apiKey;
    private string $baseUrl;
    private string $currentTime;
    private string $currentUser;
    private ApiRateLimiter $rateLimiter;
    private ResponseFormatter $formatter;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->currentTime = '2025-03-15 18:22:27';
        $this->currentUser = 'ApolloWeb';
        $this->baseUrl = get_option('wpwps_api_base_url', 'https://api.printify.com/v1');
        $this->rateLimiter = new ApiRateLimiter($this->currentTime, $this->currentUser);
        $this->formatter = new ResponseFormatter();
    }

    public function request(string $endpoint, string $method = 'GET', array $params = []): array
    {
        $path = $this->getEndpointPath($endpoint, $params);
        return $this->makeRequest($method, $path, $params);
    }

    private function getEndpointPath(string $endpoint, array &$params): string
    {
        $endpoints = get_option('wpwps_api_endpoints', []);
        
        if (!isset($endpoints[$endpoint])) {
            throw new \InvalidArgumentException("Invalid endpoint: $endpoint");
        }

        $path = $endpoints[$endpoint]['path'];
        
        // Replace path parameters
        preg_match_all('/{([^}]+)}/', $path, $matches);
        foreach ($matches[1] as $param) {
            if (!isset($params[$param])) {
                throw new \InvalidArgumentException("Missing required parameter: $param");
            }
            $path = str_replace("{{$param}}", $params[$param], $path);
            unset($params[$param]);
        }

        return $path;
    }

    private function makeRequest(string $method, string $path, array $params = []): array
    {
        return $this->rateLimiter->executeWithRetry(function() use ($method, $path, $params) {
            $url = $this->baseUrl . $path;
            
            if ($method === 'GET' && !empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $args = [
                'method' => $method,
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                    'X-Timestamp' => $this->currentTime,
                    'X-User' => $this->currentUser
                ]
            ];

            if ($method !== 'GET' && !empty($params)) {
                $args['body'] = json_encode($params);
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $statusCode = wp_remote_retrieve_response_code($response);

            if ($statusCode >= 400) {
                throw new \Exception("API Error: $body", $statusCode);
            }

            return $this->formatter->formatResponse(json_decode($body, true));
        }, $path);
    }
}