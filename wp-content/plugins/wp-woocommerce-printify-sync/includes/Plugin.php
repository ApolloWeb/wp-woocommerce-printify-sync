<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

if (!defined('ABSPATH')) {
    exit;
}

// Enable error reporting for debugging
if (WP_DEBUG === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

class Plugin {
    private static $instance = null;

    public function __construct() {
        // Prevent direct instantiation
        
        // Add display hooks
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_notices', [$this, 'displayAdminNotices']);
        add_action('admin_init', [$this, 'initializeAdmin']);
        
        // Initialize services
        new Admin\Menu();
        new Admin\AdminBar();
        new Admin\Settings();
        new Services\ApiService();
        new Services\OrderService();
        new Services\ProductService();
        new Services\EmailService();
        new Services\TicketingService();
        new Services\LoggerService();
        new Services\ShippingService();
        
        // Initialize cron jobs
        new Cron();
    }

    public static function getInstance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        try {
            // Check WordPress environment
            if (!function_exists('is_admin')) {
                throw new \Exception('WordPress environment not loaded');
            }

            if (!class_exists('WooCommerce')) {
                add_action('admin_notices', function() {
                    echo '<div class="error"><p>' . 
                         esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . 
                         '</p></div>';
                });
                return;
            }

            // Initialize components
            $this->loadDependencies();
            $this->defineHooks();
            $this->initializeServices();

            // Add debug notice in WP_DEBUG mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-info"><p>' . 
                         esc_html__('WP WooCommerce Printify Sync Debug Mode Active', 'wp-woocommerce-printify-sync') . 
                         '</p></div>';
                });
            }

        } catch (\Exception $e) {
            // Log error and display admin notice
            error_log('WPWPS Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>' . 
                     esc_html__('WP WooCommerce Printify Sync Error: ', 'wp-woocommerce-printify-sync') . 
                     esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    private function loadDependencies(): void {
        // Load third-party dependencies
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    private function defineHooks(): void {
        // Plugin activation/deactivation hooks
        register_activation_hook(WPWPS_PLUGIN_BASENAME, [$this, 'activate']);
        register_deactivation_hook(WPWPS_PLUGIN_BASENAME, [$this, 'deactivate']);
    }

    private function initializeServices(): void {
        // Initialize core services
        new Admin\Menu();
        new Admin\AdminBar();
        new Admin\Settings();
        new Services\ApiService();
        new Services\OrderService();
        new Services\ProductService();
        new Services\EmailService();
        new Services\TicketingService();
        new Services\LoggerService();
        new Services\ShippingService();
        
        // Initialize cron jobs
        new Cron();
    }

    public function enqueueAdminAssets(): void {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpwps') === false) {
            return;
        }

        // Add nonce for AJAX requests
        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-nonce')
        ]);

        // Enqueue WordPress Media Uploader
        wp_enqueue_media();

        // Enqueue Bootstrap
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            '5.2.3'
        );

        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.2.3',
            true
        );

        // Enqueue Admin CSS and JS
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-admin.js',
            ['jquery', 'bootstrap'],
            WPWPS_VERSION,
            true
        );

        // Only load settings scripts on settings page
        if (strpos($screen->id, 'wpwps-settings') !== false) {
            wp_enqueue_script(
                'wpwps-settings',
                WPWPS_PLUGIN_URL . 'assets/js/wpwps-settings.js',
                ['jquery', 'bootstrap', 'wpwps-admin'],
                WPWPS_VERSION,
                true
            );
        }
    }

    public function activate(): void {
        // Default settings
        $default_settings = [
            'wpwps_api_endpoint' => 'https://api.printify.com/v1',
            'wpwps_max_retries' => 3,
            'wpwps_retry_delay' => 5,
            'wpwps_rate_limit_buffer' => 20,
            'wpwps_openai_max_tokens' => 2000,
            'wpwps_openai_temperature' => 0.7,
            'wpwps_openai_monthly_cap' => 50.00,
            'wpwps_smtp_port' => 587,
            'wpwps_smtp_secure' => 'tls'
        ];

        foreach ($default_settings as $key => $value) {
            add_option($key, $value);
        }

        // Create necessary database tables
        $logger = new Services\LoggerService();
        $logger->createLogTable();

        // Schedule cron jobs
        if (!wp_next_scheduled('wpwps_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'wpwps_cleanup_logs');
        }
    }

    public function deactivate(): void {
        // Remove scheduled cron jobs
        wp_clear_scheduled_hook('wpwps_cleanup_logs');
    }

    public function addMenuPages(): void {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            56
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    public function renderDashboard(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        require_once plugin_dir_path(__FILE__) . '../templates/wpwps-dashboard.blade.php';
    }

    public function renderSettings(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        require_once plugin_dir_path(__FILE__) . '../templates/wpwps-settings.blade.php';
    }
}