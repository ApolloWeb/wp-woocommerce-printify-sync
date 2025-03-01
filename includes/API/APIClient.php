<?php
/**
 * Printify API Client.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Utilities\Logger;

class APIClient {

    /**
     * Base URL for Printify API.
     *
     * @var string
     */
    private $api_url = 'https://api.printify.com/v2/';

    /**
     * API Key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Constructor.
     */
    public function __construct($api_key = '') {
        if (empty($api_key)) {
            $encrypted_key = get_option('wpwps_api_key_encrypted', '');
            if (!empty($encrypted_key)) {
                $encryption = new \ApolloWeb\WPWooCommercePrintifySync\Utilities\Encryption();
                $api_key = $encryption->decrypt($encrypted_key);
            }
        }
        $this->api_key = $api_key;
    }

    /**
     * Make a request to Printify API.
     */
    public function request($endpoint, $method = 'GET', $data = array(), $retry = 3) {
        $url = $this->api_url . ltrim($endpoint, '/');
        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            Logger::log('API', 'Request error: ' . $response->get_error_message(), 'error');
            if ($retry > 0) {
                sleep(pow(2, 4 - $retry));
                return $this->request($endpoint, $method, $data, $retry - 1);
            }
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code >= 400) {
            $error_message = isset($response_data['error']) ? $response_data['error'] : 'Unknown error';
            Logger::log('API', "Error {$response_code}: {$error_message}", 'error');
            if ($response_code === 429 && $retry > 0) {
                $retry_after = wp_remote_retrieve_header($response, 'retry-after');
                $retry_seconds = $retry_after ? intval($retry_after) : 60;
                sleep($retry_seconds);
                return $this->request($endpoint, $method, $data, $retry - 1);
            }
            return array(
                'success' => false,
                'code'    => $response_code,
                'message' => $error_message,
            );
        }

        return array(
            'success' => true,
            'code'    => $response_code,
            'data'    => $response_data,
        );
    }

    /**
     * Test API connection.
     */
    public function test_connection() {
        $response = $this->request('shops.json');
        if ($response === false || !$response['success']) {
            $message = isset($response['message']) ? $response['message'] : 'Connection failed';
            return array(
                'success' => false,
                'message' => $message,
            );
        }
        return array(
            'success' => true,
            'message' => 'Connected successfully',
            'shops'   => isset($response['data']['shops']) ? $response['data']['shops'] : array(),
        );
    }

    /**
     * Get shops.
     */
    public function get_shops() {
        $response = $this->request('shops.json');
        if ($response === false || !$response['success']) {
            return false;
        }
        return isset($response['data']['shops']) ? $response['data']['shops'] : array();
    }

    /**
     * Register webhook.
     */
    public function register_webhook($webhook_url, $event) {
        $endpoint = 'webhooks.json';
        $data = array(
            'url'   => $webhook_url,
            'event' => $event,
        );

        $response = $this->request($endpoint, 'POST', $data);
        if ($response === false || !$response['success']) {
            return false;
        }
        return isset($response['data']) ? $response['data'] : array();
    }

    /**
     * Get all webhooks.
     */
    public function get_webhooks() {
        $response = $this->request('webhooks.json');
        if ($response === false || !$response['success']) {
            return false;
        }
        return isset($response['data']) ? $response['data'] : array();
    }

    /**
     * Delete a webhook.
     */
    public function delete_webhook($webhook_id) {
        $endpoint = "webhooks/{$webhook_id}.json";
        $response = $this->request($endpoint, 'DELETE');
        return $response !== false && $response['success'];
    }
}