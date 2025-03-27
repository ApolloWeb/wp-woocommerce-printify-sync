<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Providers\AdminServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Providers\ApiServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Providers\WooCommerceServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Providers\SyncServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Providers\AssetsServiceProvider;

class Plugin
{
    /**
     * The registered service providers.
     *
     * @var array
     */
    protected array $providers = [];
    
    /**
     * Boots up the plugin.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerProviders([
            AdminServiceProvider::class,
            ApiServiceProvider::class,
            WooCommerceServiceProvider::class,
            SyncServiceProvider::class,
            AssetsServiceProvider::class
        ]);
        
        // Register activation and deactivation hooks
        register_activation_hook($this->getPluginFile(), [$this, 'onActivate']);
        register_deactivation_hook($this->getPluginFile(), [$this, 'onDeactivate']);
        
        // Initialize all registered providers
        $this->initializeProviders();
    }
    
    /**
     * Registers service providers.
     *
     * @param array $providers
     * @return void
     */
    protected function registerProviders(array $providers): void
    {
        foreach ($providers as $providerClass) {
            if (class_exists($providerClass)) {
                $provider = new $providerClass;
                if ($provider instanceof ServiceProvider) {
                    $provider->register();
                    $this->providers[] = $provider;
                }
            }
        }
    }
    
    /**
     * Initializes all registered service providers.
     *
     * @return void
     */
    protected function initializeProviders(): void
    {
        // Add initialization code that runs after all providers are registered
        add_action('plugins_loaded', function() {
            // Initialize functionality that requires all providers to be registered
            do_action('wpwps_plugin_initialized');
        });
    }

    /**
     * Handle plugin activation.
     *
     * @return void
     */
    public function onActivate(): void
    {
        foreach ($this->providers as $provider) {
            $provider->bootActivation();
        }
        
        // Create necessary database tables and default settings
        $this->createRequiredStructures();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Handle plugin deactivation.
     *
     * @return void
     */
    public function onDeactivate(): void
    {
        foreach ($this->providers as $provider) {
            $provider->bootDeactivation();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create required database tables and default settings.
     *
     * @return void
     */
    protected function createRequiredStructures(): void
    {
        // Create required database tables if needed
        global $wpdb;
        
        // Example: Create sync logs table if it doesn't exist
        $charsetCollate = $wpdb->get_charset_collate();
        $syncLogsTable = $wpdb->prefix . 'wpwps_sync_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$syncLogsTable'") != $syncLogsTable) {
            $sql = "CREATE TABLE $syncLogsTable (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                type varchar(50) NOT NULL,
                message text NOT NULL,
                status varchar(20) NOT NULL,
                data longtext,
                PRIMARY KEY  (id)
            ) $charsetCollate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Set default settings if they don't exist
        if (!get_option('wpwps_settings')) {
            update_option('wpwps_settings', [
                'api_key' => '',
                'shop_id' => '',
                'auto_sync' => false,
                'sync_interval' => 'hourly',
                'sync_products' => true,
                'sync_inventory' => true,
                'sync_orders' => true,
            ]);
        }
    }
    
    /**
     * Gets the plugin main file.
     *
     * @return string
     */
    protected function getPluginFile(): string
    {
        return WPWPS_FILE;
    }
}
