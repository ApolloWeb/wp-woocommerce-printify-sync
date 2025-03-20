<?php
/**
 * Product stats cards partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm wpwps-stats-card bg-primary bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1"><?php echo esc_html__('Total Products', 'wp-woocommerce-printify-sync'); ?></h6>
                        <h2 class="mb-0 fw-bold" id="total-products">--</h2>
                    </div>
                    <div class="display-4 opacity-50">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm wpwps-stats-card bg-success bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1"><?php echo esc_html__('Synced Products', 'wp-woocommerce-printify-sync'); ?></h6>
                        <h2 class="mb-0 fw-bold" id="synced-products">--</h2>
                    </div>
                    <div class="display-4 opacity-50">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm wpwps-stats-card bg-info bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1"><?php echo esc_html__('Pending Products', 'wp-woocommerce-printify-sync'); ?></h6>
                        <h2 class="mb-0 fw-bold" id="pending-products">--</h2>
                    </div>
                    <div class="display-4 opacity-50">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm wpwps-stats-card bg-warning bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1"><?php echo esc_html__('Failed Products', 'wp-woocommerce-printify-sync'); ?></h6>
                        <h2 class="mb-0 fw-bold" id="failed-products">--</h2>
                    </div>
                    <div class="display-4 opacity-50">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
