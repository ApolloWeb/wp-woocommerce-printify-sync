<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AjaxHandler {
    private $nonce_action = 'wpps_ajax_nonce';

    public function init(): void {
        add_action('wp_ajax_wpps_test_api', [$this, 'testApiConnection']);
        add_action('wp_ajax_wpps_sync_product', [$this, 'syncProduct']);
    }

    private function verifyNonce(): void {
        if (!check_ajax_referer($this->nonce_action, 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
    }

    public function testApiConnection(): void {
        $this->verifyNonce();
        // API test logic will go here
        wp_send_json_success();
    }
}
