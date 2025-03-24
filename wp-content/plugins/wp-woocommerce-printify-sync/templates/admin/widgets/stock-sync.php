<div class="wpwps-widget-content">
    <div class="wpwps-widget-stats">
        <div class="wpwps-stat">
            <span class="wpwps-stat-label"><?php esc_html_e('Next Sync:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="wpwps-stat-value">
                <?php echo $next_sync ? esc_html(human_time_diff(time(), $next_sync)) : esc_html__('Not scheduled', 'wp-woocommerce-printify-sync'); ?>
            </span>
        </div>
        
        <div class="wpwps-stat">
            <span class="wpwps-stat-label"><?php esc_html_e('Last Sync:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="wpwps-stat-value">
                <?php echo $last_sync ? esc_html(human_time_diff(strtotime($last_sync), time())) . ' ' . esc_html__('ago', 'wp-woocommerce-printify-sync') : esc_html__('Never', 'wp-woocommerce-printify-sync'); ?>
            </span>
        </div>
    </div>

    <div class="wpwps-sync-stats">
        <h4><?php esc_html_e('Last Sync Results', 'wp-woocommerce-printify-sync'); ?></h4>
        <div class="wpwps-stat-grid">
            <div class="wpwps-stat-item">
                <span class="wpwps-stat-number"><?php echo esc_html($sync_stats['total']); ?></span>
                <span class="wpwps-stat-label"><?php esc_html_e('Total', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
            <div class="wpwps-stat-item">
                <span class="wpwps-stat-number"><?php echo esc_html($sync_stats['updated']); ?></span>
                <span class="wpwps-stat-label"><?php esc_html_e('Updated', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
            <div class="wpwps-stat-item">
                <span class="wpwps-stat-number"><?php echo esc_html($sync_stats['failed']); ?></span>
                <span class="wpwps-stat-label"><?php esc_html_e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
        </div>
    </div>
</div>
