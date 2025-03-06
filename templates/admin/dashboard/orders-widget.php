<?php
/**
 * Recent Orders Dashboard Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Get recent Printify orders - HPOS compatible
global $wpdb;
$table_name = $wpdb->prefix . 'wpwprintifysync_order_mapping';

// Check if table exists
$recent_orders = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
    $recent_orders = $wpdb->get_results(
        "SELECT order_id, printify_order_id, status, updated_at 
        FROM $table_name 
        ORDER BY updated_at DESC 
        LIMIT 5"
    );
}

// Current timestamp for display
$current_timestamp = '2025-03-05 19:24:55';
?>

<div class="wpwprintifysync-orders-widget">
    <div class="wpwprintifysync-widget-header">
        <h3><?php _e('Recent Printify Orders', 'wp-woocommerce-printify-sync'); ?></h3>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>" class="wpwprintifysync-view-all">
            <?php _e('View All', 'wp-woocommerce-printify-sync'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </div>
    
    <?php if (empty($recent_orders)): ?>
        <div class="wpwprintifysync-no-data">
            <p><?php _e('No recent Printify orders found.', 'wp-woocommerce-printify-sync'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>" class="button button-secondary">
                <?php _e('Manage Orders', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Printify ID', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <?php 
                    // Get WC Order object using WooCommerce abstraction (HPOS compatible)
                    $wc_order = wc_get_order($order->order_id);
                    $order_number = $wc_order ? $wc_order->get_order_number() : $order->order_id;
                    $order_status_class = $wc_order ? $wc_order->get_status() : '';
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->order_id . '&action=edit')); ?>">
                                #<?php echo esc_html($order_number); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($order->printify_order_id); ?></td>
                        <td>
                            <span class="order-status status-<?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html(ucfirst($order->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(human_time_diff(strtotime($order->updated_at), current_time('timestamp'))); ?> <?php _e('ago', 'wp-woocommerce-printify-sync'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="wpwprintifysync-orders-summary">
            <?php
            // Get order counts
            $pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
            $processing_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'processing'");
            $shipped_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'shipped'");
            ?>
            
            <div class="wpwprintifysync-order-count">
                <span class="wpwprintifysync-order-badge status-pending"><?php echo $pending_orders; ?></span>
                <span class="wpwprintifysync-order-label"><?php _e('Pending', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
            
            <div class="wpwprintifysync-order-count">
                <span class="wpwprintifysync-order-badge status-processing"><?php echo $processing_orders; ?></span>
                <span class="wpwprintifysync-order-label"><?php _e('Processing', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
            
            <div class="wpwprintifysync-order-count">
                <span class="wpwprintifysync-order-badge status-shipped"><?php echo $shipped_orders; ?></span>
                <span class="wpwprintifysync-order-label"><?php _e('Shipped', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .wpwprintifysync-orders-widget {
        position: relative;
    }
    .wpwprintifysync-widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .wpwprintifysync-widget-header h3 {
        margin: 0;
        padding: 0;
    }
    .wpwprintifysync-view-all {
        text-decoration: none;
        display: flex;
        align-items: center;
    }
    .wpwprintifysync-view-all .dashicons {
        font-size: 14px;
        height: 14px;
        width: 14px;
    }
    .wpwprintifysync-no-data {
        text-align: center;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 4px;
    }
    .wpwprintifysync-no-data p {
        margin-bottom: 15px;
        color: #757575;
    }
    .order-status {
        display: inline-block;
        padding: 3px 6px;
        font-size: 12px;
        border-radius: 3px;
        font-weight: 500;
    }
    .status-pending {
        background-color: #fff8e1;
        color: #ff8f00;
    }
    .status-processing {
        background-color: #e1f5fe;
        color: #0288d1;
    }
    .status-shipped {
        background-color: #e6f9e6;
        color: #1e7e1e;
    }
    .status-cancelled {
        background-color: #fafafa;
        color: #757575;
    }
    .wpwprintifysync-orders-summary {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #f0f0f0;
    }
    .wpwprintifysync-order-count {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }
    .wpwprintifysync-order-badge {
        font-size: 18px;
        font-weight: bold;
        min-width: 35px;
        height: 35px;
        line-height: 35px;
        text-align: center;
        border-radius: 50%;
        margin-bottom: 5px;
    }
    .wpwprintifysync-order-label {
        font-size: 12px;
        color: #555;
    }
</style>