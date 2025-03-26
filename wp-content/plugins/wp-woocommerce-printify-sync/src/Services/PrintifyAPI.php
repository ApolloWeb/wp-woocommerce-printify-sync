<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class PrintifyAPI extends BaseAPIService {
    private const RATE_LIMIT_KEY = 'wpwps_printify_rate_limit';
    private const RATE_LIMIT_WINDOW = 60; // 1 minute window
    private const MAX_REQUESTS_PER_WINDOW = 30; // Printify's standard rate limit
    private const ERROR_CODES = [
        400 => 'Bad Request - The request was malformed or missing required parameters',
        401 => 'Unauthorized - Invalid API key or authentication token',
        403 => 'Forbidden - The API key does not have permission to perform this action',
        404 => 'Not Found - The requested resource was not found',
        422 => 'Unprocessable Entity - The request was well-formed but contained invalid parameters',
        429 => 'Too Many Requests - Rate limit exceeded',
        500 => 'Internal Server Error - Something went wrong on Printify\'s end',
        503 => 'Service Unavailable - Printify is temporarily unavailable'
    ];

    private $client;
    private $settings;

    public function __construct() {
        $this->settings = new Settings();
        $this->initClient();
    }

    private function initClient(): void {
        $this->client = new Client([
            'base_uri' => $this->settings->getPrintifyApiEndpoint(),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->getPrintifyApiKey(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'WP-WooCommerce-Printify-Sync/1.0.0'
            ],
            'timeout' => 30,
            'http_errors' => true
        ]);
    }

    private function handlePrintifyError(\Exception $e, string $context): void {
        $code = $e->getCode();
        $message = $e->getMessage();

        if ($e instanceof ClientException || $e instanceof ServerException) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody(), true);
            
            // Extract Printify's specific error message if available
            if (isset($body['error'])) {
                $message = $body['error'];
            } elseif (isset($body['message'])) {
                $message = $body['message'];
            }

            // Add rate limit information if available
            if ($code === 429 && $response->hasHeader('X-RateLimit-Reset')) {
                $resetTime = $response->getHeader('X-RateLimit-Reset')[0];
                $waitTime = $resetTime - time();
                $message .= sprintf(
                    '. Wait %d seconds before retrying.',
                    max(1, $waitTime)
                );
            }

            // Add standard error description if available
            if (isset(self::ERROR_CODES[$code])) {
                $message .= ' - ' . self::ERROR_CODES[$code];
            }
        }

        // Log detailed error for debugging
        error_log(sprintf(
            '[Printify API Error] [%s] Code: %d, Context: %s, Message: %s',
            date('Y-m-d H:i:s'),
            $code,
            $context,
            $message
        ));

        throw new \Exception($this->sanitizeErrorMessage($message), $code);
    }

    public function validateApiKey(string $api_key, string $api_endpoint): bool {
        try {
            $this->checkRateLimit(
                self::RATE_LIMIT_KEY,
                self::RATE_LIMIT_WINDOW,
                self::MAX_REQUESTS_PER_WINDOW
            );

            $client = new Client([
                'base_uri' => $api_endpoint,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'WP-WooCommerce-Printify-Sync/1.0.0'
                ],
                'timeout' => 30,
                'http_errors' => true
            ]);

            // Test both shops and profile endpoints for complete validation
            $shopResponse = $client->request('GET', '/v1/shops.json');
            $profileResponse = $client->request('GET', '/v1/profile.json');

            $shopData = json_decode($shopResponse->getBody(), true);
            $profileData = json_decode($profileResponse->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from Printify API');
            }

            if (empty($shopData)) {
                throw new \Exception(__('No shops found in your Printify account.', 'wp-woocommerce-printify-sync'));
            }

            if (empty($profileData)) {
                throw new \Exception(__('Could not validate user profile.', 'wp-woocommerce-printify-sync'));
            }

            return true;
        } catch (\Exception $e) {
            $this->handlePrintifyError($e, 'API Key Validation');
        }
    }

    public function testConnection(string $api_key, string $api_endpoint): array {
        try {
            $this->checkRateLimit(
                self::RATE_LIMIT_KEY,
                self::RATE_LIMIT_WINDOW,
                self::MAX_REQUESTS_PER_WINDOW
            );

            $client = new Client([
                'base_uri' => $api_endpoint,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'WP-WooCommerce-Printify-Sync/1.0.0'
                ],
                'timeout' => 30,
                'http_errors' => true
            ]);

            // Get both shops and profile information
            $shopResponse = $client->request('GET', '/v1/shops.json');
            $profileResponse = $client->request('GET', '/v1/profile.json');

            $shopData = json_decode($shopResponse->getBody(), true);
            $profileData = json_decode($profileResponse->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('Invalid response from Printify API', 'wp-woocommerce-printify-sync'));
            }

            return [
                'shops' => $shopData,
                'profile' => $profileData
            ];
        } catch (\Exception $e) {
            $this->handlePrintifyError($e, 'Connection Test');
        }
    }

    public function getShops(): array {
        try {
            $this->checkRateLimit(
                self::RATE_LIMIT_KEY,
                self::RATE_LIMIT_WINDOW,
                self::MAX_REQUESTS_PER_WINDOW
            );

            $response = $this->client->request('GET', '/v1/shops.json');
            
            // Check rate limit headers
            if ($response->hasHeader('X-RateLimit-Remaining')) {
                $remaining = (int) $response->getHeader('X-RateLimit-Remaining')[0];
                if ($remaining < 5) {
                    error_log(sprintf(
                        '[Printify API Warning] Low rate limit remaining: %d requests',
                        $remaining
                    ));
                }
            }

            $data = json_decode($response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('Invalid response from Printify API', 'wp-woocommerce-printify-sync'));
            }

            return $data;
        } catch (\Exception $e) {
            $this->handlePrintifyError($e, 'Get Shops');
        }
    }

    public function validateEndpoint(string $endpoint): bool {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \Exception(__('Invalid API endpoint URL', 'wp-woocommerce-printify-sync'));
        }

        if (!preg_match('/^https:\/\//', $endpoint)) {
            throw new \Exception(__('API endpoint must use HTTPS', 'wp-woocommerce-printify-sync'));
        }

        // Validate endpoint format
        if (!preg_match('/^https:\/\/api\.printify\.com\/v\d+\/?$/', $endpoint)) {
            throw new \Exception(__('Invalid Printify API endpoint format. Should be https://api.printify.com/v1', 'wp-woocommerce-printify-sync'));
        }

        return true;
    }
}