<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Handles compatibility with WooCommerce High-Performance Order Storage (HPOS)
 */
class HPOSCompat {
    /**
     * Initialize HPOS compatibility
     */
    public function init(): void {
        add_action('before_woocommerce_init', [$this, 'declareHPOSCompatibility']);
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declareHPOSCompatibility(): void {
        if (class_exists(OrderUtil::class)) {
            OrderUtil::declare_compatibility('custom_order_tables', WPPS_FILE, true);
        }
    }
    
    // ...existing code...
}
