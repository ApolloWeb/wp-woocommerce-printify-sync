<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\Container;
use ApolloWeb\WPWooCommercePrintifySync\Database\Setup;

class Plugin {
    /**
     * Service container.
     *
     * @var Container
     */
    private $container;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->container = new Container();
    }

    /**
     * Initialize the plugin.
     */
    public function init() {
        $this->initializeHooks();
        $this->registerServices();
        $this->setupContainer();
        
        // Set global container for use in webhook handlers and other contexts
        global $wpwps_container;
        $wpwps_container = $this->container;
        
        // Initialize core services
        $this->initializeServices();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function initializeHooks() {
        add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
        
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WPWPS_PLUGIN_FILE, true);
            }
        });
    }

    /**
     * Register plugin assets.
     */
    public function registerAssets() {
        // Register email-related CSS
        wp_register_style(
            'wpwps-email-settings',
            WPWPS_PLUGIN_URL . 'assets/css/email-settings.css',
            [],
            WPWPS_VERSION
        );
        
        // Register email-related JS
        wp_register_script(
            'wpwps-email-testing',
            WPWPS_PLUGIN_URL . 'assets/js/email-testing.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
    }

    /**
     * Register services in the container.
     * 
     * This uses our updated Container method binding syntax
     */
    private function registerServices() {
        // Core services
        $this->container->bind('logger', function() {
            return new Services\Logger();
        }, true);
        
        $this->container->bind('template_loader', function($container) {
            return new Services\TemplateLoader($container->make('logger'));
        }, true);
        
        // ChatGPT services
        $this->container->bind('chatgpt_client', function($container) {
            return new API\ChatGPTClient(
                $container->make('logger'),
                new Services\EncryptionService()
            );
        }, true);
        
        // Email services
        $this->container->bind('email_analyzer', function($container) {
            return new Email\Services\EmailAnalyzer(
                $container->make('chatgpt_client'),
                $container->make('logger')
            );
        }, true);
        
        $this->container->bind('queue_manager', function($container) {
            return new Email\Services\QueueManager($container->make('logger'));
        }, true);
        
        $this->container->bind('smtp_service', function($container) {
            return new Email\Services\SMTPService($container->make('logger'));
        }, true);
        
        $this->container->bind('pop3_service', function($container) {
            return new Email\Services\POP3Service(
                $container->make('logger'),
                $container->make('queue_manager'),
                $container->make('email_analyzer')
            );
        }, true);
        
        // Order services
        $this->container->bind('id_mapper', function() {
            return new Services\IDMapper();
        }, true);
        
        $this->container->bind('order_analyzer', function($container) {
            return new Orders\OrderAnalyzer(
                $container->make('chatgpt_client'),
                $container->make('logger')
            );
        }, true);
        
        // Action scheduler service
        $this->container->bind('action_scheduler', function($container) {
            return new Services\ActionSchedulerService($container->make('logger'));
        }, true);
        
        // Activity service
        $this->container->bind('activity_service', function($container) {
            return new Services\ActivityService($container->make('logger'));
        }, true);
        
        // Product sync service
        $this->container->bind('product_sync', function($container) {
            return new Products\ProductSync(
                $container->make('api_client'),
                $container->make('logger'),
                $container->make('action_scheduler'),
                $container->make('activity_service')
            );
        }, true);
    }

    /**
     * Setup container with all required services.
     */
    private function setupContainer()
    {
        // API client service
        $this->container->bind('api_client', function($container) {
            return new API\PrintifyAPIClient(
                $container->make('logger'),
                new Services\EncryptionService(),
                new Services\Cache()
            );
        }, true);
        
        // Order sync service
        $this->container->bind('order_sync', function($container) {
            return new Orders\OrderSync(
                $container->make('api_client'),
                $container->make('logger'),
                $container->make('activity_service')
            );
        }, true);
        
        // Webhook handler
        $this->container->bind('webhook_handler', function($container) {
            return new Webhooks\WebhookHandler(
                $container->make('logger'),
                $container->make('product_sync'),
                $container->make('order_sync')
            );
        }, true);
        
        // Data formatter
        $this->container->bind('data_formatter', function() {
            return new Services\PrintifyDataFormatter();
        }, true);
    }
    
    /**
     * Initialize core services.
     */
    private function initializeServices()
    {
        // Set up admin pages
        if (is_admin()) {
            $admin_menu = new Admin\AdminMenu(
                $this->container->make('template_loader'),
                $this->container->make('logger')
            );
            $admin_menu->init();
            
            // Initialize API controller
            $api_controller = new API\APIController(
                $this->container->make('api_client'),
                $this->container->make('chatgpt_client'),
                $this->container->make('logger'),
                new Services\EncryptionService()
            );
            $api_controller->init();
            
            // Initialize diagnostics page
            $diagnostics_page = new Admin\DiagnosticsPage(
                $this->container->make('logger')
            );
            $diagnostics_page->init();
        }
        
        // Set up asset manager
        $asset_manager = new Services\AssetManager();
        $asset_manager->init();
        
        // Initialize category markup settings
        $category_markup_settings = new Admin\CategoryMarkupSettings();
        $category_markup_settings->init();
    }

    /**
     * Activation hook callback.
     */
    public function activate() {
        // Create database tables
        $db_setup = new Setup();
        $db_setup->createTables();
        
        // Schedule cron events
        $this->scheduleCronEvents();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook callback.
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wpwps_poll_emails');
        wp_clear_scheduled_hook('wpwps_process_email_queue');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Schedule cron events.
     */
    private function scheduleCronEvents() {
        if (!wp_next_scheduled('wpwps_poll_emails')) {
            wp_schedule_event(time(), 'every_five_minutes', 'wpwps_poll_emails');
        }
        
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'every_five_minutes', 'wpwps_process_email_queue');
        }
    }
}
