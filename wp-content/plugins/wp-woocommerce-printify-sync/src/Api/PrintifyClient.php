<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\API;

class PrintifyClient
{
    private string $currentTime = '2025-03-15 20:08:56';
    private string $currentUser = 'ApolloWeb';
    private string $apiKey;
    private string $baseUrl = 'https://api.printify.com/v1';

    public function __construct()
    {
        $this->apiKey = get_option('wpwps_printify_api_key');
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    private function request(
        string $method,
        string $endpoint,
        array $params = [],
        array $data = []
    ): array {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ];

        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode >= 400) {
            throw new \Exception(
                "API request failed: " . $body,
                $statusCode
            );
        }

        return json_decode($body, true);
    }
}