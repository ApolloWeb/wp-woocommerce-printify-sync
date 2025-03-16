<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

class SchemaManager
{
    public function createTables(): void
    {
        $this->createSyncLogTable();
        $this->createImageTrackingTable();
        $this->createVariantTrackingTable();
        $this->createQueueTrackingTable();
    }

    private function createSyncLogTable(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_sync_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sync_id varchar(40) NOT NULL,
            product_id bigint(20) NOT NULL,
            printify_id varchar(40) NOT NULL,
            shop_id varchar(40) NOT NULL,
            action varchar(20) NOT NULL,
            sync_data longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'completed',
            error_message text,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY (id),
            KEY sync_id (sync_id),
            KEY product_id (product_id),
            KEY printify_id (printify_id),
            KEY status (status)
        ) $charsetCollate;";

        $this->executeSql($sql);
    }

    private function createImageTrackingTable(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_image_tracking (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            printify_image_id varchar(40) NOT NULL,
            image_url varchar(2083) NOT NULL,
            attachment_id bigint(20),
            storage_path varchar(2083),
            storage_provider varchar(50),
            checksum varchar(32) NOT NULL,
            webp_path varchar(2083),
            sync_status varchar(20) NOT NULL DEFAULT 'pending',
            sync_id varchar(40) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            updated_by varchar(60) NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY printify_image_id (printify_image_id),
            KEY sync_id (sync_id)
        ) $charsetCollate;";

        $this->executeSql($sql);
    }

    private function createVariantTrackingTable(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_variant_tracking (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            variation_id bigint(20) NOT NULL,
            printify_variant_id varchar(40) NOT NULL,
            printify_sku varchar(100) NOT NULL,
            cost_price decimal(10,2) NOT NULL,
            retail_price decimal(10,2) NOT NULL,
            attributes longtext NOT NULL,
            print_provider_data longtext,
            shipping_data longtext,
            sync_id varchar(40) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            updated_by varchar(60) NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY variation_id (variation_id),
            KEY printify_variant_id (printify_variant_id)
        ) $charsetCollate;";

        $this->executeSql($sql);
    }

    private function createQueueTrackingTable(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_queue_tracking (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sync_id varchar(40) NOT NULL,
            batch_id varchar(40) NOT NULL,
            shop_id varchar(40) NOT NULL,
            total_items int NOT NULL,
            processed_items int NOT NULL DEFAULT 0,
            failed_items int NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            started_at datetime,
            completed_at datetime,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            updated_by varchar(60) NOT NULL,
            PRIMARY KEY (id),
            KEY sync_id (sync_id),
            KEY batch_id (batch_id),
            KEY status (status)
        ) $charsetCollate;";

        $this->executeSql($sql);
    }

    private function executeSql(string $sql): void
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}