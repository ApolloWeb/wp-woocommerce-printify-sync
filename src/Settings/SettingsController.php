<?php
/**
 * Settings Controller
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Settings
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

use ApolloWeb\WPWooCommercePrintifySync\Utils\Encryption;

class SettingsController {
    /**
     * @var SettingsModel
     */
    private $model;
    
    public function __construct() {
        $this->model = new SettingsModel();
    }
    
    /**
     * Test connection to Printify API
     *
     * @param string $apiKey Printify API key
     * @param string $endpoint API endpoint URL
     * @return array|\WP_Error Shops list or error
     */
    public function testPrintifyConnection(string $apiKey, string $endpoint) {
        // Ensure endpoint has trailing slash
        $endpoint = trailingslashit($endpoint);
        
        // Make API request to get shops
        $response = wp_remote_get(
            $endpoint . 'shops.json',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error(
                'api_error',
                sprintf(
                    __('API returned error code: %d', 'wp-woocommerce-printify-sync'),
                    $code
                )
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'json_error',
                __('Invalid JSON response from API', 'wp-woocommerce-printify-sync')
            );
        }
        
        // Return the shops data
        return $data['data'] ?? [];
    }
    
    /**
     * Save plugin settings
     *
     * @param array $settings Settings from form
     * @return bool|\WP_Error True on success or error
     */
    public function saveSettings(array $settings) {
        // Sanitize and validate settings
        $printifyApiKey = sanitize_text_field($settings['printify_api_key'] ?? '');
        $printifyEndpoint = esc_url_raw($settings['printify_endpoint'] ?? '');
        $shopId = absint($settings['shop_id'] ?? 0);
        $chatGptApiKey = sanitize_text_field($settings['chatgpt_api_key'] ?? '');
        $monthlySpendCap = floatval($settings['monthly_spend_cap'] ?? 0);
        $temperature = floatval($settings['temperature'] ?? 0.7);
        
        // Encrypt sensitive data
        $encryption = new Encryption();
        $encryptedPrintifyKey = $printifyApiKey ? $encryption->encrypt($printifyApiKey) : '';
        $encryptedChatGptKey = $chatGptApiKey ? $encryption->encrypt($chatGptApiKey) : '';
        
        // Save to database
        $savedSettings = [
            'printify_api_key' => $encryptedPrintifyKey,
            'printify_endpoint' => $printifyEndpoint,
            'shop_id' => $shopId,
            'chatgpt_api_key' => $encryptedChatGptKey,
            'monthly_spend_cap' => $monthlySpendCap,
            'temperature' => $temperature
        ];
        
        $result = $this->model->saveSettings($savedSettings);
        
        if (!$result) {
            return new \WP_Error(
                'save_error',
                __('Failed to save settings', 'wp-woocommerce-printify-sync')
            );
        }
        
        return true;
    }
    
    /**
     * Test connection to ChatGPT API
     *
     * @param string $apiKey ChatGPT API key
     * @param float $temperature Temperature setting (0-1)
     * @return array|\WP_Error Cost estimate or error
     */
    public function testChatGptConnection(string $apiKey, float $temperature) {
        // Use dedicated API handler instead of implementing it here
        $chatGptHandler = new \ApolloWeb\WPWooCommercePrintifySync\API\ChatGPTHandler(
            $apiKey,
            $temperature
        );
        
        return $chatGptHandler->testConnection();
    }
    
    /**
     * Get all settings with decrypted values
     *
     * @return array Settings array
     */
    public function getSettings(): array {
        $settings = $this->model->getSettings();
        
        // Decrypt sensitive values
        $encryption = new Encryption();
        
        if (!empty($settings['printify_api_key'])) {
            $settings['printify_api_key'] = $encryption->decrypt($settings['printify_api_key']);
        }
        
        if (!empty($settings['chatgpt_api_key'])) {
            $settings['chatgpt_api_key'] = $encryption->decrypt($settings['chatgpt_api_key']);
        }
        
        return $settings;
    }
}
