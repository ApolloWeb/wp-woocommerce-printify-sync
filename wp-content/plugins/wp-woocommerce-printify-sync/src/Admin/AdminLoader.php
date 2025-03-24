<?php
/**
 * Admin Loader
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\Container;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ApiService;
use ApolloWeb\WPWooCommercePrintifySync\Services\OpenAIService;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\DashboardPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\ProductsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\OrdersPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\ShippingPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\TicketsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\LogsPage;

/**
 * Class AdminLoader
 *
 * Handles admin initialization and menu creation
 */
class AdminLoader
{
    /**
     * Service container
     *
     * @var Container
     */
    private Container $container;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Template service
     *
     * @var TemplateService
     */
    private TemplateService $template_service;

    /**
     * Admin pages
     *
     * @var array
     */
    private array $pages = [];

    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        
        // Register template service
        if (!$container->has('template')) {
            $container->register('template', function () use ($container) {
                return new TemplateService($container->get('logger'));
            });
        }
        
        $this->template_service = $container->get('template');
    }

    /**
     * Initialize admin functionality
     *
     * @return void
     */
    public function init(): void
    {
        // Register admin pages
        $this->registerPages();
        
        // Add admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register Ajax endpoints 
        add_action('wp_ajax_wpwps_test_printify_api', [$this, 'ajaxTestPrintifyAPI']);
        add_action('wp_ajax_wpwps_test_openai', [$this, 'ajaxTestOpenAI']); // Changed action name
        
        // Add shop info to admin header
        add_action('admin_notices', [$this, 'displayShopInfo']);
    }

    /**
     * Register admin pages
     *
     * @return void
     */
    private function registerPages(): void
    {
        $this->pages['dashboard'] = new DashboardPage($this->container);
        $this->pages['settings'] = new SettingsPage($this->container);
        $this->pages['products'] = new ProductsPage($this->container);
        $this->pages['orders'] = new OrdersPage($this->container);
        $this->pages['shipping'] = new ShippingPage($this->container);
        $this->pages['tickets'] = new TicketsPage($this->container);
        $this->pages['logs'] = new LogsPage($this->container);
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function addAdminMenu(): void
    {
        // Primary menu with custom FA icon
        $icon_url = 'data:image/svg+xml;base64,' . base64_encode('<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M631.2 96.5L436.5 0C416.4 27.8 371.9 47.2 320 47.2S223.6 27.8 203.5 0L8.8 96.5c-7.9 4-11.1 13.6-7.2 21.5l57.2 114.5c4 7.9 13.6 11.1 21.5 7.2l56.6-27.7c10.6-5.2 23 2.5 23 14.4V480c0 17.7 14.3 32 32 32h256c17.7 0 32-14.3 32-32V226.3c0-11.8 12.4-19.6 23-14.4l56.6 27.7c7.9 4 17.5.8 21.5-7.2L638.3 118c4-7.9.8-17.6-7.1-21.5z"/></svg>');

        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this->pages['dashboard'], 'render'],
            $icon_url,
            58
        );

        // Submenus in desired order
        $submenus = [
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-dashboard',
                'callback' => [$this->pages['dashboard'], 'render']
            ],
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('Products', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('Products', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-products',
                'callback' => [$this->pages['products'], 'render']
            ],
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('Orders', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('Orders', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-orders',
                'callback' => [$this->pages['orders'], 'render']
            ],
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('Shipping', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('Shipping', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-shipping',
                'callback' => [$this->pages['shipping'], 'render']
            ],
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-tickets',
                'callback' => [$this->pages['tickets'], 'render']
            ],
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('System Logs', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('System Logs', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-logs',
                'callback' => [$this->pages['logs'], 'render']
            ],
            [
                'parent' => 'wpwps-dashboard',
                'page_title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'menu_title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'wpwps-settings',
                'callback' => [$this->pages['settings'], 'render']
            ]
        ];

        foreach ($submenus as $submenu) {
            add_submenu_page(
                $submenu['parent'],
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['menu_slug'],
                $submenu['callback']
            );
        }
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook_suffix The current admin page hook suffix
     * @return void 
     */
    public function enqueueAssets(string $hook_suffix): void
    {
        // Only load on plugin pages
        if (strpos($hook_suffix, 'wpwps-') === false) {
            return;
        }

        // Register styles
        wp_register_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
        wp_register_style('wpwps-admin-core', WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin-core.css', ['wpwps-fontawesome'], WPWPS_VERSION);

        // Register scripts
        wp_register_script('wpwps-common', WPWPS_PLUGIN_URL . 'assets/js/wpwps-common.js', ['jquery'], WPWPS_VERSION, true);

        // Load core assets
        wp_enqueue_style('wpwps-admin-core');
        wp_enqueue_script('wpwps-common');

        // Load Bootstrap only on settings page
        if (strpos($hook_suffix, 'settings') !== false) {
            wp_enqueue_style('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
            wp_enqueue_script('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', [], '5.3.0', true);
        }

        // Load Chart.js only on dashboard
        if (strpos($hook_suffix, 'dashboard') !== false) {
            wp_enqueue_script('wpwps-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js', [], '4.3.0', true);
        }

        // Load page specific assets
        $page = str_replace('wpwps-', '', $hook_suffix);
        $this->loadPageAssets($page);

        // Add admin data
        $this->localizeAdminData();
    }

    /**
     * Localize admin data
     */
    private function localizeAdminData(): void
    {
        wp_localize_script('wpwps-common', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin-ajax-nonce'),
            'strings' => [
                'error' => __('Error', 'wp-woocommerce-printify-sync'),
                'success' => __('Success', 'wp-woocommerce-printify-sync'),
                'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                'cancel' => __('Cancel', 'wp-woocommerce-printify-sync'),
                'ok' => __('OK', 'wp-woocommerce-printify-sync'),
                'saving' => __('Saving...', 'wp-woocommerce-printify-sync'),
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
            ],
        ]);
    }

    /**
     * Display shop info in admin header
     *
     * @return void
     */
    public function displayShopInfo(): void
    {
        $screen = get_current_screen();
        
        // Only display on our plugin pages
        if (!$screen || strpos($screen->id, 'wpwps-') === false) {
            return;
        }
        
        $shop_id = get_option('wpwps_shop_id', '');
        $shop_name = get_option('wpwps_shop_name', '');
        
        if (!empty($shop_id) && !empty($shop_name)) {
            echo '<div class="wpwps-shop-info notice notice-info is-dismissible">';
            echo '<p><strong>' . esc_html__('Connected Printify Shop:', 'wp-woocommerce-printify-sync') . '</strong> ';
            echo esc_html($shop_name) . ' (ID: ' . esc_html($shop_id) . ')</p>';
            echo '</div>';
        }
    }

    /**
     * AJAX handler for testing Printify API
     *
     * @return void
     */
    public function ajaxTestPrintifyAPI(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Test connection
        $api_service = new ApiService($this->logger);
        $api_service->setApiKey($api_key);
        
        $result = $api_service->testConnection();
        
        if ($result['success']) {
            // Get shops
            $shops = $result['data'] ?? [];
            
            // Format shops for dropdown
            $formatted_shops = [];
            foreach ($shops as $shop) {
                if (isset($shop['id'], $shop['title'])) {
                    $formatted_shops[] = [
                        'id' => $shop['id'],
                        'name' => $shop['title'],
                    ];
                }
            }
            
            wp_send_json_success([
                'message' => $result['message'],
                'shops' => $formatted_shops,
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
    }

    /**
     * AJAX handler for testing OpenAI API
     *
     * @return void
     */
    public function ajaxTestOpenAI(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get API settings
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-3.5-turbo';
        $temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0.7;

        // Save the settings
        $api_service = new ApiService($this->logger);
        if (!empty($api_key)) {
            update_option('wpwps_openai_api_key', $api_service->encrypt($api_key));
        }
        update_option('wpwps_openai_model', $model);
        update_option('wpwps_openai_temperature', $temperature);
        
        // Test connection
        $openai_service = new OpenAIService($this->logger);
        $result = $openai_service->testConnection();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message']
            ]);
        }
    }
}
