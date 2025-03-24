<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrap">
    <div class="wpwps-header">
        <h1><?php echo esc_html__('Support Tickets', 'wp-woocommerce-printify-sync'); ?></h1>
    </div>

    <div class="wpwps-content">
        <div class="wpwps-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('ID', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Subject', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Created', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php echo esc_html__('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>#<?php echo esc_html($ticket->id); ?></td>
                            <td><?php echo esc_html($ticket->subject); ?></td>
                            <td>
                                <span class="wpwps-status wpwps-status-<?php echo esc_attr($ticket->status); ?>">
                                    <?php echo esc_html(ucfirst($ticket->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(human_time_diff(strtotime($ticket->created_at), current_time('timestamp'))); ?> ago</td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-tickets&action=view&id=' . $ticket->id)); ?>" class="button button-small">
                                    <?php echo esc_html__('View', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
