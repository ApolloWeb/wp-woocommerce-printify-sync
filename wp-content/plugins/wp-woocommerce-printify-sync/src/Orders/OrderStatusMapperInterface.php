<?php
/**
 * Order Status Mapper Interface.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

/**
 * Interface for mapping external status to WooCommerce status.
 */
interface OrderStatusMapperInterface {
    /**
     * Map external status to WooCommerce status.
     *
     * @param string $external_status External status code.
     * @return string WooCommerce status code.
     */
    public function mapToWooCommerce($external_status);
}
