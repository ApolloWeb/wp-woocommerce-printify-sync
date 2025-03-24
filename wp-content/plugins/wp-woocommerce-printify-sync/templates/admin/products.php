<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrap">
    <div class="wpwps-header">
        <h1><?php echo esc_html__('Printify Products', 'wp-woocommerce-printify-sync'); ?></h1>
        <div class="wpwps-header-actions">
            <button type="button" class="button button-primary" id="wpwps-sync-products">
                <i class="fas fa-sync"></i> <?php echo esc_html__('Sync Products', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>

    <div class="wpwps-content">
        <div class="wpwps-card">
            <div id="wpwps-products-table">
                <!-- Products table will be loaded here -->
            </div>
        </div>
    </div>
</div>
