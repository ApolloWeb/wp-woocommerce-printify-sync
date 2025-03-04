<?php
/**
 * Admin Menu Handler
<<<<<<< HEAD
 *
=======
 * 
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
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
<<<<<<< HEAD

    /**
     * Singleton instance
     *
     * @var AdminMenu
     */
    private static $instance = null;
    
    /**
     * Plugin path
     *
     * @var string
     */
    private $plugin_path;
    
    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;
    
    /**
     * Plugin version
     *
     * @var string
     */
    private $version;
    
    /**
     * Plugin slug
     *
     * @var string
     */
    private $plugin_slug = 'wp-woocommerce-printify-sync';

    /**
     * Get the singleton instance
     *
=======
    /**
     * Singleton instance
     * 
     * @var AdminMenu
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     * 
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
     * @return AdminMenu
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
<<<<<<< HEAD
     * Constructor
     */
    private function __construct() {
        $this->plugin_path = PRINTIFY_SYNC_PATH;
        $this->plugin_url = PRINTIFY_SYNC_URL;
        $this->version = PRINTIFY_SYNC_VERSION;
        
        // Add custom icon styles
        add_action('admin_head', [$this, 'add_menu_icon_styles']);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Static register method - this is the main entry point for initializing the admin menu
     */
    public static function register() {
        $instance = self::get_instance();
        add_action('admin_menu', [$instance, 'register_menu']);
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Add Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );
        
        // Add common admin styles
        wp_enqueue_style(
            'printify-sync-admin-common',
            $this->plugin_url . 'assets/css/admin-common.css',
            [],
            $this->version
        );
        
        // Add settings styles if on settings page
        if (strpos($hook, 'printify-settings') !== false) {
            wp_enqueue_style(
                'printify-sync-admin-settings',
                $this->plugin_url . 'assets/css/admin-settings.css',
                [],
                $this->version
            );
        }
    }

    /**
     * Register the menu
     */
    public function register_menu() {
        // Check environment mode
        $environment = get_option('printify_sync_environment', 'production');
        $is_development = ($environment === 'development' || (defined('WP_DEBUG') && WP_DEBUG));
        
        // Add main menu item with 'none' as the icon, we'll use CSS to add our custom icon
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            $this->plugin_slug,
            [$this, 'display_dashboard_page'],
            'none',
            58
        );
        
        // Define submenu pages
        $submenu_pages = [
            [
                'title' => 'Dashboard',
                'slug' => $this->plugin_slug,
                'callback' => [$this, 'display_dashboard_page'],
                'template_key' => 'admin-dashboard'
=======
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
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
            ],
            [
                'title' => 'Shops',
                'slug' => 'printify-shops',
<<<<<<< HEAD
                'callback' => [$this, 'display_shops_page'],
                'template_key' => 'shops'
=======
                'callback' => [$this, 'display_shops_page']
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
            ],
            [
                'title' => 'Products',
                'slug' => 'printify-products',
<<<<<<< HEAD
                'callback' => [$this, 'display_products_page'],
                'template_key' => 'products'
=======
                'callback' => [$this, 'display_products_page']
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
            ],
            [
                'title' => 'Orders',
                'slug' => 'printify-orders',
<<<<<<< HEAD
                'callback' => [$this, 'display_orders_page'],
                'template_key' => 'orders'
=======
                'callback' => [$this, 'display_orders_page']
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
            ],
            [
                'title' => 'Exchange Rates',
                'slug' => 'printify-exchange-rates',
<<<<<<< HEAD
                'callback' => [$this, 'display_exchange_rates_page'],
                'template_key' => 'exchange-rates'
=======
                'callback' => [$this, 'display_exchange_rates_page']
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
            ],
            [
                'title' => 'Logs',
                'slug' => 'printify-logs',
<<<<<<< HEAD
                'callback' => [$this, 'display_logs_page'],
                'template_key' => 'logs'
            ],
            [
                'title' => 'Tickets',
                'slug' => 'printify-tickets',
                'callback' => [$this, 'display_tickets_page'],
                'template_key' => 'tickets'
=======
                'callback' => [$this, 'display_logs_page']
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
            ],
            [
                'title' => 'Settings',
                'slug' => 'printify-settings',
<<<<<<< HEAD
                'callback' => [$this, 'display_settings_page'],
                'template_key' => 'settings'
            ]
        ];
        
        // Add Postman page ONLY in development mode
        if ($is_development) {
            $submenu_pages[] = [
                'title' => 'API Postman',
                'slug' => 'printify-postman',
                'callback' => [$this, 'display_postman_page'],
                'template_key' => 'postman'
            ];
        }
        
        // Add all submenu items
        foreach ($submenu_pages as $page) {
            add_submenu_page(
                $this->plugin_slug,
                __($page['title'], 'wp-woocommerce-printify-sync'),
                __($page['title'], 'wp-woocommerce-printify-sync'),
                'manage_woocommerce',
=======
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
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
                $page['slug'],
                $page['callback']
            );
        }
<<<<<<< HEAD
        
        // Also remove direct access to the Postman page in production mode
        if (!$is_development) {
            add_action('admin_init', function() {
                global $pagenow;
                if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'printify-postman') {
                    wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug));
                    exit;
                }
            });
        }
    }
    
    /**
     * Add custom menu icon styles
     */
    public function add_menu_icon_styles() {
        ?>
        <style>
            /* Custom menu icon - T-shirt in white using Font Awesome */
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync .wp-menu-image::before {
                content: '\f553'; /* Font Awesome t-shirt icon */
                font-family: 'Font Awesome 6 Free';
                font-weight: 900;
                color: #fff !important;
                font-size: 18px !important;
            }
            
            /* Set WP admin menu background to Printify purple */
            #adminmenu li.toplevel_page_wp-woocommerce-printify-sync div.wp-menu-image {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Override WordPress default icon coloring */
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync:hover .wp-menu-image::before, 
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync.current .wp-menu-image::before,
            #adminmenu .toplevel_page_wp-woocommerce-printify-sync.wp-has-current-submenu .wp-menu-image::before {
                color: #fff !important;
            }
        </style>
        <?php
=======
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
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
    }

    /**
     * Display the dashboard page
     */
    public function display_dashboard_page() {
<<<<<<< HEAD
        $this->display_page('admin-dashboard', 'Dashboard', 'fas fa-tachometer-alt');
    }

    /**
     * Display the shops page
     */
    public function display_shops_page() {
        $this->display_page('shops', 'Shops', 'fas fa-store');
    }

    /**
     * Display the products page
     */
    public function display_products_page() {
        $this->display_page('products', 'Products', 'fas fa-shirt');
    }

    /**
     * Display the orders page
     */
    public function display_orders_page() {
        $this->display_page('orders', 'Orders', 'fas fa-shopping-cart');
    }

    /**
     * Display the exchange rates page
     */
    public function display_exchange_rates_page() {
        $this->display_page('exchange-rates', 'Exchange Rates', 'fas fa-exchange-alt');
=======
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
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
    }

    /**
     * Display the logs page
     */
    public function display_logs_page() {
<<<<<<< HEAD
        $this->display_page('logs', 'Logs', 'fas fa-clipboard-list');
    }

    /**
     * Display the tickets page
     */
    public function display_tickets_page() {
        $this->display_page('tickets', 'Support Tickets', 'fas fa-ticket-alt');
    }

    /**
     * Display the settings page
     */
    public function display_settings_page() {
        $this->display_page('settings', 'Settings', 'fas fa-cog');
    }

    /**
     * Display the postman page
     */
    public function display_postman_page() {
        // If in production mode, redirect to dashboard
        $environment = get_option('printify_sync_environment', 'production');
        $is_development = ($environment === 'development' || (defined('WP_DEBUG') && WP_DEBUG));
        
        if (!$is_development) {
            wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug));
            exit;
        }
        
        $this->display_page('postman', 'API Postman', 'fas fa-paper-plane');
    }

    /**
     * Display a page
     *
     * @param string $template The template to display
     * @param string $title The page title
     * @param string $icon The icon class
     */
    protected function display_page($template, $title, $icon) {
        // Try both naming conventions for templates
        $template_paths = [
            $this->plugin_path . 'templates/admin/' . $template . '.php',
            $this->plugin_path . 'templates/admin/' . $template . '-page.php'
        ];
        
        $template_found = false;
        foreach ($template_paths as $template_file) {
            if (file_exists($template_file)) {
                include $template_file;
                $template_found = true;
                break;
            }
        }
        
        if (!$template_found) {
            echo '<div class="wrap printify-dashboard-page">';
            echo '<div class="printify-content">';
            echo "<h1><i class='$icon'></i> $title</h1>";
            echo '<div class="error"><p>Template file not found. Tried:<br>';
            echo esc_html(implode('<br>', $template_paths));
            echo '</p></div>';
            echo '</div></div>';
        }
    }
}
=======
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
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
