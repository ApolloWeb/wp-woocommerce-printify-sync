<?php
/**
 * Admin Menu Setup
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminMenu {
    /**
     * Initialize the admin menu
     */
    public function init() {
        add_action( 'admin_menu', [ $this, 'registerMenuPages' ] );
    }
    
    /**
     * Register admin menu pages
     */
    public function registerMenuPages() {
        add_menu_page(
            __( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
            __( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'wpwps-dashboard',
            [ $this, 'renderDashboardPage' ],
            'dashicons-admin-generic',
            58 // After WooCommerce
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __( 'Dashboard', 'wp-woocommerce-printify-sync' ),
            __( 'Dashboard', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'wpwps-dashboard',
            [ $this, 'renderDashboardPage' ]
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __( 'Products', 'wp-woocommerce-printify-sync' ),
            __( 'Products', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'wpwps-products',
            [ $this, 'renderProductsPage' ]
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __( 'Settings', 'wp-woocommerce-printify-sync' ),
            __( 'Settings', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'wpwps-settings',
            [ $this, 'renderSettingsPage' ]
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __( 'Logs', 'wp-woocommerce-printify-sync' ),
            __( 'Logs', 'wp-woocommerce-printify-sync' ),
            'manage_woocommerce',
            'wpwps-logs',
            [ $this, 'renderLogsPage' ]
        );
    }
    
    /**
     * Render dashboard page
     */
    public function renderDashboardPage() {
        require_once WPWPS_PATH . 'templates/admin/dashboard.php';
    }
    
    /**
     * Render products page
     */
    public function renderProductsPage() {
        require_once WPWPS_PATH . 'templates/admin/products.php';
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        require_once WPWPS_PATH . 'templates/admin/settings.php';
    }
    
    /**
     * Render logs page
     */
    public function renderLogsPage() {
        require_once WPWPS_PATH . 'templates/admin/logs.php';
    }
}
