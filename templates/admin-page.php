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
        <div class="col s12 m6 l3">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Shop Name</span>
                    <p><?php echo esc_html($shop_name); ?></p>
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Shop ID</span>
                    <p><?php echo esc_html($shop_id); ?></p>
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">API Status</span>
                    <p>Active</p>
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Shipping Profiles</span>
                    <p>Active</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col s12 m6">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Order Summary</span>
                    <div id="order-summary">
                        <!-- Order summary will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col s12 m6">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Sales Graph</span>
                    <div class="chart-container">
                        <canvas id="sales-graph"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col s12 m6">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Product Import Progress</span>
                    <div id="product-import-progress"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Trigger -->
    <a class="waves-effect waves-light btn modal-trigger" href="#alert-modal">Show Alerts</a>

    <!-- Modal Structure -->
    <div id="alert-modal" class="modal">
        <div class="modal-content">
            <h4>Alerts</h4>
            <p>This is a sample alert message.</p>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        </div>
    </div>
</div>