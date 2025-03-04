<?php
/**
 * Admin Menu Handler
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminMenu
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
        $this->init();
    }

    /**
     * Initialize the admin menu
     */
    private function init() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Register the admin menus
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            'Printify Sync', 
            'Printify Sync', 
            'manage_options', 
            'wp-woocommerce-printify-sync', 
            [$this, 'display_dashboard_page'], 
            'dashicons-admin-generic', 
            20
        );

        // Submenu pages in the exact order specified
        $submenu_pages = [
            [
                'title' => 'Dashboard',
                'slug' => 'wp-woocommerce-printify-sync',
                'callback' => [$this, 'display_dashboard_page']
            ],
            [
                'title' => 'Shops',
                'slug' => 'printify-shops',
                'callback' => [$this, 'display_shops_page']
            ],
            [
                'title' => 'Products',
                'slug' => 'printify-products',
                'callback' => [$this, 'display_products_page']
            ],
            [
                'title' => 'Orders',
                'slug' => 'printify-orders',
                'callback' => [$this, 'display_orders_page']
            ],
            [
                'title' => 'Exchange Rates',
                'slug' => 'printify-exchange-rates',
                'callback' => [$this, 'display_exchange_rates_page']
            ],
            [
                'title' => 'Logs',
                'slug' => 'printify-logs',
                'callback' => [$this, 'display_logs_page']
            ],
            [
                'title' => 'Settings',
                'slug' => 'printify-settings',
                'callback' => [$this, 'display_settings_page']
            ]
        ];
        
        // Add Postman in debug mode only
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $submenu_pages[] = [
                'title' => 'API Postman',
                'slug' => 'printify-postman',
                'callback' => [$this, 'display_postman_page']
            ];
        }
        
        // Register all submenu pages
        foreach ($submenu_pages as $page) {
            add_submenu_page(
                'wp-woocommerce-printify-sync',
                $page['title'],
                $page['title'],
                'manage_options',
                $page['slug'],
                $page['callback']
            );
        }
    }

    /**
     * Try to locate a template file
     *
     * @param string $template_name Template name to search for
     * @return string|null Path to the template if found, null otherwise
     */
    private function locate_template($template_name) {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/' . $template_name;
        
        if (file_exists($template_path)) {
            if (function_exists('printify_sync_debug')) {
                printify_sync_debug('✅ Template found: ' . $template_name);
            }
            return $template_path;
        }
        
        if (function_exists('printify_sync_debug')) {
            printify_sync_debug('❌ Template not found: ' . $template_name);
        }
        return null;
    }

    /**
     * Display the dashboard page
     */
    public function display_dashboard_page() {
        $template = $this->locate_template('admin-dashboard.php');
        
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Try to load with class if template not found
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard')) {
            $dashboard = new AdminDashboard();
            if (method_exists($dashboard, 'render')) {
                $dashboard->render();
                return;
            }
        }
        
        // Fallback
        $current_user = function_exists('printify_sync_get_current_user') ? 
            printify_sync_get_current_user() : 'No user';
            
        $current_datetime = function_exists('printify_sync_get_current_datetime') ?
            printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
            
        echo '<div class="wrap">';
        echo '<h1><i class="fas fa-tachometer-alt"></i> Printify Sync Dashboard</h1>';
        echo '<p>Welcome to Printify Sync.</p>';
        
        if (current_user_can('manage_options')) {
            echo '<h2>Diagnostic Information</h2>';
            echo '<pre style="background:#f8f8f8; padding:10px; border:1px solid #ddd;">';
            echo 'Plugin Path: ' . esc_html(PRINTIFY_SYNC_PATH) . "\n";
            echo 'Plugin URL: ' . esc_html(PRINTIFY_SYNC_URL) . "\n";
            echo 'Plugin Version: ' . esc_html(PRINTIFY_SYNC_VERSION) . "\n";
            echo 'WordPress Version: ' . esc_html(get_bloginfo('version')) . "\n";
            echo 'PHP Version: ' . esc_html(PHP_VERSION) . "\n";
            echo 'Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted): ' . esc_html($current_datetime) . "\n";
            echo 'Current User\'s Login: ' . esc_html($current_user) . "\n";
            echo '</pre>';
        }
        
        echo '</div>';
    }

    /**
     * Display the settings page - use Settings namespace
     */
    public function display_settings_page() {
        $template = $this->locate_template('settings-page.php');
        
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Check for environment settings as an alternative
        $env_template = $this->locate_template('environment-settings-page.php');
        if ($env_template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $env_template;
            return;
        }
        
        // Try to load with class from Settings namespace
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Fallback
        echo '<div class="wrap"><h1><i class="fas fa-cog"></i> Settings</h1>';
        echo '<p>Configure settings here.</p></div>';
    }

    /**
     * Display the postman page - use Integration namespace
     */
    public function display_postman_page() {
        $template = $this->locate_template('postman-page.php');
        
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Try to load with class from Integration namespace (new location)
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Integration\PostmanPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Integration\PostmanPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Fallback to Admin namespace temporarily during transition
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\PostmanPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Admin\PostmanPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Final fallback
        echo '<div class="wrap"><h1><i class="fas fa-paper-plane"></i> API Postman</h1>';
        echo '<p>Test API requests here.</p></div>';
    }

    /**
     * Display the products page - use Features\Products namespace
     */
    public function display_products_page() {
        $template = $this->locate_template('products-import.php');
        if (!$template) {
            $template = $this->locate_template('products-import-page.php');
        }
        
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Try to load with class from Features namespace
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Features\Products\ProductImport')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Features\Products\ProductImport();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Fallback to old location
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\ProductImport')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Admin\ProductImport();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Final fallback
        echo '<div class="wrap"><h1><i class="fas fa-shirt"></i> Products</h1>';
        echo '<p>Manage your Printify products here.</p></div>';
    }

    /**
     * Display the orders page - use Features\Orders namespace
     */
    public function display_orders_page() {
        $template = $this->locate_template('orders-page.php');
            
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Try to load with class from Features namespace
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Features\Orders\OrdersPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Features\Orders\OrdersPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Fallback
        echo '<div class="wrap"><h1><i class="fas fa-shopping-cart"></i> Orders</h1>';
        echo '<p>Manage your orders here.</p></div>';
    }

    /**
     * Display the shops page - use Features\Shops namespace
     */
    public function display_shops_page() {
        $template = $this->locate_template('shops-page.php');
        
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Try to load with class from Features namespace
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Features\Shops\ShopsPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Features\Shops\ShopsPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Fallback to old location
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\ShopsPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Admin\ShopsPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Final fallback
        echo '<div class="wrap"><h1><i class="fas fa-store"></i> Shops</h1>';
        echo '<p>Manage your Printify shops here.</p></div>';
    }

    /**
     * Display the exchange rates page - use Features\ExchangeRates namespace
     */
    public function display_exchange_rates_page() {
        $template = $this->locate_template('exchange-rates-page.php');
        
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Try to load with class from Features namespace
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Features\ExchangeRates\ExchangeRatesPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Features\ExchangeRates\ExchangeRatesPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Fallback to old location
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\ExchangeRatesPage')) {
            $page = new \ApolloWeb\WPWooCommercePrintifySync\Admin\ExchangeRatesPage();
            if (method_exists($page, 'render')) {
                $page->render();
                return;
            }
        }
        
        // Final fallback
        echo '<div class="wrap"><h1><i class="fas fa-exchange-alt"></i> Exchange Rates</h1>';
        echo '<p>Manage exchange rates here.</p></div>';
    }

    /**
     * Display the logs page
     */
    public function display_logs_page() {
        $template = $this->locate_template('logs-page.php');
            
        if ($template) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template;
            return;
        }
        
        // Fallback
        echo '<div class="wrap"><h1><i class="fas fa-clipboard-list"></i> Logs</h1>';
        echo '<p>View logs here.</p></div>';
    }
}
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added:         echo '<p>View logs here.</p></div>';
#
#
# Commit Hash 16c804f
#
