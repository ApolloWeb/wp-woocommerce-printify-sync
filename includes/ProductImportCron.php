<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Handles background processing for product imports
 */
class ProductImportCron {
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new Logger('cron');
        
        // Register cron hooks
        add_action('wpwps_daily_sync', [$this, 'scheduleDailySync']);
        add_action('wpwps_schedule_import_chunk', [$this, 'scheduleImportChunk'], 10, 2);
        add_action('wpwps_import_complete', [$this, 'completeImport']);
    }
    
    /**
     * Schedule daily sync
     *
     * @return void
     */
    public function scheduleDailySync() {
        $this->logger->log('Starting scheduled daily sync');
        
        $importer = new ProductImporter();
        $importer->startImport();
    }
    
    /**
     * Schedule import chunk (callback for action scheduler)
     *
     * @param int $page Page number
     * @param int $limit Items per page
     * @return void
     */
    public function scheduleImportChunk($page, $limit) {
        $importer = new ProductImporter();
        $importer->scheduleImportChunks($page, $limit);
    }
    
    /**
     * Complete import process
     *
     * @return void
     */
    public function completeImport() {
        update_option('wpwps_import_in_progress', 'no');
        
        $this->logger->log('Import process completed');
    }
}