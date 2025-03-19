<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces;

interface ProductImporterInterface
{
    /**
     * Import a product from Printify to WooCommerce
     *
     * @param array $printifyProduct
     * @return int The WooCommerce product ID
     */
    public function importProduct(array $printifyProduct): int;
    
    /**
     * Get a WooCommerce product by Printify ID
     *
     * @param string $printifyId
     * @return int|null The WooCommerce product ID or null if not found
     */
    public function getWooProductIdByPrintifyId(string $printifyId): ?int;
}
