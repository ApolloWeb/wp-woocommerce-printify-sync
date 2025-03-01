<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Helper Class
 *
 * @package WP WooCommerce Printify Sync
 * @version 1.0.0
 * @date 2025-03-01 09:21:09
 * @user ApolloWeb
 */

/**
 * Class SyncHelper
 * 
 * Contains helper functions for the plugin
 */
class SyncHelper {
    /**
     * Check if current page is a plugin page
     *
     * @param string $hook Current admin page hook
     * @return bool
     */
    public static function is_plugin_page($hook) {
        if ($hook === 'toplevel_page_wp-woocommerce-printify-sync') {
            return true;
        }
        
        $plugin_pages = [
            'wp-woocommerce-printify-sync_page_wp-woocommerce-printify-sync-shops',
            'wp-woocommerce-printify-sync_page_wp-woocommerce-printify-sync-products'
        ];
        
        return in_array($hook, $plugin_pages);
    }
}