<div class="wrap">
    <h1><?php _e('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="container">
        <div class="row">
            <!-- Sales Chart Widget -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-chart-line"></i> <?php _e('Sales Chart', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="sales-filter"><?php _e('Filter By:', 'wp-woocommerce-printify-sync'); ?></label>
                            <select id="sales-filter" class="form-control">
                                <option value="day"><?php _e('Day', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="week"><?php _e('Week', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="month"><?php _e('Month', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="year"><?php _e('Year', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                        </div>
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <!-- Product Sync Status Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-tshirt"></i> <?php _e('Product Sync Status', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('Sync your products from Printify to WooCommerce.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-product'); ?>" class="btn btn-primary">
                            <i class="dashicons dashicons-products"></i> <?php _e('Go to Product Sync', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Last Sync:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-info">2025-03-01 12:00:00</span></h5>
                            <h5><?php _e('Total Products Synced:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success">150</span></h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Order Sync Status Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-tshirt"></i> <?php _e('Order Sync Status', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('Sync your orders from Printify to WooCommerce.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-order'); ?>" class="btn btn-primary">
                            <i class="dashicons dashicons-products"></i> <?php _e('Go to Order Sync', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Last Sync:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-info">2025-03-01 12:30:00</span></h5>
                            <h5><?php _e('Total Orders Synced:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success">250</span></h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Error Logs Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-exclamation-triangle"></i> <?php _e('Error Logs', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('View and manage error logs.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-error-logs'); ?>" class="btn btn-danger">
                            <i class="dashicons dashicons-warning"></i> <?php _e('Go to Error Logs', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Total Errors:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-danger">5</span></h5>
                            <ul class="list-group mt-2">
                                <li class="list-group-item"><?php _e('Error 1: Failed to sync product ID 12345', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 2: Failed to sync order ID 67890', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 3: API key invalid', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 4: Network timeout', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 5: Unknown error occurred', 'wp-woocommerce-printify-sync'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Exchange Rates Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-exchange-alt"></i> <?php _e('Exchange Rates', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('View and manage exchange rates.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-exchange-rate'); ?>" class="btn btn-primary">
                            <i class="dashicons dashicons-chart-line"></i> <?php _e('Go to Exchange Rates', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Last Updated:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-info">2025-03-01 12:00:00</span></h5>
                            <h5><?php _e('Total Currencies:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success">5</span></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <!-- Support Tickets Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-ticket-alt"></i> <?php _e('Support Tickets', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('View and manage support tickets.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-tickets'); ?>" class="btn btn-primary">
                            <i class="dashicons dashicons-tickets-alt"></i> <?php _e('Go to Support Tickets', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Total Open Tickets:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-warning">2</span></h5>
                            <h5><?php _e('Total Closed Tickets:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success">5</span></h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- API Keys Status Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-key"></i> <?php _e('API Keys Status', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('Check the status of your API keys.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-settings'); ?>" class="btn btn-primary">
                            <i class="dashicons dashicons-admin-network"></i> <?php _e('Go to API Keys Settings', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Printify API Key:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success"><?php _e('Valid', 'wp-woocommerce-printify-sync'); ?></span></h5>
                            <h5><?php _e('WooCommerce API Key:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success"><?php _e('Valid', 'wp-woocommerce-printify-sync'); ?></span></h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Recent Activities Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-history"></i> <?php _e('Recent Activities', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('View recent sync activities.', 'wp-woocommerce-printify-sync'); ?></p>
                        <ul class="list-group">
                            <li class="list-group-item"><?php _e('Product ID 12345 synced successfully.', 'wp-woocommerce-printify-sync'); ?></li>
                            <li class="list-group-item"><?php _e('Order ID 67890 synced successfully.', 'wp-woocommerce-printify-sync'); ?></li>
                            <li class="list-group-item"><?php _e('Exchange rate for EUR updated.', 'wp-woocommerce-printify-sync'); ?></li>
                            <!-- Additional dummy activities can be added here -->
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Dashboard Overview Widget -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-woocommerce-purple text-white">
                        <i class="fas fa-chart-pie"></i> <?php _e('Dashboard Overview', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('Overview of the plugin status and metrics.', 'wp-woocommerce-printify-sync'); ?></p>
                        <ul class="list-group">
                            <li class="list-group-item"><?php _e('Total Products: 150', 'wp-woocommerce-printify-sync'); ?></li>
                            <li class="list-group-item"><?php _e('Total Orders: 250', 'wp-woocommerce-printify-sync'); ?></li>
                            <li class="list-group-item"><?php _e('Total Errors: 5', 'wp-woocommerce-printify-sync'); ?></li>
                            <!-- Additional dummy metrics can be added here -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>