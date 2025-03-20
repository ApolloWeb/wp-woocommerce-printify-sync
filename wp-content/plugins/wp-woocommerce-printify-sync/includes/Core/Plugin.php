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
        
        // Initialize Action Scheduler
        ActionSchedulerIntegration::init();
        
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
}
