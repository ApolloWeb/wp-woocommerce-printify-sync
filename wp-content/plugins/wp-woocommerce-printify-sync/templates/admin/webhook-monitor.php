<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-webhook-monitor">
    <h1><?php _e('Webhook Monitor', 'wp-woocommerce-printify-sync'); ?></h1>

    <!-- Health Status -->
    <div class="wpwps-health-status <?php echo esc_attr($health['status']); ?>">
        <div class="health-indicator">
            <span class="status-dot"></span>
            <h3><?php _e('Webhook Health', 'wp-woocommerce-printify-sync'); ?></h3>
            <p class="status-message"><?php echo esc_html($health['message']); ?></p>
        </div>
        <div class="health-metrics">
            <div class="metric">
                <span class="label"><?php _e('Success Rate', 'wp-woocommerce-printify-sync'); ?></span>
                <span class="value"><?php echo esc_html($stats['success_rate']); ?>%</span>
            </div>
            <div class="metric">
                <span class="label"><?php _e('Avg Response', 'wp-woocommerce-printify-sync'); ?></span>
                <span class="value"><?php echo esc_html($stats['average_response_time']); ?>ms</span>
            </div>
        </div>
    </div>

    <!-- Webhook Stats -->
    <div class="wpwps-stats-grid">
        <div class="stat-card">
            <h4><?php _e('Total Webhooks', 'wp-woocommerce-printify-sync'); ?></h4>
            <div class="stat-value"><?php echo esc_html($stats['total_webhooks']); ?></div>
            <div class="stat-label"><?php _e('Last 24 hours', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
        <div class="stat-card">
            <h4><?php _e('Active Webhooks', 'wp-woocommerce-printify-sync'); ?></h4>
            <div class="stat-value"><?php echo esc_html($stats['active_webhooks']); ?></div>
            <div class="stat-label"><?php _e('Currently registered', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
        <div class="stat-card <?php echo $stats['failed_webhooks'] > 0 ? 'error' : ''; ?>">
            <h4><?php _e('Failed Webhooks', 'wp-woocommerce-printify-sync'); ?></h4>
            <div class="stat-value"><?php echo esc_html($stats['failed_webhooks']); ?></div>
            <div class="stat-label"><?php _e('Require attention', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
        <div class="stat-card">
            <h4><?php _e('Webhook Volume', 'wp-woocommerce-printify-sync'); ?></h4>
            <div class="stat-value"><?php echo esc_html($stats['webhook_volume']); ?></div>
            <div class="stat-label"><?php _e('Requests per hour', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="wpwps-recent-events">
        <h3><?php _e('Recent Webhook Events', 'wp-woocommerce-printify-sync'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Topic', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Response Time', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Details', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentEvents as $event): ?>
                <tr>
                    <td><?php echo esc_html(human_time_diff(strtotime($event['timestamp']))); ?> ago</td>
                    <td><?php echo esc_html($event['topic']); ?></td>
                    <td>
                        <span class="wpwps-status-badge <?php echo esc_attr($event['status']); ?>">
                            <?php echo esc_html($event['status']); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($event['response_time']); ?>ms</td>
                    <td>
                        <button class="button-link view-details" 
                                data-event-id="<?php echo esc_attr($event['id']); ?>">
                            <?php _e('View Details', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Event Details Modal -->
    <div id="wpwps-event-details" class="wpwps-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><?php _e('Event Details', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="event-details-content"></div>
        </div>
    </div>
</div>