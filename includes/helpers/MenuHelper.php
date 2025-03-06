<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Menu;

class MenuHelper {
    
    public static function init() {
        Menu::registerMenus();
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    public static function enqueueAssets() {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        wp_enqueue_script('wpwprintifysync-menu-icon', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/menu-icon.js', ['jquery'], null, true);
    }
}