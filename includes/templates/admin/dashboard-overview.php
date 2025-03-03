<div class="wrap">
    <h1>WP WooCommerce Printify Sync Dashboard</h1>
    <div class="dashboard-widgets-wrap">
        <!-- Sync Status Widget -->
        <div class="dashboard-widget">
            <h2>Sync Status</h2>
            <p>Last Sync: <?php echo esc_html($last_sync_date); ?></p>
            <p>Status: <?php echo esc_html($last_sync_status); ?></p>
        </div>
        <!-- Quick Access Widgets -->
        <div class="dashboard-widget">
            <h2>Quick Access</h2>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=printify-sync-settings'); ?>">Settings</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=printify-import-products'); ?>">Import Products</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=printify-export-products'); ?>">Export Products</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=printify-sync-logs'); ?>">View Sync Logs</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=printify-exchange-rates'); ?>">Manage Exchange Rates</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=printify-postman-integration'); ?>">Postman Integration</a></li>
            </ul>
        </div>
    </div>
</div>