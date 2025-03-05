<?php
/**
 * Admin Orders Management Page Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\OrderHelper;

// Process bulk actions
$action_message = '';
if (isset($_POST['wpwprintifysync_order_action']) && isset($_POST['order_ids']) && is_array($_POST['order_ids'])) {
    check_admin_referer('wpwprintifysync_order_action', 'wpwprintifysync_order_nonce');
    
    $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
    $order_ids = array_map('intval', $_POST['order_ids']);
    
    if ($action === 'sync') {
        // Schedule order sync
        foreach ($order_ids as $order_id) {
            wp_schedule_single_event(
                time() + 5,
                'wpwprintifysync_submit_order_to_printify',
                [
                    'order_id' => $order_id,
                    'user' => 'ApolloWeb',
                    'timestamp' => '2025-03-05 19:05:03'
                ]
            );
        }
        
        $action_message = sprintf(
            _n(
                'Scheduled %d order for submission to Printify.',
                'Scheduled %d orders for submission to Printify.',
                count($order_ids),
                'wp-woocommerce-printify-sync'
            ),
            count($order_ids)
        );
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Get orders - HPOS compatible approach
$query_args = [
    'limit' => 20,
    'paged' => $paged,
    'return' => 'objects',
    'paginate' => true
];

// Add status filter
if ($status_filter) {
    if ($status_filter === 'pending') {
        // Orders not yet sent to Printify
        $query_args['status'] = ['processing'];
        $query_args['meta_query'] = [
            [
                'key' => '_printify_order_id',
                'compare' => 'NOT EXISTS'
            ]
        ];
    } elseif ($status_filter === 'synced') {
        // Orders sent to Printify
        $query_args['meta_query'] = [
            [
                'key' => '_printify_order_id',
                'compare' => 'EXISTS'
            ]
        ];
    } elseif ($status_filter === 'shipped') {
        // Orders shipped from Printify
        $query_args['meta_query'] = [
            [
                'key' => '_printify_tracking_number',
                'compare' => 'EXISTS'
            ]
        ];
    } else {
        // Normal status filter
        $query_args['status'] = [$status_filter];
    }
}

// Add search
if ($search) {
    $query_args['s'] = $search;
}

// Get orders
$orders_query = wc_get_orders($query_args);
$orders = $orders_query->orders;
$max_pages = $orders_query->max_num_pages;

// Custom order statuses
$custom_statuses = [
    'printify-processing' => __('Printify Processing', 'wp-woocommerce-printify-sync'),
    'printify-printed' => __('Printify Printed', 'wp-woocommerce-printify-sync'),
    'printify-shipped' => __('Printify Shipped', 'wp-woocommerce-printify-sync')
];

// Include custom statuses in status filter options
$wc_statuses = wc_get_order_statuses();
$all_statuses = array_merge($wc_statuses, $custom_statuses);
?>

<div class="wrap wpwprintifysync-orders-page">
    <h1 class="wp-heading-inline"><?php _e('Printify Orders', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <?php if ($action_message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($action_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="wpwprintifysync-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="wpwprintifysync-orders">
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending Sync', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="synced" <?php selected($status_filter, 'synced'); ?>><?php _e('Synced with Printify', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="shipped" <?php selected($status_filter, 'shipped'); ?>><?php _e('Shipped from Printify', 'wp-woocommerce-printify-sync'); ?></option>
                
                <?php foreach ($all_statuses as $status => $label): ?>
                    <option value="<?php echo esc_attr(str_replace('wc-', '', $status)); ?>" <?php selected($status_filter, str_replace('wc-', '', $status)); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search orders', 'wp-woocommerce-printify-sync'); ?>">
            
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'wp-woocommerce-printify-sync'); ?>">
        </form>
    </div>
    
    <form method="post" action="" id="wpwprintifysync-orders-form">
        <?php wp_nonce_field('wpwprintifysync_order_action', 'wpwprintifysync_order_nonce'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'wp-woocommerce-printify-sync'); ?></label>
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value=""><?php _e('Bulk Actions', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="sync"><?php _e('Submit to Printify', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
                <input type="submit" name="wpwprintifysync_order_action" class="button action" value="<?php esc_attr_e('Apply', 'wp-woocommerce-printify-sync'); ?>">
            </div>
            
            <div class="tablenav-pages">
                <?php
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous', 'wp-woocommerce-printify-sync'),
                    'next_text' => __('Next &raquo;', 'wp-woocommerce-printify-sync'),
                    'total' => $max_pages,
                    'current' => $paged
                ]);
                
                if ($page_links) {
                    echo '<span class="pagination-links">' . $page_links . '</span>';
                }
                ?>
            </div>
            
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th class="manage-column column-order_id"><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                    <th class="manage-column column-date"><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                    <th class="manage-column column-status"><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th class="manage-column column-printify_id"><?php _e('Printify ID', 'wp-woocommerce-printify-sync'); ?></th>
                    <th class="manage-column column-tracking"><?php _e('Tracking', 'wp-woocommerce-printify-sync'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7"><?php _e('No orders found.', 'wp-woocommerce-printify-sync'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        // Get order data - HPOS compatible approach
                        $order_id = $order->get_id();
                        $order_number = $order->get_order_number();
                        $date = $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
                        $status = $order->get_status();
                        $status_label = wc_get_order_status_name($status);
                        
                        // Get Printify data
                        $printify_order_id = $order->get_meta('_printify_order_id');
                        $tracking_number = $order->get_meta('_printify_tracking_number');
                        $tracking_url = $order->get_meta('_printify_tracking_url');
                        ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order_id); ?>">
                            </td>
                            <td class="order_id column-order_id">
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>">
                                    #<?php echo esc_html($order_number); ?>
                                </a>
                            </td>
                            <td class="date column-date">
                                <?php echo esc_html($date); ?>
                            </td>
                            <td class="status column-status">
                                <span class="order-status status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html($status_label); ?>
                                </span>
                            </td>
                            <td class="printify_id column-printify_id">
                                <?php if ($printify_order_id): ?>
                                    <?php echo esc_html($printify_order_id); ?>
                                <?php else: ?>
                                    <span class="not-synced"><?php _e('Not synced', 'wp-woocommerce-printify-sync'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="tracking column-tracking">
                                <?php if ($tracking_number): ?>
                                    <?php if ($tracking_url): ?>
                                        <a href="<?php echo esc_url($tracking_url); ?>" target="_blank">
                                            <?php echo esc_html($tracking_number); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($tracking_number); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="no-tracking"><?php _e('No tracking', 'wp-woocommerce-printify-sync'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions column-actions">
                                <?php if (!$printify_order_id && $status === 'processing'): ?>
                                    <a href="#" class="button submit-to-printify" data-order-id="<?php echo esc_attr($order_id); ?>">
                                        <?php _e('Submit to Printify', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                <?php elseif ($printify_order_id): ?>
                                    <a href="#" class="button check-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-printify-id="<?php echo esc_attr($printify_order_id); ?>">
                                        <?php _e('Check Status', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'wp-woocommerce-printify-sync'); ?></label>
                <select name="bulk_action2" id="bulk-action-selector-bottom">
                    <option value=""><?php _e('Bulk Actions', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="sync"><?php _e('Submit to Printify', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
                <input type="submit" name="wpwprintifysync_order_action" class="button action" value="<?php esc_attr_e('Apply', 'wp-woocommerce-printify-sync'); ?>">
            </div>
            
            <div class="tablenav-pages">
                <?php
                if ($page_links) {
                    echo '<span class="pagination-links">' . $page_links . '</span>';
                }
                ?>
            </div>
            
            <br class="clear">
        </div>
    </form>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Select all functionality
            $('#cb-select-all-1').on('change', function() {
                $('input[name="order_ids[]"]').prop('checked', $(this).prop('checked'));
            });
            
            // Submit single order to Printify
            $('.submit-to-printify').on('click', function(e) {
                e.preventDefault();
                
                var orderId = $(this).data('order-id');
                var button = $(this);
                
                button.prop('disabled', true).text('<?php _e('Submitting...', 'wp-woocommerce-printify-sync'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpwprintifysync_sync_order',
                        nonce: '<?php echo wp_create_nonce('wpwprintifysync-admin'); ?>',
                        order_id: orderId
                    },
                    success: function(response) {
                        if (response.success) {
                            button.closest('tr').find('.printify_id').html(response.data.printify_id);
                            button.replaceWith(
                                '<a href="#" class="button check-status" data-order-id="' + orderId + '" data-printify-id="' + response.data.printify_id + '">' +
                                '<?php _e('Check Status', 'wp-woocommerce-printify-sync'); ?>' +
                                '</a>'
                            );
                        } else {
                            alert(response.data.message || '<?php _e('Error submitting order.', 'wp-woocommerce-printify-sync'); ?>');
                            button.prop('disabled', false).text('<?php _e('Submit to Printify', 'wp-woocommerce-printify-sync'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Network error. Please try again.', 'wp-woocommerce-printify-sync'); ?>');
                        button.prop('disabled', false).text('<?php _e('Submit to Printify', 'wp-woocommerce-printify-sync'); ?>');
                    }
                });
            });
            
            // Check order status
            $('.check-status').on('click', function(e) {
                e.preventDefault();
                
                var orderId = $(this).data('order-id');
                var printifyId = $(this).data('printify-id');
                var button = $(this);
                
                button.prop('disabled', true).text('<?php _e('Checking...', 'wp-woocommerce-printify-sync'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpwprintifysync_check_order_status',
                        nonce: '<?php echo wp_create_nonce('wpwprintifysync-admin'); ?>',
                        order_id: orderId,
                        printify_id: printifyId
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.tracking_number) {
                                var trackingHtml = response.data.tracking_url ?
                                    '<a href="' + response.data.tracking_url + '" target="_blank">' + response.data.tracking_number + '</a>' :
                                    response.data.tracking_number;
                                button.closest('tr').find('.tracking').html(trackingHtml);
                            }
                            
                            if (response.data.status) {
                                button.closest('tr').find('.status').html(response.data.status_label);
                            }
                            
                            alert(response.data.message || '<?php _e('Order status updated.', 'wp-woocommerce-printify-sync'); ?>');
                        } else {
                            alert(response.data.message || '<?php _e('Error checking order status.', 'wp-woocommerce-printify-sync'); ?>');
                        }
                        
                        button.prop('disabled', false).text('<?php _e('Check Status', 'wp-woocommerce-printify-sync'); ?>');
                    },
                    error: function() {
                        alert('<?php _e('Network error. Please try again.', 'wp-woocommerce-printify-sync'); ?>');
                        button.prop('disabled', false).text('<?php _e('Check Status', 'wp-woocommerce-printify-sync'); ?>');
                    }
                });
            });
        });
    </script>
</div>