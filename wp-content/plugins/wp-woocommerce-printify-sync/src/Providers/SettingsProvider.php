<?php
/**
 * Settings Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Providers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

/**
 * Settings Provider class
 */
class SettingsProvider extends ServiceProvider
{
    /**
     * Settings group name
     * 
     * @var string
     */
    protected $settingsGroup = 'wpwps_settings';
    
    /**
     * Settings option name
     * 
     * @var string
     */
    protected $optionName = 'wpwps_settings';
    
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify_api', [$this, 'ajaxTestPrintifyApi']);
        add_action('wp_ajax_wpwps_test_openai', [$this, 'ajaxTestOpenAI']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'ajaxSaveSettings']);
    }
    
    /**
     * Register plugin settings
     *
     * @return void
     */
    public function registerSettings()
    {
        register_setting(
            $this->settingsGroup,
            $this->optionName,
            [$this, 'sanitizeSettings']
        );
    }
    
    /**
     * Render the settings page
     *
     * @return void
     */
    public function renderPage()
    {
        $settings = $this->getSettings();
        $data = [
            'settings' => $settings,
            'saved' => isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true',
            'error' => isset($_GET['error']) ? $_GET['error'] : '',
            'shops' => $this->getShops(),
        ];
        
        View::render('wpwps-settings', $data);
    }
    
    /**
     * Get plugin settings
     *
     * @return array
     */
    public function getSettings()
    {
        $defaults = [
            'printify_api_key' => '',
            'printify_api_endpoint' => 'https://api.printify.com/v1/',
            'printify_shop_id' => '',
            'printify_shop_name' => '',
            'openai_api_key' => '',
            'openai_token_limit' => 1000,
            'openai_temperature' => 0.7,
            'openai_spend_cap' => 50.00, // Monthly spend cap in USD
            'email_queue_enabled' => false,
            'email_smtp_host' => '',
            'email_smtp_port' => '',
            'email_smtp_username' => '',
            'email_smtp_password' => '',
            'email_smtp_secure' => 'tls',
            'email_from_name' => '',
            'email_from_email' => '',
            'email_signature' => '',
            'email_social_facebook' => '',
            'email_social_instagram' => '',
            'email_social_tiktok' => '',
            'email_social_youtube' => '',
        ];
        
        $settings = get_option($this->optionName, []);
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Sanitize settings before saving
     *
     * @param array $input Settings input
     * @return array Sanitized settings
     */
    public function sanitizeSettings($input)
    {
        $sanitized = [];
        
        // Printify API settings
        $sanitized['printify_api_key'] = $this->encryptApiKey(sanitize_text_field($input['printify_api_key']));
        $sanitized['printify_api_endpoint'] = esc_url_raw($input['printify_api_endpoint']);
        $sanitized['printify_shop_id'] = sanitize_text_field($input['printify_shop_id']);
        $sanitized['printify_shop_name'] = sanitize_text_field($input['printify_shop_name']);
        
        // OpenAI API settings
        $sanitized['openai_api_key'] = $this->encryptApiKey(sanitize_text_field($input['openai_api_key']));
        $sanitized['openai_token_limit'] = absint($input['openai_token_limit']);
        $sanitized['openai_temperature'] = floatval($input['openai_temperature']);
        $sanitized['openai_spend_cap'] = floatval($input['openai_spend_cap']);
        
        // Email settings
        $sanitized['email_queue_enabled'] = isset($input['email_queue_enabled']) && $input['email_queue_enabled'] === 'on';
        $sanitized['email_smtp_host'] = sanitize_text_field($input['email_smtp_host']);
        $sanitized['email_smtp_port'] = absint($input['email_smtp_port']);
        $sanitized['email_smtp_username'] = sanitize_text_field($input['email_smtp_username']);
        
        // Only encrypt password if it's not empty
        if (!empty($input['email_smtp_password'])) {
            $sanitized['email_smtp_password'] = $this->encryptApiKey(sanitize_text_field($input['email_smtp_password']));
        } else {
            // Keep the existing password
            $settings = $this->getSettings();
            $sanitized['email_smtp_password'] = $settings['email_smtp_password'];
        }
        
        $sanitized['email_smtp_secure'] = in_array($input['email_smtp_secure'], ['tls', 'ssl']) ? $input['email_smtp_secure'] : 'tls';
        $sanitized['email_from_name'] = sanitize_text_field($input['email_from_name']);
        $sanitized['email_from_email'] = sanitize_email($input['email_from_email']);
        $sanitized['email_signature'] = wp_kses_post($input['email_signature']);
        
        // Social links
        $sanitized['email_social_facebook'] = esc_url_raw($input['email_social_facebook']);
        $sanitized['email_social_instagram'] = esc_url_raw($input['email_social_instagram']);
        $sanitized['email_social_tiktok'] = esc_url_raw($input['email_social_tiktok']);
        $sanitized['email_social_youtube'] = esc_url_raw($input['email_social_youtube']);
        
        return $sanitized;
    }
    
    /**
     * AJAX handler for testing Printify API connection
     *
     * @return void
     */
    public function ajaxTestPrintifyApi()
    {
        // Check nonce and capabilities
        if (!$this->verifyNonce() || !$this->checkCapability()) {
            wp_send_json_error(['message' => __('Unauthorized access', 'wp-woocommerce-printify-sync')], 403);
        }
        
        // Get API key and endpoint
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $endpoint = isset($_POST['endpoint']) ? esc_url_raw($_POST['endpoint']) : 'https://api.printify.com/v1/';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
        }
        
        // Test API connection
        try {
            // Initialize Guzzle client
            $client = new \GuzzleHttp\Client([
                'base_uri' => $endpoint,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);
            
            // Make API request to get shops
            $response = $client->get('shops.json');
            $body = json_decode($response->getBody(), true);
            
            // Check if response contains shops
            if (isset($body['data']) && is_array($body['data'])) {
                $shops = [];
                foreach ($body['data'] as $shop) {
                    if (isset($shop['id']) && isset($shop['title'])) {
                        $shops[] = [
                            'id' => $shop['id'],
                            'title' => $shop['title'],
                        ];
                    }
                }
                
                wp_send_json_success([
                    'message' => __('API connection successful', 'wp-woocommerce-printify-sync'),
                    'shops' => $shops,
                ]);
            } else {
                wp_send_json_error(['message' => __('Invalid API response', 'wp-woocommerce-printify-sync')]);
            }
        } catch (\Exception $e) {
            // Log the error
            error_log('Printify API error: ' . $e->getMessage());
            
            wp_send_json_error([
                'message' => __('API connection failed', 'wp-woocommerce-printify-sync') . ': ' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * AJAX handler for testing OpenAI API
     *
     * @return void
     */
    public function ajaxTestOpenAI()
    {
        // Check nonce and capabilities
        if (!$this->verifyNonce() || !$this->checkCapability()) {
            wp_send_json_error(['message' => __('Unauthorized access', 'wp-woocommerce-printify-sync')], 403);
        }
        
        // Get API key and settings
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $token_limit = isset($_POST['token_limit']) ? absint($_POST['token_limit']) : 1000;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $spend_cap = isset($_POST['spend_cap']) ? floatval($_POST['spend_cap']) : 50.00;
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
        }
        
        // Test OpenAI API connection
        try {
            // Initialize Guzzle client
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
            ]);
            
            // Make a simple API request to check the API key
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant.',
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Say hi!',
                        ],
                    ],
                    'max_tokens' => 50,
                    'temperature' => $temperature,
                ],
            ]);
            
            $body = json_decode($response->getBody(), true);
            
            // Calculate estimated monthly cost
            $estimated_usage = 30 * 10; // 10 requests per day for a month
            $token_cost_per_1k = 0.002; // $0.002 per 1K tokens for GPT-3.5-turbo
            $estimated_cost = ($token_limit / 1000) * $token_cost_per_1k * $estimated_usage;
            
            wp_send_json_success([
                'message' => __('API connection successful', 'wp-woocommerce-printify-sync'),
                'estimated_cost' => $estimated_cost,
                'spend_cap' => $spend_cap,
                'within_cap' => $estimated_cost <= $spend_cap,
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('OpenAI API error: ' . $e->getMessage());
            
            wp_send_json_error([
                'message' => __('API connection failed', 'wp-woocommerce-printify-sync') . ': ' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * AJAX handler for saving settings
     *
     * @return void
     */
    public function ajaxSaveSettings()
    {
        // Check nonce and capabilities
        if (!$this->verifyNonce() || !$this->checkCapability()) {
            wp_send_json_error(['message' => __('Unauthorized access', 'wp-woocommerce-printify-sync')], 403);
        }
        
        // Get settings from POST
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        if (empty($settings)) {
            wp_send_json_error(['message' => __('No settings provided', 'wp-woocommerce-printify-sync')]);
        }
        
        // Sanitize settings
        $sanitized = $this->sanitizeSettings($settings);
        
        // Save settings
        update_option($this->optionName, $sanitized);
        
        wp_send_json_success([
            'message' => __('Settings saved successfully', 'wp-woocommerce-printify-sync'),
        ]);
    }
    
    /**
     * Get list of shops from Printify API
     *
     * @return array
     */
    protected function getShops()
    {
        $settings = $this->getSettings();
        $api_key = $this->decryptApiKey($settings['printify_api_key']);
        $endpoint = $settings['printify_api_endpoint'];
        
        if (empty($api_key)) {
            return [];
        }
        
        $shops = get_transient('wpwps_printify_shops');
        
        if (false === $shops) {
            try {
                // Initialize Guzzle client
                $client = new \GuzzleHttp\Client([
                    'base_uri' => $endpoint,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                ]);
                
                // Make API request to get shops
                $response = $client->get('shops.json');
                $body = json_decode($response->getBody(), true);
                
                // Check if response contains shops
                if (isset($body['data']) && is_array($body['data'])) {
                    $shops = [];
                    foreach ($body['data'] as $shop) {
                        if (isset($shop['id']) && isset($shop['title'])) {
                            $shops[] = [
                                'id' => $shop['id'],
                                'title' => $shop['title'],
                            ];
                        }
                    }
                    
                    // Cache shops for 1 hour
                    set_transient('wpwps_printify_shops', $shops, HOUR_IN_SECONDS);
                    
                    return $shops;
                }
            } catch (\Exception $e) {
                // Log the error
                error_log('Printify API error: ' . $e->getMessage());
                
                return [];
            }
        }
        
        return $shops;
    }
    
    /**
     * Encrypt an API key
     *
     * @param string $key API key
     * @return string Encrypted key
     */
    protected function encryptApiKey($key)
    {
        if (empty($key)) {
            return '';
        }
        
        // This is a simple encryption for demonstration purposes
        // In a production environment, you would use a more secure method
        $salt = wp_salt('auth');
        $encrypted = base64_encode(openssl_encrypt($key, 'AES-256-CBC', $salt, 0, substr($salt, 0, 16)));
        
        return $encrypted;
    }
    
    /**
     * Decrypt an API key
     *
     * @param string $encrypted Encrypted key
     * @return string Decrypted key
     */
    protected function decryptApiKey($encrypted)
    {
        if (empty($encrypted)) {
            return '';
        }
        
        // This is a simple decryption for demonstration purposes
        // In a production environment, you would use a more secure method
        $salt = wp_salt('auth');
        $decrypted = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $salt, 0, substr($salt, 0, 16));
        
        return $decrypted;
    }
}