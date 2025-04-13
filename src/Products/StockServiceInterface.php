<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products;

/**
 * Interface for stock service
 */
interface StockServiceInterface {
    /**
     * Synchronize stock levels for all products and variants
     * 
     * @return array Statistics about the sync process
     */
    public function synchronize_stock_levels();
    
    /**
     * Update stock level for a specific product
     * 
     * @param string $printify_product_id Printify product ID
     * @return bool Whether stock was updated
     */
    public function update_product_stock($printify_product_id);
}
