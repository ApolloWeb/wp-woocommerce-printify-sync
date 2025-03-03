<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\IncomingTicketsWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\OrderTrackingWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\WebhookStatusWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\ApiCallLogsWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\ShopInfoWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\ProductSyncSummaryWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\OrdersOverviewWidget;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets\StockLevelsWidget;

class AdminDashboard
{
    public static function register()
    {
        add_action('admin_menu', [__CLASS__, 'addMenu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueAssets']);
    }

    public static function addMenu()
    {
        add_menu_page(
            'Printify Sync Dashboard',
            'Printify Sync',
            'manage_options',
            'printify-sync-dashboard',
            [__CLASS__, 'renderDashboard'],
            'dashicons-admin-generic', // Replace with a suitable dashicon
            56
        );

        add_submenu_page(
            'printify-sync-dashboard',
            'Shops',
            'Shops',
            'manage_options',
            'printify-sync-shops',
            [ShopsPage::class, 'renderPage']
        );

        add_submenu_page(
            'printify-sync-dashboard',
            'Product Import',
            'Product Import',
            'manage_options',
            'printify-sync-product-import',
            [ProductImport::class, 'renderPage']
        );

        add_submenu_page(
            'printify-sync-dashboard',
            'Exchange Rates',
            'Exchange Rates',
            'manage_options',
            'printify-sync-exchange-rates',
            [ExchangeRatesPage::class, 'renderPage']
        );

        add_submenu_page(
            'printify-sync-dashboard',
            'Postman',
            'Postman',
            'manage_options',
            'printify-sync-postman',
            [PostmanPage::class, 'renderPage']
        );
    }

    public static function enqueueAssets($hook)
    {
        if ($hook !== 'toplevel_page_printify-sync-dashboard') {
            return;
        }

       wp_enqueue_script('admin-dashboard', plugins_url('../../assets/js/admin-dashboard.js', __FILE__), ['jquery'], '1.0.0', true);
        wp_enqueue_style('admin-dashboard', plugins_url('../../assets/css/admin-dashboard.css', __FILE__), [], '1.0.0');
    }

    public static function renderDashboard()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/admin-dashboard.php';
    }
}