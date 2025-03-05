<?php
/**
 * Dashboard Widgets Manager
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

class DashboardWidgets {
    private static $instance = null;
    private $timestamp = '2025-03-05 19:22:34';
    private $user = 'ApolloWeb';
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'registerWidgets']);
    }
    
    /**
     * Register dashboard widgets
     */
    public function registerWidgets() {
        // Core status widget
        wp_add_dashboard_widget(
            'wpwprintifysync_status_widget',
            __('Printify Sync Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderStatusWidget']
        );
        
        // Country visitors widget
        wp_add_dashboard_widget(
            'wpwprintifysync_visitors_widget',
            __('Visitors by Country', 'wp-woocommerce-printify-sync'),
            [$this, 'renderVisitorsWidget']
        );
        
        // Recent orders widget
        wp_add_dashboard_widget(
            'wpwprintifysync_orders_widget',
            __('Printify Recent Orders', 'wp-woocommerce-printify-sync'),
            [$this, 'renderOrdersWidget']
        );
        
        // Product sync widget
        wp_add_dashboard_widget(
            'wpwprintifysync_product_sync_widget',
            __('Product Sync Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderProductSyncWidget']
        );
        
        // Webhook events widget
        wp_add_dashboard_widget(
            'wpwprintifysync_webhook_widget',
            __('Recent Webhook Events', 'wp-woocommerce-printify-sync'),
            [$this, 'renderWebhookWidget']
        );
        
        // Currency exchange widget
        wp_add_dashboard_widget(
            'wpwprintifysync_currency_widget',
            __('Currency Exchange Rates', 'wp-woocommerce-printify-sync'),
            [$this, 'renderCurrencyWidget']
        );
        
        // Support tickets widget
        wp_add_dashboard_widget(
            'wpwprintifysync_tickets_widget',
            __('Recent Support Tickets', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketsWidget']
        );
    }
    
    /**
     * Render status widget
     */
    public function renderStatusWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/status-widget.php');
    }
    
    /**
     * Render visitors by country widget
     */
    public function renderVisitorsWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/visitors-widget.php');
    }
    
    /**
     * Render recent orders widget
     */
    public function renderOrdersWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/orders-widget.php');
    }
    
    /**
     * Render product sync widget
     */
    public function renderProductSyncWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/product-sync-widget.php');
    }
    
    /**
     * Render webhook events widget
     */
    public function renderWebhookWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/webhook-widget.php');
    }
    
    /**
     * Render currency exchange widget
     */
    public function renderCurrencyWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/currency-widget.php');
    }
    
    /**
     * Render support tickets widget
     */
    public function renderTicketsWidget() {
        include(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard/tickets-widget.php');
    }
}