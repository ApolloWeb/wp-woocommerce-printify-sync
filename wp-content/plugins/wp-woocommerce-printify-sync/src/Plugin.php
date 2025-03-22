<?php
/**
 * Main Plugin Class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\Container;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateLoader;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;
use ApolloWeb\WPWooCommercePrintifySync\Services\Cache;
use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\API\ChatGPTClient;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;
use ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookHandler;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyDataFormatter;
use ApolloWeb\WPWooCommercePrintifySync\Database\Setup;
use Exception;

/**
 * Main plugin class.
 */
class Plugin
{
    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    private static $instance;

    /**
     * Service container.
     *
     * @var Container
     */
    protected $container;

    /**
     * Get plugin instance.
     *
     * @return Plugin Plugin instance.
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the service container.
     *
     * @return Container The container instance.
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        // Initialize Container
        $this->container = new Container();
        
        // Register core services
        $this->registerServices();
        
        // Setup container bindings
        $this->setupContainer();
        
        // Declare WooCommerce compatibility
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
     */
    private function registerServices() {
        // Core services
        $this->container->bind('logger', function() {
            return new Logger();
        }, true);
        
        $this->container->bind('template_loader', function($container) {
            return new TemplateLoader($container->make('logger'));
        }, true);
        
        // ChatGPT services
        $this->container->bind('chatgpt_client', function($container) {
            return new ChatGPTClient(
                $container->make('logger'),
                new EncryptionService()
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
            return new ActionSchedulerService($container->make('logger'));
        }, true);
        
        // Activity service
        $this->container->bind('activity_service', function($container) {
            return new ActivityService($container->make('logger'));
        }, true);
        
        // Product sync service
        $this->container->bind('product_sync', function($container) {
            return new ProductSync(
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
            return new PrintifyAPIClient(
                $container->make('logger'),
                new EncryptionService(),
                new Cache()
            );
        }, true);
        
        // Order sync service
        $this->container->bind('order_sync', function($container) {
            return new OrderSync(
                $container->make('api_client'),
                $container->make('logger'),
                $container->make('activity_service')
            );
        }, true);
        
        // Webhook handler
        $this->container->bind('webhook_handler', function($container) {
            return new WebhookHandler(
                $container->make('logger'),
                $container->make('product_sync'),
                $container->make('order_sync')
            );
        }, true);
        
        // Data formatter
        $this->container->bind('data_formatter', function() {
            return new PrintifyDataFormatter();
        }, true);
    }
    
    /**
     * Initialize core services.
     */
    public function initializeServices()
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
                new EncryptionService()
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
