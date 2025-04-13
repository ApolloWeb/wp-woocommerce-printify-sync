<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsServiceInterface;

/**
 * Handles scheduling and batch processing of product imports using Action Scheduler
 */
class ImportScheduler {
    /**
     * @var PrintifyApiInterface
     */
    private $api;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var SettingsServiceInterface
     */
    private $settings;
    
    /**
     * @var string
     */
    private $shop_id;
    
    /**
     * @const string The table name for Printify to WooCommerce ID mapping
     */
    const ID_MAPPING_TABLE = 'wpwps_printify_product_map';
    
    /**
     * @const string The hook name for product import
     */
    const IMPORT_PRODUCT_HOOK = 'wpwps_import_product';
    
    /**
     * @const string The hook name for batch fetching
     */
    const IMPORT_BATCH_HOOK = 'wpwps_import_product_batch';
    
    /**
     * @const string The hook name for import completion
     */
    const IMPORT_COMPLETE_HOOK = 'wpwps_import_complete';
    
    /**
     * @const string Action group name
     */
    const ACTION_GROUP = 'wpwps-product-import';
    
    /**
     * Constructor
     *
     * @param PrintifyApiInterface $api
     * @param LoggerInterface $logger
     * @param SettingsServiceInterface $settings
     */
    public function __construct(
        PrintifyApiInterface $api,
        LoggerInterface $logger,
        SettingsServiceInterface $settings
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->settings = $settings;
        
        $printify_settings = $this->settings->getPrintifySettings();
        $this->shop_id = $printify_settings['shop_id'];
        
        // Register hooks for Action Scheduler
        $this->register_hooks();
    }
    
    /**
     * Register Action Scheduler hooks
     */
    private function register_hooks() {
        add_action(self::IMPORT_BATCH_HOOK, [$this, 'process_batch'], 10, 2);
        add_action(self::IMPORT_PRODUCT_HOOK, [$this, 'schedule_single_product_import'], 10, 1);
        add_action(self::IMPORT_COMPLETE_HOOK, [$this, 'complete_import'], 10, 1);
    }
    
    /**
     * Set up the Printify ID mapping system
     * Creates a custom table and ensures all required structures exist
     *
     * @return bool True if successful
     */
    public function ensure_printify_id_mapping() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        // Check if the table already exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->logger->log_info('id_mapping', 'Creating Printify ID mapping table');
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                printify_product_id varchar(255) NOT NULL,
                wc_product_id bigint(20) NOT NULL,
                last_synced datetime DEFAULT CURRENT_TIMESTAMP,
                sync_status varchar(50) DEFAULT 'synced',
                PRIMARY KEY  (id),
                UNIQUE KEY printify_product_id (printify_product_id),
                KEY wc_product_id (wc_product_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Verify the table was created
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $this->logger->log_error('id_mapping', 'Failed to create Printify ID mapping table');
                return false;
            }
            
            $this->logger->log_success('id_mapping', 'Successfully created Printify ID mapping table');
        }
        
        return true;
    }
    
    /**
     * Map a Printify product ID to a WooCommerce product ID
     *
     * @param string $printify_id Printify product ID
     * @param int $wc_product_id WooCommerce product ID
     * @param string $status Sync status
     * @return bool Success status
     */
    public function map_printify_to_wc_product($printify_id, $wc_product_id, $status = 'synced') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        // Ensure the table exists
        $this->ensure_printify_id_mapping();
        
        // Store the Printify ID as product meta for quick lookups from WC side
        update_post_meta($wc_product_id, '_printify_product_id', $printify_id);
        
        // Add or update the mapping in our custom table
        $existing = $this->get_wc_product_id($printify_id);
        
        if ($existing) {
            // Update existing mapping
            $result = $wpdb->update(
                $table_name,
                [
                    'wc_product_id' => $wc_product_id,
                    'last_synced' => current_time('mysql'),
                    'sync_status' => $status
                ],
                ['printify_product_id' => $printify_id]
            );
        } else {
            // Create new mapping
            $result = $wpdb->insert(
                $table_name,
                [
                    'printify_product_id' => $printify_id,
                    'wc_product_id' => $wc_product_id,
                    'last_synced' => current_time('mysql'),
                    'sync_status' => $status
                ]
            );
        }
        
        if ($result === false) {
            $this->logger->log_error(
                'id_mapping',
                sprintf('Failed to map Printify ID %s to WC product %d', $printify_id, $wc_product_id)
            );
            return false;
        }
        
        $this->logger->log_info(
            'id_mapping',
            sprintf('Mapped Printify ID %s to WC product %d with status: %s', $printify_id, $wc_product_id, $status)
        );
        
        return true;
    }
    
    /**
     * Get WooCommerce product ID from Printify product ID
     *
     * @param string $printify_id Printify product ID
     * @return int|false WooCommerce product ID or false if not found
     */
    public function get_wc_product_id($printify_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        // Try to get from the mapping table first (faster)
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT wc_product_id FROM $table_name WHERE printify_product_id = %s",
                $printify_id
            )
        );
        
        if ($product_id) {
            return (int) $product_id;
        }
        
        // Fallback to post meta lookup
        $posts = get_posts([
            'post_type' => 'product',
            'meta_key' => '_printify_product_id',
            'meta_value' => $printify_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        if (!empty($posts)) {
            $wc_product_id = $posts[0];
            
            // Update our mapping table for future lookups
            $this->map_printify_to_wc_product($printify_id, $wc_product_id);
            
            return (int) $wc_product_id;
        }
        
        return false;
    }
    
    /**
     * Get Printify product ID from WooCommerce product ID
     *
     * @param int $wc_product_id WooCommerce product ID
     * @return string|false Printify product ID or false if not found
     */
    public function get_printify_product_id($wc_product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        // Try to get from the mapping table first (faster)
        $printify_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT printify_product_id FROM $table_name WHERE wc_product_id = %d",
                $wc_product_id
            )
        );
        
        if ($printify_id) {
            return $printify_id;
        }
        
        // Fallback to post meta
        $printify_id = get_post_meta($wc_product_id, '_printify_product_id', true);
        
        if (!empty($printify_id)) {
            // Update our mapping table for future lookups
            $this->map_printify_to_wc_product($printify_id, $wc_product_id);
            return $printify_id;
        }
        
        return false;
    }
    
    /**
     * Update sync status for a product
     *
     * @param string $printify_id Printify product ID
     * @param string $status Status (synced, error, pending)
     * @return bool Success status
     */
    public function update_sync_status($printify_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        $wc_product_id = $this->get_wc_product_id($printify_id);
        if (!$wc_product_id) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            [
                'sync_status' => $status,
                'last_synced' => current_time('mysql')
            ],
            ['printify_product_id' => $printify_id]
        );
        
        if ($result !== false) {
            // Store sync status in product meta too for WP_Query filtering
            update_post_meta($wc_product_id, '_printify_sync_status', $status);
            update_post_meta($wc_product_id, '_printify_last_sync', current_time('mysql'));
        }
        
        return $result !== false;
    }
    
    /**
     * Get all product mappings with specified status
     *
     * @param string $status Status to filter by (null for all)
     * @param int $limit Max number of results
     * @param int $offset Offset for pagination
     * @return array Array of mappings
     */
    public function get_products_by_status($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        $query = "SELECT * FROM $table_name";
        $args = [];
        
        if ($status !== null) {
            $query .= " WHERE sync_status = %s";
            $args[] = $status;
        }
        
        $query .= " ORDER BY last_synced DESC LIMIT %d OFFSET %d";
        $args[] = $limit;
        $args[] = $offset;
        
        if (empty($args)) {
            $results = $wpdb->get_results($query);
        } else {
            $results = $wpdb->get_results($wpdb->prepare($query, $args));
        }
        
        return $results;
    }
    
    /**
     * Start a full product import
     *
     * @param bool $force Force reimport of existing products
     * @param bool $is_initial Whether this is the initial import
     * @return bool True on successful scheduling, false on error
     */
    public function start_import($force = false, $is_initial = false) {
        if (empty($this->shop_id)) {
            $this->logger->log_error('import', 'Shop ID not configured');
            return false;
        }
        
        // Check if there's already an import in progress
        if ($this->is_import_in_progress() && !$force) {
            $this->logger->log_info('import', 'Import already in progress, skipping');
            return false;
        }
        
        // Set import in progress flag
        update_option('wpwps_import_in_progress', true);
        update_option('wpwps_last_import_started', current_time('mysql'));
        
        if ($is_initial) {
            update_option('wpwps_is_initial_import', true);
        }
        
        try {
            // Schedule the initial batch import
            $this->schedule_batch_import(1);
            
            $this->logger->log_info(
                'import', 
                $is_initial ? 'Initial product import started' : 'Product import started'
            );
            return true;
        } catch (\Exception $e) {
            $this->logger->log_error('import', sprintf('Failed to start import: %s', $e->getMessage()));
            update_option('wpwps_import_in_progress', false);
            return false;
        }
    }
    
    /**
     * Schedule a batch import
     *
     * @param int $page Page number
     * @param int $total_processed Products processed so far
     * @return void
     */
    public function schedule_batch_import($page = 1, $total_processed = 0) {
        as_schedule_single_action(
            time(),
            self::IMPORT_BATCH_HOOK,
            [
                'page' => $page, 
                'total_processed' => $total_processed
            ],
            self::ACTION_GROUP
        );
    }
    
    /**
     * Process a batch of products
     *
     * @param int $page Page number
     * @param int $total_processed Products processed so far
     * @return void
     */
    public function process_batch($page, $total_processed) {
        $batch_size = WPWPS_BATCH_SIZE;
        
        try {
            // Get a batch of products from Printify
            $products = $this->api->get_products($this->shop_id, [
                'page' => $page,
                'limit' => $batch_size
            ]);
            
            if (is_wp_error($products)) {
                $this->logger->log_error('import_batch', $products->get_error_message());
                $this->complete_import(['error' => true, 'message' => $products->get_error_message()]);
                return;
            }
            
            $product_count = count($products);
            $this->logger->log_info('import_batch', sprintf('Processing batch %d with %d products', $page, $product_count));
            
            // If we have products, schedule each for import
            if ($product_count > 0) {
                foreach ($products as $product) {
                    // Only proceed if we have a valid Printify product ID
                    if (!empty($product['id'])) {
                        as_schedule_single_action(
                            time(),
                            self::IMPORT_PRODUCT_HOOK,
                            ['printify_product_id' => $product['id']],
                            self::ACTION_GROUP
                        );
                    } else {
                        $this->logger->log_warning('import_batch', 'Skipping product with missing ID');
                    }
                }
                
                // Update the total processed count
                $new_total = $total_processed + $product_count;
                
                // If we got a full batch, schedule the next batch
                if ($product_count == $batch_size) {
                    $this->schedule_batch_import($page + 1, $new_total);
                } else {
                    // This was the last batch, schedule completion
                    $this->logger->log_info('import_batch', sprintf('All batches processed, total: %d products', $new_total));
                    as_schedule_single_action(
                        time() + 30, // Allow time for product imports to complete
                        self::IMPORT_COMPLETE_HOOK,
                        ['total' => $new_total],
                        self::ACTION_GROUP
                    );
                }
            } else {
                // No products in this batch, we're done
                $this->logger->log_info('import_batch', 'No products found in current batch');
                $this->complete_import(['total' => $total_processed]);
            }
        } catch (\Exception $e) {
            $this->logger->log_error('import_batch', sprintf('Error processing batch: %s', $e->getMessage()));
            $this->complete_import(['error' => true, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Schedule import of a single product using Printify ID
     *
     * @param string $printify_product_id Printify product ID
     * @return void
     */
    public function schedule_single_product_import($printify_product_id) {
        try {
            // Get the product data from Printify
            $product_data = $this->api->get_product($this->shop_id, $printify_product_id);
            
            if (is_wp_error($product_data)) {
                $this->logger->log_error(
                    'import_product', 
                    sprintf('Error fetching product data for %s: %s', $printify_product_id, $product_data->get_error_message())
                );
                $this->update_sync_status($printify_product_id, 'error');
                return;
            }
            
            // First, check if this product already exists in WooCommerce
            $wc_product_id = $this->get_wc_product_id($printify_product_id);
            
            // Set pending status while processing
            $this->update_sync_status($printify_product_id, 'pending');
            
            if ($wc_product_id) {
                // Update existing product
                $this->logger->log_info(
                    'import_product', 
                    sprintf('Updating existing product %d for Printify ID %s', $wc_product_id, $printify_product_id)
                );
                
                // Dispatch to do the actual product update
                do_action('wpwps_do_update_product', $wc_product_id, $product_data);
            } else {
                // Create new product
                $this->logger->log_info(
                    'import_product', 
                    sprintf('Creating new product for Printify ID %s', $printify_product_id)
                );
                
                // Dispatch to do the actual product creation
                do_action('wpwps_do_create_product', $product_data);
            }
            
            $this->logger->log_success(
                'import_product',
                sprintf('Processed product %s', $printify_product_id)
            );
        } catch (\Exception $e) {
            $this->logger->log_error(
                'import_product', 
                sprintf('Error processing Printify product %s: %s', $printify_product_id, $e->getMessage())
            );
            $this->update_sync_status($printify_product_id, 'error');
        }
    }
    
    /**
     * Complete the import process
     *
     * @param array $data Import summary data
     * @return void
     */
    public function complete_import($data) {
        // Mark import as complete
        update_option('wpwps_import_in_progress', false);
        update_option('wpwps_last_import_completed', current_time('mysql'));
        update_option('wpwps_last_import_stats', $data);
        
        $is_initial = get_option('wpwps_is_initial_import', false);
        if ($is_initial) {
            update_option('wpwps_initial_import_complete', true);
            delete_option('wpwps_is_initial_import');
        }
        
        if (!empty($data['error'])) {
            $this->logger->log_error('import_complete', sprintf('Import completed with errors: %s', $data['message']));
        } else {
            $this->logger->log_success(
                'import_complete', 
                sprintf('Import completed successfully, processed %d products', $data['total'])
            );
        }
        
        // Run a final check on all products
        $this->run_post_import_validation();
        
        // Trigger completion action for other integrations
        do_action('wpwps_import_completed', $data);
    }
    
    /**
     * Run validation on all imported products
     * This ensures all mappings are consistent
     *
     * @return void
     */
    private function run_post_import_validation() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        // Check for orphaned WC products
        $orphaned = $wpdb->get_results(
            "SELECT m.wc_product_id, m.printify_product_id 
            FROM $table_name m 
            LEFT JOIN $wpdb->posts p ON m.wc_product_id = p.ID 
            WHERE p.ID IS NULL"
        );
        
        if ($orphaned) {
            foreach ($orphaned as $orphan) {
                $this->logger->log_warning(
                    'validation', 
                    sprintf('Removing orphaned mapping for deleted WC product %d (Printify ID: %s)', 
                        $orphan->wc_product_id, $orphan->printify_product_id)
                );
                
                $wpdb->delete($table_name, ['wc_product_id' => $orphan->wc_product_id]);
            }
        }
        
        // Check for inconsistent product meta
        $products = $wpdb->get_results("SELECT * FROM $table_name");
        
        foreach ($products as $product) {
            $stored_id = get_post_meta($product->wc_product_id, '_printify_product_id', true);
            
            if ($stored_id !== $product->printify_product_id) {
                $this->logger->log_warning(
                    'validation', 
                    sprintf('Fixing inconsistent Printify ID for WC product %d', $product->wc_product_id)
                );
                
                update_post_meta($product->wc_product_id, '_printify_product_id', $product->printify_product_id);
            }
        }
    }
    
    /**
     * Check if an import is in progress
     *
     * @return bool
     */
    public function is_import_in_progress() {
        return (bool) get_option('wpwps_import_in_progress', false);
    }
    
    /**
     * Get import statistics
     *
     * @return array
     */
    public function get_import_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ID_MAPPING_TABLE;
        
        $stats = [
            'in_progress' => $this->is_import_in_progress(),
            'last_started' => get_option('wpwps_last_import_started', ''),
            'last_completed' => get_option('wpwps_last_import_completed', ''),
            'stats' => get_option('wpwps_last_import_stats', [])
        ];
        
        // Add sync status counts
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $stats['status_counts'] = [
                'synced' => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE sync_status = 'synced'"),
                'pending' => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE sync_status = 'pending'"),
                'error' => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE sync_status = 'error'"),
                'total' => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name")
            ];
        } else {
            $stats['status_counts'] = ['synced' => 0, 'pending' => 0, 'error' => 0, 'total' => 0];
        }
        
        // Add pending count
        $stats['pending_actions'] = as_get_scheduled_actions([
            'group' => self::ACTION_GROUP,
            'status' => \ActionScheduler_Store::STATUS_PENDING,
        ], 'count');
        
        return $stats;
    }
    
    /**
     * Cancel all pending imports
     *
     * @return int Number of cancelled actions
     */
    public function cancel_imports() {
        $cancelled = as_unschedule_all_actions('', [], self::ACTION_GROUP);
        update_option('wpwps_import_in_progress', false);
        $this->logger->log_info('import', sprintf('Cancelled %d pending import actions', $cancelled));
        return $cancelled;
    }
    
    /**
     * Start a catchup sync that only processes changed products
     * 
     * @return bool Success status
     */
    public function start_catchup_sync() {
        if ($this->is_import_in_progress()) {
            $this->logger->log_info('import', 'Import already in progress, skipping catchup sync');
            return false;
        }
        
        // Set special catchup mode flag
        update_option('wpwps_catchup_sync', true);
        
        return $this->start_import(true);
    }
}
