<?php
/**
 * Main Plugin class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Core Plugin class
 */
class Plugin
{
    /**
     * Service providers
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Boot the plugin
     *
     * @return void
     */
    public function boot()
    {
        // Boot service providers
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
        
        // Register activation and deactivation hooks
        $this->registerHooks();
        
        // Initialize admin-specific functionality
        if (is_admin()) {
            $this->initAdmin();
        }
    }

    /**
     * Register service providers
     *
     * @return void
     */
    protected function registerProviders()
    {
        $this->providers = [];
        
        // Register core service providers
        $this->registerProvider(new \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider($this));
        $this->registerProvider(new \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider($this));
    }

    /**
     * Register a service provider
     *
     * @param \ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider $provider
     * @return void
     */
    protected function registerProvider($provider)
    {
        if (is_string($provider)) {
            $provider = new $provider();
        }
        
        $provider->register();
        $this->providers[] = $provider;
    }

    /**
     * Register activation and deactivation hooks
     *
     * @return void
     */
    protected function registerHooks()
    {
        // Register custom post types and taxonomies
        add_action('init', [$this, 'registerCustomPostTypes']);
        
        // Schedule cron jobs
        add_action('wp', [$this, 'scheduleCronJobs']);
    }

    /**
     * Initialize admin-specific functionality
     *
     * @return void
     */
    protected function initAdmin()
    {
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Register custom post types
     *
     * @return void
     */
    public function registerCustomPostTypes()
    {
        // Register support_ticket CPT for AI ticketing system
        register_post_type('support_ticket', [
            'labels' => [
                'name'               => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name'      => __('Support Ticket', 'wp-woocommerce-printify-sync'),
                'menu_name'          => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'add_new'            => __('Add New', 'wp-woocommerce-printify-sync'),
                'add_new_item'       => __('Add New Ticket', 'wp-woocommerce-printify-sync'),
                'edit_item'          => __('Edit Ticket', 'wp-woocommerce-printify-sync'),
                'new_item'           => __('New Ticket', 'wp-woocommerce-printify-sync'),
                'view_item'          => __('View Ticket', 'wp-woocommerce-printify-sync'),
                'search_items'       => __('Search Tickets', 'wp-woocommerce-printify-sync'),
                'not_found'          => __('No tickets found', 'wp-woocommerce-printify-sync'),
                'not_found_in_trash' => __('No tickets found in Trash', 'wp-woocommerce-printify-sync'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'wpwps-dashboard',
            'supports'            => ['title', 'editor', 'author', 'comments'],
            'has_archive'         => false,
            'menu_icon'           => 'dashicons-admin-users', // Changed to a user icon to represent tickets
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'menu_position'       => 25, // Position it after Email Queue but before Shipping Profiles
            'hierarchical'        => false,
            'show_in_rest'        => true,
        ]);
    }

    /**
     * Schedule cron jobs
     *
     * @return void
     */
    public function scheduleCronJobs()
    {
        // Schedule stock sync (every 6 hours)
        if (!wp_next_scheduled('wpwps_stock_sync')) {
            wp_schedule_event(time(), 'six_hours', 'wpwps_stock_sync');
        }
        
        // Schedule email queue processing (every 5 minutes)
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'wpwps_process_email_queue');
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueueAdminAssets($hook)
    {
        // Only load on plugin admin pages
        if (strpos($hook, 'wpwps') === false) {
            return;
        }
        
        // Use Asset class to enqueue all necessary assets
        Asset::enqueueAssets();
        
        // Get current page for script localization
        $page = str_replace('wpwps_page_', '', $hook);
        if ($page) {
            // Localize script for AJAX
            wp_localize_script("wpwps-{$page}-js", 'wpwps', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps-nonce'),
            ]);
        }
    }

    /**
     * Get a service provider instance
     *
     * @param string $class Provider class name
     * @return object|null Provider instance or null if not found
     */
    public function getProvider($class)
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof $class) {
                return $provider;
            }
        }
        
        return null;
    }
}