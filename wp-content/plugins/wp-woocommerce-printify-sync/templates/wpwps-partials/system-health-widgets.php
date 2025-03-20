<?php
/**
 * System health widgets partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="row mb-4">
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-heartbeat me-2"></i><?php echo esc_html__('API Health', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body py-4">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <div id="api-health-indicator" class="mb-3 d-flex justify-content-center">
                            <div class="health-indicator health-good">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                        </div>
                        <h6 id="api-health-status" class="text-success fw-bold mb-0"><?php echo esc_html__('API Connection OK', 'wp-woocommerce-printify-sync'); ?></h6>
                        <p class="text-muted small mt-2 mb-0" id="api-last-check"><?php echo esc_html__('Last checked: 2 minutes ago', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <button class="btn btn-sm btn-primary w-100" id="check-api-health" <?php echo $apiConfigured ? '' : 'disabled'; ?>>
                    <i class="fas fa-sync-alt me-1"></i> <?php echo esc_html__('Check API Connection', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-exchange-alt me-2"></i><?php echo esc_html__('Webhook Status', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body py-4">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <div id="webhook-health-indicator" class="mb-3 d-flex justify-content-center">
                            <div class="health-indicator health-warning">
                                <i class="fas fa-exclamation-triangle fa-3x"></i>
                            </div>
                        </div>
                        <h6 id="webhook-health-status" class="text-warning fw-bold mb-0"><?php echo esc_html__('Webhooks Not Configured', 'wp-woocommerce-printify-sync'); ?></h6>
                        <p class="text-muted small mt-2 mb-0" id="webhook-last-event"><?php echo esc_html__('No recent events', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <button class="btn btn-sm btn-primary w-100" id="configure-webhooks" <?php echo $apiConfigured ? '' : 'disabled'; ?>>
                    <i class="fas fa-cog me-1"></i> <?php echo esc_html__('Configure Webhooks', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i><?php echo esc_html__('Order Sync Status', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body py-4">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <div id="order-sync-indicator" class="mb-3 d-flex justify-content-center">
                            <div class="health-indicator health-good">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                        </div>
                        <h6 id="order-sync-status" class="text-success fw-bold mb-0"><?php echo esc_html__('Orders in Sync', 'wp-woocommerce-printify-sync'); ?></h6>
                        <p class="text-muted small mt-2 mb-0" id="order-last-sync"><?php echo esc_html__('Last synced: 15 minutes ago', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <button class="btn btn-sm btn-primary w-100" id="sync-orders" <?php echo $apiConfigured ? '' : 'disabled'; ?>>
                    <i class="fas fa-sync-alt me-1"></i> <?php echo esc_html__('Sync Orders Now', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
