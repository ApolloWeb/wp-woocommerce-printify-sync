<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\ProductRepository;

/**
 * Handles stock synchronization between Printify and WooCommerce
 */
class StockSyncService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * @var ProductRepository
     */
    private $product_repository;
    
    /**
     * Constructor
     */
    public function __construct(
        PrintifyApiClient $api,
        Logger $logger,
        Settings $settings,
        ProductRepository $product_repository
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->product_repository = $product_repository;
    }
    
    /**
     * Initialize the service
     */
    public function init(): void {
        // Register cron schedule for stock syncing
        add_filter('cron_schedules', [$this, 'addCronSchedules']);
        
        // Schedule stock sync if not already scheduled
        if (!wp_next_scheduled('wpwps_sync_stock')) {
            $this->scheduleStockSync();
        }
        
        // Add hook for the scheduled event
        add_action('wpwps_sync_stock', [$this, 'syncAllProductsStock']);
        
        // Add hook for single product stock update
        add_action('wpwps_sync_product_stock', [$this, 'syncProductStock'], 10, 1);
        
        // Add admin AJAX handler for manual stock sync
        add_action('wp_ajax_wpwps_sync_stock_manually', [$this, 'syncStockManuallyAjax']);
    }
    
    /**
     * Add custom cron schedules
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function addCronSchedules(array $schedules): array {
        // Add a schedule for every 6 hours
        $schedules['wpwps_six_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 Hours', 'wp-woocommerce-printify-sync')
        ];
        
        // Add a schedule for every 12 hours
        $schedules['wpwps_twelve_hours'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Every 12 Hours', 'wp-woocommerce-printify-sync')
        ];
        
        return $schedules;
    }
    
    /**
     * Schedule stock sync based on settings
     */
    public function scheduleStockSync(): void {
        // Clear any existing scheduled events
        wp_clear_scheduled_hook('wpwps_sync_stock');
        
        // Get frequency from settings
        $frequency = $this->settings->get('stock_sync_frequency', 'wpwps_six_hours');
        
        // Schedule the event
        wp_schedule_event(time(), $frequency, 'wpwps_sync_stock');
        
        $this->logger->log("Scheduled stock sync with frequency: {$frequency}", 'info');
    }
    
    /**
     * Sync stock for all Printify products
     */
    public function syncAllProductsStock(): void {
        $this->logger->log('Starting stock sync for all Printify products', 'info');
        
        // Get all products linked to Printify
        $printify_products = $this->product_repository->getAllPrintifyProducts();
        
        if (empty($printify_products)) {
            $this->logger->log('No Printify products found to sync stock', 'info');
            $this->updateSyncStats(0, 0, 0);
            return;
        }
        
        $this->logger->log(sprintf('Found %d Printify products to sync stock', count($printify_products)), 'info');
        
        $total = count($printify_products);
        $success = 0;
        $failed = 0;
        
        // Batch process to avoid timeouts
        $batch_size = 10;
        $batches = array_chunk($printify_products, $batch_size);
        
        foreach ($batches as $batch) {
            foreach ($batch as $product) {
                $result = $this->syncProductStock($product['product_id']);
                
                if ($result) {
                    $success++;
                } else {
                    $failed++;
                }
                
                // Small delay to avoid API rate limits
                usleep(500000); // 0.5 seconds
            }
        }
        
        // Update sync statistics
        $this->updateSyncStats($total, $success, $failed);
        
        $this->logger->log(sprintf(
            'Stock sync completed: %d total, %d successful, %d failed',
            $total,
            $success,
            $failed
        ), 'info');
    }
    
    /**
     * Sync stock for a single product
     *
     * @param int $product_id WooCommerce product ID
     * @return bool Success
     */
    public function syncProductStock(int $product_id): bool {
        $this->logger->log("Syncing stock for product #{$product_id}", 'debug');
        
        try {
            // Get Printify product ID
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (empty($printify_product_id)) {
                $this->logger->log("Product #{$product_id} is not linked to Printify", 'warning');
                return false;
            }
            
            // Get product from Printify
            $printify_product = $this->api->getProduct($printify_product_id);
            
            if (empty($printify_product)) {
                $this->logger->log("Failed to fetch Printify product data for #{$printify_product_id}", 'error');
                return false;
            }
            
            // Get WooCommerce product
            $product = wc_get_product($product_id);
            
            if (!$product) {
                $this->logger->log("WooCommerce product #{$product_id} not found", 'error');
                return false;
            }
            
            // Check if it's a variable product
            if ($product->is_type('variable')) {
                return $this->syncVariableProductStock($product, $printify_product);
            } else {
                return $this->syncSimpleProductStock($product, $printify_product);
            }
        } catch (\Exception $e) {
            $this->logger->log("Error syncing stock for product #{$product_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Sync stock for a simple product
     *
     * @param \WC_Product $product WooCommerce product
     * @param array $printify_product Printify product data
     * @return bool Success
     */
    private function syncSimpleProductStock(\WC_Product $product, array $printify_product): bool {
        // Get stock status from Printify
        $is_in_stock = $this->getProductStockStatus($printify_product);
        
        // Check if stock has changed
        $current_stock_status = $product->get_stock_status();
        $new_stock_status = $is_in_stock ? 'instock' : 'outofstock';
        
        if ($current_stock_status === $new_stock_status) {
            $this->logger->log("Stock status unchanged for product #{$product->get_id()}", 'debug');
            return true;
        }
        
        // Update stock status
        $product->set_stock_status($new_stock_status);
        $product->save();
        
        $this->logger->log(sprintf(
            "Updated stock status for product #%d from '%s' to '%s'",
            $product->get_id(),
            $current_stock_status,
            $new_stock_status
        ), 'info');
        
        return true;
    }
    
    /**
     * Sync stock for a variable product
     *
     * @param \WC_Product_Variable $product WooCommerce variable product
     * @param array $printify_product Printify product data
     * @return bool Success
     */
    private function syncVariableProductStock(\WC_Product_Variable $product, array $printify_product): bool {
        $success = true;
        $variations = $product->get_available_variations();
        
        if (empty($variations)) {
            $this->logger->log("No variations found for product #{$product->get_id()}", 'warning');
            return false;
        }
        
        // Create a map of variant IDs to stock status
        $variant_stock_map = [];
        
        foreach ($printify_product['variants'] as $variant) {
            $variant_stock_map[$variant['id']] = !empty($variant['is_enabled']);
        }
        
        // Update each variation
        foreach ($variations as $variation_data) {
            $variation_id = $variation_data['variation_id'];
            $variation = wc_get_product($variation_id);
            
            if (!$variation) {
                continue;
            }
            
            // Get Printify variant ID
            $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
            
            if (empty($printify_variant_id) || !isset($variant_stock_map[$printify_variant_id])) {
                continue;
            }
            
            // Get stock status from map
            $is_in_stock = $variant_stock_map[$printify_variant_id];
            
            // Check if stock has changed
            $current_stock_status = $variation->get_stock_status();
            $new_stock_status = $is_in_stock ? 'instock' : 'outofstock';
            
            if ($current_stock_status !== $new_stock_status) {
                $variation->set_stock_status($new_stock_status);
                $variation->save();
                
                $this->logger->log(sprintf(
                    "Updated stock status for variation #%d from '%s' to '%s'",
                    $variation_id,
                    $current_stock_status,
                    $new_stock_status
                ), 'info');
            }
        }
        
        // Update parent product stock status based on variations
        $this->updateParentStockStatus($product);
        
        return $success;
    }
    
    /**
     * Update parent product stock status based on variations
     *
     * @param \WC_Product_Variable $product WooCommerce variable product
     */
    private function updateParentStockStatus(\WC_Product_Variable $product): void {
        $variations = $product->get_available_variations();
        $has_in_stock = false;
        
        foreach ($variations as $variation_data) {
            $variation_id = $variation_data['variation_id'];
            $variation = wc_get_product($variation_id);
            
            if ($variation && $variation->is_in_stock()) {
                $has_in_stock = true;
                break;
            }
        }
        
        $current_stock_status = $product->get_stock_status();
        $new_stock_status = $has_in_stock ? 'instock' : 'outofstock';
        
        if ($current_stock_status !== $new_stock_status) {
            $product->set_stock_status($new_stock_status);
            $product->save();
            
            $this->logger->log(sprintf(
                "Updated parent product #%d stock status from '%s' to '%s' based on variations",
                $product->get_id(),
                $current_stock_status,
                $new_stock_status
            ), 'info');
        }
    }
    
    /**
     * Get product stock status from Printify data
     *
     * @param array $printify_product Printify product data
     * @return bool Whether product is in stock
     */
    private function getProductStockStatus(array $printify_product): bool {
        // For simple products with one variant
        if (isset($printify_product['variants']) && count($printify_product['variants']) === 1) {
            return !empty($printify_product['variants'][0]['is_enabled']);
        }
        
        // Check if any variant is enabled
        if (isset($printify_product['variants']) && !empty($printify_product['variants'])) {
            foreach ($printify_product['variants'] as $variant) {
                if (!empty($variant['is_enabled'])) {
                    return true;
                }
            }
        }
        
        // Check for any publish_details with enabled status
        if (isset($printify_product['publish_details']) && isset($printify_product['publish_details']['status'])) {
            return $printify_product['publish_details']['status'] === 'published';
        }
        
        // Default to true if we don't have specific status info
        return true;
    }
    
    /**
     * Update sync statistics
     *
     * @param int $total Total products processed
     * @param int $success Successfully synced products
     * @param int $failed Failed sync attempts
     */
    private function updateSyncStats(int $total, int $success, int $failed): void {
        // Update running totals
        $current_total = (int) get_option('wpwps_stock_sync_total', 0);
        $current_success = (int) get_option('wpwps_stock_sync_success', 0);
        $current_failed = (int) get_option('wpwps_stock_sync_failed', 0);
        
        update_option('wpwps_stock_sync_total', $current_total + $total);
        update_option('wpwps_stock_sync_success', $current_success + $success);
        update_option('wpwps_stock_sync_failed', $current_failed + $failed);
        update_option('wpwps_stock_sync_last', current_time('mysql'));
    }
    
    /**
     * AJAX handler for manual stock sync
     */
    public function syncStockManuallyAjax(): void {
        check_ajax_referer('wpwps_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Schedule immediate sync
        wp_schedule_single_event(time(), 'wpwps_sync_stock');
        
        wp_send_json_success([
            'message' => __('Stock sync has been scheduled and will begin shortly.', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Get last sync information
     *
     * @return array Sync information
     */
    public function getLastSyncInfo(): array {
        return [
            'total' => get_option('wpwps_stock_sync_total', 0),
            'success' => get_option('wpwps_stock_sync_success', 0),
            'failed' => get_option('wpwps_stock_sync_failed', 0),
            'last_sync' => get_option('wpwps_stock_sync_last', ''),
            'next_sync' => wp_next_scheduled('wpwps_sync_stock')
        ];
    }
}
