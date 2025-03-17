<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-dashboard">
    <h1><?php esc_html_e('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-stats-grid">
        <div class="wpwps-stat-card" id="pending-orders">
            <h3><?php esc_html_e('Pending Orders', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="stat-value">-</div>
        </div>

        <div class="wpwps-stat-card" id="in-production">
            <h3><?php esc_html_e('In Production', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="stat-value">-</div>
        </div>

        <div class="wpwps-stat-card" id="completed-orders">
            <h3><?php esc_html_e('Completed', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="stat-value">-</div>
        </div>

        <div class="wpwps-stat-card" id="failed-orders">
            <h3><?php esc_html_e('Failed', 'wp-woocommerce-printify-sync'); ?></h3>
            <div class="stat-value">-</div>
        </div>
    </div>

    <div class="wpwps-recent-orders">
        <h2><?php esc_html