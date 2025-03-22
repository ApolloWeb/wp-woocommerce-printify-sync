<?php defined('ABSPATH') || exit; ?>

<!-- Revenue Chart -->
<div class="col-md-8">
    <div class="card mb-4 wpwps-card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-chart-line"></i> <?php esc_html_e('Revenue & Profits', 'wp-woocommerce-printify-sync'); ?>
            </h5>
            <div class="chart-container">
                <canvas id="revenue-chart"></canvas>
            </div>
            <div class="chart-filters mt-3">
                <button class="btn btn-sm btn-outline-primary" data-filter="day"><?php esc_html_e('Day', 'wp-woocommerce-printify-sync'); ?></button>
                <button class="btn btn-sm btn-outline-primary active" data-filter="week"><?php esc_html_e('Week', 'wp-woocommerce-printify-sync'); ?></button>
                <button class="btn btn-sm btn-outline-primary" data-filter="month"><?php esc_html_e('Month', 'wp-woocommerce-printify-sync'); ?></button>
                <button class="btn btn-sm btn-outline-primary" data-filter="year"><?php esc_html_e('Year', 'wp-woocommerce-printify-sync'); ?></button>
            </div>
        </div>
    </div>
</div>
