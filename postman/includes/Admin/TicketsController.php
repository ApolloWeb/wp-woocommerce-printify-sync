<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class TicketsController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addMenu() {
        add_submenu_page(
            'printify-sync',
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-tickets',
            [self::class, 'renderTicketsPage']
        );
    }

    public static function renderTicketsPage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-tickets.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-tickets') {
            return;
        }
        wp_enqueue_style('printify-sync-tickets-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/tickets.css');
        wp_enqueue_script('printify-sync-tickets-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/tickets.js', ['jquery'], null, true);
    }
}