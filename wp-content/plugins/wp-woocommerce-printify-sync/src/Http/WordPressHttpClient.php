<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Http;

use ApolloWeb\WPWooCommercePrintifySync\Http\Contracts\HttpClientInterface;

class WordPressHttpClient implements HttpClientInterface {
    public function request(string $url, string $method = 'GET', array $options = []): array {
        // Log request details
        error_log('========= PRINTIFY API REQUEST =========');
        error_log('URL: ' . $url);
        error_log('Method: ' . $method);
        error_log('Options: ' . print_r($options, true));

        $request_options = array_merge([
            'method' => $method,
            'timeout' => 60,
            'sslverify' => true,
        ], $options);

        // Log final request options
        error_log('Final Request Options: ' . print_r($request_options, true));

        $response = wp_remote_request($url, $request_options);

        if (is_wp_error($response)) {
            error_log('WP_Error: ' . $response->get_error_message());
            throw new \Exception($response->get_error_message());
        }

        // Log complete response
        error_log('========= PRINTIFY API RESPONSE =========');
        error_log('Response Code: ' . wp_remote_retrieve_response_code($response));
        error_log('Response Headers: ' . print_r(wp_remote_retrieve_headers($response), true));
        error_log('Response Body: ' . wp_remote_retrieve_body($response));

        $code = wp_remote_retrieve_response_code($response);
        $headers = wp_remote_retrieve_headers($response);
        $body = wp_remote_retrieve_body($response);
        
        $data = json_decode($body, true);

        // Log decoded data
        error_log('Printify API Decoded: ' . print_r($data, true));

        // Handle JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Printify API JSON Error: ' . json_last_error_msg());
            throw new \Exception('Invalid JSON response from API: ' . json_last_error_msg());
        }

        // Handle API errors with proper message
        if ($code >= 400) {
            $message = isset($data['error']) ? $data['error']['message'] : 
                      (isset($data['message']) ? $data['message'] : 'Unknown API error');
            error_log('Printify API Error Response: ' . $message);
            throw new \Exception($message, $code);
        }

        // Ensure we return array in correct format
        return ['data' => is_array($data) ? $data : [$data]];
    }
}
