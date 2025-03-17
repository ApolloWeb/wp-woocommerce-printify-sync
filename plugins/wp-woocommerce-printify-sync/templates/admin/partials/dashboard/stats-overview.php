<?php
/** @var array $stats */
?>

<div class="wpwps-stats-overview">
    <!-- Products Stats -->
    <div class="wpwps-card">
        <div class="card-header">
            <h3><?php _e('Products', 'wp-woocommerce-printify-sync'); ?></h3>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['products']['total']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Synced Today', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['products']['synced_today']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Pending Sync', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['products']['pending_sync']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('With Errors', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value error"><?php echo esc_html($stats['products']['with_errors']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Stats -->
    <div class="wpwps-card">
        <div class="card-header">
            <h3><?php _e('Orders', 'wp-woocommerce-printify-sync'); ?></h3>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['orders']['total']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Pending', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value warning"><?php echo esc_html($stats['orders']['pending']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Processing', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value info"><?php echo esc_html($stats['orders']['processing']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Completed', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value success"><?php echo esc_html($stats['orders']['completed']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Stats -->
    <div class="wpwps-card">
        <div class="card-header">
            <h3><?php _e('Revenue', 'wp-woocommerce-printify-sync'); ?></h3>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Today', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo wc_price($stats['revenue']['today']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('This Week', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo wc_price($stats['revenue']['this_week']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('This Month', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo wc_price($stats['revenue']['this_month']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="stat-value"><?php echo wc_price($stats['revenue']['total']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>