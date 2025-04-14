<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

use ApolloWeb\WPWooCommercePrintifySync\Repositories\Interfaces\PrintifyMappingInterface;

class PrintifyMappingRepository implements PrintifyMappingInterface {
    private $wpdb;

    public function __construct(\wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    public function getWooCommerceId(string $printify_id): ?int {
        $id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT post_id FROM {$this->wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        return $id ? (int)$id : null;
    }

    public function getPrintifyId(int $wc_product_id): ?string {
        return get_post_meta($wc_product_id, '_printify_product_id', true) ?: null;
    }

    public function getPrintifyVariantId(int $wc_variation_id): ?string {
        return get_post_meta($wc_variation_id, '_printify_variant_id', true) ?: null;
    }

    public function getVariationIdMap(int $wc_product_id): array {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT post_id, meta_value as printify_variant_id
            FROM {$this->wpdb->postmeta}
            WHERE post_id IN (
                SELECT ID FROM {$this->wpdb->posts}
                WHERE post_parent = %d
                AND post_type = 'product_variation'
            )
            AND meta_key = '_printify_variant_id'",
            $wc_product_id
        ));

        $map = [];
        foreach ($results as $row) {
            $map[$row->printify_variant_id] = (int)$row->post_id;
        }
        return $map;
    }

    public function savePrintifyIds(int $wc_product_id, string $printify_id, array $variant_ids): void {
        update_post_meta($wc_product_id, '_printify_product_id', $printify_id);
        update_post_meta($wc_product_id, '_printify_variant_ids', wp_json_encode($variant_ids));
    }
}
