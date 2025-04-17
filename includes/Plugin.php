<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Admin\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminAssets;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;
use ApolloWeb\WPWooCommercePrintifySync\Admin\ProductsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\OrdersPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AnalyticsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\ToastManager;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AjaxHandler;
use ApolloWeb\WPWooCommercePrintifySync\Admin\WebhookController;

/**
 * Main plugin class
 */
class Plugin {
    /**
     * Assets instance
     *
     * @var Assets
     */
    private $assets;
    
    /**
     * Admin assets instance
     *
     * @var AdminAssets
     */
    private $admin_assets;
    
    /**
     * Template loader instance
     *
     * @var TemplateLoader
     */
    private $template_loader;
    
    /**
     * Printify API instance
     *
     * @var PrintifyApi
     */
    private $api;
    
    /**
     * Settings instance
     *
     * @var Settings
     */
    private $settings;
    
    /**
     * Dashboard instance
     *
     * @var Dashboard
     */
    private $dashboard;
    
    /**
     * Toast manager
     *
     * @var ToastManager
     */
    private $toast_manager;
    
    /**
     * AJAX handler
     *
     * @var AjaxHandler
     */
    private $ajax_handler;
    
    /**
     * Webhook controller
     *
     * @var WebhookController
     */
    private $webhook_controller;
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Wait until init hook to instantiate classes that may use translations
        add_action('init', [$this, 'load_components'], 15);
        
        // Register hooks that don't require translations immediately
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Add custom Font Awesome icon for admin menu
        add_action('admin_head', [$this, 'add_admin_icon_styles']);
    }
    
    /**
     * Add Font Awesome icon styles for admin menu
     */
    public function add_admin_icon_styles() {
        ?>
        <style>
            /* Custom icon for the menu */
            #toplevel_page_wpwps-dashboard .wp-menu-image::before {
                font-family: 'Font Awesome 6 Free';
                content: '\f553'; /* tshirt icon */
                font-weight: 900;
            }
        </style>
        <?php
    }
    
    /**
     * Load plugin components after translations are ready
     */
    public function load_components() {
        $this->assets = new Assets();
        $this->admin_assets = new AdminAssets();
        $this->template_loader = new TemplateLoader();
        $this->settings = new Settings();
        
        // Initialize settings first so API can use them
        $this->settings->init();
        
        // Pass settings to API constructor
        $this->api = new PrintifyApi($this->settings);
        
        $this->dashboard = new Dashboard();
        $this->toast_manager = new ToastManager();
        $this->ajax_handler = new AjaxHandler();
        $this->webhook_controller = new WebhookController();
        
        // Initialize dashboard
        $this->dashboard->init();
        
        // Initialize toast manager
        $this->toast_manager->register_ajax_endpoints();
        add_action('admin_footer', [$this->toast_manager, 'render_toasts']);
        
        // Initialize AJAX handler
        $this->ajax_handler->init();
        
        // Register REST API routes
        add_action('rest_api_init', [$this->webhook_controller, 'register_routes']);
        
        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Init frontend
        $this->init_frontend();
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!isset($this->assets)) {
            $this->assets = new Assets();
        }
        $this->assets->enqueue_frontend_assets();
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        if (!isset($this->admin_assets)) {
            $this->admin_assets = new AdminAssets();
        }
        $this->admin_assets->enqueue_assets();
        
        // Enqueue toast scripts
        if (!isset($this->toast_manager)) {
            $this->toast_manager = new ToastManager();
        }
        $this->toast_manager->enqueue_scripts();
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Initialize settings page
        $settings_page = new SettingsPage($this->settings);
        $settings_page->init();
        
        // Initialize products page
        $products_page = new ProductsPage();
        $products_page->init();
        
        // Initialize orders page
        $orders_page = new OrdersPage();
        $orders_page->init();
        
        // Initialize analytics page
        $analytics_page = new AnalyticsPage();
        $analytics_page->init();
    }
    
    /**
     * Initialize frontend functionality
     */
    private function init_frontend() {
        // Load frontend classes and hooks
    }
    
    /**
     * Get template loader
     *
     * @return TemplateLoader
     */
    public function get_template_loader() {
        return $this->template_loader;
    }
    
    /**
     * Get API instance
     *
     * @return PrintifyApi
     */
    public function get_api() {
        return $this->api;
    }
    
    /**
     * Get settings instance
     *
     * @return Settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get toast manager
     *
     * @return ToastManager
     */
    public function get_toast_manager() {
        return $this->toast_manager;
    }
}
