<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\ServiceProviderInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\ProductImportService;

class AdminProductImport implements ServiceProviderInterface {
    /**
     * @var PrintifyAPI
     */
    private $api;
    
    /**
     * @var ProductImportService
     */
    private $importService;
    
    /**
     * Constructor
     */
    public function __construct(PrintifyAPI $api = null, ProductImportService $importService = null) {
        $api_key = get_option('printify_sync_api_key', '');
        $this->api = $api ?: new PrintifyAPI($api_key);
        $this->importService = $importService ?: new ProductImportService($this->api, new ImageHandler());
    }
    
    /**
     * Register services to the container
     * 
     * @return void
     */
    public function register() {
        // Register admin page and assets
        add_action('admin_menu', [$this, 'addImportSubmenu']);
    }
    
    /**
     * Bootstrap import service
     */
    public function boot() {
        // Register action scheduler hooks
        add_action('printify_process_import_queue', [$this, 'processImportQueue']);
    }
    
    /**
     * Add submenu page for product import
     */
    public function addImportSubmenu() {
        add_submenu_page(
            'woocommerce',
            __('Printify Import', 'wp-woocommerce-printify-sync'),
            __('Printify Import', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync-import',
            [$this, 'renderImportPage']
        );
    }
    
    /**
     * Render the import page
     */
    public function renderImportPage() {
        // Enqueue required assets
        wp_enqueue_style('printify-sync-product-import');
        wp_enqueue_script('printify-sync-product-import');
        
        // Localize script with import data
        wp_localize_script('printify-sync-product-import', 'printifySyncImport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('printify_import_nonce'),
            'queueSize' => count($this->importService->getQueue()),
            'strings' => [
                'importStarted' => __('Import started...', 'wp-woocommerce-printify-sync'),
                'importComplete' => __('Import complete!', 'wp-woocommerce-printify-sync'),
                'importFailed' => __('Import failed!', 'wp-woocommerce-printify-sync'),
                'importProgress' => __('Importing products... {count} of {total}', 'wp-woocommerce-printify-sync'),
                'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync')
            ]
        ]);
        
        // Get logs for display
        $logs = $this->importService->getLogs(50);
        $queue = $this->importService->getQueue();
        
        // Include template
        include plugin_dir_path(PRINTIFY_SYNC_FILE) . 'templates/admin/product-import.php';
    }
    
    /**
     * Process the import queue via action scheduler
     */
    public function processImportQueue() {
        $batch_size = apply_filters('printify_sync_import_batch_size', 5);
        $results = $this->importService->processBatch($batch_size);
        
        // If there are more items in the queue, schedule the next batch
        if ($results['results']['remaining'] > 0) {
            as_schedule_single_action(time() + 5, 'printify_process_import_queue');
        }
    }
}