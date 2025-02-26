<?php

namespace ApolloWeb\WooCommercePrintifySync;

class ProductImport
{
    private $option_api_key = 'printify_api_key';
    private $option_shop_id = 'printify_selected_shop';

    public function __construct()
    {
        add_action('wp_ajax_test_printify_products', [ $this, 'testPrintifyProducts' ]);
    }

    public function testPrintifyProducts()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');
        $apiKey = trim(get_option($this->option_api_key, ''));
        $shopId = trim(get_option($this->option_shop_id, ''));
        if (empty($apiKey) || empty($shopId)) {
            wp_send_json_error(['message' => __('API Key or Shop ID is missing', 'wp-woocommerce-printify-sync')]);
        }

        $products = $this->getPrintifyProducts($apiKey, $shopId);
        if (is_wp_error($products)) {
            wp_send_json_error(['message' => $products->get_error_message()]);
        }

        wp_send_json_success($products);
    }

    private function getPrintifyProducts($apiKey, $shopId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products.json?limit=5";
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'timeout' => 10,
        ];
        $response = wp_remote_get($endpoint, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('printify_api_error', __('Unexpected response from Printify API', 'wp-woocommerce-printify-sync'));
        }

        $body = wp_remote_retrieve_body($response);
        $products = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', __('Error decoding Printify API response', 'wp-woocommerce-printify-sync'));
        }

        return $products;
    }
}