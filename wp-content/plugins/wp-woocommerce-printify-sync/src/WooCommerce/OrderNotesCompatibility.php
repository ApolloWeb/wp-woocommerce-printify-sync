<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

/**
 * Provides HPOS compatibility for working with order notes
 */
class OrderNotesCompatibility
{
    /**
     * Add a note to an order with HPOS compatibility
     *
     * @param int $orderId
     * @param string $note
     * @param bool $isCustomerNote
     * @param bool $addedByUser
     * @return int|false Note ID or false on failure
     */
    public static function addOrderNote(int $orderId, string $note, bool $isCustomerNote = false, bool $addedByUser = false)
    {
        $order = wc_get_order($orderId);
        
        if (!$order) {
            return false;
        }
        
        return $order->add_order_note($note, $isCustomerNote ? 1 : 0, $addedByUser);
    }
    
    /**
     * Get order notes with HPOS compatibility
     *
     * @param int $orderId
     * @param array $args
     * @return array
     */
    public static function getOrderNotes(int $orderId, array $args = []): array
    {
        $order = wc_get_order($orderId);
        
        if (!$order) {
            return [];
        }
        
        return wc_get_order_notes([
            'order_id' => $orderId,
            'type' => $args['type'] ?? 'internal',
        ]);
    }
}
