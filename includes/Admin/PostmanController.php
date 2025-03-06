<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class PostmanController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addMenu() {
        add_submenu_page(
            'printify-sync',
            __('Postman Collection', 'wp-woocommerce-printify-sync'),
            __('Postman Collection', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-postman',
            [self::class, 'renderPostmanPage']
        );
    }

    public static function renderPostmanPage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-postman.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-postman') {
            return;
        }
        wp_enqueue_style('printify-sync-postman-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/postman.css');
        wp_enqueue_script('printify-sync-postman-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/postman.js', ['jquery'], null, true);
    }
}