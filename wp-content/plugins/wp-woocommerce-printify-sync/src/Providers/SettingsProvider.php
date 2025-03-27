<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;

class SettingsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerAdminMenu(
            __('Printify Settings', 'wp-woocommerce-printify-sync'),
            __('Printify', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );

        $this->registerAjaxEndpoint('wpwps_test_connection', [$this, 'testConnection']);
        $this->registerAjaxEndpoint('wpwps_save_settings', [$this, 'saveSettings']);
    }

    public function renderSettingsPage(): void
    {
        $settings = get_option('wpwps_settings', [
            'api_key' => '',
            'api_endpoint' => 'https://api.printify.com/v1',
            'shop_id' => '',
            'openai_api_key' => '',
            'token_limit' => 2000,
            'temperature' => 0.7,
            'monthly_cap' => 100
        ]);

        echo $this->view->render('wpwps-settings', ['settings' => $settings]);
    }

    public function testConnection(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        
        // Test Printify connection using GuzzleHttp
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.printify.com/v1/',
                'headers' => [
                    'Authorization' => "Bearer {$api_key}",
                    'Content-Type' => 'application/json'
                ]
            ]);

            $response = $client->get('shops.json');
            $shops = json_decode($response->getBody()->getContents(), true);

            wp_send_json_success([
                'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
                'shops' => $shops
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function saveSettings(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        $settings = [
            'api_key' => sanitize_text_field($_POST['api_key']),
            'api_endpoint' => sanitize_text_field($_POST['api_endpoint']),
            'shop_id' => sanitize_text_field($_POST['shop_id']),
            'openai_api_key' => sanitize_text_field($_POST['openai_api_key']),
            'token_limit' => absint($_POST['token_limit']),
            'temperature' => (float) $_POST['temperature'],
            'monthly_cap' => absint($_POST['monthly_cap'])
        ];

        update_option('wpwps_settings', $settings);

        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync')
        ]);
    }
}