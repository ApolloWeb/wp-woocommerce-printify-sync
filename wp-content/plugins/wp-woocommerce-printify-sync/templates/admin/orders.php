<?php
/**
 * Admin Orders Template
 *
 * @var array $orders
 * @var array $pagination
 * @var array $stats
 * @var array $statuses
 * @var string $currentStatus
 * @var string $search
 */
?>

<div class="wrap wpwps-orders">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-shopping-cart me-2"></i>
            <?php echo esc_html(get_admin_page_title()); ?>
        </h1>
        <div class="header-actions">
            <button class="wpwps-btn wpwps-btn-primary" id="syncAllOrders">
                <i class="fas fa-sync-alt me-1"></i>
                <?php _e('Sync All Orders', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <?php foreach ($stats as $key => $stat): ?>
            <div class="col-md-6 col-xl-3">
                <div class="wpwps-card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-<?php echo esc_attr($stat['color']); ?>">
                                <i class="fas fa-<?php echo esc_attr($stat['icon']); ?>"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?php echo esc_html($stat['label']); ?></h6>
                                <h3 class="mb-0"><?php echo esc_html($stat['value']); ?></h3>
                            </div>
                        </div>
                        <?php if (isset($stat['trend'])): ?>
                            <div class="mt-3">
                                <span class="trend-<?php echo $stat['trend'] >= 0 ? 'up' : 'down'; ?>">
                                    <i class="fas fa-arrow-<?php echo $stat['trend'] >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo sprintf(__('%s%% from last period', 'wp-woocommerce-printify-sync'), abs($stat['trend'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters and Search -->
    <div class="wpwps-card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="status-tabs">
                        <?php foreach ($statuses as $status => $label): ?>
                            <a href="<?php echo esc_url(add_query_arg('status', $status)); ?>" 
                               class="status-tab <?php echo $currentStatus === $status ? 'active' : ''; ?>">
                                <?php echo esc_html($label); ?>
                                <span class="badge bg-secondary ms-1">
                                    <?php echo esc_html($stats[$status]['count'] ?? 0); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <form class="d-flex gap-2">
                        <input type="hidden" name="page" value="wpwps-orders">
                        <input type="hidden" name="status" value="<?php echo esc_attr($currentStatus); ?>">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   name="s" 
                                   class="form-control" 
                                   placeholder="<?php esc_attr_e('Search orders...', 'wp-woocommerce-printify-sync'); ?>"
                                   value="<?php echo esc_attr($search); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?php _e('Search', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="wpwps-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Customer', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Printify Status', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <h5><?php _e('No Orders Found', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <p class="text-muted">
                                        <?php _e('No orders match your search criteria.', 'wp-woocommerce-printify-sync'); ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): ?>
                        <tr data-order-id="<?php echo esc_attr($order['id']); ?>">
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url($order['edit_url']); ?>">
                                        #<?php echo esc_html($order['number']); ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo esc_url(get_avatar_url($order['customer_email'])); ?>" 
                                         class="rounded-circle me-2" 
                                         width="32" 
                                         alt="">
                                    <div>
                                        <?php echo esc_html($order['customer_name']); ?>
                                        <small class="d-block text-muted">
                                            <?php echo esc_html($order['customer_email']); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo esc_attr($order['status_color']); ?>">
                                    <?php echo esc_html($order['status_label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo wc_price($order['total']); ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><?php echo esc_html($order['date_created']); ?></span>
                                    <small class="text-muted">
                                        <?php echo esc_html(human_time_diff(strtotime($order['date_created']))); ?> ago
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="printify-status">
                                    <span class="status-dot bg-<?php echo esc_attr($order['printify_status_color']); ?>"></span>
                                    <?php echo esc_html($order['printify_status']); ?>
                                </div>
                                <?php if ($order['tracking_number']): ?>
                                    <small class="d-block mt-1">
                                        <i class="fas fa-truck me-1"></i>
                                        <?php echo esc_html($order['tracking_number']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="<?php echo esc_url($order['edit_url']); ?>" class="dropdown-item">
                                                <i class="fas fa-eye me-2"></i>
                                                <?php _e('View', 'wp-woo