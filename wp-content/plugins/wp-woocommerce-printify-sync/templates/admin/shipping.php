<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrap">
    <div class="wpwps-header">
        <h1><?php echo esc_html__('Printify Shipping', 'wp-woocommerce-printify-sync'); ?></h1>
    </div>

    <div class="wpwps-content">
        <div class="wpwps-card">
            <div class="wpwps-card-header">
                <h2><?php echo esc_html__('Shipping Zones', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div id="wpwps-shipping-zones">
                <!-- Shipping zones will be loaded here -->
            </div>
        </div>

        <div class="wpwps-card">
            <div class="wpwps-card-header">
                <h2><?php echo esc_html__('Shipping Methods', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div id="wpwps-shipping-methods">
                <!-- Shipping methods will be loaded here -->
            </div>
        </div>
    </div>
</div>
