<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

/**
 * Provides compatibility methods for working with WooCommerce orders
 * regardless of whether HPOS is enabled or not.
 */
class OrderDataStoreCompatibility
{
    /**
     * Get orders by Printify ID
     *
     * @param string $printifyId The Printify order ID
     * @param array $args Additional arguments for the query
     * @return array Array of order objects or IDs
     */
    public static function getOrdersByPrintifyId(string $printifyId, array $args = []): array
    {
        $defaults = [
            'meta_key' => '_printify_id',
            'meta_value' => $printifyId,
            'limit' => 1,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return wc_get_orders($args);
    }
    
    /**
     * Check if HPOS is active
     *
     * @return bool
     */
    public static function isHposActive(): bool
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        
        return false;
    }
    
    /**
     * Add a meta data record for an order
     *
     * @param int $orderId
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function updateOrderMeta(int $orderId, string $key, $value): bool
    {
        $order = wc_get_order($orderId);
        
        if (!$order) {
            return false;
        }
        
        $order->update_meta_data($key, $value);
        return (bool) $order->save();
    }
    
    /**
     * Get a meta data value for an order
     *
     * @param int $orderId
     * @param string $key
     * @param bool $single Whether to return a single value
     * @return mixed
     */
    public static function getOrderMeta(int $orderId, string $key, bool $single = true)
    {
        $order = wc_get_order($orderId);
        
        if (!$order) {
            return $single ? '' : [];
        }
        
        return $order->get_meta($key, $single);
    }
    
    /**
     * Delete an order meta data record
     *
     * @param int $orderId
     * @param string $key
     * @return bool
     */
    public static function deleteOrderMeta(int $orderId, string $key): bool
    {
        $order = wc_get_order($orderId);
        
        if (!$order) {
            return false;
        }
        
        $order->delete_meta_data($key);
        return (bool) $order->save();
    }
}
