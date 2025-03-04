<?php

/**
 * Admin Menu Handler
 * 
 * Responsible for registering and managing the admin menu for the plugin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\MenuHelper;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminMenu
 * 
 * Handles the admin menu registration and structure for the plugin
 */
class AdminMenu {
    /**
     * Singleton instance
     * 
     * @var AdminMenu
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     * 
     * @return AdminMenu
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register the singleton instance
     */
    public static function register() {
        self::get_instance();
    }

    /**
     * Private constructor to prevent multiple instances
     */
    private function __construct() {
        // Initialize the menu
        $this->init();
    }

    /**
     * Initialize the admin menu
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_head', [$this, 'add_menu_icon_styles']);
    }

    /**
     * Register the admin menus
     */
    public function register_menu() {
        // Main menu with custom icon (will be replaced via CSS)
        add_menu_page(
            'Printify Sync', 
            'Printify Sync', 
            'manage_options', 
            'wp-woocommerce-printify-sync', 
            [$this, 'display_dashboard_page'], 
            'dashicons-admin-generic', // Default icon to be replaced
            20
        );

        // Submenu pages
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'wp-woocommerce-printify-sync',
            [$this, 'display_dashboard_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Products',
            'Products',
            'manage_options',
            'printify-products',
            [$this, 'display_products_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Orders',
            'Orders',
            'manage_options',
            'printify-orders',
            [$this, 'display_orders_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Shops',
            'Shops',
            'manage_options',
            'printify-shops',
            [$this, 'display_shops_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Exchange Rates',
            'Exchange Rates',
            'manage_options',
            'printify-exchange-rates',
            [$this, 'display_exchange_rates_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'API Postman',
            'API Postman',
            'manage_options',
            'printify-postman',
            [$this, 'display_postman_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Logs',
            'Logs',
            'manage_options',
            'printify-logs',
            [$this, 'display_logs_page']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Settings',
            'Settings',
            'manage_options',
            'printify-settings',
            [$this, 'display_settings_page']
        );
    }

    /**
     * Add custom styles to replace the dashicon with a Font Awesome icon
     * Based on https://www.powderkegwebdesign.com/replace-dash-icons-with-font-awesome-ones-in-wordpress-admin/
     */
    public function add_menu_icon_styles() {
        ?>
        <style>
            /* Replace the dashicon with a Font Awesome icon */
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync .wp-menu-image:before {
                font-family: "Font Awesome 5 Free" !important;
                content: "\f02f" !important; /* fa-print icon */
                font-weight: 900;
                font-size: 18px !important;
            }
            
            /* Add a sync badge/indicator to the print icon */
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync .wp-menu-image:after {
                font-family: "Font Awesome 5 Free" !important;
                content: "\f021"; /* fa-sync icon */
                position: absolute;
                font-size: 10px !important;
                font-weight: 900;
                right: 7px;
                bottom: 3px;
                background: rgba(0, 0, 0, 0.5);
                border-radius: 50%;
                width: 14px;
                height: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Fix position of the main icon */
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync .wp-menu-image {
                position: relative;
            }
            
            /* Ensure spacing is correct */
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync div.wp-menu-image:before {
                padding-top: 8px !important;
            }
        </style>
        <?php
    }

    /**
     * Display the dashboard page
     */
    public function display_dashboard_page() {
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard')) {
            $dashboard = new AdminDashboard();
            $dashboard->render();
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-tachometer-alt"></i> Printify Sync Dashboard</h1><p>Dashboard component not found.</p></div>';
        }
    }

    /**
     * Display the products page
     */
    public function display_products_page() {
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Admin\ProductImport')) {
            $page = new ProductImport();
            $page->render();
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-box-open"></i> Products</h1><p>Products component not found.</p></div>';
        }
    }

    /**
     * Display the orders page
     */
    public function display_orders_page() {
        // Use plugin_dir_path to get the correct path
        $template_path = defined('PRINTIFY_SYNC_PATH') ? 
            PRINTIFY_SYNC_PATH . 'templates/admin/orders-page.php' : 
            plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/orders-page.php';
            
        if (file_exists($template_path)) {
            include_once $template_path;
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-shopping-cart"></i> Orders</h1><p>Template not found.</p></div>';
        }
    }

    /**
     * Display the shops page
     */
    public function display_shops_page() {
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Admin\ShopsPage')) {
            $page = new ShopsPage();
            $page->render();
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-store"></i> Shops</h1><p>Shops component not found.</p></div>';
        }
    }

    /**
     * Display the exchange rates page
     */
    public function display_exchange_rates_page() {
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Admin\ExchangeRatesPage')) {
            $page = new ExchangeRatesPage();
            $page->render();
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-exchange-alt"></i> Exchange Rates</h1><p>Exchange Rates component not found.</p></div>';
        }
    }

    /**
     * Display the postman page
     */
    public function display_postman_page() {
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Admin\PostmanPage')) {
            $page = new PostmanPage();
            $page->render();
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-paper-plane"></i> API Postman</h1><p>Postman component not found.</p></div>';
        }
    }

    /**
     * Display the logs page
     */
    public function display_logs_page() {
        // Use plugin_dir_path to get the correct path
        $template_path = defined('PRINTIFY_SYNC_PATH') ? 
            PRINTIFY_SYNC_PATH . 'templates/admin/logs-page.php' : 
            plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/logs-page.php';
            
        if (file_exists($template_path)) {
            include_once $template_path;
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-clipboard-list"></i> Logs</h1><p>Template not found.</p></div>';
        }
    }

    /**
     * Display the settings page
     */
    public function display_settings_page() {
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage')) {
            $page = new SettingsPage();
            $page->render();
        } else {
            echo '<div class="wrap"><h1><i class="fas fa-cogs"></i> Settings</h1><p>Settings component not found.</p></div>';
        }
    }
}