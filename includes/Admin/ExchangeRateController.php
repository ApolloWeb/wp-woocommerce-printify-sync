<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ExchangeRateController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addMenu() {
        add_submenu_page(
            'printify-sync',
            __('Exchange Rates', 'wp-woocommerce-printify-sync'),
            __('Exchange Rates', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-exchange-rate',
            [self::class, 'renderExchangeRatePage']
        );
    }

    public static function renderExchangeRatePage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-exchange-rate.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-exchange-rate') {
            return;
        }
        wp_enqueue_style('printify-sync-exchange-rate-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/exchange-rate.css');
        wp_enqueue_script('printify-sync-exchange-rate-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/exchange-rate.js', ['jquery'], null, true);
    }
}