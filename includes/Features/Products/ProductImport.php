<?php
/**
 * Product Import Page
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Features\Products
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Features\Products;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * ProductImport class
 */
class ProductImport {
    /**
     * Render the products import page
     */
    public function render() {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/products-import.php';
        
        if (file_exists($template_path)) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template_path;
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1><i class="fas fa-shirt"></i> Product Import</h1>';
        echo '<p>Import products from Printify to your WooCommerce store.</p>';
        
        // Basic import form
        echo '<form method="post" action="" class="printify-import-form">';
        echo '<h2>Import Settings</h2>';
        
        echo '<div class="form-row">';
        echo '<label for="shop_id">Select Shop:</label>';
        echo '<select id="shop_id" name="shop_id" required>';
        echo '<option value="">-- Select a Shop --</option>';
        // Shops would be dynamically loaded here
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<label for="import_type">Import Type:</label>';
        echo '<select id="import_type" name="import_type">';
        echo '<option value="new">New Products Only</option>';
        echo '<option value="all">All Products</option>';
        echo '<option value="selected">Selected Products</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="submit" class="button button-primary">Start Import</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
}
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: }
#
#
# Commit Hash 16c804f
#
