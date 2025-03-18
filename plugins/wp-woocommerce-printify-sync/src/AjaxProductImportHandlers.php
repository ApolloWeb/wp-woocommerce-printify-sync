<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class AjaxProductImportHandlers implements ServiceProvider
{
    public function boot()
    {
        add_action('wp_ajax_retrieve_printify_products', [$this, 'retrievePrintifyProducts']);
        add_action('wp_ajax_import_printify_products', [$this, 'importPrintifyProducts']);
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

    private function decryptApiKey($encrypted_api_key)
    {
        $salt = wp_salt();
        return openssl_decrypt(base64_decode($encrypted_api_key), 'aes-256-cbc', $salt, 0, substr($salt, 0, 16));
    }
}