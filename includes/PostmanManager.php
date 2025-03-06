<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class PostmanManager {

    public static function uploadTestData($data) {
        // Logic to upload test data to Postman
    }

    public static function executeApiCall($endpoint, $method, $data) {
        $url = 'https://api.postman.com/' . $endpoint;
        $apiKey = 'your-postman-api-key';
        $options = [
            'headers' => [
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ];
        $response = wp_remote_request($url, $options);
        return wp_remote_retrieve_body($response);
    }
}