<?php
/**
 * Charts section partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<!-- Sales and Profit Charts -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i><?php echo esc_html__('Sales & Profit', 'wp-woocommerce-printify-sync'); ?></h5>
                <div class="btn-group time-filter" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="day"><?php echo esc_html__('Day', 'wp-woocommerce-printify-sync'); ?></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-period="week"><?php echo esc_html__('Week', 'wp-woocommerce-printify-sync'); ?></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-period="month"><?php echo esc_html__('Month', 'wp-woocommerce-printify-sync'); ?></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-period="year"><?php echo esc_html__('Year', 'wp-woocommerce-printify-sync'); ?></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="sales-profit-chart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2"></i><?php echo esc_html__('Sync Status', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body">
                <canvas id="sync-status-chart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Sync Activity Charts -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i><?php echo esc_html__('Sync Activity', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body">
                <canvas id="sync-activity-chart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-tasks me-2"></i><?php echo esc_html__('Order Status Breakdown', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body">
                <canvas id="order-status-chart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>
