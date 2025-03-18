<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 08:07:12

class AjaxHandlers implements ServiceProvider
{
    public function boot()
    {
        add_action('wp_ajax_test_printify_api', [$this, 'testPrintifyApi']);
        add_action('wp_ajax_retrieve_printify_products', [$this, 'retrievePrintifyProducts']);
        add_action('wp_ajax_import_printify_products', [$this, 'importPrintifyProducts']);
        add_action('wp_ajax_fetch_printify_shops', [$this, 'fetchPrintifyShops']);
        add_action('wp_ajax_check_api_key_status', [$this, 'checkApiKeyStatus']);
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

    public function retrievePrintifyProducts()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $api_key = get_option('printify_api_key');
        $printify_api = new PrintifyAPI($this->decryptApiKey($api_key));
        $products = $printify_api->fetchProducts();

        if (is_wp_error($products)) {
            wp_send_json_error($products->get_error_message());
        }

        set_transient('printify_products', $products, 3600);

        wp_send_json_success($products);
    }

    public function importPrintifyProducts()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        as_schedule_single_action(time(), 'printify_import_products');

        wp_send_json_success();
    }

    public function fetchPrintifyShops()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $api_key = get_option('printify_api_key');
        $printify_api = new PrintifyAPI($this->decryptApiKey($api_key));
        $shops = $printify_api->fetchShops();

        if (is_wp_error($shops)) {
            wp_send_json_error($shops->get_error_message());
        }

        wp_send_json_success($shops);
    }

    public function checkApiKeyStatus()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $api_key_present = !empty(get_option('printify_api_key'));

        wp_send_json_success(['api_key_present' => $api_key_present]);
    }

    private function decryptApiKey($encrypted_api_key)
    {
        $salt = wp_salt();
        return openssl_decrypt(base64_decode($encrypted_api_key), 'aes-256-cbc', $salt, 0, substr($salt, 0, 16));
    }
}