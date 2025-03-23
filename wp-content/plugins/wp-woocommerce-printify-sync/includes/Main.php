<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminAssets;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Controllers\WebhookController;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompat;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\OrderRepository;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\ProductRepository;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\ShippingRepository;
use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ShippingProfileService;
use ApolloWeb\WPWooCommercePrintifySync\Services\WooCommerceHooks;
use ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookHandler;

/**
 * Main plugin class
 */
class Main {
    private $services = [];
    
    /**
     * Initialize plugin
     */
    public function init(): void {
        // Initialize core components
        $this->services['logger'] = new Logger();
        $this->services['settings'] = new Settings();
        $this->services['hpos_compat'] = new HPOSCompat();
        
        // Initialize API client
        $this->services['api'] = new PrintifyApiClient(
            $this->services['settings'],
            $this->services['logger']
        );
        
        // Initialize repositories
        $this->services['order_repository'] = new OrderRepository();
        $this->services['product_repository'] = new ProductRepository();
        $this->services['shipping_repository'] = new ShippingRepository();
        
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
    }
    
    /**
     * Load plugin textdomain
     */
    public function loadTextdomain(): void {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(plugin_basename(WPPS_FILE)) . '/languages'
        );
    }
    
    /**
     * Get a service instance
     *
     * @param string $service Service name
     * @return mixed Service instance or null if not found
     */
    public function getService(string $service) {
        return $this->services[$service] ?? null;
    }
}
