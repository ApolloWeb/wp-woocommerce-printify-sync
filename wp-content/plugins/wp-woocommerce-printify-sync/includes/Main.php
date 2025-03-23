<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

// ...existing use statements...
use ApolloWeb\WPWooCommercePrintifySync\Admin\DashboardWidgets;
use ApolloWeb\WPWooCommercePrintifySync\Api\ApiRateLimiter;
use  ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\StockSyncService;

/**
 * Main plugin class
 */
class Main {
    // ...existing code...
    
    /**
     * @var DashboardWidgets
     */
    private $dashboard_widgets;
    
    /**
     * @var ApiRateLimiter
     */
    private $api_rate_limiter;
    
    /**
     * @var StockSyncService
     */
    private $stock_sync_service;
    
    /**
     * Initialize plugin
     */
    public function init(): void {
        // Initialize core components
        $this->services['logger'] = new Logger();
        $this->services['settings'] = new Settings();
        $this->services['hpos_compat'] = new HPOSCompat();
        
        // Initialize API rate limiter
        $this->services['api_rate_limiter'] = new ApiRateLimiter(
            $this->services['logger'], 
            $this->services['settings']
        );
        $this->services['api_rate_limiter']->init();
        
        // Initialize API client with rate limiter
        $this->services['api'] = new PrintifyApiClient(
            $this->services['settings'],
            $this->services['logger'],
            $this->services['api_rate_limiter']
        );
        
        // Initialize repositories
        $this->services['order_repository'] = new OrderRepository();
        $this->services['product_repository'] = new ProductRepository();
        $this->services['shipping_repository'] = new ShippingRepository();
        
        // Initialize email queue if support is enabled
        if ($this->settings->get('enable_email_support', 'no') === 'yes') {
            $this->services['email_queue'] = new EmailQueue(
                $this->services['logger'],
                $this->services['settings']
            );
            $this->services['email_queue']->init();
        }
        
        // Initialize services
        $this->services['order_sync'] = new OrderSyncService(
            $this->services['api'],
            $this->services['logger'],
            $this->services['order_repository'],
            $this->services['product_repository']
        );
        
        $this->services['shipping_profile'] = new ShippingProfileService(
            $this->services['api'],
            $this->services['logger'],
            $this->services['shipping_repository']
        );
        
        $this->services['stock_sync'] = new StockSyncService(
            $this->services['api'],
            $this->services['logger'],
            $this->services['settings'],
            $this->services['product_repository']
        );
        $this->services['stock_sync']->init();
        
        $this->services['wc_hooks'] = new WooCommerceHooks(
            $this->services['api'],
            $this->services['logger'],
            $this->services['order_sync'],
            $this->services['product_repository']
        );
        
        // Initialize webhook handler
        $this->services['webhook_handler'] = new WebhookHandler(
            $this->services['logger'],
            $this->services['order_sync'],
            $this->services['product_repository']
        );
        
        // Initialize controllers
        $this->services['webhook_controller'] = new WebhookController($this->services['logger']);
        
        // Initialize HPOS compatibility
        $this->services['hpos_compat']->init();
        
        // Initialize hooks
        $this->services['wc_hooks']->init();
        
        // Initialize admin functionality
        if (is_admin()) {
            $this->initAdminFunctionality();
        }
        
        // Register plugin hooks
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
        
        // Register shipping sync action
        add_action('wpwps_sync_shipping_profiles', [$this->services['shipping_profile'], 'syncShippingProfiles']);
    }
    
    /**
     * Initialize admin functionality
     */
    private function initAdminFunctionality(): void {
        $this->services['admin_menu'] = new AdminMenu();
        $this->services['admin_menu']->init();
        
        $this->services['admin_assets'] = new AdminAssets();
        $this->services['admin_assets']->init();
        
        // Initialize dashboard widgets
        $this->services['dashboard_widgets'] = new DashboardWidgets(
            $this->services['logger'],
            $this->services['settings'],
            isset($this->services['email_queue']) ? $this->services['email_queue'] : null
        );
        $this->services['dashboard_widgets']->init();
    }
    
    private function initServices(): void {
        // ...existing service initialization...
        
        // Initialize stock sync service
        $this->services['stock_sync'] = new StockSyncService(
            $this->services['api'],
            $this->services['logger'],
            $this->services['settings'],
            $this->services['product_repository']
        );
        $this->services['stock_sync']->init();
        
        // Initialize template system
        $this->services['template'] = new Template();
        
        // ...existing code...
    }
    
    // ...existing code...
}
