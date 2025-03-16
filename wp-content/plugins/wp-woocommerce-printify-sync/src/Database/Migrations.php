<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

use Automattic\WooCommerce\Utilities\OrderUtil;

class Migrations
{
    private const SCHEMA_VERSION = '2025.03.15';
    private const OPTION_NAME = 'wpwps_schema_version';

    public function run(): void
    {
        $installedVersion = get_option(self::OPTION_NAME);
        
        if ($installedVersion !== self::SCHEMA_VERSION) {
            $this->createTables();
            $this->createIndices();
            $this->migrateData();
            
            update_option(self::OPTION_NAME, self::SCHEMA_VERSION);
        }
    }

    private function createTables(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Sync tracking table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_sync_tracking (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entity_id bigint(20) NOT NULL,
            entity_type varchar(50) NOT NULL,
            printify_id varchar(100) NOT NULL,
            sync_status varchar(20) NOT NULL DEFAULT 'pending',
            last_sync datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY entity (entity_id, entity_type),
            KEY printify_id (printify_id),
            KEY sync_status (sync_status)
        ) $charset_collate;";

        // Product metadata table (HPOS compatible)
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_product_meta (
                product_id bigint(20) NOT NULL,
                meta_key varchar(255) NOT NULL,
                meta_value longtext,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (product_id, meta_key),
                KEY meta_key (meta_key)
            ) $charset_collate;";
        }

        // Execute queries
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    private function createIndices(): void
    {
        global $wpdb;

        // Add indices for better performance
        $indices = [
            'wpwps_sync_tracking_last_sync' => "ALTER TABLE {$wpdb->prefix}wpwps_sync_tracking ADD INDEX last_sync (last_sync);",
            'wpwps_sync_tracking_created_at' => "ALTER TABLE {$wpdb->prefix}wpwps_sync_tracking ADD INDEX created_at (created_at);"
        ];

        foreach ($indices as $index_name => $sql) {
            $wpdb->query($sql);
        }
    }

    private function migrateData(): void
    {
        if (!OrderUtil::custom_orders_table_usage_is_enabled()) {
            return;
        }

        global $wpdb;

        // Migrate existing Printify metadata to new HPOS compatible table
        $wpdb->query("
            INSERT IGNORE INTO {$wpdb->prefix}wpwps_product_meta (product_id, meta_key, meta_value, created_at, updated_at)
            SELECT post_id, meta_key, meta_value, NOW(), NOW()
            FROM {$wpdb->postmeta}
            WHERE meta_key LIKE '_printify_%'
        ");
    }
}