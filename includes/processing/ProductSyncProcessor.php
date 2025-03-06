<?php
/**
 * Product Sync Processor
 *
 * Handles background processing of product sync tasks.
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

class ProductSyncProcessor extends \WP_Background_Process {
    /**
     * Action
     *
     * @var string
     */
    protected $action = 'wpwprintifysync_product_sync';
    
    /**
     * Task
     *
     * @param array $item Queue item to iterate over
     * @return mixed
     */
    protected function task($item) {
        if (empty($item['product_id'])) {
            return false;
        }
        
        $product_id = $item['product_id'];
        $sync_type = $item['sync_type'] ?? 'update';
        $timestamp = $item['timestamp'] ?? date('Y-m-d H:i:s');
        
        try {
            // Get product manager
            $product_manager = \ApolloWeb\WPWooCommercePrintifySync\Products\ProductManager::get_instance();
            
            // Process based on sync type
            switch ($sync_type) {
                case 'create':
                    $result = $product_manager->create_printify_product($product_id);
                    break;
                case 'update':
                    $result = $product_manager->update_product_from_printify($product_id);
                    break;
                case 'delete':
                    $result = $product_manager->delete_printify_product($product_id);
                    break;
                default:
                    $result = false;
                    Logger::get_instance()->warning('Unknown product sync type', array(
                        'product_id' => $product_id,
                        'sync_type' => $sync_type,
                        'timestamp' => $timestamp
                    ));
                    break;
            }
            
            // Log sync status
            if ($result) {
                $this->log_sync_status($product_id, $sync_type, 'success', '', $timestamp);
                
                Logger::get_instance()->info('Product sync processed successfully', array(
                    'product_id' => $product_id,
                    'sync_type' => $sync_type,
                    'timestamp' => $timestamp
                ));
            } else {
                $this->log_sync_status($product_id, $sync_type, 'failed', 'Unknown error', $timestamp);
                
                Logger::get_instance()->error('Product sync failed', array(
                    'product_id' => $product_id,
                    'sync_type' => $sync_type,
                    'timestamp' => $timestamp
                ));
            }
        } catch (\Exception $e) {
            // Log exception
            $this->log_sync_status($product_id, $sync_type, 'failed', $e->getMessage(), $timestamp);
            
            Logger::get_instance()->error('Product sync exception', array(
                'product_id' => $product_id,
                'sync_type' => $sync_type,
                'message' => $e->getMessage(),
                'timestamp' => $timestamp
            ));
        }
        
        return false;
    }
    
    /**
     * Complete
     */
    protected function complete() {
        parent::complete();
        
        // Log completion
        Logger::get_instance()->info('Product sync queue processing completed', array(
            'timestamp' => date('Y-m-d H:i:s')
        ));
        
        // Update last run timestamp
        update_option($this->action . '_last_run', date('Y-m-d H:i:s'));
    }
    
    /**
     * Log sync status
     *
     * @param int $product_id Product ID
     * @param string $sync_type Sync type
     * @param string $status Status (success or failed)
     * @param string $message Error message
     * @param string $timestamp Timestamp
     */
    private function log_sync_status($product_id, $sync_type, $status, $message = '', $timestamp = '') {
        global $wpdb;
        
        if (empty($timestamp)) {
            $timestamp = date('Y-m-d H:i:s');
        }
        
        // Get Printify ID
        $printify_id = get_post_meta($product_id, '_printify_product_id', true);
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwprintifysync_product_sync',
            array(
                'product_id' => $product_id,
                'printify_id' => $printify_id ?: '',
                'sync_type' => $sync_type,
                'status' => $status,
                'message' => $message,
                'created_at' => $timestamp
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
}