<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

use ApolloWeb\WPWooCommercePrintifySync\Models\Product;

class ProductImporter {
    const HOOK = 'wpwps_import_product';
    
    public function init() {
        add_action(self::HOOK, [$this, 'importProduct'], 10, 2);
    }
    
    public function scheduleImport($product_id, $shop_id) {
        if (!as_next_scheduled_action(self::HOOK, [$product_id, $shop_id])) {
            as_schedule_single_action(time(), self::HOOK, [$product_id, $shop_id]);
        }
    }
    
    public function importProduct($product_id, $shop_id) {
        try {
            // Get product data from API
            $api = new PrintifyAPI();
            $data = $api->getProduct($product_id, $shop_id);
            
            if (empty($data)) {
                throw new \Exception('No product data received from Printify API');
            }
            
            // Import product
            $product = new Product($data);
            $wc_product_id = $product->import();
            
            do_action('wpwps_product_imported', $wc_product_id, $data);
            
        } catch (\Exception $e) {
            error_log('Product import failed: ' . $e->getMessage());
            
            // Reschedule failed import
            $this->scheduleRetry($product_id, $shop_id);
        }
    }
    
    private function scheduleRetry($product_id, $shop_id, $attempts = 0) {
        if ($attempts < 3) {
            as_schedule_single_action(
                time() + (300 * pow(2, $attempts)), // Exponential backoff
                self::HOOK,
                [$product_id, $shop_id, $attempts + 1]
            );
        }
    }
}
