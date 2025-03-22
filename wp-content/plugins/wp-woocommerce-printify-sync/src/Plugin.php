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
     */
    private function registerServices() {
        // Core services
        $this->container['logger'] = function() {
            return new Services\Logger();
        };
        
        $this->container['template_loader'] = function() {
            return new Services\TemplateLoader($this->container['logger']);
        };
        
        // ChatGPT services
        $this->container['chatgpt_client'] = function() {
            return new Services\ChatGPTClient($this->container['logger']);
        };
        
        // Email services
        $this->container['email_analyzer'] = function() {
            return new Email\Services\EmailAnalyzer(
                $this->container['chatgpt_client'],
                $this->container['logger']
            );
        };
        
        $this->container['queue_manager'] = function() {
            return new Email\Services\QueueManager($this->container['logger']);
        };
        
        $this->container['smtp_service'] = function() {
            return new Email\Services\SMTPService($this->container['logger']);
        };
        
        $this->container['pop3_service'] = function() {
            return new Email\Services\POP3Service(
                $this->container['logger'],
                $this->container['queue_manager'],
                $this->container['email_analyzer']
            );
        };
        
        // Order services
        $this->container['id_mapper'] = function() {
            return new Services\IDMapper();
        };
        
        $this->container['order_analyzer'] = function() {
            return new Orders\OrderAnalyzer(
                $this->container['chatgpt_client'],
                $this->container['logger']
            );
        };

        // Webhook services
        $this->container['webhook_handler'] = function() {
            return new Webhooks\WebhookHandler(
                $this->container['logger'],
                $this->container['product_sync'],
                $this->container['order_sync']
            );
        };

        // Initialize services
        $this->initializeServices();
    }

    /**
     * Setup container with all required services.
     */
    private function setupContainer()
    {
        // Logger service
        $this->container->bind('logger', function() {
            return new Services\Logger();
        }, true);
        
        // API client service
        $this->container->bind('api_client', function($container) {
            return new API\PrintifyAPIClient(
                $container->make('logger'),
                new Services\EncryptionService(),
                new Services\Cache()
            );
        }, true);
        
        // Template service
        $this->container->bind('template_loader', function($container) {
            return new Services\TemplateLoader($container->make('logger'));
        }, true);
        
        // Action scheduler service
        $this->container->bind('action_scheduler', function($container) {
            return new Services\ActionSchedulerService($container->make('logger'));
        }, true);
        
        // Activity service
        $this->container->bind('activity_service', function() {
            return new Services\ActivityService();
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
                $this->container->make('logger'),
                $this->container->make('api_client'),
                $this->container->make('product_sync'),
                $this->container->make('order_sync')
            );
            $api_controller->init();
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
