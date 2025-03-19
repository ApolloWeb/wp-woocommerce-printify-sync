<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface;

class ProductImporter implements ProductImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function importProduct(array $printifyProduct): int
    {
        // For now, just store the mapping
        $productId = wp_insert_post([
            'post_title' => $printifyProduct['title'],
            'post_type' => 'product',
            'post_status' => 'draft'
        ]);

        if (is_wp_error($productId)) {
            throw new \Exception($productId->get_error_message());
        }

        update_post_meta($productId, '_printify_id', $printifyProduct['id']);
        
        return $productId;
    }

    /**
     * {@inheritdoc}
     */
    public function getWooProductIdByPrintifyId(string $printifyId): ?int
    {
        global $wpdb;
        
        $productId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_id' AND meta_value = %s",
            $printifyId
        ));
        
        return $productId ? (int) $productId : null;
    }
}
