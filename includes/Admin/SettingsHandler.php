<?php
/**
 * Settings Handler Class
 * Handles saving and encrypting API keys
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SettingsHandler
 */
class SettingsHandler {
    
    /**
     * Encryption key
     *
     * @var string
     */
    private $encryption_key;
    
    /**
     * Encryption salt
     *
     * @var string
     */
    private $encryption_salt;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set up encryption keys - ideally these would be defined in wp-config.php
        $this->encryption_key = defined('PRINTIFY_SYNC_ENCRYPTION_KEY') ? 
            PRINTIFY_SYNC_ENCRYPTION_KEY : wp_salt('auth');
        $this->encryption_salt = defined('PRINTIFY_SYNC_ENCRYPTION_SALT') ? 
            PRINTIFY_SYNC_ENCRYPTION_SALT : wp_salt('secure_auth');
        
        // Register AJAX handlers
        add_action('wp_ajax_printify_sync_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_printify_sync_test_connection', [$this, 'ajax_test_connection']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Enqueue scripts and styles for the settings page
     *
     * @param string $hook The current admin page
     */
    public function enqueue_scripts($hook) {
        // Only load on our settings page
        if (strpos($hook, 'printify-settings') === false) {
            return;
        }
        
        wp_enqueue_script(
            'printify-sync-settings', 
            PRINTIFY_SYNC_URL . 'assets/js/admin-settings.js', 
            ['jquery'], 
            PRINTIFY_SYNC_VERSION, 
            true
        );
        
        wp_localize_script(
            'printify-sync-settings', 
            'printifySyncSettings', 
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('printify_sync_settings_nonce')
            ]
        );
    }
    
    /**
     * Handle AJAX request to save settings
     */
    public function ajax_save_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_settings_nonce')) {
            wp_send_json_error('Security check failed.');
            exit;
        }
        
        // Get section
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';
        if (empty($section)) {
            wp_send_json_error('Missing section parameter.');
            exit;
        }
        
        // Handle different sections
        switch ($section) {
            case 'printify':
                $this->save_printify_settings();
                break;
            
            case 'geolocation':
                $this->save_geolocation_settings();
                break;
            
            case 'currency':
                $this->save_currency_settings();
                break;
            
            case 'postman':
                $this->save_postman_settings();
                break;
                
            case 'environment':
                $this->save_environment_settings();
                break;
            
            default:
                wp_send_json_error('Invalid settings section.');
                exit;
        }
    }
    
    /**
     * Save Printify API settings
     */
    private function save_printify_settings() {
        // Get and sanitize input
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $endpoint = isset($_POST['endpoint']) ? sanitize_text_field($_POST['endpoint']) : 'production';
        
        // Validate inputs
        if (empty($api_key)) {
            wp_send_json_error('API key cannot be empty.');
            exit;
        }
        
        if (!in_array($endpoint, ['production', 'sandbox'])) {
            $endpoint = 'production';
        }
        
        // Check if the API key is already masked
        if (preg_match('/^\*+$/', $api_key)) {
            // Keep existing encrypted key
            $success = update_option('printify_sync_endpoint', $endpoint);
        } else {
            // Encrypt and save new API key
            $encrypted_key = $this->encrypt($api_key);
            $success = update_option('printify_sync_api_key', $encrypted_key);
            $success = $success && update_option('printify_sync_endpoint', $endpoint);
            
            // Store masked version for display
            $masked_key = $this->mask_string($api_key);
            update_option('printify_sync_api_key_masked', $masked_key);
        }
        
        // Return response
        if ($success) {
            wp_send_json_success([
                'message' => 'Printify API settings saved successfully.',
                'masked_api_key' => get_option('printify_sync_api_key_masked')
            ]);
        } else {
            wp_send_json_error('Failed to save Printify API settings.');
        }
    }
    
    /**
     * Save Geolocation API settings
     */
    private function save_geolocation_settings() {
        // Get and sanitize input
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        // Validate inputs
        if (empty($api_key)) {
            wp_send_json_error('API key cannot be empty.');
            exit;
        }
        
        // Check if the API key is already masked
        if (preg_match('/^\*+$/', $api_key)) {
            // Keep existing key, no update needed
            $success = true;
        } else {
            // Encrypt and save new API key
            $encrypted_key = $this->encrypt($api_key);
            $success = update_option('printify_sync_geolocation_api_key', $encrypted_key);
            
            // Store masked version for display
            $masked_key = $this->mask_string($api_key);
            update_option('printify_sync_geolocation_api_key_masked', $masked_key);
        }
        
        // Return response
        if ($success) {
            wp_send_json_success([
                'message' => 'Geolocation API settings saved successfully.',
                'masked_value' => get_option('printify_sync_geolocation_api_key_masked')
            ]);
        } else {
            wp_send_json_error('Failed to save Geolocation API settings.');
        }
    }
    
    /**
     * Save Currency API settings
     */
    private function save_currency_settings() {
        // Get and sanitize input
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        // Validate inputs
        if (empty($api_key)) {
            wp_send_json_error('API key cannot be empty.');
            exit;
        }
        
        // Check if the API key is already masked
        if (preg_match('/^\*+$/', $api_key)) {
            // Keep existing key, no update needed
            $success = true;
        } else {
            // Encrypt and save new API key
            $encrypted_key = $this->encrypt($api_key);
            $success = update_option('printify_sync_currency_api_key', $encrypted_key);
            
            // Store masked version for display
            $masked_key = $this->mask_string($api_key);
            update_option('printify_sync_currency_api_key_masked', $masked_key);
        }
        
        // Return response
        if ($success) {
            wp_send_json_success([
                'message' => 'Currency API settings saved successfully.',
                'masked_value' => get_option('printify_sync_currency_api_key_masked')
            ]);
        } else {
            wp_send_json_error('Failed to save Currency API settings.');
        }
    }
    
    /**
     * Save Postman API settings
     */
    private function save_postman_settings() {
        // Get and sanitize input
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        // Validate inputs
        if (empty($api_key)) {
            wp_send_json_error('API key cannot be empty.');
            exit;
        }
        
        // Check if the API key is already masked
        if (preg_match('/^\*+$/', $api_key)) {
            // Keep existing key, no update needed
            $success = true;
        } else {
            // Encrypt and save new API key
            $encrypted_key = $this->encrypt($api_key);
            $success = update_option('printify_sync_postman_api_key', $encrypted_key);
            
            // Store masked version for display
            $masked_key = $this->mask_string($api_key);
            update_option('printify_sync_postman_api_key_masked', $masked_key);
        }
        
        // Return response
        if ($success) {
            wp_send_json_success([
                'message' => 'Postman API settings saved successfully.',
                'masked_value' => get_option('printify_sync_postman_api_key_masked')
            ]);
        } else {
            wp_send_json_error('Failed to save Postman API settings.');
        }
    }
    
    /**
     * Save Environment settings
     */
    private function save_environment_settings() {
        // Get and sanitize input
        $environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'production';
        
        // Validate environment
        if (!in_array($environment, ['production', 'development'])) {
            $environment = 'production';
        }
        
        // Save setting
        $success = update_option('printify_sync_environment', $environment);
        
        // Return response
        if ($success) {
            wp_send_json_success([
                'message' => 'Environment settings saved successfully. Some changes may require a page refresh to take effect.',
                'environment' => $environment
            ]);
        } else {
            wp_send_json_error('Failed to save environment settings.');
        }
    }
    
    /**
     * Handle AJAX request to test connection
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_settings_nonce')) {
            wp_send_json_error('Security check failed.');
            exit;
        }
        
        // Get section
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';
        if (empty($section)) {
            wp_send_json_error('Missing section parameter.');
            exit;
        }
        
        // Handle different API tests
        switch ($section) {
            case 'printify':
                $this->test_printify_connection();
                break;
            
            case 'geolocation':
                $this->test_geolocation_connection();
                break;
            
            case 'currency':
                $this->test_currency_connection();
                break;
            
            case 'postman':
                $this->test_postman_connection();
                break;
            
            default:
                wp_send_json_error('Invalid API section.');
                exit;
        }
    }
    
    /**
     * Test Printify API connection
     */
    private function test_printify_connection() {
        // Get the stored API key
        $encrypted_key = get_option('printify_sync_api_key');
        if (empty($encrypted_key)) {
            wp_send_json_error('API key not configured. Please save API settings first.');
            exit;
        }
        
        // Decrypt the API key
        $api_key = $this->decrypt($encrypted_key);
        $endpoint = get_option('printify_sync_endpoint', 'production');
        
        // Determine API base URL
        $base_url = $endpoint === 'production' 
            ? 'https://api.printify.com/v1'
            : 'https://api.sandbox-printify.com/v1';
        
        // Make the API request
        $response = wp_remote_get(
            $base_url . '/shops.json',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 15
            ]
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error('API connection failed: ' . $response->get_error_message());
            exit;
        }
        
        // Check the response code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = isset($error['message']) ? $error['message'] : 'Unknown error';
            
            wp_send_json_error('API returned error ' . $status_code . ': ' . $error_message);
            exit;
        }
        
        // Return success response
        wp_send_json_success([
            'message' => 'Printify API connection successful!'
        ]);
    }
    
    /**
     * Test Geolocation API connection
     */
    private function test_geolocation_connection() {
        // Get the stored API key
        $encrypted_key = get_option('printify_sync_geolocation_api_key');
        if (empty($encrypted_key)) {
            wp_send_json_error('API key not configured. Please save API settings first.');
            exit;
        }
        
        // Decrypt the API key
        $api_key = $this->decrypt($encrypted_key);
        
        // Make the API request
        $response = wp_remote_get(
            'https://api.ipgeolocation.io/ipgeo?apiKey=' . $api_key,
            [
                'timeout' => 15
            ]
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error('API connection failed: ' . $response->get_error_message());
            exit;
        }
        
        // Check the response code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = isset($error['message']) ? $error['message'] : 'Unknown error';
            
            wp_send_json_error('API returned error ' . $status_code . ': ' . $error_message);
            exit;
        }
        
        // Return success response
        wp_send_json_success([
            'message' => 'Geolocation API connection successful!'
        ]);
    }
    
    /**
     * Test Currency API connection
     */
    private function test_currency_connection() {
        // Get the stored API key
        $encrypted_key = get_option('printify_sync_currency_api_key');
        if (empty($encrypted_key)) {
            wp_send_json_error('API key not configured. Please save API settings first.');
            exit;
        }
        
        // Decrypt the API key
        $api_key = $this->decrypt($encrypted_key);
        
        // Make the API request
        $response = wp_remote_get(
            'https://api.freecurrencyapi.com/v1/status?apikey=' . $api_key,
            [
                'timeout' => 15
            ]
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error('API connection failed: ' . $response->get_error_message());
            exit;
        }
        
        // Check the response code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = isset($error['message']) ? $error['message'] : 'Unknown error';
            
            wp_send_json_error('API returned error ' . $status_code . ': ' . $error_message);
            exit;
        }
        
        // Return success response
        wp_send_json_success([
            'message' => 'Currency API connection successful!'
        ]);
    }
    
    /**
     * Test Postman API connection
     */
    private function test_postman_connection() {
        // Get the stored API key
        $encrypted_key = get_option('printify_sync_postman_api_key');
        if (empty($encrypted_key)) {
            wp_send_json_error('API key not configured. Please save API settings first.');
            exit;
        }
        
        // Decrypt the API key
        $api_key = $this->decrypt($encrypted_key);
        
        // Make the API request
        $response = wp_remote_get(
            'https://api.getpostman.com/me',
            [
                'headers' => [
                    'X-Api-Key' => $api_key
                ],
                'timeout' => 15
            ]
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error('API connection failed: ' . $response->get_error_message());
            exit;
        }
        
        // Check the response code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = isset($error['error']['message']) ? $error['error']['message'] : 'Unknown error';
            
            wp_send_json_error('API returned error ' . $status_code . ': ' . $error_message);
            exit;
        }
        
        // Return success response
        wp_send_json_success([
            'message' => 'Postman API connection successful!'
        ]);
    }
    
    /**
     * Encrypt a string using OpenSSL
     *
     * @param string $string The string to encrypt
     * @return string The encrypted string
     */
    private function encrypt($string) {
        if (empty($string)) {
            return '';
        }
        
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', $this->encryption_key), 0, 32);
        $iv = substr(hash('sha256', $this->encryption_salt), 0, 16);
        
        $encrypted = openssl_encrypt($string, $method, $key, 0, $iv);
        
        return base64_encode($encrypted);
    }
    
    /**
     * Decrypt a string using OpenSSL
     *
     * @param string $encrypted The encrypted string
     * @return string The decrypted string
     */
    private function decrypt($encrypted) {
        if (empty($encrypted)) {
            return '';
        }
        
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', $this->encryption_key), 0, 32);
        $iv = substr(hash('sha256', $this->encryption_salt), 0, 16);
        
        $decrypted = openssl_decrypt(base64_decode($encrypted), $method, $key, 0, $iv);
        
        return $decrypted;
    }
    
    /**
     * Mask a string for display
     *
     * @param string $string The string to mask
     * @return string The masked string
     */
    private function mask_string($string) {
        if (empty($string)) {
            return '';
        }
        
        $length = strlen($string);
        $visible = min(4, $length);
        $masked_length = $length - $visible;
        
        return str_repeat('*', $masked_length) . substr($string, -$visible);
    }
}