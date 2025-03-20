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

    /**
     * Delete all products imported from Printify
     *
     * @return int Number of products deleted
     */
    public function deleteAllPrintifyProducts(): int;

    /**
     * Update a WooCommerce product with data from Printify
     *
     * @param int $wooProductId
     * @param array $printifyProduct
     * @return bool True if the update was successful, false otherwise
     */
    public function updateProduct(int $wooProductId, array $printifyProduct): bool;
}
