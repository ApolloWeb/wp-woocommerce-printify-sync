<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface;

class OrderImporter implements OrderImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function importOrder(array $printifyOrder): int
    {
        // For now, just store the mapping
        $orderId = wp_insert_post([
            'post_title' => $printifyOrder['id'],
            'post_type' => 'shop_order',
            'post_status' => 'wc-pending'
        ]);

        if (is_wp_error($orderId)) {
            throw new \Exception($orderId->get_error_message());
        }

        update_post_meta($orderId, '_printify_id', $printifyOrder['id']);
        
        return $orderId;
    }

    /**
     * {@inheritdoc}
     */
    public function getWooOrderIdByPrintifyId(string $printifyId): ?int
    {
        global $wpdb;
        
        $orderId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_id' AND meta_value = %s",
            $printifyId
        ));
        
        return $orderId ? (int) $orderId : null;
    }
}
