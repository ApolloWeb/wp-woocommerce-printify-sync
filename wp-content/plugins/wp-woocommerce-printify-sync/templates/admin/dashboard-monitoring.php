<div class="wpwps-monitoring-dashboard">
    <div class="wpwps-stats-grid">
        <div class="wpwps-stat-card">
            <h3><?php esc_html_e('API Health', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="wpwps-stat-value">
                <span class="wpwps-status wpwps-status-<?php echo esc_attr($api_status); ?>">
                    <?php echo esc_html($api_status_label); ?>
                </span>
            </div>
            <div class="wpwps-stat-meta">
                <?php echo esc_html(sprintf(__('Last checked: %s', 'wp-woocommerce-printify-sync'), $last_api_check)); ?>
            </div>
        </div>

        <div class="wpwps-stat-card">
            <h3><?php esc_html_e('Queue Status', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="wpwps-stat-chart">
                <!-- Chart.js implementation -->
            </div>
        </div>
    </div>

    <div class="wpwps-error-log">
        <h3><?php esc_html_e('Recent Errors', 'wp-woocommerce-printify-sync'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Type', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Message', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_errors as $error): ?>
                    <tr>
                        <td><?php echo esc_html($error->time); ?></td>
                        <td><?php echo esc_html($error->type); ?></td>
                        <td><?php echo esc_html($error->message); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
