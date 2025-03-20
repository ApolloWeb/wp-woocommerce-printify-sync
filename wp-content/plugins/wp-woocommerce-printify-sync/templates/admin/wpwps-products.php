<?php
// Initialize variables with default values to prevent errors
$products = [];
$total_products = 0;

try {
    if (isset($container) && $container !== null) {
        /** @var PrintifyAPIInterface $printifyApi */
        $printifyApi = $container->get('printify_api');
        $shop_id = get_option('wpwps_printify_shop_id', '');
        
        // Don't try to get products on initial page load - we'll fetch via AJAX
        // Just ensure we have the shop ID configured
        if (!empty($shop_id)) {
            $total_products = 0; // Will be updated via AJAX
        }
    }
} catch (\Exception $e) {
    $error_message = $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <div class="card wpwps-card w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-box"></i> Printify Products</h5>
                <div class="action-buttons">
                    <button type="button" class="btn btn-danger btn-sm me-2" id="clear-cache">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                    <button type="button" class="btn btn-primary btn-sm me-2" id="fetch-products">
                        <i class="fas fa-sync"></i> Fetch Products
                    </button>
                    <button type="button" class="btn btn-info btn-sm me-2" id="import-all-products">
                        <i class="fas fa-cloud-download-alt"></i> Import All
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="import-selected" disabled>
                        <i class="fas fa-download"></i> Import Selected (0)
                    </button>
                </div>
            </div>
            
            <!-- Add progress container -->
            <div id="import-progress-container" style="display: none;" class="p-3"></div>
            <div id="all-import-progress-container" style="display: none;" class="p-3"></div>
            
            <div class="card-body">
                <!-- Add a container for alerts -->
                <div id="products-alerts" class="mb-3">
                    <?php if (isset($cache_cleared) && $cache_cleared): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        The product cache has been automatically cleared. Click "Fetch Products" to load fresh data from Printify.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($shop_id)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No Printify shop selected. Please go to <a href="admin.php?page=wpwps-settings">Settings</a> to select a shop.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Add initial info alert -->
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Click "Fetch Products" button to load products from Printify.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped" id="products-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th style="width: 80px">Image</th>
                                <th>Title</th>
                                <th>Printify ID</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center">Click "Fetch Products" to load products from Printify</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Product counter (simplified, no pagination) -->
                <div class="mt-3">
                    <div class="text-muted" id="products-count">
                        Showing <span id="showing-start">0</span> to <span id="showing-end">0</span> of <span id="total-products">0</span> products
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
