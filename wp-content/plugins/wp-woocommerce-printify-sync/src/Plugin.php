<?php
/**
 * Main Plugin Class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\Container;
// ...existing code...

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
        
        // Debug hook
        add_action('admin_notices', function() {
            if (current_user_can('manage_options')) {
                error_log('Plugin constructor called');
            }
        });
    }

    /**
     * Register services in the container.
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
            return new Services\ChatGPTClient($container->make('logger'));
        }, true);
        
        // Email services
        if (class_exists('ApolloWeb\\WPWooCommercePrintifySync\\Email\\Services\\EmailAnalyzer')) {
            $this->container->bind('email_analyzer', function($container) {
                return new Email\Services\EmailAnalyzer(
                    $container->make('chatgpt_client'),
                    $container->make('logger')
                );
            }, true);
        }
        
        // Action scheduler service
        $this->container->bind('action_scheduler', function($container) {
            return new Services\ActionSchedulerService($container->make('logger'));
        }, true);
        
        // Activity service
        $this->container->bind('activity_service', function($container) {
            return new Services\ActivityService($container->make('logger'));
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
        
        // Shipping service
        $this->container->bind('shipping_service', function($container) {
            return new Services\ShippingService(
                $container->make('api_client'),
                $container->make('logger')
            );
        }, true);
    }

    /**
     * Initialize core services.
     */
    public function initializeServices()
    {
        // Debug message
        error_log('initializeServices called');
        
        try {
            // Set up admin pages
            if (is_admin()) {
                // Register Product service if not already registered
                if (!$this->container->has('product_service')) {
                    $this->container->bind('product_service', function($container) {
                        return new Products\ProductSync(
                            $container->make('api_client'),
                            $container->make('logger'),
                            $container->make('action_scheduler'),
                            $container->make('activity_service')
                        );
                    }, true);
                }
                
                // Register Order service if not already registered
                if (!$this->container->has('order_service')) {
                    $this->container->bind('order_service', function($container) {
                        return new Orders\OrderSync(
                            $container->make('api_client'),
                            $container->make('logger'),
                            $container->make('activity_service')
                        );
                    }, true);
                }
                
                // Create AdminMenu with all required dependencies
                $admin_menu = new Admin\AdminMenu(
                    $this->container->make('template_loader'),
                    $this->container->make('logger'),
                    $this->container->has('product_service') ? $this->container->make('product_service') : null,
                    $this->container->has('order_service') ? $this->container->make('order_service') : null,
                    $this->container->make('activity_service'),
                    $this->container->make('action_scheduler')
                );
                
                // Initialize admin menu
                $admin_menu->init();
                
                // Debug log
                error_log('Admin menu initialized with all required services');
            }
            
            // Set up asset manager
            $asset_manager = new Services\AssetManager();
            $asset_manager->init();
            
            // Initialize category markup settings
            if (class_exists('ApolloWeb\\WPWooCommercePrintifySync\\Admin\\CategoryMarkupSettings')) {
                $category_markup_settings = new Admin\CategoryMarkupSettings();
                $category_markup_settings->init();
            }
        } catch (\Throwable $e) {
            // Log detailed error
            error_log('Error initializing services: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }

    // ...existing code...
}
