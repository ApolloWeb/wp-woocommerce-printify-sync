<?php
/**
 * Direct dashboard rendering
 * This template uses traditional PHP includes instead of a template engine
 */
defined('ABSPATH') || exit;

// Variables are expected to be provided from the parent scope:
// $current_user, $settings, $stats, $credit_balance, $has_low_credit, $page_title
?>
<div class="wrap wpwps-admin">
    <?php if (defined('WP_DEBUG') && WP_DEBUG && !defined('WPWPS_HIDE_DEBUG_INFO')): ?>
    <div class="wpwps-layout-debug" style="background:#f8f9fa; padding:10px; margin-bottom:20px; border-left:4px solid #007cba;">
        <h4>Direct Include Mode</h4>
        <p>The dashboard is being rendered using direct PHP includes instead of the template engine.</p>
    </div>
    <?php endif; ?>

    <?php if ($has_low_credit): ?>
    <div class="wpwps-notification-bar" style="background: rgba(220, 53, 69, 0.1); color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
        <div class="wpwps-notification-content">
            <i class="fas fa-exclamation-triangle"></i>
            <?php esc_html_e('Your API credit balance is low. Please add funds to avoid service interruption.', 'wp-woocommerce-printify-sync'); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="wpwps-content">
        <div class="wpwps-container">
            <!-- Page Header -->
            <div class="wpwps-page-header">
                <h1 class="wpwps-page-title">
                    <i class="fas fa-tshirt"></i>
                    <?php echo esc_html($page_title); ?>
                </h1>
                
                <div class="wpwps-user-profile">
                    <img src="<?php echo esc_url(get_avatar_url($current_user->ID, ['size' => 40])); ?>" 
                         alt="<?php echo esc_attr($current_user->display_name); ?>" 
                         class="wpwps-user-avatar">
                    <div class="wpwps-user-info">
                        <span class="wpwps-user-name">
                            <a href="<?php echo esc_url(get_edit_profile_url($current_user->ID)); ?>" title="<?php esc_attr_e('Edit your profile', 'wp-woocommerce-printify-sync'); ?>">
                                <?php echo esc_html($current_user->display_name); ?>
                            </a>
                        </span>
                        <span class="wpwps-user-role"><?php echo esc_html(implode(', ', array_map(function($role) {
                            return translate_user_role($role);
                        }, $current_user->roles))); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid mb-4">
                <!-- Total Products Card -->
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-value"><?php echo esc_html(number_format($stats['total_products'])); ?></h3>
                        <p class="stats-label"><?php esc_html_e('Total Products', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                    <div class="stats-trend up">
                        <i class="fas fa-arrow-up"></i>
                        12%
                    </div>
                </div>

                <!-- Sync Rate Card -->
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-value"><?php echo esc_html($stats['sync_rate']); ?>%</h3>
                        <p class="stats-label"><?php esc_html_e('Sync Success Rate', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>

                <!-- Credit Balance Card -->
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-value">$<?php echo esc_html(number_format($stats['credit_balance'], 2)); ?></h3>
                        <p class="stats-label"><?php esc_html_e('API Credit Balance', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                    <div class="stats-trend <?php echo $stats['credit_balance'] < 2 ? 'down' : 'up'; ?>">
                        <i class="fas fa-arrow-<?php echo $stats['credit_balance'] < 2 ? 'down' : 'up'; ?>"></i>
                        5%
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-8">
                    <div class="wpwps-page-content">
                        <h2 class="h5 mb-3"><?php esc_html_e('Sync Performance', 'wp-woocommerce-printify-sync'); ?></h2>
                        <div class="wpwps-chart-container">
                            <canvas id="syncChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="wpwps-page-content">
                        <h2 class="h5 mb-3"><?php esc_html_e('API Usage', 'wp-woocommerce-printify-sync'); ?></h2>
                        <div class="wpwps-chart-container">
                            <canvas id="usageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="wpwps-page-content">
                <h2 class="h5 mb-3"><?php esc_html_e('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Time', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php esc_html_e('Event', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), time())); ?></td>
                                <td><?php esc_html_e('Plugin initialized', 'wp-woocommerce-printify-sync'); ?></td>
                                <td><span class="badge bg-success"><?php esc_html_e('Success', 'wp-woocommerce-printify-sync'); ?></span></td>
                            </tr>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), time() - 3600)); ?></td>
                                <td><?php esc_html_e('Settings updated', 'wp-woocommerce-printify-sync'); ?></td>
                                <td><span class="badge bg-success"><?php esc_html_e('Success', 'wp-woocommerce-printify-sync'); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
