<?php
/**
 * Printify API Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider;

/**
 * Printify API Provider class
 */
class PrintifyAPIProvider extends ServiceProvider
{
    /**
     * Guzzle client instance
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;
    
    /**
     * Settings provider instance
     *
     * @var SettingsProvider
     */
    protected $settings;
    
    /**
     * Printify API settings
     *
     * @var array
     */
    protected $apiSettings = [];
    
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        // Get settings
        $this->settings = $this->getProvider(SettingsProvider::class);
        if ($this->settings) {
            $this->apiSettings = $this->settings->getSettings();
        }
        
        // Register rate limiting and retry hooks
        add_filter('wpwps_api_retry_delay', [$this, 'getRetryDelay'], 10, 2);
        
        // Initialize Guzzle client
        $this->initClient();
    }
    
    /**
     * Initialize Guzzle client
     *
     * @return void
     */
    protected function initClient()
    {
        if (empty($this->apiSettings['printify_api_key']) || empty($this->apiSettings['printify_api_endpoint'])) {
            return;
        }
        
        // Get API key
        $apiKey = $this->settings->decryptApiKey($this->apiSettings['printify_api_key']);
        
        // Initialize Guzzle client
        if (!class_exists('\GuzzleHttp\Client')) {
            require_once WPWPS_PLUGIN_DIR . 'lib/GuzzleHttp/Client.php';
        }
        
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $this->apiSettings['printify_api_endpoint'],
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'http_errors' => false, // Don't throw exceptions on 4xx/5xx
        ]);
    }
    
    /**
     * Make API request to Printify
     *
     * @param string $method  HTTP method (GET, POST, PUT, DELETE)
     * @param string $path    API endpoint path
     * @param array  $options Request options
     * @param int    $retry   Retry attempt counter
     * @return array Response data or error
     */
    public function request($method, $path, $options = [], $retry = 0)
    {
        if (!$this->client) {
            $this->initClient();
        }
        
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'API client not initialized. Please check your Printify API settings.',
                'code' => 0,
            ];
        }
        
        try {
            // Log API request
            do_action('wpwps_api_request', $method, $path, $options, $retry);
            
            // Make the API request
            $response = $this->client->request($method, $path, $options);
            
            // Get response status code
            $statusCode = $response->getStatusCode();
            
            // Parse response body
            $body = json_decode($response->getBody(), true);
            
            // Handle rate limiting
            if ($statusCode === 429 && $retry < 5) {
                // Get retry delay
                $retryDelay = $this->getRetryDelay($retry, $response->getHeader('Retry-After'));
                
                // Log rate limit hit
                error_log(sprintf(
                    'Printify API rate limit hit. Retry %d after %d seconds. Path: %s',
                    $retry + 1,
                    $retryDelay,
                    $path
                ));
                
                // Wait before retrying
                sleep($retryDelay);
                
                // Retry the request
                return $this->request($method, $path, $options, $retry + 1);
            }
            
            // Log API response
            do_action('wpwps_api_response', $method, $path, $options, $statusCode, $body);
            
            // Return success response
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $body,
                    'code' => $statusCode,
                ];
            }
            
            // Return error response
            return [
                'success' => false,
                'message' => isset($body['message']) ? $body['message'] : 'Unknown API error',
                'code' => $statusCode,
                'errors' => isset($body['errors']) ? $body['errors'] : [],
            ];
        } catch (\Exception $e) {
            // Log the error
            error_log('Printify API error: ' . $e->getMessage());
            
            do_action('wpwps_api_response', $method, $path, $options, $e->getCode(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
    
    /**
     * Get retry delay for rate-limited requests
     *
     * @param int   $retry      Retry attempt counter
     * @param array $retryAfter Retry-After header value
     * @return int Delay in seconds
     */
    public function getRetryDelay($retry, $retryAfter = null)
    {
        // Use Retry-After header if available
        if (!empty($retryAfter) && is_numeric($retryAfter[0])) {
            return (int) $retryAfter[0];
        }
        
        // Otherwise use exponential backoff
        return min(pow(2, $retry) * 10, 300); // Max 5 minutes
    }
    
    /**
     * Get shops from Printify API
     *
     * @return array Shops data or error
     */
    public function getShops()
    {
        return $this->request('GET', 'shops.json');
    }
    
    /**
     * Get products from Printify API
     *
     * @param string $shopId  Shop ID
     * @param int    $page    Page number
     * @param int    $perPage Items per page
     * @return array Products data or error
     */
    public function getProducts($shopId, $page = 1, $perPage = 100)
    {
        return $this->request('GET', "shops/{$shopId}/products.json", [
            'query' => [
                'page' => $page,
                'limit' => $perPage,
            ],
        ]);
    }
    
    /**
     * Get product details from Printify API
     *
     * @param string $shopId    Shop ID
     * @param string $productId Product ID
     * @return array Product data or error
     */
    public function getProduct($shopId, $productId)
    {
        return $this->request('GET', "shops/{$shopId}/products/{$productId}.json");
    }
    
    /**
     * Create product in Printify API
     *
     * @param string $shopId Shop ID
     * @param array  $data   Product data
     * @return array Created product data or error
     */
    public function createProduct($shopId, $data)
    {
        return $this->request('POST', "shops/{$shopId}/products.json", [
            'json' => $data,
        ]);
    }
    
    /**
     * Update product in Printify API
     *
     * @param string $shopId    Shop ID
     * @param string $productId Product ID
     * @param array  $data      Product data
     * @return array Updated product data or error
     */
    public function updateProduct($shopId, $productId, $data)
    {
        return $this->request('PUT', "shops/{$shopId}/products/{$productId}.json", [
            'json' => $data,
        ]);
    }
    
    /**
     * Register external product ID with Printify API
     *
     * @param string $productId       Printify product ID
     * @param string $externalId      External product ID (WooCommerce product ID)
     * @param string $externalHandler External handler (woocommerce)
     * @return array Response data or error
     */
    public function registerExternalProduct($productId, $externalId, $externalHandler = 'woocommerce')
    {
        return $this->request('POST', "uploads/external-products.json", [
            'json' => [
                'external_product_id' => $externalId,
                'printify_product_id' => $productId,
                'external_handler' => $externalHandler,
            ],
        ]);
    }
    
    /**
     * Get orders from Printify API
     *
     * @param string $shopId  Shop ID
     * @param int    $page    Page number
     * @param int    $perPage Items per page
     * @return array Orders data or error
     */
    public function getOrders($shopId, $page = 1, $perPage = 100)
    {
        return $this->request('GET', "shops/{$shopId}/orders.json", [
            'query' => [
                'page' => $page,
                'limit' => $perPage,
            ],
        ]);
    }
    
    /**
     * Get order details from Printify API
     *
     * @param string $shopId  Shop ID
     * @param string $orderId Order ID
     * @return array Order data or error
     */
    public function getOrder($shopId, $orderId)
    {
        return $this->request('GET', "shops/{$shopId}/orders/{$orderId}.json");
    }
    
    /**
     * Create order in Printify API
     *
     * @param string $shopId Shop ID
     * @param array  $data   Order data
     * @return array Created order data or error
     */
    public function createOrder($shopId, $data)
    {
        return $this->request('POST', "shops/{$shopId}/orders.json", [
            'json' => $data,
        ]);
    }
    
    /**
     * Get shipping information for a product
     *
     * @param string $shopId       Shop ID
     * @param string $blueprintId  Blueprint ID
     * @param string $providerId   Provider ID
     * @param array  $variantIds   Variant IDs
     * @param string $addressTo    Shipping address
     * @return array Shipping data or error
     */
    public function getShipping($shopId, $blueprintId, $providerId, $variantIds, $addressTo)
    {
        return $this->request('POST', "shops/{$shopId}/shipping.json", [
            'json' => [
                'blueprint_id' => $blueprintId,
                'provider_id' => $providerId,
                'variant_ids' => $variantIds,
                'address_to' => $addressTo,
            ],
        ]);
    }
    
    /**
     * Get print providers from Printify API
     *
     * @return array Print providers data or error
     */
    public function getPrintProviders()
    {
        return $this->request('GET', 'print-providers.json');
    }
    
    /**
     * Get print provider details from Printify API
     *
     * @param string $providerId Provider ID
     * @return array Print provider data or error
     */
    public function getPrintProvider($providerId)
    {
        return $this->request('GET', "print-providers/{$providerId}.json");
    }
    
    /**
     * Get shipping profiles for a print provider
     *
     * @param string $providerId Provider ID
     * @return array Shipping profiles data or error
     */
    public function getShippingProfiles($providerId)
    {
        return $this->request('GET', "print-providers/{$providerId}/shipping.json");
    }
    
    /**
     * Request a reprint for an order
     *
     * @param string $shopId      Shop ID
     * @param string $orderId     Order ID
     * @param string $reason      Reason for reprint
     * @param array  $attachments Array of attachment URLs
     * @return array Reprint request data or error
     */
    public function requestReprint($shopId, $orderId, $reason, $attachments = [])
    {
        return $this->request('POST', "shops/{$shopId}/orders/{$orderId}/actions/reprint.json", [
            'json' => [
                'reason' => $reason,
                'attachments' => $attachments,
            ],
        ]);
    }
    
    /**
     * Request a refund for an order
     *
     * @param string $shopId      Shop ID
     * @param string $orderId     Order ID
     * @param string $reason      Reason for refund
     * @param array  $attachments Array of attachment URLs
     * @return array Refund request data or error
     */
    public function requestRefund($shopId, $orderId, $reason, $attachments = [])
    {
        return $this->request('POST', "shops/{$shopId}/orders/{$orderId}/actions/refund.json", [
            'json' => [
                'reason' => $reason,
                'attachments' => $attachments,
            ],
        ]);
    }
    
    /**
     * Calculate webhook signature
     *
     * @param string $payload   Webhook payload
     * @param string $timestamp Webhook timestamp
     * @param string $secret    Webhook secret
     * @return string Calculated signature
     */
    public function calculateWebhookSignature($payload, $timestamp, $secret)
    {
        $data = $timestamp . '.' . $payload;
        return hash_hmac('sha256', $data, $secret);
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $payload   Webhook payload
     * @param string $signature Webhook signature
     * @param string $timestamp Webhook timestamp
     * @param string $secret    Webhook secret
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature($payload, $signature, $timestamp, $secret)
    {
        $calculatedSignature = $this->calculateWebhookSignature($payload, $timestamp, $secret);
        return hash_equals($calculatedSignature, $signature);
    }
}