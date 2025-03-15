<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\{
    Container,
    ServiceProvider,
    AppContext
};
use ApolloWeb\WPWooCommercePrintifySync\Admin\{
    AdminMenu,
    SettingsPage,
    LogViewer
};
use ApolloWeb\WPWooCommercePrintifySync\API\{
    PrintifyAPI,
    ExchangeRateAPI
};
use ApolloWeb\WPWooCommercePrintifySync\Services\{
    ProductImporter,
    ShippingManager,
    OrderManager,
    TicketingSystem
};
use ApolloWeb\WPWooCommercePrintifySync\Integration\WooCommerce\{
    ShippingMethodRegistration,
    HPOSCompatibility
};

class Plugin
{
    private Container $container;
    private AppContext $context;

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        $this->container = new Container();
        
        // Register service providers
        $this->registerProviders();
        
        // Initialize hooks
        $this->initializeHooks();
        
        // Register activation/deactivation hooks
        $this->registerLifecycleHooks();
    }

    private function registerProviders(): void
    {
        new ServiceProvider($this->container);
        
        // Register core services
        $this->container->singleton(PrintifyAPI::class);
        $this->container->singleton(ExchangeRateAPI::class);
        $this->container->singleton(ProductImporter::class);
        $this->container->singleton(ShippingManager::class);
        $this->container->singleton(OrderManager::class);
        $this->container->singleton(TicketingSystem::class);
        
        // Register admin services
        if (is_admin()) {
            $this->container->singleton(AdminMenu::class);
            $this->container->singleton(SettingsPage::class);
            $this->container->singleton(LogViewer::class);
        }
    }

    private function initializeHooks(): void
    {
        // Initialize HPOS compatibility
        add_action('before_woocommerce_init', function() {
            $this->container->make(HPOSCompatibility::class)->initialize();
        });

        // Initialize shipping methods
        add_action('woocommerce_shipping_init', function() {
            $this->container->make(ShippingMethodRegistration::class)->initialize();
        });

        // Register admin menus
        if (is_admin()) {
            add_action('admin_menu', function() {
                $this->container->make(AdminMenu::class)->registerMenus();
            });

            add_action('admin_enqueue_scripts', function() {
                $this->container->make(AdminMenu::class)->enqueueAssets();
            });
        }

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_save_settings', [$this, 'handleAjaxSaveSettings']);
        add_action('wp_ajax_wpwps_test_api', [$this, 'handleAjaxTestAPI']);
        add_action('wp_ajax_wpwps_view_logs', [$this, 'handleAjaxViewLogs']);

        // Register cron events
        add_action('wpwps_sync_exchange_rates', function() {
            $this->container->make(ExchangeRateAPI::class)->updateRates();
        });

        add_action('wpwps_sync_products', function() {
            $this->container->make(ProductImporter::class)->syncProducts();
        });
    }

    private function registerLifecycleHooks(): void
    {
        register_activation_hook(WPWPS_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(WPWPS_PLUGIN_FILE, [$this, 'deactivate']);
    }

    public function activate(): void
    {
        // Create required database tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Run database migrations
        $this->container->make(DatabaseMigration::class)->up();

        // Schedule cron jobs
        if (!wp_next_scheduled('wpwps_sync_exchange_rates')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_sync_exchange_rates');
        }

        if (!wp_next_scheduled('wpwps_sync_products')) {
            wp_schedule_event(time(), 'daily', 'wpwps_sync_products');
        }

        // Create default settings
        $this->createDefaultSettings();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('wpwps_sync_exchange_rates');
        wp_clear_scheduled_hook('wpwps_sync_products');

        // Clean up if needed
        $this->container->make(DatabaseMigration::class)->down();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private function createDefaultSettings(): void
    {
        $defaults = [
            'wpwps_api_key' => '',
            'wpwps_environment' => 'production',
            'wpwps_auto_sync' => 'yes',
            'wpwps_sync_interval' => 'daily',
            'wpwps_log_retention' => 14,
            'wpwps_enable_debugging' => 'no'
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    public function handleAjaxSaveSettings(): void
    {
        check_ajax_referer('wpwps-settings', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            $settings = $this->container->make(SettingsPage::class)
                ->saveSettings($_POST);

            wp_send_json_success([
                'message' => 'Settings saved successfully',
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    // ... other AJAX handlers
}