<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Templates\TemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Import\ProductImporter;

class ProductImport
{
    /**
     * The settings object
     * 
     * @var Settings
     */
    private Settings $settings;
    
    /**
     * The API object
     * 
     * @var PrintifyAPI
     */
    private PrintifyAPI $api;
    
    /**
     * The template engine
     * 
     * @var TemplateEngine
     */
    private TemplateEngine $templateEngine;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->settings = new Settings();
        $this->api = new PrintifyAPI($this->settings);
        $this->templateEngine = new TemplateEngine();
        
        // Add submenu page
        add_action('admin_menu', [$this, 'addImportPage']);
        
        // Handle form submissions
        add_action('admin_init', [$this, 'handleFormSubmission']);
    }
    
    /**
     * Add the import page to the admin menu
     */
    public function addImportPage(): void
    {
        add_submenu_page(
            'printify-sync',
            __('Import Products', 'wp-woocommerce-printify-sync'),
            __('Import Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync-import',
            [$this, 'renderImportPage']
        );
    }
    
    /**
     * Render the import page
     */
    public function renderImportPage(): void
    {
        $shopId = $this->settings->getShopId();
        $shopName = $this->settings->getShopName();
        $apiConfigured = !empty($this->settings->getApiKey()) && !empty($shopId);
        
        // Get import status information
        $lastImport = get_option('wpwps_last_import_timestamp', 0);
        $importRunning = as_has_scheduled_action('wpwps_process_product_import_queue');
        $importStats = get_option('wpwps_import_stats', [
            'total' => 0,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
        ]);
        
        // Get list of product blueprints/catalog for filtering options
        $productTypes = [];
        if ($apiConfigured) {
            try {
                // Since the real catalogs API isn't implemented yet, we'll use a placeholder
                $productTypes = [
                    'apparel' => 'Apparel',
                    't-shirts' => 'T-Shirts',
                    'hoodies' => 'Hoodies',
                    'accessories' => 'Accessories',
                    'home-decor' => 'Home Decor',
                ];
            } catch (\Exception $e) {
                // Handle API error
            }
        }
        
        $data = [
            'apiConfigured' => $apiConfigured,
            'shopId' => $shopId,
            'shopName' => $shopName,
            'lastImport' => $lastImport,
            'importRunning' => $importRunning,
            'importStats' => $importStats,
            'productTypes' => $productTypes,
            'dashboardUrl' => admin_url('admin.php?page=printify-sync'),
            'importNonce' => wp_create_nonce('wpwps_product_import_nonce'),
            'cancelNonce' => wp_create_nonce('wpwps_cancel_import_nonce'),
        ];
        
        $this->templateEngine->render('product-import', $data);
    }
    
    /**
     * Handle form submissions
     */
    public function handleFormSubmission(): void
    {
        // Check if this is our form submission
        if (!isset($_POST['wpwps_product_import_action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['wpwps_import_nonce']) || !wp_verify_nonce($_POST['wpwps_import_nonce'], 'wpwps_product_import_nonce')) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_die('You do not have permission to import products.');
        }
        
        $action = sanitize_text_field($_POST['wpwps_product_import_action']);
        
        switch ($action) {
            case 'start_import':
                $this->startProductImport();
                break;
                
            case 'cancel_import':
                $this->cancelProductImport();
                break;
        }
    }
    
    /**
     * Start the product import process
     */
    private function startProductImport(): void
    {
        $shopId = $this->settings->getShopId();
        
        if (empty($shopId)) {
            add_settings_error(
                'wpwps_import',
                'wpwps_shop_not_set',
                __('Shop ID is not set. Please configure the Printify API settings first.', 'wp-woocommerce-printify-sync'),
                'error'
            );
            return;
        }
        
        // Check if an import is already running
        if (as_has_scheduled_action('wpwps_process_product_import_queue')) {
            add_settings_error(
                'wpwps_import',
                'wpwps_import_running',
                __('An import is already in progress. Please wait until it completes.', 'wp-woocommerce-printify-sync'),
                'error'
            );
            return;
        }
        
        // Get filters
        $productType = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : '';
        $syncMode = isset($_POST['sync_mode']) ? sanitize_text_field($_POST['sync_mode']) : 'all';
        
        // Initialize import stats
        update_option('wpwps_import_stats', [
            'total' => 0,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
        ]);
        
        // Create the importer
        $importer = new ProductImporter($this->api, $this->settings);
        
        // Queue up the first action to start the import process
        as_enqueue_async_action('wpwps_start_product_import', [
            'shop_id' => $shopId,
            'product_type' => $productType,
            'sync_mode' => $syncMode,
        ]);
        
        add_settings_error(
            'wpwps_import',
            'wpwps_import_started',
            __('Product import has been scheduled and will run in the background.', 'wp-woocommerce-printify-sync'),
            'success'
        );
        
        // Redirect back to the import page
        wp_redirect(admin_url('admin.php?page=printify-sync-import&import_started=1'));
        exit;
    }
    
    /**
     * Cancel the import process
     */
    private function cancelProductImport(): void
    {
        // Clear all scheduled product import actions
        as_unschedule_all_actions('wpwps_process_product_import_queue');
        as_unschedule_all_actions('wpwps_start_product_import');
        
        add_settings_error(
            'wpwps_import',
            'wpwps_import_cancelled',
            __('Product import has been cancelled.', 'wp-woocommerce-printify-sync'),
            'info'
        );
        
        // Redirect back to the import page
        wp_redirect(admin_url('admin.php?page=printify-sync-import&import_cancelled=1'));
        exit;
    }
}
