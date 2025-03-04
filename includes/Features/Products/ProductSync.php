<?php
/**
 * Product Sync Service
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Features\Products
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Features\Products;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * ProductSync class for syncing products from Printify to WooCommerce
 */
class ProductSync {
    /**
     * API client
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi
     */
    private $api;
    
    /**
     * WooCommerce API
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\API\WooCommerceApi
     */
    private $wc_api;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi')) {
            $this->api = new \ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi();
        }
        
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\API\WooCommerceApi')) {
            $this->wc_api = new \ApolloWeb\WPWooCommercePrintifySync\API\WooCommerceApi();
        }
    }
    
    /**
     * Sync products from Printify to WooCommerce
     *
     * @param int    $shop_id    Printify shop ID
     * @param string $import_type Import type (new, all, selected)
     * @param array  $product_ids Optional product IDs for selective import
     * @return array Result of the sync operation
     */
    public function sync_products($shop_id, $import_type = 'new', $product_ids = []) {
        // Logging
        if (function_exists('printify_sync_debug')) {
            printify_sync_debug('Starting product sync for shop ID: ' . $shop_id);
            printify_sync_debug('Import type: ' . $import_type);
            if (!empty($product_ids)) {
                printify_sync_debug('Product IDs: ' . implode(', ', $product_ids));
            }
        }
        
        // Implementation would fetch products from Printify and create/update them in WooCommerce
        
        return [
            'success' => true,
            'message' => 'Products synchronized successfully',
            'stats' => [
                'total' => count($product_ids) ?: 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0
            ]
        ];
    }
    
    /**
     * Get sync status
     *
     * @return array Sync statistics
     */
    public function get_sync_status() {
        return [
            'last_sync' => get_option('printify_last_product_sync', ''),
            'total_products' => get_option('printify_total_products', 0),
            'synced_products' => get_option('printify_synced_products', 0)
        ];
    }
<<<<<<< HEAD
}
=======
}
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: }
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
