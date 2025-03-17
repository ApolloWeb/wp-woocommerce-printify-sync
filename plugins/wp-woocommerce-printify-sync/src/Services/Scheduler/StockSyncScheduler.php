<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Scheduler;

class StockSyncScheduler
{
    public function register(): void
    {
        // Register custom cron interval
        add_filter('cron_schedules', [$this, 'addCustomInterval']);
        
        // Register cron event
        if (!wp_next_scheduled('printify_stock_sync_event')) {
            wp_schedule_event(time(), 'every_4_hours', 'printify_stock_sync_event');
        }

        // Hook the sync function to the event
        add_action('printify_stock_sync_event', [$this, 'triggerStockSync']);
    }

    public function addCustomInterval($schedules): array
    {
        $schedules['every_4_hours'] = [
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __('Every 4 hours')
        ];
        
        return $schedules;
    }

    public function triggerStockSync(): void
    {
        do_action('printify_before_stock_sync');
        
        try {
            // Get all Printify-linked products
            $products = $this->getPrintifyProducts();
            
            foreach ($products as $product) {
                $this->updateProductStock($product);
            }

        } catch (\Exception $e) {
            error_log('Printify stock sync failed: ' . $e->getMessage());
        }

        do_action('printify_after_stock_sync');
    }

    private function getPrintifyProducts(): array
    {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT p.ID as product_id, pm.meta_value as printify_id 
            FROM {$wpdb->posts} p 
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'product' 
            AND pm.meta_key = '_printify_product_id'
        ", ARRAY_A);
    }

    private function updateProductStock(array $productData): void
    {
        $product = wc_get_product($productData['product_id']);
        if (!$product) {
            return;
        }

        // Use WooCommerce's default out of stock visibility
        $visibility = $product->get_catalog_visibility();
        $manageStock = $product->get_manage_stock();

        if ($product->is_type('variable')) {
            $this->updateVariableProductStock($product);
        } else {
            $this->updateSimpleProductStock($product);
        }

        // Save the product
        $product->save();
    }

    private function updateVariableProductStock(\WC_Product_Variable $product): void
    {
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {
            $variationProduct = wc_get_product($variation['variation_id']);
            if ($variationProduct) {
                $this->updateStockForProduct($variationProduct);
            }
        }
    }

    private function updateSimpleProductStock(\WC_Product $product): void
    {
        $this->updateStockForProduct($product);
    }

    private function updateStockForProduct(\WC_Product $product): void
    {
        // Get stock from Printify API (implement this based on your API client)
        $printifyStock = $this->getPrintifyStock($product);
        
        $product->set_stock_quantity($printifyStock['quantity']);
        $product->set_stock_status($printifyStock['quantity'] > 0 ? 'instock' : 'outofstock');
    }

    private function getPrintifyStock(\WC_Product $product): array
    {
        // Implement your Printify API call here
        // This is a placeholder return
        return [
            'quantity' => 0,
            'in_stock' => false
        ];
    }
}