<?php
/**
 * Admin Menu Filter
 * 
 * This class handles filtering WordPress admin menu items based on environment
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminMenuFilter
 */
class AdminMenuFilter {
    
    /**
     * Initialize the class
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'filter_admin_menu'], 999); // Run after menu is added
    }
    
    /**
     * Filter admin menu items based on environment
     */
    public static function filter_admin_menu() {
        global $submenu;
        
        // Check environment setting
        $environment = get_option('printify_sync_environment', 'production');
        $is_development = ($environment === 'development' || (defined('WP_DEBUG') && WP_DEBUG));
        
        // Only run in production environment
        if (!$is_development && isset($submenu['wp-woocommerce-printify-sync'])) {
            // Loop through submenu items and remove Postman
            foreach ($submenu['wp-woocommerce-printify-sync'] as $key => $item) {
                if (isset($item[2]) && $item[2] === 'printify-postman') {
                    unset($submenu['wp-woocommerce-printify-sync'][$key]);
                }
            }
        }
    }
}