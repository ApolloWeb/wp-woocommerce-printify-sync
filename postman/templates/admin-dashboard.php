<div class="container">
    <div class="main-content-container container-fluid px-4">
        <!-- Page Header -->
        <div class="page-header row no-gutters py-4">
            <div class="col-12 col-sm-4 text-center text-sm-left mb-0">
                <h3 class="page-title"><?php _e('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h3>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-lg-8 col-md-12">
                <div class="card card-small mb-4">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Sales Chart', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body pt-0">
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

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Product Sync Status', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-6 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">2025-03-01 12:00:00</span>
                                </div>
                                <div class="col-6 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Total Products Synced', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">150</span>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="<?php echo admin_url('admin.php?page=printify-sync-product'); ?>" class="btn btn-primary btn-sm"><?php _e('Go to Product Sync', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Order Sync Status', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-6 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">2025-03-01 12:30:00</span>
                                </div>
                                <div class="col-6 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Total Orders Synced', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">250</span>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="<?php echo admin_url('admin.php?page=printify-sync-order'); ?>" class="btn btn-primary btn-sm"><?php _e('Go to Order Sync', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Error Logs', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Total Errors', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">5</span>
                                    <ul class="list-group mt-2">
                                        <li class="list-group-item"><?php _e('Error 1: Failed to sync product ID 12345', 'wp-woocommerce-printify-sync'); ?></li>
                                        <li class="list-group-item"><?php _e('Error 2: Failed to sync order ID 67890', 'wp-woocommerce-printify-sync'); ?></li>
                                        <li class="list-group-item"><?php _e('Error 3: API key invalid', 'wp-woocommerce-printify-sync'); ?></li>
                                        <li class="list-group-item"><?php _e('Error 4: Network timeout', 'wp-woocommerce-printify-sync'); ?></li>
                                        <li class="list-group-item"><?php _e('Error 5: Unknown error occurred', 'wp-woocommerce-printify-sync'); ?></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="<?php echo admin_url('admin.php?page=printify-sync-error-logs'); ?>" class="btn btn-danger btn-sm"><?php _e('Go to Error Logs', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Exchange Rates', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Last Updated', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">2025-03-01 12:00:00</span>
                                </div>
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Total Currencies', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">5</span>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="<?php echo admin_url('admin.php?page=printify-sync-exchange-rate'); ?>" class="btn btn-primary btn-sm"><?php _e('Go to Exchange Rates', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Support Tickets', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Total Open Tickets', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">2</span>
                                </div>
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Total Closed Tickets', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold">5</span>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="<?php echo admin_url('admin.php?page=printify-sync-tickets'); ?>" class="btn btn-primary btn-sm"><?php _e('Go to Support Tickets', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('API Keys Status', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold"><?php _e('Valid', 'wp-woocommerce-printify-sync'); ?></span>
                                </div>
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('WooCommerce API Key', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <span class="text-dark font-weight-bold"><?php _e('Valid', 'wp-woocommerce-printify-sync'); ?></span>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="<?php echo admin_url('admin.php?page=printify-sync-settings'); ?>" class="btn btn-primary btn-sm"><?php _e('Go to API Keys Settings', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Recent Activities', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="container-fluid px-0">
                            <div class="row no-gutters border-bottom">
                                <div class="col-12 text-center py-3">
                                    <h5 class="text-uppercase text-muted mb-0"><?php _e('Recent Activities', 'wp-woocommerce-printify-sync'); ?></h5>
                                </div>
                                <div class="col-12 text-center py-3">
                                    <ul class="list-group">
                                        <li class="list-group-item"><?php _e('Product ID 12345 synced successfully.', 'wp-woocommerce-printify-sync'); ?></li>
                                        <li class="list-group-item"><?php _e('Order ID 67890 synced successfully.', 'wp-woocommerce-printify-sync'); ?></li>
                                        <li class="list-group-item"><?php _e('Exchange rate for EUR updated.', 'wp-woocommerce-printify-sync'); ?></li>
                                        <!-- Additional dummy activities can be added here -->
                                    </ul>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="col-12 text-center py-3">
                                    <a href="#" class="btn btn-primary btn-sm"><?php _e('View All Activities', 'wp-woocommerce-printify-sync'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-small">
                    <div class="card-header border-bottom">
                        <h6 class="m-0"><?php _e('Dashboard Overview', 'wp-woocommerce-printify-sync'); ?></h6>
                    </div>
                    <div class="card-body