<?php
/**
 * Order Sync Processor
 *
 * Handles background processing of order sync tasks.
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

class OrderSyncProcessor extends \WP_Background_Process {
    /**
     * Action
     *
     * @var string
     */
    protected $action = 'wpwprintifysync_order_sync';
    
    /**
     * Task
     *
     * @param array $item Queue item to iterate over
     * @return mixed
     */
    protected function task($item) {
        if (empty($item['order_id'])) {
            return false;
        }
        
        $order_id = $item['order_id'];
        $sync_type = $item['sync_type'] ?? 'create';
        $timestamp = $item['timestamp'] ?? date('Y-m-d H:i:s');
        
        try {
            // Get order manager
            $order_manager = \ApolloWeb\WPWooCommercePrintifySync\Orders\OrderManager::get_instance();
            
            // Process based on sync type
            switch ($sync_type) {
                case 'create':
                    $result = $order_manager->send_order_to_printify($order_id);
                    break;
                case 'update':
                    $result = $order_manager->update_order_in_printify($order_id);
                    break;
                case 'cancel':
                    $result = $order_manager->cancel_order_in_printify($order_id);
                    break;
                default:
                    $result = false;
                    Logger::get_instance()->warning('Unknown order sync type', array(
                        'order_id' => $order_id,
                        'sync_type' => $sync_type,
                        'timestamp' => $timestamp
                    ));
                    break;
            }
            
            // Log sync status
            if ($result) {
                $this->log_sync_status($order_id, $sync_type, 'success', '', $timestamp);
                
                Logger::get_instance()->info('Order sync processed successfully', array(
                    'order_id' => $order_id,
                    'sync_type' => $sync_type,
                    'timestamp' => $timestamp
                ));
            } else {
                $this->log_sync_status($order_id, $sync_type, 'failed', 'Unknown error', $timestamp);
                
                Logger::get_instance()->error('Order sync failed', array(
                    'order_id' => $order_id,
                    'sync_type' => $sync_type,
                    'timestamp' => $timestamp
                ));
            }
        } catch (\Exception $e) {
            // Log exception
            $this->log_sync_status($order_id, $sync_type, 'failed', $e->getMessage(), $timestamp);
            
            Logger::get_instance()->error('Order sync exception', array(
                'order_id' => $order_id,
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
        Logger::get_instance()->info('Order sync queue processing completed', array(
            'timestamp' => date('Y-m-d H:i:s')
        ));
        
        // Update last run timestamp
        update_option($this->action . '_last_run', date('Y-m-d H:i:s'));
    }
    
    /**
     * Log sync status
     *
     * @param int $order_id Order ID
     * @param string $sync_type Sync type
     * @param string $status Status (success or failed)
     * @param string $message Error message
     * @param string $timestamp Timestamp
     */
    private function log_sync_status($order_id, $sync_type, $status, $message = '', $timestamp = '') {
        global $wpdb;
        
        if (empty($timestamp)) {
            $timestamp = date('Y-m-d H:i:s');
        }
        
        // Get Printify ID
        $printify_id = get_post_meta($order_id, '_printify_order_id', true);
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwprintifysync_order_sync',
            array(
                'order_id' => $order_id,
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