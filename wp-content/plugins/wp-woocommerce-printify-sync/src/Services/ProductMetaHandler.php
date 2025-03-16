<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class ProductMetaHandler
{
    private const META_TABLE = 'wpwps_product_meta';
    private $context;

    public function __construct(SyncContext $context)
    {
        $this->context = $context;
    }

    public function updateMeta(int $productId, string $key, $value): bool
    {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $this->updateHPOSMeta($productId, $key, $value);
        }

        return update_post_meta($productId, $key, $value);
    }

    public function getMeta(int $productId, string $key, bool $single = true)
    {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $this->getHPOSMeta($productId, $key, $single);
        }

        return get_post_meta($productId, $key, $single);
    }

    public function deleteMeta(int $productId, string $key): bool
    {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $this->deleteHPOSMeta($productId, $key);
        }

        return delete_post_meta($productId, $key);
    }

    private function updateHPOSMeta(int $productId, string $key, $value): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . self::META_TABLE;
        $now = $this->context->getCurrentTime();

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (product_id, meta_key, meta_value, created_at, updated_at)
            VALUES (%d, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
            meta_value = VALUES(meta_value),
            updated_at = VALUES(updated_at)",
            $productId,
            $key,
            maybe_serialize($value),
            $now,
            $now
        ));

        return $result !== false;
    }

    private function getHPOSMeta(int $productId, string $key, bool $single = true)
    {
        global $wpdb;
        $table = $wpdb->prefix . self::META_TABLE;

        if ($single) {
            $value = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM $table WHERE product_id = %d AND meta_key = %s",
                $productId,
                $key
            ));
            return $value ? maybe_unserialize($value) : null;
        }

        $values = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM $table WHERE product_id = %d AND meta_key = %s",
            $productId,
            $key
        ));

        return array_map('maybe_unserialize', $values);
    }

    private function deleteHPOSMeta(int $productId, string $key): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . self::META_TABLE;

        $result = $wpdb->delete(
            $table,
            [
                'product_id' => $productId,
                'meta_key' => $key
            ],
            ['%d', '%s']
        );

        return $result !== false;
    }
}