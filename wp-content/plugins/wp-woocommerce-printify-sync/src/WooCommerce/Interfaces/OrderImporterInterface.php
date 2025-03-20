<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces;

interface OrderImporterInterface
{
    /**
     * Import an order from Printify to WooCommerce with HPOS compatibility
     *
     * @param array $printifyOrder
     * @return int The WooCommerce order ID
     */
    public function importOrder(array $printifyOrder): int;
    
    /**
     * Get a WooCommerce order by Printify ID
     * Uses HPOS-compatible data access methods
     *
     * @param string $printifyId
     * @return int|null The WooCommerce order ID or null if not found
     */
    public function getWooOrderIdByPrintifyId(string $printifyId): ?int;

    /**
     * Delete all orders imported from Printify
     * Uses HPOS-compatible deletion methods
     *
     * @return int Number of orders deleted
     */
    public function deleteAllPrintifyOrders(): int;

    /**
     * Update the status of a WooCommerce order
     * Uses HPOS-compatible status update methods
     *
     * @param int $orderId
     * @param string $status
     * @return bool True if the status was updated successfully, false otherwise
     */
    public function updateOrderStatus(int $orderId, string $status): bool;
}
