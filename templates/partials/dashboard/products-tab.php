<?php
/**
 * Products tab for WooCommerce Printify Sync
 *
 * @package WP_Woocommerce_Printify_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get products with pagination
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Get product data
// In a real implementation, you would replace this with actual data from your database
$products = array(
    array(
        'id' => 1, 
        'title' => 'Classic T-Shirt',
        'sku' => 'TS-001',
        'stock' => 25,
        'price' => 24.99,
        'status' => 'synced',
        'last_sync' => '2025-03-05 14:22:31'
    ),
    array(
        'id' => 2,
        'title' => 'Vintage Hoodie',
        'sku' => 'HD-002',
        'stock' => 12,
        'price' => 49.99,
        'status' => 'synced',
        'last_sync' => '2025-03-06 09:15:42'
    ),
    array(
        'id' => 3,
        'title' => 'Coffee Mug',
        'sku' => 'MG-003',
        'stock' => 38,
        'price' => 14.99,
        'status' => 'pending',
        'last_sync' => '2025-03-04 11:33:20'
    ),
    array(
        'id' => 4,
        'title' => 'Canvas Print',
        'sku' => 'CP-004',
        'stock' => 7,
        'price' => 39.99,
        'status' => 'error',
        'last_sync' => '2025-03-02 16:45:11'
    ),
    array(
        'id' => 5,
        'title' => 'Phone Case',
        'sku' => 'PC-005',
        'stock' => 42,
        'price' => 19.99,
        'status' => 'synced',
        'last_sync' => '2025-03-06 12:10:05'
    )
);

// Product stats
$product_stats = array(
    'total' => 126,
    'synced' => 112,
    'pending' => 11,
    'error' => 3,
);
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2><i class="fas fa-box"></i> Products</h2>
        <p>Manage your synchronized products between Printify and WooCommerce.</p>
    </div>
</div>

<!-- Stats Row -->
<div class="wps-stat-grid">
    <div class="wps-stat-widget">
        <div class="wps-stat-icon wps-bg-primary wps-color-primary">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="wps-stat-value"><?php echo esc_html(number_format($product_stats['total'])); ?></div>
        <div class="wps-stat-label">Total Products</div>
    </div>
    
    <div class="wps-stat-widget">
        <div class="wps-stat-icon wps-bg-success wps-color-success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="wps-stat-value"><?php echo esc_html(number_format($product_stats['synced'])); ?></div>
        <div class="wps-stat-label">Synced</div>
    </div>
    
    <div class="wps-stat-widget">
        <div class="wps-stat-icon wps-bg-warning wps-color-warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="wps-stat-value"><?php echo esc_html(number_format($product_stats['pending'])); ?></div>
        <div class="wps-stat-label">Pending Sync</div>
    </div>
    
    <div class="wps-stat-widget">
        <div class="wps-stat-icon wps-bg-danger wps-color-danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="wps-stat-value"><?php echo esc_html(number_format($product_stats['error'])); ?></div>
        <div class="wps-stat-label">Sync Errors</div>
    </div>
</div>

<!-- Products Table -->
<div class="wps-card mb-4">
    <div class="wps-card-header d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-table"></i> Product List</h3>
        <div>
            <button type="button" id="wps-sync-all" class="btn btn-sm btn-primary wps-btn-primary">
                <i class="fas fa-sync"></i> Sync All
            </button>
            <button type="button" id="wps-import-new" class="btn btn-sm btn-outline-primary ms-2">
                <i class="fas fa-download"></i> Import New
            </button>
        </div>
    </div>
    <div class="wps-card-body">
        <div class="table-responsive">
            <table class="table wps-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="select-all"></th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><input type="checkbox" class="product-select" value="<?php echo esc_attr($product['id']); ?>"></td>
                            <td><?php echo esc_html($product['title']); ?></td>
                            <td><?php echo esc_html($product['sku']); ?></td>
                            <td><?php echo esc_html($product['stock']); ?></td>
                            <td>$<?php echo esc_html(number_format($product['price'], 2)); ?></td>
                            <td>
                                <?php if ($product['status'] === 'synced'): ?>
                                    <span class="badge bg-success">Synced</span>
                                <?php elseif ($product['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Error</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($product['last_sync']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-sm btn-outline-primary sync-product" data-id="<?php echo esc_attr($product['id']); ?>">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary view-product" data-id="<?php echo esc_attr($product['id']); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $product['id'] . '&action=edit')); ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Product pagination">
            <ul class="pagination">
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

<!-- Bulk Actions -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bulk Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <select class="form-select" id="bulk-action">
                            <option value="">Select action...</option>
                            <option value="sync">Sync selected products</option>
                            <option value="delete">Delete selected products</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="button" id="apply-bulk" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#select-all').change(function() {
        $('.product-select').prop('checked', $(this).prop('checked'));
    });
    
    // Sync product button
    $('.sync-product').click(function() {
        const productId = $(this).data('id');
        const button = $(this);
        
        button.html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wps_sync_product',
                product_id: productId,
                nonce: '<?php echo wp_create_nonce('wps_product_actions'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to show synced status
                    button.closest('tr').find('td:nth-child(6)').html('<span class="badge bg-success">Synced</span>');
                    button.closest('tr').find('td:nth-child(7)').text(response.data.last_sync);
                } else {
                    alert('Error: ' + response.data.message);
                }
                button.html('<i class="fas fa-sync"></i>');
            },
            error: function() {
                alert('Connection error. Please try again.');
                button.html('<i class="fas fa-sync"></i>');
            }
        });
    });
    
    // View product details (placeholder for modal)
    $('.view-product').click(function() {
        const productId = $(this).data('id');
        alert('View product details for ID: ' + productId + '\nThis would open a modal with product details.');
    });
    
    // Apply bulk action
    $('#apply-bulk').click(function() {
        const action = $('#bulk-action').val();
        if (!action) {
            alert('Please select an action.');
            return;
        }
        
        const selectedIds = [];
        $('.product-select:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Please select at least one product.');
            return;
        }
        
        if (action === 'delete' && !confirm('Are you sure you want to delete the selected products?')) {