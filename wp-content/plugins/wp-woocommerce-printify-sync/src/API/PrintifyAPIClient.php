<?php
/**
 * Printify API Client.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;
use ApolloWeb\WPWooCommercePrintifySync\Services\Cache;

/**
 * Printify API Client class.
 */
class PrintifyAPIClient
{
    /**
     * API endpoint.
     *
     * @var string
     */
    private $api_endpoint;
    
    /**
     * API key.
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Shop ID.
     *
     * @var string
     */
    private $shop_id;
    
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Encryption service.
     *
     * @var EncryptionService
     */
    private $encryption;
    
    /**
     * Last API response.
     *
     * @var array
     */
    private $last_response;

    /**
     * Rate limit information.
     *
     * @var array
     */
    private $rate_limit = [
        'remaining' => 300,
        'reset' => 0
    ];
    
    /**
     * Cache duration for blueprints in seconds (24 hours)
     */
    const BLUEPRINT_CACHE_DURATION = 86400;

    /**
     * Cache duration for shipping profiles in seconds (1 hour)
     */
    const SHIPPING_PROFILE_CACHE_DURATION = 3600;

    /**
     * Cache instance.
     *
     * @var Cache
     */
    private $cache;

    /**
     * Last request timestamp.
     *
     * @var float
     */
    private $last_request = 0;

    /**
     * Rate limit in seconds.
     *
     * @var float
     */
    private $rate_limit_seconds = 1;

    /**
     * Constructor.
     *
     * @param Logger           $logger     Logger instance.
     * @param EncryptionService $encryption Encryption service.
     * @param Cache            $cache      Cache instance.
     */
    public function __construct(Logger $logger, EncryptionService $encryption, Cache $cache)
    {
        $this->logger = $logger;
        $this->encryption = $encryption;
        $this->cache = $cache;
        
        // Load API settings
        $this->api_endpoint = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/');
        $this->api_key = $this->encryption->getKey('wpwps_printify_api_key');
        $this->shop_id = get_option('wpwps_printify_shop_id', '');
        
        // Ensure API endpoint has trailing slash
        if (substr($this->api_endpoint, -1) !== '/') {
            $this->api_endpoint .= '/';
        }
    }
    
    /**
     * Set API key.
     *
     * @param string $api_key API key.
     * @return void
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
    }
    
    /**
     * Set API endpoint.
     *
     * @param string $api_endpoint API endpoint.
     * @return void
     */
    public function setApiEndpoint($api_endpoint)
    {
        $this->api_endpoint = $api_endpoint;
        
        // Ensure API endpoint has trailing slash
        if (substr($this->api_endpoint, -1) !== '/') {
            $this->api_endpoint .= '/';
        }
    }
    
    /**
     * Set shop ID.
     *
     * @param string $shop_id Shop ID.
     * @return void
     */
    public function setShopId($shop_id)
    {
        $this->shop_id = $shop_id;
    }
    
    /**
     * Get API key.
     *
     * @return string API key.
     */
    public function getApiKey()
    {
        return $this->api_key;
    }
    
    /**
     * Get API endpoint.
     *
     * @return string API endpoint.
     */
    public function getApiEndpoint()
    {
        return $this->api_endpoint;
    }
    
    /**
     * Get shop ID.
     *
     * @return string Shop ID.
     */
    public function getShopId()
    {
        return $this->shop_id;
    }
    
    /**
     * Get shops associated with the API key.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getShops()
    {
        return $this->makeRequest('shops.json', 'GET');
    }
    
    /**
     * Get products from the shop.
     *
     * @param int $page     Page number.
     * @param int $per_page Items per page.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getProducts($page = 1, $per_page = 20)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        $params = [
            'page' => $page,
            'limit' => $per_page,
        ];
        
        return $this->makeRequest("shops/{$this->shop_id}/products.json", 'GET', $params);
    }
    
    /**
     * Get a single product by ID.
     *
     * @param string $product_id Printify product ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getProduct($product_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($product_id)) {
            return new \WP_Error('missing_product_id', 'Product ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products/{$product_id}.json", 'GET');
    }
    
    /**
     * Get orders from the shop.
     *
     * @param int $page     Page number.
     * @param int $per_page Items per page.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getOrders($page = 1, $per_page = 20)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        $params = [
            'page' => $page,
            'limit' => $per_page,
        ];
        
        return $this->makeRequest("shops/{$this->shop_id}/orders.json", 'GET', $params);
    }
    
    /**
     * Get a single order by ID.
     *
     * @param string $order_id Printify order ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getOrder($order_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($order_id)) {
            return new \WP_Error('missing_order_id', 'Order ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/orders/{$order_id}.json", 'GET');
    }
    
    /**
     * Create an order in Printify.
     *
     * @param array $order_data Order data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function createOrder($order_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/orders.json", 'POST', $order_data);
    }
    
    /**
     * Get shipping providers for a shop.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getShippingProviders()
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/shipping_providers.json", 'GET');
    }
    
    /**
     * Get print providers list.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getPrintProviders()
    {
        return $this->makeRequest("print-providers.json", 'GET');
    }
    
    /**
     * Get print provider details.
     *
     * @param int $provider_id Provider ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getPrintProvider($provider_id)
    {
        if (empty($provider_id)) {
            return new \WP_Error('missing_provider_id', 'Provider ID is required.');
        }
        
        return $this->makeRequest("print-providers/{$provider_id}.json", 'GET');
    }
    
    /**
     * Get shipping profiles for a shop.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getShippingProfiles()
    {
        $cache_key = 'wpwps_shipping_profiles_' . $this->shop_id;
        $profiles = get_transient($cache_key);

        if ($profiles !== false) {
            return $profiles;
        }

        $response = $this->makeRequest("shops/{$this->shop_id}/shipping_profiles.json", 'GET');

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, self::SHIPPING_PROFILE_CACHE_DURATION);
        }

        return $response;
    }

    /**
     * Create shipping profile.
     *
     * @param array $profile_data Profile data including name, regions, etc.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function createShippingProfile($profile_data)
    {
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles.json", 'POST', $profile_data);
    }

    /**
     * Update shipping profile.
     *
     * @param int   $profile_id   Profile ID.
     * @param array $profile_data Updated profile data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function updateShippingProfile($profile_id, $profile_data)
    {
        delete_transient('wpwps_shipping_profiles_' . $this->shop_id);
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles/{$profile_id}.json", 'PUT', $profile_data);
    }

    /**
     * Delete shipping profile.
     *
     * @param int $profile_id Profile ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function deleteShippingProfile($profile_id)
    {
        delete_transient('wpwps_shipping_profiles_' . $this->shop_id);
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles/{$profile_id}.json", 'DELETE');
    }
    
    /**
     * Create a shipping profile.
     *
     * @param array $profile_data Shipping profile data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function createShippingProfile($profile_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles.json", 'POST', $profile_data);
    }
    
    /**
     * Get shipping profile by ID.
     *
     * @param int $profile_id Shipping profile ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getShippingProfile($profile_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($profile_id)) {
            return new \WP_Error('missing_profile_id', 'Profile ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles/{$profile_id}.json", 'GET');
    }
    
    /**
     * Update shipping profile.
     *
     * @param int   $profile_id   Shipping profile ID.
     * @param array $profile_data Shipping profile data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function updateShippingProfile($profile_id, $profile_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($profile_id)) {
            return new \WP_Error('missing_profile_id', 'Profile ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles/{$profile_id}.json", 'PUT', $profile_data);
    }
    
    /**
     * Delete shipping profile.
     *
     * @param int $profile_id Shipping profile ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function deleteShippingProfile($profile_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($profile_id)) {
            return new \WP_Error('missing_profile_id', 'Profile ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/shipping_profiles/{$profile_id}.json", 'DELETE');
    }
    
    /**
     * Get catalog blueprints.
     *
     * @param array $params Optional parameters for filtering blueprints.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getCatalogBlueprints($params = [])
    {
        return $this->makeRequest('catalog/blueprints.json', 'GET', $params);
    }
    
    /**
     * Get specific catalog blueprint.
     *
     * @param string $blueprint_id Blueprint ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getCatalogBlueprint($blueprint_id)
    {
        if (empty($blueprint_id)) {
            return new \WP_Error('missing_blueprint_id', 'Blueprint ID is required.');
        }
        
        return $this->makeRequest("catalog/blueprints/{$blueprint_id}.json", 'GET');
    }
    
    /**
     * Get print providers for a specific blueprint.
     *
     * @param string $blueprint_id Blueprint ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getBlueprintPrintProviders($blueprint_id)
    {
        if (empty($blueprint_id)) {
            return new \WP_Error('missing_blueprint_id', 'Blueprint ID is required.');
        }
        
        return $this->makeRequest("catalog/blueprints/{$blueprint_id}/print_providers.json", 'GET');
    }
    
    /**
     * Get print provider variants for a blueprint.
     *
     * @param string $blueprint_id  Blueprint ID.
     * @param string $provider_id   Provider ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getBlueprintProviderVariants($blueprint_id, $provider_id)
    {
        if (empty($blueprint_id)) {
            return new \WP_Error('missing_blueprint_id', 'Blueprint ID is required.');
        }
        
        if (empty($provider_id)) {
            return new \WP_Error('missing_provider_id', 'Provider ID is required.');
        }
        
        return $this->makeRequest("catalog/blueprints/{$blueprint_id}/print_providers/{$provider_id}/variants.json", 'GET');
    }
    
    /**
     * Get shipping information for a variant.
     *
     * @param string $blueprint_id Blueprint ID.
     * @param string $provider_id Provider ID.
     * @param string $variant_id Variant ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getVariantShipping($blueprint_id, $provider_id, $variant_id)
    {
        if (empty($blueprint_id) || empty($provider_id) || empty($variant_id)) {
            return new \WP_Error('missing_parameters', 'Blueprint ID, Provider ID, and Variant ID are all required.');
        }
        
        return $this->makeRequest("catalog/blueprints/{$blueprint_id}/print_providers/{$provider_id}/variants/{$variant_id}/shipping.json", 'GET');
    }
    
    /**
     * Calculate shipping cost for an order.
     *
     * @param array $shipping_data Shipping data including items and address.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getShippingRates($shipping_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/orders/shipping.json", 'POST', $shipping_data);
    }
    
    /**
     * Create a webhook subscription.
     *
     * @param array $webhook_data Webhook data including event and url.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function createWebhook($webhook_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/webhooks.json", 'POST', $webhook_data);
    }
    
    /**
     * Get webhooks for a shop.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getWebhooks()
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/webhooks.json", 'GET');
    }
    
    /**
     * Delete a webhook.
     *
     * @param string $webhook_id Webhook ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function deleteWebhook($webhook_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($webhook_id)) {
            return new \WP_Error('missing_webhook_id', 'Webhook ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/webhooks/{$webhook_id}.json", 'DELETE');
    }
    
    /**
     * Publish a product to the shop.
     *
     * @param string $product_id Product ID.
     * @param array  $publish_data Publish data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function publishProduct($product_id, $publish_data = [])
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($product_id)) {
            return new \WP_Error('missing_product_id', 'Product ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products/{$product_id}/publish.json", 'POST', $publish_data);
    }
    
    /**
     * Unpublish a product from the shop.
     *
     * @param string $product_id Product ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function unpublishProduct($product_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($product_id)) {
            return new \WP_Error('missing_product_id', 'Product ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products/{$product_id}/unpublish.json", 'POST');
    }
    
    /**
     * Create a new product in Printify.
     *
     * @param array $product_data Product data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function createProduct($product_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products.json", 'POST', $product_data);
    }
    
    /**
     * Update a product in Printify.
     *
     * @param string $product_id Product ID.
     * @param array $product_data Product data.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function updateProduct($product_id, $product_data)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($product_id)) {
            return new \WP_Error('missing_product_id', 'Product ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products/{$product_id}.json", 'PUT', $product_data);
    }
    
    /**
     * Delete a product from Printify.
     *
     * @param string $product_id Product ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function deleteProduct($product_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($product_id)) {
            return new \WP_Error('missing_product_id', 'Product ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products/{$product_id}.json", 'DELETE');
    }
    
    /**
     * Update order status.
     *
     * @param string $order_id Order ID.
     * @param string $status New status.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function updateOrderStatus($order_id, $status)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($order_id)) {
            return new \WP_Error('missing_order_id', 'Order ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/orders/{$order_id}/status.json", 'POST', ['status' => $status]);
    }
    
    /**
     * Send artwork for review.
     *
     * @param string $product_id Product ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function sendArtworkForReview($product_id)
    {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', 'Shop ID is required.');
        }
        
        if (empty($product_id)) {
            return new \WP_Error('missing_product_id', 'Product ID is required.');
        }
        
        return $this->makeRequest("shops/{$this->shop_id}/products/{$product_id}/send_to_review.json", 'POST');
    }
    
    /**
     * Test connection to the Printify API.
     *
     * @return bool|WP_Error True if connection is successful, WP_Error on failure.
     */
    public function testConnection()
    {
        $response = $this->getShops();
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return true;
    }
    
    /**
     * Upload an image to Printify.
     *
     * @param string $file_path Path to the image file.
     * @param string $filename Optional filename to use.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function uploadImage($file_path, $filename = '')
    {
        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', 'Image file not found.');
        }
        
        if (empty($filename)) {
            $filename = basename($file_path);
        }
        
        // Get file contents
        $file_contents = file_get_contents($file_path);
        if ($file_contents === false) {
            return new \WP_Error('file_read_error', 'Could not read image file.');
        }
        
        // Get mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        if (!$mime_type) {
            $mime_type = 'application/octet-stream';
        }
        
        // Prepare multipart request
        $boundary = wp_generate_password(24, false);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        ];
        
        $payload = '';
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($filename) . '"' . "\r\n";
        $payload .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
        $payload .= $file_contents . "\r\n";
        $payload .= '--' . $boundary . '--';
        
        $response = wp_remote_post($this->api_endpoint . 'uploads/images.json', [
            'method' => 'POST',
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 60,
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->log("Image upload failed: " . $response->get_error_message(), 'error');
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->last_response = [
            'code' => $response_code,
            'body' => $response_body,
        ];
        
        if ($response_code !== 200) {
            $this->logger->log("Image upload failed with status code: {$response_code}", 'error');
            return new \WP_Error('upload_failed', "Image upload failed with status code: {$response_code}");
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->log("Invalid JSON response on image upload: " . json_last_error_msg(), 'error');
            return new \WP_Error('invalid_json', 'Invalid JSON response from API.');
        }
        
        return $data;
    }
    
    /**
     * Get catalog items.
     *
     * @param int $page Page number.
     * @param int $per_page Items per page.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getCatalog($page = 1, $per_page = 20)
    {
        return $this->makeRequest('catalog.json', 'GET', [
            'page' => $page,
            'limit' => $per_page
        ]);
    }

    /**
     * Get blueprint details.
     *
     * @param int $blueprint_id Blueprint ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getBlueprint($blueprint_id)
    {
        return $this->makeRequest("catalog/blueprints/{$blueprint_id}.json", 'GET');
    }

    /**
     * Get all blueprints.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getBlueprints()
    {
        $cache_key = 'wpwps_blueprints';
        $blueprints = get_transient($cache_key);

        if ($blueprints !== false) {
            return $blueprints;
        }

        $response = $this->makeRequest('catalog/blueprints.json', 'GET');

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, self::BLUEPRINT_CACHE_DURATION);
        }

        return $response;
    }

    /**
     * Get blueprint details with variants.
     *
     * @param int $blueprint_id Blueprint ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getBlueprintDetails($blueprint_id)
    {
        $cache_key = 'wpwps_blueprint_' . $blueprint_id;
        $blueprint = get_transient($cache_key);

        if ($blueprint !== false) {
            return $blueprint;
        }

        $response = $this->makeRequest("catalog/blueprints/{$blueprint_id}.json", 'GET');

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, self::BLUEPRINT_CACHE_DURATION);
        }

        return $response;
    }

    /**
     * Get blueprint print providers.
     *
     * @param int $blueprint_id Blueprint ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getBlueprintProviders($blueprint_id)
    {
        return $this->makeRequest("catalog/blueprints/{$blueprint_id}/print_providers.json", 'GET');
    }

    /**
     * Get provider shipping options.
     *
     * @param int   $provider_id Provider ID.
     * @param array $line_items  Array of line items with product and variant IDs.
     * @param array $address     Shipping address.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getProviderShippingOptions($provider_id, $line_items, $address)
    {
        $cache_key = 'wpwps_provider_shipping_' . $provider_id . '_' . md5(serialize($line_items) . serialize($address));
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = [
            'line_items' => $line_items,
            'address_to' => $address
        ];

        $response = $this->makeRequest("print-providers/{$provider_id}/shipping.json", 'POST', $data);

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, HOUR_IN_SECONDS);
        }

        return $response;
    }

    /**
     * Get provider shipping locations.
     *
     * @param int $provider_id Provider ID.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function getProviderLocations($provider_id)
    {
        $cache_key = 'wpwps_provider_locations_' . $provider_id;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest("print-providers/{$provider_id}/locations.json", 'GET');

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, DAY_IN_SECONDS);
        }

        return $response;
    }

    /**
     * Calculate shipping costs for multiple providers.
     *
     * @param array $shipping_requests Array of provider shipping requests.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function calculateMultiProviderShipping($shipping_requests)
    {
        return $this->makeRequest("shipping/calculate-batch.json", 'POST', [
            'requests' => $shipping_requests
        ]);
    }

    /**
     * Handle rate limiting.
     *
     * @param array $response WordPress HTTP response.
     * @return void
     */
    private function handleRateLimit($response)
    {
        $headers = wp_remote_retrieve_headers($response);
        
        if (isset($headers['x-ratelimit-remaining'])) {
            $this->rate_limit['remaining'] = (int) $headers['x-ratelimit-remaining'];
        }
        
        if (isset($headers['x-ratelimit-reset'])) {
            $this->rate_limit['reset'] = (int) $headers['x-ratelimit-reset'];
        }

        if ($this->rate_limit['remaining'] === 0) {
            $sleep_seconds = $this->rate_limit['reset'] - time();
            if ($sleep_seconds > 0) {
                sleep($sleep_seconds);
            }
        }
    }

    /**
     * Make an API request.
     *
     * @param string $endpoint API endpoint.
     * @param string $method   Request method (GET, POST, etc.).
     * @param array  $params   Request parameters.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function makeRequest($endpoint, $method = 'GET', $params = [])
    {
        if (empty($this->api_key)) {
            return new \WP_Error('missing_api_key', 'API key is required.');
        }
        
        // Add rate limiting check
        if ($this->rate_limit['remaining'] === 0) {
            $wait_time = $this->rate_limit['reset'] - time();
            if ($wait_time > 0) {
                $this->logger->info("Rate limit reached, waiting {$wait_time} seconds");
                sleep($wait_time);
            }
        }

        $url = $this->api_endpoint . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if ($method === 'GET' && !empty($params)) {
            $url = add_query_arg($params, $url);
        } elseif ($method !== 'GET' && !empty($params)) {
            $args['body'] = json_encode($params);
        }
        
        $this->logger->log("Making API request to: {$url}", 'info');
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->log("API request failed: " . $response->get_error_message(), 'error');
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->logger->log("API response code: {$response_code}", 'info');
        
        $this->last_response = [
            'code' => $response_code,
            'body' => $response_body,
        ];
        
        // Handle rate limiting headers
        $this->handleRateLimit($response);

        // Check for rate limiting
        if ($response_code === 429) {
            $this->logger->log("API rate limit exceeded.", 'error');
            return new \WP_Error('rate_limit_exceeded', 'API rate limit exceeded.');
        }
        
        // Check for server errors
        if ($response_code >= 500) {
            $this->logger->log("API server error: {$response_code}", 'error');
            return new \WP_Error('server_error', "API server error: {$response_code}");
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->log("Invalid JSON response: " . json_last_error_msg(), 'error');
            return new \WP_Error('invalid_json', 'Invalid JSON response from API.');
        }
        
        // Check for API errors
        if ($response_code >= 400 && isset($data['error'])) {
            $this->logger->log("API error: " . print_r($data['error'], true), 'error');
            return new \WP_Error('api_error', isset($data['error']['message']) ? $data['error']['message'] : 'API error.');
        }
        
        return $data;
    }
    
    /**
     * Get the last API response.
     *
     * @return array Last API response.
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * Rate limit the requests.
     *
     * @return void
     */
    private function rateLimit()
    {
        $elapsed = microtime(true) - $this->last_request;
        if ($elapsed < $this->rate_limit_seconds) {
            usleep(($this->rate_limit_seconds - $elapsed) * 1000000);
        }
        $this->last_request = microtime(true);
    }

    /**
     * Get cache key for the request.
     *
     * @param string $endpoint API endpoint.
     * @param string $method   Request method.
     * @param array  $data     Request data.
     * @return string Cache key.
     */
    private function getCacheKey($endpoint, $method, $data)
    {
        return md5($endpoint . $method . json_encode($data));
    }

    /**
     * Make a request with caching.
     *
     * @param string $endpoint  API endpoint.
     * @param string $method    Request method.
     * @param array  $data      Request data.
     * @param int    $cache_ttl Cache TTL.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function request($endpoint, $method = 'GET', $data = null, $cache_ttl = 300)
    {
        $cache_key = $this->getCacheKey($endpoint, $method, $data);
        
        if ($method === 'GET' && $cached = $this->cache->get($cache_key)) {
            return $cached;
        }

        $this->rateLimit();

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'method' => $method,
            'timeout' => 30,
            'data' => $data ? json_encode($data) : null
        ];

        try {
            $response = wp_remote_request($this->api_endpoint . $endpoint, $args);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($method === 'GET') {
                $this->cache->set($cache_key, $body, $cache_ttl);
            }

            $this->logger->debug(sprintf('API Request: %s %s', $method, $endpoint), [
                'response' => $body,
                'status' => wp_remote_retrieve_response_code($response)
            ]);

            return $body;
        } catch (\Exception $e) {
            $this->logger->error('API Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
