<?php
/**
 * Admin Menu Helper
 * 
 * Provides utility functions for the admin menu system
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MenuHelper
 * 
 * Helper functions for admin menu operations
 */
class MenuHelper {
    /**
     * Check if the current page is a plugin admin page
     *
     * @return boolean
     */
    public static function is_plugin_page() {
        global $pagenow;
        
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $plugin_pages = [
            'wp-woocommerce-printify-sync',
            'printify-products',
            'printify-orders',
            'printify-shops',
            'printify-exchange-rates',
            'printify-postman',
            'printify-logs',
            'printify-settings'
        ];
        
        return $pagenow === 'admin.php' && in_array($page, $plugin_pages);
    }
    
    /**
     * Get the current active menu slug
     *
     * @return string|null
     */
    public static function get_active_menu() {
        if (!self::is_plugin_page()) {
            return null;
        }
        
        return isset($_GET['page']) ? sanitize_text_field($_GET['page']) : null;
    }
    
    /**
     * Check if a specific menu is active
     *
     * @param string $menu_slug Menu slug to check
     * @return boolean
     */
    public static function is_menu_active($menu_slug) {
        return self::get_active_menu() === $menu_slug;
    }
    
    /**
     * Get the menu item URL by slug
     *
     * @param string $menu_slug The menu slug
     * @return string The full admin URL
     */
    public static function get_menu_url($menu_slug) {
        return admin_url('admin.php?page=' . $menu_slug);
    }
    
    /**
     * Render the navigation tabs
     *
     * @param string $current Current page slug
     * @param array $tabs Array of tabs [slug => title]
     */
    public static function render_tabs($current, $tabs) {
        echo '<div class="nav-tab-wrapper">';
        foreach ($tabs as $slug => $title) {
            $class = ($current === $slug) ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url(self::get_menu_url($slug)) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($title) . '</a>';
        }
        echo '</div>';
    }
}