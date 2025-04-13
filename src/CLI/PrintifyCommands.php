<?php
namespace ApolloWeb\WPWooCommercePrintifySync\CLI;

use WP_CLI;

/**
 * Manage Printify Sync operations via WP-CLI
 */
class PrintifyCommands {
    /**
     * Start a full product sync from Printify to WooCommerce
     *
     * ## OPTIONS
     *
     * [--force]
     * : Force sync regardless of existing products
     *
     * ## EXAMPLES
     *
     *     wp printify sync
     *     wp printify sync --force
     *
     * @param array $args       Command arguments
     * @param array $assoc_args Command associative arguments
     */
    public function sync($args, $assoc_args) {
        $force = isset($assoc_args['force']);
        
        WP_CLI::line('Starting Printify sync...');
        
        // Get product sync service
        $product_sync = new \ApolloWeb\WPWooCommercePrintifySync\Products\ProductSyncService();
        
        // Start sync
        try {
            $product_sync->start_full_sync($force);
            WP_CLI::success('Sync process has been initiated. Products will be imported in the background.');
        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }
    
    /**
     * Import a single product from Printify
     *
     * ## OPTIONS
     *
     * <product_id>
     * : Printify product ID to import
     *
     * ## EXAMPLES
     *
     *     wp printify import 12345
     *
     * @param array $args Command arguments
     */
    public function import($args) {
        if (empty($args[0])) {
            WP_CLI::error('Product ID is required.');
            return;
        }
        
        $product_id = $args[0];
        
        WP_CLI::line(sprintf('Importing product ID: %s', $product_id));
        
        // Get product sync service
        $product_sync = new \ApolloWeb\WPWooCommercePrintifySync\Products\ProductSyncService();
        
        // Import product
        try {
            $result = $product_sync->import_product($product_id);
            
            if (is_wp_error($result)) {
                WP_CLI::error($result->get_error_message());
            } else {
                WP_CLI::success(sprintf('Product imported successfully. WooCommerce product ID: %d', $result));
            }
        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }
    
    /**
     * Check sync status
     *
     * ## EXAMPLES
     *
     *     wp printify status
     */
    public function status() {
        $is_syncing = get_option('wpwps_sync_in_progress', false);
        
        if ($is_syncing) {
            WP_CLI::line('Sync status: In progress');
            
            // Get pending jobs count
            $pending_count = as_get_scheduled_actions([
                'group' => 'wpwps',
                'status' => \ActionScheduler_Store::STATUS_PENDING,
            ], 'count');
            
            // Get completed jobs count
            $completed_count = as_get_scheduled_actions([
                'group' => 'wpwps',
                'status' => \ActionScheduler_Store::STATUS_COMPLETE,
            ], 'count');
            
            WP_CLI::line(sprintf('Pending jobs: %d', $pending_count));
            WP_CLI::line(sprintf('Completed jobs: %d', $completed_count));
        } else {
            WP_CLI::line('Sync status: Idle');
        }
        
        // Get total products count
        global $wpdb;
        $synced_products = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM $wpdb->postmeta WHERE meta_key = '_printify_is_synced' AND meta_value = '1'"
        );
        
        WP_CLI::line(sprintf('Total synced products: %d', $synced_products ?: 0));
    }
    
    /**
     * Reset sync status
     *
     * ## EXAMPLES
     *
     *     wp printify reset
     */
    public function reset() {
        update_option('wpwps_sync_in_progress', false);
        WP_CLI::success('Sync status has been reset.');
    }
}
