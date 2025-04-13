<?php
defined('ABSPATH') || exit;

$active_page = 'products';
$title = __('Products - Printify Sync', 'wp-woocommerce-printify-sync');

ob_start();
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="h3"><?php echo esc_html__('Products', 'wp-woocommerce-printify-sync'); ?></h1>
        <div>
            <button id="sync-now" class="btn btn-primary me-2">
                <i class="fa-solid fa-sync me-2"></i><?php echo esc_html__('Sync Now', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <a href="#" class="btn btn-outline-secondary">
                <i class="fa-solid fa-plus me-2"></i><?php echo esc_html__('Import Products', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Sync Status Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Total Products', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h2 class="mt-2 mb-0"><?php echo esc_html($total_products); ?></h2>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-boxes-stacked fs-3 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Out of Sync', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h2 class="mt-2 mb-0"><?php echo esc_html($sync_status['out_of_sync']); ?></h2>
                        <?php if ($sync_status['out_of_sync'] > 0) : ?>
                        <p class="text-warning mb-0">
                            <i class="fa-solid fa-exclamation-triangle me-1"></i>
                            <span><?php echo esc_html__('Needs attention', 'wp-woocommerce-printify-sync'); ?></span>
                        </p>
                        <?php else : ?>
                        <p class="text-success mb-0">
                            <i class="fa-solid fa-check me-1"></i>
                            <span><?php echo esc_html__('All synced', 'wp-woocommerce-printify-sync'); ?></span>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-sync fs-3 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Last Sync', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h3 class="mt-2 mb-0"><?php echo empty($sync_status['last_sync']) ? esc_html__('Never', 'wp-woocommerce-printify-sync') : esc_html(human_time_diff(strtotime($sync_status['last_sync']))); ?></h3>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-calendar fs-3 text-info"></i>
                    </div>
                </div>
                <p class="text-muted mt-2 mb-0 small">
                    <?php echo empty($sync_status['last_sync']) ? '' : esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sync_status['last_sync']))); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Next Sync', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h3 class="mt-2 mb-0">
                            <?php 
                            if ($sync_status['next_sync'] == 'Not scheduled') {
                                echo esc_html__('Not Scheduled', 'wp-woocommerce-printify-sync');
                            } else {
                                echo esc_html(human_time_diff(strtotime($sync_status['next_sync'])));
                            }
                            ?>
                        </h3>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-clock fs-3 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Search Row -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-wpwps">
            <div class="card-body p-3">
                <form class="row g-3">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label small text-muted"><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></label>
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value=""><?php echo esc_html__('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="published"><?php echo esc_html__('Published', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="draft"><?php echo esc_html__('Draft', 'wp-woocommerce-printify-sync'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="syncFilter" class="form-label small text-muted"><?php echo esc_html__('Sync Status', 'wp-woocommerce-printify-sync'); ?></label>
                        <select id="syncFilter" class="form-select form-select-sm">
                            <option value=""><?php echo esc_html__('All', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="synced"><?php echo esc_html__('Synced', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="out_of_sync"><?php echo esc_html__('Out of Sync', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="failed"><?php echo esc_html__('Failed', 'wp-woocommerce-printify-sync'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="searchInput" class="form-label small text-muted"><?php echo esc_html__('Search', 'wp-woocommerce-printify-sync'); ?></label>
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="<?php echo esc_attr__('Search products...', 'wp-woocommerce-printify-sync'); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-secondary w-100"><?php echo esc_html__('Filter', 'wp-woocommerce-printify-sync'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-wpwps">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col" width="40px">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th scope="col"><?php echo esc_html__('Product', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('SKU', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Price', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Stock', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Last Updated', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Sync Status', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product) : ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input product-select" type="checkbox" value="<?php echo esc_attr($product['id']); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="square-img-container me-3">
                                            <img src="<?php echo esc_url($product['image']); ?>" width="40" height="40" alt="<?php echo esc_attr($product['title']); ?>">
                                        </div>
                                        <div>
                                            <a href="#" class="text-decoration-none text-dark fw-medium"><?php echo esc_html($product['title']); ?></a>
                                            <div class="text-muted small">ID: <?php echo esc_html($product['printify_id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($product['status'] === 'published') : ?>
                                        <span class="badge bg-success"><?php echo esc_html__('Published', 'wp-woocommerce-printify-sync'); ?></span>
                                    <?php elseif ($product['status'] === 'draft') : ?>
                                        <span class="badge bg-warning text-dark"><?php echo esc_html__('Draft', 'wp-woocommerce-printify-sync'); ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary"><?php echo esc_html($product['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($product['sku']); ?></td>
                                <td>$<?php echo esc_html(number_format($product['price'], 2)); ?></td>
                                <td><?php echo esc_html($product['stock_status']); ?></td>
                                <td><?php echo esc_html($product['last_updated']); ?></td>
                                <td>
                                    <?php if ($product['sync_status'] === 'synced') : ?>
                                        <span class="badge bg-success"><?php echo esc_html__('Synced', 'wp-woocommerce-printify-sync'); ?></span>
                                    <?php elseif ($product['sync_status'] === 'out_of_sync') : ?>
                                        <span class="badge bg-warning text-dark"><?php echo esc_html__('Out of Sync', 'wp-woocommerce-printify-sync'); ?></span>
                                    <?php elseif ($product['sync_status'] === 'failed') : ?>
                                        <span class="badge bg-danger"><?php echo esc_html__('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <?php echo esc_html__('Actions', 'wp-woocommerce-printify-sync'); ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-sync me-2"></i><?php echo esc_html__('Sync', 'wp-woocommerce-printify-sync'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-edit me-2"></i><?php echo esc_html__('Edit', 'wp-woocommerce-printify-sync'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-eye me-2"></i><?php echo esc_html__('View', 'wp-woocommerce-printify-sync'); ?></a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#"><i class="fa-solid fa-trash me-2"></i><?php echo esc_html__('Delete', 'wp-woocommerce-printify-sync'); ?></a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($products)) : ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa-solid fa-store fa-3x mb-3"></i>
                                        <h4><?php echo esc_html__('No products found', 'wp-woocommerce-printify-sync'); ?></h4>
                                        <p class="mb-3"><?php echo esc_html__('Import products from Printify to get started.', 'wp-woocommerce-printify-sync'); ?></p>
                                        <a href="#" class="btn btn-primary"><?php echo esc_html__('Import Products', 'wp-woocommerce-printify-sync'); ?></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if (!empty($products)) : ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <?php echo esc_html(sprintf(__('Showing %d of %d products', 'wp-woocommerce-printify-sync'), count($products), $total_products)); ?>
                        </div>
                        <ul class="pagination pagination-sm">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true"><?php echo esc_html__('Previous', 'wp-woocommerce-printify-sync'); ?></a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#"><?php echo esc_html__('Next', 'wp-woocommerce-printify-sync'); ?></a>
                            </li>
                        </ul>
                    </div>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Batch Actions Fixed Bar -->
<div id="batch-actions-bar" class="d-none position-fixed bottom-0 start-0 w-100 py-3 bg-dark text-white" style="z-index: 1030;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span id="selected-count" class="me-3">0</span> <?php echo esc_html__('products selected', 'wp-woocommerce-printify-sync'); ?>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-light me-2" id="batch-sync">
                    <i class="fa-solid fa-sync me-1"></i> <?php echo esc_html__('Sync Selected', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <button class="btn btn-sm btn-outline-light" id="batch-delete">
                    <i class="fa-solid fa-trash me-1"></i> <?php echo esc_html__('Delete Selected', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle select all checkbox
    $('#selectAll').on('change', function() {
        $('.product-select').prop('checked', $(this).is(':checked'));
        updateBatchActionsBar();
    });
    
    // Handle individual checkboxes
    $('.product-select').on('change', function() {
        updateBatchActionsBar();
    });
    
    // Update batch actions bar visibility
    function updateBatchActionsBar() {
        const selectedCount = $('.product-select:checked').length;
        $('#selected-count').text(selectedCount);
        
        if (selectedCount > 0) {
            $('#batch-actions-bar').removeClass('d-none');
        } else {
            $('#batch-actions-bar').addClass('d-none');
        }
    }
    
    // Sync now button
    $('#sync-now').on('click', function(e) {
        e.preventDefault();
        
        // Show toast notification
        showToast('Sync in Progress', 'Starting synchronization with Printify...', 'info');
        
        // In a real implementation, this would call an AJAX endpoint
        // For demo purposes, show success after a delay
        setTimeout(function() {
            showToast('Sync Complete', 'All products have been synchronized with Printify.', 'success');
        }, 2000);
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
