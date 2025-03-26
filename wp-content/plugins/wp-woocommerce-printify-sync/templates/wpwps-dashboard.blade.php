<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <div class="wpwps-navbar sticky-top mb-4 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="wp-heading-inline">
                <i class="fas fa-tshirt me-2"></i>
                <?php echo esc_html__('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?>
            </h1>
            <div class="d-flex align-items-center">
                <div class="dropdown me-3">
                    <button class="btn wpwps-btn wpwps-btn-primary dropdown-toggle" type="button" id="quickActions" data-bs-toggle="dropdown">
                        <i class="fas fa-bolt me-1"></i> <?php echo esc_html__('Quick Actions', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="syncProducts"><i class="fas fa-sync me-2"></i><?php echo esc_html__('Sync Products', 'wp-woocommerce-printify-sync'); ?></a></li>
                        <li><a class="dropdown-item" href="#" id="syncOrders"><i class="fas fa-shopping-cart me-2"></i><?php echo esc_html__('Sync Orders', 'wp-woocommerce-printify-sync'); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="checkAPIHealth"><i class="fas fa-heartbeat me-2"></i><?php echo esc_html__('Check API Health', 'wp-woocommerce-printify-sync'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="wpwps-container">
        <!-- Stats Overview -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="wpwps-card p-3">
                    <h6 class="text-muted mb-2"><?php echo esc_html__('Total Products', 'wp-woocommerce-printify-sync'); ?></h6>
                    <h3 class="mb-0" id="totalProducts">--</h3>
                    <small class="text-muted" id="productsChange">--</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="wpwps-card p-3">
                    <h6 class="text-muted mb-2"><?php echo esc_html__('Active Orders', 'wp-woocommerce-printify-sync'); ?></h6>
                    <h3 class="mb-0" id="activeOrders">--</h3>
                    <small class="text-muted" id="ordersChange">--</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="wpwps-card p-3">
                    <h6 class="text-muted mb-2"><?php echo esc_html__('Open Tickets', 'wp-woocommerce-printify-sync'); ?></h6>
                    <h3 class="mb-0" id="openTickets">--</h3>
                    <small class="text-muted" id="ticketsChange">--</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="wpwps-card p-3">
                    <h6 class="text-muted mb-2"><?php echo esc_html__('API Health', 'wp-woocommerce-printify-sync'); ?></h6>
                    <div class="d-flex align-items-center">
                        <div id="apiHealth" class="spinner-border spinner-border-sm text-primary me-2"></div>
                        <span id="apiStatus">Checking...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="wpwps-card p-4">
                    <h5 class="card-title mb-4"><?php echo esc_html__('Sales Overview', 'wp-woocommerce-printify-sync'); ?></h5>
                    <div class="wpwps-chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="wpwps-card p-4">
                    <h5 class="card-title mb-4"><?php echo esc_html__('Product Categories', 'wp-woocommerce-printify-sync'); ?></h5>
                    <div class="wpwps-chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="wpwps-card p-4">
            <h5 class="card-title mb-4"><?php echo esc_html__('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h5>
            <div class="table-responsive">
                <table class="table wpwps-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Time', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Type', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Description', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="recentActivity">
                        <tr>
                            <td colspan="4" class="text-center"><?php echo esc_html__('Loading...', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>