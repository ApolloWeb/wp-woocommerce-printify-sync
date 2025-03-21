<?php
/**
 * Orders template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap wpwps-admin-page wpwps-orders-page">
    <h1 class="wp-heading-inline">
        <i class="fas fa-shopping-cart"></i> <?php esc_html_e('Printify Sync - Orders', 'wp-woocommerce-printify-sync'); ?>
    </h1>
    
    <?php if (!empty($shop_name)) : ?>
        <p class="wpwps-shop-name">
            <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
        </p>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <div class="wpwps-alert-container"></div>
    
    <?php if (empty($shop_name)) : ?>
        <div class="wpwps-notice-box">
            <h2><?php esc_html_e('Setup Required', 'wp-woocommerce-printify-sync'); ?></h2>
            <p><?php esc_html_e('You need to configure your Printify API settings to manage orders.', 'wp-woocommerce-printify-sync'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="button button-primary">
                <i class="fas fa-cog"></i> <?php esc_html_e('Configure Settings', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    <?php else : ?>
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-primary">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_stats['total']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Total Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-success">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_stats['processing']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Processing Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-info">
                            <i class="fas fa-sync"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_stats['pending']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Pending Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-warning">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_stats['completed']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Completed Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" id="order-search" placeholder="<?php esc_attr_e('Search Orders', 'wp-woocommerce-printify-sync'); ?>">
                    <button class="btn btn-outline-secondary" type="button" id="search-button"><?php esc_html_e('Search', 'wp-woocommerce-printify-sync'); ?></button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <select class="form-select" id="order-status-filter">
                        <option value=""><?php esc_html_e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                        <option value="on-hold"><?php esc_html_e('On Hold', 'wp-woocommerce-printify-sync'); ?></option>
                        
                        <optgroup label="<?php esc_attr_e('Pre-production', 'wp-woocommerce-printify-sync'); ?>">
                            <option value="awaiting-evidence"><?php esc_html_e('Awaiting Customer Evidence', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="submit-order"><?php esc_html_e('Submit Order', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="action-required"><?php esc_html_e('Action Required', 'wp-woocommerce-printify-sync'); ?></option>
                        </optgroup>
                        
                        <optgroup label="<?php esc_attr_e('Production', 'wp-woocommerce-printify-sync'); ?>">
                            <option value="in-production"><?php esc_html_e('In Production', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="has-issues"><?php esc_html_e('Has Issues', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="canceled-provider"><?php esc_html_e('Canceled by Provider', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="canceled-other"><?php esc_html_e('Canceled (Various Reasons)', 'wp-woocommerce-printify-sync'); ?></option>
                        </optgroup>
                        
                        <optgroup label="<?php esc_attr_e('Shipping', 'wp-woocommerce-printify-sync'); ?>">
                            <option value="ready-ship"><?php esc_html_e('Ready to Ship', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="shipped"><?php esc_html_e('Shipped', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="on-the-way"><?php esc_html_e('On the Way', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="available-pickup"><?php esc_html_e('Available for Pickup', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="out-delivery"><?php esc_html_e('Out for Delivery', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="delivery-attempt"><?php esc_html_e('Delivery Attempt', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="shipping-issue"><?php esc_html_e('Shipping Issue', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="return-sender"><?php esc_html_e('Return to Sender', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="delivered"><?php esc_html_e('Delivered', 'wp-woocommerce-printify-sync'); ?></option>
                        </optgroup>
                        
                        <optgroup label="<?php esc_attr_e('Refunds & Reprints', 'wp-woocommerce-printify-sync'); ?>">
                            <option value="refund-awaiting-evidence"><?php esc_html_e('Refund Awaiting Evidence', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="refund-requested"><?php esc_html_e('Refund Requested', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="refund-approved"><?php esc_html_e('Refund Approved', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="refund-declined"><?php esc_html_e('Refund Declined', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="reprint-awaiting-evidence"><?php esc_html_e('Reprint Awaiting Evidence', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="reprint-requested"><?php esc_html_e('Reprint Requested', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="reprint-approved"><?php esc_html_e('Reprint Approved', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="reprint-declined"><?php esc_html_e('Reprint Declined', 'wp-woocommerce-printify-sync'); ?></option>
                        </optgroup>
                        
                        <optgroup label="<?php esc_attr_e('Standard', 'wp-woocommerce-printify-sync'); ?>">
                            <option value="pending"><?php esc_html_e('Pending', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="processing"><?php esc_html_e('Processing', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="completed"><?php esc_html_e('Completed', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="cancelled"><?php esc_html_e('Cancelled', 'wp-woocommerce-printify-sync'); ?></option>
                        </optgroup>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" id="filter-button"><?php esc_html_e('Filter', 'wp-woocommerce-printify-sync'); ?></button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> <?php esc_html_e('Recent Orders', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="recent-orders">
                            <?php if (empty($recent_orders)) : ?>
                                <p class="text-muted"><?php esc_html_e('No recent orders.', 'wp-woocommerce-printify-sync'); ?></p>
                            <?php else : ?>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Order ID', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php esc_html_e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php esc_html_e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php esc_html_e('Items', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php esc_html_e('Printify IDs', 'wp-woocommerce-printify-sync'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order) : ?>
                                            <tr>
                                                <td><a href="<?php echo esc_url(get_edit_post_link($order->order_id)); ?>"><?php echo esc_html($order->order_id); ?></a></td>
                                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->post_date))); ?></td>
                                                <td><?php echo esc_html(wc_get_order_status_name($order->post_status)); ?></td>
                                                <td><?php echo esc_html(wc_price($order->total)); ?></td>
                                                <td><?php echo esc_html($order->items); ?></td>
                                                <td><?php echo esc_html($order->printify_ids); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
