<?php
namespace ApolloWeb\WPWooCommercePrintifySync\API;

class PrintifyAPI implements PrintifyAPIInterface
{
    // ...existing code...

    private function makeRequest(string $path, string $method = 'GET', array $params = []): array
    {
        try {
            $url = $this->endpoint . '/' . ltrim($path, '/');
            
            if ($method === 'GET' && !empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            $args = [
                'method' => $method,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'timeout' => 30,
                'sslverify' => true
            ];
            
            if ($method !== 'GET' && !empty($params)) {
                $args['body'] = json_encode($params);
            }

            error_log("Making request to Printify API: $url");
            $response = wp_remote_request($url, $args);
            
            if (is_wp_error($response)) {
                throw new \Exception('Connection error: ' . $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if (empty($body)) {
                throw new \Exception("Empty response from API");
            }
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response: " . json_last_error_msg());
            }
            
            if ($status_code < 200 || $status_code >= 300) {
                $error_msg = isset($data['error']) ? $data['error'] : 'Unknown error';
                throw new \Exception("API responded with code {$status_code}: {$error_msg}");
            }
            
            return $data;

        } catch (\Exception $e) {
            error_log('Printify API request error: ' . $e->getMessage());
            throw $e;
        }
    }
}