<?php
/** @var array $recentOrders */
?>

<div class="wpwps-card">
    <div class="card-header">
        <h3><?php _e('Recent Orders', 'wp-woocommerce-printify-sync'); ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($recentOrders)): ?>
            <p class="no-data"><?php _e('No recent orders', 'wp-woocommerce-printify-sync'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Customer', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($order['edit_url']); ?>">#<?php echo esc_html($order['id']); ?></a>
                            </td>
                            <td>
                                <span class="order-status status-<?php echo esc_attr($order['status']); ?>">
                                    <?php echo esc_html($order['status_label']); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($order['customer_name']); ?></td>
                            <td><?php echo wc_price($order['total']); ?></td>
                            <td><?php echo esc_html($order['date']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="<?php echo esc_url($order['edit_url']); ?>" class="button button-small">
                                        <?php _e('View', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                    <?php if ($order['printify_url']): ?>
                                        <a href="<?php echo esc_url($order['printify_url']); ?>" class="button button-small" target="_blank">
                                            <?php _e('Printify', 'wp-woocommerce-printify-sync'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>