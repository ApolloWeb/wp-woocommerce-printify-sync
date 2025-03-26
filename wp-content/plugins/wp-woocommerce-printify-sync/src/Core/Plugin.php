<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Dashboard;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Orders;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Products;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Shipping;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Tickets;
use ApolloWeb\WPWooCommercePrintifySync\Services\AdminAssets;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionScheduler;
use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;

class Plugin {
    /**
     * @var Plugin Single instance of this class
     */
    private static $instance;

    /**
     * @var BladeTemplateEngine
     */
    private $templateEngine;

    /**
     * @var PrintifyAPI
     */
    private $printifyAPI;

    /**
     * Get single instance of Plugin class
     */
    public static function getInstance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    public function init(): void {
        // Initialize services
        $this->initServices();
        
        // Initialize admin
        if (is_admin()) {
            $this->initAdmin();
        }

        // Initialize webhooks
        add_action('rest_api_init', [$this, 'initWebhooks']);

        // Initialize Action Scheduler tasks
        ActionScheduler::init();
    }

    /**
     * Initialize core services
     */
    private function initServices(): void {
        // Initialize template engine
        $this->templateEngine = new BladeTemplateEngine();
        
        // Initialize Printify API service
        $this->printifyAPI = new PrintifyAPI();
        
        // Initialize assets
        new AdminAssets();
    }

    /**
     * Initialize admin pages
     */
    private function initAdmin(): void {
        // Add custom icon CSS
        add_action('admin_head', function() {
            echo '<style>
                #adminmenu .toplevel_page_wpwps-dashboard .wp-menu-image::before {
                    font-family: "Font Awesome 6 Free";
                    content: "\f553";
                    font-weight: 900;
                }
            </style>';
        });

        // Add admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Initialize admin pages
        new Dashboard($this->templateEngine, $this->printifyAPI);
        new Products($this->templateEngine, $this->printifyAPI);
        new Orders($this->templateEngine, $this->printifyAPI);
        new Settings($this->templateEngine, $this->printifyAPI);
        new Shipping($this->templateEngine, $this->printifyAPI);
        new Tickets($this->templateEngine, $this->printifyAPI);
    }

    /**
     * Add admin menu items
     */
    public function addAdminMenu(): void {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'none', // We're using custom FA icon via CSS
            56
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard'
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProducts']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrders']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-shipping',
            [$this, 'renderShipping']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Tickets', 'wp-woocommerce-printify-sync'),
            __('Tickets', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-tickets',
            [$this, 'renderTickets']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    /**
     * Initialize webhooks
     */
    public function initWebhooks(): void {
        // Register webhook endpoints
        register_rest_route('wpwps/v1', '/webhook/product', [
            'methods' => 'POST',
            'callback' => [$this, 'handleProductWebhook'],
            'permission_callback' => [$this, 'validateWebhook']
        ]);

        register_rest_route('wpwps/v1', '/webhook/order', [
            'methods' => 'POST',
            'callback' => [$this, 'handleOrderWebhook'],
            'permission_callback' => [$this, 'validateWebhook']
        ]);
    }
}