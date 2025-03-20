<?php
/**
 * Dashboard modals partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<!-- Sync Confirmation Modal -->
<div class="modal fade" id="syncConfirmModal" tabindex="-1" aria-labelledby="syncConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncConfirmModalLabel">
                    <i class="fas fa-sync-alt me-2"></i><?php echo esc_html__('Confirm Synchronization', 'wp-woocommerce-printify-sync'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo esc_html__('Are you sure you want to sync all products from Printify to WooCommerce?', 'wp-woocommerce-printify-sync'); ?></p>
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div><?php echo esc_html__('This process may take some time depending on the number of products.', 'wp-woocommerce-printify-sync'); ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i><?php echo esc_html__('Cancel', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="confirm-sync">
                    <i class="fas fa-check me-1"></i><?php echo esc_html__('Proceed', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sync Progress Modal -->
<div class="modal fade" id="syncProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="syncProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncProgressModalLabel">
                    <i class="fas fa-sync-alt me-2"></i><?php echo esc_html__('Synchronization in Progress', 'wp-woocommerce-printify-sync'); ?>
                </h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php echo esc_html__('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
                    </div>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="sync-progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="text-center mb-0" id="sync-status-message"><?php echo esc_html__('Preparing to sync products...', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
        </div>
    </div>
</div>
