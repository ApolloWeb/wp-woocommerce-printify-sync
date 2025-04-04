<?php
/**
 * Dashboard Provider
 *
 * Handles dashboard functionality for the Printify Sync plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @subpackage Providers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Factories\ConfigFactory;
use ApolloWeb\WPWooCommercePrintifySync\Factories\MonitorFactory;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ConfigInterface;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;
use ApolloWeb\WPWooCommercePrintifySync\Services\Config\PrintifyConfig;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ApiClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\MonitorInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\RateLimiterInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\OrderSyncInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\TrackingInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Order\OrderStatusManager;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ProductSyncInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ImageProcessorInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Product\ProductSyncManager;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ShippingProfileInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\CurrencyConverterInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Shipping\ShippingProfileManager;
use ApolloWeb\WPWooCommercePrintifySync\Services\Shipping\CurrencyConverter;
use ApolloWeb\WPWooCommercePrintifySync\Services\Shipping\ZoneManager;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\TicketManagerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\EmailProcessorInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\RefundManagerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Support\TicketManager;
use ApolloWeb\WPWooCommercePrintifySync\Services\Support\EmailProcessor;
use ApolloWeb\WPWooCommercePrintifySync\Services\Support\RefundManager;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\EmailQueueInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\EmailBrandingInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Email\QueueManager;
use ApolloWeb\WPWooCommercePrintifySync\Services\Email\BrandingManager;
use ApolloWeb\WPWooCommercePrintifySync\Services\UI\DashboardAssets;
use ApolloWeb\WPWooCommercePrintifySync\Services\UI\DashboardTheme;
use ApolloWeb\WPWooCommercePrintifySync\Services\UI\NotificationManager;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\NavigatorInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\UI\MenuNavigator;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ThemeInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\UI\DashboardUI;

if (!defined('ABSPATH')) {
    exit;
}

class DashboardProvider extends ServiceProvider {
    
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->registerConfigFactory();
        $this->registerMonitorFactory();
        $this->registerServices();
    }

    private function registerConfigFactory() {
        $this->app->singleton(ConfigFactory::class, function($app) {
            return new ConfigFactory($app);
        });

        $this->app->singleton(ConfigInterface::class, function($app) {
            return $app->make(ConfigFactory::class)->create([
                'base_uri' => 'https://api.printify.com/v1/',
                'timeout'  => 30,
                'retry' => [
                    'max_attempts' => 3,
                    'delay' => 1000,
                    'multiplier' => 2,
                    'circuit_breaker' => [
                        'failure_threshold' => 5,
                        'reset_timeout' => 300
                    ],
                    'jitter' => true
                ],
                'webhooks' => [
                    'order_created' => true,
                    'order_shipped' => true,
                    'order_canceled' => true
                ],
                'order_sync' => [
                    'hpos_enabled' => true,
                    'batch_size' => 100,
                    'lock_exchange_rate' => true
                ],
                'metrics' => [
                    'enabled' => true,
                    'retention_days' => 7
                ]
            ]);
        });
    }

    private function registerMonitorFactory() {
        $this->app->singleton(MonitorFactory::class, function($app) {
            return new MonitorFactory(
                $app->make(ConfigInterface::class),
                $app->make('encryption')
            );
        });

        $this->app->singleton(MonitorInterface::class, function($app) {
            return $app->make(MonitorFactory::class)->createComposite([
                'api' => true,
                'queue' => true,
                'sync' => true,
                'webhooks' => true
            ]);
        });
    }

    private function registerServices() {
        // Add Printify API Service
        $this->app->singleton(PrintifyApiInterface::class, function($app) {
            return new PrintifyApi(
                $app->make(ConfigInterface::class),
                $app->make(RateLimiterInterface::class)
            );
        });

        // API Client with injected dependencies
        $this->app->singleton(ApiClientInterface::class, function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyClient(
                $app->make(ConfigInterface::class),
                $app->make(RateLimiterInterface::class),
                $app->make('encryption')
            );
        });

        // Rate Limiter
        $this->app->singleton(RateLimiterInterface::class, function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\RateLimiter([
                'requests_per_minute' => 120,
                'buffer_threshold' => 0.1,
                'sliding_window' => true
            ]);
        });

        // Other services
        $this->app->singleton('dashboard.stats', function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\DashboardStats();
        });

        $this->app->singleton('ai.support', function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\AiSupport([
                'model' => 'gpt-3.5-turbo',
                'api_key' => $this->getOpenAiKey(),
                'temperature' => get_option('wpwps_ai_temperature', 0.7),
                'max_tokens' => get_option('wpwps_ai_max_tokens', 2000),
                'spend_cap' => get_option('wpwps_ai_spend_cap', 50)
            ]);
        });

        $this->app->singleton('printify.shipping', function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\ShippingManager([
                'client' => $app->make(ApiClientInterface::class),
                'cache' => $app->make('printify.cache'),
                'geolocation' => new \ApolloWeb\WPWooCommercePrintifySync\Services\MaxMindGeo()
            ]);
        });

        $this->app->singleton('queue.manager', function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\QueueManager([
                'email_interval' => 300,
                'sync_interval' => 21600,
                'log_retention' => 30
            ]);
        });

        $this->app->singleton('printify.cache', function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyCache([
                'path' => WPWPS_PLUGIN_PATH . 'cache',
                'ttl'  => 3600,
                'tags' => ['products', 'orders', 'shops']
            ]);
        });

        // Order Management Services
        $this->app->singleton(OrderSyncInterface::class, function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\Order\OrderSync(
                $app->make(ApiClientInterface::class),
                $app->make('encryption'),
                $app->make(OrderStatusManager::class)
            );
        });

        $this->app->singleton(OrderStatusManager::class, function($app) {
            return new OrderStatusManager([
                'pre_production' => [
                    'on-hold' => __('On Hold', 'wp-woocommerce-printify-sync'),
                    'awaiting-customer-evidence' => __('Awaiting Evidence', 'wp-woocommerce-printify-sync'),
                    'submit-order' => __('Submit Order', 'wp-woocommerce-printify-sync')
                ],
                'production' => [
                    'in-production' => __('In Production', 'wp-woocommerce-printify-sync'),
                    'has-issues' => __('Has Issues', 'wp-woocommerce-printify-sync')
                ],
                'shipping' => [
                    'ready-to-ship' => __('Ready to Ship', 'wp-woocommerce-printify-sync'),
                    'shipped' => __('Shipped', 'wp-woocommerce-printify-sync'),
                    'delivered' => __('Delivered', 'wp-woocommerce-printify-sync')
                ],
                'refund' => [
                    'refund-requested' => __('Refund Requested', 'wp-woocommerce-printify-sync'),
                    'refund-approved' => __('Refund Approved', 'wp-woocommerce-printify-sync')
                ]
            ]);
        });

        $this->app->singleton(TrackingInterface::class, function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\Order\TrackingManager([
                'notify_customer' => true,
                'email_template' => 'emails/tracking-info.php',
                'ast_integration' => true
            ]);
        });

        // Product Sync Services
        $this->app->singleton(ProductSyncInterface::class, function($app) {
            return new ProductSyncManager([
                'client' => $app->make(ApiClientInterface::class),
                'image_processor' => $app->make(ImageProcessorInterface::class),
                'batch_size' => 50,
                'sync_interval' => HOUR_IN_SECONDS,
                'meta_keys' => [
                    'product_id' => '_printify_product_id',
                    'provider_id' => '_printify_provider_id',
                    'variant_ids' => '_printify_variant_ids',
                    'last_synced' => '_printify_last_synced',
                    'blueprint_id' => '_printify_blueprint_id',
                    'shop_id' => '_printify_shop_id',
                    'provider_name' => '_printify_print_provider_name',
                    'is_synced' => '_printify_is_synced',
                    'cost_price' => '_printify_cost_price'
                ]
            ]);
        });

        // Image Processing Service
        $this->app->singleton(ImageProcessorInterface::class, function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\Product\ImageProcessor([
                'smush_enabled' => class_exists('WP_Smush'),
                'parallel_processing' => true,
                'max_concurrent' => 5,
                'cdn_enabled' => defined('WPSMUSH_CDN_STATUS') && WPSMUSH_CDN_STATUS
            ]);
        });

        // Shipping Profile Services
        $this->app->singleton(ShippingProfileInterface::class, function($app) {
            return new ShippingProfileManager([
                'client' => $app->make(ApiClientInterface::class),
                'currency_converter' => $app->make(CurrencyConverterInterface::class),
                'zone_manager' => $app->make(ZoneManager::class),
                'cache_duration' => DAY_IN_SECONDS,
                'batch_size' => 50,
                'sync_interval' => DAY_IN_SECONDS,
                'provider_meta_key' => '_printify_provider_id',
                'meta_keys' => [
                    'shipping_profile' => '_printify_shipping_profile',
                    'first_item' => '_printify_shipping_first_item',
                    'additional_items' => '_printify_shipping_additional_items',
                    'production_time' => '_printify_production_time',
                    'last_synced' => '_printify_shipping_last_synced'
                ]
            ]);
        });

        // Currency Conversion Service
        $this->app->singleton(CurrencyConverterInterface::class, function($app) {
            return new CurrencyConverter([
                'base_currency' => 'USD',
                'store_currency' => get_woocommerce_currency(),
                'update_interval' => HOUR_IN_SECONDS * 6,
                'fallback_rate' => get_option('wpwps_fallback_exchange_rate', 1)
            ]);
        });

        $this->app->singleton('shipping.currency', function($app) {
            return new CurrencyConverter();
        });

        // Zone Management Service
        $this->app->singleton(ZoneManager::class, function($app) {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\Shipping\ZoneManager([
                'auto_create_zones' => true,
                'method_prefix' => 'printify_',
                'geolocation' => $app->make('geolocation'),
                'defaults' => [
                    'title' => __('Printify Shipping', 'wp-woocommerce-printify-sync'),
                    'tax_status' => 'taxable',
                    'calculation_type' => 'item'
                ]
            ]);
        });

        // Support Ticket Services
        $this->app->singleton(TicketManagerInterface::class, function($app) {
            return new TicketManager([
                'ai_service' => $app->make('ai.support'),
                'refund_manager' => $app->make(RefundManagerInterface::class),
                'currency_converter' => $app->make(CurrencyConverterInterface::class),
                'email_queue' => $app->make(EmailQueueInterface::class),
                'email_branding' => $app->make(EmailBrandingInterface::class),
                'eligibility_days' => 30,
                'meta_keys' => [
                    'order_id' => '_wpwps_order_id',
                    'issue_type' => '_wpwps_issue_type',
                    'customer_email' => '_wpwps_customer_email',
                    'evidence' => '_wpwps_evidence',
                    'summary' => '_wpwps_parsed_summary',
                    'status' => '_wpwps_ticket_status',
                    'sender_email' => '_wpwps_sender_email',
                    'intent' => '_wpwps_intent',
                    'urgency' => '_wpwps_urgency',
                    'parsed_summary' => '_wpwps_parsed_summary',
                    'thread_history' => '_wpwps_thread_history',
                    'last_reply' => '_wpwps_last_reply_time'
                ],
                'statuses' => [
                    'awaiting-evidence' => __('Awaiting Evidence', 'wp-woocommerce-printify-sync'),
                    'refund-requested' => __('Refund Requested', 'wp-woocommerce-printify-sync'),
                    'refund-approved' => __('Refund Approved', 'wp-woocommerce-printify-sync'),
                    'refund-declined' => __('Refund Declined', 'wp-woocommerce-printify-sync'),
                    'reprint-requested' => __('Reprint Requested', 'wp-woocommerce-printify-sync'),
                    'reprint-approved' => __('Reprint Approved', 'wp-woocommerce-printify-sync'),
                    'reprint-declined' => __('Reprint Declined', 'wp-woocommerce-printify-sync')
                ],
                'ai_prompt' => [
                    'system' => 'You are a customer support AI assistant analyzing support emails.',
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ]
            ]);
        });

        // Email Processing Service
        $this->app->singleton(EmailProcessorInterface::class, function($app) {
            return new EmailProcessor([
                'host' => get_option('wpwps_pop3_host'),
                'port' => get_option('wpwps_pop3_port', 995),
                'username' => get_option('wpwps_pop3_username'),
                'password' => $this->getEmailPassword(),
                'options' => [
                    'ssl' => true,
                    'validate-cert' => true,
                    'timeout' => 30
                ],
                'folder' => 'INBOX',
                'delete_after_fetch' => true
            ]);
        });

        // Refund Management Service
        $this->app->singleton(RefundManagerInterface::class, function($app) {
            return new RefundManager([
                'api_client' => $app->make(ApiClientInterface::class),
                'currency_converter' => $app->make(CurrencyConverterInterface::class),
                'support_email' => 'support@printify.com',
                'meta_keys' => [
                    'refund_amount' => '_wpwps_refund_amount',
                    'original_currency' => '_wpwps_original_currency',
                    'exchange_rate' => '_wpwps_exchange_rate_locked'
                ]
            ]);
        });

        // Email Queue Service
        $this->app->singleton(EmailQueueInterface::class, function($app) {
            return new QueueManager([
                'process_interval' => 300, // 5 minutes
                'batch_size' => 50,
                'max_retries' => 3,
                'smtp' => [
                    'host' => get_option('wpwps_smtp_host'),
                    'port' => get_option('wpwps_smtp_port', 587),
                    'username' => get_option('wpwps_smtp_username'),
                    'password' => $this->getSmtpPassword(),
                    'encryption' => get_option('wpwps_smtp_encryption', 'tls'),
                    'from_email' => get_option('wpwps_smtp_from_email'),
                    'from_name' => get_option('wpwps_smtp_from_name')
                ]
            ]);
        });

        // Email Branding Service
        $this->app->singleton(EmailBrandingInterface::class, function($app) {
            return new BrandingManager([
                'company_name' => get_option('wpwps_company_name'),
                'logo_url' => get_option('wpwps_company_logo'),
                'social_media' => [
                    'facebook' => get_option('wpwps_social_facebook'),
                    'instagram' => get_option('wpwps_social_instagram'),
                    'tiktok' => get_option('wpwps_social_tiktok'),
                    'youtube' => get_option('wpwps_social_youtube')
                ],
                'templates_path' => WPWPS_PLUGIN_PATH . 'templates/emails',
                'images_path' => WPWPS_PLUGIN_PATH . 'assets/images',
                'signature_template' => 'signature.php',
                'auto_text' => [
                    'greeting' => __('Hello {customer_name},', 'wp-woocommerce-printify-sync'),
                    'footer' => __('Best regards,', 'wp-woocommerce-printify-sync')
                ]
            ]);
        });

        // UI Theme Service
        $this->app->singleton(ThemeInterface::class, function($app) {
            return new DashboardTheme();
        });

        // UI Components
        $this->app->singleton('dashboard.ui', function($app) {
            return new DashboardUI([
                'theme' => $app->make(ThemeInterface::class),
                'components' => [
                    'navbar' => [
                        'sticky' => true,
                        'glass' => true,
                        'height' => '4rem'
                    ],
                    'sidebar' => [
                        'width' => '280px',
                        'collapsed' => '64px'
                    ],
                    'content' => [
                        'max_width' => '1440px',
                        'padding' => '2rem'
                    ]
                ],
                'animations' => [
                    'duration' => 400,
                    'easing' => 'ease-out'
                ]
            ]);
        });

        $this->app->singleton('dashboard.notify', function($app) {
            return new NotificationManager([
                'position' => 'top-right',
                'timeout' => 5000,
                'theme' => $app->make('dashboard.ui'),
                'toast_template' => 'components/toast.php'
            ]);
        });

        // Navigation Service
        $this->app->singleton(NavigatorInterface::class, function($app) {
            return new MenuNavigator(
                $app->make(TicketManagerInterface::class),
                $app->make(EmailQueueInterface::class)
            );
        });
    }
    
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot() {
        // Add menu pages with higher priority to ensure it runs early
        add_action('admin_menu', [$this, 'registerMenuPages'], 9);
        
        // Enqueue dashboard assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_dashboard_stats', [$this, 'getDashboardStats']);
        
        // Add dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
        
        // Add REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Register webhooks
        add_action('woocommerce_api_printify_webhook', [$this, 'handleWebhook']);
        
        // Order sync hooks
        add_action('woocommerce_order_status_changed', [$this, 'syncOrderStatus'], 10, 4);
        add_action('woocommerce_new_order', [$this, 'createPrintifyOrder']);
        
        // HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });

        // Product sync hooks
        add_action('wpwps_schedule_product_sync', [$this, 'scheduleProductSync']);
        add_action('wpwps_process_product', [$this, 'processProduct']);
        add_action('wpwps_process_product_images', [$this, 'processProductImages']);

        // Register CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('printify products', [$this, 'handleProductCli']);
        }

        // Add product webhook handlers
        add_action('woocommerce_api_printify_product_webhook', [$this, 'handleProductWebhook']);

        // Shipping hooks
        add_action('wpwps_sync_shipping_profiles', [$this, 'syncShippingProfiles']);
        add_action('woocommerce_shipping_init', [$this, 'initShippingMethods']);
        add_filter('woocommerce_shipping_methods', [$this, 'addShippingMethods']);
        add_action('woocommerce_cart_calculate_fees', [$this, 'calculateProviderShipping']);
        
        // Schedule daily shipping sync
        if (!wp_next_scheduled('wpwps_sync_shipping_profiles')) {
            wp_schedule_event(time(), 'daily', 'wpwps_sync_shipping_profiles');
        }

        // Register custom post type
        add_action('init', [$this, 'registerSupportTicketCPT']);
        
        // Support ticket processing
        add_action('wpwps_process_email_queue', [$this, 'processEmailQueue']);
        
        // Schedule email fetch
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'wpwps_process_email_queue');
        }

        // Email queue processing (single registration)
        add_action('wpwps_process_email_queue', [$this, 'processEmailQueue']);
        
        // Add email queue dashboard widget
        add_action('wp_dashboard_setup', [$this, 'addEmailQueueWidget']);

        // Enhanced dashboard assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueDashboardAssets'], 20);
        add_action('admin_footer', [$this, 'renderDashboardTemplates']);
        add_action('admin_body_class', [$this, 'addDashboardBodyClass']);
    }
    
    /**
     * Register admin menu pages.
     *
     * @return void
     */
    public function registerMenuPages() {
        $capability = 'manage_options';
        
        if (defined('WP_DEBUG') && WP_DEBUG && !current_user_can($capability)) {
            error_log('WPWPS Menu: Current user does not have ' . $capability . ' capability');
        }
        
        $page = add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-store',
             56
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WPWPS Menu: add_menu_page returned ' . ($page ? $page : 'false/empty'));
        }
        
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            $capability,
            'wpwps-dashboard',
            [$this, 'renderDashboardPage']
        );
    }
    
    /**
     * Render the dashboard page.
     *
     * @return void
     */
    public function renderDashboardPage() {
        $data = $this->getDashboardData();
        
        // Initialize UI data
        $data['ui'] = [
            'theme' => $this->app->make('dashboard.ui'),
            'notify' => $this->app->make('dashboard.notify'),
            'user' => wp_get_current_user(),
            'navigation' => $this->getDashboardNavigation()
        ];
        
        // Start content buffer
        ob_start();
        
        // Render dashboard content
        echo View::render('dashboard/content', [
            'stats' => $this->getDashboardStats(),
            'notices' => $data['ui']['notify']->getAll()
        ]);
        
        // Get content from buffer
        $data['content'] = ob_get_clean();
        
        // Render full layout with content
        echo View::render('dashboard/layout', ['data' => $data]);
    }

    /**
     * AJAX handler for dashboard statistics.
     *
     * @return void|WP_Error
     */
    public function getDashboardStats() 
    {
        // Handle AJAX request
        if (wp_doing_ajax()) {
            check_ajax_referer('wpwps_dashboard_nonce', 'nonce');
            
            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error(['message' => __('You do not have permission to access this data', 'wp-woocommerce-printify-sync')]);
            }
        }

        return [
            'products' => [
                'total' => $this->getProductsCount(),
                'synced' => $this->getSyncedProductsCount(),
                'pending' => $this->getPendingProductsCount()
            ],
            'orders' => [
                'total' => $this->getOrdersCount(),
                'pending' => $this->getPendingOrdersCount(),
                'completed' => $this->getCompletedOrdersCount()
            ]
        ];
    }

    private function getSyncedProductsCount(): int {
        return (int) get_posts([
            'post_type' => 'product',
            'meta_key' => '_printify_is_synced',
            'meta_value' => '1',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
    }

    private function getPendingProductsCount(): int {
        return (int) get_posts([
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_printify_is_synced',
                    'value' => '0',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
    }

    private function getPendingOrdersCount(): int {
        return count(wc_get_orders([
            'status' => ['processing', 'on-hold'],
            'return' => 'ids',
            'limit' => -1
        ]));
    }

    private function getCompletedOrdersCount(): int {
        return count(wc_get_orders([
            'status' => ['completed'],
            'return' => 'ids',
            'limit' => -1
        ]));
    }

    private function getOrdersCount(): int 
    {
        return count(wc_get_orders([
            'return' => 'ids',
            'limit' => -1
        ]));
    }
    
    /**
     * Enqueue dashboard assets.
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueueAssets($hook) {
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wpwps-bootstrap',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/core/css/bootstrap.min.css',
            [],
            WPWPS_VERSION
        );
        
        wp_enqueue_style(
            'wpwps-fontawesome',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/core/css/fontawesome.min.css',
            [],
            WPWPS_VERSION
        );
        
        wp_enqueue_script(
            'wpwps-bootstrap',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/core/js/bootstrap.bundle.min.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wpwps-fontawesome',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/core/js/fontawesome.min.js',
            [],
            WPWPS_VERSION,
            true
        );
        
        if ($hook === 'toplevel_page_wpwps-dashboard' || $hook === 'printify-sync_page_wpwps-dashboard') {
            wp_enqueue_style(
                'wpwps-dashboard',
                plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/wpwps-dashboard.css',
                [],
                WPWPS_VERSION
            );
            
            wp_enqueue_script(
                'wpwps-chart',
                plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/core/js/chart.min.js',
                [],
                WPWPS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'wpwps-dashboard',
                plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/wpwps-dashboard.js',
                ['jquery', 'wpwps-chart'],
                WPWPS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'wpwps-dashboard-vue',
                plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/vue.min.js',
                [],
                WPWPS_VERSION,
                true
            );
            
            wp_localize_script('wpwps-dashboard', 'wpwps', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('wpwps/v1'),
                'nonce' => wp_create_nonce('wpwps_dashboard_nonce'),
                'is_debug' => defined('WP_DEBUG') && WP_DEBUG,
            ]);
        }
    }

    public function enqueueDashboardAssets($hook) {
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }

        // Core styles with Inter font
        wp_enqueue_style(
            'wpwps-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            WPWPS_VERSION
        );
        
        // Enhanced Bootstrap styles
        wp_enqueue_style(
            'wpwps-bootstrap',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/bootstrap.custom.min.css',
            [],
            WPWPS_VERSION
        );

        // Custom dashboard styles
        wp_enqueue_style(
            'wpwps-dashboard',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/dashboard.min.css',
            ['wpwps-bootstrap'],
            WPWPS_VERSION
        );

        // Enhanced scripts
        wp_enqueue_script(
            'wpwps-chart',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/chart.min.js',
            [],
            WPWPS_VERSION,
            true
        );

        wp_enqueue_script(
            'wpwps-dashboard',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/dashboard.min.js',
            ['jquery', 'wpwps-chart'],
            WPWPS_VERSION,
            true
        );

        // Pass UI config to JS
        wp_localize_script('wpwps-dashboard', 'wpwpsUI', [
            'colors' => $this->app->make('dashboard.ui')->getColors(),
            'animations' => [
                'duration' => 400,
                'easing' => 'ease-out'
            ],
            'charts' => [
                'defaults' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'animation' => [
                        'duration' => 1000,
                        'easing' => 'easeOutQuart'
                    ]
                ]
            ],
            'toasts' => [
                'position' => 'top-right',
                'timeout' => 5000
            ]
        ]);
    }

    public function addDashboardBodyClass($classes) {
        if (strpos(get_current_screen()->id, 'wpwps-') !== false) {
            $classes .= ' wpwps-dashboard';
        }
        return $classes;
    }
    
    /**
     * Get dashboard data for display.
     *
     * @return array
     */
    private function getDashboardData() {
        $products_count = $this->getProductsCount();
        $orders_count = $this->getOrdersCount();
        $pending_sync = $this->getPendingSyncCount();
        
        return [
            'plugin_name' => 'WP WooCommerce Printify Sync',
            'plugin_version' => WPWPS_VERSION,
            'stats' => [
                'products_count' => $products_count,
                'orders_count' => $orders_count,
                'pending_sync' => $pending_sync,
            ],
            'api_connected' => $this->isApiConnected(),
        ];
    }
    
    /**
     * Get the count of synced products.
     *
     * @return int
     */
    private function getProductsCount() {
        return 0;
    }
    
    /**
     * Get the count of synced orders.
     *
     * @return int
     */
    private function getOrdersCount() {
        return 0;
    }
    
    /**
     * Get the count of items pending sync.
     *
     * @return int
     */
    private function getPendingSyncCount() {
        return 0;
    }
    
    /**
     * Check if API is connected.
     *
     * @return bool
     */
    private function isApiConnected() {
        return false;
    }
    
    /**
     * Get encrypted API key
     *
     * @return string
     */
    private function getApiKey() {
        $encrypted = get_option('wpwps_printify_api_key');
        return $this->app->make('encryption')->decrypt($encrypted);
    }

    /**
     * Get OpenAI API key
     *
     * @return string
     */
    private function getOpenAiKey() {
        $encrypted = get_option('wpwps_openai_api_key');
        return $this->app->make('encryption')->decrypt($encrypted);
    }
    
    /**
     * Get webhook secret key
     *
     * @return string
     */
    private function getWebhookSecret() {
        $secret = get_option('wpwps_printify_webhook_secret');
        if (!$secret) {
            $secret = wp_generate_password(32, true, true);
            update_option('wpwps_printify_webhook_secret', $secret);
        }
        return $secret;
    }
    
    /**
     * Register REST API routes
     *
     * @return void
     */
    public function registerRestRoutes() {
        register_rest_route('wpwps/v1', '/dashboard/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'getDashboardStats'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * Add dashboard widgets
     */
    public function addDashboardWidgets() {
        wp_add_dashboard_widget(
            'wpwps_sync_status',
            __('Printify Sync Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSyncWidget']
        );

        wp_add_dashboard_widget(
            'wpwps_email_queue',
            __('Support Email Queue', 'wp-woocommerce-printify-sync'),
            [$this, 'renderEmailQueueWidget']
        );
    }

    /**
     * Add email queue dashboard widget
     */
    public function addEmailQueueWidget() {
        wp_add_dashboard_widget(
            'wpwps_email_queue_status',
            __('Email Queue Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderEmailQueueWidget']
        );
    }

    /**
     * Render email queue widget
     */
    public function renderEmailQueueWidget() {
        $queue = $this->app->make(EmailQueueInterface::class);
        $stats = $queue->getStats();
        
        echo View::render('dashboard/email-queue-widget', [
            'queued' => $stats['queued'],
            'sent' => $stats['sent'],
            'failed' => $stats['failed']
        ]);
    }

    /**
     * Process email queue 
     *
     * Handles both incoming POP3 messages and outgoing email queue
     * 
     * @return void
     */
    public function processEmailQueue() {
        try {
            // Process incoming emails
            $processor = $this->app->make(EmailProcessorInterface::class);
            $messages = $processor->fetchMessages();
            
            foreach ($messages as $message) {
                as_enqueue_async_action(
                    'wpwps_process_ticket',
                    ['message_data' => $message],
                    'support-tickets'
                );
            }

            // Process outgoing email queue
            $queue = $this->app->make(EmailQueueInterface::class);
            $queue->process();

        } catch (\Exception $e) {
            do_action('wpwps_sync_error', $e, 'email_processing');
        }
    }

    /**
     * Handle webhook events
     *
     * @return void
     */
    public function handleWebhook() {
        // Webhook handling logic
    }

    /**
     * Sync order status
     *
     * @param int $order_id WooCommerce order ID
     * @param string $old_status Previous order status
     * @param string $new_status New order status
     * @param \WC_Order $order WooCommerce order object
     * @return void
     */
    public function syncOrderStatus($order_id, $old_status, $new_status, $order) {
        // Order status synchronization logic
    }

    /**
     * Create Printify order
     *
     * @param int $order_id WooCommerce order ID
     * @return void
     */
    public function createPrintifyOrder($order_id) {
        // Printify order creation logic
    }

    /**
     * Schedule product sync using Action Scheduler
     *
     * @param array $args Sync arguments
     * @return void
     */
    public function scheduleProductSync($args = []) {
        $products = $this->app->make(ProductSyncInterface::class)->getProducts($args);
        
        foreach ($products as $product) {
            as_enqueue_async_action(
                'wpwps_process_product',
                ['product_data' => $product],
                'printify-product-sync'
            );
        }
    }

    /**
     * Process single product
     *
     * @param array $product_data Product data from Printify
     * @return void
     */
    public function processProduct($product_data) {
        try {
            $product = $this->app->make(ProductSyncInterface::class)
                              ->processProduct($product_data);

            // Schedule image processing
            if (!empty($product_data['images'])) {
                as_enqueue_async_action(
                    'wpwps_process_product_images',
                    [
                        'product_id' => $product->get_id(),
                        'images' => $product_data['images']
                    ],
                    'printify-image-sync'
                );
            }
        } catch (\Exception $e) {
            // Log error and maybe retry
            do_action('wpwps_sync_error', $e, $product_data);
        }
    }

    /**
     * Process product images
     *
     * @param int $product_id WooCommerce product ID
     * @param array $images Image URLs from Printify
     * @return void
     */
    public function processProductImages($product_id, $images) {
        try {
            $this->app->make(ImageProcessorInterface::class)
                     ->processImages($product_id, $images);
        } catch (\Exception $e) {
            // Log error and maybe retry
            do_action('wpwps_sync_error', $e, compact('product_id', 'images'));
        }
    }

    /**
     * Handle product webhook
     *
     * @return void
     */
    public function handleProductWebhook() {
        $payload = $this->verifyWebhook();
        if (!$payload) {
            return;
        }

        as_enqueue_async_action(
            'wpwps_process_product',
            ['product_data' => $payload],
            'printify-webhook-sync'
        );
    }

    /**
     * Sync shipping profiles using Action Scheduler
     *
     * @return void
     */
    public function syncShippingProfiles() {
        try {
            $manager = $this->app->make(ShippingProfileInterface::class);
            $manager->syncAll();
        } catch (\Exception $e) {
            do_action('wpwps_sync_error', $e, 'shipping_profiles');
        }
    }

    /**
     * Initialize shipping methods
     */
    public function initShippingMethods() {
        require_once WPWPS_PLUGIN_PATH . 'src/Shipping/PrintifyShippingMethod.php';
    }

    /**
     * Add shipping methods to WooCommerce
     */
    public function addShippingMethods($methods) {
        $methods['printify_shipping'] = '\ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyShippingMethod';
        return $methods;
    }

    /**
     * Calculate provider-specific shipping costs
     *
     * @param \WC_Cart $cart WooCommerce cart object
     * @return void
     */
    public function calculateProviderShipping($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        try {
            $manager = $this->app->make(ShippingProfileInterface::class);
            $shipping_costs = $manager->calculateCartShipping($cart);
            
            foreach ($shipping_costs as $provider_id => $cost) {
                $label = sprintf(
                    __('Shipping via %s', 'wp-woocommerce-printify-sync'),
                    $manager->getProviderName($provider_id)
                );
                
                $cart->add_fee($label, $cost);
            }
        } catch (\Exception $e) {
            do_action('wpwps_sync_error', $e, 'shipping_calculation');
        }
    }

    /**
     * Register support ticket custom post type
     *
     * @return void
     */
    public function registerSupportTicketCPT() {
        register_post_type('wpwps_support_ticket', [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync')
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-tickets-alt'
        ]);
    }

    /**
     * Process support ticket with AI
     *
     * @param array $message_data Email message data
     * @return void
     */
    public function processTicket($message_data) {
        try {
            $manager = $this->app->make(TicketManagerInterface::class);
            $ticket = $manager->createFromEmail($message_data);

            // Schedule status check
            as_schedule_single_action(
                time() + HOUR_IN_SECONDS,
                'wpwps_check_ticket_status',
                ['ticket_id' => $ticket->ID]
            );
        } catch (\Exception $e) {
            do_action('wpwps_sync_error', $e, 'ticket_processing');
        }
    }

    /**
     * Get encrypted email password
     *
     * @return string
     */
    private function getEmailPassword() {
        $encrypted = get_option('wpwps_pop3_password');
        return $this->app->make('encryption')->decrypt($encrypted);
    }

    /**
     * Get encrypted SMTP password
     *
     * @return string
     */
    private function getSmtpPassword() {
        $encrypted = get_option('wpwps_smtp_password');
        return $this->app->make('encryption')->decrypt($encrypted);
    }

    /**
     * Get dashboard navigation
     *
     * @return array
     */
    private function getDashboardNavigation() {
        return $this->app->make(NavigatorInterface::class)->getNavigation();
    }
}