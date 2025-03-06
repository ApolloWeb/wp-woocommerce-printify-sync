<?php
/**
 * System Status template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Action buttons for the card
$card_actions = '
<a href="#" class="btn btn-sm btn-primary">
    <i class="fas fa-sync-alt me-1"></i> ' . esc_html__('Refresh Status', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php esc_html_e('System Information', 'wp-woocommerce-printify-sync'); ?></h5>
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th><?php esc_html_e('WordPress Version:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('PHP Version:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(phpversion()); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('MySQL Version:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html($wpdb->db_version()); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('WP Memory Limit:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(WP_MEMORY_LIMIT); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('WP Debug Mode:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(WP_DEBUG ? __('Enabled', 'wp-woocommerce-printify-sync') : __('Disabled', 'wp-woocommerce-printify-sync')); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php esc_html_e('Plugin Information', 'wp-woocommerce-printify-sync'); ?></h5>
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Plugin Version:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(WPWPRINTIFYSYNC_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Plugin Directory:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(WPWPRINTIFYSYNC_PLUGIN_DIR); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Plugin URL:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_url(WPWPRINTIFYSYNC_PLUGIN_URL); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Log Directory:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html(WPWPRINTIFYSYNC_LOG_DIR); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('System Status', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-info-circle',
    'card_actions' => $card_actions,
));