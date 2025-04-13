<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSyncServiceInterface;
use ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsServiceInterface;

/**
 * Main Plugin Class
 */
class Plugin {
    /**
     * @var ServiceContainer
     */
    private $container;
    
    /**
     * Constructor
     *
     * @param ServiceContainer $container
     */
    public function __construct(ServiceContainer $container) {
        $this->container = $container;
        $this->registerServices();
    }
    
    /**
     * Register services with the container
     */
    private function registerServices() {
        // Register API service
        $this->container->register('api', function($container) {
            $settings = $container->get('settings');
            $printify_settings = $settings->getPrintifySettings();
            
            return new \ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi(
                $printify_settings['api_key'],
                $printify_settings['api_endpoint']
            );
        });
        
        // Register settings service
        $this->container->register('settings', function() {
            return new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsService();
        });
        
        // Register product sync service
        $this->container->register('product_sync', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\ProductSyncService(
                $container->get('api'),
                $container->get('settings'),
                $container->get('logger')
            );
        });
        
        // Register logger
        $this->container->register('logger', function() {
            return new \ApolloWeb\WPWooCommercePrintifySync\Logger\SyncLogger();
        });
        
        // Register webhook handler
        $this->container->register('webhook_handler', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookHandler(
                $container->get('product_sync'),
                $container->get('logger')
            );
        });
        
        // Register category helper
        $this->container->register('category_helper', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\Helpers\CategoryHelper(
                $container->get('logger')
            );
        });
        
        // Register tag helper
        $this->container->register('tag_helper', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\Helpers\TagHelper(
                $container->get('logger')
            );
        });
        
        // Register variant helper
        $this->container->register('variant_helper', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\Helpers\VariantHelper(
                $container->get('logger')
            );
        });
        
        // Register image handler
        $this->container->register('image_handler', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\Helpers\ImageHandler(
                $container->get('logger')
            );
        });
        
        // Register price handler
        $this->container->register('price_handler', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\Helpers\PriceHandler(
                $container->get('logger'),
                $container->get('settings')
            );
        });
        
        // Register import scheduler
        $this->container->register('import_scheduler', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\ImportScheduler(
                $container->get('api'),
                $container->get('logger'),
                $container->get('settings')
            );
        });
        
        // Register product processor
        $this->container->register('product_processor', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\ProductProcessor(
                $container->get('api'),
                $container->get('logger'),
                $container->get('category_helper'),
                $container->get('tag_helper'),
                $container->get('variant_helper'),
                $container->get('image_handler'),
                $container->get('price_handler')
            );
        });
        
        // Register webhook service
        $this->container->register('webhook_service', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookService(
                $container->get('api'),
                $container->get('logger'),
                $container->get('settings'),
                $container->get('import_scheduler')
            );
        });
        
        // Register stock service
        $this->container->register('stock_service', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Products\StockService(
                $container->get('api'),
                $container->get('logger'),
                $container->get('settings')
            );
        });
    }
    
    /**
     * Initialize the plugin
     * 
     * @return void
     */
    public function initialize() {
        // Register admin pages
        $this->registerAdminPages();
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
        
        // Initialize REST API
        $this->initializeRestApi();
        
        // Initialize AJAX handlers
        $this->initializeAjaxHandlers();
        
        // Register action scheduler hooks
        $this->registerActionSchedulerHooks();
    }
    
    /**
     * Register admin pages
     * 
     * @return void
     */
    private function registerAdminPages() {
        $adminPage = new \ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\AdminPage(
            $this->container->get('settings'),
            new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine()
        );
        add_action('admin_menu', [$adminPage, 'registerMenus']);
    }
    
    /**
     * Initialize REST API
     */
    private function initializeRestApi() {
        add_action('rest_api_init', function() {
            $controller = new \ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookController(
                $this->container->get('webhook_handler')
            );
            $controller->register_routes();
        });
    }
    
    /**
     * Register action scheduler hooks
     */
    private function registerActionSchedulerHooks() {
        // Product import hook
        add_action('wpwps_import_product', function($printify_product_id) {
            $this->container->get('product_sync')->import_product($printify_product_id);
        }, 10, 1);
        
        // Product image import hook
        add_action('wpwps_import_product_image', function($product_id, $image_url, $is_featured) {
            $this->container->get('product_sync')->import_product_image($product_id, $image_url, $is_featured);
        }, 10, 3);
        
        // Variant image import hook
        add_action('wpwps_import_variant_image', function($product_id, $variant_id, $image_url) {
            $this->container->get('product_sync')->import_variant_image($product_id, $variant_id, $image_url);
        }, 10, 3);
        
        // Other Action Scheduler hooks...
    }
    
    /**
     * Register assets
     * 
     * @param string $hook Current admin page
     * @return void
     */
    public function registerAssets($hook) {
        // Core assets - loaded on all plugin pages
        if (strpos($hook, 'wpwps') !== false) {
            // Bootstrap CSS
            wp_enqueue_style(
                'wpwps-bootstrap',
                WPWPS_PLUGIN_URL . 'assets/core/css/bootstrap.min.css',
                [],
                WPWPS_VERSION
            );
            
            // Font Awesome CSS
            wp_enqueue_style(
                'wpwps-fontawesome',
                WPWPS_PLUGIN_URL . 'assets/core/css/fontawesome.min.css',
                [],
                WPWPS_VERSION
            );
            
            // Admin CSS Overrides - load after Bootstrap
            wp_enqueue_style(
                'wpwps-admin-override',
                WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin-override.css',
                ['wpwps-bootstrap'],
                WPWPS_VERSION
            );
            
            // Bootstrap JS
            wp_enqueue_script(
                'wpwps-bootstrap',
                WPWPS_PLUGIN_URL . 'assets/core/js/bootstrap.bundle.min.js',
                ['jquery'],
                WPWPS_VERSION,
                true
            );
            
            // Chart.js
            wp_enqueue_script(
                'wpwps-chart',
                WPWPS_PLUGIN_URL . 'assets/core/js/chart.min.js',
                [],
                WPWPS_VERSION,
                true
            );
            
            // Font Awesome JS
            wp_enqueue_script(
                'wpwps-fontawesome',
                WPWPS_PLUGIN_URL . 'assets/core/js/fontawesome.min.js',
                [],
                WPWPS_VERSION,
                true
            );
            
            // Common JS
            wp_enqueue_script(
                'wpwps-common',
                WPWPS_PLUGIN_URL . 'assets/js/wpwps-common.js',
                ['jquery', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
            
            // Localize common script
            $current_screen = get_current_screen();
            $current_page = isset($current_screen->base) ? str_replace('toplevel_page_', '', $current_screen->base) : '';
            wp_localize_script('wpwps-common', 'wpwps_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps-common-nonce'),
                'current_page' => $current_page
            ]);
        }
    }
    
    /**
     * Initialize AJAX handlers
     * 
     * @return void
     */
    private function initializeAjaxHandlers() {
        $settings = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsAjax(
            $this->container->get('settings')
        );
        $settings->registerAjaxHandlers();
        
        $product_ajax = new \ApolloWeb\WPWooCommercePrintifySync\Products\ProductAjax(
            $this->container->get('product_sync')
        );
        $product_ajax->registerAjaxHandlers();
    }
}
