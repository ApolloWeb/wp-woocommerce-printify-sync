<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Activator
{
    /**
     * Plugin activation
     *
     * @return void
     */
    public static function activate(): void
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(WPWPS_PLUGIN_BASENAME));
            wp_die(
                __('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync'),
                'Plugin Activation Error',
                ['back_link' => true]
            );
        }
        
        // Add default options
        if (!get_option('wpwps_printify_api_endpoint')) {
            update_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/');
        }
        
        // Create necessary database tables if needed
        self::createTables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables
     *
     * @return void
     */
    private static function createTables(): void
    {
        global $wpdb;
        
        $charsetCollate = $wpdb->get_charset_collate();
        
        // Example: Add a table for product sync status if needed
        $tableName = $wpdb->prefix . 'wpwps_product_sync';
        
        $sql = "CREATE TABLE IF NOT EXISTS $tableName (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            printify_id varchar(255) NOT NULL,
            wc_product_id bigint(20) NOT NULL,
            last_synced datetime NOT NULL,
            sync_status varchar(50) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY printify_id (printify_id)
        ) $charsetCollate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
