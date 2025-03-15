<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class BulkOptimizationPage
{
    private string $currentTime = '2025-03-15 19:24:02';
    private string $currentUser = 'ApolloWeb';
    private BulkOptimizationService $optimizer;

    public function __construct()
    {
        $this->optimizer = new BulkOptimizationService();

        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_get_optimization_progress', [$this, 'getProgress']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'upload.php',
            'Bulk Optimize Images',
            'Bulk Optimize',
            'manage_options',
            'wpwps-bulk-optimize',
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        $bulkStatus = get_option('wpwps_bulk_optimization');
        $isRunning = $bulkStatus['status'] === 'running';
        ?>
        <div class="wrap">
            <h1>Bulk Image Optimization</h1>

            <?php if ($isRunning): ?>
                <div id="optimization-progress" class="notice notice-info">
                    <p>Optimization in progress: <span id="progress-percent">0</span>%</p>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: 0%"></div>
                    </div>
                    <p>
                        <button class="button" id="pause-optimization">Pause</button>
                    </p>
                </div>
            <?php else: ?>
                <form method="post" id="bulk-optimize-form">
                    <table class="form-table">
                        <tr>
                            <th>Date Range</th>
                            <td>
                                <input type="date" name="date_from" />
                                <p class="description">Only optimize images uploaded after this date</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Minimum Size</th>
                            <td>
                                <input type="number" name="min_size" /> KB
                                <p class="description">Only optimize images larger than this size</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('Start Optimization'); ?>
                </form>
            <?php endif; ?>

            <div id="optimization-stats" style="display: none;">
                <h2>Optimization Statistics</h2>
                <table class="widefat">
                    <tr>
                        <th>Processed</th>
                        <td id="stat-processed">0</td>
                    </tr>
                    <tr>
                        <th>Optimized</th>
                        <td id="stat-optimized">0</td>
                    </tr>
                    <tr>
                        <th>Skipped</th>
                        <td id="stat-skipped">0</td>
                    </tr>
                    <tr>
                        <th>Failed</th>
                        <td id="stat-failed">0</td>
                    </tr>
                    <tr>
                        <th>Total Savings</th>
                        <td id="stat-savings">0 KB</td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_script(
            'wpwps-bulk-optimize',
            plugins_url('assets/js/bulk-optimize.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('wpwps-bulk-optimize', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-bulk-optimize')
        ]);
    }

    public function getProgress(): void
    {
        check_ajax_referer('wpwps-bulk-optimize');

        $status = get_option('wpwps_bulk_optimization');
        $stats = get_option('wpwps_bulk_optimization_stats');

        wp_send_json_success([
            'progress' => $status['progress'] ?? 0,
            'status' => $status['status'] ?? 'idle',
            'stats' => $stats
        ]);
    }
}