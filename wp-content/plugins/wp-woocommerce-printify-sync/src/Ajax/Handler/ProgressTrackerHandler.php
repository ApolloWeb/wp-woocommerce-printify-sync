<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

class ProgressTrackerHandler extends AbstractAjaxHandler
{
    /**
     * Get import progress
     */
    public function getImportProgress(): void
    {
        try {
            $shopId = $this->getShopId();
            
            // Check if import has completed
            $completed = get_transient('wpwps_import_completed_' . $shopId);
            if ($completed) {
                wp_send_json_success([
                    'status' => 'completed',
                    'message' => $completed['message'],
                    'timestamp' => $completed['timestamp']
                ]);
                return;
            }
            
            // Get import progress
            $progress = get_transient('wpwps_import_progress_' . $shopId);
            if (!$progress) {
                wp_send_json_error([
                    'message' => 'No import in progress',
                    'status' => 'not_started'
                ]);
                return;
            }
            
            wp_send_json_success([
                'status' => 'in_progress',
                'progress' => $progress
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'Error getting import progress: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get image import progress
     */
    public function getImageImportProgress(): void
    {
        try {
            $productId = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
            
            if (!$productId) {
                wp_send_json_error([
                    'message' => 'Product ID is required',
                    'status' => 'error'
                ]);
                return;
            }
            
            // Get image import progress
            $progressKey = 'wpwps_import_progress_' . $productId;
            $progress = get_transient($progressKey);
            
            if (!$progress) {
                wp_send_json_error([
                    'message' => 'No image import in progress for this product',
                    'status' => 'not_started'
                ]);
                return;
            }
            
            wp_send_json_success([
                'status' => 'in_progress',
                'progress' => $progress
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'Error getting image import progress: ' . $e->getMessage()
            ]);
        }
    }
}
