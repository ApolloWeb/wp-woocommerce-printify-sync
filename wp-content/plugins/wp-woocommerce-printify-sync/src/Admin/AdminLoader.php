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
        add_action('wp_ajax_wpwps_test_openai_api', [$this, 'ajaxTestOpenAIAPI']);
        
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
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function addAdminMenu(): void
    {
        // Primary menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this->pages['dashboard'], 'render'],
            'dashicons-admin-generic', // We'll replace this with custom icon
            58
        );
        
        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this->pages['dashboard'], 'render']
        );
        
        // Products submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this->pages['products'], 'render']
        );
        
        // Orders submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this->pages['orders'], 'render']
        );
        
        // Shipping submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-shipping',
            [$this->pages['shipping'], 'render']
        );
        
        // Support tickets submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-tickets',
            [$this->pages['tickets'], 'render']
        );
        
        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this->pages['settings'], 'render']
        );
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

        // Register common assets
        $this->registerCommonAssets();
        
        // Load page specific assets
        $this->loadPageAssets($hook_suffix);
        
        // Add admin data
        $this->localizeAdminData();
    }

    /**
     * Register common CSS/JS assets
     */
    private function registerCommonAssets(): void
    {
        wp_register_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        wp_register_style('wpwps-common', WPWPS_PLUGIN_URL . 'assets/css/wpwps-common.css', ['wpwps-fontawesome'], WPWPS_VERSION);
        wp_register_script('wpwps-common', WPWPS_PLUGIN_URL . 'assets/js/wpwps-common.js', ['jquery'], WPWPS_VERSION, true);
    }

    /**
     * Load page specific assets
     */
    private function loadPageAssets(string $hook_suffix): void 
    {
        $page = str_replace('wpwps-', '', $hook_suffix);
        
        if (file_exists(WPWPS_PLUGIN_DIR . "assets/css/wpwps-{$page}.css")) {
            wp_enqueue_style("wpwps-{$page}", WPWPS_PLUGIN_URL . "assets/css/wpwps-{$page}.css", ['wpwps-common'], WPWPS_VERSION);
        }

        if (file_exists(WPWPS_PLUGIN_DIR . "assets/js/wpwps-{$page}.js")) {
            wp_enqueue_script("wpwps-{$page}", WPWPS_PLUGIN_URL . "assets/js/wpwps-{$page}.js", ['wpwps-common'], WPWPS_VERSION, true);
        }
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
    public function ajaxTestOpenAIAPI(): void
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
        
        // Save API key (encrypted)
        $api_service = new ApiService($this->logger);
        $encrypted_key = $api_service->encrypt($api_key);
        update_option('wpwps_openai_api_key', $encrypted_key);
        
        // Test connection
        $openai_service = new OpenAIService($this->logger);
        $result = $openai_service->testConnection();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'response' => $result['data']['response'] ?? '',
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
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-3.5-turbo';
        $temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0.7;
        
        if (empty($api_key)) {
            // Try to use the stored API key
            $encrypted_key = get_option('wpwps_openai_api_key', '');
            if (empty($encrypted_key)) {
                wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
                return;
            }
        } else {
            // Encrypt and store the API key
            $api_service = new ApiService($this->logger);
            $encrypted_key = $api_service->encrypt($api_key);
            update_option('wpwps_openai_api_key', $encrypted_key);
            
            // Also update model and temperature if provided
            if (!empty($model)) {
                update_option('wpwps_openai_model', $model);
            }
            
            update_option('wpwps_openai_temperature', $temperature);
        }
        
        // Test connection
        $openai_service = new OpenAIService($this->logger);
        $result = $openai_service->testConnection();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'] ?? [],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
    }
}
