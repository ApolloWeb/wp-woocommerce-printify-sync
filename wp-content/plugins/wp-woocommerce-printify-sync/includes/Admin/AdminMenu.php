<?php
/**
 * Admin menu handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Plugin;
use ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine;

/**
 * Class AdminMenu
 */
class AdminMenu {
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private $plugin;
    
    /**
     * Template engine
     *
     * @var TemplateEngine
     */
    private $template;
    
    /**
     * Menu pages
     *
     * @var array
     */
    private $pages = [];

    /**
     * Constructor
     *
     * @param Plugin $plugin Plugin instance.
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $this->template = new TemplateEngine();
        
        add_action('admin_menu', [$this, 'registerMenus']);
    }

    /**
     * Register admin menus
     *
     * @return void
     */
    public function registerMenus() {
        // Main menu
        $this->pages['dashboard'] = add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            57 // After WooCommerce
        );
        
        // Dashboard submenu (to rename the default)
        $this->pages['dashboard_sub'] = add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard']
        );
        
        // Products submenu
        $this->pages['products'] = add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProducts']
        );
        
        // Orders submenu
        $this->pages['orders'] = add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrders']
        );
        
        // Tickets submenu
        $this->pages['tickets'] = add_submenu_page(
            'wpwps-dashboard',
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-tickets',
            [$this, 'renderTickets']
        );
        
        // Settings submenu
        $this->pages['settings'] = add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
        
        // Logs submenu
        $this->pages['logs'] = add_submenu_page(
            'wpwps-dashboard',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-logs',
            [$this, 'renderLogs']
        );
    }

    /**
     * Render dashboard page
     *
     * @return void
     */
    public function renderDashboard() {
        $dashboard = new Dashboard($this->plugin);
        $dashboard->render();
    }
    
    /**
     * Render products page
     *
     * @return void
     */
    public function renderProducts() {
        $products = new Products($this->plugin);
        $products->render();
    }
    
    /**
     * Render orders page
     *
     * @return void
     */
    public function renderOrders() {
        $orders = new Orders($this->plugin);
        $orders->render();
    }
    
    /**
     * Render tickets page
     *
     * @return void
     */
    public function renderTickets() {
        $tickets = new Tickets($this->plugin);
        $tickets->render();
    }
    
    /**
     * Render settings page
     *
     * @return void
     */
    public function renderSettings() {
        $settings = new Settings($this->plugin);
        $settings->render();
    }
    
    /**
     * Render logs page
     *
     * @return void
     */
    public function renderLogs() {
        $logs = new Logs($this->plugin);
        $logs->render();
    }
}
