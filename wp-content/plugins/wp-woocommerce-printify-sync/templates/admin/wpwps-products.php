<?php
// Get products for display
$products = [];
$current_page = isset($_GET['products_page']) ? max(1, intval($_GET['products_page'])) : 1;
$per_page = 10;

try {
    if (isset($container) && $container !== null) {
        /** @var PrintifyAPIInterface $printifyApi */
        $printifyApi = $container->get('printify_api');
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $allProducts = $printifyApi->getCachedProducts($shop_id);
        
        // Handle pagination
        $total_products = count($allProducts);
        $offset = ($current_page - 1) * $per_page;
        $products = array_slice($allProducts, $offset, $per_page);
        $total_pages = ceil($total_products / $per_page);
    } else {
        // Fallback if container is not available
        $products = [];
        $total_pages = 0;
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
                <div>
                    <button type="button" class="btn btn-danger btn-sm me-2" id="clear-cache">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                    <button type="button" class="btn btn-primary btn-sm me-2" id="fetch-products">
                        <i class="fas fa-sync"></i> Fetch Products
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="import-selected" disabled>
                        <i class="fas fa-download"></i> Import Selected
                    </button>
                </div>
            </div>
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
                </div>
                
                <div class="table-responsive">
                    <?php if (!empty($products)): ?>
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
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="product-select" value="<?php echo esc_attr($product['id']); ?>">
                                </td>
                                <td>
                                    <img src="<?php echo esc_url($product['images'][0]['src'] ?? ''); ?>" 
                                         alt="<?php echo esc_attr($product['title']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo esc_html($product['title']); ?></td>
                                <td><?php echo esc_html($product['id']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['visible'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $product['visible'] ? 'Active' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($product['updated_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary import-single" 
                                            data-id="<?php echo esc_attr($product['id']); ?>">
                                        <i class="fas fa-download"></i> Import
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
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
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted" id="products-count">
                            Showing <span id="showing-start"><?php echo !empty($products) ? (($current_page - 1) * $per_page) + 1 : 0; ?></span> to <span id="showing-end"><?php echo !empty($products) ? min($current_page * $per_page, $total_products) : 0; ?></span> of <span id="total-products"><?php echo $total_products ?? 0; ?></span> products
                        </div>
                        <?php if (!empty($products) && $total_pages > 1): ?>
                        <nav aria-label="Products navigation">
                            <ul class="pagination mb-0" id="products-pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $current_page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo add_query_arg('products_page', $i); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php else: ?>
                        <nav aria-label="Products navigation">
                            <ul class="pagination mb-0" id="products-pagination"></ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
