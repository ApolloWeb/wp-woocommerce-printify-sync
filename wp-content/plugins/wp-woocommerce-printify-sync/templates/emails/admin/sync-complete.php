<?php
/**
 * Admin Sync Complete Email
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php esc_html_e('Product synchronization with Printify has completed.', 'wp-woocommerce-printify-sync'); ?>
</p>

<h2><?php esc_html_e('Sync Results', 'wp-woocommerce-printify-sync'); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Batch ID:', 'wp-woocommerce-printify-sync'); ?></th>
        <td class="td" style="text-align:left;"><?php echo esc_html($sync_results['batch_id']); ?></td>
    </tr>
    <tr>
        <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Products Synced:', 'wp-woocommerce-printify-sync'); ?></th>
        <td class="td" style="text-align:left;"><?php echo esc_html($sync_results['synced']); ?></td>
    </tr>
    <tr>
        <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Failed Syncs:', 'wp-woocommerce-printify-sync'); ?></th>
        <td class="td" style="text-align:left;"><?php echo esc_html($sync_results['failed']); ?></td>
    </tr>
    <tr>
        <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Completion Time:', 'wp-woocommerce-printify-sync'); ?></th>
        <td class="td" style="text-align:left;"><?php echo esc_html($sync_results['completion_time']); ?></td>
    </tr>
</table>

<?php if (!empty($sync_results['failed'])): ?>
    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-sync-logs')); ?>" class="button button-primary">
            <?php esc_html_e('View Sync Logs', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </p>
<?php endif; ?>

<?php
do_action('woocommerce_email_footer', $email);