<?php 
defined('ABSPATH') || exit;
$this->extend('wpwps-app');

// Get current user info
$current_user = wp_get_current_user();
$user_roles = array_map(function($role) {
    return translate_user_role($role);
}, $current_user->roles);
?>

<?php $this->section('content'); ?>
<!-- Basic inline styles to ensure content is visible even if external CSS fails -->
<style>
.wpwps-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.wpwps-page-header { margin-bottom: 20px; padding: 15px; background: #fff; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.wpwps-page-title { font-size: 24px; margin: 0; }
.wpwps-content-section { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
</style>

<div class="wpwps-container">
    <!-- Page Header -->
    <div class="wpwps-page-header">
        <h1 class="wpwps-page-title">
            <i class="fas fa-tshirt"></i>
            <?php esc_html_e('Printify Dashboard', 'wp-woocommerce-printify-sync'); ?>
        </h1>
        
        <div class="wpwps-user-profile">
            <img src="<?php echo esc_url(get_avatar_url($current_user->ID, ['size' => 40])); ?>" 
                 alt="<?php echo esc_attr($current_user->display_name); ?>" 
                 class="wpwps-user-avatar">
            <div class="wpwps-user-info">
                <span class="wpwps-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <span class="wpwps-user-role"><?php echo esc_html(implode(', ', $user_roles)); ?></span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid mb-4">
        <?php
        $this->component('wpwps-stats-card', [
            'icon' => 'shopping-cart',
            'value' => number_format($stats['total_products']),
            'label' => __('Total Products', 'wp-woocommerce-printify-sync'),
            'trend' => ['direction' => 'up', 'value' => '12']
        ]);

        $this->component('wpwps-stats-card', [
            'icon' => 'sync',
            'value' => $stats['sync_rate'] . '%',
            'label' => __('Sync Success Rate', 'wp-woocommerce-printify-sync')
        ]);

        $this->component('wpwps-stats-card', [
            'icon' => 'money-bill-wave',
            'value' => '$' . number_format($stats['credit_balance'], 2),
            'label' => __('API Credit Balance', 'wp-woocommerce-printify-sync'),
            'trend' => ['direction' => $credit_balance < 2 ? 'down' : 'up', 'value' => '5']
        ]);
        ?>
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
<?php $this->endSection('content'); ?>
