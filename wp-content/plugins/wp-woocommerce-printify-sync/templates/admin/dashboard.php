<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-dashboard">
    <h1><?php _e('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-status-cards">
        <!-- API Health Card -->
        <div class="wpwps-card <?php echo esc_attr($api_health['status']); ?>">
            <h3><?php _e('API Health', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="wpwps-card-content">
                <div class="wpwps-status-indicator">
                    <span class="status-dot"></span>
                    <?php echo esc_html($api_health['message']); ?>
                </div>
                <div class="wpwps-metrics">
                    <div class="metric">
                        <span class="label"><?php _e('Response Time', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($api_health['response_time']); ?>ms</span>
                    </div>
                    <div class="metric">
                        <span class="label"><?php _e('Success Rate', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($api_health['success_rate']); ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhook Stats Card -->
        <div class="wpwps-card">
            <h3><?php _e('Webhook Activity', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="wpwps-card-content">
                <div class="wpwps-webhook-stats">
                    <div class="stat">
                        <span class="label"><?php _e('Total Today', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($webhook_stats['total_today']); ?></span>
                    </div>
                    <div class="stat">
                        <span class="label"><?php _e('Success Rate', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($webhook_stats['success_rate']); ?>%</span>
                    </div>
                    <div class="stat">
                        <span class="label"><?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($webhook_stats['failed']); ?></span>
                    </div>
                </div>
                <div id="webhook-chart"></div>
            </div>
        </div>

        <!-- Sync Status Card -->
        <div class="wpwps-card">
            <h3><?php _e('Sync Status', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="wpwps-card-content">
                <div class="wpwps-sync-stats">
                    <div class="stat">
                        <span class="label"><?php _e('Products Synced', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($sync_status['products_synced']); ?></span>
                    </div>
                    <div class="stat">
                        <span class="label"><?php _e('Orders Synced', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($sync_status['orders_synced']); ?></span>
                    </div>
                    <div class="stat">
                        <span class="label"><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html(human_time_diff(strtotime($sync_status['last_sync']))); ?> ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="wpwps-recent-activity">
        <h3><?php _e('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Event', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Details', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_activity as $activity): ?>
                <tr>
                    <td><?php echo esc_html(human_time_diff(strtotime($activity['created_at']))); ?> ago</td>
                    <td><?php echo esc_html($activity['event']); ?></td>
                    <td>
                        <span class="wpwps-status-badge <?php echo esc_attr($activity['status']); ?>">
                            <?php echo esc_html($activity['status']); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($activity['message']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>