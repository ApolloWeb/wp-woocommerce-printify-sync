<?php
/**
 * Logs template for WooCommerce Printify Sync Plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */
global $wpdb;
$table_name = $wpdb->prefix . 'wpwps_sync_logs';
$logs = $wpdb->get_results("SELECT time, type, message, status FROM $table_name ORDER BY time DESC LIMIT 50", ARRAY_A);
?>
<div class="wrap">
    <h1><?php esc_html_e('Sync Logs', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="card">
        <table class="widefat fixed striped table table-hover table-striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Type', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Message', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty($logs) ) : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td><?php echo esc_html($log['type']); ?></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                            <td><?php echo esc_html($log['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No logs found.', 'wp-woocommerce-printify-sync'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>