<?php
/**
 * API Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ApiHelper {
    /**
     * @var ApiHelper Instance of this class.
     */
    private static $instance = null;
    
    /**
     * @var string Printify API base URL
     */
    private $printifyApiUrl;
    
    /**
     * @var string Currency API base URL
     */
    private $currencyApiUrl;
    
    /**
     * @var string Geolocation API base URL
     */
    private $geolocationApiUrl;
    
    /**
     * Get single instance of this class
     *
     * @return ApiHelper
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $api_mode = get_option('wpwprintifysync_api_mode', 'production');
        
        // Set API URLs based on mode
        $this->printifyApiUrl = ($api_mode === 'production') 
            ? 'https://api.printify.com/v1/'
            : 'https://api.sandbox.printify.com/v1/';
            
        $this->currencyApiUrl = 'https://api.exchangeratesapi.io/v1/';
        $this->geolocationApiUrl = 'http://api.ipstack.com/';
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwprintifysync_test_webhook', [$this, 'ajaxTestWebhook']);
    }
    
    /**
     * Send request to Printify API
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response
     */
    public function sendPrintifyRequest($endpoint, $args = []) {
        $api_key = get_option('wpwprintifysync_printify_api_key', '');
        
        if (empty($api_key)) {
            LogHelper::getInstance()->error('Printify API request failed: No API key configured');
            return [
                'success' => false,
                'message' => __('Printify API key is not configured.', 'wp-woocommerce-printify-sync')
            ];
        }
        
        $defaults = [
            'method' => 'GET',
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Convert body to JSON if it's an array
        if (!empty($args['body']) && is_array($args['body'])) {
            $args['body'] = json_encode($args['body']);
        }
        
        $url = $this->printifyApiUrl . ltrim($endpoint, '/');
        
        LogHelper::getInstance()->debug('Sending Printify API request', [
            'url' => $url,
            'method' => $args['method']
        ]);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            LogHelper::getInstance()->error('Printify API request error', [
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 400) {
            LogHelper::getInstance()->error('Printify API error response', [
                'status_code' => $status_code,
                'body' => $body
            ]);
            
            return [
                'success' => false,
                'status_code' => $status_code,
                'message' => isset($body['message']) ? $body['message'] : __('Unknown error', 'wp-woocommerce-printify-sync'),
                'body' => $body
            ];
        }
        
        return [
            'success' => true,
            'status_code' => $status_code,
            'body' => $body
        ];
    }
    
    /**
     * Send request to Currency API
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response
     */
    public function sendCurrencyRequest($endpoint, $args = []) {
        $api_key = get_option('wpwprintifysync_currency_api_key', '');
        
        if (empty($api_key)) {
            LogHelper::getInstance()->error('Currency API request failed: No API key configured');
            return [
                'success' => false,
                'message' => __('Currency API key is not configured.', 'wp-woocommerce-printify-sync')
            ];
        }
        
        $url = $this->currencyApiUrl . ltrim($endpoint, '/');
        
        // Add API key to URL
        $url = add_query_arg(['access_key' => $api_key], $url);
        
        $defaults = [
            'method' => 'GET',
            'timeout' => 30
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        LogHelper::getInstance()->debug('Sending Currency API request', [
            'url' => $url,
            'method' => $args['method']
        ]);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            LogHelper::getInstance()->error('Currency API request error', [
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 400 || (isset($body['success']) && $body['success'] === false)) {
            LogHelper::getInstance()->error('Currency API error response', [
                'status_code' => $status_code,
                'body' => $body
            ]);
            
            return [
                'success' => false,
                'status_code' => $status_code,
                'message' => isset($body['error']['info']) ? $body['error']['info'] : __('Unknown error', 'wp-woocommerce-printify-sync'),
                'body' => $body
            ];
        }
        
        return [
            'success' => true,
            'status_code' => $status_code,
            'body' => $body
        ];
    }
    
    /**
     * Send request to Geolocation API
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response
     */
    public function sendGeolocationRequest($endpoint, $args = []) {
        $api_key = get_option('wpwprintifysync_geolocation_api_key', '');
        
        if (empty($api_key)) {
            LogHelper::getInstance()->error('Geolocation API request failed: No API key configured');
            return [
                'success' => false,
                'message' => __('Geolocation API key is not configured.', 'wp-woocommerce-printify-sync')
            ];
        }
        
        $url = $this->geolocationApiUrl . ltrim($endpoint, '/');
        
        // Add API key to URL
        $url = add_query_arg(['access_key' => $api_key], $url);
        
        $defaults = [
            'method' => 'GET',
            'timeout' => 30
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        LogHelper::getInstance()->debug('Sending Geolocation API request', [
            'url' => $url,
            'method' => $args['method']
        ]);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            LogHelper::getInstance()->error('Geolocation API request error', [
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 400 || (isset($body['success']) && $body['success'] === false)) {
            LogHelper::getInstance()->error('Geolocation API error response', [
                'status_code' => $status_code,
                'body' => $body
            ]);
            
            return [
                'success' => false,
                'status_code' => $status_code,
                'message' => isset($body['error']['info']) ? $body['error']['info'] : __('Unknown error', 'wp-woocommerce-printify-sync'),
                'body' => $body
            ];
        }
        
        return [
            'success' => true,
            'status_code' => $status_code,
            'body' => $body
        ];
    }
    
    /**
     * Test API Connection
     *
     * @param string $api_type API type (printify, currency, geolocation)
     * @param string $api_key API key to test (optional)
     * @return array Test result
     */
    public function testApiConnection($api_type, $api_key = null) {
        switch ($api_type) {
            case 'printify':
                if ($api_key !== null) {
                    // Temporarily store the current key
                    $current_key = get_option('wpwprintifysync_printify_api_key', '');
                    // Set the test key
                    update_option('wpwprintifysync_printify_api_key', $api_key);
                }
                
                // Test connection by getting shops
                $result = $this->sendPrintifyRequest('shops.json');
                
                if ($api_key !== null) {
                    // Restore the original key
                    update_option('wpwprintifysync_printify_api_key', $current_key);
                }
                
                if ($result['success']) {
                    return [
                        'success' => true,
                        'message' => __('Successfully connected to Printify API.', 'wp-woocommerce-printify-sync'),
                        'data' => $result['body']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => $result['message']
                    ];
                }
                break;
                
            case 'currency':
                if ($api_key !== null) {
                    $current_key = get_option('wpwprintifysync_currency_api_key', '');
                    update_option('wpwprintifysync_currency_api_key', $api_key);
                }
                
                // Test connection by getting latest rates
                $result = $this->sendCurrencyRequest('latest');
                
                if ($api_key !== null) {
                    update_option('wpwprintifysync_currency_api_key', $current_key);
                }
                
                if ($result['success']) {
                    return [
                        'success' => true,
                        'message' => __('Successfully connected to Currency API.', 'wp-woocommerce-printify-sync'),
                        'data' => $result['body']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => $result['message']
                    ];
                }
                break;
                
            case 'geolocation':
                if ($api_key !== null) {
                    $current_key = get_option('wpwprintifysync_geolocation_api_key', '');
                    update_option('wpwprintifysync_geolocation_api_key', $api_key);
                }
                
                // Test connection by getting location for example IP
                $result = $this->sendGeolocationRequest('check');
                
                if ($api_key !== null) {
                    update_option('wpwprintifysync_geolocation_api_key', $current_key);
                }
                
                if ($result['success']) {
                    return [
                        'success' => true,
                        'message' => __('Successfully connected to Geolocation API.', 'wp-woocommerce-printify-sync'),
                        'data' => $result['body']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => $result['message']
                    ];
                }
                break;
                
            default:
                return [
                    'success' => false,
                    'message' => __('Invalid API type.', 'wp-woocommerce-printify-sync')
                ];
        }
    }
    
    /**
     * Handle webhook request
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response object
     */
    public function handleWebhook($request) {
        $body = $request->get_json_params();
        
        if (empty($body)) {
            LogHelper::getInstance()->error('Empty webhook payload received');
            return new \WP_REST_Response(['message' => 'Empty payload'], 400);
        }
        
        LogHelper::getInstance()->info('Webhook received', [
            'type' => isset($body['type']) ? $body['type'] : 'unknown',
            'data' => $body
        ]);
        
        // Process webhook based on type
        if (isset($body['type'])) {
            switch ($body['type']) {
                case 'order.created':
                case 'order.fulfilled':
                case 'order.shipped':
                case 'order.cancelled':
                    // Handle order updates
                    OrderHelper::getInstance()->processWebhookOrder($body);
                    break;
                    
                case 'product.updated':
                case 'product.deleted':
                    // Handle product updates
                    ProductHelper::getInstance()->processWebhookProduct($body);
                    break;
                    
                default:
                    LogHelper::getInstance()->warning('Unhandled webhook type', [
                        'type' => $body['type']
                    ]);
                    break;
            }
        }
        
        return new \WP_REST_Response(['message' => 'Webhook received'], 200);
    }
    
    /**
     * Validate webhook request
     *
     * @param \WP_REST_Request $request Request object
     * @return bool Whether the request is valid
     */
    public function validateWebhookRequest($request) {
        $signature = $request->get_header('X-Printify-Signature');
        
        if (empty($signature)) {
            LogHelper::getInstance()->error('Missing webhook signature');
            return false;
        }
        
        $webhook_secret = get_option('wpwprintifysync_webhook_secret', '');
        
        if (empty($webhook_secret)) {
            LogHelper::getInstance()->error('Webhook secret not configured');
            return false;
        }
        
        // Get raw request body
        $payload = $request->get_body();
        
        // Calculate expected signature
        $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);
        
        if ($signature !== $expected_signature) {
            LogHelper::getInstance()->error('Invalid webhook signature', [
                'received' => $signature,
                'expected' => $expected_signature
            ]);
            
            return false;
        }
        
        return true;
    }
    
    /**
     * AJAX handler for testing webhook
     */
    public function ajaxTestWebhook() {
        // Check nonce
        if (!check_ajax_referer('wpwprintifysync-admin', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }
        
        $webhook_url = rest_url('wpwprintifysync/v1/webhook/printify');
        $webhook_secret = get_option('wpwprintifysync_webhook_secret', '');
        
        if (empty($webhook_secret)) {
            wp_send_json_error([
                'message' => __('Webhook secret not configured.', 'wp-woocommerce-printify-sync')
            ]);
        }
        
        // Create test payload
        $payload = json_encode([
            'type' => 'test.webhook',
            'timestamp' => '2025-03-05 18:27:12',
            'triggered_by' => 'ApolloWeb'
        ]);
        
        // Calculate signature
        $signature = hash_hmac('sha256', $payload, $webhook_secret);
        
        // Send request to our webhook endpoint
        $response = wp_remote_post($webhook_url, [
            'body' => $payload,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Printify-Signature' => $signature
            ],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message()
            ]);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            wp_send_json_error([
                'message' => sprintf(__('Webhook test failed with status code: %s', 'wp-woocommerce-printify-sync'), $status_code),
                'response' => wp_remote_retrieve_body($response)
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Webhook test successful!', 'wp-woocommerce-printify-sync')
        ]);
    }
}