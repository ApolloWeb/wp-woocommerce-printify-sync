<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateCustomTables
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:25:31';
        $this->currentUser = 'ApolloWeb';
    }

    public function up(): void
    {
        global $wpdb;

        // Only create custom tables if HPOS is enabled
        if (\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            $this->createHPOSTables($wpdb);
        }
    }

    private function createHPOSTables($wpdb): void
    {
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_product_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            meta_key varchar(255) DEFAULT NULL,
            meta_value longtext,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY meta_key (meta_key(191))
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}