<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\API\WebhookEndpoints;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyService;

class Activator {
    public static function activate() {
        // Generate webhook secret if not exists
        if (!get_option(WebhookEndpoints::WEBHOOK_SECRET)) {
            update_option(WebhookEndpoints::WEBHOOK_SECRET, wp_generate_password(32, true, true));
        }

        // Register webhooks with Printify
        self::register_printify_webhooks();

        // Create required database tables
        self::create_tables();
    }

    private static function register_printify_webhooks() {
        $api = PrintifyService::fromWordPressOptions();
        $site_url = get_site_url();
        $secret = get_option(WebhookEndpoints::WEBHOOK_SECRET);

        $webhooks = [
            [
                'url' => "{$site_url}/wp-json/wpwps/v1/webhook/products",
                'events' => ['product.update', 'product.delete'],
            ],
            [
                'url' => "{$site_url}/wp-json/wpwps/v1/webhook/orders",
                'events' => ['order.created', 'order.status_update'],
            ],
        ];

        foreach ($webhooks as $webhook) {
            $api->request('webhooks.json', 'POST', [
                'url' => $webhook['url'],
                'events' => $webhook['events'],
                'secret' => $secret,
            ]);
        }
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            "CREATE TABLE {$wpdb->prefix}printify_products (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                printify_id VARCHAR(255) NOT NULL,
                wc_product_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY printify_id (printify_id),
                UNIQUE KEY wc_product_id (wc_product_id)
            ) $charset_collate;",
            "CREATE TABLE {$wpdb->prefix}printify_orders (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                printify_id VARCHAR(255) NOT NULL,
                wc_order_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY printify_id (printify_id),
                UNIQUE KEY wc_order_id (wc_order_id)
            ) $charset_collate;",
        ];

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($tables as $table) {
            dbDelta($table);
        }
    }
}
