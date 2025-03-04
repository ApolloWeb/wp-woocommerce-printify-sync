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
     * @return AdminMenu
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
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
            ],
            [
                'title' => 'Shops',
                'slug' => 'printify-shops',
                'callback' => [$this, 'display_shops_page'],
                'template_key' => 'shops'
            ],
            [
                'title' => 'Products',
                'slug' => 'printify-products',
                'callback' => [$this, 'display_products_page'],
                'template_key' => 'products'
            ],
            [
                'title' => 'Orders',
                'slug' => 'printify-orders',
                'callback' => [$this, 'display_orders_page'],
                'template_key' => 'orders'
            ],
            [
                'title' => 'Exchange Rates',
                'slug' => 'printify-exchange-rates',
                'callback' => [$this, 'display_exchange_rates_page'],
                'template_key' => 'exchange-rates'
            ],
            [
                'title' => 'Logs',
                'slug' => 'printify-logs',
                'callback' => [$this, 'display_logs_page'],
                'template_key' => 'logs'
            ],
            [
                'title' => 'Tickets',
                'slug' => 'printify-tickets',
                'callback' => [$this, 'display_tickets_page'],
                'template_key' => 'tickets'
            ],
            [
                'title' => 'Settings',
                'slug' => 'printify-settings',
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
                $page['slug'],
                $page['callback']
            );
        }
        
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
    }

    /**
     * Display the dashboard page
     */
    public function display_dashboard_page() {
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
    }

    /**
     * Display the logs page
     */
    public function display_logs_page() {
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