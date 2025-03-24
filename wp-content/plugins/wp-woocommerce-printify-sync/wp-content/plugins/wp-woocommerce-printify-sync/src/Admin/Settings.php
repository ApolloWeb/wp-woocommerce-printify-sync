<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Container;
use ApolloWeb\WPWooCommercePrintifySync\View\TemplateEngine;

class Settings
{
    private $container;
    private $templateEngine;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->templateEngine = new TemplateEngine();
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'saveSettings']);
        add_action('wp_ajax_wpwps_test_openai', [$this, 'testOpenAiConnection']);
    }

    public function render(): void
    {
        $data = [
            'printify_api_key' => $this->getEncryptedOption('wpwps_printify_api_key'),
            'printify_api_endpoint' => get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1'),
            'printify_shop_id' => get_option('wpwps_printify_shop_id', ''),
            'openai_api_key' => $this->getEncryptedOption('wpwps_openai_api_key'),
            'openai_token_limit' => get_option('wpwps_openai_token_limit', 1000),
            'openai_spend_cap' => get_option('wpwps_openai_spend_cap', 10),
            'openai_temperature' => get_option('wpwps_openai_temperature', 0.7),
        ];

        echo $this->templateEngine->render('admin/settings', $data);
    }

    public function testPrintifyConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
            return;
        }

        $printifyApi = $this->container->get('ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi');
        $response = $printifyApi->testConnection($api_key, $api_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => 'Connection successful!',
            'shops' => $response['data']
        ]);
    }

    public function testOpenAiConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $token_limit = isset($_POST['token_limit']) ? intval($_POST['token_limit']) : 1000;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $spend_cap = isset($_POST['spend_cap']) ? floatval($_POST['spend_cap']) : 10;

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'OpenAI API key is required']);
            return;
        }

        $openAiApi = $this->container->get('ApolloWeb\WPWooCommercePrintifySync\Api\OpenAiApi');
        $response = $openAiApi->testConnection($api_key);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Calculate estimated monthly cost
        $cost_per_token = 0.000002; // Approximate cost per token for GPT-3.5-Turbo
        $estimated_daily_tickets = 10; // Assumed average
        $avg_tokens_per_request = $token_limit * 0.75; // Average usage
        $daily_cost = $estimated_daily_tickets * $avg_tokens_per_request * $cost_per_token;
        $monthly_cost = $daily_cost * 30;

        wp_send_json_success([
            'message' => 'OpenAI API connection successful!',
            'estimated_monthly_cost' => '$' . number_format($monthly_cost, 2),
            'model_info' => $response['data']
        ]);
    }

    public function saveSettings(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Printify settings
        if (isset($_POST['printify_api_key'])) {
            $this->saveEncryptedOption('wpwps_printify_api_key', sanitize_text_field($_POST['printify_api_key']));
        }
        
        if (isset($_POST['printify_api_endpoint'])) {
            update_option('wpwps_printify_api_endpoint', esc_url_raw($_POST['printify_api_endpoint']));
        }
        
        if (isset($_POST['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($_POST['printify_shop_id']));
        }

        // OpenAI settings
        if (isset($_POST['openai_api_key'])) {
            $this->saveEncryptedOption('wpwps_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        }
        
        if (isset($_POST['openai_token_limit'])) {
            update_option('wpwps_openai_token_limit', intval($_POST['openai_token_limit']));
        }
        
        if (isset($_POST['openai_spend_cap'])) {
            update_option('wpwps_openai_spend_cap', floatval($_POST['openai_spend_cap']));
        }
        
        if (isset($_POST['openai_temperature'])) {
            update_option('wpwps_openai_temperature', floatval($_POST['openai_temperature']));
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    private function getEncryptedOption(string $option_name): string
    {
        $encrypted_value = get_option($option_name, '');
        if (empty($encrypted_value)) {
            return '';
        }

        return $this->decrypt($encrypted_value);
    }

    private function saveEncryptedOption(string $option_name, string $value): void
    {
        if (empty($value)) {
            delete_option($option_name);
            return;
        }

        $encrypted_value = $this->encrypt($value);
        update_option($option_name, $encrypted_value);
    }

    private function encrypt(string $value): string
    {
        if (!function_exists('openssl_encrypt')) {
            // Fallback if OpenSSL is not available
            return base64_encode($value);
        }

        $encryption_key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }

    private function decrypt(string $encrypted_value): string
    {
        if (!function_exists('openssl_decrypt')) {
            // Fallback if OpenSSL is not available
            return base64_decode($encrypted_value);
        }

        $encryption_key = $this->getEncryptionKey();
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_value), 2);
        
        return openssl_decrypt($encrypted_data, 'AES-256-CBC', $encryption_key, 0, $iv);
    }

    private function getEncryptionKey(): string
    {
        // Use WordPress authentication keys as an encryption key
        // This is secure because it's unique to each WordPress installation
        if (defined('AUTH_KEY')) {
            return substr(hash('sha256', AUTH_KEY), 0, 32);
        }
        
        // Fallback if AUTH_KEY is not defined
        return substr(hash('sha256', DB_NAME . DB_USER . DB_PASSWORD), 0, 32);
    }
}
