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