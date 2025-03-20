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
    
    public function request(string $path, string $method = 'GET', array $params = []): array 
    {
        $url = $this->endpoint . '/' . ltrim($path, '/');
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15
        ];
        
        if ($method !== 'GET' && !empty($params)) {
            $args['body'] = json_encode($params);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('Connection error: ' . $response->get_error_message());
        }
        
        return $this->handleResponse($response);
    }
    
    private function handleResponse($response): array
    {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code < 200 || $status_code >= 300) {
            $error_msg = isset($data['error']) ? $data['error'] : 'Unknown error';
            throw new \Exception("API responded with code {$status_code}: {$error_msg}");
        }
        
        return $data;
    }
}
