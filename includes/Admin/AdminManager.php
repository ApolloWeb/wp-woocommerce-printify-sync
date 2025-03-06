<?php
/**
 * Admin Manager class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class AdminManager
 * Manages all admin pages and menus
 */
class AdminManager {

    /**
     * Initialize the admin pages
     *
     * @return void
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Initialize individual page controllers
        new ShopsPage();
        new ProductImportPage();
        new CurrencySettingsPage();
        new StockManagementPage();
        new TestingDebuggingPage();
        new OrderTicketingPage();
        new CustomerNotificationsPage();
        new SettingsPage();
        new PostmanManagementPage();
        new LogViewerPage();
        
        // Add dashboard widgets
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
    }

    /**
     * Register admin menu and pages
     *
     * @return void
     */
    public function register_admin_pages() {
        add_menu_page(
            __( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
            __( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync',
            array( $this, 'render_dashboard' ),
            'dashicons-store',
            56
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Dashboard', 'wp-woocommerce-printify-sync' ),
            __( 'Dashboard', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync',
            array( $this, 'render_dashboard' )
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Shops', 'wp-woocommerce-printify-sync' ),
            __( 'Shops', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync-shops',
            array( $this, 'render_shops_page' )
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Product Import', 'wp-woocommerce-printify-sync' ),
            __( 'Product Import', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync-products',
            array( $this, 'render_product_import_page' )
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Currency Settings', 'wp-woocommerce-printify-sync' ),
            __( 'Currency', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync-currency',
            array( $this, 'render_currency_settings_page' )
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Stock Management', 'wp-woocommerce-printify-sync' ),
            __( 'Stock', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync-stock',
            array( $this, 'render_stock_management_page' )
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Orders & Tickets', 'wp-woocommerce-printify-sync' ),
            __( 'Orders & Tickets', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'printify-sync-orders',
            array( $this, 'render_order_ticketing_page' )
        );
        
        add_submenu_page(
            'printify-sync',
            __( 'Customer Notifications', 'wp-woocommerce-printify-sync' ),
            __(