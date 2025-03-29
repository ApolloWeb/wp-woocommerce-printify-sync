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
 * Settings Provider class for plugin configuration
 */
class SettingsProvider extends ServiceProvider
{
    /**
     * Register the provider
     *
     * @return void
     */
    public function register()
    {
        // Add settings submenu
        add_action('admin_menu', [$this, 'registerSettingsPage']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_save_printify_settings', [$this, 'savePrintifySettings']);
        add_action('wp_ajax_wpwps_test_openai_connection', [$this, 'testOpenAIConnection']);
        add_action('wp_ajax_wpwps_save_openai_settings', [$this, 'saveOpenAISettings']);
        
        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
    }
    
    /**
     * Register settings page
     *
     * @return void
     */
    public function registerSettingsPage()
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', WPWPS_TEXT_DOMAIN),
            __('Settings', WPWPS_TEXT_DOMAIN),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Register plugin settings
     *
     * @return void
     */
    public function registerSettings()
    {
        // Printify settings
        register_setting('wpwps_printify_settings', 'wpwps_printify_api_key');
        register_setting('wpwps_printify_settings', 'wpwps_printify_api_endpoint');
        register_setting('wpwps_printify_settings', 'wpwps_printify_shop_id');
        
        // OpenAI settings
        register_setting('wpwps_openai_settings', 'wpwps_openai_api_key');
        register_setting('wpwps_openai_settings', 'wpwps_openai_max_tokens');
        register_setting('wpwps_openai_settings', 'wpwps_openai_temperature');
        register_setting('wpwps_openai_settings', 'wpwps_openai_spend_cap');
    }
    
    /**
     * Render the settings page
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        // Check user capabilities
        if (!$this->userCan('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', WPWPS_TEXT_DOMAIN));
        }
        
        $data = [
            'printify' => [
                'api_key' => $this->getEncryptedOption('wpwps_printify_api_key'),
                'api_endpoint' => get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/'),
                'shop_id' => get_option('wpwps_printify_shop_id'),
                'is_shop_selected' => !empty(get_option('wpwps_printify_shop_id'))
            ],
            'openai' => [
                'api_key' => $this->getEncryptedOption('wpwps_openai_api_key'),
                'max_tokens' => get_option('wpwps_openai_max_tokens', 500),
                'temperature' => get_option('wpwps_openai_temperature', 0.7),
                'spend_cap' => get_option('wpwps_openai_spend_cap', 10)
            ]
        ];
        
        // Render the settings page using the View helper
        $view = new View();
        echo $view->render('wpwps-settings', $data);
    }
    
    /**
     * Test Printify API connection
     *
     * @return void
     */
    public function testPrintifyConnection()
    {
        // Check nonce
        check_ajax_referer('wpwps_nonce', 'nonce');
        
        // Check user permissions
        if (!$this->userCan('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Get API key and endpoint from request
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? sanitize_text_field($_POST['api_endpoint']) : 'https://api.printify.com/v1/';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Initialize Guzzle HTTP client
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $api_endpoint,
                'timeout' => 10,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);
            
            // Call the Printify API to get shops
            $response = $client->get('shops.json');
            
            $shops = json_decode($response->getBody(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => __('Invalid response from Printify API.', WPWPS_TEXT_DOMAIN)]);
                return;
            }
            
            // Set API health status
            set_transient('wpwps_api_health', [
                'status' => 'connected',
                'last_checked' => time()
            ], HOUR_IN_SECONDS);
            
            // Return shops data
            wp_send_json_success([
                'message' => __('Connection successful!', WPWPS_TEXT_DOMAIN),
                'shops' => $shops
            ]);
            
        } catch (\Exception $e) {
            // Set API health status
            set_transient('wpwps_api_health', [
                'status' => 'error',
                'last_checked' => time(),
                'error_message' => $e->getMessage()
            ], HOUR_IN_SECONDS);
            
            wp_send_json_error([
                'message' => __('Connection failed: ', WPWPS_TEXT_DOMAIN) . $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Save Printify settings
     *
     * @return void
     */
    public function savePrintifySettings()
    {
        // Check nonce
        check_ajax_referer('wpwps_nonce', 'nonce');
        
        // Check user permissions
        if (!$this->userCan('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Get settings from request
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? sanitize_text_field($_POST['api_endpoint']) : 'https://api.printify.com/v1/';
        $shop_id = isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '';
        
        // Validate inputs
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        if (empty($shop_id) && empty(get_option('wpwps_printify_shop_id'))) {
            wp_send_json_error(['message' => __('Shop ID is required.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Save settings
        $this->saveEncryptedOption('wpwps_printify_api_key', $api_key);
        update_option('wpwps_printify_api_endpoint', $api_endpoint);
        
        // Only update shop ID if it's not already set or if it's empty
        if (empty(get_option('wpwps_printify_shop_id')) && !empty($shop_id)) {
            update_option('wpwps_printify_shop_id', $shop_id);
        }
        
        wp_send_json_success(['message' => __('Settings saved successfully!', WPWPS_TEXT_DOMAIN)]);
    }
    
    /**
     * Test OpenAI connection
     *
     * @return void
     */
    public function testOpenAIConnection()
    {
        // Check nonce
        check_ajax_referer('wpwps_nonce', 'nonce');
        
        // Check user permissions
        if (!$this->userCan('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Get API key from request
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $max_tokens = isset($_POST['max_tokens']) ? intval($_POST['max_tokens']) : 500;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Initialize Guzzle HTTP client
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ]
            ]);
            
            // Make a simple test request to OpenAI
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => 'Say "Connection to Printify Sync is successful" in one short sentence.']
                    ],
                    'max_tokens' => min($max_tokens, 100), // Limit tokens for test
                    'temperature' => $temperature
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE || empty($data['choices'][0]['message']['content'])) {
                wp_send_json_error(['message' => __('Invalid response from OpenAI API.', WPWPS_TEXT_DOMAIN)]);
                return;
            }
            
            // Calculate estimated monthly cost (approximate)
            $cost_per_1k_tokens = 0.002; // $0.002 per 1K tokens for gpt-3.5-turbo
            $avg_daily_usage = 100; // Estimated average number of calls per day
            $avg_tokens_per_call = $max_tokens; // Average tokens per API call
            
            $monthly_cost = ($avg_tokens_per_call * $avg_daily_usage * 30 / 1000) * $cost_per_1k_tokens;
            $monthly_cost = number_format($monthly_cost, 2);
            
            wp_send_json_success([
                'message' => __('Connection successful!', WPWPS_TEXT_DOMAIN),
                'response' => $data['choices'][0]['message']['content'],
                'estimated_cost' => $monthly_cost
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Connection failed: ', WPWPS_TEXT_DOMAIN) . $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Save OpenAI settings
     *
     * @return void
     */
    public function saveOpenAISettings()
    {
        // Check nonce
        check_ajax_referer('wpwps_nonce', 'nonce');
        
        // Check user permissions
        if (!$this->userCan('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Get settings from request
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $max_tokens = isset($_POST['max_tokens']) ? intval($_POST['max_tokens']) : 500;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $spend_cap = isset($_POST['spend_cap']) ? floatval($_POST['spend_cap']) : 10;
        
        // Validate inputs
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        if ($max_tokens < 100 || $max_tokens > 4000) {
            wp_send_json_error(['message' => __('Max tokens should be between 100 and 4000.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        if ($temperature < 0 || $temperature > 1) {
            wp_send_json_error(['message' => __('Temperature should be between 0 and 1.', WPWPS_TEXT_DOMAIN)]);
            return;
        }
        
        // Save settings
        $this->saveEncryptedOption('wpwps_openai_api_key', $api_key);
        update_option('wpwps_openai_max_tokens', $max_tokens);
        update_option('wpwps_openai_temperature', $temperature);
        update_option('wpwps_openai_spend_cap', $spend_cap);
        
        wp_send_json_success(['message' => __('Settings saved successfully!', WPWPS_TEXT_DOMAIN)]);
    }
    
    /**
     * Save an encrypted option
     *
     * @param string $option_name
     * @param string $value
     * @return bool
     */
    private function saveEncryptedOption($option_name, $value)
    {
        // For now, simply use WordPress's option API with AUTH_KEY as a basic encryption method
        // In a production environment, you'd want to use a proper encryption method like OpenSSL
        if (empty($value)) {
            return delete_option($option_name);
        }
        
        $encrypted = openssl_encrypt(
            $value, 
            'AES-256-CBC', 
            AUTH_KEY, 
            0, 
            substr(AUTH_SALT, 0, 16)
        );
        
        return update_option($option_name, $encrypted);
    }
    
    /**
     * Get an encrypted option
     *
     * @param string $option_name
     * @return string
     */
    private function getEncryptedOption($option_name)
    {
        $encrypted = get_option($option_name, '');
        
        if (empty($encrypted)) {
            return '';
        }
        
        $decrypted = openssl_decrypt(
            $encrypted, 
            'AES-256-CBC', 
            AUTH_KEY, 
            0, 
            substr(AUTH_SALT, 0, 16)
        );
        
        return $decrypted;
    }
}