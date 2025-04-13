<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Handles AJAX requests for product operations
 */
class ProductAjax {
    /**
     * @var PrintifyApiInterface
     */
    private $api;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var ImportScheduler
     */
    private $import_scheduler;
    
    /**
     * Constructor
     *
     * @param PrintifyApiInterface $api
     * @param LoggerInterface $logger
     * @param ImportScheduler $import_scheduler
     */
    public function __construct(
        PrintifyApiInterface $api,
        LoggerInterface $logger,
        ImportScheduler $import_scheduler
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->import_scheduler = $import_scheduler;
    }
    
    /**
     * Register AJAX hooks
     */
    public function register_hooks() {
        // Admin AJAX hooks
        add_action('wp_ajax_wpwps_sync_product', [$this, 'handle_sync_product']);
        add_action('wp_ajax_wpwps_get_product_status', [$this, 'handle_get_product_status']);
        add_action('wp_ajax_wpwps_start_sync', [$this, 'handle_start_sync']);
        add_action('wp_ajax_wpwps_check_sync_status', [$this, 'handle_check_sync_status']);
        add_action('wp_ajax_wpwps_cancel_sync', [$this, 'handle_cancel_sync']);
    }
    
    /**
     * Handle product sync AJAX request
     */
    public function handle_sync_product() {
        // Check nonce
        if (!check_ajax_referer('wpwps_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get product ID from request
        $printify_product_id = isset($_POST['printify_product_id']) ? sanitize_text_field($_POST['printify_product_id']) : '';
        
        if (empty($printify_product_id)) {
            wp_send_json_error(['message' => __('Product ID is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Schedule immediate product import
            as_schedule_single_action(
                time(),
                ImportScheduler::IMPORT_PRODUCT_HOOK,
                ['printify_product_id' => $printify_product_id],
                ImportScheduler::ACTION_GROUP
            );
            
            $this->logger->log_info(
                'ajax', 
                sprintf('Product sync scheduled for product ID: %s', $printify_product_id)
            );
            
            wp_send_json_success([
                'message' => __('Product sync has been scheduled', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            $this->logger->log_error('ajax', 'Error scheduling product sync: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error scheduling product sync', 'wp-woocommerce-printify-sync'),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle getting product status AJAX request
     */
    public function handle_get_product_status() {
        // Check nonce
        if (!check_ajax_referer('wpwps_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get product IDs from request
        $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        
        if (empty($product_ids) || !is_array($product_ids)) {
            wp_send_json_error(['message' => __('Product IDs are required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            $status_data = [];
            
            foreach ($product_ids as $printify_product_id) {
                $printify_product_id = sanitize_text_field($printify_product_id);
                $wc_product_id = $this->import_scheduler->get_wc_product_id_from_printify_id($printify_product_id);
                
                $status = [
                    'printify_id' => $printify_product_id,
                    'wc_id' => $wc_product_id,
                    'status' => 'unknown'
                ];
                
                if ($wc_product_id) {
                    $sync_status = get_post_meta($wc_product_id, '_printify_sync_status', true) ?: 'unknown';
                    $last_sync = get_post_meta($wc_product_id, '_printify_last_sync', true) ?: '';
                    
                    $status['status'] = $sync_status;
                    $status['last_sync'] = $last_sync;
                }
                
                $status_data[] = $status;
            }
            
            wp_send_json_success([
                'products' => $status_data
            ]);
        } catch (\Exception $e) {
            $this->logger->log_error('ajax', 'Error getting product status: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error retrieving product status', 'wp-woocommerce-printify-sync'),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle start sync AJAX request
     */
    public function handle_start_sync() {
        // Check nonce
        if (!check_ajax_referer('wpwps_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get sync options from request
        $force = isset($_POST['force']) && $_POST['force'] === 'true';
        
        try {
            // Start import process
            $this->import_scheduler->start_import($force);
            
            wp_send_json_success([
                'message' => __('Synchronization process has been started', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            $this->logger->log_error('ajax', 'Error starting sync: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error starting synchronization process', 'wp-woocommerce-printify-sync'),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle check sync status AJAX request
     */
    public function handle_check_sync_status() {
        // Check nonce
        if (!check_ajax_referer('wpwps_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Get sync status
            $status = $this->import_scheduler->get_sync_status();
            
            wp_send_json_success($status);
        } catch (\Exception $e) {
            $this->logger->log_error('ajax', 'Error checking sync status: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error checking synchronization status', 'wp-woocommerce-printify-sync'),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle cancel sync AJAX request
     */
    public function handle_cancel_sync() {
        // Check nonce
        if (!check_ajax_referer('wpwps_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Cancel import process
            $this->import_scheduler->cancel_import();
            
            wp_send_json_success([
                'message' => __('Synchronization process has been canceled', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            $this->logger->log_error('ajax', 'Error canceling sync: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error canceling synchronization process', 'wp-woocommerce-printify-sync'),
                'error' => $e->getMessage()
            ]);
        }
    }
}
