<?php
/**
 * Sync status widget template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wpwps-widget sync-status-widget">
    <div class="wpwps-widget-stat">
        <div class="wpwps-widget-stat-label"><?php esc_html_e('Last Sync:', 'wp-woocommerce-printify-sync'); ?></div>
        <div class="wpwps-widget-stat-value"><?php echo esc_html($last_sync); ?></div>
    </div>
    
    <div class="wpwps-widget-stat">
        <div class="wpwps-widget-stat-label"><?php esc_html_e('Next Sync:', 'wp-woocommerce-printify-sync'); ?></div>
        <div class="wpwps-widget-stat-value"><?php echo esc_html($next_sync); ?></div>
    </div>
    
    <div class="wpwps-widget-stat">
        <div class="wpwps-widget-stat-label"><?php esc_html_e('Synced Products:', 'wp-woocommerce-printify-sync'); ?></div>
        <div class="wpwps-widget-stat-value"><?php echo esc_html($products_count); ?></div>
    </div>
    
    <div class="wpwps-widget-stat">
        <div class="wpwps-widget-stat-label"><?php esc_html_e('Synced Orders:', 'wp-woocommerce-printify-sync'); ?></div>
        <div class="wpwps-widget-stat-value"><?php echo esc_html($orders_count); ?></div>
    </div>
    
    <div class="wpwps-widget-stat">
        <div class="wpwps-widget-stat-label"><?php esc_html_e('Sync Success Rate:', 'wp-woocommerce-printify-sync'); ?></div>
        <div class="wpwps-widget-stat-value">
            <div class="wpwps-progress-bar">
                <div class="wpwps-progress-bar-fill" style="width: <?php echo esc_attr($sync_success_rate); ?>%"></div>
            </div>
            <span><?php echo esc_html($sync_success_rate); ?>%</span>
        </div>
    </div>
    
    <div class="wpwps-widget-footer">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-logs')); ?>" class="button button-secondary">
            <?php esc_html_e('View Logs', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="#" class="button button-primary wpwps-sync-now-button">
            <?php esc_html_e('Sync Now', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
</div>

<style>
.wpwps-widget {
    padding: 10px 0;
}

.wpwps-widget-stat {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.wpwps-widget-stat-label {
    font-weight: 500;
    color: #23282d;
}

.wpwps-progress-bar {
    height: 8px;
    background-color: #f0f0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 10px;
    display: inline-block;
    width: 100px;
    vertical-align: middle;
}

.wpwps-progress-bar-fill {
    height: 100%;
    background-color: #96588a;
    border-radius: 4px;
}

.wpwps-widget-footer {
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.wpwps-sync-now-button').on('click', function(e) {
        e.preventDefault();
        
        $(this).prop('disabled', true).text('<?php esc_html_e('Syncing...', 'wp-woocommerce-printify-sync'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_sync_now',
                nonce: '<?php echo wp_create_nonce('wpwps-sync-now'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    $('.wpwps-sync-now-button').prop('disabled', false).text('<?php esc_html_e('Sync Now', 'wp-woocommerce-printify-sync'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'wp-woocommerce-printify-sync'); ?>');
                $('.wpwps-sync-now-button').prop('disabled', false).text('<?php esc_html_e('Sync Now', 'wp-woocommerce-printify-sync'); ?>');
            }
        });
    });
});
</script>
