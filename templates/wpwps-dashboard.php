<?php 
defined('ABSPATH') || exit;

$active_page = 'dashboard';
$title = __('Dashboard - Printify Sync', 'wp-woocommerce-printify-sync');

ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4"><?php echo esc_html__('Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Stats Cards -->
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stats-info">
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Products', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h2 class="mt-2 mb-0">128</h2>
                        <p class="text-success mb-0">
                            <i class="fa-solid fa-arrow-up me-1"></i>
                            <span>12% <?php echo esc_html__('This Week', 'wp-woocommerce-printify-sync'); ?></span>
                        </p>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-shirt fs-3 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stats-info">
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Orders', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h2 class="mt-2 mb-0">45</h2>
                        <p class="text-success mb-0">
                            <i class="fa-solid fa-arrow-up me-1"></i>
                            <span>8% <?php echo esc_html__('This Week', 'wp-woocommerce-printify-sync'); ?></span>
                        </p>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-cart-shopping fs-3 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stats-info">
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('Revenue', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h2 class="mt-2 mb-0">$4,582</h2>
                        <p class="text-danger mb-0">
                            <i class="fa-solid fa-arrow-down me-1"></i>
                            <span>3% <?php echo esc_html__('This Week', 'wp-woocommerce-printify-sync'); ?></span>
                        </p>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-dollar-sign fs-3 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card card-wpwps h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stats-info">
                        <h5 class="card-title text-muted mb-0"><?php echo esc_html__('API Calls', 'wp-woocommerce-printify-sync'); ?></h5>
                        <h2 class="mt-2 mb-0">2,430</h2>
                        <p class="text-success mb-0">
                            <i class="fa-solid fa-check-circle me-1"></i>
                            <span><?php echo esc_html__('All Systems Normal', 'wp-woocommerce-printify-sync'); ?></span>
                        </p>
                    </div>
                    <div class="stats-icon bg-light rounded p-3">
                        <i class="fa-solid fa-code fs-3 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart & Recent Activity -->
    <div class="col-lg-8">
        <div class="card card-wpwps h-100">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo esc_html__('Orders Overview', 'wp-woocommerce-printify-sync'); ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartTimeRange" data-bs-toggle="dropdown">
                            <?php echo esc_html__('Last 30 Days', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="chartTimeRange">
                            <li><a class="dropdown-item" href="#"><?php echo esc_html__('Last 7 Days', 'wp-woocommerce-printify-sync'); ?></a></li>
                            <li><a class="dropdown-item active" href="#"><?php echo esc_html__('Last 30 Days', 'wp-woocommerce-printify-sync'); ?></a></li>
                            <li><a class="dropdown-item" href="#"><?php echo esc_html__('Last Quarter', 'wp-woocommerce-printify-sync'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="ordersChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-wpwps h-100">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0"><?php echo esc_html__('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex">
                            <div class="activity-icon me-3 bg-success-subtle p-2 rounded">
                                <i class="fa-solid fa-check text-success"></i>
                            </div>
                            <div>
                                <p class="mb-1 fw-bold"><?php echo esc_html__('Order #1234 fulfilled', 'wp-woocommerce-printify-sync'); ?></p>
                                <p class="text-muted small mb-0"><?php echo esc_html__('20 minutes ago', 'wp-woocommerce-printify-sync'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex">
                            <div class="activity-icon me-3 bg-primary-subtle p-2 rounded">
                                <i class="fa-solid fa-sync text-primary"></i>
                            </div>
                            <div>
                                <p class="mb-1 fw-bold"><?php echo esc_html__('Products synchronized', 'wp-woocommerce-printify-sync'); ?></p>
                                <p class="text-muted small mb-0"><?php echo esc_html__('45 minutes ago', 'wp-woocommerce-printify-sync'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex">
                            <div class="activity-icon me-3 bg-warning-subtle p-2 rounded">
                                <i class="fa-solid fa-exclamation-triangle text-warning"></i>
                            </div>
                            <div>
                                <p class="mb-1 fw-bold"><?php echo esc_html__('API rate limit at 80%', 'wp-woocommerce-printify-sync'); ?></p>
                                <p class="text-muted small mb-0"><?php echo esc_html__('1 hour ago', 'wp-woocommerce-printify-sync'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex">
                            <div class="activity-icon me-3 bg-info-subtle p-2 rounded">
                                <i class="fa-solid fa-shopping-cart text-info"></i>
                            </div>
                            <div>
                                <p class="mb-1 fw-bold"><?php echo esc_html__('New order #1235 received', 'wp-woocommerce-printify-sync'); ?></p>
                                <p class="text-muted small mb-0"><?php echo esc_html__('3 hours ago', 'wp-woocommerce-printify-sync'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Products Table -->
    <div class="col-12">
        <div class="card card-wpwps">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo esc_html__('Recent Products', 'wp-woocommerce-printify-sync'); ?></h5>
                    <a href="?page=wpwps-products" class="btn btn-sm btn-outline-primary">
                        <?php echo esc_html__('View All', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo esc_html__('Product', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('SKU', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Price', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Stock', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Last Updated', 'wp-woocommerce-printify-sync'); ?></th>
                                <th scope="col"><?php echo esc_html__('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="square-img-container me-3">
                                            <img src="https://via.placeholder.com/50" width="50" height="50" alt="Product image">
                                        </div>
                                        <span>Vintage T-Shirt</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-success">Published</span></td>
                                <td>TSHIRT-001</td>
                                <td>$24.99</td>
                                <td>In Stock</td>
                                <td>2023-08-15</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="square-img-container me-3">
                                            <img src="https://via.placeholder.com/50" width="50" height="50" alt="Product image">
                                        </div>
                                        <span>Premium Hoodie</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-warning text-dark">Draft</span></td>
                                <td>HOODIE-003</td>
                                <td>$39.99</td>
                                <td>Low Stock</td>
                                <td>2023-08-14</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="square-img-container me-3">
                                            <img src="https://via.placeholder.com/50" width="50" height="50" alt="Product image">
                                        </div>
                                        <span>Canvas Tote Bag</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-success">Published</span></td>
                                <td>BAG-024</td>
                                <td>$19.99</td>
                                <td>In Stock</td>
                                <td>2023-08-10</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
