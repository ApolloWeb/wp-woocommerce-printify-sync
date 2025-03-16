<?php
/**
 * Admin Dashboard Template
 * 
 * @var array $stats
 * @var array $recentOrders
 * @var array $recentSyncs
 * @var array $apiStatus
 * @var array $errors
 * @var array $currency
 */
?>

<div class="wrap wpwps-dashboard">
    <!-- Header Section with Quick Stats -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-tshirt me-2"></i>
            <?php echo esc_html(get_admin_page_title()); ?>
        </h1>
        <div class="quick-actions">
            <button class="wpwps-btn wpwps-btn-primary me-2" id="syncNow">
                <i class="fas fa-sync-alt"></i> <?php _e('Sync Now', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <div class="btn-group">
                <button class="wpwps-btn wpwps-btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i> <?php _e('Actions', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#" class="dropdown-item" id="refreshRates">
                            <i class="fas fa-dollar-sign"></i> <?php _e('Update Exchange Rates', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="dropdown-item" id="checkAPI">
                            <i class="fas fa-plug"></i> <?php _e('Check API Status', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="dropdown-item">
                            <i class="fas fa-sliders-h"></i> <?php _e('Settings', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Live Status Bar -->
    <div class="wpwps-status-bar mb-4">
        <div class="row g-0">
            <?php foreach ($apiStatus as $service => $status): ?>
                <div class="col">
                    <div class="status-item <?php echo $status ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-<?php echo $this->getServiceIcon($service); ?>"></i>
                        <span class="service-name"><?php echo esc_html(ucfirst($service)); ?></span>
                        <span class="status-badge">
                            <?php if ($status): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle text-danger"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <!-- Orders Stats -->
        <div class="col-md-6 col-xl-3">
            <div class="wpwps-card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1"><?php _e('Today\'s Orders', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="mb-0"><?php echo esc_html($stats['orders']['today']); ?></h3>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo esc_attr($stats['orders']['progress']); ?>%"></div>
                    </div>
                    <small class="mt-2 d-block text-success">
                        <i class="fas fa-arrow-up"></i> 
                        <?php echo sprintf(__('%s%% from yesterday', 'wp-woocommerce-printify-sync'), $stats['orders']['growth']); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Revenue Stats -->
        <div class="col-md-6 col-xl-3">
            <div class="wpwps-card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1"><?php _e('Revenue', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="mb-0"><?php echo wc_price($stats['revenue']['today']); ?></h3>
                        </div>
                    </div>
                    <canvas id="revenueChart" height="50"></canvas>
                    <small class="mt-2 d-block text-<?php echo $stats['revenue']['growth'] >= 0 ? 'success' : 'danger'; ?>">
                        <i class="fas fa-arrow-<?php echo $stats['revenue']['growth'] >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo sprintf(__('%s%% from last week', 'wp-woocommerce-printify-sync'), abs($stats['revenue']['growth'])); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Products Stats -->
        <div class="col-md-6 col-xl-3">
            <div class="wpwps-card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1"><?php _e('Products', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="mb-0"><?php echo esc_html($stats['products']['total']); ?></h3>
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between">
                        <div class="status-pill">
                            <span class="badge bg-success"><?php echo esc_html($stats['products']['synced']); ?> Synced</span>
                        </div>
                        <div class="status-pill">
                            <span class="badge bg-warning"><?php echo esc_html($stats['products']['pending']); ?> Pending</span>
                        </div>
                        <div class="status-pill">
                            <span class="badge bg-danger"><?php echo esc_html($stats['products']['errors']); ?> Errors</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Stats -->
        <div class="col-md-6 col-xl-3">
            <div class="wpwps-card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning">
                            <i class="fas fa-sync"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1"><?php _e('Next Sync', 'wp-woocommerce-printify-sync'); ?></h6>
                            <h3 class="mb-0 countdown" data-time="<?php echo esc_attr($stats['sync']['next']); ?>">
                                --:--:--
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></span>
                            <span class="text-muted"><?php echo esc_html(human_time_diff(strtotime($stats['sync']['last']))); ?> ago</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span><?php _e('Success Rate', 'wp-woocommerce-printify-sync'); ?></span>
                            <span class="text-<?php echo $stats['sync']['success_rate'] > 90 ? 'success' : 'warning'; ?>">
                                <?php echo esc_html($stats['sync']['success_rate']); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="wpwps-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php _e('Sales Overview', 'wp-woocommerce-printify-sync'); ?></h5>
                    <div class="chart-actions">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm active" data-period="week">Week</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="month">Month</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="year">Year</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="wpwps-card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php _e('Top Products', 'wp-woocommerce-printify-sync'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="productsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="wpwps-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php _e('Recent Orders', 'wp-woocommerce-printify-sync'); ?></h5>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="btn btn-sm btn-primary">
                        <?php _e('View All', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Customer', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Amount', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo esc_html($order['number']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo esc_url(get_avatar_url($order['customer_email'])); ?>" class="rounded-circle me-2" width="32">
                                                <div>
                                                    <?php echo esc_html($order['customer_name']); ?>
                                                    <small class="d-block text-muted"><?php echo esc_html($order['customer_email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo esc_attr($this->getStatusColor($order['status'])); ?>">
                                                <?php echo esc_html($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo wc_price($order['total']); ?></td>
                                        <td><?php echo esc_html(human_time_diff(strtotime($order['date_created']))); ?> ago</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="<?php echo esc_url($order['edit_url']); ?>" class="dropdown-item">
                                                            <i class="fas fa-eye me-2"></i> <?php _e('View', 'wp-woocommerce-printify-sync'); ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                