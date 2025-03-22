<?php
/**
 * Dashboard template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

global $wpwps_container;
$activity_service = $wpwps_container->get('activity_service');
$recent_activities = $activity_service->getActivities(5);

$api_status = 'unknown';
if (!empty($shop_id)) {
    $api_client = $wpwps_container->get('api_client');
    $test_connection = $api_client->testConnection();
    $api_status = is_wp_error($test_connection) ? 'error' : 'healthy';
}

$action_scheduler = $wpwps_container->get('action_scheduler');
$pending_product_syncs = $action_scheduler->getPendingActionsCount('wpwps_as_sync_product');
$pending_order_syncs = $action_scheduler->getPendingActionsCount('wpwps_as_sync_order');
$pending_emails = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'pending'");
?>

<div class="wrap wpwps-admin-wrap">
    <?php require WPWPS_PLUGIN_DIR . 'templates/partials/dashboard/wpwps-header.php'; ?>
    
    <div class="wpwps-dashboard-container">
        <?php if (empty($shop_id)) : ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php esc_html_e('Your Printify Shop is not configured yet. Please go to the Settings page and set up your API connection.', 'wp-woocommerce-printify-sync'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="btn btn-primary ms-3">
                    <i class="fas fa-cog"></i> <?php esc_html_e('Go to Settings', 'wp-woocommerce-printify-sync'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="wpwps-grid">
                <div class="row gx-4">
                    <div class="col-lg-8">
                        <!-- Stats Cards Row -->
                        <?php require WPWPS_PLUGIN_DIR . 'templates/partials/dashboard/wpwps-stats.php'; ?>
                        
                        <!-- Revenue Chart -->
                        <div class="wpwps-card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-line"></i> <?php esc_html_e('Revenue & Profits', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <div class="chart-container">
                                    <canvas id="revenue-chart"></canvas>
                                </div>
                                <div class="chart-filters mt-3">
                                    <button class="btn btn-sm btn-outline-primary" data-filter="day"><?php esc_html_e('Day', 'wp-woocommerce-printify-sync'); ?></button>
                                    <button class="btn btn-sm btn-outline-primary active" data-filter="week"><?php esc_html_e('Week', 'wp-woocommerce-printify-sync'); ?></button>
                                    <button class="btn btn-sm btn-outline-primary" data-filter="month"><?php esc_html_e('Month', 'wp-woocommerce-printify-sync'); ?></button>
                                    <button class="btn btn-sm btn-outline-primary" data-filter="year"><?php esc_html_e('Year', 'wp-woocommerce-printify-sync'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Recent Activity -->
                        <div class="wpwps-card mb-4">
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
                        
                        <!-- Queue Status -->
                        <div class="wpwps-card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-tasks"></i> <?php esc_html_e('Queue Status', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <div class="queue-status">
                                    <div class="queue-item">
                                        <span class="queue-label"><?php esc_html_e('Product Syncs', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="queue-value"><?php echo esc_html($pending_product_syncs); ?></span>
                                    </div>
                                    <div class="queue-item">
                                        <span class="queue-label"><?php esc_html_e('Order Syncs', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="queue-value"><?php echo esc_html($pending_order_syncs); ?></span>
                                    </div>
                                    <div class="queue-item">
                                        <span class="queue-label"><?php esc_html_e('Email Queue', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="queue-value"><?php echo esc_html($pending_emails); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="wpwps-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-bolt"></i> <?php esc_html_e('Quick Actions', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <div class="d-grid gap-2">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-box me-2"></i> <?php esc_html_e('Manage Products', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-shopping-cart me-2"></i> <?php esc_html_e('Manage Orders', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-cog me-2"></i> <?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-logs')); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-file-alt me-2"></i> <?php esc_html_e('View Logs', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require WPWPS_PLUGIN_DIR . 'templates/partials/footer-version.php'; ?>
</div>
