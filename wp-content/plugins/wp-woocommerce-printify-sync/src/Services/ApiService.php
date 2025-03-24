<?php
/**
 * API Service for Printify
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Class ApiService
 *
 * Handles API communication with Printify
 */
class ApiService
{
    /**
     * API endpoint
     *
     * @var string
     */
    private string $api_endpoint;

    /**
     * API key
     *
     * @var string
     */
    private string $api_key;

    /**
     * Shop ID
     *
     * @var string
     */
    private string $shop_id;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Retry attempts
     *
     * @var int
     */
    private int $retry_attempts = 3;

    /**
     * Rate limiter
     *
     * @var RateLimiter
     */
    private RateLimiter $rate_limiter;

    /**
     * Constructor
     *
     * @param LoggerService $logger Logger service.
     * @param RateLimiter $rate_limiter Rate limiter service.
     */
    public function __construct(LoggerService $logger, RateLimiter $rate_limiter)
    {
        $this->logger = $logger;
        $this->rate_limiter = $rate_limiter;
        $this->api_endpoint = get_option('wpwps_api_endpoint', 'https://api.printify.com/v1/');
        $this->api_key = $this->getApiKey();
        $this->shop_id = get_option('wpwps_shop_id', '');
    }

    /**
     * Get the encrypted API key
     *
     * @return string
     */
    private function getApiKey(): string
    {
        $encrypted_key = get_option('wpwps_api_key', '');
        
        if (empty($encrypted_key)) {
            return '';
        }
        
        // Decrypt the key
        return $this->decrypt($encrypted_key);
    }

    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt
     * @return string
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';
        $iv = substr(wp_hash(wp_salt('secure_auth'), 'nonce'), 0, 16);
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        if ($encrypted === false) {
            $this->logger->error('Encryption failed');
            return '';
        }
        
        return base64_encode($encrypted);
    }

    /**
     * Decrypt data
     *
     * @param string $data Encrypted data
     * @return string
     */
    public function decrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';
        $iv = substr(wp_hash(wp_salt('secure_auth'), 'nonce'), 0, 16);
        
        $decrypted = openssl_decrypt(base64_decode($data), $method, $key, 0, $iv);
        
        if ($decrypted === false) {
            $this->logger->error('Decryption failed');
            return '';
        }
        
        return $decrypted;
    }

    /**
     * Set the API key
     *
     * @param string $api_key API key
     * @return void
     */
    public function setApiKey(string $api_key): void
    {
        $this->api_key = $api_key;
        
        // Encrypt and save the key
        $encrypted_key = $this->encrypt($api_key);
        update_option('wpwps_api_key', $encrypted_key);
    }

    /**
     * Set the shop ID
     *
     * @param string $shop_id Shop ID
     * @return void
     */
    public function setShopId(string $shop_id): void
    {
        $this->shop_id = $shop_id;
        update_option('wpwps_shop_id', $shop_id);
    }

    /**
     * Get shop ID
     *
     * @return string
     */
    public function getShopId(): string
    {
        return $this->shop_id;
    }

    /**
     * Check if API credentials are set
     *
     * @return bool
     */
    public function hasCredentials(): bool
    {
        return !empty($this->api_key);
    }

    /**
     * Make a GET request to the API
     *
     * @param string $endpoint Endpoint to request
     * @param array  $params   Query parameters
     * @return array|null
     */
    public function get(string $endpoint, array $params = []): ?array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint Endpoint to request
     * @param array  $data     Request data
     * @return array|null
     */
    public function post(string $endpoint, array $data = []): ?array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint Endpoint to request
     * @param array  $data     Request data
     * @return array|null
     */
    public function put(string $endpoint, array $data = []): ?array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint Endpoint to request
     * @param array  $params   Query parameters
     * @return array|null
     */
    public function delete(string $endpoint, array $params = []): ?array
    {
        return $this->request('DELETE', $endpoint, $params);
    }

    /**
     * Make a request to the API with retry logic
     *
     * @param string $method   HTTP method
     * @param string $endpoint Endpoint to request
     * @param array  $data     Request data or params
     * @return array|null
     */
    private function request(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return null;
        }

        if (!$this->rate_limiter->checkLimit('api_calls')) {
            $this->logger->warning('API rate limit reached');
            throw new \Exception('API rate limit reached');
        }

        // Build full URL
        $url = rtrim($this->api_endpoint, '/') . '/' . ltrim($endpoint, '/');

        // Add query params for GET and DELETE requests
        if (in_array($method, ['GET', 'DELETE']) && !empty($data)) {
            $url = add_query_arg($data, $url);
        }

        // Build request arguments
        $args = [
            'method'    => $method,
            'timeout'   => 45,
            'headers'   => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ];

        // Add body for POST and PUT requests
        if (in_array($method, ['POST', 'PUT']) && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        // Execute request with retry logic
        return $this->executeRequestWithRetry($url, $args);
    }

    /**
     * Execute API request with retry logic
     *
     * @param string $url  Request URL
     * @param array  $args Request arguments
     * @return array|null
     */
    private function executeRequestWithRetry(string $url, array $args): ?array
    {
        $attempt = 0;
        
        while ($attempt < $this->retry_attempts) {
            $attempt++;
            
            // Log the request (without sensitive data)
            $log_args = $args;
            unset($log_args['headers']['Authorization']);
            
            $this->logger->debug('API request', [
                'url' => $url,
                'method' => $args['method'],
                'attempt' => $attempt,
            ]);
            
            // Execute request
            $response = wp_remote_request($url, $args);
            
            // Check for WP error
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->logger->error('API request failed with WP error', [
                    'error' => $error_message,
                    'attempt' => $attempt,
                ]);
                
                // Exponential backoff
                sleep(pow(2, $attempt - 1));
                continue;
            }
            
            // Get response code
            $response_code = wp_remote_retrieve_response_code($response);
            
            // Success
            if ($response_code >= 200 && $response_code < 300) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                $this->logger->debug('API request successful', [
                    'response_code' => $response_code,
                    'attempt' => $attempt,
                ]);
                
                return $data;
            }
            
            // Rate limiting
            if ($response_code === 429) {
                $retry_after = wp_remote_retrieve_header($response, 'retry-after');
                $retry_after = $retry_after ? (int) $retry_after : 60;
                
                $this->logger->warning('API rate limit hit', [
                    'retry_after' => $retry_after,
                    'attempt' => $attempt,
                ]);
                
                // Wait for the specified time
                sleep($retry_after);
                continue;
            }
            
            // Server error, retry
            if ($response_code >= 500) {
                $this->logger->warning('API server error', [
                    'response_code' => $response_code,
                    'attempt' => $attempt,
                ]);
                
                // Exponential backoff
                sleep(pow(2, $attempt - 1));
                continue;
            }
            
            // Client error, no retry
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            $this->logger->error('API request failed with client error', [
                'response_code' => $response_code,
                'response' => $data,
            ]);
            
            return null;
        }
        
        $this->logger->error('API request failed after maximum retry attempts');
        return null;
    }

    /**
     * Test the API connection
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function testConnection(): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'message' => __('API key is not set', 'wp-woocommerce-printify-sync'),
            ];
        }

        $shops = $this->get('shops.json');

        if (null === $shops) {
            return [
                'success' => false,
                'message' => __('Failed to connect to Printify API', 'wp-woocommerce-printify-sync'),
            ];
        }

        return [
            'success' => true,
            'message' => __('Successfully connected to Printify API', 'wp-woocommerce-printify-sync'),
            'data' => $shops,
        ];
    }
}
