<?php
/**
 * Admin dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined( 'ABSPATH' ) || exit;

// Current time display
$current_time = '2025-03-06 01:28:42'; // Using the provided UTC time
$current_user = 'ApolloWeb'; // Using the provided username
?>

<div class="wrap printify-sync-dashboard">
    <h1><?php esc_html_e( 'Printify Sync Dashboard', 'wp-woocommerce-printify-sync' ); ?></h1>
    
    <div class="printify-sync-dashboard-header">
        <div class="printify-sync-meta">
            <p>
                <span class="dashicons dashicons-clock"></span> 
                <?php esc_html_e( 'Current Time (UTC):', 'wp-woocommerce-printify-sync' ); ?> 
                <strong><?php echo esc_html( $current_time ); ?></strong>
            </p>
            <p>
                <span class="dashicons dashicons-admin-users"></span> 
                <?php esc_html_e( 'Logged in as:', 'wp-woocommerce-printify-sync' ); ?> 
                <strong><?php echo esc_html( $current_user ); ?></strong>
            </p>
        </div>
        
        <?php if ( isset( $shop_data ) && ! empty( $shop_data ) ) : ?>
            <div class="printify-sync-shop-info">
                <h2><?php esc_html_e( 'Connected Shop', 'wp-woocommerce-printify-sync' ); ?></h2>
                <p>
                    <strong><?php esc_html_e( 'Shop Name:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                    <?php echo esc_html( $shop_data['name'] ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Shop ID:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                    <?php echo esc_html( $shop_data['id'] ); ?>
                </p>
                <?php if ( isset( $shop_data['sales_channel'] ) ) : ?>
                    <p>
                        <strong><?php esc_html_e( 'Sales Channel:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <?php echo esc_html( $shop_data['sales_channel'] ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="printify-sync-notice notice-warning">
                <p><?php esc_html_e( 'No shop connected. Please go to the Shops page to connect your Printify shop.', 'wp-woocommerce-printify-sync' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-shops' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Connect Shop', 'wp-woocommerce-printify-sync' ); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ( isset( $api_status ) && $api_status === 'connected' ) : ?>
            <div class="printify-sync-api-status connected">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e( 'API Connected', 'wp-woocommerce-printify-sync' ); ?>
            </div>
        <?php else : ?>
            <div class="printify-sync-api-status disconnected">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'API Disconnected', 'wp-woocommerce-printify-sync' ); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="printify-sync-dashboard-widgets">
        <div class="printify-sync-row">
            <!-- Product Sync Widget -->
            <div class="printify-sync-widget product-sync">
                <h3>
                    <span class="dashicons dashicons-products"></span>
                    <?php esc_html_e( 'Product Sync', 'wp-woocommerce-printify-sync' ); ?>
                </h3>
                <div class="printify-sync-widget-content">
                    <p>
                        <strong><?php esc_html_e( 'Total Products:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['total_products'] ) ? esc_html( $stats['total_products'] ) : '0'; ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Pending Sync:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['pending_sync'] ) ? esc_html( $stats['pending_sync'] ) : '0'; ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Last Sync:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $sync_status['last_product_sync'] ) ? esc_html( $sync_status['last_product_sync'] ) : esc_html__( 'Never', 'wp-woocommerce-printify-sync' ); ?></span>
                    </p>
                    <div class="printify-sync-widget-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-products' ) ); ?>" class="button">
                            <?php esc_html_e( 'Manage Products', 'wp-woocommerce-printify-sync' ); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Orders Widget -->
            <div class="printify-sync-widget orders">
                <h3>
                    <span class="dashicons dashicons-cart"></span>
                    <?php esc_html_e( 'Orders', 'wp-woocommerce-printify-sync' ); ?>
                </h3>
                <div class="printify-sync-widget-content">
                    <p>
                        <strong><?php esc_html_e( 'Total Orders:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['total_orders'] ) ? esc_html( $stats['total_orders'] ) : '0'; ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Processing:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['orders_processing'] ) ? esc_html( $stats['orders_processing'] ) : '0'; ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Completed:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['orders_completed'] ) ? esc_html( $stats['orders_completed'] ) : '0'; ?></span>
                    </p>
                    <div class="printify-sync-widget-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-orders' ) ); ?>" class="button">
                            <?php esc_html_e( 'Manage Orders', 'wp-woocommerce-printify-sync' ); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Tickets Widget -->
            <div class="printify-sync-widget tickets">
                <h3>
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <?php esc_html_e( 'Support Tickets', 'wp-woocommerce-printify-sync' ); ?>
                </h3>
                <div class="printify-sync-widget-content">
                    <p>
                        <strong><?php esc_html_e( 'Open Tickets:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['tickets_open'] ) ? esc_html( $stats['tickets_open'] ) : '0'; ?></span>
                    </p>
                    <div class="printify-sync-widget-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-orders' ) ); ?>" class="button">
                            <?php esc_html_e( 'View Tickets', 'wp-woocommerce-printify-sync' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="printify-sync-row">
            <!-- Currency Widget -->
            <div class="printify-sync-widget currency">
                <h3>
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php esc_html_e( 'Currency', 'wp-woocommerce-printify-sync' ); ?>
                </h3>
                <div class="printify-sync-widget-content">
                    <p>
                        <strong><?php esc_html_e( 'USD to Shop Currency:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $stats['currency_rate'] ) ? esc_html( $stats['currency_rate'] ) : '1.00'; ?></span>
                    </p>
                    <div class="printify-sync-widget-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-currency' ) ); ?>" class="button">
                            <?php esc_html_e( 'Currency Settings', 'wp-woocommerce-printify-sync' ); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sync Status Widget -->
            <div class="printify-sync-widget sync-status">
                <h3>
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Sync Status', 'wp-woocommerce-printify-sync' ); ?>
                </h3>
                <div class="printify-sync-widget-content">
                    <p>
                        <strong><?php esc_html_e( 'Last Product Sync:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $sync_status['last_product_sync'] ) ? esc_html( $sync_status['last_product_sync'] ) : esc_html__( 'Never', 'wp-woocommerce-printify-sync' ); ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Last Order Sync:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $sync_status['last_order_sync'] ) ? esc_html( $sync_status['last_order_sync'] ) : esc_html__( 'Never', 'wp-woocommerce-printify-sync' ); ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Last Stock Sync:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $sync_status['last_stock_sync'] ) ? esc_html( $sync_status['last_stock_sync'] ) : esc_html__( 'Never', 'wp-woocommerce-printify-sync' ); ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Webhook Status:', 'wp-woocommerce-printify-sync' ); ?></strong> 
                        <span class="stat"><?php echo isset( $sync_status['webhook_status'] ) ? esc_html( $sync_status['webhook_status'] ) : esc_html__( 'Not configured', 'wp-woocommerce-printify-sync' ); ?></span>
                    </p>
                </div>
            </div>
            
            <!-- Quick Actions Widget -->
            <div class="printify-sync-widget quick-actions">
                <h3>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e( 'Quick Actions', 'wp-woocommerce-printify-sync' ); ?>
                </h3>
                <div class="printify-sync-widget-content">
                    <div class="printify-sync-widget-actions">
                        <button id="refresh-dashboard" class="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Refresh Dashboard', 'wp-woocommerce-printify-sync' ); ?>
                        </button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-testing' ) ); ?>" class="button">
                            <?php esc_html_e( 'Testing Tools', 'wp-woocommerce-printify-sync' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-settings' ) ); ?>" class="button">
                            <?php esc_html_e( 'Settings', 'wp-woocommerce-printify-sync' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders Section -->
    <div class="printify-sync-section">
        <h2><?php esc_html_e( 'Recent Orders', 'wp-woocommerce-printify-sync' ); ?></h2>
        <?php if ( ! empty( $recent_orders ) ) : ?>
            <table class="widefat printify-sync-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order ID', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Printify ID', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Customer', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-woocommerce-printify-sync' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_orders as $order ) : ?>
                        <tr>
                            <td><?php echo esc_html( $order['id'] ); ?></td>
                            <td><?php echo esc_html( $order['printify_id'] ); ?></td>
                            <td><?php echo esc_html( $order['date'] ); ?></td>
                            <td><?php echo esc_html( $order['customer'] ); ?></td>
                            <td><?php echo wp_kses_post( $order['total'] ); ?></td>
                            <td>
                                <span class="order-status status-<?php echo esc_attr( $order['status'] ); ?>">
                                    <?php echo esc_html( wc_get_order_status_name( $order['status'] ) ); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order['id'] . '&action=edit' ) ); ?>" class="button button-small">
                                    <?php esc_html_e( 'View', 'wp-woocommerce-printify-sync' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="printify-sync-view-all">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-orders' ) ); ?>" class="button">
                    <?php esc_html_e( 'View All Orders', 'wp-woocommerce-printify-sync' ); ?>
                </a>
            </p>
        <?php else : ?>
            <div class="printify-sync-notice notice-info">
                <p><?php esc_html_e( 'No recent orders found.', 'wp-woocommerce-printify-sync' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Tickets Section -->
    <?php if ( ! empty( $recent_tickets ) ) : ?>
        <div class="printify-sync-section">
            <h2><?php esc_html_e( 'Recent Tickets', 'wp-woocommerce-printify-sync' ); ?></h2>
            <table class="widefat printify-sync-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Ticket ID', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Related Order', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Subject', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-woocommerce-printify-sync' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_tickets as $ticket ) : ?>
                        <tr>
                            <td><?php echo esc_html( $ticket['id'] ); ?></td>
                            <td>
                                <?php if ( ! empty( $ticket['order_id'] ) ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $ticket['order_id'] . '&action=edit' ) ); ?>">
                                        #<?php echo esc_html( $ticket['order_id'] ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php esc_html_e( 'N/A', 'wp-woocommerce-printify-sync' ); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $ticket['subject'] ); ?></td>
                            <td><?php echo esc_html( $ticket['date'] ); ?></td>
                            <td>
                                <span class="ticket-status status-<?php echo esc_attr( $ticket['status'] ); ?>">
                                    <?php echo esc_html( $ticket['status'] ); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-orders&view=ticket&id=' . $ticket['id'] ) ); ?>" class="button button-small">
                                    <?php esc_html_e( 'View', 'wp-woocommerce-printify-sync' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="printify-sync-view-all">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-orders&tab=tickets' ) ); ?>" class="button">
                    <?php esc_html_e( 'View All Tickets', 'wp-woocommerce-printify-sync' ); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#refresh-dashboard').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true);
            $button.html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Refreshing...', 'wp-woocommerce-printify-sync' ); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync_dashboard',
                    action_type: 'refresh_stats',
                    nonce: PrintifySyncAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats
                        for (var key in response.data.stats) {
                            $('.printify-sync-widget .stat').filter(function() {
                                return $(this).prev('strong').text().trim().replace(':', '') === key;
                            }).text(response.data.stats[key]);
                        }
                        
                        // Show success message
                        alert('<?php esc_html_e( 'Dashboard refreshed successfully!', 'wp-woocommerce-printify-sync' ); ?>');
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Error refreshing dashboard.', 'wp-woocommerce-printify-sync' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error connecting to server.', 'wp-woocommerce-printify-sync' ); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.html('<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh Dashboard', 'wp-woocommerce-printify-sync' ); ?>');
                }
            });
        });
    });
</script>

<style>
.printify-sync-dashboard {
    margin-top: 20px;
}

.printify-sync-dashboard-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.printify-sync-meta {
    display: flex;
    flex-direction: column;
}

.printify-sync-api-status {
    padding: 10px 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.printify-sync-api-status.connected {
    background-color: #d4edda;
    color: #155724;
}

.printify-sync-api-status.disconnected {
    background-color: #f8d7da;
    color: #721c24;
}

.printify-sync-api-status .dashicons {
    margin-right: 5px;
}

.printify-sync-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
    margin-bottom: 20px;
}

.printify-sync-widget {
    flex: 1;
    min-width: 300px;
    margin: 0 10px 20px;
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.printify-sync-widget h3 {
    margin: 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
}

.printify-sync-widget h3 .dashicons {
    margin-right: 5px;
}

.printify-sync-widget-content {
    padding: 15px;
}

.printify-sync-widget-actions {
    margin-top: 15px;
    display: flex;
    justify-content: flex-start;
    gap: 10px;
}

.printify-sync-widget p {
    margin: 5px 0;
}

.printify-sync-notice {
    padding: 10px 15px;
    margin-bottom: 15px;
}

.printify-sync-table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 15px;
}

.printify-sync-table th, 
.printify-sync-table td {
    padding: 8px 12px;
}

.printify-sync-section {
    background-color: #fff;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.printify-sync-view-all {
    text-align: right;
}

.order-status, .ticket-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.order-status.status-completed {
    background-color: #c6e1c6;
    color: #5b841b;
}

.order-status.status-processing {
    background-color: #c8d7e1;
    color: #2e4453;
}

.order-status.status-on-hold {
    background-color: #f8dda7;
    color: #94660c;
}

.ticket-status.status-open {
    background-color: #c8d7e1;
    color: #2e4453;
}

.ticket-status.status-resolved {
    background-color: #c6e1c6;
    color: #5b841b;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spin {
    animation: spin 2s linear infinite;
}

@media (max-width: 768px) {
    .printify-sync-dashboard-header {
        flex-direction: column;
    }
    
    .printify-sync-api-status {
        margin-top: 15px;
    }
    
    .printify-sync-widget {
        min-width: 100%;
    }
}
</style>