<?php
/**
 * Activation routines.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\API\APIClient;

class Activator {

    /**
     * Activate the plugin.
     */
    public static function activate() {
        self::create_tables();
        self::schedule_events();

        $api_key = get_option('wpwps_api_key', '');
        if (!empty($api_key)) {
            self::register_webhooks($api_key);
        }

        add_option('wpwps_version', WPWPS_VERSION);
        add_option('wpwps_last_sync', '');
    }

    /**
     * Create required database tables.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'wpwps_webhook_logs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            event varchar(100) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $table_name = $wpdb->prefix . 'wpwps_sync_logs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Register webhooks with Printify.
     */
    private static function register_webhooks($api_key) {
        $api_client = new APIClient($api_key);
        $webhook_url = site_url('/wp-json/wpwps/v1/webhook');
        $events = [
            'product.created',
            'product.updated',
            'order.created',
            'order.updated'
        ];

        foreach ($events as $event) {
            $api_client->register_webhook($webhook_url, $event);
        }
    }

    /**
     * Schedule cron events.
     */
    private static function schedule_events() {
        if (!wp_next_scheduled('wpwps_scheduled_product_sync')) {
            wp_schedule_event(time(), 'twicedaily', 'wpwps_scheduled_product_sync');
        }
        if (!wp_next_scheduled('wpwps_scheduled_order_sync')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_scheduled_order_sync');
        }
    }
}