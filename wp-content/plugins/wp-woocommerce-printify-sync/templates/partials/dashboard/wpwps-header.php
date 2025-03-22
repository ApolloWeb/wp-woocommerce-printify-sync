<?php defined('ABSPATH') || exit; ?>

<header class="wpwps-dashboard-header">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <h1>
                <i class="fas fa-tachometer-alt"></i> <?php esc_html_e('Dashboard', 'wp-woocommerce-printify-sync'); ?>
                <?php if (!empty($shop_name)) : ?>
                <div class="wpwps-shop-info">
                    <span class="wpwps-shop-badge">
                        <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
                    </span>
                </div>
                <?php endif; ?>
            </h1>
        </div>
        <div class="d-flex align-items-center">
            <div class="api-status-wrapper me-4">
                <div class="d-flex align-items-center">
                    <span class="text-secondary me-2"><?php esc_html_e('API Status:', 'wp-woocommerce-printify-sync'); ?></span>
                    <?php if ($api_status === 'healthy') : ?>
                        <span class="status-healthy"><i class="fas fa-check-circle"></i></span>
                    <?php elseif ($api_status === 'error') : ?>
                        <span class="status-error"><i class="fas fa-times-circle"></i></span>
                    <?php else : ?>
                        <span class="status-unknown"><i class="fas fa-question-circle"></i></span>
                    <?php endif; ?>
                </div>
            </div>
            <button id="test-api-connection" class="btn btn-sm btn-light">
                <i class="fas fa-plug"></i> <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>
</header>
