<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyClient;
use ApolloWeb\WPWooCommercePrintifySync\API\Exceptions\PrintifyApiException;
use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;

class AjaxHandler {
    private EncryptionService $encryptionService;

    public function __construct() {
        $this->encryptionService = new EncryptionService();
    }

    public function init(): void {
        add_action('wp_ajax_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_test_openai_connection', [$this, 'testOpenAIConnection']);
        add_action('wp_ajax_save_settings', [$this, 'saveSettings']);
    }

    public function testPrintifyConnection(): void {
        check_ajax_referer('wp-woocommerce-printify-sync-settings', 'nonce');

        try {
            if (empty($_POST['api_key'])) {
                throw new \Exception('API key is required');
            }

            $apiKey = sanitize_text_field($_POST['api_key']);
            $endpoint = sanitize_url($_POST['endpoint'] ?? 'https://api.printify.com/v1');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Testing Printify connection with endpoint: ' . $endpoint);
            }
            
            $client = new PrintifyClient($apiKey, null, $endpoint);
            $shops = $client->getShops();
            
            // Store the API key only if connection test succeeds
            update_option('printify_api_key', $this->encryptionService->encrypt($apiKey));
            update_option('printify_api_endpoint', $endpoint);
            
            wp_send_json_success([
                'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
                'shops' => $shops
            ]);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Printify connection error: ' . $e->getMessage());
            }
            wp_send_json_error([
                'message' => $e->getMessage(),
                'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : []
            ]);
        }
    }

    public function testOpenAIConnection(): void {
        check_ajax_referer('wp-woocommerce-printify-sync-settings', 'nonce');
        
        try {
            $api_key = sanitize_text_field($_POST['api_key']);
            $token_limit = (int)$_POST['token_limit'];
            $monthly_spend_cap = (float)$_POST['monthly_spend_cap'];
            
            // Get current credit balance from OpenAI API
            $credit_balance = $this->getOpenAICredit($api_key);
            update_option('credit_balance', $credit_balance);
            
            // Estimate cost based on $0.002 per 1K tokens
            $estimated_daily_requests = 100;
            $estimated_monthly_cost = ($token_limit * $estimated_daily_requests * 30 * 0.002) / 1000;
            
            if ($credit_balance < 2) {
                update_option('openai_low_credit_alert', true);
            } else {
                update_option('openai_low_credit_alert', false);
            }
            
            wp_send_json_success([
                'estimated_cost' => number_format($estimated_monthly_cost, 2),
                'credit_balance' => number_format($credit_balance, 2)
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function saveSettings(): void {
        check_ajax_referer('wp-woocommerce-printify-sync-settings', 'nonce');
        
        try {
            $settings = [
                'printify_api_key' => $this->encryptionService->encrypt(sanitize_text_field($_POST['printify_api_key'])),
                'printify_api_endpoint' => sanitize_url($_POST['printify_api_endpoint']),
                'printify_shop' => sanitize_text_field($_POST['printify_shop']),
                'openai_api_key' => $this->encryptionService->encrypt(sanitize_text_field($_POST['openai_api_key'])),
                'token_limit' => (int)$_POST['token_limit'],
                'temperature' => (float)$_POST['temperature'],
                'monthly_spend_cap' => (float)$_POST['monthly_spend_cap']
            ];
            
            foreach ($settings as $key => $value) {
                update_option($key, $value);
            }

            // Update credit balance if OpenAI key is provided
            if (!empty($_POST['openai_api_key'])) {
                $credit_balance = $this->getOpenAICredit($_POST['openai_api_key']);
                update_option('credit_balance', $credit_balance);
                if ($credit_balance < 2) {
                    update_option('openai_low_credit_alert', true);
                }
            }
            
            wp_send_json_success([
                'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync'),
                'credit_balance' => get_option('credit_balance', 0)
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getDecryptedApiKey(string $key): string {
        $encrypted = get_option($key, '');
        return $encrypted ? $this->encryptionService->decrypt($encrypted) : '';
    }

    private function getOpenAICredit(string $api_key): float {
        try {
            // TODO: Implement actual OpenAI API call
            // For now, return a test value
            return 10.00;
        } catch (\Exception $e) {
            error_log('OpenAI Credit Check Error: ' . $e->getMessage());
            return 0.00;
        }
    }
}
