<?php
/**
 * Deactivation routines.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\API\APIClient;

class Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('wpwps_scheduled_product_sync');
        wp_clear_scheduled_hook('wpwps_scheduled_order_sync');

        $api_key = get_option('wpwps_api_key', '');
        if (!empty($api_key)) {
            self::deregister_webhooks($api_key);
        }
    }

    /**
     * Deregister webhooks from Printify.
     */
    private static function deregister_webhooks($api_key) {
        $api_client = new APIClient($api_key);
        $webhooks = $api_client->get_webhooks();
        $webhook_url = site_url('/wp-json/wpwps/v1/webhook');
        
        foreach ($webhooks as $webhook) {
            if ($webhook['url'] === $webhook_url) {
                $api_client->delete_webhook($webhook['id']);
            }
        }
    }
}