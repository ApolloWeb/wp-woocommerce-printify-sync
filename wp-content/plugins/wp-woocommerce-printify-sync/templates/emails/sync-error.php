<?php
/**
 * Product Sync Error Email
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php esc_html_e('Product synchronization errors have been detected:', 'wp-woocommerce-printify-sync'); ?>
</p>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th class="td" scope="col"><?php esc_html_e('Product', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="td" scope="col"><?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="td" scope="col"><?php esc_html_e('Time', 'wp-woocommerce-printify-sync'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($sync_errors as $error): ?>
            <tr>
                <td class="td">
                    <?php echo esc_html($error['product_name']); ?>
                    <br>
                    <small><?php echo esc_html($error['product_id']); ?></small>
                </td>
                <td class="td"><?php echo esc_html($error['message']); ?></td>
                <td class="td"><?php echo esc_html($error['timestamp']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p>
    <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-sync-logs')); ?>">
        <?php esc_html_e('View Sync Logs', 'wp-woocommerce-printify-sync'); ?>
    </a>
</p>

<?php do_action('woocommerce_email_footer', $email); ?>