<?php
/** @var array $recentActivity */
?>

<div class="wpwps-card">
    <div class="card-header">
        <h3><?php _e('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h3>
    </div>
    <div class="card-content">
        <?php if (empty($recentActivity)): ?>
            <p class="no-data"><?php _e('No recent activity', 'wp-woocommerce-printify-sync'); ?></p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($recentActivity as $activity): ?>
                    <li class="activity-item">
                        <span class="activity-icon <?php echo esc_attr($activity['type']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></span>
                        </span>
                        <div class="activity-details">
                            <p class="activity-message"><?php echo esc_html($activity['message']); ?></p>
                            <span class="activity-time"><?php echo esc_html(human_time_diff(strtotime($activity['timestamp']))); ?> ago</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>