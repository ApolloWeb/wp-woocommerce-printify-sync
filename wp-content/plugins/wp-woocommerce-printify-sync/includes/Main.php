<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\DashboardWidgets;
use ApolloWeb\WPWooCommercePrintifySync\Api\ApiRateLimiter;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\StockSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

/**
 * Main plugin class
 */
class Main {
    private $container;
    
    public function __construct() {
        $this->container = new ServiceContainer();
    }
    
    public function init(): void {
        $this->registerServices();
        $this->initializeCore();
        $this->initializeOptional();
        
        if (is_admin()) {
            $this->initAdminFunctionality();
        }
        
        // Register health check
        add_filter('debug_information', [$this, 'addHealthCheckData']);
        add_filter('site_status_tests', [$this, 'addHealthTests']);
    }
    
    private function registerServices(): void {
        // Core services
        $this->container->register('logger', fn() => new Logger());
        $this->container->register('settings', fn() => new Settings());
        
        // API services
        $this->container->register('api_rate_limiter', function() {
            $limiter = new ApiRateLimiter(
                $this->container->get('logger'),
                $this->container->get('settings')
            );
            $limiter->init();
            return $limiter;
        });
        
        // Initialize API client with rate limiter
        $this->container->register('api', function() {
            return new PrintifyApiClient(
                $this->container->get('settings'),
                $this->container->get('logger'),
                $this->container->get('api_rate_limiter')
            );
        });
        
        // Initialize repositories
        $this->container->register('order_repository', fn() => new OrderRepository());
        $this->container->register('product_repository', fn() => new ProductRepository());
        $this->container->register('shipping_repository', fn() => new ShippingRepository());
        
        // Initialize email queue if support is enabled
        $this->container->register('email_queue', function() {
            $queue = new EmailQueue(
                $this->container->get('logger'),
                $this->container->get('settings')
            );
            $queue->init();
            return $queue;
        });
        
        // Initialize services
        $this->container->register('order_sync', function() {
            return new OrderSyncService(
                $this->container->get('api'),
                $this->container->get('logger'),
                $this->container->get('order_repository'),
                $this->container->get('product_repository')
            );
        });
        
        $this->container->register('shipping_profile', function() {
            return new ShippingProfileService(
                $this->container->get('api'),
                $this->container->get('logger'),
                $this->container->get('shipping_repository')
            );
        });
        
        $this->container->register('stock_sync', function() {
            $sync = new StockSyncService(
                $this->container->get('api'),
                $this->container->get('logger'),
                $this->container->get('settings'),
                $this->container->get('product_repository')
            );
            $sync->init();
            return $sync;
        });
        
        $this->container->register('wc_hooks', function() {
            return new WooCommerceHooks(
                $this->container->get('api'),
                $this->container->get('logger'),
                $this->container->get('order_sync'),
                $this->container->get('product_repository')
            );
        });
        
        // Initialize webhook handler
        $this->container->register('webhook_handler', function() {
            return new WebhookHandler(
                $this->container->get('logger'),
                $this->container->get('order_sync'),
                $this->container->get('product_repository')
            );
        });
        
        // Initialize controllers
        $this->container->register('webhook_controller', fn() => new WebhookController($this->container->get('logger')));
        
        // Initialize HPOS compatibility
        $this->container->register('hpos_compat', function() {
            $compat = new HPOSCompat();
            $compat->init();
            return $compat;
        });
        
        // Initialize hooks
        $this->container->register('wc_hooks', function() {
            $hooks = new WooCommerceHooks(
                $this->container->get('api'),
                $this->container->get('logger'),
                $this->container->get('order_sync'),
                $this->container->get('product_repository')
            );
            $hooks->init();
            return $hooks;
        });
        
        // Register shipping sync action
        add_action('wpwps_sync_shipping_profiles', [$this->container->get('shipping_profile'), 'syncShippingProfiles']);
    }
    
    private function initializeCore(): void {
        // Initialize only essential services
        $this->container->get('logger');
        $this->container->get('settings');
        $this->container->get('api_rate_limiter');
        $this->container->get('api');
    }
    
    private function initializeOptional(): void {
        // Lazy load optional services
        if ($this->container->get('settings')->get('enable_email_support', 'no') === 'yes') {
            $this->container->get('email_queue')->init();
        }
        
        if ($this->container->get('settings')->get('enable_stock_sync', 'yes') === 'yes') {
            $this->container->get('stock_sync')->init();
        }
    }
    
    public function addHealthCheckData($info): array {
        $info['wpwps'] = [
            'label' => __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'fields' => $this->getHealthCheckFields()
        ];
        
        return $info;
    }
    
    private function getHealthCheckFields(): array {
        $status = $this->container->getStatus();
        $fields = [];
        
        foreach ($status as $service => $data) {
            $fields[$service] = [
                'label' => sprintf(__('%s Status', 'wp-woocommerce-printify-sync'), ucfirst($service)),
                'value' => isset($data['initialized']) ? __('Active', 'wp-woocommerce-printify-sync') : __('Inactive', 'wp-woocommerce-printify-sync')
            ];
        }
        
        return $fields;
    }
    
    private function initAdminFunctionality(): void {
        $this->container->register('admin_menu', function() {
            $menu = new AdminMenu();
            $menu->init();
            return $menu;
        });
        
        $this->container->register('admin_assets', function() {
            $assets = new AdminAssets();
            $assets->init();
            return $assets;
        });
        
        // Initialize dashboard widgets
        $this->container->register('dashboard_widgets', function() {
            $widgets = new DashboardWidgets(
                $this->container->get('logger'),
                $this->container->get('settings'),
                $this->container->get('email_queue')
            );
            $widgets->init();
            return $widgets;
        });
    }
}
