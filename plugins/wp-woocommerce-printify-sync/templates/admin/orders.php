<div class="wrap">
    <h1><?php _e('Printify Orders', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-orders-container">
        <div class="wpwps-orders-filters">
            <select id="wpwps-status-filter">
                <option value=""><?php _e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="pending"><?php _e('Pending', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="processing"><?php _e('Processing', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="shipped"><?php _e('Shipped', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="cancelled"><?php _e('Cancelled', 'wp-woocommerce-printify-sync'); ?></option>
            </select>

            <input type="text" id="wpwps-search" placeholder="<?php esc_attr_e('Search orders...', 'wp-woocommerce-printify-sync'); ?>">
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('WC Order', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Printify Order', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Created', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $orders = wc_get_orders([
                    'limit' => 50,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'meta_key' => '_printify_order_id',
                    'meta_compare' => 'EXISTS'
                ]);

                foreach ($orders as $order) {
                    $printifyOrderId = $order->get_meta('_printify_order_id');
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">#<?php echo esc_html($order->get_order_number()); ?></a>
                        </td>
                        <td><?php echo esc_html($printifyOrderId); ?></td>
                        <td><?php echo esc_html($order->get_status()); ?></td>
                        <td><?php echo esc_html($order->get_date_created()->format('Y-m-d H:i:s')); ?></td>
                        <td>
                            <?php if ($order->get_status() !== 'cancelled') : ?>
                                <button class="button wpwps-cancel-order" data-order-id="<?php echo esc_attr($printifyOrderId); ?>">
                                    <?php _e('Cancel', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>