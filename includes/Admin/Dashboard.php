<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Admin Dashboard Page
 */
class Dashboard extends AdminPage {
    /**
     * Constructor
     */
    public function __construct() {
        $this->page_title = __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync');
        $this->menu_title = __('Printify Sync', 'wp-woocommerce-printify-sync');
        $this->menu_slug = 'wpwps-dashboard';
        $this->parent_slug = ''; // Empty to create top-level menu
        $this->icon = 'dashicons-admin-generic'; // Will be overridden with Font Awesome
        $this->position = 58; // Just after WooCommerce
    }
    
    /**
     * Initialize the dashboard
     */
    public function init() {
        parent::init();
        
        // Register dashboard-specific hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_scripts']);
    }
    
    /**
     * Enqueue dashboard-specific scripts
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_dashboard_scripts($hook) {
        if ('woocommerce_page_' . $this->menu_slug !== $hook) {
            return;
        }
        
        // Enqueue dashboard-specific scripts
        wp_enqueue_script(
            WPWPS_ASSET_PREFIX . 'dashboard',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'dashboard.js',
            [WPWPS_ASSET_PREFIX . 'admin-scripts'],
            WPWPS_VERSION,
            true
        );
    }
    
    /**
     * Render the dashboard content
     */
    protected function render_content() {
        ?>
        <div class="container-fluid py-4">
            <!-- Summary Cards Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-tshirt fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <h5 class="fw-bold mb-1">237</h5>
                                    <p class="text-muted mb-0">Total Products</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-sync-alt fa-2x text-success"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <h5 class="fw-bold mb-1">24</h5>
                                    <p class="text-muted mb-0">Synced Today</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <h5 class="fw-bold mb-1">3</h5>
                                    <p class="text-muted mb-0">Sync Errors</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-shopping-cart fa-2x text-info"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <h5 class="fw-bold mb-1">42</h5>
                                    <p class="text-muted mb-0">Orders Processed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Sync Activity</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="syncChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Product Categories</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Activity</h5>
                            <button class="btn btn-sm btn-outline-primary">View All</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <img src="https://via.placeholder.com/40" alt="Product" class="rounded">
                                                    </div>
                                                    <div class="ms-3">
                                                        <p class="fw-bold mb-0">Classic T-Shirt</p>
                                                        <small class="text-muted">SKU: PRN-001</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Sync</td>
                                            <td><span class="badge bg-success">Success</span></td>
                                            <td>Today, 10:30 AM</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <img src="https://via.placeholder.com/40" alt="Product" class="rounded">
                                                    </div>
                                                    <div class="ms-3">
                                                        <p class="fw-bold mb-0">Hoodie Premium</p>
                                                        <small class="text-muted">SKU: PRN-002</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Order</td>
                                            <td><span class="badge bg-warning">Processing</span></td>
                                            <td>Today, 09:15 AM</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <img src="https://via.placeholder.com/40" alt="Product" class="rounded">
                                                    </div>
                                                    <div class="ms-3">
                                                        <p class="fw-bold mb-0">Coffee Mug</p>
                                                        <small class="text-muted">SKU: PRN-003</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Sync</td>
                                            <td><span class="badge bg-danger">Failed</span></td>
                                            <td>Yesterday, 2:45 PM</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
