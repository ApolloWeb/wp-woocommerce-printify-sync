<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Plugin bootstrap class
 */
class Bootstrap {
    /**
     * @var ServiceContainer
     */
    private $container;
    
    /**
     * Initialize the plugin
     */
    public function init() {
        $this->container = new ServiceContainer();
        
        $this->checkRequirements();
        // Move text domain loading to init hook
        add_action('init', [$this, 'loadTextDomain']);
        $this->registerServices();
        $this->registerHooks();
        $this->registerCronSchedules();
        $this->registerEventHandlers();
    }
    
    /**
     * Check if all plugin requirements are met
     */
    private function checkRequirements() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . 
                     '</p></div>';
            });
            throw new \Exception('WooCommerce is required');
        }
        
        if (!class_exists('ActionScheduler')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('WP WooCommerce Printify Sync requires Action Scheduler which is included in WooCommerce. Please ensure WooCommerce is up to date.', 'wp-woocommerce-printify-sync') . 
                     '</p></div>';
            });
            throw new \Exception('ActionScheduler is required');
        }
    }
    
    /**
     * Load the plugin text domain
     */
    public function loadTextDomain() {
        load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(WPWPS_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Register services with the container
     */
    private function registerServices() {
        $plugin = new Plugin($this->container);
        $plugin->initialize();
        
        // Register rate limiter
        $this->container->register('rate_limiter', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyRateLimiter(
                $container->get('logger')
            );
        });
        
        // Register base API client
        $this->container->register('base_api', function($container) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi(
                $container->get('settings'),
                $container->get('logger')
            );
        });
        
        // Register retry-enabled API client (decorates the base client)
        $this->container->register('api', function($container) {
            // Skip rate limiting if disabled in settings
            if (get_option('wpwps_enable_rate_limiting', 'yes') !== 'yes') {
                return $container->get('base_api');
            }
            
            return new \ApolloWeb\WPWooCommercePrintifySync\Api\RetryableApiClient(
                $container->get('base_api'),
                $container->get('rate_limiter'),
                $container->get('logger')
            );
        });
        
        // Register rate limit settings in admin
        if (is_admin()) {
            $this->container->register('rate_limit_settings', function() {
                return new \ApolloWeb\WPWooCommercePrintifySync\Admin\RateLimitSettings();
            });
            
            // Register settings
            add_action('admin_init', function() {
                $this->container->get('rate_limit_settings')->register_settings();
            });
        }
    }
    
    /**
     * Register all WordPress hooks
     */
    private function registerHooks() {
        // ... register hooks
    }
    
    /**
     * Register custom cron schedules
     */
    private function registerCronSchedules() {
        add_filter('cron_schedules', function($schedules) {
            $schedules['quarter_daily'] = [
                'interval' => 6 * HOUR_IN_SECONDS,
                'display'  => esc_html__('Four times daily', 'wp-woocommerce-printify-sync')
            ];
            return $schedules;
        });
        
        // Schedule stock synchronization if not already scheduled
        if (!wp_next_scheduled('wpwps_sync_stock_levels')) {
            wp_schedule_event(time(), 'quarter_daily', 'wpwps_sync_stock_levels');
        }
    }
    
    /**
     * Register event handlers for scheduled events
     */
    private function registerEventHandlers() {
        add_action('wpwps_sync_stock_levels', function() {
            $this->container->get('stock_service')->synchronize_stock_levels();
        });
        
        // ... register other event handlers
    }
    
    /**
     * Get the service container
     * 
     * @return ServiceContainer
     */
    public function getContainer() {
        return $this->container;
    }
}
