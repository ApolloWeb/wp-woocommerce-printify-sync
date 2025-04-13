<?php
/**
 * Dashboard template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Set page title
$this->set('title', __('Dashboard', 'wp-woocommerce-printify-sync'));
?>

<div class="wpwps-dashboard">
    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <!-- Toasts will be added here dynamically -->
    </div>

    <!-- Stats Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="stat-label text-muted fw-normal"><?php esc_html_e('Products Synced', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="stat-number mb-0" id="products-count">0</h3>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle">
                            <i class="fas fa-box text-primary"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-3">
                        <div class="progress-bar bg-primary" id="products-progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="stat-label text-muted fw-normal"><?php esc_html_e('Orders Processed', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="stat-number mb-0" id="orders-count">0</h3>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle">
                            <i class="fas fa-shopping-cart text-success"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-3">
                        <div class="progress-bar bg-success" id="orders-progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="stat-label text-muted fw-normal"><?php esc_html_e('Support Tickets', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="stat-number mb-0" id="tickets-count">0</h3>
                        </div>
                        <div class="stat-icon bg-warning-light rounded-circle">
                            <i class="fas fa-ticket-alt text-warning"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-3">
                        <div class="progress-bar bg-warning" id="tickets-progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="stat-label text-muted fw-normal"><?php esc_html_e('Sync Status', 'wp-woocommerce-printify-sync'); ?></h6>
                            <div class="d-flex align-items-center">
                                <h3 class="stat-number mb-0" id="sync-status-icon"><i class="fas fa-check-circle text-success"></i></h3>
                                <span class="ms-2 stat-status" id="sync-status-text"><?php esc_html_e('Synced', 'wp-woocommerce-printify-sync'); ?></span>
                            </div>
                        </div>
                        <div class="stat-icon bg-info-light rounded-circle">
                            <i class="fas fa-sync text-info"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="small text-muted" id="last-sync-time"><?php esc_html_e('Last sync: Never', 'wp-woocommerce-printify-sync'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables -->
    <div class="row mb-4">
        <!-- Sales Overview Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php esc_html_e('Sales Overview', 'wp-woocommerce-printify-sync'); ?></h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartPeriodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php esc_html_e('This Month', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="chartPeriodDropdown">
                                <li><a class="dropdown-item chart-period" data-period="week" href="#"><?php esc_html_e('This Week', 'wp-woocommerce-printify-sync'); ?></a></li>
                                <li><a class="dropdown-item chart-period active" data-period="month" href="#"><?php esc_html_e('This Month', 'wp-woocommerce-printify-sync'); ?></a></li>
                                <li><a class="dropdown-item chart-period" data-period="year" href="#"><?php esc_html_e('This Year', 'wp-woocommerce-printify-sync'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Sync Activity -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php esc_html_e('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h5>
                        <button class="btn btn-sm btn-primary" id="refresh-activity">
                            <i class="fas fa-sync-alt"></i> <?php esc_html_e('Refresh', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="activity-list">
                        <div class="list-group-item list-group-item-action p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php esc_html_e('No recent activity', 'wp-woocommerce-printify-sync'); ?></h6>
                                <small class="text-muted">-</small>
                            </div>
                            <p class="mb-1 text-muted"><?php esc_html_e('Activities will appear here as they occur.', 'wp-woocommerce-printify-sync'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product & Order Management -->
    <div class="row mb-4">
        <!-- Product Status -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php esc_html_e('Product Status', 'wp-woocommerce-printify-sync'); ?></h5>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>" class="btn btn-sm btn-outline-primary">
                            <?php esc_html_e('View All', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="status-circle mx-auto mb-2">
                                <span id="synced-count">0</span>
                            </div>
                            <h6 class="text-muted"><?php esc_html_e('Synced', 'wp-woocommerce-printify-sync'); ?></h6>
                        </div>
                        <div class="col-md-4">
                            <div class="status-circle bg-warning mx-auto mb-2">
                                <span id="pending-count">0</span>
                            </div>
                            <h6 class="text-muted"><?php esc_html_e('Pending', 'wp-woocommerce-printify-sync'); ?></h6>
                        </div>
                        <div class="col-md-4">
                            <div class="status-circle bg-danger mx-auto mb-2">
                                <span id="error-count">0</span>
                            </div>
                            <h6 class="text-muted"><?php esc_html_e('Errors', 'wp-woocommerce-printify-sync'); ?></h6>
                        </div>
                    </div>
                    <div class="mt-4">
                        <canvas id="productStatusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php esc_html_e('Recent Orders', 'wp-woocommerce-printify-sync'); ?></h5>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="btn btn-sm btn-outline-primary">
                            <?php esc_html_e('View All', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th><?php esc_html_e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php esc_html_e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php esc_html_e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="recent-orders">
                                <tr>
                                    <td colspan="4" class="text-center py-3">
                                        <div class="text-muted"><?php esc_html_e('No recent orders found', 'wp-woocommerce-printify-sync'); ?></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and System Status -->
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><?php esc_html_e('Quick Actions', 'wp-woocommerce-printify-sync'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-light border w-100 h-100 py-3 action-btn" id="sync-products">
                                <i class="fas fa-sync text-primary d-block mb-2"></i>
                                <?php esc_html_e('Sync Products', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-light border w-100 h-100 py-3 action-btn" id="sync-orders">
                                <i class="fas fa-shopping-cart text-success d-block mb-2"></i>
                                <?php esc_html_e('Sync Orders', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-light border w-100 h-100 py-3 action-btn" id="check-shipping">
                                <i class="fas fa-truck text-info d-block mb-2"></i>
                                <?php esc_html_e('Update Shipping', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-light border w-100 h-100 py-3 action-btn" id="check-connection">
                                <i class="fas fa-link text-warning d-block mb-2"></i>
                                <?php esc_html_e('Check Connection', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><?php esc_html_e('System Status', 'wp-woocommerce-printify-sync'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <tbody>
                                <tr>
                                    <td class="border-0">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-server text-muted me-2"></i>
                                            <?php esc_html_e('API Connection', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    </td>
                                    <td class="border-0 text-end"><span class="badge bg-success" id="api-status"><?php esc_html_e('Connected', 'wp-woocommerce-printify-sync'); ?></span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-robot text-muted me-2"></i>
                                            <?php esc_html_e('ChatGPT API', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="badge bg-success" id="chatgpt-status"><?php esc_html_e('Connected', 'wp-woocommerce-printify-sync'); ?></span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-check text-muted me-2"></i>
                                            <?php esc_html_e('Next Scheduled Sync', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    </td>
                                    <td class="text-end" id="next-sync"><?php esc_html_e('In 6 hours', 'wp-woocommerce-printify-sync'); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-envelope text-muted me-2"></i>
                                            <?php esc_html_e('Email Queue', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    </td>
                                    <td class="text-end" id="email-queue"><?php esc_html_e('0 pending', 'wp-woocommerce-printify-sync'); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-database text-muted me-2"></i>
                                            <?php esc_html_e('Database Status', 'wp-woocommerce-printify-sync'); ?>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="badge bg-success" id="db-status"><?php esc_html_e('Healthy', 'wp-woocommerce-printify-sync'); ?></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
