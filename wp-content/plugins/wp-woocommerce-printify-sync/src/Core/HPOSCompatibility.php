<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Handle compatibility between traditional post meta and HPOS for WooCommerce orders.
 */
class HPOSCompatibility
{
    /**
     * Check if HPOS is enabled and available
     * 
     * @return bool
     */
    public static function isHPOSEnabled(): bool
    {
        if (!class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return false;
        }
        
        return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
    
    /**
     * Get order meta data, compatible with both traditional and HPOS.
     * 
     * @param \WC_Order $order
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public static function getOrderMeta(\WC_Order $order, string $key, bool $single = false)
    {
        if (self::isHPOSEnabled()) {
            return $order->get_meta($key, $single);
        } else {
            return get_post_meta($order->get_id(), $key, $single);
        }
    }
    
    /**
     * Update order meta data, compatible with both traditional and HPOS.
     * 
     * @param \WC_Order $order
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function updateOrderMeta(\WC_Order $order, string $key, $value)
    {
        if (self::isHPOSEnabled()) {
            $order->update_meta_data($key, $value);
            $order->save();
            return true;
        } else {
            return update_post_meta($order->get_id(), $key, $value);
        }
    }
    
    /**
     * Delete order meta data, compatible with both traditional and HPOS.
     * 
     * @param \WC_Order $order
     * @param string $key
     * @return bool
     */
    public static function deleteOrderMeta(\WC_Order $order, string $key): bool
    {
        if (self::isHPOSEnabled()) {
            $order->delete_meta_data($key);
            $order->save();
            return true;
        } else {
            return delete_post_meta($order->get_id(), $key);
        }
    }
    
    /**
     * Get order by ID, compatible with both traditional and HPOS.
     * 
     * @param int $order_id
     * @return \WC_Order|false
     */
    public static function getOrder(int $order_id)
    {
        return wc_get_order($order_id);
    }
    
    /**
     * Get order by custom field value, compatible with both traditional and HPOS.
     * 
     * @param string $meta_key
     * @param string $meta_value
     * @return int[] Array of order IDs
     */
    public static function getOrdersByMeta(string $meta_key, string $meta_value): array
    {
        if (self::isHPOSEnabled()) {
            // Use HPOS method to query orders
            $query = new \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery([
                'meta_query' => [
                    [
                        'key' => $meta_key,
                        'value' => $meta_value,
                        'compare' => '=',
                    ]
                ]
            ]);
            
            $results = $query->get_orders();
            return array_map(function($order) {
                return $order->get_id();
            }, $results);
        } else {
            // Use traditional WP_Query
            $query = new \WP_Query([
                'post_type' => 'shop_order',
                'meta_key' => $meta_key,
                'meta_value' => $meta_value,
                'post_status' => 'any',
                'fields' => 'ids',
                'posts_per_page' => -1,
            ]);
            
            return $query->posts;
        }
    }
}