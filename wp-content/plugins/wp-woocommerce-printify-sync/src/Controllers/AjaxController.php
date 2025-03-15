<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

class AjaxController
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:43:33';
        $this->currentUser = 'ApolloWeb';

        add_action('wp_ajax_wpwps_sync_products', [$this, 'handleSync']);
        add_action('wp_ajax_wpwps_check_api', [$this, 'handleApiCheck']);
        add_action('wp_ajax_wpwps_cleanup', [$this, 'handleCleanup']);
    }

    public function handleSync(): void
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        try {
            $syncManager = new \ApolloWeb\WPWooCommercePrintifySync\Services\SyncManager();
            $result = $syncManager->syncProducts($_POST['shop_id'] ?? '');
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleApiCheck(): void
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        try {
            $apiClient = new \ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyApiClient();
            $result = $apiClient->testConnection();
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleCleanup(): void
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        try {
            $cleanup = new \ApolloWeb\WPWooCommercePrintifySync\Services\DataCleanupService();
            $result = $cleanup->clean($_POST['items'] ?? []);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}