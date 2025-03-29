<?php
/**
 * Main Plugin Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Plugin class for initializing the plugin
 */
class Plugin
{
    /**
     * The registered service providers
     *
     * @var array
     */
    protected $providers = [];
    
    /**
     * Boot the plugin
     *
     * @return void
     */
    public function boot()
    {
        // Register default service providers
        $this->registerDefaultServiceProviders();
        
        // Initialize service providers
        $this->initializeProviders();
        
        // Register activation and deactivation hooks
        register_activation_hook(WPWPS_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(WPWPS_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Load textdomain
        add_action('init', [$this, 'loadTextDomain']);
    }
    
    /**
     * Register the default service providers
     *
     * @return void
     */
    protected function registerDefaultServiceProviders()
    {
        $this->providers = [
            \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class,
        ];
    }
    
    /**
     * Initialize all registered service providers
     *
     * @return void
     */
    protected function initializeProviders()
    {
        foreach ($this->providers as $provider) {
            $instance = new $provider();
            if ($instance instanceof ServiceProvider) {
                $instance->register();
            }
        }
    }
    
    /**
     * Actions to perform on plugin activation
     *
     * @return void
     */
    public function activate()
    {
        // Create necessary database tables or set up default options
        $this->createRequiredDirectories();
        $this->setDefaultOptions();
        
        // Add custom capabilities
        $this->addCustomCapabilities();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Actions to perform on plugin deactivation
     *
     * @return void
     */
    public function deactivate()
    {
        // Clean up any temporary files or transients
        $this->cleanupTemporaryFiles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create required directories for the plugin
     *
     * @return void
     */
    protected function createRequiredDirectories()
    {
        $directories = [
            WPWPS_PLUGIN_DIR . 'logs',
            WPWPS_PLUGIN_DIR . 'templates/cache',
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Create .htaccess to protect directories
                if (strpos($dir, 'logs') !== false || strpos($dir, 'cache') !== false) {
                    $htaccess = $dir . '/.htaccess';
                    if (!file_exists($htaccess)) {
                        file_put_contents($htaccess, 'Deny from all');
                    }
                }
            }
        }
    }
    
    /**
     * Set default plugin options
     *
     * @return void
     */
    protected function setDefaultOptions()
    {
        $default_options = [
            'printify_api_endpoint' => 'https://api.printify.com/v1/',
            'sync_interval' => 12, // hours
            'log_level' => 'info',
        ];
        
        foreach ($default_options as $key => $value) {
            if (get_option('wpwps_' . $key) === false) {
                update_option('wpwps_' . $key, $value);
            }
        }
    }
    
    /**
     * Add custom capabilities to roles
     *
     * @return void
     */
    protected function addCustomCapabilities()
    {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_printify_sync');
        }
    }
    
    /**
     * Clean up temporary files on deactivation
     *
     * @return void
     */
    protected function cleanupTemporaryFiles()
    {
        // Delete all transients
        delete_transient('wpwps_sync_status');
        delete_transient('wpwps_api_health');
    }
    
    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function loadTextDomain()
    {
        load_plugin_textdomain(
            WPWPS_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(WPWPS_PLUGIN_FILE)) . '/languages'
        );
    }
}