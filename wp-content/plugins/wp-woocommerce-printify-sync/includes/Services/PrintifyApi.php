<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class PrintifyApi {
    private $settings;
    private $api_url = 'https://api.printify.com/v1/';
    private $timeout = 30;

    public function __construct(Settings $settings) {
        $this->settings = $settings;
    }

    public function request(string $endpoint, array $args = []) {
        $defaults = [
            'method' => 'GET',
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->get('printify_api_key'),
                'Content-Type' => 'application/json'
            ]
        ];

        $args = wp_parse_args($args, $defaults);
        $url = $this->api_url . ltrim($endpoint, '/');
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function testConnection(): bool {
        try {
            $response = $this->request('shops.json');
            return isset($response['data']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
