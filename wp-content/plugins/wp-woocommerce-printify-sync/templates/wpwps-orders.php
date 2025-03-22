<?php
/**
 * Orders template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get action scheduler service to display queue information
global $wpwps_container;
$action_scheduler = $wpwps_container->get('action_scheduler');
$pending_order_syncs = $action_scheduler->getPendingActionsCount('wpwps_as_sync_order');
?>
<div class="wrap wpwps-admin-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="wp-heading-inline">
            <i class="fas fa-shopping-cart"></i> <?php echo esc_html__('Printify Sync - Orders', 'wp-woocommerce-printify-sync'); ?>
        </h1>
        
        <?php if (!empty($shop_name)) : ?>
        <div class="wpwps-shop-info">
            <span class="wpwps-shop-badge">
                <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <hr class="wp-header-end">
    
    <?php if (empty($shop_id)) : ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <?php esc_html_e('Your Printify Shop is not configured yet. Please go to the Settings page and set up your API connection.', 'wp-woocommerce-printify-sync'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="btn btn-primary ms-3">
            <i class="fas fa-cog"></i> <?php esc_html_e('Go to Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
    <?php else : ?>
    
    <div class="wpwps-orders-container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="wpwps-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-sync-alt"></i> <?php esc_html_e('Order Synchronization', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <div>
                                <button id="wpwps-sync-orders" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> <?php esc_html_e('Sync All Orders', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="sync-status-wrapper">
                            <div id="sync-status"></div>
                            <?php if ($pending_order_syncs > 0) : ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <?php printf(
                                        esc_html__('There are %d orders currently queued for synchronization.', 'wp-woocommerce-printify-sync'),
                                        $pending_order_syncs
                                    ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="wpwps-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php esc_html_e('Printify Orders', 'wp-woocommerce-printify-sync'); ?></h5>
                        <div class="form-inline">
                            <div class="input-group">
                                <input type="text" id="search-orders" class="form-control" placeholder="<?php esc_attr_e('Search orders...', 'wp-woocommerce-printify-sync'); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <select id="filter-status" class="form-select ms-2">
                                <option value=""><?php esc_html_e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="processing"><?php esc_html_e('Processing', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="completed"><?php esc_html_e('Completed', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="on-hold"><?php esc_html_e('On Hold', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="cancelled"><?php esc_html_e('Cancelled', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="refunded"><?php esc_html_e('Refunded', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table wpwps-table" id="printify-orders-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Customer', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Last Synced', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden"><?php esc_html_e('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
                                            </div>
                                            <p><?php esc_html_e('Loading orders...', 'wp-woocommerce-printify-sync'); ?></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-wrapper mt-3 d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                <?php esc_html_e('Showing 0 of 0 orders', 'wp-woocommerce-printify-sync'); ?>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Details Modal -->
        <div class="modal fade" id="order-details-modal" tabindex="-1" aria-labelledby="order-details-title" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="order-details-title"><?php esc_html_e('Order Details', 'wp-woocommerce-printify-sync'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="order-details-content">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden"><?php esc_html_e('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
                                </div>
                                <p><?php esc_html_e('Loading order details...', 'wp-woocommerce-printify-sync'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'wp-woocommerce-printify-sync'); ?></button>
                        <button type="button" class="btn btn-primary" id="sync-single-order"><?php esc_html_e('Sync This Order', 'wp-woocommerce-printify-sync'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>
