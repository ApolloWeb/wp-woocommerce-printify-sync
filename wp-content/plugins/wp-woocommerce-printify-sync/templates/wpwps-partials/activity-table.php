<?php
/**
 * Activity table partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i><?php echo esc_html__('Recent Sync Activity', 'wp-woocommerce-printify-sync'); ?></h5>
                <div>
                    <button class="btn btn-primary btn-sm" id="refresh-activity" <?php echo $apiConfigured ? '' : 'disabled'; ?>>
                        <i class="fas fa-sync-alt me-1"></i> <?php echo esc_html__('Refresh', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    <button class="btn btn-success btn-sm" id="sync-products" <?php echo $apiConfigured ? '' : 'disabled'; ?>>
                        <i class="fas fa-exchange-alt me-1"></i> <?php echo esc_html__('Sync Products', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo esc_html__('Product', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Type', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Last Updated', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Action', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="activity-table-body">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <?php if ($apiConfigured): ?>
                                        <div class="text-muted fs-6">
                                            <i class="fas fa-spinner fa-spin me-2"></i> <?php echo esc_html__('Loading recent activity...', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted fs-6">
                                            <i class="fas fa-info-circle me-2"></i> <?php echo esc_html__('Configure API settings to view sync activity', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i> 
                    <?php echo esc_html__('Showing recent product synchronization activity between Printify and WooCommerce', 'wp-woocommerce-printify-sync'); ?>
                </small>
            </div>
        </div>
    </div>
</div>
