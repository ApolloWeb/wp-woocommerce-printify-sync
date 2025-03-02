<div class="wrap">
    <h1>Printify Sync Dashboard</h1>
    
    <?php
    // Fetch the shop details
    $shop_name = get_option('wpwcs_shop_name', 'N/A');
    $shop_id = get_option('wpwcs_shop_id', 'N/A');
    $test_mode = get_option('wpwcs_test_mode', false);

    if ($test_mode) {
        echo '<div class="test-mode-banner">TEST MODE</div>';
    }
    ?>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?php echo esc_html($shop_name); ?></h3>
                    <p>Shop Name</p>
                </div>
                <div class="icon">
                    <i class="fas fa-store"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?php echo esc_html($shop_id); ?></h3>
                    <p>Shop ID</p>
                </div>
                <div class="icon">
                    <i class="fas fa-id-badge"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>API Status</h3>
                    <p>Active</p>
                </div>
                <div class="icon">
                    <i class="fas fa-plug"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>Shipping</h3>
                    <p>Profiles</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Order Summary</h3>
                </div>
                <div class="card-body">
                    <div id="order-summary">
                        <!-- Order summary will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sales Graph</h3>
                </div>
                <div class="card-body">
                    <canvas id="sales-graph" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Import Progress</h3>
                </div>
                <div class="card-body">
                    <div id="product-import-progress" style="width: 100px; margin: auto;"></div>
                </div>
            </div>
        </div>
    </div>
</div>