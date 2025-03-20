<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces;

interface OrderImporterInterface
{
    /**
     * Import an order from Printify to WooCommerce
     *
     * @param array $printifyOrder
     * @return int The WooCommerce order ID
     */
    public function importOrder(array $printifyOrder): int;
    
    /**
     * Get a WooCommerce order by Printify ID
     *
     * @param string $printifyId
     * @return int|null The WooCommerce order ID or null if not found
     */
    public function getWooOrderIdByPrintifyId(string $printifyId): ?int;
}
