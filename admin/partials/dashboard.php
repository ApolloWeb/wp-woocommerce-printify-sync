<?php
/**
 * Dashboard template for a visually stunning modern WordPress Admin Dashboard.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */
?>
<link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__).'../../admin/css/dashboard.css'; ?>">
<div class="dashboard-container">
    <header class="dashboard-header">
        <h1>Modern Admin Dashboard</h1>
        <p>Welcome, ApolloWeb! Here's your overview with the latest insights.</p>
    </header>
    <section class="dashboard-grid">
        <article class="card card-stats">
            <h2>Product Sync</h2>
            <p>Last Sync: 1 hour ago</p>
            <button id="sync-products-btn" class="button-primary">Sync Now</button>
        </article>
        <article class="card card-stats">
            <h2>Inventory Status</h2>
            <p>98% Stock Available</p>
        </article>
        <article class="card card-alert">
            <h2>Alerts</h2>
            <p>No recent alerts.</p>
        </article>
        <article class="card card-logs">
            <h2>Recent Logs</h2>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Sample logs. Replace this with dynamic logs as needed.
                    $logs = [
                        ['time' => '2025-03-01 13:45', 'type' => 'Info', 'message' => 'Product sync completed.'],
                        ['time' => '2025-03-01 12:30', 'type' => 'Warning', 'message' => 'Inventory discrepancy detected.']
                    ];
                    foreach ($logs as $log) {
                        echo "<tr>";
                        echo "<td>" . esc_html($log['time']) . "</td>";
                        echo "<td>" . esc_html($log['type']) . "</td>";
                        echo "<td>" . esc_html($log['message']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </article>
    </section>
</div>
<script src="<?php echo plugin_dir_url(__FILE__).'../../admin/js/dashboard.js'; ?>"></script>