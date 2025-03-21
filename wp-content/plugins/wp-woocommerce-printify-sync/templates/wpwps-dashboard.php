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

// Get recent activities if needed for dashboard display
global $wpwps_container;
$activity_service = $wpwps_container->get('activity_service');
$recent_activities = $activity_service->getActivities(5);

// Get API status
$api_status = 'unknown';
if (!empty($shop_id)) {
    $api_client = $wpwps_container->get('api_client');
    $test_connection = $api_client->testConnection();
    $api_status = is_wp_error($test_connection) ? 'error' : 'healthy';
}

// Get email queue count
$email_queue = 0;
global $wpdb;
$email_queue = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'pending'");
?>
<div class="wrap wpwps-admin-wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-tshirt"></i> <?php echo esc_html__('Printify Sync - Dashboard', 'wp-woocommerce-printify-sync'); ?>
    </h1>
    
    <?php if (!empty($shop_name)) : ?>
    <div class="wpwps-shop-info">
        <span class="wpwps-shop-badge">
            <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
        </span>
    </div>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if (empty($shop_id)) : ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <?php esc_html_e('Your Printify Shop is not configured yet. Please go to the Settings page and set up your API connection.', 'wp-woocommerce-printify-sync'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="button button-primary"><?php esc_html_e('Go to Settings', 'wp-woocommerce-printify-sync'); ?></a>
    </div>
    <?php else : ?>
    
    <div class="wpwps-dashboard-container">
        <div class="row">
            <!-- Stats Cards -->
            <div class="col-md-12">
                <div class="row">
                    <!-- Products Card -->
                    <div class="col-md-3">
                        <div class="card mb-4 wpwps-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-box"></i> <?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <h2 class="card-stat"><?php echo esc_html($product_count ?? '0'); ?></h2>
                                <p class="card-text"><?php esc_html_e('Total synced products', 'wp-woocommerce-printify-sync'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>" class="btn btn-primary btn-sm">
                                    <?php esc_html_e('View Products', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders Card -->
                    <div class="col-md-3">
                        <div class="card mb-4 wpwps-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-shopping-cart"></i> <?php esc_html_e('Orders', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <h2 class="card-stat"><?php echo esc_html($order_count ?? '0'); ?></h2>
                                <p class="card-text"><?php esc_html_e('Total synced orders', 'wp-woocommerce-printify-sync'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="btn btn-primary btn-sm">
                                    <?php esc_html_e('View Orders', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tickets Card -->
                    <div class="col-md-3">
                        <div class="card mb-4 wpwps-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-ticket-alt"></i> <?php esc_html_e('Support Tickets', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <h2 class="card-stat"><?php echo esc_html($ticket_count ?? '0'); ?></h2>
                                <p class="card-text"><?php esc_html_e('Active support tickets', 'wp-woocommerce-printify-sync'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-tickets')); ?>" class="btn btn-primary btn-sm">
                                    <?php esc_html_e('View Tickets', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Status Card -->
                    <div class="col-md-3">
                        <div class="card mb-4 wpwps-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-plug"></i> <?php esc_html_e('API Status', 'wp-woocommerce-printify-sync'); ?>
                                </h5>
                                <h2 class="card-stat">
                                    <?php if (isset($api_status) && $api_status === 'healthy') : ?>
                                        <span class="status-healthy"><i class="fas fa-check-circle"></i></span>
                                    <?php else : ?>
                                        <span class="status-unknown"><i class="fas fa-question-circle"></i></span>
                                    <?php endif; ?>
                                </h2>
                                <p class="card-text"><?php esc_html_e('Printify API Connection', 'wp-woocommerce-printify-sync'); ?></p>
                                <button class="btn btn-primary btn-sm" id="wpwps-test-api">
                                    <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="col-md-8">
                <div class="card mb-4 wpwps-card">
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
                        <a href="#" class="btn btn-link btn-sm"><?php esc_html_e('View All Activity', 'wp-woocommerce-printify-sync'); ?></a>
                    </div>
                </div>
                
                <!-- Queue Status -->
                <div class="card mb-4 wpwps-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tasks"></i> <?php esc_html_e('Queue Status', 'wp-woocommerce-printify-sync'); ?>
                        </h5>
                        <div class="queue-status">
                            <div class="queue-item">
                                <span class="queue-label"><?php esc_html_e('Product Sync', 'wp-woocommerce-printify-sync'); ?></span>
                                <span class="queue-value"><?php echo esc_html($product_queue ?? '0'); ?> <?php esc_html_e('pending', 'wp-woocommerce-printify-sync'); ?></span>
                            </div>
                            <div class="queue-item">
                                <span class="queue-label"><?php esc_html_e('Order Sync', 'wp-woocommerce-printify-sync'); ?></span>
                                <span class="queue-value"><?php echo esc_html($order_queue ?? '0'); ?> <?php esc_html_e('pending', 'wp-woocommerce-printify-sync'); ?></span>
                            </div>
                            <div class="queue-item">
                                <span class="queue-label"><?php esc_html_e('Email Queue', 'wp-woocommerce-printify-sync'); ?></span>
                                <span class="queue-value"><?php echo esc_html($email_queue ?? '0'); ?> <?php esc_html_e('pending', 'wp-woocommerce-printify-sync'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>
