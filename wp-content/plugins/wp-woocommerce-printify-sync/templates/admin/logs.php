<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrap">
    <div class="wpwps-header">
        <h1><?php echo esc_html__('System Logs', 'wp-woocommerce-printify-sync'); ?></h1>
        <div class="wpwps-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-logs&action=download')); ?>" class="button">
                <i class="fas fa-download"></i> <?php echo esc_html__('Download Logs', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    </div>

    <div class="wpwps-content">
        <div class="wpwps-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Time', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Level', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Message', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Context', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                            <td>
                                <span class="wpwps-status wpwps-status-<?php echo esc_attr(strtolower($log->level)); ?>">
                                    <?php echo esc_html($log->level); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td><pre><?php echo esc_html($log->context ? json_encode(json_decode($log->context), JSON_PRETTY_PRINT) : ''); ?></pre></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
