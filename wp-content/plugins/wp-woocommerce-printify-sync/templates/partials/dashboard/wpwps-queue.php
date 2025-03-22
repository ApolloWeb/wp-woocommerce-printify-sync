<?php defined('ABSPATH') || exit; ?>

<!-- Queue Status -->
<div class="col-md-4">
    <div class="card mb-4 wpwps-card">
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
</div>
