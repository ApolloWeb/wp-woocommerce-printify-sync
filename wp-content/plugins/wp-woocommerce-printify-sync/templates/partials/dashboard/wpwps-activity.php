<?php defined('ABSPATH') || exit; ?>

<!-- Recent Activity -->
<div class="col-md-4">
    <div class="card mb-4 wpwps-card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-history"></i> <?php esc_html_e('Recent Activity', 'wp-woocommerce-printify-sync'); ?>
            </h5>
            <div class="activity-list">
                <?php if (empty($recent_activities)) : ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <?php esc_html_e('No recent activity found.', 'wp-woocommerce-printify-sync'); ?>
                        </div>
                    </div>
                <?php else : ?>
                    <?php foreach ($recent_activities as $activity) : ?>
                        <div class="activity-item">
                            <div class="activity-time">
                                <?php echo esc_html($activity_service->getRelativeTime($activity['created_at'])); ?>
                            </div>
                            <div class="activity-content">
                                <i class="<?php echo esc_attr($activity_service->getActivityIcon($activity['type'])); ?>"></i>
                                <?php echo esc_html($activity['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
