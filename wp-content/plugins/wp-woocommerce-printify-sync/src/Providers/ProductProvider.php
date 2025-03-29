<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;

class ProductProvider implements ServiceProvider
{
    private ProductSync $productSync;

    public function register(): void
    {
        $this->productSync = new ProductSync();
        
        add_action('wp_ajax_wpwps_sync_product', [$this, 'handleManualSync']);
        add_action('wpwps_product_webhook', [$this, 'handleWebhook']);
        add_action('wpwps_hourly_sync', [$this, 'syncAllProducts']);
        
        if (!wp_next_scheduled('wpwps_hourly_sync')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_hourly_sync');
        }
    }

    public function handleManualSync(): void
    {
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $printifyId = sanitize_text_field($_POST['printify_id'] ?? '');
        if (empty($printifyId)) {
            wp_send_json_error('Invalid product ID');
        }

        $result = $this->productSync->syncProduct($printifyId);
        if ($result) {
            wp_send_json_success(['product_id' => $result]);
        }

        wp_send_json_error('Sync failed');
    }

    public function handleWebhook(array $data): void
    {
        $this->productSync->handleWebhook($data);
    }

    public function syncAllProducts(): void
    {
        // Implementation for bulk sync will go here
        // This will be called hourly by WordPress cron
    }
}