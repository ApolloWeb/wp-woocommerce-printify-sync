<?php
namespace ApolloWeb\WPWooCommercePrintifySync\API;

class PrintifyHttpClient
{
    private $apiKey;
    private $endpoint;
    
    public function __construct(string $apiKey, string $endpoint)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = rtrim($endpoint, '/');
    }
    
    public function request(string $endpoint, string $method = 'GET', array $params = [], array $data = []): array
    {
        // Build the full URL, ensuring query parameters are handled correctly for GET requests
        $url = rtrim($this->endpoint, '/') . '/' . ltrim($endpoint, '/');
        
        // For GET requests, append parameters to URL as query string
        if ($method === 'GET' && !empty($params)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($params);
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];

        // For non-GET requests with data, add as JSON body
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }

        // Enhanced debugging
        error_log("DEBUG PrintifyHttpClient - API Key Check: " . (empty($this->apiKey) ? 'EMPTY!' : 'Present (length: ' . strlen($this->apiKey) . ')'));
        error_log("DEBUG PrintifyHttpClient - Request: {$method} {$url}");
        error_log("DEBUG PrintifyHttpClient - Headers: " . json_encode($headers));
        
        if (isset($args['body'])) {
            error_log("DEBUG PrintifyHttpClient - Body: " . substr($args['body'], 0, 1000) . (strlen($args['body']) > 1000 ? '...' : ''));
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("ERROR PrintifyHttpClient - WP Error: {$error_message}");
            throw new \Exception('API request failed: ' . $error_message);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("DEBUG PrintifyHttpClient - Response Status: {$status_code}");
        error_log("DEBUG PrintifyHttpClient - Response Body: " . substr($body, 0, 1000) . (strlen($body) > 1000 ? '...' : ''));

        if (empty($body)) {
            error_log("ERROR PrintifyHttpClient - Empty response body");
            throw new \Exception('Empty response from Printify API');
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ERROR PrintifyHttpClient - JSON decode error: " . json_last_error_msg());
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        if ($status_code < 200 || $status_code >= 300) {
            $error_message = isset($data['error']) ? $data['error'] : 'Unknown error';
            error_log("ERROR PrintifyHttpClient - API Error: Status {$status_code}, Message: {$error_message}");
            throw new \Exception("API responded with code {$status_code}: {$error_message}");
        }
        
        return $data;
    }
}
