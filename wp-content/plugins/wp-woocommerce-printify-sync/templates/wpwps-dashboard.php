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
?>
<div class="wrap wpwps-admin-page wpwps-dashboard-page">
    <h1 class="wp-heading-inline">
        <i class="fas fa-tachometer-alt"></i> <?php esc_html_e('Printify Sync - Dashboard', 'wp-woocommerce-printify-sync'); ?>
    </h1>
    
    <?php if (!empty($shop_name)) : ?>
        <p class="wpwps-shop-name">
            <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
        </p>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <div class="wpwps-alert-container"></div>
    
    <?php if (empty($shop_name)) : ?>
        <div class="wpwps-notice-box">
            <h2><?php esc_html_e('Setup Required', 'wp-woocommerce-printify-sync'); ?></h2>
            <p><?php esc_html_e('You need to configure your Printify API settings to start syncing products.', 'wp-woocommerce-printify-sync'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="button button-primary">
                <i class="fas fa-cog"></i> <?php esc_html_e('Configure Settings', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="row mb-4">
            <!-- Stats Cards -->
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-primary">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($product_counts['printify']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Printify Products', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-success">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_counts['processing']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Processing Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-info">
                            <i class="fas fa-sync"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_counts['pending']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Pending Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-icon bg-warning">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="stats-number"><?php echo esc_html($order_counts['completed']); ?></h3>
                        <p class="stats-text"><?php esc_html_e('Completed Orders', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Charts -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2><i class="fas fa-chart-line"></i> <?php esc_html_e('Analytics', 'wp-woocommerce-printify-sync'); ?></h2>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary date-range" data-range="7d"><?php esc_html_e('7 Days', 'wp-woocommerce-printify-sync'); ?></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary date-range active" data-range="30d"><?php esc_html_e('30 Days', 'wp-woocommerce-printify-sync'); ?></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary date-range" data-range="90d"><?php esc_html_e('90 Days', 'wp-woocommerce-printify-sync'); ?></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-filters mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary chart-type active" data-type="sales"><?php esc_html_e('Sales', 'wp-woocommerce-printify-sync'); ?></button>
                                <button type="button" class="btn btn-sm btn-outline-primary chart-type" data-type="orders"><?php esc_html_e('Orders', 'wp-woocommerce-printify-sync'); ?></button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="wpwps-chart" height="300"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <div class="chart-loading d-none">
                                <div class="wpwps-spinner"></div> <?php esc_html_e('Loading data...', 'wp-woocommerce-printify-sync'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Logs -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> <?php esc_html_e('Recent API Activity', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="recent-logs">
                            <?php if (empty($recent_logs)) : ?>
                                <p class="text-muted"><?php esc_html_e('No recent API activity.', 'wp-woocommerce-printify-sync'); ?></p>
                            <?php else : ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($recent_logs as $log) : ?>
                                        <li class="log-item">
                                            <span class="log-time"><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log->created_at))); ?></span>
                                            <span class="log-endpoint"><?php echo esc_html($log->endpoint); ?></span>
                                            <span class="log-status status-<?php echo ($log->status_code >= 200 && $log->status_code < 300) ? 'success' : 'error'; ?>">
                                                <?php echo esc_html($log->status_code); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="btn btn-sm btn-outline-secondary mt-3">
                                    <?php esc_html_e('View All Logs', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Quick Actions -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> <?php esc_html_e('Quick Actions', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>" class="quick-action-card">
                                    <i class="fas fa-sync"></i>
                                    <span><?php esc_html_e('Sync Products', 'wp-woocommerce-printify-sync'); ?></span>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="quick-action-card">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span><?php esc_html_e('Manage Orders', 'wp-woocommerce-printify-sync'); ?></span>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-shipping')); ?>" class="quick-action-card">
                                    <i class="fas fa-truck"></i>
                                    <span><?php esc_html_e('Shipping Settings', 'wp-woocommerce-printify-sync'); ?></span>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="quick-action-card">
                                    <i class="fas fa-cog"></i>
                                    <span><?php esc_html_e('API Settings', 'wp-woocommerce-printify-sync'); ?></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
