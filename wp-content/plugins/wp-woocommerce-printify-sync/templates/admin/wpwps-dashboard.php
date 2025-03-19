<?php
$api_key = get_option('wpwps_printify_api_key', '');
$shop_id = get_option('wpwps_printify_shop_id', '');
$endpoint = get_option('wpwps_printify_endpoint', '');

if (empty($api_key) || empty($shop_id) || empty($endpoint)): 
?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Configuration Required</h4>
    <p>Your Printify integration is not fully configured. Please complete the following steps:</p>
    <ul>
        <?php if (empty($api_key)): ?><li>Add your Printify API key</li><?php endif; ?>
        <?php if (empty($endpoint)): ?><li>Verify API endpoint</li><?php endif; ?>
        <?php if (empty($shop_id)): ?><li>Select a Printify shop</li><?php endif; ?>
    </ul>
    <p><a href="admin.php?page=wpwps-settings" class="btn btn-sm btn-warning">Go to Settings</a></p>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php
// Get products for display
$products = [];
$current_page = isset($_GET['products_page']) ? max(1, intval($_GET['products_page'])) : 1;
$per_page = 10;

try {
    /** @var PrintifyAPIInterface $printifyApi */
    $printifyApi = $container->get('printify_api');
    $allProducts = $printifyApi->getCachedProducts($shop_id);
    
    // Handle pagination
    $total_products = count($allProducts);
    $offset = ($current_page - 1) * $per_page;
    $products = array_slice($allProducts, $offset, $per_page);
    $total_pages = ceil($total_products / $per_page);
} catch (\Exception $e) {
    $error_message = $e->getMessage();
}
?>

<div class="wpwps-dashboard-stats">
    <div class="wpwps-stat-card wpwps-card">
        <h4>Total Products</h4>
        <p class="h2"><?php echo intval(get_option('wpwps_products_synced', 0)); ?></p>
        <small>Last synced: <?php echo get_option('wpwps_last_sync', 'Never'); ?></small>
    </div>
    <div class="wpwps-stat-card wpwps-card">
        <h4>Total Orders</h4>
        <p class="h2">0</p>
        <small>Orders will appear here after syncing</small>
    </div>
    <div class="wpwps-stat-card wpwps-card">
        <h4>Quick Actions</h4>
        <div class="d-grid gap-2 mt-3">
            <a href="admin.php?page=wpwps-products" class="btn btn-primary">
                <i class="fas fa-box"></i> View Products
            </a>
            <a href="admin.php?page=wpwps-settings" class="btn btn-outline-secondary">
                <i class="fas fa-cogs"></i> Settings
            </a>
        </div>
    </div>
</div>

<?php if (!empty($products)): ?>
<div class="wpwps-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-box"></i> Recent Products</h5>
        <a href="<?php echo admin_url('admin.php?page=wpwps-products'); ?>" class="btn btn-sm btn-primary">
            View All Products
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px">Image</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc_url($product['images'][0]['src'] ?? ''); ?>" 
                                 alt="<?php echo esc_attr($product['title']); ?>"
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td><?php echo esc_html($product['title']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $product['visible'] ? 'success' : 'secondary'; ?>">
                                <?php echo $product['visible'] ? 'Active' : 'Draft'; ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($product['updated_at'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary import-product" 
                                    data-id="<?php echo esc_attr($product['id']); ?>">
                                <i class="fas fa-download"></i> Import
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Products navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $current_page === $i ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo add_query_arg('products_page', $i); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="wpwps-chart-container wpwps-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Sales Overview</h3>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary" data-period="day">Day</button>
            <button type="button" class="btn btn-outline-primary" data-period="week">Week</button>
            <button type="button" class="btn btn-outline-primary active" data-period="month">Month</button>
            <button type="button" class="btn btn-outline-primary" data-period="year">Year</button>
        </div>
    </div>
    <canvas id="salesChart"></canvas>
</div>