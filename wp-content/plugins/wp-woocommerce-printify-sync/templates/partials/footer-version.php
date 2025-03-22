<?php defined('ABSPATH') || exit; ?>
<div class="wpwps-footer-version">
    <?php 
    printf(
        esc_html__('WP WooCommerce Printify Sync v%s', 'wp-woocommerce-printify-sync'),
        WPWPS_VERSION
    ); 
    ?>
</div>
<style>
    .wpwps-footer-version {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        color: #646970;
        font-size: 12px;
        font-family: 'Inter', sans-serif;
    }
</style>
