<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;

class AjaxHandlers extends ServiceProvider
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
        $api_base_url = get_option('printify_api_base_url', 'https://api.printify.com/v1/');
        $decrypted_api_key = ApiHelper::decryptApiKey($api_key);
        $response = ApiHelper::fetchFromApi($api_base_url . 'shops.json', $decrypted_api_key);

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
        $api_base_url = get_option('printify_api_base_url', 'https://api.printify.com/v1/');
        $decrypted_api_key = ApiHelper::decryptApiKey($api_key);
        $response = ApiHelper::fetchFromApi($api_base_url . 'shops.json', $decrypted_api_key);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $products = $response['products'] ?? [];
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
        $api_base_url = get_option('printify_api_base_url', 'https://api.printify.com/v1/');
        $decrypted_api_key = ApiHelper::decryptApiKey($api_key);
        $response = ApiHelper::fetchFromApi($api_base_url . 'shops.json', $decrypted_api_key);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        wp_send_json_success($response);
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
}