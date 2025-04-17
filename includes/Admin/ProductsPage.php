<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Products Management Page
 */
class ProductsPage extends AdminPage {
    /**
     * Constructor
     */
    public function __construct() {
        $this->page_title = __('Printify Products', 'wp-woocommerce-printify-sync');
        $this->menu_title = __('Products', 'wp-woocommerce-printify-sync');
        $this->menu_slug = 'wpwps-products';
        $this->parent_slug = 'wpwps-dashboard';
        $this->capability = 'manage_options';
    }
    
    /**
     * Initialize the page
     */
    public function init() {
        parent::init();
        
        // Add AJAX handlers for product operations
        add_action('wp_ajax_wpwps_sync_product', [$this, 'ajax_sync_product']);
        add_action('wp_ajax_wpwps_bulk_sync_products', [$this, 'ajax_bulk_sync_products']);
    }
    
    /**
     * AJAX handler for product sync
     */
    public function ajax_sync_product() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        // Simulate successful sync
        wp_send_json_success([
            'message' => __('Product synced successfully', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * AJAX handler for bulk sync
     */
    public function ajax_bulk_sync_products() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        // Simulate successful bulk sync
        wp_send_json_success([
            'message' => __('Products synced successfully', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Render the page content
     */
    protected function render_content() {
        ?>
        <div class="container-fluid">
            <!-- Action Bar Row -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center mb-2 mb-md-0">
                                    <button class="btn btn-primary me-2" id="sync-all-products">
                                        <i class="fas fa-sync-alt me-2"></i> Sync All Products
                                    </button>
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Bulk Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" id="bulk-sync">Sync Selected</a></li>
                                        <li><a class="dropdown-item" href="#" id="bulk-publish">Publish Selected</a></li>
                                        <li><a class="dropdown-item" href="#" id="bulk-unpublish">Unpublish Selected</a></li>
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search products" id="product-search">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <div class="ms-2">
                                        <button class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Filter Options">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Table Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Printify Products</h5>
                            <span class="badge bg-primary">237 Products</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover products-table">
                                    <thead>
                                        <tr>
                                            <th width="40px">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="select-all">
                                                </div>
                                            </th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Last Sync</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input product-checkbox" type="checkbox" value="<?php echo $i; ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <img src="https://via.placeholder.com/50" alt="Product" class="rounded">
                                                    </div>
                                                    <div class="ms-3">
                                                        <p class="fw-bold mb-0">Classic T-Shirt <?php echo $i; ?></p>
                                                        <small class="text-muted">Categories: Apparel, T-Shirts</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>PRN-00<?php echo $i; ?></td>
                                            <td>$24.99</td>
                                            <td>
                                                <?php if ($i % 3 == 0): ?>
                                                <span class="badge bg-warning">Out of Sync</span>
                                                <?php elseif ($i % 5 == 0): ?>
                                                <span class="badge bg-danger">Error</span>
                                                <?php else: ?>
                                                <span class="badge bg-success">Synced</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime("-$i days")); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary sync-product" data-id="<?php echo $i; ?>">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <a href="<?php echo admin_url('admin.php?page=wpwps-product-edit&id=' . $i); ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <nav aria-label="Products pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Handle product sync button click
                $('.sync-product').on('click', function() {
                    const productId = $(this).data('id');
                    const $button = $(this);
                    
                    $button.html('<i class="fas fa-spinner fa-spin"></i>');
                    
                    $.ajax({
                        url: wpwps_admin.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpwps_sync_product',
                            nonce: wpwps_admin.nonce,
                            product_id: productId
                        },
                        success: function(response) {
                            if (response.success) {
                                wpwpsToastManager.showToast(
                                    'success',
                                    'Success',
                                    response.data.message,
                                    5000
                                );
                            } else {
                                wpwpsToastManager.showToast(
                                    'error',
                                    'Error',
                                    'Failed to sync product.',
                                    5000
                                );
                            }
                            $button.html('<i class="fas fa-sync-alt"></i>');
                        },
                        error: function() {
                            wpwpsToastManager.showToast(
                                'error',
                                'Error',
                                'Server error occurred.',
                                5000
                            );
                            $button.html('<i class="fas fa-sync-alt"></i>');
                        }
                    });
                });
                
                // Handle select all checkbox
                $('#select-all').on('change', function() {
                    $('.product-checkbox').prop('checked', $(this).prop('checked'));
                });
                
                // Handle sync all products button
                $('#sync-all-products').on('click', function() {
                    const $button = $(this);
                    
                    $button.prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin me-2"></i> Syncing...'
                    );
                    
                    setTimeout(function() {
                        $button.prop('disabled', false).html(
                            '<i class="fas fa-sync-alt me-2"></i> Sync All Products'
                        );
                        
                        wpwpsToastManager.showToast(
                            'success',
                            'Success',
                            'All products have been synced successfully.',
                            5000
                        );
                    }, 2000);
                });
            });
        </script>
        <?php
    }
}
