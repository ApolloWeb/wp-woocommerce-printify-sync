<?php
/**
 * Admin Links Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class AdminLinksHelper {
    /**
     * Add action links to plugins page
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public static function addActionLinks($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=wpwprintifysync-settings') . '">' . __('Settings', 'wp-woocommerce-printify-sync') . '</a>',
            '<a href="' . admin_url('admin.php?page=wpwprintifysync') . '">' . __('Dashboard', 'wp-woocommerce-printify-sync') . '</a>',
        ];
        
        return array_merge($plugin_links, $links);
    }
}