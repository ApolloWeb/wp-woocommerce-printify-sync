<?php
/**
 * Admin class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Admin
 * Manages all admin functionality
 */
class Admin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    public function init_hooks() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Initialize individual admin pages
        new Pages\Dashboard();
        new Pages\Shops();
        new Pages\ProductImport();
        new Pages\CurrencySettings();
        new Pages\StockManagement();
        new Pages\OrderTicketing();
        new Pages\TestingDebugging();
        new Pages\Settings();
        new Pages\LogViewer();
    }

    /**
     * Register admin menu items
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
            __( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync',
            null,
            'dashicons-store',
            56
        );

        add_submenu_page(
            'printify-sync',
            __( 'Dashboard', 'wp-woocommerce-printify-sync' ),
            __( 'Dashboard', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Shops', 'wp-woocommerce-printify-sync' ),
            __( 'Shops', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-shops',
            array( $this, 'render_shops_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Product Import', 'wp-woocommerce-printify-sync' ),
            __( 'Product Import', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-products',
            array( $this, 'render_product_import_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Currency Settings', 'wp-woocommerce-printify-sync' ),
            __( 'Currency', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-currency',
            array( $this, 'render_currency_settings_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Stock Management', 'wp-woocommerce-printify-sync' ),
            __( 'Stock', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-stock',
            array( $this, 'render_stock_management_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Orders & Tickets', 'wp-woocommerce-printify-sync' ),
            __( 'Orders & Tickets', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-orders',
            array( $this, 'render_order_ticketing_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Testing & Debugging', 'wp-woocommerce-printify-sync' ),
            __( 'Testing', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-testing',
            array( $this, 'render_testing_debugging_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Settings', 'wp-woocommerce-printify-sync' ),
            __( 'Settings', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-settings',
            array( $this, 'render_settings_page' )
        );

        add_submenu_page(
            'printify-sync',
            __( 'Logs', 'wp-woocommerce-printify-sync' ),
            __( 'Logs', 'wp-woocommerce-printify-sync' ),
            'manage_options',
            'printify-sync-logs',
            array( $this, 'render_log_viewer_page' )
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin pages
        if ( strpos( $hook, 'printify-sync' ) === false ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'printify-sync-admin-css',
            PRINTIFY_SYNC_URL . 'assets/css/admin.css',
            array(),
            PRINTIFY_SYNC_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'printify-sync-admin-js',
            PRINTIFY_SYNC_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            PRINTIFY_SYNC_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'printify-sync-admin-js',
            'PrintifySyncAdmin',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'printify_sync_admin_nonce' ),
                'i18n' => array(
                    'confirm_delete' => __( 'Are you sure you want to delete this?', 'wp-woocommerce-printify-sync' ),
                    'processing' => __( 'Processing...', 'wp-woocommerce-printify-sync' ),
                    'success' => __( 'Success!', 'wp-woocommerce-printify-sync' ),
                    'error' => __( 'Error!', 'wp-woocommerce-printify-sync' ),
                )
            )
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        // Let the Dashboard page class handle the rendering
        do_action( 'printify_sync_render_dashboard_page' );
    }

    /**
     * Render shops page
     */
    public function render_shops_page() {
        do_action( 'printify_sync_render_shops_page' );
    }

    /**
     * Render product import page
     */
    public function render_product_import_page() {
        do_action( 'printify_sync_render_product_import_page' );
    }

    /**
     * Render currency settings page
     */
    public function render_currency_settings_page() {
        do_action( 'printify_sync_render_currency_settings_page' );
    }

    /**
     * Render stock management page
     */
    public function render_stock_management_page() {
        do_action( 'printify_sync_render_stock_management_page' );
    }

    /**
     * Render order ticketing page
     */
    public function render_order_ticketing_page() {
        do_action( 'printify_sync_render_order_ticketing_page' );
    }

    /**
     * Render testing debugging page
     */
    public function render_testing_debugging_page() {
        do_action( 'printify_sync_render_testing_debugging_page' );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        do_action( 'printify_sync_render_settings_page' );
    }

    /**
     * Render log viewer page
     */
    public function render_log_viewer_page() {
        do_action( 'printify_sync_render_log_viewer_page' );
    }
}