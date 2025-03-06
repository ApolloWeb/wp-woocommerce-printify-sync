<?php
/**
 * Background Processor
 *
 * Handles background processing tasks.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Processing
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Processing;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BackgroundProcessor {
    /**
     * Singleton instance
     *
     * @var BackgroundProcessor
     */
    private static $instance = null;
    
    /**
     * Current timestamp
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Current user
     *
     * @var string
     */
    private $user;
    
    /**
     * Queue name
     *
     * @var string
     */
    private $queue_name;
    
    /**
     * Get singleton instance
     *
     * @return BackgroundProcessor
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->timestamp = '2025-03-05 20:34:32';
        $this->user = 'ApolloWeb';
        $this->queue_name = 'wpwprintifysync_queue';
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Load WP Background Processing library if not already included
        if (!class_exists('WP_Background_Process')) {
            require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/libraries/wp-background-processing/wp-background-processing.php';
        }
        
        // Initialize background processors
        $this->product_sync_processor = new ProductSyncProcessor();
        $this->order_sync_processor = new OrderSyncProcessor();
        
        // Handle AJAX requests
        add_action('wp_ajax_wpwprintifysync_check_queue_status', array($this, 'ajax_check_queue_status'));
    }
    
    /**
     * AJAX handler for checking queue status
     */
    public function ajax_check_queue_status() {
        // Check nonce
        check_ajax_referer('wpwprintifysync-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync')));
            return;
        }
        
        // Get queue stats
        $product_queue = $this->get_queue_status('product_sync');
        $order_queue = $this->get_queue_status('order_sync');
        
        wp_send_json_success(array(
            'product_sync' => $product_queue,
            'order_sync' => $order_queue
        ));
    }
    
    /**
     * Get queue status
     *
     * @param string $queue Queue name
     * @return array Queue stats
     */
    private function get_queue_status($queue) {
        $queue_key = $this->queue_name . '_' . $queue;
        $queue_data = get_option($queue_key . '_data', array());
        $queue_in_progress = get_option($queue_key . '_in_progress', array());
        
        return array(
            'items_count' => count($queue_data),
            'in_progress' => !empty($queue_in_progress),
            'last_run' => get_option($queue_key . '_last_run', __('Never', 'wp-woocommerce-printify-sync'))
        );
    }
    
    /**
     * Queue product sync
     *
     * @param int $product_id WooCommerce product ID
     * @param string $sync_type Sync type (create, update, delete)
     * @return bool Success status
     */
    public function queue_product_sync($product_id, $sync_type = 'update') {
        if (empty($product_id)) {
            return false;
        }
        
        $this->product_sync_processor->push_to_queue(array(
            'product_id' => $product_id,
            'sync_type' => $sync_type,
            'timestamp' => $this->timestamp
        ));
        
        $this->product_sync_processor->save()->dispatch();
        
        return true;
    }
    
    /**
     * Queue order sync
     *
     * @param int $order_id WooCommerce order ID
     * @param string $sync_type Sync type (create, update, cancel)
     * @return bool Success status
     */
    public function queue_order_sync($order_id, $sync_type = 'create') {
        if (empty($order_id)) {
            return false;
        }
        
        $this->order_sync_processor->push_to_queue(array(
            'order_id' => $order_id,
            'sync_type' => $sync_type,
            'timestamp' => $this->timestamp
        ));
        
        $this->order_sync_processor->save()->dispatch();
        
        return true;
    }
    
    /**
     * Queue bulk product sync
     *
     * @param array $product_ids WooCommerce product IDs
     * @param string $sync_type Sync type (create, update, delete)
     * @return bool Success status
     */
    public function queue_bulk_product_sync($product_ids, $sync_type = 'update') {
        if (empty($product_ids) || !is_array($product_ids)) {
            return false;
        }
        
        foreach ($product_ids as $product_id) {
            $this->product_sync_processor->push_to_queue(array(
                'product_id' => $product_id,
                'sync_type' => $sync_type,
                'timestamp' => $this->timestamp
            ));
        }
        
        $this->product_sync_processor->save()->dispatch();
        
        return true;
    }
    
    /**
     * Queue bulk order sync
     *
     * @param array $order_ids WooCommerce order IDs
     * @param string $sync_type Sync type (create, update, cancel)
     * @return bool Success status
     */
    public function queue_bulk_order_sync($order_ids, $sync_type = 'create') {
        if (empty($order_ids) || !is_array($order_ids)) {
            return false;
        }
        
        foreach ($order_ids as $order_id) {
            $this->order_sync_processor->push_to_queue(array(
                'order_id' => $order_id,
                'sync_type' => $sync_type,
                'timestamp' => $this->timestamp
            ));
        }
        
        $this->order_sync_processor->save()->dispatch();
        
        return true;
    }
}