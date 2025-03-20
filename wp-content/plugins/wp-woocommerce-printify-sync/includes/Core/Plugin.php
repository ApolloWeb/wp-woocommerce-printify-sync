<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AjaxHandler;
use ApolloWeb\WPWooCommercePrintifySync\Admin\ProductImport;
use ApolloWeb\WPWooCommercePrintifySync\Import\ActionSchedulerIntegration;

class Plugin
{
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function run(): void
    {
        // Register activation and deactivation hooks
        register_activation_hook(WPWPS_PLUGIN_BASENAME, [Activator::class, 'activate']);
        register_deactivation_hook(WPWPS_PLUGIN_BASENAME, [Deactivator::class, 'deactivate']);
        
        // Initialize the admin menu
        $this->initAdminMenu();
        
        // Initialize AJAX handlers
        $this->initAjaxHandlers();
        
        // Initialize product import
        $this->initProductImport();
        
        // Initialize Action Scheduler (this won't fail fatally anymore with our improved code)
        ActionSchedulerIntegration::init();
        
        // Add admin notice if WooCommerce is not active
        add_action('admin_notices', [$this, 'checkWooCommerceDependency']);
        
        // Load translations
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
    }
    
    /**
     * Initialize the admin menu
     *
     * @return void
     */
    private function initAdminMenu(): void
    {
        $adminMenu = new AdminMenu();
        add_action('admin_menu', [$adminMenu, 'registerMenuPages']);
        add_action('admin_enqueue_scripts', [$adminMenu, 'enqueueAssets']);
    }
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    private function initAjaxHandlers(): void
    {
        $ajaxHandler = new AjaxHandler();
        $ajaxHandler->registerHandlers();
    }
    
    /**
     * Initialize product import
     *
     * @return void
     */
    private function initProductImport(): void
    {
        new ProductImport();
    }
    
    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPWPS_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Check if WooCommerce is active and show admin notice if not
     *
     * @return void
     */
    public function checkWooCommerceDependency(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>WP WooCommerce Printify Sync:</strong> 
                    <?php _e('WooCommerce is required for this plugin to work properly. Please install and activate WooCommerce.', 'wp-woocommerce-printify-sync'); ?>
                </p>
            </div>
            <?php
        }
    }
}
