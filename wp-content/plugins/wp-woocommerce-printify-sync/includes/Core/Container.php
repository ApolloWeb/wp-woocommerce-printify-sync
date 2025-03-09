<?php
/**
 * Dependency Injection Container
 *
 * Manages class dependencies and service instantiation.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Api\ApiManager;
use ApolloWeb\WPWooCommercePrintifySync\Api\ApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyClient;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminPages;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Handlers\WebhookHandler;
use ApolloWeb\WPWooCommercePrintifySync\Handlers\CronHandler;
use ApolloWeb\WPWooCommercePrintifySync\Handlers\AjaxHandler;
use ApolloWeb\WPWooCommercePrintifySync\Services\ProductService;
use ApolloWeb\WPWooCommercePrintifySync\Services\OrderService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ImageService;
use ApolloWeb\WPWooCommercePrintifySync\Utils\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Utils\CacheManager;
use ApolloWeb\WPWooCommercePrintifySync\Utils\Enqueuer;
use ApolloWeb\WPWooCommercePrintifySync\View\ViewManager;

/**
 * Container Class
 */
class Container {
    /**
     * The container instances
     *
     * @var array
     */
    private $instances = [];

    /**
     * Bootstrap the plugin
     *
     * @return void
     */
    public function bootstrap() {
        // Initialize Logger first
        $this->get('logger');
        
        // Register services
        $this->registerServices();
        
        // Initialize core components
        $this->initializeComponents();
        
        // Log initialization
        $this->get('logger')->log('Plugin initialized', 'info');
    }

    /**
     * Register services with the container
     *
     * @return void
     */
    private function registerServices() {
        // Register core services
        $this->bind('logger', function() {
            return new Logger();
        });
        
        $this->bind('cache', function() {
            return new CacheManager();
        });
        
        $this->bind('enqueuer', function() {
            return new Enqueuer();
        });
        
        $this->bind('view', function() {
            return new ViewManager();
        });
        
        // Register API services
        $this->bind('api.client', function() {
            return new PrintifyClient();
        });
        
        $this->bind('api.manager', function() {
            return new ApiManager($this->get('api.client'), $this->get('cache'));
        });
        
        // Register handlers
        $this->bind('handler.webhook', function() {
            return new WebhookHandler(
                $this->get('api.manager'), 
                $this->get('service.order'),
                $this->get('service.product'),
                $this->get('logger')
            );
        });
        
        $this->bind('handler.cron', function() {
            return new CronHandler(
                $this->get('service.product'),
                $this->get('service.order'),
                $this->get('logger')
            );
        });
        
        $this->bind('handler.ajax', function() {
            return new AjaxHandler(
                $this->get('service.product'),
                $this->get('service.order'),
                $this->get('api.manager'),
                $this->get('logger')
            );
        });
        
        // Register services
        $this->bind('service.product', function() {
            return new ProductService(
                $this->get('api.manager'),
                $this->get('service.image'),
                $this->get('logger'),
                $this->get('cache')
            );
        });
        
        $this->bind('service.order', function() {
            return new OrderService(
                $this->get('api.manager'),
                $this->get('logger')
            );
        });
        
        $this->bind('service.image', function() {
            return new ImageService($this->get('logger'));
        });
        
        // Register admin components
        $this->bind('admin.pages', function() {
            return new AdminPages(
                $this->get('api.manager'), 
                $this->get('service.product'),
                $this->get('view')
            );
        });
        
        $this->bind('admin.settings', function() {
            return new Settings($this->get('view'));
        });
    }

    /**
     * Initialize core components
     *
     * @return void
     */
    private function initializeComponents() {
        // Load assets
        $this->get('enqueuer')->register();
        
        // Initialize admin pages if in admin
        if (is_admin()) {
            $this->get('admin.pages')->init();
            $this->get('admin.settings')->init();
        }
        
        // Initialize webhook handler
        $this->get('handler.webhook')->register();
        
        // Initialize cron handler
        $this->get('handler.cron')->register();
        
        // Initialize ajax handler
        $this->get('handler.ajax')->register();
    }

    /**
     * Bind a service to the container
     *
     * @param string   $key     Service key
     * @param callable $factory Factory function
     * @return void
     */
    public function bind($key, callable $factory) {
        $this->instances[$key] = $factory;
    }

    /**
     * Get a service from the container
     *
     * @param string $key Service key
     * @return mixed
     * @throws \Exception If service not found
     */
    public function get($key) {
        if (!isset($this->instances[$key])) {
            throw new \Exception("No {$key} service is registered in the container");
        }

        // Lazily create the instance if it's a factory
        if (is_callable($this->instances[$key])) {
            $this->instances[$key] = $this->instances[$key]();
        }

        return $this->instances[$key];
    }

    /**
     * Check if a service exists in the container
     *
     * @param string $key Service key
     * @return bool
     */
    public function has($key) {
        return isset($this->instances[$key]);
    }
}