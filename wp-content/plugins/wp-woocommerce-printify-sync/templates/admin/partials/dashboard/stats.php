<?php
/** @var array $stats */
?>

<div class="wpwps-stats-grid">
    <!-- Total Products -->
    <div class="wpwps-stat-card">
        <div class="stat-icon">
            <span class="dashicons dashicons-products"></span>
        </div>
        <div class="stat-content">
            <h3><?php echo esc_html($stats['total_products']); ?></h3>
            <p><?php _e('Total Products', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
    </div>

    <!-- Synced Today -->
    <div class="wpwps-stat-card">
        <div class="stat-icon">
            <span class="dashicons dashicons-update"></span>
        </div>
        <div class="stat-content">
            <h3><?php echo esc_html($stats['synced_today']); ?></h3>
            <p><?php _e('Synced Today', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
    </div>

    <!-- Failed Syncs -->
    <div class="wpwps-stat-card <?php echo $stats['failed_syncs'] > 0 ? 'error' : ''; ?>">
        <div class="stat-icon">
            <span class="dashicons dashicons-warning"></span>
        </div>
        <div class="stat-content">
            <h3><?php echo esc_html($stats['failed_syncs']); ?></h3>
            <p><?php _e('Failed Syncs', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
    </div>

    <!-- API Status -->
    <div class="wpwps-stat-card <?php echo $stats['printify_status'] ? 'success' : 'error'; ?>">
        <div class="stat-icon">
            <span class="dashicons dashicons-<?php echo $stats['printify_status'] ? 'yes' : 'no'; ?>"></span>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['printify_status'] ? __('Connected', 'wp-woocommerce-printify-sync') : __('Disconnected', 'wp-woocommerce-printify-sync'); ?></h3>
            <p><?php _e('Printify API', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
    </div>
</div>