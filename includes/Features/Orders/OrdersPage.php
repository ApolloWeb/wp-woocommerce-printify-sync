<?php
/**
 * Orders Page
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Features\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Features\Orders;

<<<<<<< HEAD
if (!defined('
=======
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * OrdersPage class
 */
class OrdersPage {
    /**
     * Render the orders page
     */
    public function render() {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/orders-page.php';
        
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
        echo '<h1><i class="fas fa-shopping-cart"></i> Orders</h1>';
        echo '<p>Manage your WooCommerce to Printify orders here.</p>';
        
        echo '<div class="orders-container">';
        echo '<div class="order-filters">';
        echo '<h2>Filter Orders</h2>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="printify-orders">';
        
        echo '<div class="filter-row">';
        echo '<label for="status">Status:</label>';
        echo '<select id="status" name="status">';
        echo '<option value="">All Statuses</option>';
        echo '<option value="pending">Pending</option>';
        echo '<option value="processing">Processing</option>';
        echo '<option value="shipped">Shipped</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="filter-row">';
        echo '<label for="date_from">From:</label>';
        echo '<input type="date" id="date_from" name="date_from">';
        echo '</div>';
        
        echo '<div class="filter-row">';
        echo '<label for="date_to">To:</label>';
        echo '<input type="date" id="date_to" name="date_to">';
        echo '</div>';
        
        echo '<div class="filter-actions">';
        echo '<button type="submit" class="button button-secondary">Apply Filters</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        
        echo '<div class="orders-table">';
        echo '<h2>Orders</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Order ID</th>';
        echo '<th>Date</th>';
        echo '<th>Customer</th>';
        echo '<th>Status</th>';
        echo '<th>Total</th>';
        echo '<th>Printify Status</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // Sample order data for display
        echo '<tr>';
        echo '<td>1001</td>';
        echo '<td>' . esc_html($current_datetime) . '</td>';
        echo '<td>John Doe</td>';
        echo '<td>Processing</td>';
        echo '<td>$24.99</td>';
        echo '<td>Sent to production</td>';
        echo '<td><a href="#">View</a> | <a href="#">Sync</a></td>';
        echo '</tr>';
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '</div>'; // .orders-container
        echo '</div>'; // .wrap
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
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
