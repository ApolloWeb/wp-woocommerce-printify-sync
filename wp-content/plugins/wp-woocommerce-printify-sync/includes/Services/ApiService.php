<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Traits\RateLimit;

class ApiService {
    use RateLimit;

    private $api_key;
    private $api_endpoint;
    private $shop_id;
    private $logger_service;
    private $encryption_key;

    public function __construct() {
        $this->api_key = $this->getDecryptedOption('wpwps_api_key');
        $this->api_endpoint = get_option('wpwps_api_endpoint', 'https://api.printify.com/v1');
        $this->shop_id = get_option('wpwps_shop_id');
        $this->logger_service = new LoggerService();
        $this->encryption_key = wp_salt('auth');
        
        $this->setMaxRetries(get_option('wpwps_max_retries', 3));
        $this->setRetryDelay(get_option('wpwps_retry_delay', 5));
    }

    protected function request(string $method, string $endpoint, array $args = []): array {
        $url = $this->api_endpoint . $endpoint;
        $attempt = 1;
        $max_attempts = $this->max_retries + 1;

        do {
            $headers = [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ];

            $request_args = array_merge([
                'method' => $method,
                'headers' => $headers,
                'timeout' => 30
            ], $args);

            if (!empty($request_args['body']) && is_array($request_args['body'])) {
                $request_args['body'] = json_encode($request_args['body']);
            }

            $response = wp_remote_request($url, $request_args);
            
            if (is_wp_error($response)) {
                if ($attempt < $max_attempts && $this->shouldRetry($attempt, 500)) {
                    $this->exponentialBackoff($attempt);
                    $attempt++;
                    continue;
                }

                $result = [
                    'success' => false,
                    'message' => $response->get_error_message(),
                    'code' => 500
                ];
            } else {
                $headers = wp_remote_retrieve_headers($response);
                $code = wp_remote_retrieve_response_code($response);
                $body = json_decode(wp_remote_retrieve_body($response), true);

                // Handle rate limiting
                if ($this->handleRateLimiting($headers)) {
                    continue;
                }

                if ($code >= 200 && $code < 300) {
                    $result = [
                        'success' => true,
                        'data' => $body,
                        'code' => $code
                    ];
                    break;
                } else {
                    if ($attempt < $max_attempts && $this->shouldRetry($attempt, $code)) {
                        $this->exponentialBackoff($attempt);
                        $attempt++;
                        continue;
                    }

                    $result = [
                        'success' => false,
                        'message' => $body['message'] ?? 'Unknown error',
                        'code' => $code,
                        'data' => $body
                    ];
                }
            }
        } while ($attempt < $max_attempts);

        // Log the API call
        $this->logger_service->logApiCall($endpoint, $result, $attempt - 1);

        return $result;
    }

    public function setApiKey(string $key): void {
        $this->api_key = $key;
        update_option('wpwps_api_key', $this->encrypt($key));
    }

    public function setApiEndpoint(string $endpoint): void {
        $this->api_endpoint = $endpoint;
    }

    public function encrypt(string $value): string {
        if (!extension_loaded('openssl')) {
            return base64_encode($value);
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', $this->encryption_key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $encrypted): string {
        if (!extension_loaded('openssl')) {
            return base64_decode($encrypted);
        }

        $data = base64_decode($encrypted);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryption_key, 0, $iv);
    }

    private function getDecryptedOption(string $option): ?string {
        $value = get_option($option);
        return $value ? $this->decrypt($value) : null;
    }

    // API Endpoints
    public function testConnection(): array {
        return $this->request('GET', '/shops.json');
    }

    public function getShops(): array {
        return $this->request('GET', '/shops.json');
    }

    public function getProducts(): array {
        return $this->request('GET', "/shops/{$this->shop_id}/products.json");
    }

    public function getProduct(string $id): array {
        return $this->request('GET', "/shops/{$this->shop_id}/products/{$id}.json");
    }

    public function createOrder(array $data): array {
        return $this->request('POST', "/shops/{$this->shop_id}/orders.json", [
            'body' => $data
        ]);
    }

    public function getOrders(array $params = []): array {
        $query = http_build_query($params);
        return $this->request('GET', "/shops/{$this->shop_id}/orders.json" . ($query ? "?{$query}" : ''));
    }

    public function updateOrderStatus(string $order_id, array $data): array {
        return $this->request('PUT', "/shops/{$this->shop_id}/orders/{$order_id}.json", [
            'body' => $data
        ]);
    }

    public function getOrderWebhooks(): array {
        return $this->request('GET', "/shops/{$this->shop_id}/webhooks.json");
    }

    public function createWebhook(array $data): array {
        return $this->request('POST', "/shops/{$this->shop_id}/webhooks.json", [
            'body' => $data
        ]);
    }

    public function deleteWebhook(string $webhook_id): array {
        return $this->request('DELETE', "/shops/{$this->shop_id}/webhooks/{$webhook_id}.json");
    }
}