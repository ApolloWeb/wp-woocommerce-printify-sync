<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Models;

class ImageTracker
{
    private const TABLE_NAME = 'wpwps_image_tracking';

    public static function createTable(): void
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            printify_image_id varchar(255) NOT NULL,
            printify_image_url varchar(2083) NOT NULL,
            attachment_id bigint(20) DEFAULT NULL,
            storage_path varchar(2083) DEFAULT NULL,
            storage_provider varchar(50) DEFAULT NULL,
            checksum varchar(32) NOT NULL,
            webp_path varchar(2083) DEFAULT NULL,
            sync_status varchar(20) DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            updated_by varchar(60) NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY printify_image_id (printify_image_id),
            KEY sync_status (sync_status)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function hasImageChanged(string $printifyImageId, string $imageUrl): bool
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        
        $currentChecksum = md5($imageUrl);
        $storedChecksum = $wpdb->get_var($wpdb->prepare(
            "SELECT checksum FROM {$tableName} WHERE printify_image_id = %s ORDER BY id DESC LIMIT 1",
            $printifyImageId
        ));

        return $currentChecksum !== $storedChecksum;
    }

    public static function trackImage(array $data): void
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $wpdb->insert($tableName, [
            'product_id' => $data['product_id'],
            'printify_image_id' => $data['printify_image_id'],
            'printify_image_url' => $data['printify_image_url'],
            'attachment_id' => $data['attachment_id'] ?? null,
            'storage_path' => $data['storage_path'] ?? null,
            'storage_provider' => $data['storage_provider'] ?? null,
            'checksum' => md5($data['printify_image_url']),
            'webp_path' => $data['webp_path'] ?? null,
            'sync_status' => $data['sync_status'] ?? 'pending',
            'created_at' => $data['timestamp'],
            'updated_at' => $data['timestamp'],
            'created_by' => $data['user'],
            'updated_by' => $data['user']
        ]);
    }
}