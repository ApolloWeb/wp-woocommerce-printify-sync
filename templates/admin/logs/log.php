<?php
/**
 * Logs template
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
    <i class="fas fa-sync-alt me-1"></i> ' . esc_html__('Refresh Logs', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form class="row g-2" method="get">
            <input type="hidden" name="page" value="wpwprintifysync-logs">
            
            <div class="col-12 col-md-3 col-lg-2">
                <select name="log_level" class="form-select form-select-sm">
                    <option value=""><?php esc_html_e('All Levels', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="info" <?php selected(isset($_GET['log_level']) ? $_GET['log_level'] : '', 'info'); ?>><?php esc_html_e('Info', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="warning" <?php selected(isset($_GET['log_level']) ? $_GET['log_level'] : '', 'warning'); ?>><?php esc_html_e('Warning', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="error" <?php selected(isset($_GET['log_level']) ? $_GET['log_level'] : '', 'error'); ?>><?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
            </div>
            
            <div class="col-12 col-md-4 col-lg-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search logs...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><?php esc_html_e('Search', 'wp-woocommerce-printify-sync'); ?></button>
                </div>
            </div>
            
            <div class="col-12 col-md-2 col-lg-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100"><?php esc_html_e('Filter', 'wp-woocommerce-printify-sync'); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th style="width: 60px;"><?php esc_html_e('Level', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Message', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Context', 'wp-woocommerce-printify-sync'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?php echo esc_attr($log['level_color']); ?>">
                                <?php echo esc_html($log['level']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log['date']); ?></td>
                        <td><?php echo esc_html($log['message']); ?></td>
                        <td><?php echo esc_html($log['context']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4" class="text-center"><?php esc_html_e('No logs found.', 'wp-woocommerce-printify-sync'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Logs', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-file-alt',
    'card_actions' => $card_actions,
));