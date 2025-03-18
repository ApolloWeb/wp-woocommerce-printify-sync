<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

class AjaxHandlers implements ServiceProvider
{
    public function boot()
    {
        add_action('wp_ajax_test_printify_api', [$this, 'testPrintifyApi']);
    }

    public function testPrintifyApi()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $api_key = get_option('printify_api_key');
        $printify_api = new PrintifyAPI($this->decryptApiKey($api_key));
        $response = $printify_api->fetchShops();

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        wp_send_json_success('API connection successful.');
    }

    private function decryptApiKey($encrypted_api_key)
    {
        $salt = wp_salt();
        return openssl_decrypt(base64_decode($encrypted_api_key), 'aes-256-cbc', $salt, 0, substr($salt, 0, 16));
    }
}